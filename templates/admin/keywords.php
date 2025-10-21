<?php
if (!defined('ABSPATH')) exit;

$keyword_map = new \OrsozoxDivineSEO\Admin\KeywordMapPage();
$conflicts = $keyword_map->get_conflicts();
?>

<div class="wrap odse-keywords" dir="rtl">
    <h1>
        <span class="dashicons dashicons-admin-network"></span>
        <?php echo esc_html(get_admin_page_title()); ?>
    </h1>
    
    <p class="description">
        إدارة الكلمات المفتاحية وكشف التنافس (Keyword Cannibalization)
    </p>
    
    <!-- Quick Stats -->
    <div class="keyword-stats">
        <div class="stat-box">
            <span class="dashicons dashicons-admin-network"></span>
            <div>
                <strong><?php echo count($conflicts); ?></strong>
                <span>تنافس على الكلمات</span>
            </div>
        </div>
        
        <button type="button" class="button button-primary" id="odse-refresh-conflicts">
            <span class="dashicons dashicons-update"></span>
            تحديث البيانات
        </button>
    </div>
    
    <!-- Conflicts Table -->
    <?php if (!empty($conflicts)) : ?>
        <h2>🔴 تنافس الكلمات المفتاحية المكتشف</h2>
        <p>المقالات التالية تتنافس على نفس الكلمات المفتاحية. يُنصح بتعديل أحدها لتجنب التنافس.</p>
        
        <?php foreach ($conflicts as $keyword => $posts) : ?>
            <div class="conflict-card">
                <h3>
                    <span class="dashicons dashicons-warning"></span>
                    الكلمة: <strong><?php echo esc_html($keyword); ?></strong>
                </h3>
                <p class="conflict-count">
                    <?php echo count($posts); ?> مقالات تتنافس على هذه الكلمة
                </p>
                
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>المقال</th>
                            <th>الرابط</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $post) : ?>
                            <tr>
                                <td>
                                    <strong>
                                        <a href="<?php echo get_edit_post_link($post['id']); ?>">
                                            <?php echo esc_html($post['title']); ?>
                                        </a>
                                    </strong>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url($post['url']); ?>" target="_blank">
                                        عرض المقال
                                    </a>
                                </td>
                                <td>
                                    <a href="<?php echo get_edit_post_link($post['id']); ?>" class="button button-small">
                                        تعديل
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="conflict-suggestions">
                    <h4>💡 اقتراحات الحل:</h4>
                    <ul>
                        <li>اختر مقالاً واحداً كـ "مقال رئيسي" لهذه الكلمة</li>
                        <li>عدّل الكلمات المفتاحية للمقالات الأخرى لتكون أكثر تحديداً</li>
                        <li>ادمج المقالات المتشابهة في مقال واحد شامل</li>
                        <li>استخدم الكلمات ذات الذيل الطويل (Long-tail keywords)</li>
                    </ul>
                </div>
            </div>
        <?php endforeach; ?>
        
    <?php else : ?>
        <div class="notice notice-success">
            <p>
                <span class="dashicons dashicons-yes-alt"></span>
                <strong>رائع!</strong> لا يوجد تنافس على الكلمات المفتاحية. موقعك منظم بشكل جيد!
            </p>
        </div>
    <?php endif; ?>
    
    <!-- Help Section -->
    <div class="keywords-help">
        <h2>ما هو Keyword Cannibalization؟</h2>
        <p>
            تنافس الكلمات المفتاحية (Keyword Cannibalization) يحدث عندما تتنافس عدة صفحات 
            في موقعك على نفس الكلمة المفتاحية. هذا يضعف ترتيب جميع الصفحات في محركات البحث 
            بدلاً من تقويتها.
        </p>
        
        <h3>كيف تحل المشكلة؟</h3>
        <ol>
            <li><strong>اختر صفحة رئيسية:</strong> حدد أي صفحة هي الأفضل للكلمة المفتاحية</li>
            <li><strong>عدّل الصفحات الأخرى:</strong> استخدم كلمات مفتاحية مختلفة أو أكثر تحديداً</li>
            <li><strong>استخدم الروابط الداخلية:</strong> اربط الصفحات الفرعية بالصفحة الرئيسية</li>
            <li><strong>الدمج:</strong> في بعض الحالات، دمج الصفحات المتشابهة أفضل</li>
        </ol>
    </div>
</div>
