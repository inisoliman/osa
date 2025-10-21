<?php
namespace OrsozoxDivineSEO\Admin;

/**
 * Settings Page Class
 */
class SettingsPage {
    
    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
    }
    
    /**
     * Register all plugin settings
     */
    public function register_settings() {
        // AI Settings
        register_setting('odse_ai_settings', 'odse_ai_provider');
        register_setting('odse_ai_settings', 'odse_ai_api_key');
        register_setting('odse_ai_settings', 'odse_ai_model');
        register_setting('odse_ai_settings', 'odse_claude_api_key');
        register_setting('odse_ai_settings', 'odse_gemini_api_key');
        
        // Linking Settings
        register_setting('odse_linking_settings', 'odse_max_links_per_post');
        register_setting('odse_linking_settings', 'odse_auto_linking_enabled');
        register_setting('odse_linking_settings', 'odse_cache_duration');
        register_setting('odse_linking_settings', 'odse_min_content_length');
        register_setting('odse_linking_settings', 'odse_link_same_category');
        
        // Analysis Settings
        register_setting('odse_analysis_settings', 'odse_auto_analyze_new_posts');
        register_setting('odse_analysis_settings', 'odse_sge_optimization_enabled');
        register_setting('odse_analysis_settings', 'odse_rank_math_integration');
        
        // Advanced Settings
        register_setting('odse_advanced_settings', 'odse_keep_data_on_uninstall');
    }
}
