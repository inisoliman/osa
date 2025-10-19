<?php
namespace OrsozoxDivineSEO\Cron;

/**
 * Cron Scheduler Class
 */
class Scheduler {
    
    public function __construct() {
        // Register cron actions
        add_action('odse_daily_analysis', [$this, 'daily_analysis']);
        add_action('odse_weekly_cannibalization_check', [$this, 'weekly_cannibalization_check']);
        
        // Add custom cron schedules
        add_filter('cron_schedules', [$this, 'add_cron_schedules']);
    }
    
    /**
     * Add custom cron schedules
     */
    public function add_cron_schedules($schedules) {
        $schedules['weekly'] = [
            'interval' => 604800, // 7 days
            'display'  => __('Once Weekly', 'orsozox-divine-seo')
        ];
        
        return $schedules;
    }
    
    /**
     * Daily analysis task
     * Analyzes recently published posts
     */
    public function daily_analysis() {
        // Get posts published in last 24 hours without analysis
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 10,
            'date_query' => [
                [
                    'after' => '24 hours ago'
                ]
            ],
            'meta_query' => [
                [
                    'key' => '_odse_analysis',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ]);
        
        if (empty($posts)) {
            return;
        }
        
        $engine = new \OrsozoxDivineSEO\AI\Engine();
        
        foreach ($posts as $post) {
            $result = $engine->analyze_content($post->post_content, $post->post_title);
            
            if (!is_wp_error($result)) {
                update_post_meta($post->ID, '_odse_analysis', $result);
                
                // Small delay to avoid rate limits
                sleep(2);
            }
        }
        
        // Log completion
        update_option('odse_last_daily_analysis', current_time('mysql'));
    }
    
    /**
     * Weekly cannibalization check
     */
    public function weekly_cannibalization_check() {
        $engine = new \OrsozoxDivineSEO\AI\Engine();
        $conflicts = $engine->detect_cannibalization();
        
        // Save conflicts
        update_option('odse_keyword_conflicts', $conflicts);
        
        // Send notification to admin if conflicts found
        if (!empty($conflicts)) {
            $admin_email = get_option('admin_email');
            $count = count($conflicts);
            
            $subject = sprintf(
                __('[%s] Keyword Cannibalization Detected', 'orsozox-divine-seo'),
                get_bloginfo('name')
            );
            
            $message = sprintf(
                __('Hello,\n\nThe weekly SEO check has detected %d keyword conflicts on your site.\n\nPlease review them in the Divine SEO dashboard: %s\n\nBest regards,\nOrsozox Divine SEO', 'orsozox-divine-seo'),
                $count,
                admin_url('admin.php?page=orsozox-divine-seo-keywords')
            );
            
            wp_mail($admin_email, $subject, $message);
        }
        
        // Log completion
        update_option('odse_last_cannibalization_check', current_time('mysql'));
    }
}
