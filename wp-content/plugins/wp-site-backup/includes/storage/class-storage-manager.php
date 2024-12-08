<?php
namespace WPSB\Storage;

class StorageManager {
    private $providers = [];

    public function __construct() {
        $this->init_providers();
    }

    private function init_providers() {
        // Initialize storage providers based on settings
        if (get_option('wpsb_storage_s3_enabled')) {
            $this->providers['s3'] = new S3Provider();
        }
        
        if (get_option('wpsb_storage_gdrive_enabled')) {
            $this->providers['gdrive'] = new GoogleDriveProvider();
        }
        
        if (get_option('wpsb_storage_dropbox_enabled')) {
            $this->providers['dropbox'] = new DropboxProvider();
        }

        // Local storage is always enabled
        $this->providers['local'] = new LocalProvider();
    }

    public function store($file_path) {
        $results = [];

        foreach ($this->providers as $provider_name => $provider) {
            try {
                $result = $provider->store($file_path);
                $results[$provider_name] = [
                    'status' => 'success',
                    'location' => $result
                ];
            } catch (\Exception $e) {
                $results[$provider_name] = [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    public function retrieve($backup_id, $provider = 'local') {
        if (!isset($this->providers[$provider])) {
            throw new \Exception("Storage provider not found: {$provider}");
        }

        return $this->providers[$provider]->retrieve($backup_id);
    }

    public function list_backups($provider = 'local') {
        if (!isset($this->providers[$provider])) {
            throw new \Exception("Storage provider not found: {$provider}");
        }

        return $this->providers[$provider]->list_backups();
    }

    public function delete_backup($backup_id, $provider = 'local') {
        if (!isset($this->providers[$provider])) {
            throw new \Exception("Storage provider not found: {$provider}");
        }

        return $this->providers[$provider]->delete($backup_id);
    }
}