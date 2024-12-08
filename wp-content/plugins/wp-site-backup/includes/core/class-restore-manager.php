<?php
namespace WPSB\Core;

class RestoreManager {
    private $logger;
    private $encryption;
    private $system_info;

    public function __construct() {
        $this->logger = new Logger();
        $this->encryption = new Encryption();
        $this->system_info = new SystemInfo();
    }

    public function start_restore($backup_id) {
        try {
            if (!$this->check_server_resources()) {
                throw new \Exception('Insufficient server resources');
            }

            $this->logger->info("Starting restore: {$backup_id}");

            // Create temporary directory
            $temp_dir = $this->create_temp_directory('restore_' . $backup_id);

            // Decrypt backup
            $this->encryption->decrypt_backup($backup_id, $temp_dir);

            // Validate backup
            $this->validate_backup($temp_dir);

            // Restore database
            $this->restore_database($temp_dir);

            // Restore files
            $this->restore_files($temp_dir);

            // Cleanup
            $this->cleanup($temp_dir);

            $this->logger->info("Restore completed: {$backup_id}");

            return [
                'status' => 'success',
                'message' => 'Restore completed successfully'
            ];

        } catch (\Exception $e) {
            $this->logger->error("Restore failed: " . $e->getMessage());
            throw $e;
        }
    }

    private function validate_backup($temp_dir) {
        $manifest_file = $temp_dir . '/manifest.json';
        if (!file_exists($manifest_file)) {
            throw new \Exception('Invalid backup: Missing manifest file');
        }

        $manifest = json_decode(file_get_contents($manifest_file), true);
        if (!$manifest) {
            throw new \Exception('Invalid backup: Corrupt manifest file');
        }

        // Validate WordPress version compatibility
        if (version_compare($manifest['wordpress_version'], get_bloginfo('version'), '>')) {
            throw new \Exception('Backup is from a newer WordPress version');
        }
    }

    private function restore_database($temp_dir) {
        global $wpdb;

        $sql_file = $temp_dir . '/database.sql';
        if (!file_exists($sql_file)) {
            throw new \Exception('Database backup file not found');
        }

        // Disable foreign key checks
        $wpdb->query('SET foreign_key_checks = 0');

        // Read SQL file in chunks
        $handle = fopen($sql_file, 'r');
        $query = '';

        while (!feof($handle)) {
            $line = fgets($handle);
            
            // Skip comments
            if (substr($line, 0, 2) == '--' || $line == '') {
                continue;
            }

            $query .= $line;

            if (substr(trim($line), -1, 1) == ';') {
                $wpdb->query($query);
                $query = '';
            }
        }

        fclose($handle);

        // Re-enable foreign key checks
        $wpdb->query('SET foreign_key_checks = 1');
    }

    private function restore_files($temp_dir) {
        $source_dir = $temp_dir . '/files';
        if (!is_dir($source_dir)) {
            throw new \Exception('Files backup directory not found');
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source_dir),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                continue;
            }

            $relative_path = str_replace($source_dir, '', $item->getPathname());
            $target_path = ABSPATH . $relative_path;

            // Create directory if it doesn't exist
            if (!is_dir(dirname($target_path))) {
                mkdir(dirname($target_path), 0755, true);
            }

            copy($item->getPathname(), $target_path);
        }
    }

    private function check_server_resources() {
        $resources = $this->system_info->get_resources();
        
        // Ensure 50% of resources remain free
        if ($resources['memory_usage'] > 50 || 
            $resources['cpu_usage'] > 50 || 
            $resources['disk_usage'] > 50) {
            return false;
        }

        return true;
    }

    private function cleanup($temp_dir) {
        if (is_dir($temp_dir)) {
            $this->recursive_remove_directory($temp_dir);
        }
    }

    private function recursive_remove_directory($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object)) {
                        $this->recursive_remove_directory($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }
}