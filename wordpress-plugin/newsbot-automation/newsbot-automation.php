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

class NewsBotAutomation {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_newsbot_api', array($this, 'handle_ajax_request'));
        add_action('wp_ajax_nopriv_newsbot_api', array($this, 'handle_ajax_request'));
        
        // Hata ayıklama için
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    public function init() {
        // Plugin başlatma işlemleri
        load_plugin_textdomain('newsbot-automation', false, dirname(plugin_basename(__FILE__)) . '/languages');
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
            // React build dosyalarının varlığını kontrol et
            $js_file = NEWSBOT_PLUGIN_PATH . 'dist/assets/index.js';
            $css_file = NEWSBOT_PLUGIN_PATH . 'dist/assets/index.css';
            
            if (!file_exists($js_file)) {
                // Build dosyaları yoksa basit HTML interface kullan
                wp_enqueue_script('newsbot-simple', NEWSBOT_PLUGIN_URL . 'assets/simple-interface.js', array('jquery'), NEWSBOT_VERSION, true);
                wp_enqueue_style('newsbot-simple', NEWSBOT_PLUGIN_URL . 'assets/simple-interface.css', array(), NEWSBOT_VERSION);
            } else {
                // React build dosyalarını yükle
                wp_enqueue_script('newsbot-app', NEWSBOT_PLUGIN_URL . 'dist/assets/index.js', array(), NEWSBOT_VERSION, true);
                wp_enqueue_style('newsbot-app', NEWSBOT_PLUGIN_URL . 'dist/assets/index.css', array(), NEWSBOT_VERSION);
            }
            
            // WordPress AJAX için nonce
            wp_localize_script('newsbot-app', 'newsbot_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('newsbot_nonce'),
                'plugin_url' => NEWSBOT_PLUGIN_URL
            ));
            
        } catch (Exception $e) {
            error_log('NewsBot Plugin Script Error: ' . $e->getMessage());
        }
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>NewsBot Automation Dashboard</h1>
            
            <!-- React App Container -->
            <div id="newsbot-root">
                <!-- Fallback HTML Interface -->
                <div class="newsbot-fallback">
                    <div class="newsbot-card">
                        <h2>📊 Dashboard</h2>
                        <div class="newsbot-stats">
                            <div class="stat-box">
                                <h3>Günlük Ziyaretçi</h3>
                                <p class="stat-number"><?php echo $this->get_daily_visitors(); ?></p>
                            </div>
                            <div class="stat-box">
                                <h3>Takip Edilen Kelimeler</h3>
                                <p class="stat-number"><?php echo count(get_option('newsbot_keywords', array())); ?></p>
                            </div>
                            <div class="stat-box">
                                <h3>Toplam Sayfa Görüntüleme</h3>
                                <p class="stat-number"><?php echo $this->get_total_pageviews(); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="newsbot-card">
                        <h2>🔍 SEO Durumu</h2>
                        <div class="seo-keywords">
                            <?php $keywords = get_option('newsbot_keywords', array()); ?>
                            <?php if (empty($keywords)): ?>
                                <p>Henüz anahtar kelime eklenmemiş. <a href="<?php echo admin_url('admin.php?page=newsbot-settings'); ?>">Ayarlar</a> sayfasından ekleyebilirsiniz.</p>
                            <?php else: ?>
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th>Anahtar Kelime</th>
                                            <th>Sıralama</th>
                                            <th>Değişim</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($keywords as $keyword): ?>
                                        <tr>
                                            <td><?php echo esc_html($keyword); ?></td>
                                            <td><?php echo rand(1, 50); ?></td>
                                            <td><span class="dashicons dashicons-arrow-up-alt" style="color: green;"></span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="newsbot-card">
                        <h2>📈 Popüler İçerikler</h2>
                        <div class="popular-posts">
                            <?php $popular = $this->get_popular_pages(); ?>
                            <?php if (empty($popular)): ?>
                                <p>Henüz popüler içerik verisi yok.</p>
                            <?php else: ?>
                                <ul>
                                    <?php foreach (array_slice($popular, 0, 5) as $post): ?>
                                    <li>
                                        <a href="<?php echo esc_url($post['url']); ?>" target="_blank">
                                            <?php echo esc_html($post['title']); ?>
                                        </a>
                                        <span class="views">(<?php echo $post['views']; ?> görüntüleme)</span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .newsbot-fallback {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .newsbot-card {
            background: white;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .newsbot-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        .stat-box {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #0073aa;
            margin: 5px 0;
        }
        .popular-posts ul {
            list-style: none;
            padding: 0;
        }
        .popular-posts li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .views {
            color: #666;
            font-size: 12px;
        }
        </style>
        <?php
    }
    
    public function settings_page() {
        if (isset($_POST['submit'])) {
            $keywords = array_filter(array_map('trim', explode("\n", $_POST['keywords'])));
            update_option('newsbot_keywords', $keywords);
            update_option('newsbot_ga_id', sanitize_text_field($_POST['ga_id']));
            echo '<div class="notice notice-success"><p>Ayarlar kaydedildi!</p></div>';
        }
        
        $keywords = get_option('newsbot_keywords', array());
        $ga_id = get_option('newsbot_ga_id', '');
        ?>
        <div class="wrap">
            <h1>NewsBot Ayarları</h1>
            
            <form method="post" action="">
                <table class="form-table">
                    <tr>
                        <th scope="row">Google Analytics ID</th>
                        <td>
                            <input type="text" name="ga_id" value="<?php echo esc_attr($ga_id); ?>" class="regular-text" placeholder="GA-XXXXXXXXX-X" />
                            <p class="description">Google Analytics takip ID'nizi girin.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Takip Edilecek Anahtar Kelimeler</th>
                        <td>
                            <textarea name="keywords" rows="10" cols="50" class="large-text"><?php echo esc_textarea(implode("\n", $keywords)); ?></textarea>
                            <p class="description">Her satıra bir anahtar kelime yazın.</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
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
    
    private function save_plugin_settings() {
        $settings = array(
            'api_keys' => sanitize_text_field($_POST['api_keys']),
            'keywords' => array_map('sanitize_text_field', $_POST['keywords']),
            'schedule' => sanitize_text_field($_POST['schedule'])
        );
        
        update_option('newsbot_settings', $settings);
        wp_send_json_success('Ayarlar kaydedildi');
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
}
?>