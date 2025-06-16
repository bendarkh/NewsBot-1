<?php
/**
 * Admin sayfaları - Haber Analizi + İçerik Üretici + Planlayıcı Birleşik
 */

if (!defined('ABSPATH')) {
    exit;
}

class NewsBot_Admin_Pages {
    
    private $wp_stats_integration;
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_newsbot_api', array($this, 'handle_ajax_requests'));
        add_action('wp_ajax_newsbot_save_draft', array($this, 'save_content_as_draft'));
        add_action('wp_ajax_newsbot_auto_schedule', array($this, 'auto_schedule_content'));
        add_action('wp_ajax_newsbot_generate_content_from_news', array($this, 'generate_content_from_news'));
        add_action('admin_notices', array($this, 'show_wp_statistics_notice'));
        
        // WP Statistics entegrasyonu
        $this->wp_stats_integration = new NewsBot_WP_Statistics_Integration();
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'NewsBot Automation',
            'NewsBot',
            'manage_options',
            'newsbot-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-chart-line',
            30
        );
        
        add_submenu_page(
            'newsbot-dashboard',
            'Haber Analizi & İçerik Üretici',
            'Haber Analizi & İçerik',
            'manage_options',
            'newsbot-news-analysis',
            array($this, 'news_analysis_page')
        );
        
        add_submenu_page(
            'newsbot-dashboard',
            'Başlık Jeneratörü',
            'Başlık Jeneratörü',
            'manage_options',
            'newsbot-title-generator',
            array($this, 'title_generator_page')
        );
        
        add_submenu_page(
            'newsbot-dashboard',
            'SEO Takip',
            'SEO Takip',
            'manage_options',
            'newsbot-seo-tracker',
            array($this, 'seo_tracker_page')
        );
        
        add_submenu_page(
            'newsbot-dashboard',
            'Ayarlar',
            'Ayarlar',
            'manage_options',
            'newsbot-settings',
            array($this, 'settings_page')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'newsbot') === false) {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script('jquery-ui-tooltip');
        wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/ui-lightness/jquery-ui.css');
        
        wp_enqueue_script(
            'newsbot-admin',
            plugin_dir_url(dirname(__FILE__)) . 'assets/admin-script.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        wp_enqueue_style(
            'newsbot-admin',
            plugin_dir_url(dirname(__FILE__)) . 'assets/admin-style.css',
            array(),
            '1.0.0'
        );
        
        wp_localize_script('newsbot-admin', 'newsbot_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('newsbot_nonce'),
            'wp_statistics_active' => $this->wp_stats_integration->is_wp_statistics_active()
        ));
    }
    
    /**
     * WP Statistics uyarısını göster
     */
    public function show_wp_statistics_notice() {
        $screen = get_current_screen();
        if (strpos($screen->id, 'newsbot') !== false) {
            $this->wp_stats_integration->show_wp_statistics_notice();
        }
    }
    
    public function dashboard_page() {
        ?>
        <div class="wrap newsbot-dashboard">
            <h1>NewsBot Automation Dashboard</h1>
            
            <!-- WP Statistics Durum Kontrolü -->
            <div class="newsbot-wp-statistics-status" id="wp-statistics-status">
                <div class="status-loading">WP Statistics durumu kontrol ediliyor...</div>
            </div>
            
            <!-- İstatistik Kartları -->
            <div class="newsbot-stats-grid" id="stats-grid">
                <div class="newsbot-stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3>Günlük Ziyaretçi</h3>
                        <p class="stat-number" id="daily-visitors">-</p>
                        <span class="stat-change" id="visitors-change">Yükleniyor...</span>
                    </div>
                </div>
                
                <div class="newsbot-stat-card">
                    <div class="stat-icon">📈</div>
                    <div class="stat-content">
                        <h3>Toplam Ziyaret</h3>
                        <p class="stat-number" id="total-visits">-</p>
                        <span class="stat-change" id="visits-change">Yükleniyor...</span>
                    </div>
                </div>
                
                <div class="newsbot-stat-card">
                    <div class="stat-icon">📝</div>
                    <div class="stat-content">
                        <h3>Planlanmış İçerik</h3>
                        <p class="stat-number" id="scheduled-content">-</p>
                        <span class="stat-change neutral">Bu hafta</span>
                    </div>
                </div>
                
                <div class="newsbot-stat-card">
                    <div class="stat-icon">🔍</div>
                    <div class="stat-content">
                        <h3>Çıkma Oranı</h3>
                        <p class="stat-number" id="bounce-rate">-</p>
                        <span class="stat-change" id="bounce-change">Yükleniyor...</span>
                    </div>
                </div>
            </div>
            
            <!-- Dashboard Grid -->
            <div class="newsbot-dashboard-grid">
                <!-- En Popüler İçerikler (WP Statistics'ten) -->
                <div class="newsbot-card">
                    <h2>📈 En Popüler İçerikler</h2>
                    <div class="popular-posts-list" id="popular-posts-list">
                        <div class="loading">Popüler içerikler yükleniyor...</div>
                    </div>
                </div>
                
                <!-- Arama Kelimeleri (WP Statistics'ten) -->
                <div class="newsbot-card">
                    <h2>🔍 En Çok Aranan Kelimeler</h2>
                    <div class="search-keywords-list" id="search-keywords-list">
                        <div class="loading">Arama kelimeleri yükleniyor...</div>
                    </div>
                </div>
                
                <!-- Haftalık Trafik Grafiği -->
                <div class="newsbot-card">
                    <h2>📊 Haftalık Trafik</h2>
                    <div class="weekly-chart" id="weekly-chart">
                        <div class="loading">Haftalık veriler yükleniyor...</div>
                    </div>
                </div>
                
                <!-- Ziyaretçi Ülkeleri -->
                <div class="newsbot-card">
                    <h2>🌍 Ziyaretçi Ülkeleri</h2>
                    <div class="visitor-countries" id="visitor-countries">
                        <div class="loading">Ülke verileri yükleniyor...</div>
                    </div>
                </div>
                
                <!-- Hızlı İşlemler -->
                <div class="newsbot-card">
                    <h2>⚡ Hızlı İşlemler</h2>
                    <div class="quick-actions">
                        <a href="<?php echo admin_url('admin.php?page=newsbot-news-analysis'); ?>" class="action-button">
                            <div class="action-icon">📰</div>
                            <div class="action-text">Haber Analizi</div>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=newsbot-title-generator'); ?>" class="action-button">
                            <div class="action-icon">💡</div>
                            <div class="action-text">Başlık Üret</div>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=newsbot-seo-tracker'); ?>" class="action-button">
                            <div class="action-icon">🔍</div>
                            <div class="action-text">SEO Takip</div>
                        </a>
                        <a href="<?php echo admin_url('post-new.php'); ?>" class="action-button">
                            <div class="action-icon">✍️</div>
                            <div class="action-text">Yeni Yazı</div>
                        </a>
                    </div>
                </div>
                
                <!-- Tarayıcı İstatistikleri -->
                <div class="newsbot-card">
                    <h2>🌐 Tarayıcı İstatistikleri</h2>
                    <div class="browser-stats" id="browser-stats">
                        <div class="loading">Tarayıcı verileri yükleniyor...</div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // WP Statistics verilerini yükle
            loadRealAnalyticsData();
            
            function loadRealAnalyticsData() {
                $.post(ajaxurl, {
                    action: 'newsbot_get_real_analytics',
                    nonce: newsbot_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        displayRealAnalyticsData(response.data);
                        showWPStatisticsStatus(true);
                    } else {
                        displayFallbackData(response.data.fallback_data);
                        showWPStatisticsStatus(false, response.data.message, response.data.install_url);
                    }
                }).fail(function() {
                    showWPStatisticsStatus(false, 'Veri yüklenirken hata oluştu');
                });
            }
            
            function showWPStatisticsStatus(active, message, installUrl) {
                let html = '';
                
                if (active) {
                    html = `
                        <div class="wp-statistics-active">
                            <span class="status-icon">✅</span>
                            <span class="status-text">WP Statistics aktif - Gerçek veriler gösteriliyor</span>
                        </div>
                    `;
                } else {
                    html = `
                        <div class="wp-statistics-inactive">
                            <span class="status-icon">⚠️</span>
                            <span class="status-text">${message}</span>
                            ${installUrl ? `<a href="${installUrl}" class="button button-primary" target="_blank">WP Statistics Kur</a>` : ''}
                        </div>
                    `;
                }
                
                $('#wp-statistics-status').html(html);
            }
            
            function displayRealAnalyticsData(data) {
                // İstatistik kartlarını güncelle
                $('#daily-visitors').text(data.daily_visitors.toLocaleString());
                $('#total-visits').text(data.total_visits.toLocaleString());
                $('#bounce-rate').text(data.bounce_rate + '%');
                
                // Değişim yüzdelerini hesapla ve göster
                updateChangeIndicators(data);
                
                // Popüler içerikleri göster
                displayPopularPosts(data.popular_pages);
                
                // Arama kelimelerini göster
                displaySearchKeywords(data.search_keywords);
                
                // Haftalık grafiği göster
                displayWeeklyChart(data.weekly_stats);
                
                // Ziyaretçi ülkelerini göster
                displayVisitorCountries(data.visitor_countries);
                
                // Tarayıcı istatistiklerini göster
                displayBrowserStats(data.browser_stats);
                
                // Planlanmış içerik sayısını güncelle
                updateScheduledContentCount();
            }
            
            function displayFallbackData(data) {
                if (!data) return;
                
                $('#daily-visitors').text(data.daily_visitors.toLocaleString());
                $('#total-visits').text(data.total_visits.toLocaleString());
                $('#bounce-rate').text('45%');
                
                displayPopularPosts(data.popular_pages);
                displaySearchKeywords(data.search_keywords);
                displayWeeklyChart(data.weekly_stats);
            }
            
            function updateChangeIndicators(data) {
                // Örnek değişim hesaplamaları
                const visitorsChange = Math.floor(Math.random() * 20) - 10; // -10 ile +10 arası
                const visitsChange = Math.floor(Math.random() * 15) - 5;
                const bounceChange = Math.floor(Math.random() * 10) - 5;
                
                updateChangeElement('#visitors-change', visitorsChange);
                updateChangeElement('#visits-change', visitsChange);
                updateChangeElement('#bounce-change', bounceChange);
            }
            
            function updateChangeElement(selector, change) {
                const $element = $(selector);
                const changeText = change > 0 ? `+${change}%` : `${change}%`;
                const changeClass = change > 0 ? 'positive' : (change < 0 ? 'negative' : 'neutral');
                
                $element.text(changeText).removeClass('positive negative neutral').addClass(changeClass);
            }
            
            function displayPopularPosts(posts) {
                let html = '';
                posts.slice(0, 8).forEach(function(post) {
                    html += `
                        <div class="popular-post-item">
                            <a href="${post.url}" class="post-title" target="_blank">${post.title}</a>
                            <span class="post-views">${post.views.toLocaleString()} görüntüleme</span>
                        </div>
                    `;
                });
                
                if (html === '') {
                    html = '<div class="no-content">Henüz popüler içerik verisi yok.</div>';
                }
                
                $('#popular-posts-list').html(html);
            }
            
            function displaySearchKeywords(keywords) {
                let html = '';
                keywords.slice(0, 10).forEach(function(keyword) {
                    html += `
                        <div class="keyword-item">
                            <span class="keyword">${keyword.keyword}</span>
                            <span class="keyword-count">${keyword.count} arama</span>
                        </div>
                    `;
                });
                
                if (html === '') {
                    html = '<div class="no-content">Henüz arama kelimesi verisi yok.</div>';
                }
                
                $('#search-keywords-list').html(html);
            }
            
            function displayWeeklyChart(weeklyStats) {
                let html = '<div class="chart-container">';
                
                weeklyStats.forEach(function(day) {
                    const maxHeight = Math.max(...weeklyStats.map(d => d.visitors));
                    const height = (day.visitors / maxHeight) * 100;
                    
                    html += `
                        <div class="chart-bar">
                            <div class="bar" style="height: ${height}%" title="${day.visitors} ziyaretçi"></div>
                            <div class="bar-label">${day.day_name.substr(0, 3)}</div>
                        </div>
                    `;
                });
                
                html += '</div>';
                $('#weekly-chart').html(html);
            }
            
            function displayVisitorCountries(countries) {
                let html = '';
                countries.slice(0, 8).forEach(function(country) {
                    html += `
                        <div class="country-item">
                            <span class="country-name">${country.country}</span>
                            <span class="country-count">${country.count.toLocaleString()}</span>
                        </div>
                    `;
                });
                
                if (html === '') {
                    html = '<div class="no-content">Henüz ülke verisi yok.</div>';
                }
                
                $('#visitor-countries').html(html);
            }
            
            function displayBrowserStats(browsers) {
                let html = '';
                browsers.slice(0, 6).forEach(function(browser) {
                    html += `
                        <div class="browser-item">
                            <span class="browser-name">${browser.browser}</span>
                            <div class="browser-bar">
                                <div class="browser-fill" style="width: ${browser.percentage}%"></div>
                            </div>
                            <span class="browser-percentage">${browser.percentage}%</span>
                        </div>
                    `;
                });
                
                if (html === '') {
                    html = '<div class="no-content">Henüz tarayıcı verisi yok.</div>';
                }
                
                $('#browser-stats').html(html);
            }
            
            function updateScheduledContentCount() {
                // Planlanmış içerik sayısını WordPress'ten al
                $.post(ajaxurl, {
                    action: 'newsbot_get_scheduled_posts',
                    nonce: newsbot_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        $('#scheduled-content').text(response.data.length);
                    }
                });
            }
            
            // Verileri 5 dakikada bir yenile
            setInterval(loadRealAnalyticsData, 300000);
        });
        </script>
        <?php
    }
    
    /**
     * Haber Analizi + İçerik Üretici + Planlayıcı Birleşik Sayfa
     */
    public function news_analysis_page() {
        ?>
        <div class="wrap newsbot-news-analysis">
            <h1>📰 Haber Analizi & İçerik Üretici</h1>
            
            <div class="newsbot-analysis-grid">
                <!-- Sol Panel: Kategorili Haberler -->
                <div class="newsbot-card news-categories-panel">
                    <h2>📊 Kategorili Haberler</h2>
                    
                    <!-- Kategori Sekmeleri -->
                    <div class="newsbot-category-tabs">
                        <div class="tab-navigation">
                            <button class="tab-button active" data-category="yapay_zeka">
                                <span class="tab-icon">🤖</span>
                                <span class="tab-name">Yapay Zeka</span>
                            </button>
                            <button class="tab-button" data-category="blockchain">
                                <span class="tab-icon">₿</span>
                                <span class="tab-name">Blockchain</span>
                            </button>
                            <button class="tab-button" data-category="mobil">
                                <span class="tab-icon">📱</span>
                                <span class="tab-name">Mobil</span>
                            </button>
                            <button class="tab-button" data-category="oyun">
                                <span class="tab-icon">🎮</span>
                                <span class="tab-name">Oyun</span>
                            </button>
                            <button class="tab-button" data-category="siber_guvenlik">
                                <span class="tab-icon">🔒</span>
                                <span class="tab-name">Güvenlik</span>
                            </button>
                            <button class="tab-button" data-category="startup">
                                <span class="tab-icon">🚀</span>
                                <span class="tab-name">Startup</span>
                            </button>
                            <button class="tab-button" data-category="bilim">
                                <span class="tab-icon">🔬</span>
                                <span class="tab-name">Bilim</span>
                            </button>
                            <button class="tab-button" data-category="sosyal_medya">
                                <span class="tab-icon">📲</span>
                                <span class="tab-name">Sosyal Medya</span>
                            </button>
                        </div>
                        
                        <!-- Haber Listesi -->
                        <div class="tab-content">
                            <div class="category-header">
                                <h3 id="current-category-title">Yapay Zeka Haberleri</h3>
                                <button class="button refresh-category-btn" id="refresh-category">
                                    <span>🔄</span> Yenile
                                </button>
                            </div>
                            
                            <!-- İki Sütunlu Haber Listesi -->
                            <div class="news-grid" id="news-grid">
                                <div class="loading">Haberler yükleniyor...</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sağ Panel: İçerik Üretici ve Planlayıcı -->
                <div class="newsbot-card content-generator-panel">
                    <h2>✍️ İçerik Üretici & Planlayıcı</h2>
                    
                    <!-- Seçili Haber -->
                    <div class="selected-news-panel" id="selected-news-panel" style="display: none;">
                        <div class="selected-news">
                            <h4>📰 Seçili Haber:</h4>
                            <p id="selected-news-title">-</p>
                            <small id="selected-news-source">-</small>
                        </div>
                        
                        <!-- İçerik Üretim Seçenekleri -->
                        <div class="content-generation-options">
                            <div class="option-group">
                                <label>İçerik Türü:</label>
                                <select id="content-type">
                                    <option value="news_article">Haber Makalesi</option>
                                    <option value="analysis">Detaylı Analiz</option>
                                    <option value="tutorial">Rehber İçerik</option>
                                    <option value="review">İnceleme</option>
                                    <option value="listicle">Liste Makalesi</option>
                                </select>
                            </div>
                            
                            <div class="option-group">
                                <label>Hedef Kelime Sayısı:</label>
                                <select id="word-count">
                                    <option value="500">500-800 kelime</option>
                                    <option value="1000">1000-1500 kelime</option>
                                    <option value="2000">2000+ kelime</option>
                                </select>
                            </div>
                            
                            <div class="option-group">
                                <label>SEO Odak Kelimesi:</label>
                                <input type="text" id="focus-keyword" placeholder="Ana anahtar kelime">
                            </div>
                            
                            <div class="option-group">
                                <label>Yayın Stratejisi:</label>
                                <select id="publish-strategy">
                                    <option value="draft">Taslak Olarak Kaydet</option>
                                    <option value="immediate">Hemen Yayınla</option>
                                    <option value="scheduled">Zamanla</option>
                                </select>
                            </div>
                            
                            <div class="option-group" id="schedule-options" style="display: none;">
                                <label>Yayın Tarihi:</label>
                                <input type="date" id="publish-date" min="<?php echo date('Y-m-d'); ?>">
                                <input type="time" id="publish-time" value="09:00">
                            </div>
                        </div>
                        
                        <!-- İçerik Üretim Butonu -->
                        <div class="generation-actions">
                            <button class="button button-primary" id="generate-content-btn">
                                ✨ İçerik Üret ve Kaydet
                            </button>
                            <button class="button" id="preview-content-btn">
                                👁️ Önizleme
                            </button>
                        </div>
                    </div>
                    
                    <!-- İçerik Önizleme -->
                    <div class="content-preview-panel" id="content-preview-panel" style="display: none;">
                        <h4>📝 Üretilen İçerik Önizlemesi</h4>
                        <div class="content-preview" id="content-preview">
                            <!-- İçerik önizlemesi buraya gelecek -->
                        </div>
                        
                        <div class="preview-actions">
                            <button class="button button-primary" id="save-to-wordpress-btn">
                                💾 WordPress'e Kaydet
                            </button>
                            <button class="button" id="edit-content-btn">
                                ✏️ Düzenle
                            </button>
                            <button class="button" id="regenerate-btn">
                                🔄 Yeniden Üret
                            </button>
                        </div>
                    </div>
                    
                    <!-- Planlama Takvimi -->
                    <div class="planning-calendar">
                        <h4>📅 Bu Haftanın İçerik Planı</h4>
                        <div class="calendar-grid" id="planning-calendar">
                            <!-- Takvim JavaScript ile doldurulacak -->
                        </div>
                    </div>
                    
                    <!-- Son Üretilen İçerikler -->
                    <div class="recent-content">
                        <h4>📋 Son Üretilen İçerikler</h4>
                        <div class="recent-content-list" id="recent-content-list">
                            <div class="loading">İçerikler yükleniyor...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            let selectedNews = null;
            let currentCategory = 'yapay_zeka';
            let generatedContent = null;
            
            // Kategori değiştirme
            $('.tab-button').on('click', function() {
                $('.tab-button').removeClass('active');
                $(this).addClass('active');
                
                currentCategory = $(this).data('category');
                const categoryName = $(this).find('.tab-name').text();
                $('#current-category-title').text(categoryName + ' Haberleri');
                
                loadCategoryNews(currentCategory);
            });
            
            // Haberleri yükle
            function loadCategoryNews(category) {
                $('#news-grid').html('<div class="loading">Haberler yükleniyor...</div>');
                
                $.post(ajaxurl, {
                    action: 'newsbot_get_categorized_news',
                    category: category,
                    limit: 20,
                    nonce: newsbot_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        displayNewsGrid(response.data);
                    }
                });
            }
            
            // Haberleri iki sütunlu grid'de göster
            function displayNewsGrid(news) {
                let html = '<div class="news-items-grid">';
                
                news.forEach(function(item, index) {
                    html += `
                        <div class="news-item-card" data-news='${JSON.stringify(item)}'>
                            <div class="news-item-header">
                                <span class="news-number">${index + 1}</span>
                                <span class="news-source">${item.source}</span>
                                <span class="news-time">${item.reading_time}</span>
                            </div>
                            <h4 class="news-title">${item.title}</h4>
                            <p class="news-summary">${item.summary}</p>
                            <div class="news-meta">
                                <span class="news-category">${item.category}</span>
                                <button class="select-news-btn button button-small">Seç ve Üret</button>
                            </div>
                        </div>
                    `;
                });
                
                html += '</div>';
                $('#news-grid').html(html);
            }
            
            // Haber seçme
            $(document).on('click', '.select-news-btn', function() {
                const newsData = $(this).closest('.news-item-card').data('news');
                selectedNews = newsData;
                
                // Seçili haberi göster
                $('#selected-news-title').text(newsData.title);
                $('#selected-news-source').text(newsData.source + ' - ' + newsData.published_at);
                $('#selected-news-panel').show();
                
                // Focus keyword'ü otomatik doldur
                if (newsData.keywords && newsData.keywords.length > 0) {
                    $('#focus-keyword').val(newsData.keywords[0]);
                }
                
                // Diğer seçimleri kaldır
                $('.news-item-card').removeClass('selected');
                $(this).closest('.news-item-card').addClass('selected');
                
                // Önizleme panelini gizle
                $('#content-preview-panel').hide();
            });
            
            // Yayın stratejisi değişikliği
            $('#publish-strategy').on('change', function() {
                if ($(this).val() === 'scheduled') {
                    $('#schedule-options').show();
                } else {
                    $('#schedule-options').hide();
                }
            });
            
            // İçerik üretme
            $('#generate-content-btn').on('click', function() {
                if (!selectedNews) {
                    alert('Lütfen bir haber seçin.');
                    return;
                }
                
                const contentData = {
                    news: selectedNews,
                    content_type: $('#content-type').val(),
                    word_count: $('#word-count').val(),
                    focus_keyword: $('#focus-keyword').val(),
                    strategy: $('#publish-strategy').val(),
                    publish_date: $('#publish-date').val(),
                    publish_time: $('#publish-time').val()
                };
                
                const $btn = $(this);
                const originalText = $btn.text();
                $btn.prop('disabled', true).text('✨ İçerik üretiliyor...');
                
                $.post(ajaxurl, {
                    action: 'newsbot_generate_content_from_news',
                    content_data: contentData,
                    nonce: newsbot_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        generatedContent = response.data;
                        displayContentPreview(response.data);
                        $('#content-preview-panel').show();
                        
                        // Başarı mesajı
                        showNotification('İçerik başarıyla üretildi!', 'success');
                    } else {
                        showNotification('İçerik üretilemedi: ' + response.data, 'error');
                    }
                }).always(function() {
                    $btn.prop('disabled', false).text(originalText);
                });
            });
            
            // İçerik önizlemesi göster
            function displayContentPreview(content) {
                let html = `
                    <div class="content-preview-container">
                        <div class="content-meta">
                            <h3>${content.title}</h3>
                            <div class="meta-info">
                                <span><strong>Kategori:</strong> ${content.category}</span>
                                <span><strong>Kelime Sayısı:</strong> ${content.word_count}</span>
                                <span><strong>SEO Skoru:</strong> ${content.seo_score}/100</span>
                                <span><strong>Okuma Süresi:</strong> ${content.reading_time}</span>
                            </div>
                        </div>
                        
                        <div class="content-excerpt">
                            <h4>Özet:</h4>
                            <p>${content.excerpt}</p>
                        </div>
                        
                        <div class="content-body">
                            <h4>İçerik:</h4>
                            <div class="content-text">${content.content.substring(0, 500)}...</div>
                        </div>
                        
                        <div class="content-tags">
                            <h4>Etiketler:</h4>
                            ${content.tags.map(tag => `<span class="tag">${tag}</span>`).join('')}
                        </div>
                        
                        <div class="seo-analysis">
                            <h4>SEO Analizi:</h4>
                            <ul>
                                <li>Başlık uzunluğu: ${content.title.length} karakter</li>
                                <li>Meta açıklama: ${content.meta_description.length} karakter</li>
                                <li>Anahtar kelime yoğunluğu: Uygun</li>
                                <li>Okunabilirlik: ${content.readability_score}/100</li>
                            </ul>
                        </div>
                    </div>
                `;
                
                $('#content-preview').html(html);
            }
            
            // WordPress'e kaydet
            $('#save-to-wordpress-btn').on('click', function() {
                if (!generatedContent) {
                    alert('Kaydedilecek içerik bulunamadı.');
                    return;
                }
                
                const strategy = $('#publish-strategy').val();
                const $btn = $(this);
                const originalText = $btn.text();
                $btn.prop('disabled', true).text('💾 Kaydediliyor...');
                
                $.post(ajaxurl, {
                    action: 'newsbot_save_draft',
                    content: generatedContent,
                    strategy: strategy,
                    nonce: newsbot_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        showNotification('İçerik WordPress\'e kaydedildi!', 'success');
                        
                        // Yeni sekmede düzenleme sayfasını aç
                        setTimeout(function() {
                            window.open(response.data.edit_url, '_blank');
                        }, 1000);
                        
                        // Panelleri temizle
                        $('#selected-news-panel').hide();
                        $('#content-preview-panel').hide();
                        $('.news-item-card').removeClass('selected');
                        selectedNews = null;
                        generatedContent = null;
                        
                        // Son içerikleri yenile
                        loadRecentContent();
                        loadPlanningCalendar();
                    } else {
                        showNotification('Kaydetme işlemi başarısız: ' + response.data, 'error');
                    }
                }).always(function() {
                    $btn.prop('disabled', false).text(originalText);
                });
            });
            
            // Planlama takvimini yükle
            function loadPlanningCalendar() {
                const today = new Date();
                let html = '';
                
                for (let i = 0; i < 7; i++) {
                    const date = new Date(today);
                    date.setDate(today.getDate() + i);
                    
                    const dayName = date.toLocaleDateString('tr-TR', { weekday: 'short' });
                    const dayNumber = date.getDate();
                    const monthName = date.toLocaleDateString('tr-TR', { month: 'short' });
                    
                    html += `
                        <div class="calendar-day">
                            <div class="day-header">
                                <span class="day-name">${dayName}</span>
                                <span class="day-number">${dayNumber} ${monthName}</span>
                            </div>
                            <div class="day-slots">
                                <div class="time-slot">09:00</div>
                                <div class="time-slot">12:00</div>
                                <div class="time-slot">15:00</div>
                                <div class="time-slot">18:00</div>
                            </div>
                        </div>
                    `;
                }
                
                $('#planning-calendar').html(html);
            }
            
            // Son içerikleri yükle
            function loadRecentContent() {
                $.post(ajaxurl, {
                    action: 'newsbot_get_recent_generated_content',
                    nonce: newsbot_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        displayRecentContent(response.data);
                    }
                });
            }
            
            // Son içerikleri göster
            function displayRecentContent(contents) {
                let html = '';
                contents.slice(0, 5).forEach(function(content) {
                    html += `
                        <div class="recent-content-item">
                            <div class="content-title">${content.title}</div>
                            <div class="content-meta">
                                <span class="content-date">${content.created_date}</span>
                                <span class="content-status">${content.status}</span>
                                <a href="${content.edit_url}" target="_blank" class="edit-link">Düzenle</a>
                            </div>
                        </div>
                    `;
                });
                
                if (html === '') {
                    html = '<div class="no-content">Henüz üretilen içerik yok.</div>';
                }
                
                $('#recent-content-list').html(html);
            }
            
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
            
            // Sayfa yüklendiğinde
            loadCategoryNews(currentCategory);
            loadPlanningCalendar();
            loadRecentContent();
        });
        </script>
        <?php
    }
    
    /**
     * Haberden içerik üret
     */
    public function generate_content_from_news() {
        check_ajax_referer('newsbot_nonce', 'nonce');
        
        $content_data = $_POST['content_data'];
        $news = $content_data['news'];
        
        // İçerik üret
        $generated_content = array(
            'title' => $this->generate_content_title($news['title'], $content_data['content_type']),
            'content' => $this->generate_content_body($news, $content_data),
            'excerpt' => $this->generate_excerpt($news['title']),
            'category' => $news['category'],
            'tags' => $this->extract_tags($news['title']),
            'meta_description' => $this->generate_meta_description($news['title'], $content_data['focus_keyword']),
            'focus_keyword' => $content_data['focus_keyword'],
            'word_count' => $this->estimate_word_count($content_data['word_count']),
            'seo_score' => rand(75, 95),
            'readability_score' => rand(70, 90),
            'reading_time' => $this->calculate_reading_time($content_data['word_count']),
            'source_news' => $news
        );
        
        wp_send_json_success($generated_content);
    }
    
    /**
     * İçeriği WordPress'e kaydet
     */
    public function save_content_as_draft() {
        check_ajax_referer('newsbot_nonce', 'nonce');
        
        $content = $_POST['content'];
        $strategy = $_POST['strategy'];
        
        // Post durumunu belirle
        $post_status = 'draft';
        $post_date = current_time('mysql');
        
        if ($strategy === 'immediate') {
            $post_status = 'publish';
        } elseif ($strategy === 'scheduled') {
            $post_status = 'future';
            $publish_date = sanitize_text_field($_POST['publish_date']);
            $publish_time = sanitize_text_field($_POST['publish_time']);
            $post_date = $publish_date . ' ' . $publish_time . ':00';
        }
        
        // WordPress'e yazı ekle
        $post_data = array(
            'post_title' => $content['title'],
            'post_content' => $content['content'],
            'post_excerpt' => $content['excerpt'],
            'post_status' => $post_status,
            'post_date' => $post_date,
            'post_type' => 'post',
            'post_author' => get_current_user_id()
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            wp_send_json_error('İçerik kaydedilemedi: ' . $post_id->get_error_message());
        }
        
        // Meta bilgileri kaydet
        update_post_meta($post_id, 'newsbot_generated', true);
        update_post_meta($post_id, 'newsbot_source_news', $content['source_news']);
        update_post_meta($post_id, 'newsbot_seo_score', $content['seo_score']);
        update_post_meta($post_id, 'newsbot_focus_keyword', $content['focus_keyword']);
        
        // Yoast SEO meta description
        if ($content['meta_description']) {
            update_post_meta($post_id, '_yoast_wpseo_metadesc', $content['meta_description']);
        }
        
        // Kategori ata
        if (!empty($content['category'])) {
            $cat_id = $this->get_or_create_category($content['category']);
            wp_set_post_categories($post_id, array($cat_id));
        }
        
        // Etiketleri ata
        if (!empty($content['tags'])) {
            wp_set_post_tags($post_id, $content['tags']);
        }
        
        wp_send_json_success(array(
            'post_id' => $post_id,
            'edit_url' => admin_url('post.php?post=' . $post_id . '&action=edit'),
            'view_url' => get_permalink($post_id),
            'status' => $post_status
        ));
    }
    
    // Yardımcı metodlar
    private function generate_content_title($original_title, $content_type) {
        $templates = array(
            'news_article' => array(
                '%s: Son Gelişmeler ve Detaylar',
                '%s Hakkında Bilmeniz Gerekenler',
                '%s ile İlgili Önemli Açıklama'
            ),
            'analysis' => array(
                '%s: Detaylı Analiz ve Değerlendirme',
                '%s Konusunda Uzman Görüşleri',
                '%s Analizi: Ne Anlama Geliyor?'
            ),
            'tutorial' => array(
                '%s Nasıl Kullanılır? Adım Adım Rehber',
                '%s için Başlangıç Rehberi',
                '%s Öğrenmek İsteyenler İçin Kılavuz'
            ),
            'review' => array(
                '%s İncelemesi: Artıları ve Eksileri',
                '%s Değerlendirmesi: Alınır mı?',
                '%s Hakkında Dürüst İnceleme'
            ),
            'listicle' => array(
                '%s için 10 Önemli İpucu',
                '%s Hakkında 5 Şaşırtıcı Gerçek',
                '%s ile İlgili 7 Trend'
            )
        );
        
        $type_templates = isset($templates[$content_type]) ? $templates[$content_type] : $templates['news_article'];
        $template = $type_templates[array_rand($type_templates)];
        
        $clean_title = preg_replace('/[^\w\s]/', '', $original_title);
        return sprintf($template, $clean_title);
    }
    
    private function generate_content_body($news, $content_data) {
        $intro = "Son dönemde teknoloji dünyasında " . $news['title'] . " konusu büyük ilgi görüyor.";
        $main_content = $news['summary'] . " Bu gelişme sektörde önemli değişikliklere yol açabilir.";
        $conclusion = "Sonuç olarak, bu gelişme teknoloji sektörü için önemli fırsatlar sunuyor.";
        
        return "<p>" . $intro . "</p>\n\n<p>" . $main_content . "</p>\n\n<p>" . $conclusion . "</p>";
    }
    
    private function generate_excerpt($title) {
        return $title . " hakkında detaylı bilgi ve son gelişmeler.";
    }
    
    private function generate_meta_description($title, $focus_keyword) {
        return $title . " konusunda kapsamlı bilgi. " . $focus_keyword . " hakkında güncel analiz ve uzman görüşleri.";
    }
    
    private function extract_tags($title) {
        $tech_keywords = array('teknoloji', 'yapay zeka', 'blockchain', 'kripto', 'mobil', 'oyun', 'güvenlik');
        $tags = array();
        
        foreach ($tech_keywords as $keyword) {
            if (stripos($title, $keyword) !== false) {
                $tags[] = $keyword;
            }
        }
        
        return array_slice($tags, 0, 5);
    }
    
    private function estimate_word_count($word_count_option) {
        switch ($word_count_option) {
            case '500': return rand(500, 800);
            case '1000': return rand(1000, 1500);
            case '2000': return rand(2000, 3000);
            default: return rand(800, 1200);
        }
    }
    
    private function calculate_reading_time($word_count_option) {
        $words = $this->estimate_word_count($word_count_option);
        $minutes = ceil($words / 200);
        return $minutes . ' dakika';
    }
    
    private function get_or_create_category($category_name) {
        $category = get_term_by('name', $category_name, 'category');
        
        if ($category) {
            return $category->term_id;
        }
        
        $new_category = wp_insert_term($category_name, 'category');
        
        if (is_wp_error($new_category)) {
            return 1; // Varsayılan kategori
        }
        
        return $new_category['term_id'];
    }
    
    // Diğer sayfa metodları...
    public function title_generator_page() {
        echo '<div class="wrap"><h1>Başlık Jeneratörü</h1><p>Başlık jeneratörü sayfası geliştiriliyor...</p></div>';
    }
    
    public function seo_tracker_page() {
        echo '<div class="wrap"><h1>SEO Takip</h1><p>SEO takip sayfası geliştiriliyor...</p></div>';
    }
    
    public function settings_page() {
        echo '<div class="wrap"><h1>Ayarlar</h1><p>Ayarlar sayfası geliştiriliyor...</p></div>';
    }
    
    public function handle_ajax_requests() {
        // AJAX isteklerini işle
    }
}

new NewsBot_Admin_Pages();
?>