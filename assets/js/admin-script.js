/* ===================================
   Orsozox Divine SEO - Admin Scripts
   =================================== */

jQuery(document).ready(function($) {
    
    'use strict';
    
    // ============================================
    // Test AI Connection
    // ============================================
    $('#odse-test-connection').on('click', function() {
        const btn = $(this);
        const result = $('#odse-test-result');
        
        btn.prop('disabled', true).text('جاري الاختبار...');
        result.removeClass('success error').hide();
        
        $.ajax({
            url: odseAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'odse_test_ai',
                nonce: odseAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    result.addClass('success')
                          .html('<span class="dashicons dashicons-yes-alt"></span> ' + response.message)
                          .show();
                } else {
                    result.addClass('error')
                          .html('<span class="dashicons dashicons-dismiss"></span> ' + response.message)
                          .show();
                }
            },
            error: function(xhr, status, error) {
                result.addClass('error')
                      .html('<span class="dashicons dashicons-dismiss"></span> فشل الاتصال: ' + error)
                      .show();
            },
            complete: function() {
                btn.prop('disabled', false).text('اختبار الاتصال');
            }
        });
    });
    
    // ============================================
    // Analyze Single Post (Meta Box)
    // ============================================
    $('#odse-analyze-post').on('click', function() {
        const btn = $(this);
        const postId = btn.data('post-id');
        const resultDiv = $('#odse-analysis-result');
        
        btn.prop('disabled', true).html('<span class="odse-loading"></span> جاري التحليل...');
        resultDiv.removeClass('show').html('');
        
        $.ajax({
            url: odseAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'odse_analyze_post',
                nonce: odseAdmin.nonce,
                post_id: postId
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    let html = '<div style="background:#d4edda; padding:15px; border-radius:6px; margin-top:15px;">';
                    html += '<h4 style="margin-top:0;">✅ تم التحليل بنجاح!</h4>';
                    
                    if (data.main_topic) {
                        html += '<p><strong>الموضوع:</strong> ' + data.main_topic + '</p>';
                    }
                    
                    if (data.primary_keywords && data.primary_keywords.length > 0) {
                        html += '<p><strong>الكلمات المفتاحية:</strong> ' + data.primary_keywords.join(', ') + '</p>';
                    }
                    
                    html += '<p><em>يرجى حفظ المقال لتطبيق التغييرات</em></p>';
                    html += '</div>';
                    
                    resultDiv.html(html).addClass('show');
                    
                    // Reload page after 2 seconds
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    resultDiv.html('<div style="background:#f8d7da; padding:15px; border-radius:6px; margin-top:15px;">' +
                                 '<strong>❌ خطأ:</strong> ' + response.data +
                                 '</div>').addClass('show');
                }
            },
            error: function(xhr, status, error) {
                resultDiv.html('<div style="background:#f8d7da; padding:15px; border-radius:6px; margin-top:15px;">' +
                             '<strong>❌ خطأ:</strong> فشل الاتصال' +
                             '</div>').addClass('show');
            },
            complete: function() {
                btn.prop('disabled', false).html('<span class="dashicons dashicons-analytics"></span> تحليل الآن');
            }
        });
    });
    
    // ============================================
    // Analyze All Posts (Batch Processing)
    // ============================================
    $('#odse-analyze-all').on('click', function() {
        const btn = $(this);
        const progressDiv = $('#odse-analyze-progress');
        const progressBar = progressDiv.find('.progress-bar-fill');
        const progressText = progressDiv.find('.progress-text');
        
        if (!confirm('هل تريد تحليل جميع المقالات؟ قد يستغرق هذا بعض الوقت.')) {
            return;
        }
        
        btn.prop('disabled', true);
        progressDiv.show();
        progressBar.css('width', '0%');
        progressText.text('جاري بدء التحليل...');
        
        let offset = 0;
        let totalProcessed = 0;
        
        function analyzeNextBatch() {
            $.ajax({
                url: odseAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'odse_analyze_all',
                    nonce: odseAdmin.nonce,
                    offset: offset
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.completed) {
                            progressBar.css('width', '100%');
                            progressText.text('✅ اكتمل التحليل بنجاح!');
                            
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            totalProcessed += response.data.processed;
                            offset = response.data.offset;
                            
                            // Update progress (rough estimate)
                            const progress = Math.min(95, (totalProcessed / 100) * 100);
                            progressBar.css('width', progress + '%');
                            progressText.text(response.data.message);
                            
                            // Continue to next batch
                            setTimeout(analyzeNextBatch, 1000);
                        }
                    } else {
                        alert('خطأ: ' + response.data);
                        btn.prop('disabled', false);
                        progressDiv.hide();
                    }
                },
                error: function() {
                    alert('حدث خطأ في الاتصال');
                    btn.prop('disabled', false);
                    progressDiv.hide();
                }
            });
        }
        
        analyzeNextBatch();
    });
    
    // ============================================
    // Suggest Internal Links
    // ============================================
    $('#odse-suggest-links').on('click', function() {
        const btn = $(this);
        const postId = btn.data('post-id');
        
        btn.prop('disabled', true).html('<span class="odse-loading"></span> جاري البحث...');
        
        $.ajax({
            url: odseAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'odse_suggest_links',
                nonce: odseAdmin.nonce,
                post_id: postId
            },
            success: function(response) {
                if (response.success) {
                    alert('تم إنشاء ' + response.data.suggestions.length + ' رابط مقترح!');
                    location.reload();
                } else {
                    alert('خطأ: ' + response.data);
                }
            },
            error: function() {
                alert('حدث خطأ في الاتصال');
            },
            complete: function() {
                btn.prop('disabled', false).html('<span class="dashicons dashicons-admin-links"></span> إنشاء اقتراحات الروابط');
            }
        });
    });
    
    // ============================================
    // Remove Internal Link
    // ============================================
    $(document).on('click', '.odse-remove-link', function() {
        const btn = $(this);
        const linkId = btn.data('link-id');
        
        if (!confirm('هل تريد حذف هذا الرابط؟')) {
            return;
        }
        
        $.ajax({
            url: odseAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'odse_remove_link',
                nonce: odseAdmin.nonce,
                link_id: linkId
            },
            success: function(response) {
                if (response.success) {
                    btn.closest('tr').fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    alert('خطأ: ' + response.data);
                }
            }
        });
    });
    
    // ============================================
    // Detect Keyword Cannibalization
    // ============================================
    $('#odse-detect-conflicts, #odse-refresh-conflicts').on('click', function() {
        const btn = $(this);
        
        btn.prop('disabled', true).html('<span class="odse-loading"></span> جاري الفحص...');
        
        $.ajax({
            url: odseAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'odse_detect_cannibalization',
                nonce: odseAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.count > 0) {
                        alert('تم اكتشاف ' + response.data.count + ' تنافس على الكلمات المفتاحية!');
                    } else {
                        alert('رائع! لا يوجد تنافس على الكلمات المفتاحية.');
                    }
                    location.reload();
                } else {
                    alert('خطأ: ' + response.data);
                }
            },
            complete: function() {
                btn.prop('disabled', false).html('<span class="dashicons dashicons-search"></span> كشف التنافس');
            }
        });
    });
    
    // ============================================
    // Build Internal Links
    // ============================================
    $('#odse-build-links').on('click', function() {
        const btn = $(this);
        
        if (!confirm('هل تريد بناء الروابط الداخلية بين المقالات المرتبطة؟')) {
            return;
        }
        
        btn.prop('disabled', true).html('<span class="odse-loading"></span> جاري البناء...');
        
        // This would trigger a batch process similar to analyze_all
        alert('هذه الميزة قيد التطوير');
        btn.prop('disabled', false).html('<span class="dashicons dashicons-admin-links"></span> بناء الروابط الداخلية');
    });
    
    // ============================================
    // Make Post Pillar
    // ============================================
    $(document).on('click', '.odse-make-pillar', function() {
        const btn = $(this);
        const postId = btn.data('post-id');
        const cluster = btn.data('cluster');
        
        if (!confirm('هل تريد جعل هذا المقال Pillar Content؟')) {
            return;
        }
        
        btn.prop('disabled', true).text('جاري التحديث...');
        
        $.ajax({
            url: odseAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'odse_make_pillar',
                nonce: odseAdmin.nonce,
                post_id: postId,
                cluster: cluster
            },
            success: function(response) {
                if (response.success) {
                    alert('تم تحويل المقال إلى Pillar Content!');
                    location.reload();
                } else {
                    alert('خطأ: ' + response.data);
                    btn.prop('disabled', false).text('جعله Pillar');
                }
            }
        });
    });
    
    // ============================================
    // Settings: Toggle Provider Fields
    // ============================================
    $('#odse_ai_provider').on('change', function() {
        const provider = $(this).val();
        
        $('.provider-setting').hide();
        $('.' + provider + '-setting').show();
    });
    
    // ============================================
    // ✅ جديد: عرض حالة التحليل الشامل التلقائي
    // ============================================
    function updateBulkAnalysisStatus() {
        $.ajax({
            url: odseAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'odse_get_bulk_analysis_status',
                nonce: odseAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    const status = response.data;
                    const container = $('#odse-bulk-status-container');
                    
                    let html = '';
                    
                    if (status.status === 'waiting_api_key') {
                        html = `
                            <div style="text-align: center; padding: 20px;">
                                <span class="dashicons dashicons-admin-network" style="font-size: 64px; color: #dba617;"></span>
                                <h3>⚠️ في انتظار API Key</h3>
                                <p>الرجاء إدخال API Key من صفحة الإعدادات لبدء التحليل التلقائي.</p>
                                <a href="${odseAdmin.ajaxUrl.replace('admin-ajax.php', 'admin.php?page=orsozox-divine-seo-ai')}" class="button button-primary">
                                    إدخال API Key
                                </a>
                            </div>
                        `;
                    } else if (status.status === 'in_progress') {
                        const percentage = Math.round((status.processed / status.total_posts) * 100);
                        
                        html = `
                            <div>
                                <h3 style="color: #2271b1; margin-top: 0;">
                                    <span class="dashicons dashicons-update" style="animation: spin 2s linear infinite;"></span>
                                    جاري التحليل التلقائي في الخلفية...
                                </h3>
                                
                                <div class="bulk-status-info">
                                    <div class="bulk-status-box">
                                        <h3>تم تحليله</h3>
                                        <div class="number" style="color: #00a32a;">${status.processed}</div>
                                    </div>
                                    <div class="bulk-status-box">
                                        <h3>المتبقي</h3>
                                        <div class="number" style="color: #dba617;">${status.remaining}</div>
                                    </div>
                                    <div class="bulk-status-box">
                                        <h3>إجمالي المقالات</h3>
                                        <div class="number">${status.total_posts}</div>
                                    </div>
                                </div>
                                
                                <div class="bulk-progress-bar">
                                    <div class="bulk-progress-fill" style="width: ${percentage}%;">
                                        ${percentage}%
                                    </div>
                                </div>
                                
                                <p style="text-align: center; color: #666;">
                                    <strong>آخر دفعة:</strong> ${status.last_batch || 0} مقالات<br>
                                    <strong>آخر تشغيل:</strong> ${status.last_run || 'جاري...'}
                                </p>
                                
                                <div style="background: #e7f3ff; padding: 15px; border-radius: 8px; margin-top: 20px;">
                                    <p style="margin: 0;">
                                        💡 <strong>ملاحظة:</strong> التحليل يعمل تلقائياً في الخلفية (10 مقالات كل ساعة).
                                        لا حاجة لفعل أي شيء! أو يمكنك استخدام زر "تحليل المقالات القديمة" أدناه للإسراع.
                                    </p>
                                </div>
                            </div>
                        `;
                        
                        // تحديث تلقائي كل 30 ثانية
                        setTimeout(updateBulkAnalysisStatus, 30000);
                        
                    } else if (status.status === 'completed') {
                        html = `
                            <div style="text-align: center; padding: 30px;">
                                <span class="dashicons dashicons-yes-alt" style="font-size: 64px; color: #00a32a;"></span>
                                <h3 style="color: #00a32a;">✅ اكتمل التحليل الشامل!</h3>
                                <p>تم تحليل <strong>${status.total_analyzed}</strong> مقالة بنجاح.</p>
                                <p style="color: #666; font-size: 14px;">
                                    تاريخ الإكمال: ${status.completed_at}
                                </p>
                            </div>
                        `;
                    } else {
                        html = `
                            <div style="text-align: center; padding: 20px;">
                                <span class="dashicons dashicons-info" style="font-size: 48px; color: #72aee6;"></span>
                                <h3>لم يبدأ التحليل بعد</h3>
                                <p>سيبدأ التحليل التلقائي خلال 5 دقائق من تفعيل الإضافة.</p>
                            </div>
                        `;
                    }
                    
                    container.html(html);
                }
            }
        });
    }
    
    // تشغيل عند تحميل الصفحة
    if ($('#odse-bulk-status-container').length > 0) {
        updateBulkAnalysisStatus();
    }
    
    // ============================================
    // ✅ جديد: تحليل المقالات القديمة (زر يدوي)
    // ============================================
    $('#odse-analyze-old').on('click', function() {
        const btn = $(this);
        const progressDiv = $('#odse-old-progress');
        const progressBar = progressDiv.find('.progress-bar-fill');
        const progressText = progressDiv.find('.progress-text');
        
        if (!confirm('هل تريد تحليل جميع المقالات القديمة الآن؟\n\nملاحظة: التحليل التلقائي يعمل في الخلفية بالفعل، هذا الزر للإسراع فقط.')) {
            return;
        }
        
        btn.prop('disabled', true);
        progressDiv.show();
        progressBar.css('width', '0%').text('0%');
        progressText.text('جاري بدء التحليل السريع...');
        
        let offset = 0;
        let totalProcessed = 0;
        
        function analyzeOldBatch() {
            $.ajax({
                url: odseAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'odse_analyze_old_posts',
                    nonce: odseAdmin.nonce,
                    offset: offset
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.completed) {
                            progressBar.css('width', '100%').text('100%');
                            progressText.html('✅ ' + response.data.message + '<br><strong>إجمالي المحلل: ' + response.data.total_analyzed + '</strong>');
                            
                            setTimeout(function() {
                                location.reload();
                            }, 3000);
                        } else {
                            totalProcessed += response.data.processed;
                            offset = response.data.offset;
                            
                            // تحديث شريط التقدم (تقديري)
                            const progress = Math.min(95, (totalProcessed / 100) * 100);
                            progressBar.css('width', progress + '%').text(Math.round(progress) + '%');
                            progressText.text(response.data.message);
                            
                            // استمر في الدفعة التالية
                            setTimeout(analyzeOldBatch, 1000);
                        }
                    } else {
                        alert('خطأ: ' + response.data);
                        btn.prop('disabled', false);
                        progressDiv.hide();
                    }
                },
                error: function() {
                    alert('حدث خطأ في الاتصال');
                    btn.prop('disabled', false);
                    progressDiv.hide();
                }
            });
        }
        
        analyzeOldBatch();
    });
    
});
