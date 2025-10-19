<?php
namespace OrsozoxDivineSEO\Admin;

/**
 * Meta Boxes Class
 */
class MetaBoxes {
    
    public function __construct() {
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_box']);
    }
    
    /**
     * Add meta boxes to post edit screen
     */
    public function add_meta_boxes() {
        add_meta_box(
            'odse_analysis',
            __('Divine SEO Analysis', 'orsozox-divine-seo'),
            [$this, 'render_analysis_meta_box'],
            'post',
            'side',
            'high'
        );
        
        add_meta_box(
            'odse_internal_links',
            __('Suggested Internal Links', 'orsozox-divine-seo'),
            [$this, 'render_links_meta_box'],
            'post',
            'normal',
            'default'
        );
    }
    
    /**
     * Render analysis meta box
     */
    public function render_analysis_meta_box($post) {
        wp_nonce_field('odse_meta_box', 'odse_meta_box_nonce');
        
        $analysis = get_post_meta($post->ID, '_odse_analysis', true);
        
        echo '<div class="odse-meta-box">';
        
        if ($analysis) {
            echo '<p><strong>' . __('Main Topic:', 'orsozox-divine-seo') . '</strong> ' . esc_html($analysis['main_topic'] ?? '') . '</p>';
            
            if (!empty($analysis['primary_keywords'])) {
                echo '<p><strong>' . __('Keywords:', 'orsozox-divine-seo') . '</strong><br>';
                echo implode(', ', array_map('esc_html', $analysis['primary_keywords']));
                echo '</p>';
            }
        } else {
            echo '<p>' . __('Not analyzed yet', 'orsozox-divine-seo') . '</p>';
        }
        
        echo '<button type="button" class="button button-primary" id="odse-analyze-post" data-post-id="' . $post->ID . '">';
        echo '<span class="dashicons dashicons-analytics"></span> ';
        echo __('Analyze Now', 'orsozox-divine-seo');
        echo '</button>';
        
        echo '<div id="odse-analysis-result"></div>';
        
        echo '</div>';
    }
    
    /**
     * Render internal links meta box
     */
    public function render_links_meta_box($post) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'odse_internal_links';
        
        $links = $wpdb->get_results($wpdb->prepare("
            SELECT il.*, p.post_title, p.guid
            FROM $table il
            INNER JOIN {$wpdb->posts} p ON il.target_post_id = p.ID
            WHERE il.source_post_id = %d
            ORDER BY il.priority DESC, il.created_at DESC
        ", $post->ID));
        
        echo '<div class="odse-links-box">';
        
        if ($links) {
            echo '<table class="widefat">';
            echo '<thead><tr>';
            echo '<th>' . __('Target Post', 'orsozox-divine-seo') . '</th>';
            echo '<th>' . __('Anchor Text', 'orsozox-divine-seo') . '</th>';
            echo '<th>' . __('Priority', 'orsozox-divine-seo') . '</th>';
            echo '<th>' . __('Actions', 'orsozox-divine-seo') . '</th>';
            echo '</tr></thead><tbody>';
            
            foreach ($links as $link) {
                echo '<tr>';
                echo '<td>' . esc_html($link->post_title) . '</td>';
                echo '<td>' . esc_html($link->anchor_text) . '</td>';
                echo '<td><span class="priority-' . esc_attr($link->priority) . '">' . esc_html($link->priority) . '</span></td>';
                echo '<td><button type="button" class="button button-small odse-remove-link" data-link-id="' . $link->id . '">حذف</button></td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        } else {
            echo '<p>' . __('No suggested links yet', 'orsozox-divine-seo') . '</p>';
        }
        
        echo '<button type="button" class="button" id="odse-suggest-links" data-post-id="' . $post->ID . '">';
        echo '<span class="dashicons dashicons-admin-links"></span> ';
        echo __('Generate Link Suggestions', 'orsozox-divine-seo');
        echo '</button>';
        
        echo '</div>';
    }
    
    /**
     * Save meta box data
     */
    public function save_meta_box($post_id) {
        // Check nonce
        if (!isset($_POST['odse_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['odse_meta_box_nonce'], 'odse_meta_box')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Auto-analyze on save if enabled
        if (get_option('odse_auto_analyze_new_posts')) {
            $post = get_post($post_id);
            if ($post && $post->post_status === 'publish') {
                $engine = new \OrsozoxDivineSEO\AI\Engine();
                $analysis = $engine->analyze_content($post->post_content, $post->post_title);
                
                if (!is_wp_error($analysis)) {
                    update_post_meta($post_id, '_odse_analysis', $analysis);
                }
            }
        }
    }
}
