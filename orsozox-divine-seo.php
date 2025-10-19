<?php
/**
 * Plugin Name: Orsozox Divine SEO Engine
 * Plugin URI: https://orsozox.com
 * Description: AI-Powered SEO Architecture & Internal Linking System for Christian Content - نظام ذكي لتحسين محركات البحث بالذكاء الاصطناعي للمحتوى المسيحي
 * Version: 1.0.0
 * Author: Orsozox Team
 * Author URI: https://orsozox.com
 * Text Domain: orsozox-divine-seo
 * Domain Path: /languages
 * Requires PHP: 8.0
 * Requires at least: 6.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace OrsozoxDivineSEO;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define Constants
define('ODSE_VERSION', '1.0.0');
define('ODSE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ODSE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ODSE_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('ODSE_MIN_PHP', '8.0');
define('ODSE_MIN_WP', '6.0');

// Check PHP version
if (version_compare(PHP_VERSION, ODSE_MIN_PHP, '<')) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p>' . 
            sprintf(__('Orsozox Divine SEO requires PHP %s or higher. You are running version %s.', 'orsozox-divine-seo'), ODSE_MIN_PHP, PHP_VERSION) . 
            '</p></div>';
    });
    return;
}

// Autoloader
require_once ODSE_PLUGIN_DIR . 'includes/class-autoloader.php';
Autoloader::register();

// Initialize Plugin
function odse_init() {
    $plugin = Core\Plugin::get_instance();
    $plugin->run();
}
add_action('plugins_loaded', __NAMESPACE__ . '\\odse_init');

// Activation Hook
register_activation_hook(__FILE__, function() {
    Core\Activator::activate();
});

// Deactivation Hook
register_deactivation_hook(__FILE__, function() {
    Core\Deactivator::deactivate();
});

// Uninstall Hook
register_uninstall_hook(__FILE__, ['OrsozoxDivineSEO\\Core\\Uninstaller', 'uninstall']);
