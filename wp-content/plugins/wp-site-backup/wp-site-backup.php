<?php
/**
 * Plugin Name: WP Site Backup
 * Plugin URI: https://example.com/wp-site-backup
 * Description: A comprehensive WordPress backup solution with encryption, cloud storage, and restore capabilities
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: wp-site-backup
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('WPSB_VERSION', '1.0.0');
define('WPSB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPSB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPSB_BACKUP_DIR', WP_CONTENT_DIR . '/backups/');
define('WPSB_MIN_PHP_VERSION', '7.4');
define('WPSB_MIN_WP_VERSION', '5.6');

// Autoloader
require_once WPSB_PLUGIN_DIR . 'includes/class-autoloader.php';

// Initialize the plugin
function wpsb_init() {
    // Check requirements
    if (!wpsb_check_requirements()) {
        return;
    }

    // Initialize main plugin class
    $plugin = new WPSB\Core\Plugin();
    $plugin->init();
}

// Check plugin requirements
function wpsb_check_requirements() {
    if (version_compare(PHP_VERSION, WPSB_MIN_PHP_VERSION, '<')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>' . 
                sprintf(
                    __('WP Site Backup requires PHP version %s or higher.', 'wp-site-backup'),
                    WPSB_MIN_PHP_VERSION
                ) . 
                '</p></div>';
        });
        return false;
    }

    if (version_compare($GLOBALS['wp_version'], WPSB_MIN_WP_VERSION, '<')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>' . 
                sprintf(
                    __('WP Site Backup requires WordPress version %s or higher.', 'wp-site-backup'),
                    WPSB_MIN_WP_VERSION
                ) . 
                '</p></div>';
        });
        return false;
    }

    return true;
}

// Activation hook
register_activation_hook(__FILE__, function() {
    require_once WPSB_PLUGIN_DIR . 'includes/class-activator.php';
    WPSB\Core\Activator::activate();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    require_once WPSB_PLUGIN_DIR . 'includes/class-deactivator.php';
    WPSB\Core\Deactivator::deactivate();
});

// Initialize the plugin
add_action('plugins_loaded', 'wpsb_init');