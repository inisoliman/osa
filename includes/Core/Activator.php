<?php
namespace OrsozoxDivineSEO\Core;

/**
 * Fired during plugin activation
 */
class Activator {
    
    /**
     * Activation tasks
     */
    public static function activate() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table for post topics
        $table_topics = $wpdb->prefix . 'odse_post_topics';
        $sql_topics = "CREATE TABLE IF NOT EXISTS $table_topics (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            topic_name varchar(255) NOT NULL,
            topic_slug varchar(255) NOT NULL,
            confidence_score decimal(5,2) DEFAULT 0.00,
            is_primary tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY topic_slug (topic_slug)
        ) $charset_collate;";
        
        // Table for internal links
        $table_links = $wpdb->prefix . 'odse_internal_links';
        $sql_links = "CREATE TABLE IF NOT EXISTS $table_links (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            source_post_id bigint(20) NOT NULL,
            target_post_id bigint(20) NOT NULL,
            anchor_text varchar(255) NOT NULL,
            context_before text,
            context_after text,
            priority varchar(20) DEFAULT 'medium',
            ai_generated tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY source_post_id (source_post_id),
            KEY target_post_id (target_post_id)
        ) $charset_collate;";
        
        // Table for keyword mapping
        $table_keywords = $wpdb->prefix . 'odse_keywords';
        $sql_keywords = "CREATE TABLE IF NOT EXISTS $table_keywords (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            keyword varchar(255) NOT NULL,
            keyword_slug varchar(255) NOT NULL,
            assigned_post_id bigint(20),
            search_volume int(11) DEFAULT 0,
            difficulty int(3) DEFAULT 0,
            cannibalization_detected tinyint(1) DEFAULT 0,
            conflicting_posts text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY keyword_slug (keyword_slug),
            KEY assigned_post_id (assigned_post_id)
        ) $charset_collate;";
        
        // Table for site architecture
        $table_architecture = $wpdb->prefix . 'odse_architecture';
        $sql_architecture = "CREATE TABLE IF NOT EXISTS $table_architecture (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            content_type varchar(50) DEFAULT 'supporting',
            parent_pillar_id bigint(20) DEFAULT NULL,
            hierarchy_level int(3) DEFAULT 1,
            topic_cluster varchar(255),
            silo_group varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY post_id (post_id),
            KEY parent_pillar_id (parent_pillar_id),
            KEY topic_cluster (topic_cluster)
        ) $charset_collate;";
        
        // Table for AI analysis cache
        $table_cache = $wpdb->prefix . 'odse_ai_cache';
        $sql_cache = "CREATE TABLE IF NOT EXISTS $table_cache (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            cache_key varchar(255) NOT NULL,
            cache_data longtext NOT NULL,
            cache_type varchar(50) NOT NULL,
            expires_at datetime NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY cache_key (cache_key),
            KEY cache_type (cache_type),
            KEY expires_at (expires_at)
        ) $charset_collate;";
        
        // Table for analytics
        $table_analytics = $wpdb->prefix . 'odse_analytics';
        $sql_analytics = "CREATE TABLE IF NOT EXISTS $table_analytics (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            metric_type varchar(50) NOT NULL,
            metric_value text NOT NULL,
            recorded_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY metric_type (metric_type),
            KEY recorded_at (recorded_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_topics);
        dbDelta($sql_links);
        dbDelta($sql_keywords);
        dbDelta($sql_architecture);
        dbDelta($sql_cache);
        dbDelta($sql_analytics);
        
        // Set default options
        $defaults = [
            'odse_version' => ODSE_VERSION,
            'odse_ai_provider' => 'openai',
            'odse_ai_model' => 'gpt-4o',
            'odse_max_links_per_post' => 10,
            'odse_auto_linking_enabled' => 1,
            'odse_cache_duration' => 7 * DAY_IN_SECONDS,
            'odse_auto_analyze_new_posts' => 1,
            'odse_sge_optimization_enabled' => 1,
            'odse_rank_math_integration' => 1,
            'odse_min_content_length' => 300,
            'odse_link_same_category' => 1,
        ];
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
        
        // Schedule cron jobs
        if (!wp_next_scheduled('odse_daily_analysis')) {
            wp_schedule_event(time(), 'daily', 'odse_daily_analysis');
        }
        
        if (!wp_next_scheduled('odse_weekly_cannibalization_check')) {
            wp_schedule_event(time(), 'weekly', 'odse_weekly_cannibalization_check');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set activation flag for redirect
        set_transient('odse_activation_redirect', true, 30);
    }
}
