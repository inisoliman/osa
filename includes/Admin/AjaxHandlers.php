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
        add_action('wp_ajax_odse_build_internal_links', [$this, 'build_internal_links']);
        
        // Keyword actions
        add_action('wp_ajax_odse_assign_keyword', [$this, 'assign_keyword']);
        add_action('wp_ajax_odse_detect_cannibalization', [$this, 'detect_cannibalization']);
        add_action('wp_ajax_odse_resolve_cannibalization', [$this, 'resolve_cannibalization']);
        
        // Old posts actions
        add_action('wp_ajax_odse_analyze_old_posts', [$this, 'analyze_old_posts']);
        add_action('wp_ajax_odse_get_bulk_analysis_status', [$this, 'get_bulk_analysis_status']);
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
        $batch_size = 5;
        
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
            
            usleep(500000);
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
     * ✅ Build internal links for all posts
     */
    public function build_internal_links() {
        check_ajax_referer('odse_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $batch_size = 5;
        
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $batch_size,
            'offset' => $offset,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => [
                [
                    'key' => '_odse_analysis',
                    'compare' => 'EXISTS'
                ]
            ]
        ]);
        
        if (empty($posts)) {
            global $wpdb;
            $total_links = $wpdb->get_var("
                SELECT COUNT(*) 
                FROM {$wpdb->prefix}odse_internal_links
            ");
            
            wp_send_json_success([
                'completed' => true,
                'total_links' => $total_links,
                'message' => '✅ تم بناء الروابط الداخلية بنجاح!'
            ]);
        }
        
        $engine = new \OrsozoxDivineSEO\AI\Engine();
        $processed = 0;
        $links_created = 0;
        
        foreach ($posts as $post) {
            $result = $engine->suggest_internal_links($post->ID);
            
            if (!is_wp_error($result) && !empty($result['suggestions'])) {
                global $wpdb;
                $table = $wpdb->prefix . 'odse_internal_links';
                
                $wpdb->delete($table, ['source_post_id' => $post->ID]);
                
                foreach ($result['suggestions'] as $suggestion) {
                    $wpdb->insert($table, [
                        'source_post_id' => $post->ID,
                        'target_post_id' => $suggestion['post_id'],
                        'anchor_text' => $suggestion['anchor_text'],
                        'priority' => $suggestion['priority'] ?? 'medium',
                        'ai_generated' => 1
                    ]);
                    $links_created++;
                }
                
                $processed++;
            }
            
            usleep(500000);
        }
        
        wp_send_json_success([
            'completed' => false,
            'processed' => $processed,
            'links_created' => $links_created,
            'offset' => $offset + $batch_size,
            'message' => sprintf('تم معالجة %d مقالات... (إنشاء %d روابط)', $offset + $processed, $links_created)
        ]);
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
     * ✅ جديد: حل تنافس الكلمات المفتاحية تلقائياً بالـ AI
     */
    public function resolve_cannibalization() {
        check_ajax_referer('odse_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $batch_size = 10; // 10 تنافسات في المرة
        
        $engine = new \OrsozoxDivineSEO\AI\Engine();
        $all_conflicts = $engine->detect_cannibalization();
        
        if (empty($all_conflicts)) {
            wp_send_json_success([
                'completed' => true,
                'total_resolved' => 0,
                'message' => '✅ لا يوجد تنافسات للحل!'
            ]);
        }
        
        // خذ دفعة من التنافسات
        $conflicts_batch = array_slice($all_conflicts, $offset, $batch_size, true);
        
        if (empty($conflicts_batch)) {
            wp_send_json_success([
                'completed' => true,
                'total_resolved' => $offset,
                'message' => '✅ تم حل جميع التنافسات بنجاح!'
            ]);
        }
        
        $resolved = 0;
        
        foreach ($conflicts_batch as $keyword => $posts) {
            // تحليل المقالات واختيار الأقوى
            $best_post = $this->select_best_post_for_keyword($posts, $keyword);
            
            if ($best_post) {
                // حفظ الكلمة المفتاحية للمقال الأقوى
                update_post_meta($best_post['id'], 'rank_math_focus_keyword', $keyword);
                
                // تعديل الكلمات المفتاحية للمقالات الأخرى
                foreach ($posts as $post) {
                    if ($post['id'] != $best_post['id']) {
                        // احذف الكلمة المتنافسة
                        $current_keywords = get_post_meta($post['id'], 'rank_math_focus_keyword', true);
                        $keywords_array = array_filter(array_map('trim', explode(',', $current_keywords)));
                        
                        // احذف الكلمة المتنافسة
                        $keywords_array = array_filter($keywords_array, function($k) use ($keyword) {
                            return strtolower($k) !== strtolower($keyword);
                        });
                        
                        // احفظ الكلمات المتبقية
                        update_post_meta($post['id'], 'rank_math_focus_keyword', implode(', ', $keywords_array));
                    }
                }
                
                $resolved++;
            }
        }
        
        wp_send_json_success([
            'completed' => false,
            'resolved' => $resolved,
            'offset' => $offset + $batch_size,
            'total_conflicts' => count($all_conflicts),
            'message' => sprintf('تم حل %d تنافسات... (الإجمالي: %d / %d)', $resolved, $offset + $resolved, count($all_conflicts))
        ]);
    }
    
    /**
     * ✅ اختيار المقال الأقوى للكلمة المفتاحية
     */
    private function select_best_post_for_keyword($posts, $keyword) {
        $scores = [];
        
        foreach ($posts as $post) {
            $post_obj = get_post($post['id']);
            if (!$post_obj) continue;
            
            $score = 0;
            
            // 1. طول المحتوى (40%)
            $content_length = strlen(wp_strip_all_tags($post_obj->post_content));
            $score += ($content_length / 100) * 0.4;
            
            // 2. الكلمة في العنوان (30%)
            if (stripos($post_obj->post_title, $keyword) !== false) {
                $score += 30;
            }
            
            // 3. تاريخ النشر - الأحدث أفضل (15%)
            $days_old = (time() - strtotime($post_obj->post_date)) / DAY_IN_SECONDS;
            $score += max(0, 15 - ($days_old / 30)); // كل شهر يقلل النقاط
            
            // 4. عدد الكلمات المفتاحية الأخرى (10%)
            $other_keywords = get_post_meta($post['id'], 'rank_math_focus_keyword', true);
            $keywords_count = count(array_filter(explode(',', $other_keywords)));
            $score += max(0, 10 - $keywords_count); // الأقل كلمات أفضل
            
            // 5. التحليل بالـ AI (5%)
            $analysis = get_post_meta($post['id'], '_odse_analysis', true);
            if (!empty($analysis)) {
                $score += 5;
            }
            
            $scores[$post['id']] = [
                'id' => $post['id'],
                'title' => $post['title'],
                'score' => $score
            ];
        }
        
        // رتب حسب النقاط
        usort($scores, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        return $scores[0] ?? null;
    }
    
    /**
     * Analyze old posts
     */
    public function analyze_old_posts() {
        check_ajax_referer('odse_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $batch_size = 5;
        
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $batch_size,
            'offset' => $offset,
            'orderby' => 'date',
            'order' => 'ASC',
            'meta_query' => [
                [
                    'key' => '_odse_analysis',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ]);
        
        if (empty($posts)) {
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
                update_post_meta($post->ID, '_odse_analysis', $result);
                
                if (!empty($result['primary_keywords'])) {
                    global $wpdb;
                    $table = $wpdb->prefix . 'odse_post_topics';
                    
                    $wpdb->delete($table, ['post_id' => $post->ID]);
                    
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
            
            usleep(500000);
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
     * Get bulk analysis status
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
