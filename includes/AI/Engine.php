<?php
namespace OrsozoxDivineSEO\AI;

class Engine {
    private $api_key;
    private $model;
    
    public function __construct() {
        $this->api_key = get_option('odse_ai_api_key');
        $this->model = get_option('odse_ai_model', 'gpt-4o');
    }
    
    public function analyze_content($content, $title) {
        $clean = wp_strip_all_tags($content);
        $clean = substr($clean, 0, 3000);
        
        $prompt = "أنت خبير SEO للمحتوى المسيحي. حلل هذا المقال وقدم النتيجة بصيغة JSON:\n\nالعنوان: {$title}\nالمحتوى: {$clean}\n\n{\"main_topic\": \"\", \"keywords\": []}";
        
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an SEO expert. Respond in JSON.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.3,
            ]),
            'timeout' => 60
        ]);
        
        if (is_wp_error($response)) return $response;
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return json_decode($body['choices'][0]['message']['content'] ?? '{}', true);
    }
    
    public function test_connection() {
        if (empty($this->api_key)) {
            return ['success' => false, 'message' => 'No API key'];
        }
        return ['success' => true, 'message' => 'اتصال ناجح!'];
    }
}
