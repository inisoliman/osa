<?php
namespace OrsozoxDivineSEO\Frontend;

/**
 * Frontend Content Optimizer Class
 */
class ContentOptimizer {
    
    public function __construct() {
        // Add schema markup
        add_action('wp_head', [$this, 'add_schema_markup']);
        
        // Optimize content for SGE
        if (get_option('odse_sge_optimization_enabled', 1)) {
            add_filter('the_content', [$this, 'optimize_for_sge'], 10);
        }
    }
    
    /**
     * Add enhanced schema markup
     */
    public function add_schema_markup() {
        if (!is_single()) {
            return;
        }
        
        $post_id = get_the_ID();
        $analysis = get_post_meta($post_id, '_odse_analysis', true);
        
        if (!$analysis) {
            return;
        }
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => get_the_title(),
            'datePublished' => get_the_date('c'),
            'dateModified' => get_the_modified_date('c'),
            'author' => [
                '@type' => 'Person',
                'name' => get_the_author()
            ]
        ];
        
        // Add keywords
        if (!empty($analysis['primary_keywords'])) {
            $schema['keywords'] = implode(', ', $analysis['primary_keywords']);
        }
        
        // Add about (topics)
        if (!empty($analysis['biblical_themes'])) {
            $schema['about'] = [];
            foreach ($analysis['biblical_themes'] as $theme) {
                $schema['about'][] = [
                    '@type' => 'Thing',
                    'name' => $theme
                ];
            }
        }
        
        echo '<script type="application/ld+json">';
        echo json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        echo '</script>';
    }
    
    /**
     * Optimize content for SGE (Search Generative Experience)
     */
    public function optimize_for_sge($content) {
        if (!is_single() || !in_the_loop()) {
            return $content;
        }
        
        // Add FAQ schema if there are questions in content
        $content = $this->add_faq_schema($content);
        
        // Enhance headings for better AI understanding
        $content = $this->enhance_headings($content);
        
        return $content;
    }
    
    /**
     * Add FAQ schema to content
     */
    private function add_faq_schema($content) {
        // Detect Q&A patterns
        preg_match_all('/<h[2-3]>(.*?سؤال.*?|.*؟.*?)<\/h[2-3]>(.*?)<p>(.*?)<\/p>/is', $content, $matches);
        
        if (empty($matches[0])) {
            return $content;
        }
        
        $faq_items = [];
        for ($i = 0; $i < count($matches[1]); $i++) {
            $faq_items[] = [
                '@type' => 'Question',
                'name' => strip_tags($matches[1][$i]),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => strip_tags($matches[3][$i])
                ]
            ];
        }
        
        if (!empty($faq_items)) {
            $schema = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $faq_items
            ];
            
            $content .= '<script type="application/ld+json">';
            $content .= json_encode($schema, JSON_UNESCAPED_UNICODE);
            $content .= '</script>';
        }
        
        return $content;
    }
    
    /**
     * Enhance headings for better structure
     */
    private function enhance_headings($content) {
        // Add IDs to headings for better anchor linking
        $content = preg_replace_callback(
            '/<h([2-6])>(.*?)<\/h\1>/i',
            function($matches) {
                $level = $matches[1];
                $text = $matches[2];
                $id = sanitize_title($text);
                
                return sprintf(
                    '<h%s id="%s">%s</h%s>',
                    $level,
                    $id,
                    $text,
                    $level
                );
            },
            $content
        );
        
        return $content;
    }
}
