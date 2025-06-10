<?php
/**
 * Plugin Name: NewsBot Automation
 * Plugin URI: https://yoursite.com
 * Description: WordPress Teknoloji Haberi Otomasyonu Yönetim Paneli
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: newsbot-automation
 */

// Güvenlik kontrolü
if (!defined('ABSPATH')) {
    exit;
}

// Plugin sabitleri
define('NEWSBOT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NEWSBOT_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('NEWSBOT_VERSION', '1.0.0');

// Sınıfları dahil et
require_once NEWSBOT_PLUGIN_PATH . 'includes/class-analytics.php';
require_once NEWSBOT_PLUGIN_PATH . 'includes/class-seo-tracker.php';
require_once NEWSBOT_PLUGIN_PATH . 'includes/class-news-analyzer.php';
require_once NEWSBOT_PLUGIN_PATH . 'includes/class-content-generator.php';
require_once NEWSBOT_PLUGIN_PATH . 'includes/class-content-scheduler.php';
require_once NEWSBOT_PLUGIN_PATH . 'includes/class-title-generator.php';
require_once NEWSBOT_PLUGIN_PATH . 'includes/class-news-categorizer.php';

class NewsBotAutomation {
    
    private $analytics;
    private $seo_tracker;
    private $news_analyzer;
    private $content_generator;
    private $content_scheduler;
    private $title_generator;
    private $news_categorizer;
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_newsbot_api', array($this, 'handle_ajax_request'));
        add_action('wp_ajax_nopriv_newsbot_api', array($this, 'handle_ajax_request'));
        
        // Hata ayıklama için
        add_action('admin_notices', array($this, 'admin_notices'));
        
        // Sınıfları başlat
        $this->analytics = new NewsBot_Analytics();
        $this->seo_tracker = new NewsBot_SEO_Tracker();
        $this->news_analyzer = new NewsBot_News_Analyzer();
        $this->content_generator = new NewsBot_Content_Generator();
        $this->content_scheduler = new NewsBot_Content_Scheduler();
        $this->title_generator = new NewsBot_Title_Generator();
        $this->news_categorizer = new NewsBot_News_Categorizer();
    }
    
    public function init() {
        // Plugin başlatma işlemleri
        load_plugin_textdomain('newsbot-automation', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Günlük görevleri zamanla
        $this->schedule_daily_tasks();
    }
    
    public function admin_notices() {
        // Hata mesajları göster
        if (isset($_GET['newsbot_error'])) {
            echo '<div class="notice notice-error"><p>NewsBot Plugin Hatası: ' . esc_html($_GET['newsbot_error']) . '</p></div>';
        }
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'NewsBot Automation',
            'NewsBot',
            'manage_options',
            'newsbot-automation',
            array($this, 'admin_page'),
            'dashicons-rss',
            30
        );
        
        // Alt menüler
        add_submenu_page(
            'newsbot-automation',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'newsbot-automation',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'newsbot-automation',
            'Haber Analizi',
            'Haber Analizi',
            'manage_options',
            'newsbot-news-analysis',
            array($this, 'news_analysis_page')
        );
        
        add_submenu_page(
            'newsbot-automation',
            'İçerik Üretici',
            'İçerik Üretici',
            'manage_options',
            'newsbot-content-generator',
            array($this, 'content_generator_page')
        );
        
        add_submenu_page(
            'newsbot-automation',
            'İçerik Planlayıcı',
            'İçerik Planlayıcı',
            'manage_options',
            'newsbot-content-scheduler',
            array($this, 'content_scheduler_page')
        );
        
        add_submenu_page(
            'newsbot-automation',
            'Başlık Jeneratörü',
            'Başlık Jeneratörü',
            'manage_options',
            'newsbot-title-generator',
            array($this, 'title_generator_page')
        );
        
        add_submenu_page(
            'newsbot-automation',
            'SEO Takibi',
            'SEO Takibi',
            'manage_options',
            'newsbot-seo-tracking',
            array($this, 'seo_tracking_page')
        );
        
        add_submenu_page(
            'newsbot-automation',
            'Ayarlar',
            'Ayarlar',
            'manage_options',
            'newsbot-settings',
            array($this, 'settings_page')
        );
    }
    
    public function enqueue_scripts($hook) {
        // Sadece plugin sayfalarında script yükle
        if (strpos($hook, 'newsbot') === false) {
            return;
        }
        
        try {
            // WordPress admin stilleri
            wp_enqueue_style('newsbot-admin', NEWSBOT_PLUGIN_URL . 'assets/admin-style.css', array(), NEWSBOT_VERSION);
            wp_enqueue_script('newsbot-admin', NEWSBOT_PLUGIN_URL . 'assets/admin-script.js', array('jquery'), NEWSBOT_VERSION, true);
            
            // WordPress AJAX için nonce
            wp_localize_script('newsbot-admin', 'newsbot_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('newsbot_nonce'),
                'plugin_url' => NEWSBOT_PLUGIN_URL
            ));
            
        } catch (Exception $e) {
            error_log('NewsBot Plugin Script Error: ' . $e->getMessage());
        }
    }
    
    public function admin_page() {
        $daily_visitors = $this->get_daily_visitors();
        $keywords = get_option('newsbot_keywords', array());
        $popular_posts = $this->get_popular_pages();
        $seo_summary = $this->get_seo_summary();
        ?>
        <div class="wrap newsbot-dashboard">
            <h1>📊 NewsBot Dashboard</h1>
            
            <div class="newsbot-stats-grid">
                <div class="newsbot-stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <h3>Günlük Ziyaretçi</h3>
                        <p class="stat-number"><?php echo number_format($daily_visitors); ?></p>
                        <span class="stat-change positive">+12%</span>
                    </div>
                </div>
                
                <div class="newsbot-stat-card">
                    <div class="stat-icon">🔍</div>
                    <div class="stat-content">
                        <h3>Takip Edilen Kelimeler</h3>
                        <p class="stat-number"><?php echo count($keywords); ?></p>
                        <span class="stat-change neutral">Aktif</span>
                    </div>
                </div>
                
                <div class="newsbot-stat-card">
                    <div class="stat-icon">📈</div>
                    <div class="stat-content">
                        <h3>Ortalama SEO Skoru</h3>
                        <p class="stat-number"><?php echo $seo_summary['average_score']; ?></p>
                        <span class="stat-change <?php echo $seo_summary['trend']; ?>"><?php echo $seo_summary['change']; ?></span>
                    </div>
                </div>
                
                <div class="newsbot-stat-card">
                    <div class="stat-icon">📄</div>
                    <div class="stat-content">
                        <h3>Toplam İçerik</h3>
                        <p class="stat-number"><?php echo wp_count_posts()->publish; ?></p>
                        <span class="stat-change positive">Yayında</span>
                    </div>
                </div>
            </div>
            
            <div class="newsbot-dashboard-grid">
                <div class="newsbot-card">
                    <h2>🔍 SEO Anahtar Kelime Durumu</h2>
                    <?php if (empty($keywords)): ?>
                        <p>Henüz anahtar kelime eklenmemiş. <a href="<?php echo admin_url('admin.php?page=newsbot-settings'); ?>" class="button">Kelime Ekle</a></p>
                    <?php else: ?>
                        <div class="keyword-list">
                            <?php foreach (array_slice($keywords, 0, 5) as $keyword): ?>
                                <?php 
                                $position = rand(1, 50);
                                $change = rand(-5, 5);
                                $trend_class = $change > 0 ? 'positive' : ($change < 0 ? 'negative' : 'neutral');
                                ?>
                                <div class="keyword-item">
                                    <span class="keyword"><?php echo esc_html($keyword); ?></span>
                                    <span class="position">#<?php echo $position; ?></span>
                                    <span class="change <?php echo $trend_class; ?>">
                                        <?php echo $change > 0 ? '+' : ''; ?><?php echo $change; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <a href="<?php echo admin_url('admin.php?page=newsbot-seo-tracking'); ?>" class="button">Tümünü Görüntüle</a>
                    <?php endif; ?>
                </div>
                
                <div class="newsbot-card">
                    <h2>📈 Popüler İçerikler</h2>
                    <?php if (empty($popular_posts)): ?>
                        <p>Henüz popüler içerik verisi yok.</p>
                    <?php else: ?>
                        <div class="popular-posts-list">
                            <?php foreach (array_slice($popular_posts, 0, 5) as $post): ?>
                                <div class="popular-post-item">
                                    <a href="<?php echo esc_url($post['url']); ?>" target="_blank" class="post-title">
                                        <?php echo esc_html(wp_trim_words($post['title'], 8)); ?>
                                    </a>
                                    <span class="post-views"><?php echo number_format($post['views']); ?> görüntüleme</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="newsbot-card">
                    <h2>🚀 Hızlı İşlemler</h2>
                    <div class="quick-actions">
                        <a href="<?php echo admin_url('admin.php?page=newsbot-news-analysis'); ?>" class="action-button">
                            <span class="action-icon">📰</span>
                            <span class="action-text">Haber Analizi</span>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=newsbot-content-generator'); ?>" class="action-button">
                            <span class="action-icon">✍️</span>
                            <span class="action-text">İçerik Üret</span>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=newsbot-content-scheduler'); ?>" class="action-button">
                            <span class="action-icon">📅</span>
                            <span class="action-text">İçerik Planla</span>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=newsbot-title-generator'); ?>" class="action-button">
                            <span class="action-icon">💡</span>
                            <span class="action-text">Başlık Üret</span>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=newsbot-seo-tracking'); ?>" class="action-button">
                            <span class="action-icon">📊</span>
                            <span class="action-text">SEO Takip</span>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=newsbot-settings'); ?>" class="action-button">
                            <span class="action-icon">⚙️</span>
                            <span class="action-text">Ayarlar</span>
                        </a>
                    </div>
                </div>
                
                <div class="newsbot-card">
                    <h2>📊 Site Durumu</h2>
                    <div class="site-status">
                        <div class="status-item">
                            <span class="status-label">Bugün Gelen Ziyaretçi:</span>
                            <span class="status-value"><?php echo number_format($daily_visitors); ?></span>
                        </div>
                        <div class="status-item">
                            <span class="status-label">Ortalama Kalış Süresi:</span>
                            <span class="status-value"><?php echo rand(2, 8); ?> dakika</span>
                        </div>
                        <div class="status-item">
                            <span class="status-label">Çıkma Oranı:</span>
                            <span class="status-value"><?php echo rand(30, 70); ?>%</span>
                        </div>
                        <div class="status-item">
                            <span class="status-label">En Çok Aranan:</span>
                            <span class="status-value">"<?php echo !empty($keywords) ? $keywords[0] : 'teknoloji'; ?>"</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function news_analysis_page() {
        $categories = $this->news_categorizer->get_all_categories();
        ?>
        <div class="wrap newsbot-news-analysis">
            <h1>📰 Haber Analizi</h1>
            
            <!-- Kategori Sekmeleri -->
            <div class="newsbot-category-tabs">
                <div class="tab-navigation">
                    <?php foreach ($categories as $key => $category): ?>
                        <button class="tab-button <?php echo $key === 'yapay_zeka' ? 'active' : ''; ?>" 
                                data-category="<?php echo $key; ?>"
                                style="border-left: 3px solid <?php echo $category['color']; ?>">
                            <span class="tab-icon"><?php echo $category['icon']; ?></span>
                            <span class="tab-name"><?php echo $category['name']; ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
                
                <div class="tab-content">
                    <?php foreach ($categories as $key => $category): ?>
                        <div class="tab-panel <?php echo $key === 'yapay_zeka' ? 'active' : ''; ?>" 
                             id="tab-<?php echo $key; ?>">
                            <div class="category-header">
                                <h2><?php echo $category['icon']; ?> <?php echo $category['name']; ?> Haberleri</h2>
                                <button class="refresh-category-btn button" data-category="<?php echo $key; ?>">
                                    🔄 Yenile
                                </button>
                            </div>
                            
                            <div class="news-grid" id="news-grid-<?php echo $key; ?>">
                                <div class="loading">Haberler yükleniyor...</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- İçerik Üretim Paneli -->
            <div class="newsbot-card content-generation-panel" style="display: none;">
                <h2>✍️ Seçilen Haber İçin İçerik Üret</h2>
                <div id="selected-news-info"></div>
                
                <div class="generation-options">
                    <label for="content-angle">İçerik Açısı:</label>
                    <select id="content-angle">
                        <option value="news_summary">Haber Özeti</option>
                        <option value="detailed_analysis">Detaylı Analiz</option>
                        <option value="opinion_piece">Görüş Yazısı</option>
                        <option value="tutorial">Nasıl Yapılır Rehberi</option>
                        <option value="comparison">Karşılaştırma</option>
                    </select>
                    
                    <label for="target-audience">Hedef Kitle:</label>
                    <select id="target-audience">
                        <option value="general">Genel Okuyucu</option>
                        <option value="technical">Teknik Kitle</option>
                        <option value="business">İş Dünyası</option>
                        <option value="beginner">Başlangıç Seviyesi</option>
                    </select>
                    
                    <button id="generate-from-news" class="button button-primary">🚀 İçerik Üret</button>
                </div>
                
                <div id="generated-news-content" style="display: none;">
                    <h3>Üretilen İçerik:</h3>
                    <div id="news-content-preview"></div>
                    <div class="content-actions">
                        <button id="schedule-news-content" class="button">📅 Planla</button>
                        <button id="save-news-draft" class="button button-primary">💾 Taslak Kaydet</button>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Kategori sekmelerini yönet
            $('.tab-button').click(function() {
                const category = $(this).data('category');
                
                $('.tab-button').removeClass('active');
                $('.tab-panel').removeClass('active');
                
                $(this).addClass('active');
                $('#tab-' + category).addClass('active');
                
                loadCategoryNews(category);
            });
            
            // Kategori haberlerini yükle
            function loadCategoryNews(category) {
                const $grid = $('#news-grid-' + category);
                $grid.html('<div class="loading">Haberler yükleniyor...</div>');
                
                $.post(ajaxurl, {
                    action: 'newsbot_get_categorized_news',
                    category: category,
                    limit: 12,
                    nonce: newsbot_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        displayCategoryNews(response.data, category);
                    } else {
                        $grid.html('<div class="error">Haberler yüklenemedi.</div>');
                    }
                });
            }
            
            // Haberleri görüntüle
            function displayCategoryNews(news, category) {
                const $grid = $('#news-grid-' + category);
                let html = '';
                
                news.forEach(function(item) {
                    html += `
                        <div class="news-item" data-news-id="${item.id}">
                            <div class="news-image">
                                <img src="${item.image}" alt="${item.title}" loading="lazy">
                                <span class="reading-time">${item.reading_time}</span>
                            </div>
                            <div class="news-content">
                                <h3 class="news-title">${item.title}</h3>
                                <p class="news-summary">${item.summary}</p>
                                <div class="news-meta">
                                    <span class="news-source">${item.source}</span>
                                    <span class="news-time">${formatTimeAgo(item.published_at)}</span>
                                    <span class="engagement-score">📊 ${item.engagement_score}</span>
                                </div>
                                <div class="news-actions">
                                    <button class="select-news-btn button button-small">Seç</button>
                                    <a href="${item.url}" target="_blank" class="button button-small">Oku</a>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                $grid.html(html);
            }
            
            // Haber seçme
            $(document).on('click', '.select-news-btn', function() {
                const $newsItem = $(this).closest('.news-item');
                const newsId = $newsItem.data('news-id');
                const title = $newsItem.find('.news-title').text();
                const summary = $newsItem.find('.news-summary').text();
                
                $('.news-item').removeClass('selected');
                $newsItem.addClass('selected');
                
                $('#selected-news-info').html(`
                    <div class="selected-news">
                        <h4>Seçilen Haber:</h4>
                        <p><strong>${title}</strong></p>
                        <p>${summary}</p>
                    </div>
                `);
                
                $('.content-generation-panel').show();
                window.selectedNews = { id: newsId, title: title, summary: summary };
            });
            
            // Kategori yenileme
            $('.refresh-category-btn').click(function() {
                const category = $(this).data('category');
                
                $.post(ajaxurl, {
                    action: 'newsbot_refresh_category',
                    category: category,
                    nonce: newsbot_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        displayCategoryNews(response.data, category);
                        showNotification('Kategori haberleri yenilendi!', 'success');
                    }
                });
            });
            
            // İlk kategoriyi yükle
            loadCategoryNews('yapay_zeka');
            
            // Zaman formatı
            function formatTimeAgo(dateString) {
                const date = new Date(dateString);
                const now = new Date();
                const diffInHours = Math.floor((now - date) / (1000 * 60 * 60));
                
                if (diffInHours < 1) return 'Az önce';
                if (diffInHours < 24) return diffInHours + ' saat önce';
                return Math.floor(diffInHours / 24) + ' gün önce';
            }
            
            // Bildirim göster
            function showNotification(message, type) {
                const notificationClass = type === 'success' ? 'notice-success' : 'notice-error';
                const $notification = $(`
                    <div class="notice ${notificationClass} is-dismissible">
                        <p>${message}</p>
                    </div>
                `);
                
                $('.wrap').prepend($notification);
                
                setTimeout(function() {
                    $notification.fadeOut();
                }, 3000);
            }
        });
        </script>
        <?php
    }
    
    public function content_scheduler_page() {
        ?>
        <div class="wrap newsbot-content-scheduler">
            <h1>📅 İçerik Planlayıcı</h1>
            
            <div class="newsbot-scheduler-grid">
                <div class="newsbot-card">
                    <h2>📝 Yeni İçerik Planla</h2>
                    <form id="content-schedule-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row">Başlık</th>
                                <td>
                                    <input type="text" id="schedule-title" class="regular-text" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">İçerik</th>
                                <td>
                                    <textarea id="schedule-content" rows="8" cols="50" required></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Özet</th>
                                <td>
                                    <textarea id="schedule-excerpt" rows="3" cols="50"></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Yayın Tarihi</th>
                                <td>
                                    <input type="date" id="schedule-date" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Yayın Saati</th>
                                <td>
                                    <input type="time" id="schedule-time" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Kategori</th>
                                <td>
                                    <select id="schedule-category">
                                        <option value="">Kategori Seçin</option>
                                        <option value="Teknoloji">Teknoloji</option>
                                        <option value="Yapay Zeka">Yapay Zeka</option>
                                        <option value="Blockchain">Blockchain</option>
                                        <option value="Mobil">Mobil</option>
                                        <option value="Oyun">Oyun</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Etiketler</th>
                                <td>
                                    <input type="text" id="schedule-tags" class="regular-text" placeholder="Virgülle ayırın">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Öne Çıkan Görsel URL</th>
                                <td>
                                    <input type="url" id="schedule-image" class="regular-text">
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary">📅 İçeriği Planla</button>
                        </p>
                    </form>
                </div>
                
                <div class="newsbot-card">
                    <h2>📋 Planlanmış İçerikler</h2>
                    <div class="scheduled-content-controls">
                        <button id="refresh-scheduled" class="button">🔄 Yenile</button>
                        <select id="schedule-filter">
                            <option value="all">Tümü</option>
                            <option value="today">Bugün</option>
                            <option value="week">Bu Hafta</option>
                            <option value="month">Bu Ay</option>
                        </select>
                    </div>
                    
                    <div id="scheduled-posts-list">
                        <div class="loading">Planlanmış içerikler yükleniyor...</div>
                    </div>
                </div>
            </div>
            
            <!-- Toplu Planlama Paneli -->
            <div class="newsbot-card">
                <h2>📊 Toplu İçerik Planlama</h2>
                <div class="bulk-schedule-options">
                    <div class="bulk-option">
                        <label for="bulk-start-date">Başlangıç Tarihi:</label>
                        <input type="date" id="bulk-start-date">
                    </div>
                    <div class="bulk-option">
                        <label for="bulk-interval">Yayın Aralığı:</label>
                        <select id="bulk-interval">
                            <option value="24">Her Gün</option>
                            <option value="48">2 Günde Bir</option>
                            <option value="72">3 Günde Bir</option>
                            <option value="168">Haftalık</option>
                        </select>
                    </div>
                    <div class="bulk-option">
                        <label for="bulk-time">Yayın Saati:</label>
                        <input type="time" id="bulk-time" value="09:00">
                    </div>
                    <button id="bulk-schedule-btn" class="button">📅 Toplu Planla</button>
                </div>
                
                <div id="bulk-content-queue" style="display: none;">
                    <h3>Planlama Kuyruğu:</h3>
                    <div id="queue-items"></div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // İçerik planlama formu
            $('#content-schedule-form').submit(function(e) {
                e.preventDefault();
                
                const formData = {
                    action: 'newsbot_schedule_content',
                    title: $('#schedule-title').val(),
                    content: $('#schedule-content').val(),
                    excerpt: $('#schedule-excerpt').val(),
                    publish_date: $('#schedule-date').val(),
                    publish_time: $('#schedule-time').val(),
                    category: $('#schedule-category').val(),
                    tags: $('#schedule-tags').val().split(',').map(tag => tag.trim()),
                    featured_image: $('#schedule-image').val(),
                    nonce: newsbot_ajax.nonce
                };
                
                $('button[type="submit"]').prop('disabled', true).text('Planlanıyor...');
                
                $.post(ajaxurl, formData, function(response) {
                    if (response.success) {
                        showNotification('İçerik başarıyla planlandı!', 'success');
                        $('#content-schedule-form')[0].reset();
                        loadScheduledPosts();
                    } else {
                        showNotification('Planlama başarısız: ' + response.data, 'error');
                    }
                    $('button[type="submit"]').prop('disabled', false).text('📅 İçeriği Planla');
                });
            });
            
            // Planlanmış içerikleri yükle
            function loadScheduledPosts() {
                $('#scheduled-posts-list').html('<div class="loading">Yükleniyor...</div>');
                
                $.post(ajaxurl, {
                    action: 'newsbot_get_scheduled_posts',
                    nonce: newsbot_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        displayScheduledPosts(response.data);
                    } else {
                        $('#scheduled-posts-list').html('<div class="error">Yüklenemedi.</div>');
                    }
                });
            }
            
            // Planlanmış içerikleri görüntüle
            function displayScheduledPosts(posts) {
                let html = '';
                
                if (posts.length === 0) {
                    html = '<p>Henüz planlanmış içerik yok.</p>';
                } else {
                    posts.forEach(function(post) {
                        html += `
                            <div class="scheduled-post-item" data-post-id="${post.id}">
                                <div class="post-info">
                                    <h4>${post.title}</h4>
                                    <div class="post-meta">
                                        <span class="publish-date">📅 ${post.publish_date}</span>
                                        <span class="category">📂 ${post.category}</span>
                                        <span class="status">⏰ ${post.status}</span>
                                    </div>
                                </div>
                                <div class="post-actions">
                                    <a href="${post.edit_url}" target="_blank" class="button button-small">✏️ Düzenle</a>
                                    <button class="reschedule-btn button button-small" data-post-id="${post.id}">📅 Yeniden Planla</button>
                                    <button class="delete-scheduled-btn button button-small" data-post-id="${post.id}">🗑️ Sil</button>
                                </div>
                            </div>
                        `;
                    });
                }
                
                $('#scheduled-posts-list').html(html);
            }
            
            // Yeniden planlama
            $(document).on('click', '.reschedule-btn', function() {
                const postId = $(this).data('post-id');
                const newDate = prompt('Yeni tarih (YYYY-MM-DD):');
                const newTime = prompt('Yeni saat (HH:MM):');
                
                if (newDate && newTime) {
                    $.post(ajaxurl, {
                        action: 'newsbot_update_schedule',
                        post_id: postId,
                        new_date: newDate,
                        new_time: newTime,
                        nonce: newsbot_ajax.nonce
                    }, function(response) {
                        if (response.success) {
                            showNotification('Planlama güncellendi!', 'success');
                            loadScheduledPosts();
                        } else {
                            showNotification('Güncelleme başarısız: ' + response.data, 'error');
                        }
                    });
                }
            });
            
            // Planlanmış içerik silme
            $(document).on('click', '.delete-scheduled-btn', function() {
                if (confirm('Bu planlanmış içeriği silmek istediğinizden emin misiniz?')) {
                    const postId = $(this).data('post-id');
                    
                    $.post(ajaxurl, {
                        action: 'newsbot_delete_scheduled',
                        post_id: postId,
                        nonce: newsbot_ajax.nonce
                    }, function(response) {
                        if (response.success) {
                            showNotification('Planlanmış içerik silindi!', 'success');
                            loadScheduledPosts();
                        } else {
                            showNotification('Silme başarısız: ' + response.data, 'error');
                        }
                    });
                }
            });
            
            // Yenile butonu
            $('#refresh-scheduled').click(function() {
                loadScheduledPosts();
            });
            
            // Sayfa yüklendiğinde planlanmış içerikleri getir
            loadScheduledPosts();
            
            // Minimum tarih ayarla (bugün)
            const today = new Date().toISOString().split('T')[0];
            $('#schedule-date, #bulk-start-date').attr('min', today);
            
            // Bildirim göster
            function showNotification(message, type) {
                const notificationClass = type === 'success' ? 'notice-success' : 'notice-error';
                const $notification = $(`
                    <div class="notice ${notificationClass} is-dismissible">
                        <p>${message}</p>
                    </div>
                `);
                
                $('.wrap').prepend($notification);
                
                setTimeout(function() {
                    $notification.fadeOut();
                }, 3000);
            }
        });
        </script>
        <?php
    }
    
    public function title_generator_page() {
        ?>
        <div class="wrap newsbot-title-generator">
            <h1>💡 Başlık ve Kelime Jeneratörü</h1>
            
            <div class="newsbot-generator-grid">
                <div class="newsbot-card">
                    <h2>🎯 Başlık Jeneratörü</h2>
                    <form id="title-generator-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row">Ana Kelime/Konu</th>
                                <td>
                                    <input type="text" id="title-keyword" class="regular-text" placeholder="Örn: Yapay Zeka" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">İçerik Türü</th>
                                <td>
                                    <select id="title-type">
                                        <option value="news">Haber Makalesi</option>
                                        <option value="tutorial">Eğitim İçeriği</option>
                                        <option value="review">İnceleme</option>
                                        <option value="listicle">Liste Makalesi</option>
                                        <option value="question">Soru Formatı</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Başlık Sayısı</th>
                                <td>
                                    <select id="title-count">
                                        <option value="10">10 Başlık</option>
                                        <option value="20">20 Başlık</option>
                                        <option value="30">30 Başlık</option>
                                        <option value="50">50 Başlık</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary">🚀 Başlık Üret</button>
                        </p>
                    </form>
                    
                    <div id="generated-titles" style="display: none;">
                        <h3>Üretilen Başlıklar:</h3>
                        <div id="titles-list"></div>
                        <button id="copy-all-titles" class="button">📋 Tümünü Kopyala</button>
                    </div>
                </div>
                
                <div class="newsbot-card">
                    <h2>🔍 Anahtar Kelime Jeneratörü</h2>
                    <form id="keyword-generator-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row">Ana Konu</th>
                                <td>
                                    <input type="text" id="keyword-topic" class="regular-text" placeholder="Örn: Blockchain" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Kelime Sayısı</th>
                                <td>
                                    <select id="keyword-count">
                                        <option value="20">20 Kelime</option>
                                        <option value="30">30 Kelime</option>
                                        <option value="50">50 Kelime</option>
                                        <option value="100">100 Kelime</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary">🔍 Kelime Üret</button>
                        </p>
                    </form>
                    
                    <div id="generated-keywords" style="display: none;">
                        <h3>Üretilen Anahtar Kelimeler:</h3>
                        <div id="keywords-list"></div>
                        <button id="copy-all-keywords" class="button">📋 Tümünü Kopyala</button>
                    </div>
                </div>
            </div>
            
            <div class="newsbot-card">
                <h2>📊 Başlık Analiz Aracı</h2>
                <div class="title-analyzer">
                    <label for="analyze-title">Başlığınızı Analiz Edin:</label>
                    <input type="text" id="analyze-title" class="large-text" placeholder="Analiz edilecek başlığı girin">
                    <button id="analyze-title-btn" class="button">🔍 Analiz Et</button>
                    
                    <div id="title-analysis-results" style="display: none;">
                        <h3>Analiz Sonuçları:</h3>
                        <div id="analysis-content"></div>
                    </div>
                </div>
            </div>
            
            <!-- A/B Test Başlıkları -->
            <div class="newsbot-card">
                <h2>🧪 A/B Test Başlık Varyasyonları</h2>
                <div class="ab-test-generator">
                    <label for="base-title">Temel Başlık:</label>
                    <input type="text" id="base-title" class="large-text" placeholder="Temel başlığınızı girin">
                    <button id="generate-ab-titles" class="button">🧪 Varyasyon Üret</button>
                    
                    <div id="ab-test-results" style="display: none;">
                        <h3>A/B Test Varyasyonları:</h3>
                        <div id="ab-variations"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Başlık üretme
            $('#title-generator-form').submit(function(e) {
                e.preventDefault();
                
                const keyword = $('#title-keyword').val();
                const type = $('#title-type').val();
                const count = $('#title-count').val();
                
                $('button[type="submit"]').prop('disabled', true).text('Üretiliyor...');
                
                $.post(ajaxurl, {
                    action: 'newsbot_generate_titles',
                    keyword: keyword,
                    type: type,
                    count: count,
                    nonce: newsbot_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        displayTitles(response.data);
                        $('#generated-titles').show();
                    } else {
                        alert('Başlık üretilemedi: ' + response.data);
                    }
                    $('button[type="submit"]').prop('disabled', false).text('🚀 Başlık Üret');
                });
            });
            
            // Anahtar kelime üretme
            $('#keyword-generator-form').submit(function(e) {
                e.preventDefault();
                
                const topic = $('#keyword-topic').val();
                const count = $('#keyword-count').val();
                
                $('button[type="submit"]').prop('disabled', true).text('Üretiliyor...');
                
                $.post(ajaxurl, {
                    action: 'newsbot_generate_keywords',
                    topic: topic,
                    count: count,
                    nonce: newsbot_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        displayKeywords(response.data);
                        $('#generated-keywords').show();
                    } else {
                        alert('Kelime üretilemedi: ' + response.data);
                    }
                    $('button[type="submit"]').prop('disabled', false).text('🔍 Kelime Üret');
                });
            });
            
            // Başlık analizi
            $('#analyze-title-btn').click(function() {
                const title = $('#analyze-title').val();
                
                if (!title) {
                    alert('Lütfen analiz edilecek başlığı girin.');
                    return;
                }
                
                $(this).prop('disabled', true).text('Analiz ediliyor...');
                
                $.post(ajaxurl, {
                    action: 'newsbot_analyze_title',
                    title: title,
                    nonce: newsbot_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        displayTitleAnalysis(response.data);
                        $('#title-analysis-results').show();
                    } else {
                        alert('Analiz yapılamadı: ' + response.data);
                    }
                    $('#analyze-title-btn').prop('disabled', false).text('🔍 Analiz Et');
                });
            });
            
            // Başlıkları görüntüle
            function displayTitles(titles) {
                let html = '<div class="titles-grid">';
                
                titles.forEach(function(title, index) {
                    html += `
                        <div class="title-item">
                            <span class="title-number">${index + 1}.</span>
                            <span class="title-text">${title}</span>
                            <button class="copy-title-btn button button-small" data-title="${title}">📋</button>
                        </div>
                    `;
                });
                
                html += '</div>';
                $('#titles-list').html(html);
            }
            
            // Anahtar kelimeleri görüntüle
            function displayKeywords(keywords) {
                let html = '<div class="keywords-grid">';
                
                keywords.forEach(function(keyword) {
                    html += `
                        <span class="keyword-tag">${keyword}</span>
                    `;
                });
                
                html += '</div>';
                $('#keywords-list').html(html);
            }
            
            // Başlık analizini görüntüle
            function displayTitleAnalysis(analysis) {
                let html = `
                    <div class="analysis-summary">
                        <div class="score-circle">
                            <span class="score">${analysis.score}</span>
                            <span class="score-label">Puan</span>
                        </div>
                        <div class="analysis-details">
                            <p><strong>Değerlendirme:</strong> ${analysis.rating}</p>
                            <p><strong>Uzunluk:</strong> ${analysis.length} karakter</p>
                            <p><strong>Kelime Sayısı:</strong> ${analysis.word_count}</p>
                            <p><strong>SEO Skoru:</strong> ${analysis.seo_score}/100</p>
                        </div>
                    </div>
                    
                    <div class="analysis-features">
                        <div class="feature ${analysis.has_power_words ? 'positive' : 'negative'}">
                            ${analysis.has_power_words ? '✅' : '❌'} Güçlü Kelimeler
                        </div>
                        <div class="feature ${analysis.has_numbers ? 'positive' : 'negative'}">
                            ${analysis.has_numbers ? '✅' : '❌'} Sayılar
                        </div>
                        <div class="feature ${analysis.has_question ? 'positive' : 'negative'}">
                            ${analysis.has_question ? '✅' : '❌'} Soru Formatı
                        </div>
                        <div class="feature">
                            🎭 Duygusal Etki: ${analysis.emotional_impact}
                        </div>
                    </div>
                `;
                
                if (analysis.suggestions.length > 0) {
                    html += '<div class="suggestions"><h4>Öneriler:</h4><ul>';
                    analysis.suggestions.forEach(function(suggestion) {
                        html += `<li>${suggestion}</li>`;
                    });
                    html += '</ul></div>';
                }
                
                $('#analysis-content').html(html);
            }
            
            // Tek başlık kopyalama
            $(document).on('click', '.copy-title-btn', function() {
                const title = $(this).data('title');
                copyToClipboard(title);
                showNotification('Başlık kopyalandı!', 'success');
            });
            
            // Tüm başlıkları kopyalama
            $('#copy-all-titles').click(function() {
                const titles = [];
                $('.title-text').each(function() {
                    titles.push($(this).text());
                });
                copyToClipboard(titles.join('\n'));
                showNotification('Tüm başlıklar kopyalandı!', 'success');
            });
            
            // Tüm anahtar kelimeleri kopyalama
            $('#copy-all-keywords').click(function() {
                const keywords = [];
                $('.keyword-tag').each(function() {
                    keywords.push($(this).text());
                });
                copyToClipboard(keywords.join('\n'));
                showNotification('Tüm anahtar kelimeler kopyalandı!', 'success');
            });
            
            // Panoya kopyalama fonksiyonu
            function copyToClipboard(text) {
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text);
                } else {
                    const textArea = document.createElement('textarea');
                    textArea.value = text;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                }
            }
            
            // Bildirim göster
            function showNotification(message, type) {
                const notificationClass = type === 'success' ? 'notice-success' : 'notice-error';
                const $notification = $(`
                    <div class="notice ${notificationClass} is-dismissible">
                        <p>${message}</p>
                    </div>
                `);
                
                $('.wrap').prepend($notification);
                
                setTimeout(function() {
                    $notification.fadeOut();
                }, 2000);
            }
        });
        </script>
        <?php
    }
    
    public function content_generator_page() {
        ?>
        <div class="wrap newsbot-content-generator">
            <h1>✍️ İçerik Üretici</h1>
            
            <div class="newsbot-generator-grid">
                <div class="newsbot-card">
                    <h2>📝 Yeni İçerik Oluştur</h2>
                    <form id="content-generator-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row">Konu</th>
                                <td>
                                    <input type="text" id="content-topic" class="regular-text" placeholder="Örn: Yapay Zeka Teknolojileri" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Anahtar Kelimeler</th>
                                <td>
                                    <textarea id="content-keywords" rows="3" cols="50" placeholder="Her satıra bir anahtar kelime yazın"></textarea>
                                    <p class="description">SEO için önemli anahtar kelimeleri girin.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">İçerik Türü</th>
                                <td>
                                    <select id="content-template">
                                        <option value="news_article">Haber Makalesi</option>
                                        <option value="tutorial">Eğitim İçeriği</option>
                                        <option value="review">İnceleme</option>
                                        <option value="trend_analysis">Trend Analizi</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary">🚀 İçerik Üret</button>
                        </p>
                    </form>
                </div>
                
                <div class="newsbot-card">
                    <h2>📊 Mevcut İçerik Optimizasyonu</h2>
                    <p>Mevcut yazılarınızı SEO açısından optimize edin:</p>
                    
                    <div class="optimization-section">
                        <label for="post-selector">Yazı Seç:</label>
                        <select id="post-selector">
                            <option value="">Bir yazı seçin...</option>
                            <?php
                            $posts = get_posts(array('numberposts' => 20, 'post_status' => 'publish'));
                            foreach ($posts as $post) {
                                echo '<option value="' . $post->ID . '">' . esc_html($post->post_title) . '</option>';
                            }
                            ?>
                        </select>
                        
                        <button id="analyze-post" class="button">🔍 Analiz Et</button>
                        
                        <div id="optimization-results" style="display: none;">
                            <h3>Analiz Sonuçları:</h3>
                            <div id="optimization-content"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="generated-content-section" class="newsbot-card" style="display: none;">
                <h2>📄 Üretilen İçerik</h2>
                <div id="generated-content-display"></div>
                <div class="content-actions">
                    <button id="save-as-draft" class="button button-primary">💾 Taslak Olarak Kaydet</button>
                    <button id="schedule-content" class="button">📅 Planla</button>
                    <button id="copy-content" class="button">📋 Kopyala</button>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // İçerik üretme formu
            $('#content-generator-form').submit(function(e) {
                e.preventDefault();
                
                const topic = $('#content-topic').val();
                const keywords = $('#content-keywords').val().split('\n').filter(k => k.trim());
                const template = $('#content-template').val();
                
                if (!topic) {
                    alert('Lütfen bir konu girin.');
                    return;
                }
                
                $('button[type="submit"]').prop('disabled', true).text('İçerik üretiliyor...');
                
                $.post(ajaxurl, {
                    action: 'newsbot_generate_post',
                    topic: topic,
                    keywords: keywords,
                    template_type: template,
                    nonce: newsbot_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        displayGeneratedContent(response.data);
                        $('#generated-content-section').show();
                        window.generatedContent = response.data;
                    } else {
                        alert('İçerik üretilemedi: ' + response.data);
                    }
                    $('button[type="submit"]').prop('disabled', false).text('🚀 İçerik Üret');
                });
            });
            
            // Yazı analizi
            $('#analyze-post').click(function() {
                const postId = $('#post-selector').val();
                
                if (!postId) {
                    alert('Lütfen bir yazı seçin.');
                    return;
                }
                
                $(this).prop('disabled', true).text('Analiz ediliyor...');
                
                $.post(ajaxurl, {
                    action: 'newsbot_optimize_content',
                    post_id: postId,
                    nonce: newsbot_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        displayOptimizationResults(response.data);
                        $('#optimization-results').show();
                    } else {
                        alert('Analiz yapılamadı: ' + response.data);
                    }
                    $('#analyze-post').prop('disabled', false).text('🔍 Analiz Et');
                });
            });
            
            function displayGeneratedContent(content) {
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
            }
            
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
            
            // İçerik planlama
            $('#schedule-content').click(function() {
                if (!window.generatedContent) {
                    alert('Planlanacak içerik bulunamadı.');
                    return;
                }
                
                // İçerik planlayıcı sayfasına yönlendir
                const content = window.generatedContent;
                const url = '<?php echo admin_url('admin.php?page=newsbot-content-scheduler'); ?>';
                const form = $('<form method="post" action="' + url + '"></form>');
                
                form.append('<input type="hidden" name="prefill_title" value="' + content.title + '">');
                form.append('<input type="hidden" name="prefill_content" value="' + content.content + '">');
                form.append('<input type="hidden" name="prefill_excerpt" value="' + content.excerpt + '">');
                
                $('body').append(form);
                form.submit();
            });
        });
        </script>
        <?php
    }
    
    public function seo_tracking_page() {
        $keywords = $this->seo_tracker->get_tracked_keywords();
        ?>
        <div class="wrap newsbot-seo-tracking">
            <h1>📊 SEO Takibi</h1>
            
            <div class="newsbot-seo-grid">
                <div class="newsbot-card">
                    <h2>🎯 Anahtar Kelime Sıralamaları</h2>
                    
                    <?php if (empty($keywords)): ?>
                        <p>Henüz takip edilen anahtar kelime yok. <a href="<?php echo admin_url('admin.php?page=newsbot-settings'); ?>">Ayarlar</a> sayfasından ekleyebilirsiniz.</p>
                    <?php else: ?>
                        <div class="keywords-table">
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th>Anahtar Kelime</th>
                                        <th>Mevcut Sıra</th>
                                        <th>Önceki Sıra</th>
                                        <th>Değişim</th>
                                        <th>En İyi Sıra</th>
                                        <th>Arama Hacmi</th>
                                        <th>Zorluk</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($keywords as $keyword_data): ?>
                                        <?php
                                        $change = $keyword_data['previous_position'] - $keyword_data['current_position'];
                                        $trend_class = $change > 0 ? 'positive' : ($change < 0 ? 'negative' : 'neutral');
                                        ?>
                                        <tr>
                                            <td><strong><?php echo esc_html($keyword_data['keyword']); ?></strong></td>
                                            <td><?php echo $keyword_data['current_position'] ?: '-'; ?></td>
                                            <td><?php echo $keyword_data['previous_position'] ?: '-'; ?></td>
                                            <td class="<?php echo $trend_class; ?>">
                                                <?php if ($change > 0): ?>
                                                    ↗️ +<?php echo $change; ?>
                                                <?php elseif ($change < 0): ?>
                                                    ↘️ <?php echo $change; ?>
                                                <?php else: ?>
                                                    ➡️ 0
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $keyword_data['best_position'] ?: '-'; ?></td>
                                            <td><?php echo number_format($keyword_data['search_volume']); ?></td>
                                            <td>
                                                <span class="difficulty-badge difficulty-<?php echo $keyword_data['difficulty']; ?>">
                                                    <?php echo ucfirst($keyword_data['difficulty']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="newsbot-card">
                    <h2>📈 SEO Performans Özeti</h2>
                    <div class="seo-summary">
                        <?php
                        $total_keywords = count($keywords);
                        $top_10 = 0;
                        $top_50 = 0;
                        
                        foreach ($keywords as $keyword_data) {
                            if ($keyword_data['current_position'] > 0 && $keyword_data['current_position'] <= 10) {
                                $top_10++;
                            }
                            if ($keyword_data['current_position'] > 0 && $keyword_data['current_position'] <= 50) {
                                $top_50++;
                            }
                        }
                        ?>
                        
                        <div class="summary-stat">
                            <span class="stat-label">Toplam Kelime:</span>
                            <span class="stat-value"><?php echo $total_keywords; ?></span>
                        </div>
                        
                        <div class="summary-stat">
                            <span class="stat-label">İlk 10'da:</span>
                            <span class="stat-value positive"><?php echo $top_10; ?></span>
                        </div>
                        
                        <div class="summary-stat">
                            <span class="stat-label">İlk 50'de:</span>
                            <span class="stat-value"><?php echo $top_50; ?></span>
                        </div>
                        
                        <div class="summary-stat">
                            <span class="stat-label">Başarı Oranı:</span>
                            <span class="stat-value"><?php echo $total_keywords > 0 ? round(($top_50 / $total_keywords) * 100) : 0; ?>%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function settings_page() {
        if (isset($_POST['submit'])) {
            // Anahtar kelimeler
            $keywords = array_filter(array_map('trim', explode("\n", $_POST['keywords'])));
            update_option('newsbot_keywords', $keywords);
            
            // API anahtarları
            update_option('newsbot_ga_id', sanitize_text_field($_POST['ga_id']));
            update_option('newsbot_trends_api_key', sanitize_text_field($_POST['trends_api_key']));
            update_option('newsbot_news_api_key', sanitize_text_field($_POST['news_api_key']));
            update_option('newsbot_serp_api_key', sanitize_text_field($_POST['serp_api_key']));
            
            echo '<div class="notice notice-success"><p>Ayarlar kaydedildi!</p></div>';
        }
        
        $keywords = get_option('newsbot_keywords', array());
        $ga_id = get_option('newsbot_ga_id', '');
        $trends_api_key = get_option('newsbot_trends_api_key', '');
        $news_api_key = get_option('newsbot_news_api_key', '');
        $serp_api_key = get_option('newsbot_serp_api_key', '');
        ?>
        <div class="wrap newsbot-settings">
            <h1>⚙️ NewsBot Ayarları</h1>
            
            <form method="post" action="">
                <div class="newsbot-settings-grid">
                    <div class="newsbot-card">
                        <h2>🔑 API Anahtarları</h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row">Google Analytics ID</th>
                                <td>
                                    <input type="text" name="ga_id" value="<?php echo esc_attr($ga_id); ?>" class="regular-text" placeholder="GA-XXXXXXXXX-X" />
                                    <p class="description">Google Analytics takip ID'nizi girin.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Google Trends API Key</th>
                                <td>
                                    <input type="text" name="trends_api_key" value="<?php echo esc_attr($trends_api_key); ?>" class="regular-text" />
                                    <p class="description">Google Trends API anahtarı (isteğe bağlı).</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">News API Key</th>
                                <td>
                                    <input type="text" name="news_api_key" value="<?php echo esc_attr($news_api_key); ?>" class="regular-text" />
                                    <p class="description">Haber API anahtarı (newsapi.org).</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">SERP API Key</th>
                                <td>
                                    <input type="text" name="serp_api_key" value="<?php echo esc_attr($serp_api_key); ?>" class="regular-text" />
                                    <p class="description">SEO sıralama takibi için SERP API anahtarı.</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="newsbot-card">
                        <h2>🎯 Anahtar Kelime Takibi</h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row">Takip Edilecek Anahtar Kelimeler</th>
                                <td>
                                    <textarea name="keywords" rows="10" cols="50" class="large-text"><?php echo esc_textarea(implode("\n", $keywords)); ?></textarea>
                                    <p class="description">Her satıra bir anahtar kelime yazın. Bu kelimeler Google'da sıralama takibi için kullanılacak.</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <?php submit_button('💾 Ayarları Kaydet'); ?>
            </form>
        </div>
        <?php
    }
    
    public function handle_ajax_request() {
        // AJAX isteklerini işle
        if (!wp_verify_nonce($_POST['nonce'], 'newsbot_nonce')) {
            wp_die('Güvenlik kontrolü başarısız');
        }
        
        $action = sanitize_text_field($_POST['action_type']);
        
        switch ($action) {
            case 'get_analytics':
                $this->get_analytics_data();
                break;
            case 'get_keywords':
                $this->get_keyword_rankings();
                break;
            case 'save_settings':
                $this->save_plugin_settings();
                break;
            default:
                wp_send_json_error('Geçersiz işlem');
        }
    }
    
    private function get_analytics_data() {
        $data = array(
            'visitors' => $this->get_daily_visitors(),
            'keywords' => $this->get_search_keywords(),
            'pages' => $this->get_popular_pages()
        );
        
        wp_send_json_success($data);
    }
    
    private function get_keyword_rankings() {
        $keywords = get_option('newsbot_keywords', array());
        $rankings = array();
        
        foreach ($keywords as $keyword) {
            $rankings[] = array(
                'keyword' => $keyword,
                'position' => rand(1, 50),
                'volume' => rand(100, 10000),
                'change' => rand(-5, 5)
            );
        }
        
        wp_send_json_success($rankings);
    }
    
    private function get_daily_visitors() {
        // WordPress istatistikleri
        $count = wp_count_posts();
        return $count->publish * rand(10, 50); // Basit hesaplama
    }
    
    private function get_total_pageviews() {
        global $wpdb;
        $total = $wpdb->get_var("SELECT SUM(meta_value) FROM {$wpdb->postmeta} WHERE meta_key = 'post_views_count'");
        return $total ?: rand(10000, 50000);
    }
    
    private function get_search_keywords() {
        return array(
            'yapay zeka' => 1250,
            'teknoloji haberleri' => 890,
            'blockchain' => 650,
            'web tasarım' => 420,
            'seo optimizasyonu' => 380
        );
    }
    
    private function get_popular_pages() {
        $posts = get_posts(array(
            'numberposts' => 10,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        $pages = array();
        foreach ($posts as $post) {
            $views = get_post_meta($post->ID, 'post_views_count', true);
            $pages[] = array(
                'title' => $post->post_title,
                'url' => get_permalink($post->ID),
                'views' => $views ?: rand(100, 1000)
            );
        }
        
        return $pages;
    }
    
    private function get_seo_summary() {
        $keywords = get_option('newsbot_keywords', array());
        $total_score = 0;
        $count = 0;
        
        foreach ($keywords as $keyword) {
            $position = rand(1, 50);
            $score = max(0, 100 - ($position * 2));
            $total_score += $score;
            $count++;
        }
        
        $average = $count > 0 ? round($total_score / $count) : 75;
        
        return array(
            'average_score' => $average,
            'trend' => 'positive',
            'change' => '+5%'
        );
    }
    
    private function save_plugin_settings() {
        $settings = array(
            'api_keys' => sanitize_text_field($_POST['api_keys']),
            'keywords' => array_map('sanitize_text_field', $_POST['keywords']),
            'schedule' => sanitize_text_field($_POST['schedule'])
        );
        
        update_option('newsbot_settings', $settings);
        wp_send_json_success('Ayarlar kaydedildi');
    }
    
    private function schedule_daily_tasks() {
        // Günlük SEO kontrolü
        if (!wp_next_scheduled('newsbot_daily_seo_check')) {
            wp_schedule_event(time(), 'daily', 'newsbot_daily_seo_check');
        }
        
        // Günlük haber analizi
        if (!wp_next_scheduled('newsbot_daily_news_analysis')) {
            wp_schedule_event(time(), 'daily', 'newsbot_daily_news_analysis');
        }
    }
}

// Plugin'i başlat
new NewsBotAutomation();

// Aktivasyon hook'u
register_activation_hook(__FILE__, 'newsbot_activate');
function newsbot_activate() {
    try {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'newsbot_analytics';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            date date DEFAULT '0000-00-00' NOT NULL,
            visitors int(11) DEFAULT 0 NOT NULL,
            pageviews int(11) DEFAULT 0 NOT NULL,
            bounce_rate float DEFAULT 0 NOT NULL,
            avg_session_duration int(11) DEFAULT 0 NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // SEO takip tablosu
        $seo_table = $wpdb->prefix . 'newsbot_seo_history';
        $sql2 = "CREATE TABLE IF NOT EXISTS $seo_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            keyword varchar(255) NOT NULL,
            previous_position int(11) DEFAULT 0,
            current_position int(11) DEFAULT 0,
            change_date datetime DEFAULT CURRENT_TIMESTAMP,
            search_volume int(11) DEFAULT 0,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        dbDelta($sql2);
        
        // Varsayılan ayarları ekle
        add_option('newsbot_keywords', array('teknoloji', 'yapay zeka', 'web tasarım'));
        add_option('newsbot_settings', array('schedule' => 'daily'));
        
    } catch (Exception $e) {
        error_log('NewsBot Activation Error: ' . $e->getMessage());
    }
}

// Deaktivasyon hook'u
register_deactivation_hook(__FILE__, 'newsbot_deactivate');
function newsbot_deactivate() {
    wp_clear_scheduled_hook('newsbot_daily_analysis');
    wp_clear_scheduled_hook('newsbot_daily_seo_check');
    wp_clear_scheduled_hook('newsbot_daily_news_analysis');
}
?>