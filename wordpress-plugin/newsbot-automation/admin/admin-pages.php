<?php
/**
 * Admin sayfaları ve menü yapısı
 */

if (!defined('ABSPATH')) {
    exit;
}

class NewsBot_Admin_Pages {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_newsbot_api', array($this, 'handle_ajax_requests'));
        add_action('wp_ajax_newsbot_save_draft', array($this, 'save_content_as_draft'));
        add_action('wp_ajax_newsbot_auto_schedule', array($this, 'auto_schedule_content'));
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
            'Haber Analizi',
            'Haber Analizi',
            'manage_options',
            'newsbot-news-analysis',
            array($this, 'news_analysis_page')
        );
        
        add_submenu_page(
            'newsbot-dashboard',
            'İçerik Planlayıcı',
            'İçerik Planlayıcı',
            'manage_options',
            'newsbot-content-scheduler',
            array($this, 'content_scheduler_page')
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
            'İçerik Üretici',
            'İçerik Üretici',
            'manage_options',
            'newsbot-content-generator',
            array($this, 'content_generator_page')
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
            'nonce' => wp_create_nonce('newsbot_nonce')
        ));
    }
    
    public function dashboard_page() {
        ?>
        <div class="wrap newsbot-dashboard">
            <h1>NewsBot Automation Dashboard</h1>
            
            <!-- İstatistik Kartları -->
            <div class="newsbot-stats-grid">
                <div class="newsbot-stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3>Günlük Ziyaretçi</h3>
                        <p class="stat-number">2,847</p>
                        <span class="stat-change positive">+12%</span>
                    </div>
                </div>
                
                <div class="newsbot-stat-card">
                    <div class="stat-icon">🔍</div>
                    <div class="stat-content">
                        <h3>SEO Sıralaması</h3>
                        <p class="stat-number">15</p>
                        <span class="stat-change positive">+3</span>
                    </div>
                </div>
                
                <div class="newsbot-stat-card">
                    <div class="stat-icon">📝</div>
                    <div class="stat-content">
                        <h3>Planlanmış İçerik</h3>
                        <p class="stat-number">28</p>
                        <span class="stat-change neutral">0</span>
                    </div>
                </div>
                
                <div class="newsbot-stat-card">
                    <div class="stat-icon">📈</div>
                    <div class="stat-content">
                        <h3>Aylık Büyüme</h3>
                        <p class="stat-number">34%</p>
                        <span class="stat-change positive">+8%</span>
                    </div>
                </div>
            </div>
            
            <!-- Dashboard Grid -->
            <div class="newsbot-dashboard-grid">
                <!-- SEO Anahtar Kelimeler -->
                <div class="newsbot-card">
                    <h2>🔍 SEO Anahtar Kelimeler</h2>
                    <div class="keyword-list">
                        <div class="keyword-item">
                            <span class="keyword">yapay zeka</span>
                            <span class="position">3</span>
                            <span class="change positive">↑2</span>
                        </div>
                        <div class="keyword-item">
                            <span class="keyword">blockchain teknoloji</span>
                            <span class="position">7</span>
                            <span class="change negative">↓1</span>
                        </div>
                        <div class="keyword-item">
                            <span class="keyword">kripto para</span>
                            <span class="position">12</span>
                            <span class="change positive">↑5</span>
                        </div>
                        <div class="keyword-item">
                            <span class="keyword">metaverse nedir</span>
                            <span class="position">8</span>
                            <span class="change neutral">-</span>
                        </div>
                    </div>
                </div>
                
                <!-- En Popüler İçerikler -->
                <div class="newsbot-card">
                    <h2>📈 En Popüler İçerikler</h2>
                    <div class="popular-posts-list">
                        <div class="popular-post-item">
                            <a href="#" class="post-title">ChatGPT 4.0 Yenilikleri ve Özellikleri</a>
                            <span class="post-views">1,247 görüntüleme</span>
                        </div>
                        <div class="popular-post-item">
                            <a href="#" class="post-title">Blockchain Teknolojisi Geleceği</a>
                            <span class="post-views">892 görüntüleme</span>
                        </div>
                        <div class="popular-post-item">
                            <a href="#" class="post-title">Metaverse Yatırım Rehberi</a>
                            <span class="post-views">756 görüntüleme</span>
                        </div>
                        <div class="popular-post-item">
                            <a href="#" class="post-title">NFT Pazarı 2024 Trendleri</a>
                            <span class="post-views">634 görüntüleme</span>
                        </div>
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
                        <a href="<?php echo admin_url('admin.php?page=newsbot-content-scheduler'); ?>" class="action-button">
                            <div class="action-icon">📅</div>
                            <div class="action-text">İçerik Planla</div>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=newsbot-title-generator'); ?>" class="action-button">
                            <div class="action-icon">💡</div>
                            <div class="action-text">Başlık Üret</div>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=newsbot-seo-tracker'); ?>" class="action-button">
                            <div class="action-icon">🔍</div>
                            <div class="action-text">SEO Takip</div>
                        </a>
                    </div>
                </div>
                
                <!-- Site Durumu -->
                <div class="newsbot-card">
                    <h2>🌐 Web Site Durumu</h2>
                    <div class="site-status">
                        <div class="status-item">
                            <span class="status-label">Günlük Ziyaretçi</span>
                            <span class="status-value">2,847</span>
                        </div>
                        <div class="status-item">
                            <span class="status-label">Ortalama Kalış Süresi</span>
                            <span class="status-value">3:24</span>
                        </div>
                        <div class="status-item">
                            <span class="status-label">Çıkma Oranı</span>
                            <span class="status-value">42%</span>
                        </div>
                        <div class="status-item">
                            <span class="status-label">En Çok Aranan</span>
                            <span class="status-value">yapay zeka</span>
                        </div>
                        <div class="status-item">
                            <span class="status-label">Mobil Trafik</span>
                            <span class="status-value">68%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function news_analysis_page() {
        ?>
        <div class="wrap newsbot-news-analysis">
            <h1>📰 Haber Analizi ve İçerik Planlama</h1>
            
            <div class="newsbot-analysis-grid">
                <!-- Sol Panel: Kategorili Haberler -->
                <div class="newsbot-card">
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
                        </div>
                        
                        <!-- Haber Listesi -->
                        <div class="tab-content">
                            <div class="category-header">
                                <h3 id="current-category-title">Yapay Zeka Haberleri</h3>
                                <button class="button refresh-category-btn" id="refresh-category">
                                    <span>🔄</span> Yenile
                                </button>
                            </div>
                            
                            <!-- Tek Satır Haber Listesi -->
                            <div class="news-headlines-list" id="news-headlines">
                                <div class="loading">Haberler yükleniyor...</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sağ Panel: İçerik Planlama -->
                <div class="newsbot-card">
                    <h2>📅 Otomatik İçerik Planlama</h2>
                    
                    <!-- Seçili Haber -->
                    <div class="selected-news-panel" id="selected-news-panel" style="display: none;">
                        <div class="selected-news">
                            <h4>Seçili Haber:</h4>
                            <p id="selected-news-title">-</p>
                            <small id="selected-news-source">-</small>
                        </div>
                        
                        <!-- Planlama Seçenekleri -->
                        <div class="planning-options">
                            <div class="option-group">
                                <label>İçerik Türü:</label>
                                <select id="content-type">
                                    <option value="news_article">Haber Makalesi</option>
                                    <option value="analysis">Detaylı Analiz</option>
                                    <option value="tutorial">Rehber İçerik</option>
                                    <option value="review">İnceleme</option>
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
                                <label>Yayın Stratejisi:</label>
                                <select id="publish-strategy">
                                    <option value="immediate">Hemen Yayınla</option>
                                    <option value="next_slot">Sonraki Boş Slota</option>
                                    <option value="peak_time">En İyi Saatte</option>
                                    <option value="custom">Özel Tarih</option>
                                </select>
                            </div>
                            
                            <div class="option-group" id="custom-date-group" style="display: none;">
                                <label>Özel Tarih:</label>
                                <input type="date" id="custom-date" min="<?php echo date('Y-m-d'); ?>">
                                <input type="time" id="custom-time" value="09:00">
                            </div>
                        </div>
                        
                        <!-- Planlama Butonu -->
                        <div class="planning-actions">
                            <button class="button button-primary" id="auto-schedule-btn">
                                📅 Otomatik Planla
                            </button>
                            <button class="button" id="preview-content-btn">
                                👁️ İçerik Önizle
                            </button>
                        </div>
                    </div>
                    
                    <!-- Planlama Takvimi -->
                    <div class="planning-calendar">
                        <h4>📅 Bu Haftanın Planı (Günlük 4 İçerik)</h4>
                        <div class="calendar-grid" id="planning-calendar">
                            <!-- Takvim JavaScript ile doldurulacak -->
                        </div>
                    </div>
                    
                    <!-- Planlanmış İçerikler -->
                    <div class="scheduled-content-preview">
                        <h4>📋 Planlanmış İçerikler</h4>
                        <div class="scheduled-list" id="scheduled-content-list">
                            <div class="loading">Planlanmış içerikler yükleniyor...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            let selectedNews = null;
            let currentCategory = 'yapay_zeka';
            
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
                $('#news-headlines').html('<div class="loading">Haberler yükleniyor...</div>');
                
                $.post(ajaxurl, {
                    action: 'newsbot_get_categorized_news',
                    category: category,
                    limit: 20,
                    nonce: newsbot_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        displayNewsHeadlines(response.data);
                    }
                });
            }
            
            // Haberleri tek satır halinde göster
            function displayNewsHeadlines(news) {
                let html = '';
                news.forEach(function(item, index) {
                    html += `
                        <div class="news-headline-item" data-news='${JSON.stringify(item)}'>
                            <div class="headline-content">
                                <span class="headline-number">${index + 1}.</span>
                                <span class="headline-title">${item.title}</span>
                                <span class="headline-source">(${item.source})</span>
                                <span class="headline-time">${item.reading_time}</span>
                            </div>
                            <button class="select-news-btn button button-small">Seç</button>
                        </div>
                    `;
                });
                $('#news-headlines').html(html);
            }
            
            // Haber seçme
            $(document).on('click', '.select-news-btn', function() {
                const newsData = $(this).closest('.news-headline-item').data('news');
                selectedNews = newsData;
                
                // Seçili haberi göster
                $('#selected-news-title').text(newsData.title);
                $('#selected-news-source').text(newsData.source + ' - ' + newsData.published_at);
                $('#selected-news-panel').show();
                
                // Diğer seçimleri kaldır
                $('.news-headline-item').removeClass('selected');
                $(this).closest('.news-headline-item').addClass('selected');
            });
            
            // Yayın stratejisi değişikliği
            $('#publish-strategy').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('#custom-date-group').show();
                } else {
                    $('#custom-date-group').hide();
                }
            });
            
            // Otomatik planlama
            $('#auto-schedule-btn').on('click', function() {
                if (!selectedNews) {
                    alert('Lütfen bir haber seçin.');
                    return;
                }
                
                const planningData = {
                    news: selectedNews,
                    content_type: $('#content-type').val(),
                    word_count: $('#word-count').val(),
                    strategy: $('#publish-strategy').val(),
                    custom_date: $('#custom-date').val(),
                    custom_time: $('#custom-time').val()
                };
                
                const $btn = $(this);
                const originalText = $btn.text();
                $btn.prop('disabled', true).text('📅 Planlanıyor...');
                
                $.post(ajaxurl, {
                    action: 'newsbot_auto_schedule',
                    planning_data: planningData,
                    nonce: newsbot_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        alert('İçerik başarıyla planlandı!');
                        loadPlanningCalendar();
                        loadScheduledContent();
                        $('#selected-news-panel').hide();
                        $('.news-headline-item').removeClass('selected');
                        selectedNews = null;
                    } else {
                        alert('Planlama hatası: ' + response.data);
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
                                <div class="time-slot">09:00 - Slot 1</div>
                                <div class="time-slot">12:00 - Slot 2</div>
                                <div class="time-slot">15:00 - Slot 3</div>
                                <div class="time-slot">18:00 - Slot 4</div>
                            </div>
                        </div>
                    `;
                }
                
                $('#planning-calendar').html(html);
            }
            
            // Planlanmış içerikleri yükle
            function loadScheduledContent() {
                $.post(ajaxurl, {
                    action: 'newsbot_get_scheduled_posts',
                    nonce: newsbot_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        displayScheduledContent(response.data);
                    }
                });
            }
            
            // Planlanmış içerikleri göster
            function displayScheduledContent(posts) {
                let html = '';
                posts.slice(0, 5).forEach(function(post) {
                    html += `
                        <div class="scheduled-item">
                            <div class="scheduled-title">${post.title}</div>
                            <div class="scheduled-meta">
                                <span class="scheduled-date">${post.publish_date}</span>
                                <span class="scheduled-category">${post.category}</span>
                            </div>
                        </div>
                    `;
                });
                
                if (html === '') {
                    html = '<div class="no-content">Henüz planlanmış içerik yok.</div>';
                }
                
                $('#scheduled-content-list').html(html);
            }
            
            // Sayfa yüklendiğinde
            loadCategoryNews(currentCategory);
            loadPlanningCalendar();
            loadScheduledContent();
        });
        </script>
        <?php
    }
    
    public function auto_schedule_content() {
        check_ajax_referer('newsbot_nonce', 'nonce');
        
        $planning_data = $_POST['planning_data'];
        $news = $planning_data['news'];
        $strategy = $planning_data['strategy'];
        
        // İçerik oluştur
        $content_data = array(
            'title' => $this->generate_content_title($news['title']),
            'content' => $this->generate_content_body($news, $planning_data['content_type'], $planning_data['word_count']),
            'excerpt' => $this->generate_excerpt($news['title']),
            'category' => $news['category'],
            'tags' => $this->extract_tags($news['title']),
            'featured_image' => $news['image']
        );
        
        // Yayın tarihini belirle
        $publish_date = $this->calculate_publish_date($strategy, $planning_data);
        
        // WordPress'e planlanmış yazı olarak ekle
        $post_data = array(
            'post_title' => $content_data['title'],
            'post_content' => $content_data['content'],
            'post_excerpt' => $content_data['excerpt'],
            'post_status' => 'future',
            'post_date' => $publish_date,
            'post_date_gmt' => get_gmt_from_date($publish_date),
            'post_type' => 'post',
            'post_author' => get_current_user_id()
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            wp_send_json_error('İçerik planlanamadı: ' . $post_id->get_error_message());
        }
        
        // Meta bilgileri kaydet
        update_post_meta($post_id, 'newsbot_auto_generated', true);
        update_post_meta($post_id, 'newsbot_source_news', $news);
        update_post_meta($post_id, 'newsbot_planning_data', $planning_data);
        
        // Kategori ve etiketleri ata
        if (!empty($content_data['category'])) {
            $cat_id = $this->get_or_create_category($content_data['category']);
            wp_set_post_categories($post_id, array($cat_id));
        }
        
        if (!empty($content_data['tags'])) {
            wp_set_post_tags($post_id, $content_data['tags']);
        }
        
        wp_send_json_success(array(
            'post_id' => $post_id,
            'publish_date' => $publish_date,
            'edit_url' => admin_url('post.php?post=' . $post_id . '&action=edit')
        ));
    }
    
    private function calculate_publish_date($strategy, $planning_data) {
        $now = current_time('mysql');
        
        switch ($strategy) {
            case 'immediate':
                return $now;
                
            case 'next_slot':
                return $this->get_next_available_slot();
                
            case 'peak_time':
                return $this->get_next_peak_time();
                
            case 'custom':
                $date = $planning_data['custom_date'];
                $time = $planning_data['custom_time'];
                return $date . ' ' . $time . ':00';
                
            default:
                return $this->get_next_available_slot();
        }
    }
    
    private function get_next_available_slot() {
        // Günlük 4 slot: 09:00, 12:00, 15:00, 18:00
        $slots = array('09:00:00', '12:00:00', '15:00:00', '18:00:00');
        $today = current_time('Y-m-d');
        $current_time = current_time('H:i:s');
        
        // Bugün için uygun slot var mı kontrol et
        foreach ($slots as $slot) {
            $slot_datetime = $today . ' ' . $slot;
            if ($slot > $current_time && !$this->is_slot_occupied($slot_datetime)) {
                return $slot_datetime;
            }
        }
        
        // Bugün uygun slot yoksa yarından başla
        $date = new DateTime($today);
        $date->add(new DateInterval('P1D'));
        
        for ($i = 0; $i < 30; $i++) { // 30 gün ileriye kadar kontrol et
            $check_date = $date->format('Y-m-d');
            
            foreach ($slots as $slot) {
                $slot_datetime = $check_date . ' ' . $slot;
                if (!$this->is_slot_occupied($slot_datetime)) {
                    return $slot_datetime;
                }
            }
            
            $date->add(new DateInterval('P1D'));
        }
        
        // Hiç boş slot bulunamazsa 1 saat sonra
        return date('Y-m-d H:i:s', strtotime('+1 hour'));
    }
    
    private function get_next_peak_time() {
        // En iyi saatler: 09:00, 15:00, 18:00
        $peak_slots = array('09:00:00', '15:00:00', '18:00:00');
        $today = current_time('Y-m-d');
        $current_time = current_time('H:i:s');
        
        foreach ($peak_slots as $slot) {
            $slot_datetime = $today . ' ' . $slot;
            if ($slot > $current_time && !$this->is_slot_occupied($slot_datetime)) {
                return $slot_datetime;
            }
        }
        
        // Bugün uygun peak time yoksa yarın 09:00
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        return $tomorrow . ' 09:00:00';
    }
    
    private function is_slot_occupied($datetime) {
        $posts = get_posts(array(
            'post_status' => 'future',
            'meta_key' => 'newsbot_auto_generated',
            'meta_value' => true,
            'date_query' => array(
                array(
                    'year' => date('Y', strtotime($datetime)),
                    'month' => date('m', strtotime($datetime)),
                    'day' => date('d', strtotime($datetime)),
                    'hour' => date('H', strtotime($datetime)),
                )
            )
        ));
        
        return !empty($posts);
    }
    
    private function generate_content_title($original_title) {
        $title_variations = array(
            '%s: Detaylı Analiz ve Değerlendirme',
            '%s Hakkında Bilmeniz Gerekenler',
            '%s ile İlgili Son Gelişmeler',
            '%s: Kapsamlı Rehber ve İncelemeler',
            '%s Konusunda Uzman Görüşleri'
        );
        
        $template = $title_variations[array_rand($title_variations)];
        $clean_title = preg_replace('/[^\w\s]/', '', $original_title);
        
        return sprintf($template, $clean_title);
    }
    
    private function generate_content_body($news, $content_type, $word_count) {
        $intro = "Son dönemde teknoloji dünyasında " . $news['title'] . " konusu büyük ilgi görüyor. Bu gelişme, sektörde önemli değişikliklere yol açabilir.";
        
        $main_content = "Bu konuyla ilgili detaylı analiz ve uzman görüşlerini sizler için derledik. " . $news['summary'];
        
        $conclusion = "Sonuç olarak, bu gelişme teknoloji sektörü için önemli fırsatlar sunuyor. Konuyla ilgili gelişmeleri takip etmeye devam edeceğiz.";
        
        return "<p>" . $intro . "</p>\n\n<p>" . $main_content . "</p>\n\n<p>" . $conclusion . "</p>";
    }
    
    private function generate_excerpt($title) {
        return $title . " hakkında detaylı bilgi ve son gelişmeler. Uzman analizleri ve değerlendirmeler.";
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
    public function content_scheduler_page() {
        echo '<div class="wrap"><h1>İçerik Planlayıcı</h1><p>İçerik planlama sayfası geliştiriliyor...</p></div>';
    }
    
    public function title_generator_page() {
        echo '<div class="wrap"><h1>Başlık Jeneratörü</h1><p>Başlık jeneratörü sayfası geliştiriliyor...</p></div>';
    }
    
    public function seo_tracker_page() {
        echo '<div class="wrap"><h1>SEO Takip</h1><p>SEO takip sayfası geliştiriliyor...</p></div>';
    }
    
    public function content_generator_page() {
        echo '<div class="wrap"><h1>İçerik Üretici</h1><p>İçerik üretici sayfası geliştiriliyor...</p></div>';
    }
    
    public function settings_page() {
        echo '<div class="wrap"><h1>Ayarlar</h1><p>Ayarlar sayfası geliştiriliyor...</p></div>';
    }
    
    public function handle_ajax_requests() {
        // AJAX isteklerini işle
    }
    
    public function save_content_as_draft() {
        // İçeriği taslak olarak kaydet
    }
}

new NewsBot_Admin_Pages();
?>