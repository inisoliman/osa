<?php
namespace OrsozoxDivineSEO\Core;

/**
 * Fired during plugin deactivation
 */
class Deactivator {
    
    /**
     * Deactivation tasks
     */
    public static function deactivate() {
        // Clear scheduled cron jobs
        wp_clear_scheduled_hook('odse_daily_analysis');
        wp_clear_scheduled_hook('odse_weekly_cannibalization_check');
        
        // Clear transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_odse_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_odse_%'");
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}
