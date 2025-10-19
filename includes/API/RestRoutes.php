<?php
namespace OrsozoxDivineSEO\API;

/**
 * REST API Routes Class
 */
class RestRoutes {
    
    private $namespace = 'orsozox-divine-seo/v1';
    
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Analyze post endpoint
        register_rest_route($this->namespace, '/analyze/(?P<id>\d+)', [
            'methods' => 'POST',
            'callback' => [$this, 'analyze_post'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'id' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ]
            ]
        ]);
        
        // Get stats endpoint
        register_rest_route($this->namespace, '/stats', [
            'methods' => 'GET',
            'callback' => [$this, 'get_stats'],
            'permission_callback' => [$this, 'check_permission']
        ]);
        
        // Get keyword conflicts
        register_rest_route($this->namespace, '/keywords/conflicts', [
            'methods' => 'GET',
            'callback' => [$this, 'get_conflicts'],
            'permission_callback' => [$this, 'check_permission']
        ]);
        
        // Get internal links
        register_rest_route($this->namespace, '/links/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_links'],
            'permission_callback' => [$this, 'check_permission']
        ]);
    }
    
    /**
     * Check permissions
     */
    public function check_permission() {
        return current_user_can('edit_posts');
    }
    
    /**
     * Analyze post endpoint
     */
    public function analyze_post($request) {
        $post_id = $request->get_param('id');
        $post = get_post($post_id);
        
        if (!$post) {
            return new \WP_Error('invalid_post', 'Post not found', ['status' => 404]);
        }
        
        $engine = new \OrsozoxDivineSEO\AI\Engine();
        $result = $engine->analyze_content($post->post_content, $post->post_title);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Save analysis
        update_post_meta($post_id, '_odse_analysis', $result);
        
        return rest_ensure_response([
            'success' => true,
            'data' => $result
        ]);
    }
    
    /**
     * Get dashboard stats
     */
    public function get_stats() {
        global $wpdb;
        
        $stats = [
            'total_posts' => wp_count_posts('post')->publish,
            'analyzed_posts' => $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$wpdb->prefix}odse_post_topics"),
            'internal_links' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}odse_internal_links"),
            'topic_clusters' => $wpdb->get_var("SELECT COUNT(DISTINCT topic_name) FROM {$wpdb->prefix}odse_post_topics")
        ];
        
        return rest_ensure_response($stats);
    }
    
    /**
     * Get keyword conflicts
     */
    public function get_conflicts() {
        $engine = new \OrsozoxDivineSEO\AI\Engine();
        $conflicts = $engine->detect_cannibalization();
        
        return rest_ensure_response([
            'conflicts' => $conflicts,
            'count' => count($conflicts)
        ]);
    }
    
    /**
     * Get internal links for post
     */
    public function get_links($request) {
        $post_id = $request->get_param('id');
        
        global $wpdb;
        $table = $wpdb->prefix . 'odse_internal_links';
        
        $links = $wpdb->get_results($wpdb->prepare("
            SELECT il.*, p.post_title
            FROM $table il
            INNER JOIN {$wpdb->posts} p ON il.target_post_id = p.ID
            WHERE il.source_post_id = %d
        ", $post_id));
        
        return rest_ensure_response($links);
    }
}
