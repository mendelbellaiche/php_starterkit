<?php

namespace Core;

class Logger
{
    private static ?self $instance = null;
    private string $logFile;
    private int $maxFileSize;
    private int $maxFiles;
    private const SENSITIVE_KEYS = [
        'password',
        'passwd',
        'pwd',
        'token',
        'secret',
        'authorization',
        'api_key',
        'apikey',
        'cookie',
        'session',
    ];

    private function __construct(int $maxFileSizeMb = 5, int $maxFiles = 10)
    {
        $this->logFile = __DIR__ . '/../../logs/app.log';
        $this->maxFileSize = $maxFileSizeMb * 1024 * 1024;
        $this->maxFiles = $maxFiles;

        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
    }

    private function __clone()
    {
    }

    public function __wakeup()
    {
        throw new \Exception('Cannot unserialize singleton ' . self::class);
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function debug(string $message, array $context = []): void
    {
        self::log('DEBUG', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    /**
     * Enregistre un message dans le fichier de log.
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        $logger = self::getInstance();
        $safeContext = self::sanitizeContext($context);
        $timestamp = date('Y-m-d H:i:s');
        $requestId = self::resolveRequestId();
        $contextJson = $safeContext !== [] ? json_encode($safeContext, JSON_UNESCAPED_SLASHES) : '';

        if ($contextJson === false) {
            $contextJson = '{"context":"encoding_error"}';
        }

        $ip = self::resolveClientIp();
        $formattedMessage = "[$timestamp] | " . strtoupper($level) . " | request_id=$requestId | ip=$ip | $message";
        if ($contextJson !== '') {
            $formattedMessage .= " | context=$contextJson";
        }
        $formattedMessage .= PHP_EOL;

        file_put_contents($logger->logFile, $formattedMessage, FILE_APPEND);
        $logger->rotate();
    }

    private function rotate(): void
    {
        if (!file_exists($this->logFile)) {
            return;
        }

        if (filesize($this->logFile) < $this->maxFileSize) {
            return;
        }

        $logDir  = dirname($this->logFile);
        $base    = basename($this->logFile, '.log');
        $rotated = $logDir . '/' . $base . '_' . date('Y-m-d_His') . '.log';

        rename($this->logFile, $rotated);

        // Supprime les anciens fichiers si le nombre max est dépassé
        $pattern = $logDir . '/' . $base . '_*.log';
        $files   = glob($pattern) ?: [];
        usort($files, fn($a, $b) => filemtime($a) <=> filemtime($b));

        while (count($files) > $this->maxFiles) {
            unlink(array_shift($files));
        }
    }

    private static function resolveClientIp(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                // X-Forwarded-For peut contenir plusieurs IPs séparées par des virgules
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return 'unknown';
    }

    private static function resolveRequestId(): string
    {
        if (!empty($_SERVER['HTTP_X_REQUEST_ID'])) {
            return (string) $_SERVER['HTTP_X_REQUEST_ID'];
        }

        if (!empty($_SERVER['UNIQUE_ID'])) {
            return (string) $_SERVER['UNIQUE_ID'];
        }

        return bin2hex(random_bytes(8));
    }

    private static function sanitizeContext(array $context): array
    {
        $result = [];

        foreach ($context as $key => $value) {
            $stringKey = is_string($key) ? strtolower($key) : (string) $key;

            if (self::isSensitiveKey($stringKey)) {
                $result[$key] = '***';
                continue;
            }

            if (is_array($value)) {
                $result[$key] = self::sanitizeContext($value);
                continue;
            }

            if (is_object($value)) {
                $result[$key] = method_exists($value, '__toString') ? (string) $value : '[object ' . $value::class . ']';
                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    private static function isSensitiveKey(string $key): bool
    {
        foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
            if (str_contains($key, $sensitiveKey)) {
                return true;
            }
        }

        return false;
    }
}
