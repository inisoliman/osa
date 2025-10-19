<?php
namespace OrsozoxDivineSEO\InternalLinking;

/**
 * Auto Linker Class
 * Automatically adds internal links to content
 */
class AutoLinker {
    
    private $max_links_per_post;
    
    public function __construct() {
        $this->max_links_per_post = get_option('odse_max_links_per_post', 10);
        
        // Hook into content filter
        add_filter('the_content', [$this, 'auto_add_links'], 999);
    }
    
    /**
     * Automatically add internal links to content
     */
    public function auto_add_links($content) {
        // Only on single posts
        if (!is_single() || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        
        // Check if auto-linking is enabled
        if (!get_option('odse_auto_linking_enabled', 1)) {
            return $content;
        }
        
        $post_id = get_the_ID();
        
        // Check if auto-linking is disabled for this post
        if (get_post_meta($post_id, '_odse_disable_auto_linking', true)) {
            return $content;
        }
        
        // Get suggested links from database
        $links = $this->get_suggested_links($post_id);
        
        if (empty($links)) {
            return $content;
        }
        
        // Apply links to content
        $content = $this->apply_links($content, $links);
        
        return $content;
    }
    
    /**
     * Get suggested links for post
     */
    private function get_suggested_links($post_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'odse_internal_links';
        
        $links = $wpdb->get_results($wpdb->prepare("
            SELECT il.*, p.guid
            FROM $table il
            INNER JOIN {$wpdb->posts} p ON il.target_post_id = p.ID
            WHERE il.source_post_id = %d
            AND p.post_status = 'publish'
            ORDER BY 
                CASE il.priority
                    WHEN 'high' THEN 1
                    WHEN 'medium' THEN 2
                    ELSE 3
                END,
                il.created_at DESC
            LIMIT %d
        ", $post_id, $this->max_links_per_post));
        
        return $links;
    }
    
    /**
     * Apply links to content
     */
    private function apply_links($content, $links) {
        $links_added = 0;
        $used_anchors = [];
        
        foreach ($links as $link) {
            if ($links_added >= $this->max_links_per_post) {
                break;
            }
            
            $anchor_text = $link->anchor_text;
            
            // Skip if this anchor text was already used
            if (in_array($anchor_text, $used_anchors)) {
                continue;
            }
            
            // Skip if link already exists in content
            if ($this->link_exists($content, $link->target_post_id)) {
                continue;
            }
            
            // Create the link
            $target_url = get_permalink($link->target_post_id);
            $replacement = sprintf(
                '<a href="%s" class="odse-auto-link" data-priority="%s" title="%s">%s</a>',
                esc_url($target_url),
                esc_attr($link->priority),
                esc_attr(get_the_title($link->target_post_id)),
                esc_html($anchor_text)
            );
            
            // Find and replace first occurrence
            $pattern = '/\b' . preg_quote($anchor_text, '/') . '\b/u';
            
            $replaced = false;
            $content = preg_replace_callback($pattern, function($matches) use ($replacement, &$replaced) {
                if ($replaced) {
                    return $matches[0];
                }
                $replaced = true;
                return $replacement;
            }, $content, 1);
            
            if ($replaced) {
                $links_added++;
                $used_anchors[] = $anchor_text;
            }
        }
        
        return $content;
    }
    
    /**
     * Check if link to post already exists in content
     */
    private function link_exists($content, $post_id) {
        $permalink = get_permalink($post_id);
        return strpos($content, $permalink) !== false;
    }
}
