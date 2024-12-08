<?php
namespace WPSB\Core;

class Plugin {
    private $loader;
    private $version;

    public function __construct() {
        $this->version = WPSB_VERSION;
        $this->load_dependencies();
    }

    private function load_dependencies() {
        require_once WPSB_PLUGIN_DIR . 'includes/core/class-loader.php';
        require_once WPSB_PLUGIN_DIR . 'includes/core/class-backup-manager.php';
        require_once WPSB_PLUGIN_DIR . 'includes/core/class-restore-manager.php';
        require_once WPSB_PLUGIN_DIR . 'includes/core/class-encryption.php';
        require_once WPSB_PLUGIN_DIR . 'includes/core/class-logger.php';
        require_once WPSB_PLUGIN_DIR . 'includes/admin/class-admin.php';
        require_once WPSB_PLUGIN_DIR . 'includes/api/class-rest-controller.php';
        require_once WPSB_PLUGIN_DIR . 'includes/storage/class-storage-manager.php';

        $this->loader = new Loader();
    }

    public function init() {
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_api_hooks();
        $this->loader->run();
    }

    private function set_locale() {
        add_action('plugins_loaded', function() {
            load_plugin_textdomain(
                'wp-site-backup',
                false,
                dirname(plugin_basename(WPSB_PLUGIN_DIR)) . '/languages/'
            );
        });
    }

    private function define_admin_hooks() {
        $admin = new Admin\Admin($this->version);
        
        $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $admin, 'add_menu_pages');
        $this->loader->add_action('admin_init', $admin, 'register_settings');
        $this->loader->add_action('wp_dashboard_setup', $admin, 'add_dashboard_widgets');
    }

    private function define_api_hooks() {
        $api = new API\RestController();
        $this->loader->add_action('rest_api_init', $api, 'register_routes');
    }
}