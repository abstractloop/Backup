<?php
namespace WPSB\Admin;

class Admin {
    private $version;

    public function __construct($version) {
        $this->version = $version;
    }

    public function enqueue_styles() {
        wp_enqueue_style(
            'wp-site-backup',
            WPSB_PLUGIN_URL . 'assets/css/admin.css',
            [],
            $this->version
        );
    }

    public function enqueue_scripts() {
        wp_enqueue_script(
            'wp-site-backup',
            WPSB_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            $this->version,
            true
        );

        wp_localize_script('wp-site-backup', 'wpsbAdmin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpsb_nonce'),
            'i18n' => [
                'confirm_restore' => __('Are you sure you want to restore this backup? This will overwrite your current site data.', 'wp-site-backup'),
                'confirm_delete' => __('Are you sure you want to delete this backup?', 'wp-site-backup')
            ]
        ]);
    }

    public function add_menu_pages() {
        add_menu_page(
            __('Site Backup', 'wp-site-backup'),
            __('Site Backup', 'wp-site-backup'),
            'manage_ options',
            'wp-site-backup',
            [$this, 'render_main_page'],
            'dashicons-backup',
            100
        );

        add_submenu_page(
            'wp-site-backup',
            __('Backup Settings', 'wp-site-backup'),
            __('Settings', 'wp-site-backup'),
            'manage_options',
            'wp-site-backup-settings',
            [$this, 'render_settings_page']
        );

        add_submenu_page(
            'wp-site-backup',
            __('Backup Logs', 'wp-site-backup'),
            __('Logs', 'wp-site-backup'),
            'manage_options',
            'wp-site-backup-logs',
            [$this, 'render_logs_page']
        );
    }

    public function register_settings() {
        register_setting('wpsb_settings', 'wpsb_schedule_type');
        register_setting('wpsb_settings', 'wpsb_custom_schedule');
        register_setting('wpsb_settings', 'wpsb_retention_days');
        register_setting('wpsb_settings', 'wpsb_email_notifications');
        register_setting('wpsb_settings', 'wpsb_notification_email');
        register_setting('wpsb_settings', 'wpsb_storage_s3_enabled');
        register_setting('wpsb_settings', 'wpsb_storage_gdrive_enabled');
        register_setting('wpsb_settings', 'wpsb_storage_dropbox_enabled');
    }

    public function render_main_page() {
        include WPSB_PLUGIN_DIR . 'templates/admin/main-page.php';
    }

    public function render_settings_page() {
        include WPSB_PLUGIN_DIR . 'templates/admin/settings-page.php';
    }

    public function render_logs_page() {
        include WPSB_PLUGIN_DIR . 'templates/admin/logs-page.php';
    }

    public function add_dashboard_widgets() {
        wp_add_dashboard_widget(
            'wpsb_status_widget',
            __('Backup Status', 'wp-site-backup'),
            [$this, 'render_dashboard_widget']
        );
    }

    public function render_dashboard_widget() {
        include WPSB_PLUGIN_DIR . 'templates/admin/dashboard-widget.php';
    }
}