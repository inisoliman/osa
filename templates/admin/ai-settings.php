<?php
if (!defined('ABSPATH')) exit;

// Save settings
if (isset($_POST['odse_save_settings']) && check_admin_referer('odse_settings_nonce')) {
    update_option('odse_ai_provider', sanitize_text_field($_POST['odse_ai_provider']));
    update_option('odse_ai_api_key', sanitize_text_field($_POST['odse_ai_api_key']));
    update_option('odse_ai_model', sanitize_text_field($_POST['odse_ai_model']));
    update_option('odse_claude_api_key', sanitize_text_field($_POST['odse_claude_api_key']));
    update_option('odse_gemini_api_key', sanitize_text_field($_POST['odse_gemini_api_key']));
    
    echo '<div class="notice notice-success"><p>تم حفظ الإعدادات بنجاح!</p></div>';
}

$provider = get_option('odse_ai_provider', 'openai');
?>

<div class="wrap odse-settings" dir="rtl">
    <h1>
        <span class="dashicons dashicons-admin-generic"></span>
        <?php echo esc_html(get_admin_page_title()); ?>
    </h1>
    
    <p class="description">
        قم بإعداد الذكاء الاصطناعي لتحليل المحتوى وتحسين SEO تلقائياً
    </p>
    
    <form method="post" action="">
        <?php wp_nonce_field('odse_settings_nonce'); ?>
        
        <div class="odse-settings-tabs">
            <div class="tab-content active">
                <h2>إعدادات الذكاء الاصطناعي</h2>
                
                <table class="form-table" role="presentation">
                    <tbody>
                        <!-- AI Provider -->
                        <tr>
                            <th scope="row">
                                <label for="odse_ai_provider">مزود الخدمة</label>
                            </th>
                            <td>
                                <select name="odse_ai_provider" id="odse_ai_provider" class="regular-text">
                                    <option value="openai" <?php selected($provider, 'openai'); ?>>
                                        OpenAI (GPT-4, GPT-4o) - الأفضل
                                    </option>
                                    <option value="claude" <?php selected($provider, 'claude'); ?>>
                                        Anthropic Claude
                                    </option>
                                    <option value="gemini" <?php selected($provider, 'gemini'); ?>>
                                        Google Gemini
                                    </option>
                                </select>
                                <p class="description">
                                    اختر مزود خدمة الذكاء الاصطناعي. نوصي بـ OpenAI لأفضل نتائج.
                                </p>
                            </td>
                        </tr>
                        
                        <!-- OpenAI Settings -->
                        <tr class="provider-setting openai-setting" style="<?php echo $provider !== 'openai' ? 'display:none' : ''; ?>">
                            <th scope="row">
                                <label for="odse_ai_api_key">OpenAI API Key</label>
                            </th>
                            <td>
                                <input type="password" 
                                       name="odse_ai_api_key" 
                                       id="odse_ai_api_key"
                                       value="<?php echo esc_attr(get_option('odse_ai_api_key')); ?>" 
                                       class="regular-text" 
                                       placeholder="sk-..." />
                                <p class="description">
                                    احصل على API Key من 
                                    <a href="https://platform.openai.com/api-keys" target="_blank">
                                        OpenAI Platform
                                    </a>
                                </p>
                            </td>
                        </tr>
                        
                        <tr class="provider-setting openai-setting" style="<?php echo $provider !== 'openai' ? 'display:none' : ''; ?>">
                            <th scope="row">
                                <label for="odse_ai_model">النموذج</label>
                            </th>
                            <td>
                                <select name="odse_ai_model" id="odse_ai_model" class="regular-text">
                                    <option value="gpt-4o" <?php selected(get_option('odse_ai_model'), 'gpt-4o'); ?>>
                                        GPT-4o (الأسرع والأفضل) ⭐
                                    </option>
                                    <option value="gpt-4-turbo" <?php selected(get_option('odse_ai_model'), 'gpt-4-turbo'); ?>>
                                        GPT-4 Turbo
                                    </option>
                                    <option value="gpt-4" <?php selected(get_option('odse_ai_model'), 'gpt-4'); ?>>
                                        GPT-4
                                    </option>
                                </select>
                                <p class="description">
                                    GPT-4o موصى به لأفضل أداء وسرعة
                                </p>
                            </td>
                        </tr>
                        
                        <!-- Claude Settings -->
                        <tr class="provider-setting claude-setting" style="<?php echo $provider !== 'claude' ? 'display:none' : ''; ?>">
                            <th scope="row">
                                <label for="odse_claude_api_key">Claude API Key</label>
                            </th>
                            <td>
                                <input type="password" 
                                       name="odse_claude_api_key" 
                                       id="odse_claude_api_key"
                                       value="<?php echo esc_attr(get_option('odse_claude_api_key')); ?>" 
                                       class="regular-text" />
                                <p class="description">
                                    احصل على API Key من 
                                    <a href="https://console.anthropic.com/" target="_blank">
                                        Anthropic Console
                                    </a>
                                </p>
                            </td>
                        </tr>
                        
                        <!-- Gemini Settings -->
                        <tr class="provider-setting gemini-setting" style="<?php echo $provider !== 'gemini' ? 'display:none' : ''; ?>">
                            <th scope="row">
                                <label for="odse_gemini_api_key">Gemini API Key</label>
                            </th>
                            <td>
                                <input type="password" 
                                       name="odse_gemini_api_key" 
                                       id="odse_gemini_api_key"
                                       value="<?php echo esc_attr(get_option('odse_gemini_api_key')); ?>" 
                                       class="regular-text" />
                                <p class="description">
                                    احصل على API Key من 
                                    <a href="https://makersuite.google.com/app/apikey" target="_blank">
                                        Google AI Studio
                                    </a>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <p class="submit">
                    <button type="submit" name="odse_save_settings" class="button button-primary button-large">
                        <span class="dashicons dashicons-yes"></span>
                        حفظ الإعدادات
                    </button>
                </p>
            </div>
        </div>
    </form>
    
    <!-- Test Connection -->
    <div class="odse-test-section">
        <h2>اختبار الاتصال</h2>
        <p>تأكد من أن API Key يعمل بشكل صحيح</p>
        
        <button type="button" class="button button-secondary" id="odse-test-connection">
            <span class="dashicons dashicons-admin-plugins"></span>
            اختبار الاتصال
        </button>
        
        <div id="odse-test-result" style="margin-top: 15px;"></div>
    </div>
    
    <!-- Information Cards -->
    <div class="odse-info-cards">
        <div class="info-card">
            <h3>💡 نصيحة</h3>
            <p>للحصول على أفضل النتائج، استخدم OpenAI GPT-4o. يوفر أفضل دقة وسرعة في التحليل.</p>
        </div>
        
        <div class="info-card">
            <h3>🔒 الخصوصية</h3>
            <p>API Keys محفوظة بشكل آمن في قاعدة البيانات ولا يتم مشاركتها مع أي طرف ثالث.</p>
        </div>
        
        <div class="info-card">
            <h3>💰 التكلفة</h3>
            <p>تحليل مقال واحد يكلف تقريباً $0.01 - $0.03 حسب الطول والنموذج المستخدم.</p>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle provider settings
    $('#odse_ai_provider').on('change', function() {
        var provider = $(this).val();
        
        $('.provider-setting').hide();
        $('.' + provider + '-setting').show();
    });
});
</script>
