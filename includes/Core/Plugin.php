<?php
namespace OrsozoxDivineSEO\Core;

/**
 * Main Plugin Class
 * Handles plugin initialization and dependencies
 */
class Plugin {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
    }
    
    /**
     * Load all dependencies
     */
    private function load_dependencies() {
        // Admin dependencies
        if (is_admin()) {
            new \OrsozoxDivineSEO\Admin\AdminMenu();
            new \OrsozoxDivineSEO\Admin\DashboardPage();
            new \OrsozoxDivineSEO\Admin\SettingsPage();
            new \OrsozoxDivineSEO\Admin\KeywordMapPage();
            new \OrsozoxDivineSEO\Admin\ArchitecturePage();
            new \OrsozoxDivineSEO\Admin\MetaBoxes();
            new \OrsozoxDivineSEO\Admin\AjaxHandlers();
        }
        
        // Frontend dependencies
        new \OrsozoxDivineSEO\Frontend\ContentOptimizer();
        
        // Core functionality
        new \OrsozoxDivineSEO\InternalLinking\AutoLinker();
        new \OrsozoxDivineSEO\Analytics\Tracker();
        
        // Cron jobs
        new \OrsozoxDivineSEO\Cron\Scheduler();
        
        // REST API
        new \OrsozoxDivineSEO\API\RestRoutes();
        
        // Integrations
        new \OrsozoxDivineSEO\Integrations\RankMath();
    }
    
    /**
     * Run the plugin
     */
    public function run() {
        // Load text domain for translations
        add_action('init', [$this, 'load_textdomain']);
        
        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        
        // Check for activation redirect
        add_action('admin_init', [$this, 'activation_redirect']);
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'orsozox-divine-seo',
            false,
            dirname(ODSE_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'orsozox-divine-seo') === false) {
            return;
        }
        
        // Styles
        wp_enqueue_style(
            'odse-admin-style',
            ODSE_PLUGIN_URL . 'assets/css/admin-style.css',
            [],
            ODSE_VERSION
        );
        
        // Chart.js for visualizations
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', [], '4.4.0', true);
        
        // Scripts
        wp_enqueue_script(
            'odse-admin-script',
            ODSE_PLUGIN_URL . 'assets/js/admin-script.js',
            ['jquery', 'chart-js'],
            ODSE_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('odse-admin-script', 'odseAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('orsozox-divine-seo/v1/'),
            'nonce' => wp_create_nonce('odse_nonce'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'strings' => [
                'analyzing' => __('جاري التحليل...', 'orsozox-divine-seo'),
                'success' => __('تم بنجاح!', 'orsozox-divine-seo'),
                'error' => __('حدث خطأ', 'orsozox-divine-seo'),
                'confirm' => __('هل أنت متأكد؟', 'orsozox-divine-seo'),
                'processing' => __('جاري المعالجة...', 'orsozox-divine-seo'),
            ]
        ]);
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (!is_single()) {
            return;
        }
        
        wp_enqueue_style(
            'odse-frontend-style',
            ODSE_PLUGIN_URL . 'assets/css/frontend-style.css',
            [],
            ODSE_VERSION
        );
    }
    
    /**
     * Redirect to settings page on activation
     */
    public function activation_redirect() {
        if (get_transient('odse_activation_redirect')) {
            delete_transient('odse_activation_redirect');
            
            if (!isset($_GET['activate-multi'])) {
                wp_safe_redirect(admin_url('admin.php?page=orsozox-divine-seo'));
                exit;
            }
        }
    }
}
