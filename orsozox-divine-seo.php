<?php
/**
 * Plugin Name: Orsozox Divine SEO Engine
 * Plugin URI: https://orsozox.com
 * Description: AI-Powered SEO System for Christian Content
 * Version: 1.0.0
 * Author: Orsozox Team
 * Text Domain: orsozox-divine-seo
 * Requires PHP: 8.0
 * License: GPL v2 or later
 */

namespace OrsozoxDivineSEO;

if (!defined('ABSPATH')) exit;

define('ODSE_VERSION', '1.0.0');
define('ODSE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ODSE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ODSE_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once ODSE_PLUGIN_DIR . 'includes/class-autoloader.php';
Autoloader::register();

add_action('plugins_loaded', function() {
    $plugin = Core\Plugin::get_instance();
    $plugin->run();
});

register_activation_hook(__FILE__, ['OrsozoxDivineSEO\Core\Activator', 'activate']);
register_deactivation_hook(__FILE__, ['OrsozoxDivineSEO\Core\Deactivator', 'deactivate']);
