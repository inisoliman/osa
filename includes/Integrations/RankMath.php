<?php
namespace OrsozoxDivineSEO\Integrations;

/**
 * Rank Math Integration Class
 */
class RankMath {
    
    public function __construct() {
        // Check if Rank Math is active
        if (!$this->is_rank_math_active()) {
            return;
        }
        
        // Add integration hooks
        add_action('rank_math/admin_bar/items', [$this, 'add_admin_bar_items']);
        add_filter('rank_math/json_ld', [$this, 'enhance_schema'], 10, 2);
    }
    
    /**
     * Check if Rank Math is active
     */
    private function is_rank_math_active() {
        return class_exists('RankMath');
    }
    
    /**
     * Add items to Rank Math admin bar
     */
    public function add_admin_bar_items($items) {
        if (!is_single()) {
            return $items;
        }
        
        $post_id = get_the_ID();
        $analysis = get_post_meta($post_id, '_odse_analysis', true);
        
        if ($analysis) {
            $items[] = [
                'id' => 'odse-analysis',
                'title' => '✨ Divine SEO: Analyzed',
                'href' => '#',
                'meta' => [
                    'class' => 'odse-rank-math-item'
                ]
            ];
        }
        
        return $items;
    }
    
    /**
     * Enhance Rank Math schema with our data
     */
    public function enhance_schema($data, $jsonld) {
        if (!is_single()) {
            return $data;
        }
        
        $post_id = get_the_ID();
        $analysis = get_post_meta($post_id, '_odse_analysis', true);
        
        if ($analysis && !empty($analysis['biblical_themes'])) {
            // Add keywords from our analysis
            if (!isset($data['keywords'])) {
                $data['keywords'] = [];
            }
            
            $data['keywords'] = array_merge(
                $data['keywords'],
                $analysis['primary_keywords'] ?? []
            );
            
            // Add about schema for biblical themes
            if (!isset($data['about'])) {
                $data['about'] = [];
            }
            
            foreach ($analysis['biblical_themes'] as $theme) {
                $data['about'][] = [
                    '@type' => 'Thing',
                    'name' => $theme
                ];
            }
        }
        
        return $data;
    }
    
    /**
     * Sync our keywords with Rank Math focus keywords
     */
    public function sync_keywords($post_id) {
        $analysis = get_post_meta($post_id, '_odse_analysis', true);
        
        if (!$analysis || empty($analysis['primary_keywords'])) {
            return;
        }
        
        // Get current Rank Math focus keyword
        $rm_keyword = get_post_meta($post_id, 'rank_math_focus_keyword', true);
        
        // If no focus keyword set, use our primary keyword
        if (empty($rm_keyword) && !empty($analysis['primary_keywords'][0])) {
            update_post_meta($post_id, 'rank_math_focus_keyword', $analysis['primary_keywords'][0]);
        }
    }
}
