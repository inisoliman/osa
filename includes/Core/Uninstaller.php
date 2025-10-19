<?php
namespace OrsozoxDivineSEO\Core;

/**
 * Fired during plugin uninstallation
 */
class Uninstaller {
    
    /**
     * Uninstallation tasks
     */
    public static function uninstall() {
        global $wpdb;
        
        // Check if user wants to keep data
        if (get_option('odse_keep_data_on_uninstall')) {
            return;
        }
        
        // Drop tables
        $tables = [
            $wpdb->prefix . 'odse_post_topics',
            $wpdb->prefix . 'odse_internal_links',
            $wpdb->prefix . 'odse_keywords',
            $wpdb->prefix . 'odse_architecture',
            $wpdb->prefix . 'odse_ai_cache',
            $wpdb->prefix . 'odse_analytics',
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        // Delete all plugin options
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'odse_%'");
        
        // Delete all post meta
        $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_odse_%'");
        
        // Clear cron jobs
        wp_clear_scheduled_hook('odse_daily_analysis');
        wp_clear_scheduled_hook('odse_weekly_cannibalization_check');
    }
}
