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
        
        // ✅ جديد: تحليل شامل للمقالات القديمة (مرة واحدة)
        add_action('odse_initial_bulk_analysis', [$this, 'initial_bulk_analysis']);
        
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
     * ✅ جديد: تحليل شامل للمقالات القديمة (يعمل تلقائياً في الخلفية)
     * يحلل 10 مقالات كل مرة، ثم يجدول نفسه مرة أخرى حتى ينتهي
     */
    public function initial_bulk_analysis() {
        // تحقق إذا كان التحليل الشامل مطلوب
        if (!get_option('odse_bulk_analysis_needed')) {
            return;
        }
        
        // تحقق من وجود API Key
        $api_key = get_option('odse_ai_api_key');
        if (empty($api_key)) {
            // جدول مرة أخرى بعد ساعة إذا لم يكن API Key موجود
            wp_schedule_single_event(time() + 3600, 'odse_initial_bulk_analysis');
            
            // حفظ رسالة للمستخدم
            update_option('odse_bulk_analysis_status', [
                'status' => 'waiting_api_key',
                'message' => 'في انتظار إدخال API Key',
                'last_check' => current_time('mysql')
            ]);
            
            return;
        }
        
        $offset = get_option('odse_bulk_analysis_offset', 0);
        $batch_size = 10; // 10 مقالات في المرة الواحدة
        
        // جلب المقالات غير المحللة
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
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ]);
        
        if (empty($posts)) {
            // انتهى التحليل - حساب الإحصائيات
            global $wpdb;
            $total_analyzed = $wpdb->get_var("
                SELECT COUNT(DISTINCT post_id) 
                FROM {$wpdb->postmeta} 
                WHERE meta_key = '_odse_analysis'
            ");
            
            // تحديث الحالة
            delete_option('odse_bulk_analysis_needed');
            delete_option('odse_bulk_analysis_offset');
            update_option('odse_bulk_analysis_completed', current_time('mysql'));
            update_option('odse_bulk_analysis_status', [
                'status' => 'completed',
                'total_analyzed' => $total_analyzed,
                'completed_at' => current_time('mysql')
            ]);
            
            // إرسال إشعار للمدير
            $admin_email = get_option('admin_email');
            $site_name = get_bloginfo('name');
            
            wp_mail(
                $admin_email,
                sprintf(__('[%s] Orsozox Divine SEO - تم إكمال التحليل الشامل!', 'orsozox-divine-seo'), $site_name),
                sprintf(
                    __("أخبار رائعة! 🎉

تم تحليل جميع مقالاتك بنجاح باستخدام الذكاء الاصطناعي.

📊 الإحصائيات:
- إجمالي المقالات المحللة: %d
- تاريخ الإكمال: %s

🔗 عرض النتائج:
%s

يمكنك الآن الاستفادة من:
✅ خريطة الكلمات المفتاحية
✅ كشف تنافس الكلمات (Keyword Cannibalization)
✅ الروابط الداخلية الذكية
✅ هيكل الموقع (Site Architecture)

---
Orsozox Divine SEO Engine
صُنع بـ ❤️ للمحتوى المسيحي العربي", 'orsozox-divine-seo'),
                    $total_analyzed,
                    current_time('Y-m-d H:i:s'),
                    admin_url('admin.php?page=orsozox-divine-seo')
                )
            );
            
            return;
        }
        
        // معالجة المقالات
        $engine = new \OrsozoxDivineSEO\AI\Engine();
        $processed = 0;
        $errors = 0;
        
        foreach ($posts as $post) {
            $result = $engine->analyze_content($post->post_content, $post->post_title);
            
            if (!is_wp_error($result)) {
                // حفظ التحليل
                update_post_meta($post->ID, '_odse_analysis', $result);
                
                // حفظ المواضيع في قاعدة البيانات
                if (!empty($result['primary_keywords'])) {
                    global $wpdb;
                    $table = $wpdb->prefix . 'odse_post_topics';
                    
                    // حذف المواضيع القديمة إذا وجدت
                    $wpdb->delete($table, ['post_id' => $post->ID]);
                    
                    // إضافة المواضيع الجديدة
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
            } else {
                $errors++;
            }
            
            // تأخير صغير لتجنب Rate Limits (2 ثانية بين كل مقال)
            sleep(2);
        }
        
        // تحديث الـ offset
        $new_offset = $offset + $processed;
        update_option('odse_bulk_analysis_offset', $new_offset);
        
        // حفظ حالة التقدم
        update_option('odse_bulk_analysis_status', [
            'status' => 'in_progress',
            'processed' => $new_offset,
            'errors' => get_option('odse_bulk_analysis_errors', 0) + $errors,
            'last_batch' => $processed,
            'last_run' => current_time('mysql')
        ]);
        
        // جدولة الدفعة التالية بعد دقيقة
        wp_schedule_single_event(time() + 60, 'odse_initial_bulk_analysis');
    }
    
    /**
     * Daily analysis task
     * معدّل: يحلل أي مقالات غير محللة (قديمة أو جديدة)
     */
    public function daily_analysis() {
        // جلب 5 مقالات عشوائية غير محللة
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 5,
            'orderby' => 'rand', // عشوائي للتنويع
            'meta_query' => [
                [
                    'key' => '_odse_analysis',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ]);
        
        if (empty($posts)) {
            // كل المقالات محللة! جرب تحديث المقالات القديمة
            $posts = get_posts([
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => 3,
                'orderby' => 'modified',
                'order' => 'ASC', // الأقدم تعديلاً
                'meta_query' => [
                    [
                        'key' => '_odse_analysis',
                        'compare' => 'EXISTS'
                    ]
                ]
            ]);
        }
        
        if (empty($posts)) {
            return;
        }
        
        $engine = new \OrsozoxDivineSEO\AI\Engine();
        
        foreach ($posts as $post) {
            $result = $engine->analyze_content($post->post_content, $post->post_title);
            
            if (!is_wp_error($result)) {
                update_post_meta($post->ID, '_odse_analysis', $result);
                
                // تأخير صغير
                sleep(2);
            }
        }
        
        // تسجيل آخر تشغيل
        update_option('odse_last_daily_analysis', current_time('mysql'));
    }
    
    /**
     * Weekly cannibalization check
     */
    public function weekly_cannibalization_check() {
        $engine = new \OrsozoxDivineSEO\AI\Engine();
        $conflicts = $engine->detect_cannibalization();
        
        // حفظ النتائج
        update_option('odse_keyword_conflicts', $conflicts);
        
        // إرسال إشعار للمدير إذا وجدت تنافسات
        if (!empty($conflicts)) {
            $admin_email = get_option('admin_email');
            $site_name = get_bloginfo('name');
            $count = count($conflicts);
            
            $subject = sprintf(
                __('[%s] ⚠️ تم اكتشاف تنافس على الكلمات المفتاحية', 'orsozox-divine-seo'),
                $site_name
            );
            
            $message = sprintf(
                __("مرحباً،

الفحص الأسبوعي لـ SEO اكتشف %d تنافس على الكلمات المفتاحية في موقعك.

🔴 ما هو Keyword Cannibalization؟
عندما تتنافس عدة صفحات على نفس الكلمة المفتاحية، هذا يضعف ترتيبها جميعاً في محركات البحث.

📊 راجع التنافسات وحلها من هنا:
%s

💡 اقتراحات الحل:
- اختر صفحة رئيسية واحدة لكل كلمة
- عدّل الكلمات المفتاحية للصفحات الأخرى
- ادمج الصفحات المتشابهة إذا أمكن

---
Orsozox Divine SEO Engine", 'orsozox-divine-seo'),
                $count,
                admin_url('admin.php?page=orsozox-divine-seo-keywords')
            );
            
            wp_mail($admin_email, $subject, $message);
        }
        
        // تسجيل آخر فحص
        update_option('odse_last_cannibalization_check', current_time('mysql'));
    }
}
