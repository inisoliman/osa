<?php
namespace OrsozoxDivineSEO\Admin;

/**
 * Architecture Page Class
 */
class ArchitecturePage {
    
    public function __construct() {
        // Architecture functionality
    }
    
    /**
     * Get site architecture structure
     */
    public function get_architecture() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'odse_architecture';
        
        $architecture = $wpdb->get_results("
            SELECT a.*, p.post_title, p.post_status, p.post_date
            FROM $table a
            INNER JOIN {$wpdb->posts} p ON a.post_id = p.ID
            WHERE p.post_status = 'publish'
            ORDER BY a.hierarchy_level ASC, a.topic_cluster ASC
        ");
        
        return $this->organize_by_clusters($architecture);
    }
    
    /**
     * Organize posts by topic clusters
     */
    private function organize_by_clusters($architecture) {
        $clusters = [];
        
        foreach ($architecture as $item) {
            $cluster = $item->topic_cluster ?: 'uncategorized';
            
            if (!isset($clusters[$cluster])) {
                $clusters[$cluster] = [
                    'pillars' => [],
                    'supporting' => []
                ];
            }
            
            if ($item->content_type === 'pillar') {
                $clusters[$cluster]['pillars'][] = $item;
            } else {
                $clusters[$cluster]['supporting'][] = $item;
            }
        }
        
        return $clusters;
    }
    
    /**
     * Set post as pillar content
     */
    public function set_as_pillar($post_id, $topic_cluster) {
        global $wpdb;
        $table = $wpdb->prefix . 'odse_architecture';
        
        $wpdb->replace($table, [
            'post_id' => $post_id,
            'content_type' => 'pillar',
            'hierarchy_level' => 1,
            'topic_cluster' => $topic_cluster
        ]);
        
        return true;
    }
}
