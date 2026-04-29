<?php

namespace Core;

use PDO;

class AuthThrottle
{
    private const TABLE = 'login_attempts';
    private const TABLE_IP = 'login_attempts_ip';
    private const CLEANUP_INTERVAL_SECONDS = 300;
    private const CLEANUP_RETENTION_SECONDS = 86400;
    private static int $lastCleanupAt = 0;
    private int $maxAttempts;
    private int $maxIpAttempts;
    private int $windowSeconds;
    private int $blockSeconds;
    private PDO $db;

    public function __construct(
        int $maxAttempts = 5,
        int $maxIpAttempts = 20,
        int $windowSeconds = 900,
        int $blockSeconds = 900
    ) {
        $this->maxAttempts  = $maxAttempts;
        $this->maxIpAttempts = $maxIpAttempts;
        $this->windowSeconds = $windowSeconds;
        $this->blockSeconds  = $blockSeconds;
        $this->db = Database::getConnection();
    }

    public function isBlocked(string $email, string $ip): bool
    {
        $stmt = $this->db->prepare('
            SELECT blocked_until
            FROM ' . self::TABLE . '
            WHERE email_hash = :email_hash AND ip_address = :ip
            LIMIT 1
        ');
        $stmt->execute([
            ':email_hash' => $this->hashEmail($email),
            ':ip'         => $ip,
        ]);
        $row = $stmt->fetch();

        if (!$row || empty($row->blocked_until)) {
            return false;
        }

        $blockedUntil = strtotime($row->blocked_until);
        if ($blockedUntil > time()) {
            return true;
        }

        // Blocage expiré : on nettoie
        $this->deleteEntry($email, $ip);
        return false;
    }

    public function registerFailure(string $email, string $ip): void
    {
        $this->maybeCleanupExpiredEntries();

        $now = date('Y-m-d H:i:s');
        $emailHash = $this->hashEmail($email);

        $stmt = $this->db->prepare('
            SELECT *
            FROM ' . self::TABLE . '
            WHERE email_hash = :email_hash AND ip_address = :ip
            LIMIT 1
        ');
        $stmt->execute([':email_hash' => $emailHash, ':ip' => $ip]);
        $entry = $stmt->fetch();

        if (!$entry) {
            // Première tentative
            $stmt = $this->db->prepare('
                INSERT INTO ' . self::TABLE . '
                    (email_hash, ip_address, attempts, first_attempt_at, last_attempt_at, blocked_until)
                VALUES
                    (:email_hash, :ip, 1, :now, :now, NULL)
            ');
            $stmt->execute([':email_hash' => $emailHash, ':ip' => $ip, ':now' => $now]);
            return;
        }

        // Fenêtre expirée → reset
        $firstAttemptAt = strtotime($entry->first_attempt_at);
        if ((time() - $firstAttemptAt) > $this->windowSeconds) {
            $stmt = $this->db->prepare('
                UPDATE ' . self::TABLE . '
                SET attempts = 1, first_attempt_at = :now, last_attempt_at = :now, blocked_until = NULL
                WHERE email_hash = :email_hash AND ip_address = :ip
            ');
            $stmt->execute([':email_hash' => $emailHash, ':ip' => $ip, ':now' => $now]);
            return;
        }

        $newAttempts = (int)$entry->attempts + 1;
        $blockedUntil = null;

        if ($newAttempts >= $this->maxAttempts) {
            $blockedUntil = date('Y-m-d H:i:s', time() + $this->blockSeconds);
        }

        $stmt = $this->db->prepare('
            UPDATE ' . self::TABLE . '
            SET attempts = :attempts, last_attempt_at = :now, blocked_until = :blocked_until
            WHERE email_hash = :email_hash AND ip_address = :ip
        ');
        $stmt->execute([
            ':attempts'      => $newAttempts,
            ':now'           => $now,
            ':blocked_until' => $blockedUntil,
            ':email_hash'    => $emailHash,
            ':ip'            => $ip,
        ]);
    }

    public function clear(string $email, string $ip): void
    {
        $this->deleteEntry($email, $ip);
    }

    public function isIpBlocked(string $ip): bool
    {
        $stmt = $this->db->prepare('
            SELECT blocked_until
            FROM ' . self::TABLE_IP . '
            WHERE ip_address = :ip
            LIMIT 1
        ');
        $stmt->execute([':ip' => $ip]);
        $row = $stmt->fetch();

        if (!$row || empty($row->blocked_until)) {
            return false;
        }

        $blockedUntil = strtotime($row->blocked_until);
        if ($blockedUntil > time()) {
            return true;
        }

        $this->deleteIpEntry($ip);
        return false;
    }

    public function registerIpFailure(string $ip): void
    {
        $this->maybeCleanupExpiredEntries();

        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare('
            SELECT *
            FROM ' . self::TABLE_IP . '
            WHERE ip_address = :ip
            LIMIT 1
        ');
        $stmt->execute([':ip' => $ip]);
        $entry = $stmt->fetch();

        if (!$entry) {
            $stmt = $this->db->prepare('
                INSERT INTO ' . self::TABLE_IP . '
                    (ip_address, attempts, first_attempt_at, last_attempt_at, blocked_until)
                VALUES
                    (:ip, 1, :now, :now, NULL)
            ');
            $stmt->execute([':ip' => $ip, ':now' => $now]);
            return;
        }

        $firstAttemptAt = strtotime($entry->first_attempt_at);
        if ((time() - $firstAttemptAt) > $this->windowSeconds) {
            $stmt = $this->db->prepare('
                UPDATE ' . self::TABLE_IP . '
                SET attempts = 1, first_attempt_at = :now, last_attempt_at = :now, blocked_until = NULL
                WHERE ip_address = :ip
            ');
            $stmt->execute([':ip' => $ip, ':now' => $now]);
            return;
        }

        $newAttempts = (int)$entry->attempts + 1;
        $blockedUntil = null;

        if ($newAttempts >= $this->maxIpAttempts) {
            $blockedUntil = date('Y-m-d H:i:s', time() + $this->blockSeconds);
        }

        $stmt = $this->db->prepare('
            UPDATE ' . self::TABLE_IP . '
            SET attempts = :attempts, last_attempt_at = :now, blocked_until = :blocked_until
            WHERE ip_address = :ip
        ');
        $stmt->execute([
            ':attempts'      => $newAttempts,
            ':now'           => $now,
            ':blocked_until' => $blockedUntil,
            ':ip'            => $ip,
        ]);
    }

    public function clearIp(string $ip): void
    {
        $this->deleteIpEntry($ip);
    }

    public function secondsRemaining(string $email, string $ip): int
    {
        $stmt = $this->db->prepare('
            SELECT blocked_until
            FROM ' . self::TABLE . '
            WHERE email_hash = :email_hash AND ip_address = :ip
            LIMIT 1
        ');
        $stmt->execute([':email_hash' => $this->hashEmail($email), ':ip' => $ip]);
        $row = $stmt->fetch();

        if (!$row || empty($row->blocked_until)) {
            return 0;
        }

        return max(0, strtotime($row->blocked_until) - time());
    }

    public function ipSecondsRemaining(string $ip): int
    {
        $stmt = $this->db->prepare('
            SELECT blocked_until
            FROM ' . self::TABLE_IP . '
            WHERE ip_address = :ip
            LIMIT 1
        ');
        $stmt->execute([':ip' => $ip]);
        $row = $stmt->fetch();

        if (!$row || empty($row->blocked_until)) {
            return 0;
        }

        return max(0, strtotime($row->blocked_until) - time());
    }

    private function deleteEntry(string $email, string $ip): void
    {
        $stmt = $this->db->prepare('
            DELETE FROM ' . self::TABLE . '
            WHERE email_hash = :email_hash AND ip_address = :ip
        ');
        $stmt->execute([':email_hash' => $this->hashEmail($email), ':ip' => $ip]);
    }

    private function deleteIpEntry(string $ip): void
    {
        $stmt = $this->db->prepare('
            DELETE FROM ' . self::TABLE_IP . '
            WHERE ip_address = :ip
        ');
        $stmt->execute([':ip' => $ip]);
    }

    private function maybeCleanupExpiredEntries(): void
    {
        $nowTs = time();
        if (($nowTs - self::$lastCleanupAt) < self::CLEANUP_INTERVAL_SECONDS) {
            return;
        }

        self::$lastCleanupAt = $nowTs;
        $now = date('Y-m-d H:i:s', $nowTs);
        $stale = date('Y-m-d H:i:s', $nowTs - self::CLEANUP_RETENTION_SECONDS);

        $stmt = $this->db->prepare('
            DELETE FROM ' . self::TABLE . '
            WHERE (blocked_until IS NOT NULL AND blocked_until < :now)
               OR (blocked_until IS NULL AND last_attempt_at < :stale)
        ');
        $stmt->execute([
            ':now' => $now,
            ':stale' => $stale,
        ]);

        $stmt = $this->db->prepare('
            DELETE FROM ' . self::TABLE_IP . '
            WHERE (blocked_until IS NOT NULL AND blocked_until < :now)
               OR (blocked_until IS NULL AND last_attempt_at < :stale)
        ');
        $stmt->execute([
            ':now' => $now,
            ':stale' => $stale,
        ]);
    }

    private function hashEmail(string $email): string
    {
        return hash('sha256', mb_strtolower(trim($email)));
    }
}