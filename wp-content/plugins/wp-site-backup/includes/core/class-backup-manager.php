<?php
namespace WPSB\Core;

class BackupManager {
    private $logger;
    private $encryption;
    private $storage;
    private $system_info;

    public function __construct() {
        $this->logger = new Logger();
        $this->encryption = new Encryption();
        $this->storage = new Storage\StorageManager();
        $this->system_info = new SystemInfo();
    }

    public function start_backup($type = 'full', $schedule = false) {
        try {
            // Check server resources
            if (!$this->check_server_resources()) {
                throw new \Exception('Insufficient server resources');
            }

            // Initialize backup
            $backup_id = uniqid('backup_');
            $this->logger->info("Starting backup: {$backup_id}");

            // Create temporary directory
            $temp_dir = $this->create_temp_directory($backup_id);

            // Backup database
            $this->backup_database($temp_dir);

            // Backup files
            if ($type === 'full') {
                $this->backup_files($temp_dir);
            }

            // Create manifest
            $this->create_manifest($temp_dir, $type);

            // Encrypt backup
            $encrypted_path = $this->encryption->encrypt_directory($temp_dir);

            // Store backup
            $storage_result = $this->storage->store($encrypted_path);

            // Cleanup
            $this->cleanup($temp_dir);

            // Send notification
            $this->send_notification('success', $backup_id);

            return [
                'status' => 'success',
                'backup_id' => $backup_id,
                'storage_info' => $storage_result
            ];

        } catch (\Exception $e) {
            $this->logger->error("Backup failed: " . $e->getMessage());
            $this->send_notification('error', $backup_id, $e->getMessage());
            throw $e;
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

    private function backup_database($temp_dir) {
        global $wpdb;

        $tables = $wpdb->get_results('SHOW TABLES', ARRAY_N);
        $sql_file = $temp_dir . '/database.sql';
        $handle = fopen($sql_file, 'w');

        foreach ($tables as $table) {
            $table_name = $table[0];
            
            // Get create table syntax
            $create_table = $wpdb->get_row("SHOW CREATE TABLE $table_name", ARRAY_N);
            fwrite($handle, $create_table[1] . ";\n\n");

            // Get table data
            $rows = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
            foreach ($rows as $row) {
                $values = array_map([$wpdb, '_real_escape'], $row);
                $sql = "INSERT INTO $table_name VALUES ('" . implode("','", $values) . "');\n";
                fwrite($handle, $sql);
            }
        }

        fclose($handle);
    }

    private function backup_files($temp_dir) {
        $excludes = [
            'wp-content/backups',
            'wp-content/cache',
            'wp-content/upgrade',
            'wp-content/uploads/backup'
        ];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(ABSPATH)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relative_path = str_replace(ABSPATH, '', $file->getPathname());
                
                // Skip excluded paths
                if ($this->is_excluded($relative_path, $excludes)) {
                    continue;
                }

                // Create directory structure
                $backup_path = $temp_dir . '/files/' . dirname($relative_path);
                if (!is_dir($backup_path)) {
                    mkdir($backup_path, 0755, true);
                }

                // Copy file
                copy($file->getPathname(), $temp_dir . '/files/' . $relative_path);
            }
        }
    }

    private function is_excluded($path, $excludes) {
        foreach ($excludes as $exclude) {
            if (strpos($path, $exclude) === 0) {
                return true;
            }
        }
        return false;
    }

    private function create_manifest($temp_dir, $type) {
        $manifest = [
            'version' => WPSB_VERSION,
            'timestamp' => time(),
            'type' => $type,
            'wordpress_version' => get_bloginfo('version'),
            'site_url' => get_site_url(),
            'backup_info' => [
                'files_count' => $this->count_files($temp_dir . '/files'),
                'database_size' => filesize($temp_dir . '/database.sql'),
                'plugins' => get_option('active_plugins')
            ]
        ];

        file_put_contents(
            $temp_dir . '/manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT)
        );
    }

    private function count_files($dir) {
        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $count++;
            }
        }

        return $count;
    }

    private function send_notification($status, $backup_id, $error_message = '') {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');

        if ($status === 'success') {
            $subject = sprintf(__('[%s] Backup Completed Successfully', 'wp-site-backup'), $site_name);
            $message = sprintf(
                __('Backup ID: %s completed successfully at %s', 'wp-site-backup'),
                $backup_id,
                current_time('mysql')
            );
        } else {
            $subject = sprintf(__('[%s] Backup Failed', 'wp-site-backup'), $site_name);
            $message = sprintf(
                __('Backup ID: %s failed at %s. Error: %s', 'wp-site-backup'),
                $backup_id,
                current_time('mysql'),
                $error_message
            );
        }

        wp_mail($admin_email, $subject, $message);
    }

    private function cleanup($temp_dir) {
        $this->recursive_remove_directory($temp_dir);
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