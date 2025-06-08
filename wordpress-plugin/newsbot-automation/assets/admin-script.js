// NewsBot Admin JavaScript
jQuery(document).ready(function($) {
    
    // Global AJAX ayarları
    $.ajaxSetup({
        beforeSend: function() {
            // Loading göster
            $('.loading').show();
        },
        complete: function() {
            // Loading gizle
            $('.loading').hide();
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            alert('Bir hata oluştu: ' + error);
        }
    });
    
    // Dashboard istatistiklerini güncelle
    function updateDashboardStats() {
        $.post(ajaxurl, {
            action: 'newsbot_api',
            action_type: 'get_analytics',
            nonce: newsbot_ajax.nonce
        }, function(response) {
            if (response.success) {
                updateStatsDisplay(response.data);
            }
        });
    }
    
    function updateStatsDisplay(data) {
        // İstatistikleri güncelle
        $('.stat-number').each(function(index) {
            const $this = $(this);
            const targetValue = parseInt($this.text().replace(/,/g, ''));
            animateNumber($this, 0, targetValue, 1000);
        });
    }
    
    // Sayı animasyonu
    function animateNumber($element, start, end, duration) {
        $({ value: start }).animate({ value: end }, {
            duration: duration,
            easing: 'swing',
            step: function() {
                $element.text(Math.floor(this.value).toLocaleString());
            },
            complete: function() {
                $element.text(end.toLocaleString());
            }
        });
    }
    
    // Trend konularını yükle
    function loadTrendingTopics() {
        if ($('#trending-topics').length === 0) return;
        
        $('#trending-topics').html('<div class="loading">Trend konuları yükleniyor...</div>');
        
        $.post(ajaxurl, {
            action: 'newsbot_get_trends',
            nonce: newsbot_ajax.nonce
        }, function(response) {
            if (response.success) {
                displayTrendingTopics(response.data);
            } else {
                $('#trending-topics').html('<div class="error">Trend konuları yüklenemedi.</div>');
            }
        });
    }
    
    function displayTrendingTopics(trends) {
        let html = '';
        trends.forEach(function(trend, index) {
            html += `
                <div class="trend-item" data-topic="${trend.title}" data-keywords='${JSON.stringify(trend.keywords)}'>
                    <h4>${trend.title}</h4>
                    <div class="trend-meta">
                        <span class="trend-category">${trend.category}</span>
                        <span class="trend-score">Skor: ${trend.trend_score}</span>
                        <span class="trend-volume">${trend.search_volume.toLocaleString()} arama</span>
                    </div>
                    <div class="trend-keywords">
                        ${trend.keywords.map(k => `<span class="keyword-tag">${k}</span>`).join('')}
                    </div>
                    <button class="select-topic-btn button button-small">Seç</button>
                </div>
            `;
        });
        $('#trending-topics').html(html);
    }
    
    // Trend konusu seçme
    $(document).on('click', '.select-topic-btn', function() {
        const $trendItem = $(this).closest('.trend-item');
        const topic = $trendItem.data('topic');
        const keywords = $trendItem.data('keywords');
        
        $('#selected-topic').val(topic);
        $('#generate-content').prop('disabled', false);
        
        // Seçili durumu göster
        $('.trend-item').removeClass('selected');
        $trendItem.addClass('selected');
        
        // Anahtar kelimeleri forma aktar
        if ($('#content-keywords').length > 0) {
            $('#content-keywords').val(keywords.join('\n'));
        }
    });
    
    // İçerik üretme
    $(document).on('click', '#generate-content', function() {
        const topic = $('#selected-topic').val();
        const contentType = $('#content-type').val();
        
        if (!topic) {
            alert('Lütfen bir konu seçin.');
            return;
        }
        
        const $button = $(this);
        const originalText = $button.text();
        
        $button.prop('disabled', true).text('İçerik üretiliyor...');
        
        $.post(ajaxurl, {
            action: 'newsbot_generate_content',
            topic: topic,
            content_type: contentType,
            keywords: ['teknoloji', 'haber', 'analiz'],
            nonce: newsbot_ajax.nonce
        }, function(response) {
            if (response.success) {
                displayGeneratedContent(response.data);
                $('#generated-content').show();
                
                // Başarı mesajı
                showNotification('İçerik başarıyla üretildi!', 'success');
            } else {
                showNotification('İçerik üretilemedi: ' + response.data, 'error');
            }
        }).always(function() {
            $button.prop('disabled', false).text(originalText);
        });
    });
    
    function displayGeneratedContent(content) {
        let html = `
            <div class="content-preview">
                <h4>Başlık: ${content.title}</h4>
                <p><strong>Kategori:</strong> ${content.category}</p>
                <p><strong>Tahmini Okuma Süresi:</strong> ${content.estimated_read_time}</p>
                <p><strong>SEO Skoru:</strong> ${content.seo_score}/100</p>
                <div class="content-structure">
                    <h5>İçerik Yapısı:</h5>
                    ${content.content_structure.map(section => `
                        <div class="section-preview">
                            <h6>${section.section}</h6>
                            <p>${section.content}</p>
                            <small>${section.word_count}</small>
                        </div>
                    `).join('')}
                </div>
                <div class="seo-keywords">
                    <h5>Anahtar Kelimeler:</h5>
                    ${content.seo_keywords.map(k => `<span class="keyword-tag">${k}</span>`).join('')}
                </div>
            </div>
        `;
        $('#content-preview').html(html);
    }
    
    // İçerik üretici formu
    $(document).on('submit', '#content-generator-form', function(e) {
        e.preventDefault();
        
        const topic = $('#content-topic').val();
        const keywords = $('#content-keywords').val().split('\n').filter(k => k.trim());
        const template = $('#content-template').val();
        
        if (!topic) {
            alert('Lütfen bir konu girin.');
            return;
        }
        
        const $button = $('button[type="submit"]');
        const originalText = $button.text();
        
        $button.prop('disabled', true).text('İçerik üretiliyor...');
        
        $.post(ajaxurl, {
            action: 'newsbot_generate_post',
            topic: topic,
            keywords: keywords,
            template_type: template,
            nonce: newsbot_ajax.nonce
        }, function(response) {
            if (response.success) {
                displayFullGeneratedContent(response.data);
                $('#generated-content-section').show();
                showNotification('İçerik başarıyla üretildi!', 'success');
            } else {
                showNotification('İçerik üretilemedi: ' + response.data, 'error');
            }
        }).always(function() {
            $button.prop('disabled', false).text(originalText);
        });
    });
    
    function displayFullGeneratedContent(content) {
        let html = `
            <div class="generated-content">
                <h3>${content.title}</h3>
                <div class="content-meta">
                    <span><strong>Kategori:</strong> ${content.category}</span>
                    <span><strong>Okuma Süresi:</strong> ${content.estimated_read_time}</span>
                    <span><strong>SEO Skoru:</strong> ${content.seo_score}/100</span>
                </div>
                <div class="content-excerpt">
                    <h4>Özet:</h4>
                    <p>${content.excerpt}</p>
                </div>
                <div class="content-structure">
                    <h4>İçerik:</h4>
                    <div class="content-text">${content.content}</div>
                </div>
                <div class="content-tags">
                    <h4>Etiketler:</h4>
                    ${content.tags.map(tag => `<span class="tag">${tag}</span>`).join('')}
                </div>
            </div>
        `;
        $('#generated-content-display').html(html);
        
        // İçeriği global değişkende sakla
        window.generatedContent = content;
    }
    
    // Yazı analizi
    $(document).on('click', '#analyze-post', function() {
        const postId = $('#post-selector').val();
        
        if (!postId) {
            alert('Lütfen bir yazı seçin.');
            return;
        }
        
        const $button = $(this);
        const originalText = $button.text();
        
        $button.prop('disabled', true).text('Analiz ediliyor...');
        
        $.post(ajaxurl, {
            action: 'newsbot_optimize_content',
            post_id: postId,
            nonce: newsbot_ajax.nonce
        }, function(response) {
            if (response.success) {
                displayOptimizationResults(response.data);
                $('#optimization-results').show();
                showNotification('Analiz tamamlandı!', 'success');
            } else {
                showNotification('Analiz yapılamadı: ' + response.data, 'error');
            }
        }).always(function() {
            $button.prop('disabled', false).text(originalText);
        });
    });
    
    function displayOptimizationResults(results) {
        let html = `
            <div class="optimization-results">
                <div class="current-stats">
                    <h4>Mevcut Durum:</h4>
                    <p><strong>Kelime Sayısı:</strong> ${results.current_word_count}</p>
                    <p><strong>SEO Skoru:</strong> ${results.current_seo_score}/100</p>
                    <p><strong>Okunabilirlik:</strong> ${results.readability_score}/100</p>
                </div>
                
                <div class="suggestions">
                    <h4>Öneriler:</h4>
                    <ul>
                        ${results.suggestions.map(suggestion => `<li>${suggestion}</li>`).join('')}
                    </ul>
                </div>
                
                <div class="improved-elements">
                    <h4>İyileştirme Önerileri:</h4>
                    <p><strong>Önerilen Meta Açıklama:</strong></p>
                    <textarea readonly rows="2" cols="60">${results.improved_meta}</textarea>
                    
                    <p><strong>Ek Anahtar Kelimeler:</strong></p>
                    ${results.additional_keywords.map(keyword => `<span class="keyword-tag">${keyword}</span>`).join('')}
                </div>
            </div>
        `;
        $('#optimization-content').html(html);
    }
    
    // Taslak olarak kaydet
    $(document).on('click', '#save-as-draft', function() {
        if (!window.generatedContent) {
            alert('Kaydedilecek içerik bulunamadı.');
            return;
        }
        
        const content = window.generatedContent;
        const $button = $(this);
        const originalText = $button.text();
        
        $button.prop('disabled', true).text('Kaydediliyor...');
        
        // WordPress'e yeni yazı ekle
        $.post(ajaxurl, {
            action: 'newsbot_save_draft',
            title: content.title,
            content: content.content,
            excerpt: content.excerpt,
            tags: content.tags,
            category: content.category,
            nonce: newsbot_ajax.nonce
        }, function(response) {
            if (response.success) {
                showNotification('İçerik taslak olarak kaydedildi!', 'success');
                
                // Yazılar sayfasına yönlendir
                setTimeout(function() {
                    window.open(response.data.edit_url, '_blank');
                }, 1000);
            } else {
                showNotification('Kaydetme işlemi başarısız: ' + response.data, 'error');
            }
        }).always(function() {
            $button.prop('disabled', false).text(originalText);
        });
    });
    
    // İçeriği kopyala
    $(document).on('click', '#copy-content', function() {
        if (!window.generatedContent) {
            alert('Kopyalanacak içerik bulunamadı.');
            return;
        }
        
        const content = window.generatedContent;
        const textToCopy = `${content.title}\n\n${content.content}`;
        
        // Clipboard API kullan
        if (navigator.clipboard) {
            navigator.clipboard.writeText(textToCopy).then(function() {
                showNotification('İçerik panoya kopyalandı!', 'success');
            });
        } else {
            // Fallback
            const textArea = document.createElement('textarea');
            textArea.value = textToCopy;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showNotification('İçerik panoya kopyalandı!', 'success');
        }
    });
    
    // Trend yenileme
    $(document).on('click', '#refresh-trends', function() {
        loadTrendingTopics();
    });
    
    // Bildirim göster
    function showNotification(message, type) {
        const notificationClass = type === 'success' ? 'notice-success' : 'notice-error';
        const $notification = $(`
            <div class="notice ${notificationClass} is-dismissible">
                <p>${message}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Bu bildirimi kapat.</span>
                </button>
            </div>
        `);
        
        $('.wrap').prepend($notification);
        
        // Otomatik kapat
        setTimeout(function() {
            $notification.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
        
        // Manuel kapat
        $notification.on('click', '.notice-dismiss', function() {
            $notification.fadeOut(function() {
                $(this).remove();
            });
        });
    }
    
    // Sayfa yüklendiğinde çalışacak fonksiyonlar
    function initializePage() {
        // Dashboard sayfasında istatistikleri güncelle
        if ($('.newsbot-dashboard').length > 0) {
            updateDashboardStats();
        }
        
        // Haber analizi sayfasında trend konularını yükle
        if ($('.newsbot-news-analysis').length > 0) {
            loadTrendingTopics();
        }
        
        // Tooltip'leri etkinleştir
        $('[title]').tooltip();
        
        // Form validasyonları
        $('form').on('submit', function() {
            const requiredFields = $(this).find('[required]');
            let isValid = true;
            
            requiredFields.each(function() {
                if (!$(this).val().trim()) {
                    $(this).addClass('error');
                    isValid = false;
                } else {
                    $(this).removeClass('error');
                }
            });
            
            if (!isValid) {
                showNotification('Lütfen tüm gerekli alanları doldurun.', 'error');
                return false;
            }
        });
    }
    
    // Sayfa hazır olduğunda başlat
    initializePage();
    
    // Dinamik içerik için event delegation
    $(document).on('click', '.newsbot-card', function() {
        $(this).addClass('clicked');
        setTimeout(() => {
            $(this).removeClass('clicked');
        }, 200);
    });
    
    // Responsive menü toggle
    $(document).on('click', '.menu-toggle', function() {
        $('.newsbot-sidebar').toggleClass('active');
    });
    
    // Arama fonksiyonu
    $(document).on('input', '.newsbot-search', function() {
        const searchTerm = $(this).val().toLowerCase();
        const $items = $('.searchable-item');
        
        $items.each(function() {
            const text = $(this).text().toLowerCase();
            if (text.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl+S ile kaydet
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            $('#save-as-draft').click();
        }
        
        // Ctrl+Enter ile içerik üret
        if (e.ctrlKey && e.key === 'Enter') {
            e.preventDefault();
            $('#generate-content').click();
        }
    });
});

// Utility fonksiyonlar
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('tr-TR');
}

function truncateText(text, maxLength) {
    if (text.length <= maxLength) return text;
    return text.substr(0, maxLength) + '...';
}

// Chart.js entegrasyonu (eğer yüklüyse)
if (typeof Chart !== 'undefined') {
    function createChart(canvasId, data, options) {
        const ctx = document.getElementById(canvasId);
        if (ctx) {
            return new Chart(ctx, {
                type: 'line',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    ...options
                }
            });
        }
    }
}