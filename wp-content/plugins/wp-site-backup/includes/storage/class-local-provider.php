<?php
namespace WPSB\Storage;

class LocalProvider implements StorageProviderInterface {
    private $storage_dir;

    public function __construct() {
        $this->storage_dir = WPSB_BACKUP_DIR;
        $this->init_storage();
    }

    private function init_storage() {
        if (!file_exists($this->storage_dir)) {
            mkdir($this->storage_dir, 0755, true);
        }
    }

    public function store($file_path) {
        $backup_id = basename($file_path);
        $destination = $this->storage_dir . $backup_id;

        if (!copy($file_path, $destination)) {
            throw new \Exception("Failed to store backup locally");
        }

        return $destination;
    }

    public function retrieve($backup_id) {
        $file_path = $this->storage_dir . $backup_id;
        
        if (!file_exists($file_path)) {
            throw new \Exception("Backup not found: {$backup_id}");
        }

        return $file_path;
    }

    public function list_backups() {
        $backups = [];
        $files = glob($this->storage_dir . '*.encrypted');

        foreach ($files as $file) {
            $backups[] = [
                'id' => basename($file),
                'size' => filesize($file),
                'date' => filemtime($file)
            ];
        }

        return $backups;
    }

    public function delete($backup_id) {
        $file_path = $this->storage_dir . $backup_id;
        
        if (!file_exists($file_path)) {
            throw new \Exception("Backup not found: {$backup_id}");
        }

        if (!unlink($file_path)) {
            throw new \Exception("Failed to delete backup: {$backup_id}");
        }

        return true;
    }
}