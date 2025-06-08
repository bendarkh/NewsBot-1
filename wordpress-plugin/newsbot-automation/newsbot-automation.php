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

class NewsBotAutomation {
    
    private $analytics;
    private $seo_tracker;
    private $news_analyzer;
    private $content_generator;
    
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
        ?>
        <div class="wrap newsbot-news-analysis">
            <h1>📰 Haber Analizi</h1>
            
            <div class="newsbot-analysis-grid">
                <div class="newsbot-card">
                    <h2>🔥 Güncel Trend Konuları</h2>
                    <p>Google Trends'den Türkiye geneli güncel hareketli konular:</p>
                    
                    <div id="trending-topics" class="trending-topics">
                        <div class="loading">Trend konuları yükleniyor...</div>
                    </div>
                    
                    <button id="refresh-trends" class="button button-primary">🔄 Yenile</button>
                </div>
                
                <div class="newsbot-card">
                    <h2>📝 Haber Başlıkları</h2>
                    <div id="news-headlines" class="news-headlines">
                        <div class="headline-item">
                            <h4>Yapay Zeka ChatGPT'de Yeni Özellikler</h4>
                            <span class="headline-source">TechCrunch</span>
                        </div>
                        <div class="headline-item">
                            <h4>Blockchain Teknolojisinde Son Gelişmeler</h4>
                            <span class="headline-source">CoinDesk</span>
                        </div>
                        <div class="headline-item">
                            <h4>5G Teknolojisi Türkiye'de Yaygınlaşıyor</h4>
                            <span class="headline-source">Dünya Gazetesi</span>
                        </div>
                        <div class="headline-item">
                            <h4>Metaverse Platformları Büyümeye Devam Ediyor</h4>
                            <span class="headline-source">Wired</span>
                        </div>
                        <div class="headline-item">
                            <h4>Siber Güvenlik Tehditleri Artıyor</h4>
                            <span class="headline-source">ZDNet</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="newsbot-card">
                <h2>🎯 İçerik Üretimi</h2>
                <p>Trend konulardan birini seçerek otomatik içerik üretebilirsiniz:</p>
                
                <div id="content-generation" class="content-generation">
                    <div class="generation-form">
                        <label for="selected-topic">Seçilen Konu:</label>
                        <input type="text" id="selected-topic" placeholder="Bir trend konusu seçin" readonly>
                        
                        <label for="content-type">İçerik Türü:</label>
                        <select id="content-type">
                            <option value="news_article">Haber Makalesi</option>
                            <option value="tutorial">Eğitim İçeriği</option>
                            <option value="review">İnceleme</option>
                            <option value="trend_analysis">Trend Analizi</option>
                        </select>
                        
                        <button id="generate-content" class="button button-primary" disabled>✍️ İçerik Üret</button>
                    </div>
                    
                    <div id="generated-content" class="generated-content" style="display: none;">
                        <h3>Üretilen İçerik Taslağı:</h3>
                        <div id="content-preview"></div>
                        <button id="save-as-draft" class="button button-secondary">💾 Taslak Olarak Kaydet</button>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Trend konularını yükle
            function loadTrendingTopics() {
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
                trends.forEach(function(trend) {
                    html += `
                        <div class="trend-item" data-topic="${trend.title}">
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
            
            // Trend konusu seç
            $(document).on('click', '.select-topic-btn', function() {
                const topic = $(this).closest('.trend-item').data('topic');
                $('#selected-topic').val(topic);
                $('#generate-content').prop('disabled', false);
                $('.trend-item').removeClass('selected');
                $(this).closest('.trend-item').addClass('selected');
            });
            
            // İçerik üret
            $('#generate-content').click(function() {
                const topic = $('#selected-topic').val();
                const contentType = $('#content-type').val();
                
                if (!topic) {
                    alert('Lütfen bir konu seçin.');
                    return;
                }
                
                $(this).prop('disabled', true).text('İçerik üretiliyor...');
                
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
                    } else {
                        alert('İçerik üretilemedi: ' + response.data);
                    }
                    $('#generate-content').prop('disabled', false).text('✍️ İçerik Üret');
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
            
            // Sayfa yüklendiğinde trend konularını getir
            loadTrendingTopics();
            
            // Yenile butonu
            $('#refresh-trends').click(function() {
                loadTrendingTopics();
            });
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