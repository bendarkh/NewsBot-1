<?php
/**
 * SEO Takip sınıfı - Anahtar kelime sıralaması takibi
 */

if (!defined('ABSPATH')) {
    exit;
}

class NewsBot_SEO_Tracker {
    
    private $api_key;
    
    public function __construct() {
        $this->api_key = get_option('newsbot_serp_api_key');
        add_action('newsbot_daily_seo_check', array($this, 'check_keyword_rankings'));
    }
    
    public function add_keyword($keyword, $target_url = '') {
        $keywords = get_option('newsbot_tracked_keywords', array());
        
        $keywords[] = array(
            'keyword' => sanitize_text_field($keyword),
            'target_url' => esc_url($target_url),
            'added_date' => current_time('mysql'),
            'current_position' => 0,
            'previous_position' => 0,
            'best_position' => 0,
            'search_volume' => 0,
            'difficulty' => 'unknown'
        );
        
        update_option('newsbot_tracked_keywords', $keywords);
        
        return true;
    }
    
    public function remove_keyword($index) {
        $keywords = get_option('newsbot_tracked_keywords', array());
        
        if (isset($keywords[$index])) {
            unset($keywords[$index]);
            $keywords = array_values($keywords); // Dizini yeniden düzenle
            update_option('newsbot_tracked_keywords', $keywords);
            return true;
        }
        
        return false;
    }
    
    public function get_tracked_keywords() {
        return get_option('newsbot_tracked_keywords', array());
    }
    
    public function check_keyword_rankings() {
        $keywords = $this->get_tracked_keywords();
        $site_url = get_site_url();
        
        foreach ($keywords as $index => $keyword_data) {
            $keyword = $keyword_data['keyword'];
            
            // Önceki pozisyonu kaydet
            $keywords[$index]['previous_position'] = $keyword_data['current_position'];
            
            // Yeni pozisyonu al
            $new_position = $this->get_google_position($keyword, $site_url);
            $keywords[$index]['current_position'] = $new_position;
            
            // En iyi pozisyonu güncelle
            if ($new_position > 0 && ($keyword_data['best_position'] == 0 || $new_position < $keyword_data['best_position'])) {
                $keywords[$index]['best_position'] = $new_position;
            }
            
            // Arama hacmini güncelle
            $keywords[$index]['search_volume'] = $this->get_search_volume($keyword);
            $keywords[$index]['difficulty'] = $this->get_keyword_difficulty($keyword);
            
            // Son kontrol tarihini güncelle
            $keywords[$index]['last_checked'] = current_time('mysql');
            
            // Kısa bekleme (API rate limit için)
            sleep(1);
        }
        
        update_option('newsbot_tracked_keywords', $keywords);
        
        // Değişiklikleri logla
        $this->log_ranking_changes($keywords);
    }
    
    private function get_google_position($keyword, $site_url) {
        if (!$this->api_key) {
            return rand(1, 50); // API anahtarı yoksa örnek veri döndür
        }
        
        // SERP API ile Google'da pozisyon kontrolü
        $api_url = "https://serpapi.com/search.json";
        $params = array(
            'engine' => 'google',
            'q' => $keyword,
            'location' => 'Turkey',
            'hl' => 'tr',
            'gl' => 'tr',
            'api_key' => $this->api_key
        );
        
        $url = $api_url . '?' . http_build_query($params);
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            return 0;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!isset($data['organic_results'])) {
            return 0;
        }
        
        // Site URL'ini arama sonuçlarında bul
        foreach ($data['organic_results'] as $index => $result) {
            if (strpos($result['link'], parse_url($site_url, PHP_URL_HOST)) !== false) {
                return $index + 1; // Pozisyon 1'den başlar
            }
        }
        
        return 0; // Bulunamadı
    }
    
    private function get_search_volume($keyword) {
        // Keyword Planner API veya başka bir servis kullanılabilir
        // Şimdilik örnek veri döndürüyoruz
        return rand(100, 10000);
    }
    
    private function get_keyword_difficulty($keyword) {
        // Keyword zorluk seviyesi hesaplama
        $length = strlen($keyword);
        
        if ($length < 10) {
            return 'zor';
        } elseif ($length < 20) {
            return 'orta';
        } else {
            return 'kolay';
        }
    }
    
    private function log_ranking_changes($keywords) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'newsbot_seo_history';
        
        foreach ($keywords as $keyword_data) {
            if ($keyword_data['current_position'] != $keyword_data['previous_position']) {
                $wpdb->insert(
                    $table_name,
                    array(
                        'keyword' => $keyword_data['keyword'],
                        'previous_position' => $keyword_data['previous_position'],
                        'current_position' => $keyword_data['current_position'],
                        'change_date' => current_time('mysql'),
                        'search_volume' => $keyword_data['search_volume']
                    ),
                    array('%s', '%d', '%d', '%s', '%d')
                );
            }
        }
    }
    
    public function get_ranking_history($keyword, $days = 30) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'newsbot_seo_history';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE keyword = %s 
             AND change_date >= DATE_SUB(NOW(), INTERVAL %d DAY) 
             ORDER BY change_date DESC",
            $keyword,
            $days
        ));
        
        return $results;
    }
    
    public function schedule_daily_check() {
        if (!wp_next_scheduled('newsbot_daily_seo_check')) {
            wp_schedule_event(time(), 'daily', 'newsbot_daily_seo_check');
        }
    }
}
?>