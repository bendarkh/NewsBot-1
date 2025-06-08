<?php
/**
 * Plugin Name: NewsBot Automation
 * Plugin URI: https://yoursite.com
 * Description: WordPress Teknoloji Haberi Otomasyonu Yönetim Paneli
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

// Güvenlik kontrolü
if (!defined('ABSPATH')) {
    exit;
}

// Plugin sabitleri
define('NEWSBOT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NEWSBOT_PLUGIN_PATH', plugin_dir_path(__FILE__));

class NewsBotAutomation {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_newsbot_api', array($this, 'handle_ajax_request'));
        add_action('wp_ajax_nopriv_newsbot_api', array($this, 'handle_ajax_request'));
    }
    
    public function init() {
        // Plugin başlatma işlemleri
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
    }
    
    public function enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_newsbot-automation') {
            return;
        }
        
        // React build dosyalarını yükle
        wp_enqueue_script(
            'newsbot-app',
            NEWSBOT_PLUGIN_URL . 'dist/assets/index.js',
            array(),
            '1.0.0',
            true
        );
        
        wp_enqueue_style(
            'newsbot-app',
            NEWSBOT_PLUGIN_URL . 'dist/assets/index.css',
            array(),
            '1.0.0'
        );
        
        // WordPress AJAX için nonce
        wp_localize_script('newsbot-app', 'newsbot_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('newsbot_nonce')
        ));
    }
    
    public function admin_page() {
        echo '<div id="newsbot-root"></div>';
    }
    
    public function handle_ajax_request() {
        // AJAX isteklerini işle
        check_ajax_referer('newsbot_nonce', 'nonce');
        
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
                wp_die('Geçersiz işlem');
        }
    }
    
    private function get_analytics_data() {
        // Google Analytics API entegrasyonu
        $data = array(
            'visitors' => $this->get_daily_visitors(),
            'keywords' => $this->get_search_keywords(),
            'pages' => $this->get_popular_pages()
        );
        
        wp_send_json_success($data);
    }
    
    private function get_keyword_rankings() {
        // SEO sıralama verilerini al
        $keywords = get_option('newsbot_keywords', array());
        $rankings = array();
        
        foreach ($keywords as $keyword) {
            $rankings[] = array(
                'keyword' => $keyword,
                'position' => $this->get_google_ranking($keyword),
                'volume' => $this->get_search_volume($keyword)
            );
        }
        
        wp_send_json_success($rankings);
    }
    
    private function get_google_ranking($keyword) {
        // Google Search Console API ile sıralama al
        // Bu kısım gerçek API entegrasyonu gerektirir
        return rand(1, 20); // Örnek veri
    }
    
    private function get_search_volume($keyword) {
        // Anahtar kelime arama hacmi
        return rand(100, 10000); // Örnek veri
    }
    
    private function get_daily_visitors() {
        // WordPress istatistikleri veya Google Analytics
        return rand(1000, 5000); // Örnek veri
    }
    
    private function get_search_keywords() {
        // En çok aranan kelimeler
        return array(
            'yapay zeka' => 1250,
            'teknoloji haberleri' => 890,
            'blockchain' => 650
        );
    }
    
    private function get_popular_pages() {
        // En popüler sayfalar
        $posts = get_posts(array(
            'numberposts' => 10,
            'meta_key' => 'post_views_count',
            'orderby' => 'meta_value_num',
            'order' => 'DESC'
        ));
        
        $pages = array();
        foreach ($posts as $post) {
            $pages[] = array(
                'title' => $post->post_title,
                'url' => get_permalink($post->ID),
                'views' => get_post_meta($post->ID, 'post_views_count', true) ?: rand(100, 1000)
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
    // Veritabanı tablolarını oluştur
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'newsbot_analytics';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
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
}

// Deaktivasyon hook'u
register_deactivation_hook(__FILE__, 'newsbot_deactivate');
function newsbot_deactivate() {
    // Temizlik işlemleri
    wp_clear_scheduled_hook('newsbot_daily_analysis');
}
?>