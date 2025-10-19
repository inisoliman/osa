<?php
namespace OrsozoxDivineSEO\Admin;

/**
 * AJAX Handlers Class
 */
class AjaxHandlers {
    
    public function __construct() {
        // Analysis actions
        add_action('wp_ajax_odse_analyze_post', [$this, 'analyze_post']);
        add_action('wp_ajax_odse_analyze_all', [$this, 'analyze_all_posts']);
        add_action('wp_ajax_odse_test_ai', [$this, 'test_ai_connection']);
        
        // Linking actions
        add_action('wp_ajax_odse_suggest_links', [$this, 'suggest_links']);
        add_action('wp_ajax_odse_remove_link', [$this, 'remove_link']);
        
        // Keyword actions
        add_action('wp_ajax_odse_assign_keyword', [$this, 'assign_keyword']);
        add_action('wp_ajax_odse_detect_cannibalization', [$this, 'detect_cannibalization']);
    }
    
    /**
     * Analyze single post
     */
    public function analyze_post() {
        check_ajax_referer('odse_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error('Invalid post');
        }
        
        $engine = new \OrsozoxDivineSEO\AI\Engine();
        $result = $engine->analyze_content($post->post_content, $post->post_title);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        // Save analysis
        update_post_meta($post_id, '_odse_analysis', $result);
        
        // Save topics to database
        if (!empty($result['primary_keywords'])) {
            global $wpdb;
            $table = $wpdb->prefix . 'odse_post_topics';
            
            // Delete old topics
            $wpdb->delete($table, ['post_id' => $post_id]);
            
            // Insert new topics
            foreach ($result['primary_keywords'] as $keyword) {
                $wpdb->insert($table, [
                    'post_id' => $post_id,
                    'topic_name' => $keyword,
                    'topic_slug' => sanitize_title($keyword),
                    'is_primary' => 1
                ]);
            }
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Analyze all posts (batch processing)
     */
    public function analyze_all_posts() {
        check_ajax_referer('odse_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $batch_size = 5; // Process 5 posts at a time
        
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $batch_size,
            'offset' => $offset,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);
        
        if (empty($posts)) {
            wp_send_json_success([
                'completed' => true,
                'message' => 'All posts analyzed successfully!'
            ]);
        }
        
        $engine = new \OrsozoxDivineSEO\AI\Engine();
        $processed = 0;
        
        foreach ($posts as $post) {
            $result = $engine->analyze_content($post->post_content, $post->post_title);
            
            if (!is_wp_error($result)) {
                update_post_meta($post->ID, '_odse_analysis', $result);
                $processed++;
            }
            
            // Small delay to avoid rate limits
            usleep(500000); // 0.5 seconds
        }
        
        wp_send_json_success([
            'completed' => false,
            'processed' => $processed,
            'offset' => $offset + $batch_size,
            'message' => sprintf('Processed %d posts...', $offset + $processed)
        ]);
    }
    
    /**
     * Test AI connection
     */
    public function test_ai_connection() {
        check_ajax_referer('odse_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $engine = new \OrsozoxDivineSEO\AI\Engine();
        $result = $engine->test_connection();
        
        wp_send_json($result);
    }
    
    /**
     * Suggest internal links for post
     */
    public function suggest_links() {
        check_ajax_referer('odse_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $post_id = intval($_POST['post_id']);
        
        $engine = new \OrsozoxDivineSEO\AI\Engine();
        $suggestions = $engine->suggest_internal_links($post_id);
        
        if (is_wp_error($suggestions)) {
            wp_send_json_error($suggestions->get_error_message());
        }
        
        // Save suggestions to database
        if (!empty($suggestions['suggestions'])) {
            global $wpdb;
            $table = $wpdb->prefix . 'odse_internal_links';
            
            foreach ($suggestions['suggestions'] as $suggestion) {
                $wpdb->insert($table, [
                    'source_post_id' => $post_id,
                    'target_post_id' => $suggestion['post_id'],
                    'anchor_text' => $suggestion['anchor_text'],
                    'priority' => $suggestion['priority'] ?? 'medium',
                    'ai_generated' => 1
                ]);
            }
        }
        
        wp_send_json_success($suggestions);
    }
    
    /**
     * Remove internal link
     */
    public function remove_link() {
        check_ajax_referer('odse_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $link_id = intval($_POST['link_id']);
        
        global $wpdb;
        $table = $wpdb->prefix . 'odse_internal_links';
        
        $wpdb->delete($table, ['id' => $link_id]);
        
        wp_send_json_success('Link removed');
    }
    
    /**
     * Assign keyword to post
     */
    public function assign_keyword() {
        check_ajax_referer('odse_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $keyword = sanitize_text_field($_POST['keyword']);
        $post_id = intval($_POST['post_id']);
        
        $keyword_map = new KeywordMapPage();
        $result = $keyword_map->assign_keyword($keyword, $post_id);
        
        wp_send_json_success('Keyword assigned');
    }
    
    /**
     * Detect keyword cannibalization
     */
    public function detect_cannibalization() {
        check_ajax_referer('odse_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $engine = new \OrsozoxDivineSEO\AI\Engine();
        $conflicts = $engine->detect_cannibalization();
        
        wp_send_json_success([
            'conflicts' => $conflicts,
            'count' => count($conflicts)
        ]);
    }

    /**
     * ✅ جديد: تحليل المقالات القديمة من Dashboard (زر يدوي)
     */
    public function analyze_old_posts() {
        check_ajax_referer('odse_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $batch_size = 5; // 5 مقالات في المرة
        
        // جلب المقالات القديمة غير المحللة
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $batch_size,
            'offset' => $offset,
            'orderby' => 'date',
            'order' => 'ASC', // من الأقدم للأحدث
            'meta_query' => [
                [
                    'key' => '_odse_analysis',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ]);
        
        if (empty($posts)) {
            // احسب إجمالي المقالات المحللة
            global $wpdb;
            $total_analyzed = $wpdb->get_var("
                SELECT COUNT(DISTINCT post_id) 
                FROM {$wpdb->postmeta} 
                WHERE meta_key = '_odse_analysis'
            ");
            
            wp_send_json_success([
                'completed' => true,
                'total_analyzed' => $total_analyzed,
                'message' => '✅ تم تحليل جميع المقالات القديمة بنجاح!'
            ]);
        }
        
        $engine = new \OrsozoxDivineSEO\AI\Engine();
        $processed = 0;
        $post_titles = [];
        
        foreach ($posts as $post) {
            $result = $engine->analyze_content($post->post_content, $post->post_title);
            
            if (!is_wp_error($result)) {
                // حفظ التحليل
                update_post_meta($post->ID, '_odse_analysis', $result);
                
                // حفظ في قاعدة البيانات
                if (!empty($result['primary_keywords'])) {
                    global $wpdb;
                    $table = $wpdb->prefix . 'odse_post_topics';
                    
                    // حذف القديم
                    $wpdb->delete($table, ['post_id' => $post->ID]);
                    
                    // إضافة الجديد
                    foreach ($result['primary_keywords'] as $keyword) {
                        $wpdb->insert($table, [
                            'post_id' => $post->ID,
                            'topic_name' => $keyword,
                            'topic_slug' => sanitize_title($keyword),
                            'is_primary' => 1,
                            'confidence_score' => 0.90
                        ]);
                    }
                }
                
                $processed++;
                $post_titles[] = $post->post_title;
            }
            
            // تأخير صغير
            usleep(500000); // 0.5 ثانية
        }
        
        wp_send_json_success([
            'completed' => false,
            'processed' => $processed,
            'offset' => $offset + $batch_size,
            'message' => sprintf('تم تحليل %d مقالات... (%s)', $offset + $processed, implode(', ', array_slice($post_titles, 0, 2))),
            'post_titles' => $post_titles
        ]);
    }
    
    /**
     * ✅ جديد: الحصول على حالة التحليل الشامل التلقائي
     */
    public function get_bulk_analysis_status() {
        check_ajax_referer('odse_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $status = get_option('odse_bulk_analysis_status', [
            'status' => 'not_started',
            'processed' => 0
        ]);
        
        // احسب إجمالي المقالات غير المحللة
        global $wpdb;
        $remaining = $wpdb->get_var("
            SELECT COUNT(p.ID) 
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_odse_analysis'
            WHERE p.post_type = 'post' 
            AND p.post_status = 'publish'
            AND pm.post_id IS NULL
        ");
        
        $status['remaining'] = intval($remaining);
        $status['total_posts'] = wp_count_posts('post')->publish;
        
        wp_send_json_success($status);
    }
    
}

