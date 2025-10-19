<?php
namespace OrsozoxDivineSEO\Admin;

/**
 * Dashboard Page Class
 */
class DashboardPage {
    
    public function __construct() {
        // Dashboard functionality
    }
    
    /**
     * Get dashboard statistics
     */
    public function get_stats() {
        global $wpdb;
        
        $stats = [
            'total_posts' => wp_count_posts('post')->publish,
            'analyzed_posts' => $this->get_analyzed_posts_count(),
            'internal_links' => $this->get_internal_links_count(),
            'keyword_conflicts' => count($this->get_keyword_conflicts()),
            'topic_clusters' => $this->get_topic_clusters_count(),
        ];
        
        return $stats;
    }
    
    private function get_analyzed_posts_count() {
        global $wpdb;
        $table = $wpdb->prefix . 'odse_post_topics';
        return $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM $table");
    }
    
    private function get_internal_links_count() {
        global $wpdb;
        $table = $wpdb->prefix . 'odse_internal_links';
        return $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE ai_generated = 1");
    }
    
    private function get_keyword_conflicts() {
        $engine = new \OrsozoxDivineSEO\AI\Engine();
        return $engine->detect_cannibalization();
    }
    
    private function get_topic_clusters_count() {
        global $wpdb;
        $table = $wpdb->prefix . 'odse_post_topics';
        return $wpdb->get_var("SELECT COUNT(DISTINCT topic_name) FROM $table");
    }
}
