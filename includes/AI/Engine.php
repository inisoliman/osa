<?php
namespace OrsozoxDivineSEO\AI;

/**
 * AI Engine Class
 * Updated October 2025 - Latest Models
 */
class Engine {
    
    private $api_key;
    private $model = 'gpt-4o';
    private $provider = 'openai';
    private $temperature = 0.3;
    
    public function __construct() {
        $this->provider = get_option('odse_ai_provider', 'openai');
        
        switch($this->provider) {
            case 'openai':
                $this->api_key = get_option('odse_ai_api_key');
                $this->model = get_option('odse_ai_model', 'gpt-4o');
                break;
            case 'claude':
                $this->api_key = get_option('odse_claude_api_key');
                $this->model = 'claude-3-5-sonnet-20241022';
                break;
            case 'gemini':
                $this->api_key = get_option('odse_gemini_api_key');
                // ✅ النموذج الصحيح بدون -latest
                $this->model = 'gemini-1.5-flash-8b';
                break;
            case 'groq':
                $this->api_key = get_option('odse_groq_api_key');
                // ✅ النموذج المحدث (3.3 بدلاً من 3.1)
                $this->model = 'llama-3.3-70b-versatile';
                break;
        }
    }
    
    /**
     * Analyze content and extract topics, keywords, themes
     */
    public function analyze_content($content, $title = '') {
        $cache_key = 'odse_analysis_' . md5($content . $title);
        $cached = $this->get_from_cache($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $prompt = $this->build_analysis_prompt($content, $title);
        $response = $this->make_api_request($prompt);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $result = $this->parse_analysis_response($response);
        
        if (!is_wp_error($result)) {
            $this->save_to_cache($cache_key, $result, 'analysis');
        }
        
        return $result;
    }
    
    private function build_analysis_prompt($content, $title) {
        return sprintf(
            "أنت خبير SEO متخصص في تحليل المحتوى المسيحي الديني باللغة العربية.

قم بتحليل هذا المقال وقدم الإجابة بصيغة JSON فقط:

**العنوان:** %s
**المحتوى:** %s

قدم JSON فقط بدون أي نص إضافي:
{
  \"main_topic\": \"\",
  \"subtopics\": [],
  \"primary_keywords\": [],
  \"secondary_keywords\": [],
  \"biblical_themes\": [],
  \"content_type\": \"\",
  \"content_depth\": \"\",
  \"linking_keywords\": [],
  \"recommended_pillar\": \"\"
}",
            $title,
            $this->clean_content($content)
        );
    }
    
    private function clean_content($content) {
        $content = wp_strip_all_tags($content);
        $content = preg_replace('/\s+/', ' ', $content);
        
        if (strlen($content) > 4000) {
            $content = substr($content, 0, 4000) . '...';
        }
        
        return trim($content);
    }
    
    private function make_api_request($prompt, $system_message = '') {
        if (empty($this->api_key)) {
            return new \WP_Error('no_api_key', __('No API key configured', 'orsozox-divine-seo'));
        }
        
        switch($this->provider) {
            case 'openai':
                return $this->openai_request($prompt, $system_message);
            case 'claude':
                return $this->claude_request($prompt, $system_message);
            case 'gemini':
                return $this->gemini_request($prompt, $system_message);
            case 'groq':
                return $this->groq_request($prompt, $system_message);
            default:
                return new \WP_Error('invalid_provider', __('Invalid AI provider', 'orsozox-divine-seo'));
        }
    }
    
    private function openai_request($prompt, $system_message = '') {
        $messages = [];
        
        if (!empty($system_message)) {
            $messages[] = ['role' => 'system', 'content' => $system_message];
        } else {
            $messages[] = ['role' => 'system', 'content' => 'You are an expert SEO analyst. Always respond in valid JSON format.'];
        }
        
        $messages[] = ['role' => 'user', 'content' => $prompt];
        
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => $this->temperature,
                'response_format' => ['type' => 'json_object']
            ]),
            'timeout' => 60
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new \WP_Error('api_error', $body['error']['message']);
        }
        
        return $body['choices'][0]['message']['content'] ?? '';
    }
    
    private function claude_request($prompt, $system_message = '') {
        $headers = [
            'x-api-key' => $this->api_key,
            'Content-Type' => 'application/json',
            'anthropic-version' => '2023-06-01'
        ];
        
        $body_data = [
            'model' => $this->model,
            'max_tokens' => 4096,
            'temperature' => $this->temperature,
            'messages' => [['role' => 'user', 'content' => $prompt]]
        ];
        
        if (!empty($system_message)) {
            $body_data['system'] = $system_message;
        }
        
        $response = wp_remote_post('https://api.anthropic.com/v1/messages', [
            'headers' => $headers,
            'body' => json_encode($body_data),
            'timeout' => 60
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new \WP_Error('api_error', $body['error']['message']);
        }
        
        return $body['content'][0]['text'] ?? '';
    }
    
    /**
     * Gemini API Request - محدث أكتوبر 2025
     */
    private function gemini_request($prompt, $system_message = '') {
        $full_prompt = !empty($system_message) ? $system_message . "\n\n" . $prompt : $prompt;
        
        // ✅ استخدام النموذج الصحيح
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $this->model . ':generateContent?key=' . $this->api_key;
        
        $response = wp_remote_post($url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode([
                'contents' => [['parts' => [['text' => $full_prompt]]]]
            ]),
            'timeout' => 60,
            'sslverify' => false
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            return $body['candidates'][0]['content']['parts'][0]['text'];
        }
        
        if (isset($body['error']['message'])) {
            return new \WP_Error('api_error', $body['error']['message']);
        }
        
        return new \WP_Error('parse_error', 'Could not parse Gemini response');
    }
    
    /**
     * Groq API Request - محدث أكتوبر 2025
     */
    private function groq_request($prompt, $system_message = '') {
        $messages = [];
        
        if (!empty($system_message)) {
            $messages[] = ['role' => 'system', 'content' => $system_message];
        } else {
            $messages[] = ['role' => 'system', 'content' => 'You are an expert SEO analyst. Always respond in valid JSON format.'];
        }
        
        $messages[] = ['role' => 'user', 'content' => $prompt];
        
        $response = wp_remote_post('https://api.groq.com/openai/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'model' => $this->model, // llama-3.3-70b-versatile
                'messages' => $messages,
                'temperature' => $this->temperature,
                'response_format' => ['type' => 'json_object']
            ]),
            'timeout' => 60
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new \WP_Error('api_error', $body['error']['message']);
        }
        
        return $body['choices'][0]['message']['content'] ?? '';
    }
    
    private function parse_analysis_response($response) {
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_Error('json_error', __('Invalid JSON response from AI', 'orsozox-divine-seo'));
        }
        
        return $data;
    }
    
    public function detect_cannibalization() {
        global $wpdb;
        
        $posts = $wpdb->get_results("
            SELECT p.ID, p.post_title, pm.meta_value as focus_keyword
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE pm.meta_key = 'rank_math_focus_keyword'
            AND p.post_status = 'publish'
            AND p.post_type = 'post'
        ");
        
        $keyword_map = [];
        $conflicts = [];
        
        foreach($posts as $post) {
            if (empty($post->focus_keyword)) continue;
            
            $keywords = explode(',', $post->focus_keyword);
            foreach($keywords as $keyword) {
                $keyword = trim(strtolower($keyword));
                if(empty($keyword)) continue;
                
                if(!isset($keyword_map[$keyword])) {
                    $keyword_map[$keyword] = [];
                }
                $keyword_map[$keyword][] = [
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'url' => get_permalink($post->ID)
                ];
            }
        }
        
        foreach($keyword_map as $keyword => $posts_array) {
            if(count($posts_array) > 1) {
                $conflicts[$keyword] = $posts_array;
            }
        }
        
        return $conflicts;
    }
    
    public function suggest_internal_links($post_id) {
        $post = get_post($post_id);
        if(!$post) return new \WP_Error('invalid_post', __('Invalid post ID', 'orsozox-divine-seo'));
        
        $content = $post->post_content;
        $title = $post->post_title;
        
        $related_posts = $this->find_related_posts($post_id);
        
        if (empty($related_posts)) {
            return ['suggestions' => []];
        }
        
        $prompt = $this->build_linking_prompt($content, $title, $related_posts);
        $response = $this->make_api_request($prompt);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return $this->parse_linking_suggestions($response);
    }
    
    private function find_related_posts($post_id, $limit = 15) {
        global $wpdb;
        
        $topics = $wpdb->get_col($wpdb->prepare("
            SELECT topic_name FROM {$wpdb->prefix}odse_post_topics
            WHERE post_id = %d
        ", $post_id));
        
        if(empty($topics)) {
            $categories = wp_get_post_categories($post_id);
            if (empty($categories)) {
                return [];
            }
            
            return get_posts([
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => $limit,
                'post__not_in' => [$post_id],
                'category__in' => $categories
            ]);
        }
        
        $placeholders = implode(',', array_fill(0, count($topics), '%s'));
        $params = array_merge($topics, [$post_id, $limit]);
        
        $query = $wpdb->prepare("
            SELECT DISTINCT p.ID, p.post_title, p.post_excerpt, p.post_content
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->prefix}odse_post_topics pt ON p.ID = pt.post_id
            WHERE pt.topic_name IN ($placeholders)
            AND p.ID != %d
            AND p.post_status = 'publish'
            AND p.post_type = 'post'
            ORDER BY pt.confidence_score DESC
            LIMIT %d
        ", $params);
        
        return $wpdb->get_results($query);
    }
    
    private function build_linking_prompt($content, $title, $related_posts) {
        $related_list = '';
        foreach($related_posts as $i => $post) {
            $excerpt = !empty($post->post_excerpt) ? $post->post_excerpt : substr(wp_strip_all_tags($post->post_content), 0, 200);
            $related_list .= sprintf("%d. ID: %d | العنوان: %s\n", ($i + 1), $post->ID, $post->post_title);
        }
        
        return sprintf(
            "اقترح 5 روابط داخلية. قدم JSON فقط:

العنوان: %s
المقالات: %s

JSON:
{\"suggestions\": [{\"post_id\": 123, \"anchor_text\": \"نص\", \"reason\": \"سبب\", \"priority\": \"high\"}]}",
            $title,
            $related_list
        );
    }
    
    private function parse_linking_suggestions($response) {
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['suggestions' => []];
        }
        
        return $data;
    }
    
    public function test_connection() {
        $test_prompt = "Respond with valid JSON: {\"status\":\"success\",\"message\":\"مرحباً! الاتصال ناجح\"}";
        $response = $this->make_api_request($test_prompt);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message()
            ];
        }
        
        return [
            'success' => true,
            'message' => __('Connection successful!', 'orsozox-divine-seo'),
            'response' => $response
        ];
    }
    
    private function get_from_cache($key) {
        global $wpdb;
        $table = $wpdb->prefix . 'odse_ai_cache';
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT cache_data FROM $table WHERE cache_key = %s AND expires_at > NOW()",
            $key
        ));
        
        if ($result) {
            return json_decode($result->cache_data, true);
        }
        
        return false;
    }
    
    private function save_to_cache($key, $data, $type = 'general') {
        global $wpdb;
        $table = $wpdb->prefix . 'odse_ai_cache';
        
        $cache_duration = get_option('odse_cache_duration', 7 * DAY_IN_SECONDS);
        $expires_at = date('Y-m-d H:i:s', time() + $cache_duration);
        
        $wpdb->replace($table, [
            'cache_key' => $key,
            'cache_data' => json_encode($data),
            'cache_type' => $type,
            'expires_at' => $expires_at
        ]);
    }
}
