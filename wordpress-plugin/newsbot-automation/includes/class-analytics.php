<?php
/**
 * Analytics sınıfı - Google Analytics ve WordPress istatistikleri
 */

if (!defined('ABSPATH')) {
    exit;
}

class NewsBot_Analytics {
    
    private $google_analytics_id;
    
    public function __construct() {
        $this->google_analytics_id = get_option('newsbot_ga_id');
        add_action('wp_head', array($this, 'add_tracking_code'));
        add_action('wp_footer', array($this, 'track_page_views'));
    }
    
    public function add_tracking_code() {
        if (!$this->google_analytics_id) return;
        
        ?>
        <!-- Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($this->google_analytics_id); ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '<?php echo esc_attr($this->google_analytics_id); ?>');
        </script>
        <?php
    }
    
    public function track_page_views() {
        if (is_single()) {
            $post_id = get_the_ID();
            $views = get_post_meta($post_id, 'post_views_count', true);
            $views = $views ? $views + 1 : 1;
            update_post_meta($post_id, 'post_views_count', $views);
        }
    }
    
    public function get_analytics_data($days = 30) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'newsbot_analytics';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE date >= DATE_SUB(NOW(), INTERVAL %d DAY) ORDER BY date DESC",
            $days
        ));
        
        return $results;
    }
    
    public function get_popular_posts($limit = 10) {
        $posts = get_posts(array(
            'numberposts' => $limit,
            'meta_key' => 'post_views_count',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'post_status' => 'publish'
        ));
        
        $popular_posts = array();
        foreach ($posts as $post) {
            $popular_posts[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'url' => get_permalink($post->ID),
                'views' => get_post_meta($post->ID, 'post_views_count', true) ?: 0,
                'date' => $post->post_date
            );
        }
        
        return $popular_posts;
    }
    
    public function get_search_keywords() {
        // WordPress arama loglarından en çok aranan kelimeleri al
        global $wpdb;
        
        $keywords = $wpdb->get_results(
            "SELECT meta_value as keyword, COUNT(*) as count 
             FROM {$wpdb->postmeta} 
             WHERE meta_key = 'search_keyword' 
             GROUP BY meta_value 
             ORDER BY count DESC 
             LIMIT 20"
        );
        
        return $keywords;
    }
    
    public function save_daily_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'newsbot_analytics';
        $today = date('Y-m-d');
        
        // Bugünün istatistiklerini al
        $visitors = $this->get_daily_visitors();
        $pageviews = $this->get_daily_pageviews();
        $bounce_rate = $this->get_bounce_rate();
        $avg_session = $this->get_avg_session_duration();
        
        // Veritabanına kaydet
        $wpdb->replace(
            $table_name,
            array(
                'date' => $today,
                'visitors' => $visitors,
                'pageviews' => $pageviews,
                'bounce_rate' => $bounce_rate,
                'avg_session_duration' => $avg_session
            ),
            array('%s', '%d', '%d', '%f', '%d')
        );
    }
    
    private function get_daily_visitors() {
        // Gerçek Google Analytics API entegrasyonu gerekli
        // Şimdilik örnek veri döndürüyoruz
        return rand(1000, 3000);
    }
    
    private function get_daily_pageviews() {
        return rand(2000, 8000);
    }
    
    private function get_bounce_rate() {
        return rand(30, 70);
    }
    
    private function get_avg_session_duration() {
        return rand(120, 400); // saniye cinsinden
    }
}
?>