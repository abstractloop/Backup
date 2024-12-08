<?php
namespace WPSB\Core;

class Logger {
    private $log_file;

    public function __construct() {
        $this->log_file = WPSB_BACKUP_DIR . 'backup.log';
        $this->init_log_file();
    }

    private function init_log_file() {
        if (!file_exists(WPSB_BACKUP_DIR)) {
            mkdir(WPSB_BACKUP_DIR, 0755, true);
        }

        if (!file_exists($this->log_file)) {
            touch($this->log_file);
        }

        // Rotate log if it's too large
        if (filesize($this->log_file) > 5 * 1024 * 1024) { // 5MB
            $this->rotate_log();
        }
    }

    public function info($message) {
        $this->log('INFO', $message);
    }

    public function error($message) {
        $this->log('ERROR', $message);
    }

    public function warning($message) {
        $this->log('WARNING', $message);
    }

    private function log($level, $message) {
        $timestamp = current_time('mysql');
        $log_entry = sprintf(
            "[%s] [%s] %s\n",
            $timestamp,
            $level,
            $message
        );

        file_put_contents($this->log_file, $log_entry, FILE_APPEND);
    }

    private function rotate_log() {
        $backup_log = $this->log_file . '.' . date('Y-m-d');
        rename($this->log_file, $backup_log);
        touch($this->log_file);
    }

    public function get_logs($limit = 100) {
        if (!file_exists($this->log_file)) {
            return [];
        }

        $logs = array_reverse(file($this->log_file));
        return array_slice($logs, 0, $limit);
    }

    public function clear_logs() {
        if (file_exists($this->log_file)) {
            unlink($this->log_file);
        }
        $this->init_log_file();
    }
}