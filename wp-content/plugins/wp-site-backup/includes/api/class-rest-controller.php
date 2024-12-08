<?php
namespace WPSB\API;

class RestController {
    private $namespace = 'wp-site-backup/v1';
    private $backup_manager;
    private $restore_manager;

    public function __construct() {
        $this->backup_manager = new \WPSB\Core\BackupManager();
        $this->restore_manager = new \WPSB\Core\RestoreManager();
    }

    public function register_routes() {
        register_rest_route($this->namespace, '/backup', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_backup'],
                'permission_callback' => [$this, 'check_admin_permissions'],
            ]
        ]);

        register_rest_route($this->namespace, '/restore/(?P<id>[a-zA-Z0-9-]+)', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'restore_backup'],
                'permission_callback' => [$this, 'check_admin_permissions'],
            ]
        ]);

        register_rest_route($this->namespace, '/backups', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'list_backups'],
                'permission_callback' => [$this, 'check_admin_permissions'],
            ]
        ]);

        register_rest_route($this->namespace, '/backup/(?P<id>[a-zA-Z0-9-]+)', [
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_backup'],
                'permission_callback' => [$this, 'check_admin_permissions'],
            ]
        ]);
    }

    public function check_admin_permissions() {
        return current_user_can('manage_options');
    }

    public function create_backup($request) {
        try {
            $type = $request->get_param('type') ?? 'full';
            $schedule = $request->get_param('schedule') ?? false;

            $result = $this->backup_manager->start_backup($type, $schedule);
            return rest_ensure_response($result);
        } catch (\Exception $e) {
            return new \WP_Error(
                'backup_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    public function restore_backup($request) {
        try {
            $backup_id = $request->get_param('id');
            $result = $this->restore_manager->start_restore($backup_id);
            return rest_ensure_response($result);
        } catch (\Exception $e) {
            return new \WP_Error(
                'restore_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    public function list_backups() {
        try {
            $storage = new \WPSB\Storage\StorageManager();
            $backups = $storage->list_backups();
            return rest_ensure_response($backups);
        } catch (\Exception $e) {
            return new \WP_Error(
                'list_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    public function delete_backup($request) {
        try {
            $backup_id = $request->get_param('id');
            $storage = new \WPSB\Storage\StorageManager();
            $result = $storage->delete_backup($backup_id);
            return rest_ensure_response(['success' => $result]);
        } catch (\Exception $e) {
            return new \WP_Error(
                'delete_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
}