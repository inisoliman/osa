jQuery(document).ready(function($) {
    
    $('#odse-test-connection').on('click', function() {
        const btn = $(this);
        const result = $('#odse-test-result');
        
        btn.prop('disabled', true);
        result.text('جاري الاختبار...');
        
        $.ajax({
            url: odseAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'odse_test_ai',
                nonce: odseAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    result.css('color', 'green').text('✅ ' + response.message);
                } else {
                    result.css('color', 'red').text('❌ ' + response.message);
                }
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    });
    
});
