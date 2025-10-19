<?php
if (!defined('ABSPATH')) exit;

$architecture_page = new \OrsozoxDivineSEO\Admin\ArchitecturePage();
$architecture = $architecture_page->get_architecture();
?>

<div class="wrap odse-architecture" dir="rtl">
    <h1>
        <span class="dashicons dashicons-networking"></span>
        <?php echo esc_html(get_admin_page_title()); ?>
    </h1>
    
    <p class="description">
        هيكل الموقع ومجموعات المواضيع (Topic Clusters)
    </p>
    
    <!-- Architecture Visualization -->
    <div class="architecture-view">
        <?php if (!empty($architecture)) : ?>
            <?php foreach ($architecture as $cluster_name => $cluster_data) : ?>
                <div class="cluster-group">
                    <h2 class="cluster-title">
                        <span class="dashicons dashicons-category"></span>
                        <?php echo esc_html($cluster_name); ?>
                    </h2>
                    
                    <!-- Pillar Content -->
                    <?php if (!empty($cluster_data['pillars'])) : ?>
                        <div class="pillar-section">
                            <h3>📌 المقالات الرئيسية (Pillars)</h3>
                            <div class="pillar-posts">
                                <?php foreach ($cluster_data['pillars'] as $post) : ?>
                                    <div class="post-card pillar-card">
                                        <h4>
                                            <a href="<?php echo get_edit_post_link($post->post_id); ?>">
                                                <?php echo esc_html($post->post_title); ?>
                                            </a>
                                        </h4>
                                        <div class="post-meta">
                                            <span class="post-type pillar">Pillar Content</span>
                                            <span class="post-date">
                                                <?php echo get_the_date('Y/m/d', $post->post_id); ?>
                                            </span>
                                        </div>
                                        <div class="post-actions">
                                            <a href="<?php echo get_permalink($post->post_id); ?>" target="_blank" class="button button-small">
                                                عرض
                                            </a>
                                            <a href="<?php echo get_edit_post_link($post->post_id); ?>" class="button button-small">
                                                تعديل
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Supporting Content -->
                    <?php if (!empty($cluster_data['supporting'])) : ?>
                        <div class="supporting-section">
                            <h3>📄 المقالات الداعمة (Supporting Content)</h3>
                            <div class="supporting-posts">
                                <?php foreach ($cluster_data['supporting'] as $post) : ?>
                                    <div class="post-card supporting-card">
                                        <h4>
                                            <a href="<?php echo get_edit_post_link($post->post_id); ?>">
                                                <?php echo esc_html($post->post_title); ?>
                                            </a>
                                        </h4>
                                        <div class="post-meta">
                                            <span class="post-type supporting">Supporting</span>
                                            <span class="hierarchy-level">
                                                Level <?php echo esc_html($post->hierarchy_level); ?>
                                            </span>
                                        </div>
                                        <div class="post-actions">
                                            <a href="<?php echo get_permalink($post->post_id); ?>" target="_blank" class="button button-small">
                                                عرض
                                            </a>
                                            <button type="button" 
                                                    class="button button-small odse-make-pillar" 
                                                    data-post-id="<?php echo esc_attr($post->post_id); ?>"
                                                    data-cluster="<?php echo esc_attr($cluster_name); ?>">
                                                جعله Pillar
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="notice notice-info">
                <p>
                    لم يتم بناء هيكل الموقع بعد. قم بتحليل المقالات أولاً من 
                    <a href="<?php echo admin_url('admin.php?page=orsozox-divine-seo'); ?>">لوحة التحكم</a>.
                </p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Info Section -->
    <div class="architecture-info">
        <h2>ما هو Topic Cluster؟</h2>
        <p>
            Topic Cluster هو استراتيجية تنظيم المحتوى حيث:
        </p>
        <ul>
            <li><strong>Pillar Content:</strong> مقال شامل وطويل يغطي الموضوع بالكامل</li>
            <li><strong>Supporting Content:</strong> مقالات فرعية تتناول تفاصيل محددة</li>
            <li><strong>Internal Links:</strong> روابط داخلية تربط المقالات الفرعية بالمقال الرئيسي</li>
        </ul>
        
        <h3>الفوائد:</h3>
        <ul>
            <li>✅ تحسين ترتيب الموقع في محركات البحث</li>
            <li>✅ بناء سلطة موضوعية (Topical Authority)</li>
            <li>✅ تحسين تجربة المستخدم</li>
            <li>✅ زيادة الوقت المقضي في الموقع</li>
        </ul>
    </div>
</div>
