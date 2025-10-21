<?php
namespace OrsozoxDivineSEO\Admin;

/**
 * Keyword Map Page Class
 */
class KeywordMapPage {
    
    public function __construct() {
        // Keyword map functionality
    }
    
    /**
     * Get all keywords with their assignments
     */
    public function get_keyword_map() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'odse_keywords';
        
        $keywords = $wpdb->get_results("
            SELECT k.*, p.post_title, p.post_status
            FROM $table k
            LEFT JOIN {$wpdb->posts} p ON k.assigned_post_id = p.ID
            ORDER BY k.keyword ASC
        ");
        
        return $keywords;
    }
    
    /**
     * Get keyword conflicts (cannibalization)
     */
    public function get_conflicts() {
        $engine = new \OrsozoxDivineSEO\AI\Engine();
        return $engine->detect_cannibalization();
    }
    
    /**
     * Assign keyword to post
     */
    public function assign_keyword($keyword, $post_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'odse_keywords';
        
        $keyword_slug = sanitize_title($keyword);
        
        $wpdb->replace($table, [
            'keyword' => $keyword,
            'keyword_slug' => $keyword_slug,
            'assigned_post_id' => $post_id
        ]);
        
        return true;
    }
}
