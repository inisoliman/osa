<?php
namespace OrsozoxDivineSEO\Analytics;

/**
 * Analytics Tracker Class
 */
class Tracker {
    
    public function __construct() {
        // Track post views
        add_action('wp_head', [$this, 'track_post_view']);
        
        // Track link clicks
        add_action('wp_footer', [$this, 'add_click_tracking_script']);
    }
    
    /**
     * Track post views
     */
    public function track_post_view() {
        if (!is_single()) {
            return;
        }
        
        $post_id = get_the_ID();
        
        // Update view count
        $views = get_post_meta($post_id, '_odse_views', true);
        $views = $views ? intval($views) + 1 : 1;
        update_post_meta($post_id, '_odse_views', $views);
        
        // Log to analytics table
        $this->log_metric($post_id, 'view', [
            'timestamp' => current_time('mysql'),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'referer' => wp_get_referer()
        ]);
    }
    
    /**
     * Add JavaScript for click tracking
     */
    public function add_click_tracking_script() {
        if (!is_single()) {
            return;
        }
        
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('.odse-auto-link').on('click', function() {
                var link = $(this);
                var targetUrl = link.attr('href');
                var anchorText = link.text();
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'odse_track_link_click',
                        post_id: <?php echo get_the_ID(); ?>,
                        target_url: targetUrl,
                        anchor_text: anchorText
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Log metric to database
     */
    private function log_metric($post_id, $metric_type, $metric_value) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'odse_analytics';
        
        $wpdb->insert($table, [
            'post_id' => $post_id,
            'metric_type' => $metric_type,
            'metric_value' => json_encode($metric_value)
        ]);
    }
    
    /**
     * Get analytics for post
     */
    public function get_post_analytics($post_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'odse_analytics';
        
        $analytics = $wpdb->get_results($wpdb->prepare("
            SELECT metric_type, COUNT(*) as count
            FROM $table
            WHERE post_id = %d
            GROUP BY metric_type
        ", $post_id));
        
        $result = [
            'views' => 0,
            'clicks' => 0
        ];
        
        foreach ($analytics as $metric) {
            if ($metric->metric_type === 'view') {
                $result['views'] = intval($metric->count);
            } elseif ($metric->metric_type === 'link_click') {
                $result['clicks'] = intval($metric->count);
            }
        }
        
        return $result;
    }
}
