<?php
if (!defined('ABSPATH')) exit;

$dashboard = new \OrsozoxDivineSEO\Admin\DashboardPage();
$stats = $dashboard->get_stats();
?>

<div class="wrap odse-dashboard" dir="rtl">
    <h1>
        <span class="dashicons dashicons-networking"></span>
        <?php echo esc_html(get_admin_page_title()); ?>
    </h1>
    
    <p class="description">
        نظام ذكي لتحسين محركات البحث بالذكاء الاصطناعي للمحتوى المسيحي
    </p>
    
    <!-- Statistics Cards -->
    <div class="odse-stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-admin-post"></span>
            </div>
            <div class="stat-content">
                <h3>إجمالي المقالات</h3>
                <p class="stat-number"><?php echo esc_html($stats['total_posts']); ?></p>
            </div>
        </div>
        
        <div class="stat-card analyzed">
            <div class="stat-icon">
                <span class="dashicons dashicons-analytics"></span>
            </div>
            <div class="stat-content">
                <h3>تم تحليلها بالـ AI</h3>
                <p class="stat-number"><?php echo esc_html($stats['analyzed_posts']); ?></p>
                <p class="stat-percentage">
                    <?php 
                    $percentage = $stats['total_posts'] > 0 ? round(($stats['analyzed_posts'] / $stats['total_posts']) * 100) : 0;
                    echo esc_html($percentage) . '%'; 
                    ?>
                </p>
            </div>
        </div>
        
        <div class="stat-card links">
            <div class="stat-icon">
                <span class="dashicons dashicons-admin-links"></span>
            </div>
            <div class="stat-content">
                <h3>الروابط الداخلية</h3>
                <p class="stat-number"><?php echo esc_html($stats['internal_links']); ?></p>
            </div>
        </div>
        
        <div class="stat-card conflicts">
            <div class="stat-icon">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="stat-content">
                <h3>تنافس الكلمات</h3>
                <p class="stat-number <?php echo $stats['keyword_conflicts'] > 0 ? 'warning' : ''; ?>">
                    <?php echo esc_html($stats['keyword_conflicts']); ?>
                </p>
            </div>
        </div>
        
        <div class="stat-card clusters">
            <div class="stat-icon">
                <span class="dashicons dashicons-networking"></span>
            </div>
            <div class="stat-content">
                <h3>مجموعات المواضيع</h3>
                <p class="stat-number"><?php echo esc_html($stats['topic_clusters']); ?></p>
            </div>
        </div>
    </div>
    
    <!-- ✅ Bulk Analysis Status Section -->
    <div class="odse-bulk-analysis-section" style="margin: 40px 0;">
        <h2>📦 حالة التحليل الشامل للمقالات القديمة</h2>
        
        <div class="bulk-analysis-card" style="background: white; border: 1px solid #ddd; border-radius: 12px; padding: 30px;">
            <div id="odse-bulk-status-container">
                <div class="status-loading" style="text-align: center; padding: 20px;">
                    <span class="dashicons dashicons-update" style="font-size: 32px; animation: spin 2s linear infinite;"></span>
                    <p>جاري التحقق من الحالة...</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="odse-actions-section">
        <h2>إجراءات سريعة</h2>
        
        <div class="odse-actions-grid">
            <!-- تحليل المقالات الجديدة -->
            <div class="action-card">
                <h3>
                    <span class="dashicons dashicons-analytics"></span>
                    تحليل المقالات الجديدة
                </h3>
                <p>قم بتحليل المقالات المنشورة مؤخراً باستخدام الذكاء الاصطناعي</p>
                <button type="button" class="button button-primary button-hero" id="odse-analyze-all">
                    <span class="dashicons dashicons-update"></span>
                    تحليل المقالات الجديدة
                </button>
                <div id="odse-analyze-progress" style="display:none; margin-top: 15px;">
                    <div class="progress-bar">
                        <div class="progress-bar-fill" style="width: 0%"></div>
                    </div>
                    <p class="progress-text">جاري التحليل...</p>
                </div>
            </div>
            
            <!-- ✅ تحليل المقالات القديمة -->
            <div class="action-card" style="border: 2px solid #00a32a;">
                <h3>
                    <span class="dashicons dashicons-backup"></span>
                    تحليل المقالات القديمة ⭐
                </h3>
                <p>تحليل سريع يدوي لجميع المقالات القديمة غير المحللة</p>
                <button type="button" class="button button-secondary button-hero" id="odse-analyze-old">
                    <span class="dashicons dashicons-clock"></span>
                    تحليل المقالات القديمة الآن
                </button>
                <div id="odse-old-progress" style="display:none; margin-top: 15px;">
                    <div class="progress-bar">
                        <div class="progress-bar-fill" style="width: 0%"></div>
                    </div>
                    <p class="progress-text">جاري التحليل...</p>
                </div>
                <p style="margin-top: 10px; font-size: 12px; color: #666;">
                    💡 نصيحة: التحليل التلقائي يعمل في الخلفية، هذا الزر للإسراع فقط
                </p>
            </div>
            
            <!-- ✅ بناء الروابط الداخلية -->
            <div class="action-card">
                <h3>
                    <span class="dashicons dashicons-admin-links"></span>
                    بناء الروابط الداخلية
                </h3>
                <p>إنشاء روابط داخلية ذكية بين المقالات المرتبطة باستخدام الذكاء الاصطناعي</p>
                <button type="button" class="button button-secondary button-hero" id="odse-build-links">
                    <span class="dashicons dashicons-admin-links"></span>
                    بناء الروابط الداخلية
                </button>
            </div>
            
            <!-- ✅ حل تنافس الكلمات تلقائياً -->
            <div class="action-card" style="border: 2px solid #d63638;">
                <h3>
                    <span class="dashicons dashicons-warning"></span>
                    حل تنافس الكلمات تلقائياً 🤖
                </h3>
                <p>يقوم الذكاء الاصطناعي بتحليل التنافسات واختيار المقال الأقوى لكل كلمة مفتاحية</p>
                <button type="button" class="button button-primary button-hero" id="odse-resolve-conflicts">
                    <span class="dashicons dashicons-yes-alt"></span>
                    حل جميع التنافسات تلقائياً
                </button>
                <p style="margin-top: 10px; font-size: 12px; color: #666;">
                    ⚡ حل ذكي: يختار المقال الأقوى بناءً على طول المحتوى، العنوان، التاريخ، والتحليل
                </p>
            </div>
            
            <!-- كشف التنافس -->
            <div class="action-card">
                <h3>
                    <span class="dashicons dashicons-search"></span>
                    كشف التنافس
                </h3>
                <p>اكتشف المقالات التي تتنافس على نفس الكلمات المفتاحية</p>
                <button type="button" class="button button-secondary" id="odse-detect-conflicts">
                    <span class="dashicons dashicons-search"></span>
                    كشف التنافس
                </button>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="odse-recent-activity">
        <h2>آخر النشاطات</h2>
        
        <?php
        $recent_analyzed = get_posts([
            'post_type' => 'post',
            'posts_per_page' => 5,
            'meta_key' => '_odse_analysis',
            'orderby' => 'modified',
            'order' => 'DESC'
        ]);
        
        if ($recent_analyzed) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>المقال</th>
                        <th>الموضوع الرئيسي</th>
                        <th>الكلمات المفتاحية</th>
                        <th>تاريخ التحليل</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_analyzed as $post) : 
                        $analysis = get_post_meta($post->ID, '_odse_analysis', true);
                    ?>
                        <tr>
                            <td>
                                <strong>
                                    <a href="<?php echo get_edit_post_link($post->ID); ?>">
                                        <?php echo esc_html($post->post_title); ?>
                                    </a>
                                </strong>
                            </td>
                            <td><?php echo esc_html($analysis['main_topic'] ?? '-'); ?></td>
                            <td>
                                <?php 
                                if (!empty($analysis['primary_keywords'])) {
                                    echo esc_html(implode(', ', array_slice($analysis['primary_keywords'], 0, 3)));
                                }
                                ?>
                            </td>
                            <td><?php echo get_the_modified_date('Y/m/d', $post->ID); ?></td>
                            <td>
                                <a href="<?php echo get_permalink($post->ID); ?>" class="button button-small" target="_blank">
                                    عرض
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <div class="notice notice-info">
                <p>لم يتم تحليل أي مقالات بعد. ابدأ بالضغط على "تحليل جميع المقالات" أعلاه.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Help Section -->
    <div class="odse-help-section">
        <div class="help-card">
            <h3>
                <span class="dashicons dashicons-book"></span>
                تحتاج مساعدة؟
            </h3>
            <p>تعرف على كيفية استخدام الإضافة بشكل كامل</p>
            <a href="https://orsozox.com/docs" target="_blank" class="button">
                التوثيق الكامل
            </a>
        </div>
        
        <div class="help-card">
            <h3>
                <span class="dashicons dashicons-admin-settings"></span>
                إعدادات الذكاء الاصطناعي
            </h3>
            <p>قم بإعداد API Key وتخصيص الإعدادات</p>
            <a href="<?php echo admin_url('admin.php?page=orsozox-divine-seo-ai'); ?>" class="button">
                الإعدادات
            </a>
        </div>
        
        <div class="help-card">
            <h3>
                <span class="dashicons dashicons-sos"></span>
                الدعم الفني
            </h3>
            <p>تواصل معنا للحصول على المساعدة</p>
            <a href="https://github.com/inisoliman/osa/issues" target="_blank" class="button">
                فتح تذكرة
            </a>
        </div>
    </div>
</div>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.dashicons-spin {
    animation: spin 1s linear infinite;
}

.bulk-status-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.bulk-status-box {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}

.bulk-status-box h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #666;
}

.bulk-status-box .number {
    font-size: 32px;
    font-weight: bold;
    color: #2271b1;
}

.bulk-progress-bar {
    width: 100%;
    height: 40px;
    background: #f0f0f0;
    border-radius: 20px;
    overflow: hidden;
    margin: 20px 0;
}

.bulk-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #2271b1 0%, #00a32a 100%);
    transition: width 0.5s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
}
</style>
