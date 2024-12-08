<?php
namespace WPSB\Storage;

interface StorageProviderInterface {
    public function store($file_path);
    public function retrieve($backup_id);
    public function list_backups();
    public function delete($backup_id);
}