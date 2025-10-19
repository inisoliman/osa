<div class="wrap">
    <h1>⚙️ إعدادات الذكاء الاصطناعي</h1>
    
    <form method="post" action="options.php">
        <table class="form-table">
            <tr>
                <th>API Key</th>
                <td>
                    <input type="password" name="odse_ai_api_key" 
                           value="<?php echo esc_attr(get_option('odse_ai_api_key')); ?>" 
                           class="regular-text" />
                    <p class="description">
                        احصل على API Key من 
                        <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI</a>
                    </p>
                </td>
            </tr>
        </table>
        
        <?php submit_button('حفظ الإعدادات'); ?>
    </form>
    
    <hr>
    <h2>اختبار الاتصال</h2>
    <button type="button" class="button" id="odse-test-connection">
        اختبار الاتصال
    </button>
    <div id="odse-test-result"></div>
</div>
