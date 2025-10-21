<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$table = $wpdb->prefix . 'odse_internal_links';

// Get all internal links
$links = $wpdb->get_results("
    SELECT 
        il.*,
        sp.post_title as source_title,
        tp.post_title as target_title
    FROM {$table} il
    LEFT JOIN {$wpdb->posts} sp ON il.source_post_id = sp.ID
    LEFT JOIN {$wpdb->posts} tp ON il.target_post_id = tp.ID
    ORDER BY il.created_at DESC
    LIMIT 100
");
?>

<div class="wrap odse-internal-links" dir="rtl">
    <h1>
        <span class="dashicons dashicons-admin-links"></span>
        الروابط الداخلية
    </h1>
    
    <p class="description">
        الروابط الداخلية المُنشأة بواسطة الذكاء الاصطناعي
    </p>
    
    <div class="odse-stats-row">
        <div class="stat-box">
            <h3>إجمالي الروابط</h3>
            <p class="big-number"><?php echo count($links); ?></p>
        </div>
    </div>
    
    <?php if (!empty($links)) : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>المقال المصدر</th>
                    <th>المقال المستهدف</th>
                    <th>نص الرابط</th>
                    <th>الأولوية</th>
                    <th>تاريخ الإنشاء</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($links as $link) : ?>
                    <tr>
                        <td>
                            <a href="<?php echo get_edit_post_link($link->source_post_id); ?>">
                                <?php echo esc_html($link->source_title); ?>
                            </a>
                        </td>
                        <td>
                            <a href="<?php echo get_edit_post_link($link->target_post_id); ?>">
                                <?php echo esc_html($link->target_title); ?>
                            </a>
                        </td>
                        <td>
                            <code><?php echo esc_html($link->anchor_text); ?></code>
                        </td>
                        <td>
                            <?php
                            $priority_badge = [
                                'high' => '<span class="badge badge-success">عالية</span>',
                                'medium' => '<span class="badge badge-warning">متوسطة</span>',
                                'low' => '<span class="badge badge-secondary">منخفضة</span>'
                            ];
                            echo $priority_badge[$link->priority] ?? $link->priority;
                            ?>
                        </td>
                        <td><?php echo date('Y/m/d H:i', strtotime($link->created_at)); ?></td>
                        <td>
                            <button class="button button-small odse-remove-link" data-link-id="<?php echo $link->id; ?>">
                                حذف
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <div class="notice notice-info">
            <p>لم يتم إنشاء أي روابط داخلية بعد. اضغط على "بناء الروابط الداخلية" من Dashboard.</p>
        </div>
    <?php endif; ?>
</div>

<style>
.badge {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
}
.badge-success {
    background: #00a32a;
    color: white;
}
.badge-warning {
    background: #dba617;
    color: white;
}
.badge-secondary {
    background: #999;
    color: white;
}
.stat-box {
    background: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    border: 1px solid #ddd;
    display: inline-block;
    margin: 20px 0;
}
.big-number {
    font-size: 48px;
    font-weight: bold;
    color: #2271b1;
    margin: 0;
}
</style>
