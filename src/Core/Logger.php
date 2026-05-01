<?php

namespace Core;

class Logger
{
    private static ?self $instance = null;
    private string $logFile;
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

    private function __construct()
    {
        $this->logFile = __DIR__ . '/../../logs/app.log';

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

        $formattedMessage = "[$timestamp] | " . strtoupper($level) . " | request_id=$requestId | $message";
        if ($contextJson !== '') {
            $formattedMessage .= " | context=$contextJson";
        }
        $formattedMessage .= PHP_EOL;

        file_put_contents($logger->logFile, $formattedMessage, FILE_APPEND);
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
