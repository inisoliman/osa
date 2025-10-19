<?php
namespace OrsozoxDivineSEO\AI;

/**
 * AI Engine Class
 * Handles all AI-related operations
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
                $this->model = 'gemini-1.5-pro';
                break;
        }
    }
    
    /**
     * Analyze content and extract topics, keywords, themes
     */
    public function analyze_content($content, $title = '') {
        // Check cache first
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
        
        // Cache the result
        if (!is_wp_error($result)) {
            $this->save_to_cache($cache_key, $result, 'analysis');
        }
        
        return $result;
    }
    
    /**
     * Build comprehensive analysis prompt
     */
    private function build_analysis_prompt($content, $title) {
        return sprintf(
            "أنت خبير SEO متخصص في تحليل المحتوى المسيحي الديني باللغة العربية.

قم بتحليل هذا المقال وقدم:

1. **الموضوع الرئيسي** (Main Topic): الموضوع الأساسي للمقال
2. **المواضيع الفرعية** (Subtopics): 3-5 مواضيع فرعية مرتبطة
3. **الكلمات المفتاحية الأساسية** (Primary Keywords): 3-5 كلمات رئيسية
4. **الكلمات المفتاحية الثانوية** (Secondary Keywords): 5-10 كلمات داعمة
5. **الثيمات الكتابية المرتبطة** (Biblical Themes): الموضوعات الروحية والكتابية
6. **نوع المحتوى** (Content Type): (theology, spirituality, biblical_study, church_history, saints, prayers, etc.)
7. **مستوى العمق** (Content Depth): (pillar, comprehensive, supporting, basic)
8. **الكلمات المناسبة للربط الداخلي** (Internal Linking Keywords): كلمات يمكن استخدامها للربط

**العنوان:** %s

**المحتوى:**
%s

قدم الإجابة بصيغة JSON فقط بدون أي نص إضافي:
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
    
    /**
     * Clean and truncate content for AI processing
     */
    private function clean_content($content) {
        $content = wp_strip_all_tags($content);
        $content = preg_replace('/\s+/', ' ', $content);
        
        // Limit to 4000 characters for API efficiency
        if (strlen($content) > 4000) {
            $content = substr($content, 0, 4000) . '...';
        }
        
        return trim($content);
    }
    
    /**
     * Make API request based on provider
     */
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
            default:
                return new \WP_Error('invalid_provider', __('Invalid AI provider', 'orsozox-divine-seo'));
        }
    }
    
    /**
     * OpenAI API Request
     */
    private function openai_request($prompt, $system_message = '') {
        $messages = [];
        
        if (!empty($system_message)) {
            $messages[] = [
                'role' => 'system',
                'content' => $system_message
            ];
        } else {
            $messages[] = [
                'role' => 'system',
                'content' => 'You are an expert SEO analyst specializing in Christian religious content in Arabic. Always respond in valid JSON format.'
            ];
        }
        
        $messages[] = [
            'role' => 'user',
            'content' => $prompt
        ];
        
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
    
    /**
     * Claude API Request  
     */
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
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ]
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
     * Gemini API Request
     */
    private function gemini_request($prompt, $system_message = '') {
        $full_prompt = $system_message ? $system_message . "\n\n" . $prompt : $prompt;
        
        $response = wp_remote_post(
            'https://generativelanguage.googleapis.com/v1beta/models/' . $this->model . ':generateContent?key=' . $this->api_key,
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode([
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $full_prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => $this->temperature,
                        'maxOutputTokens' => 4096,
                    ]
                ]),
                'timeout' => 60
            ]
        );
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new \WP_Error('api_error', $body['error']['message']);
        }
        
        return $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }
    
    /**
     * Parse analysis response
     */
    private function parse_analysis_response($response) {
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_Error('json_error', __('Invalid JSON response from AI', 'orsozox-divine-seo'));
        }
        
        return $data;
    }
    
    /**
     * Detect keyword cannibalization
     */
    public function detect_cannibalization() {
        global $wpdb;
        
        // Get all posts with their focus keywords from Rank Math
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
        
        // Find conflicts (same keyword on multiple posts)
        foreach($keyword_map as $keyword => $posts_array) {
            if(count($posts_array) > 1) {
                $conflicts[$keyword] = $posts_array;
            }
        }
        
        return $conflicts;
    }
    
    /**
     * Suggest internal links using AI
     */
    public function suggest_internal_links($post_id) {
        $post = get_post($post_id);
        if(!$post) return new \WP_Error('invalid_post', __('Invalid post ID', 'orsozox-divine-seo'));
        
        $content = $post->post_content;
        $title = $post->post_title;
        
        // Get related posts
        $related_posts = $this->find_related_posts($post_id);
        
        if (empty($related_posts)) {
            return ['suggestions' => []];
        }
        
        // Use AI to suggest best linking strategy
        $prompt = $this->build_linking_prompt($content, $title, $related_posts);
        $response = $this->make_api_request($prompt);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return $this->parse_linking_suggestions($response);
    }
    
    /**
     * Find related posts by topic
     */
    private function find_related_posts($post_id, $limit = 15) {
        global $wpdb;
        
        // Get post topics
        $topics = $wpdb->get_col($wpdb->prepare("
            SELECT topic_name FROM {$wpdb->prefix}odse_post_topics
            WHERE post_id = %d
        ", $post_id));
        
        if(empty($topics)) {
            // Fallback: get posts from same category
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
        
        // Find posts with similar topics
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
    
    /**
     * Build linking prompt for AI
     */
    private function build_linking_prompt($content, $title, $related_posts) {
        $related_list = '';
        foreach($related_posts as $i => $post) {
            $excerpt = !empty($post->post_excerpt) ? $post->post_excerpt : substr(wp_strip_all_tags($post->post_content), 0, 200);
            $related_list .= sprintf(
                "%d. ID: %d | العنوان: %s | ملخص: %s\n",
                ($i + 1),
                $post->ID,
                $post->post_title,
                $excerpt
            );
        }
        
        return sprintf(
            "أنت خبير في بناء استراتيجيات الربط الداخلي للمواقع المسيحية الدينية.

**المقال الحالي:**
العنوان: %s
المحتوى: %s

**المقالات المرتبطة المتاحة:**
%s

**المطلوب:**
اقترح أفضل 5-8 روابط داخلية لهذا المقال. لكل رابط قدم:

1. **post_id**: رقم المقال المستهدف
2. **anchor_text**: نص الرابط (يجب أن يكون طبيعياً ومن كلمات موجودة فعلاً في المحتوى)
3. **reason**: سبب اختيار هذا الرابط
4. **priority**: (high, medium, low) حسب أهمية الرابط
5. **placement_suggestion**: اقتراح مكان وضع الرابط في المحتوى

**ملاحظات مهمة:**
- نص الرابط (anchor_text) يجب أن يكون كلمات موجودة فعلياً في المحتوى
- تجنب الربط الزائد (over-optimization)
- ركز على القيمة للقارئ
- اختر مقالات ذات صلة حقيقية

قدم الإجابة بصيغة JSON:
{
  \"suggestions\": [
    {
      \"post_id\": 123,
      \"anchor_text\": \"النص المقترح\",
      \"reason\": \"السبب\",
      \"priority\": \"high\",
      \"placement_suggestion\": \"في الفقرة الثانية\"
    }
  ]
}",
            $title,
            $this->clean_content($content),
            $related_list
        );
    }
    
    /**
     * Parse linking suggestions
     */
    private function parse_linking_suggestions($response) {
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['suggestions' => []];
        }
        
        return $data;
    }
    
    /**
     * Test API connection
     */
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
    
    // Cache methods
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
