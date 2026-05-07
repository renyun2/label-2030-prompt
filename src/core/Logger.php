<?php
class Logger {
    private static $instance = null;
    private $logDir;

    private function __construct() {
        $this->logDir = __DIR__ . '/../logs/';
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }

    public static function getInstance(): Logger {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function write(string $level, string $message, array $context = []): void {
        $date     = date('Y-m-d');
        $time     = date('Y-m-d H:i:s');
        $file     = $this->logDir . "app-{$date}.log";

        $ip       = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $uri      = $_SERVER['REQUEST_URI'] ?? '';
        $ctx      = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        $line     = "[{$time}] [{$level}] [{$ip}] {$message}{$ctx} | {$uri}" . PHP_EOL;

        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $message, array $context = []): void {
        self::getInstance()->write('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void {
        self::getInstance()->write('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void {
        self::getInstance()->write('ERROR', $message, $context);
    }

    public static function debug(string $message, array $context = []): void {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            self::getInstance()->write('DEBUG', $message, $context);
        }
    }
}
