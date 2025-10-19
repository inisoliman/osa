<?php
namespace OrsozoxDivineSEO\Core;

class Activator {
    public static function activate() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        
        $table = $wpdb->prefix . 'odse_post_topics';
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            topic_name varchar(255) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        add_option('odse_version', ODSE_VERSION);
        add_option('odse_ai_provider', 'openai');
        add_option('odse_ai_model', 'gpt-4o');
        
        flush_rewrite_rules();
    }
}
