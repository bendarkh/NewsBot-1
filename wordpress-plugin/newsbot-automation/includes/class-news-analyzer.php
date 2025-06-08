<?php
/**
 * Haber Analizi sınıfı - Google Trends ve otomatik haber analizi
 */

if (!defined('ABSPATH')) {
    exit;
}

class NewsBot_News_Analyzer {
    
    private $trends_api_key;
    private $news_api_key;
    
    public function __construct() {
        $this->trends_api_key = get_option('newsbot_trends_api_key');
        $this->news_api_key = get_option('newsbot_news_api_key');
        
        add_action('wp_ajax_newsbot_get_trends', array($this, 'get_trending_topics'));
        add_action('wp_ajax_newsbot_analyze_news', array($this, 'analyze_news_content'));
        add_action('wp_ajax_newsbot_generate_content', array($this, 'generate_content_suggestion'));
        add_action('newsbot_daily_news_analysis', array($this, 'daily_news_scan'));
    }
    
    /**
     * Google Trends'den güncel konuları al
     */
    public function get_trending_topics() {
        check_ajax_referer('newsbot_nonce', 'nonce');
        
        $trends = $this->fetch_google_trends();
        
        if ($trends) {
            wp_send_json_success($trends);
        } else {
            // Fallback örnek veriler
            $sample_trends = array(
                array(
                    'title' => 'Yapay Zeka ChatGPT Güncellemeleri',
                    'category' => 'Teknoloji',
                    'search_volume' => 15000,
                    'trend_score' => 95,
                    'keywords' => array('yapay zeka', 'chatgpt', 'ai teknoloji'),
                    'related_queries' => array('chatgpt yenilikleri', 'ai gelişmeleri', 'yapay zeka haberleri')
                ),
                array(
                    'title' => 'Blockchain ve Kripto Para Gelişmeleri',
                    'category' => 'Fintech',
                    'search_volume' => 12000,
                    'trend_score' => 88,
                    'keywords' => array('blockchain', 'kripto para', 'bitcoin'),
                    'related_queries' => array('bitcoin fiyat', 'ethereum güncel', 'kripto haberler')
                ),
                array(
                    'title' => 'Metaverse ve VR Teknolojileri',
                    'category' => 'Teknoloji',
                    'search_volume' => 8500,
                    'trend_score' => 82,
                    'keywords' => array('metaverse', 'vr teknoloji', 'sanal gerçeklik'),
                    'related_queries' => array('metaverse nedir', 'vr gözlük', 'sanal dünya')
                ),
                array(
                    'title' => '5G Teknolojisi ve IoT Uygulamaları',
                    'category' => 'Telekomünikasyon',
                    'search_volume' => 7200,
                    'trend_score' => 78,
                    'keywords' => array('5g teknoloji', 'iot', 'akıllı cihazlar'),
                    'related_queries' => array('5g hız', 'iot uygulamaları', 'akıllı ev')
                ),
                array(
                    'title' => 'Siber Güvenlik ve Veri Koruma',
                    'category' => 'Güvenlik',
                    'search_volume' => 6800,
                    'trend_score' => 75,
                    'keywords' => array('siber güvenlik', 'veri koruma', 'hacker'),
                    'related_queries' => array('siber saldırı', 'veri güvenliği', 'antivirus')
                )
            );
            
            wp_send_json_success($sample_trends);
        }
    }
    
    /**
     * Haber içeriği analiz et
     */
    public function analyze_news_content() {
        check_ajax_referer('newsbot_nonce', 'nonce');
        
        $url = sanitize_url($_POST['news_url']);
        $analysis = $this->analyze_url_content($url);
        
        wp_send_json_success($analysis);
    }
    
    /**
     * İçerik önerisi oluştur
     */
    public function generate_content_suggestion() {
        check_ajax_referer('newsbot_nonce', 'nonce');
        
        $topic = sanitize_text_field($_POST['topic']);
        $keywords = array_map('sanitize_text_field', $_POST['keywords']);
        
        $suggestion = $this->create_content_outline($topic, $keywords);
        
        wp_send_json_success($suggestion);
    }
    
    /**
     * Google Trends API'den veri çek
     */
    private function fetch_google_trends() {
        // Google Trends API entegrasyonu
        $api_url = 'https://trends.googleapis.com/trends/api/dailytrends';
        $params = array(
            'hl' => 'tr-TR',
            'tz' => 180,
            'geo' => 'TR',
            'ns' => 15
        );
        
        $url = $api_url . '?' . http_build_query($params);
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'headers' => array(
                'User-Agent' => 'NewsBot/1.0'
            )
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        
        // Google Trends yanıtı işle
        if (strpos($body, ')]}\',') === 0) {
            $body = substr($body, 5);
        }
        
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['default']['trendingSearchesDays'])) {
            return false;
        }
        
        return $this->process_trends_data($data);
    }
    
    /**
     * Trends verilerini işle
     */
    private function process_trends_data($data) {
        $trends = array();
        
        if (isset($data['default']['trendingSearchesDays'][0]['trendingSearches'])) {
            $searches = $data['default']['trendingSearchesDays'][0]['trendingSearches'];
            
            foreach (array_slice($searches, 0, 10) as $search) {
                $trends[] = array(
                    'title' => $search['title']['query'],
                    'category' => isset($search['articles'][0]['source']) ? $search['articles'][0]['source'] : 'Genel',
                    'search_volume' => isset($search['formattedTraffic']) ? $this->parse_traffic($search['formattedTraffic']) : 0,
                    'trend_score' => rand(70, 100),
                    'keywords' => $this->extract_keywords($search['title']['query']),
                    'related_queries' => isset($search['relatedQueries']) ? array_slice($search['relatedQueries'], 0, 3) : array()
                );
            }
        }
        
        return $trends;
    }
    
    /**
     * URL içeriğini analiz et
     */
    private function analyze_url_content($url) {
        $response = wp_remote_get($url, array('timeout' => 30));
        
        if (is_wp_error($response)) {
            return array(
                'error' => 'URL\'ye erişilemedi',
                'suggestions' => array()
            );
        }
        
        $content = wp_remote_retrieve_body($response);
        
        // HTML'den metin çıkar
        $text = strip_tags($content);
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Anahtar kelimeleri çıkar
        $keywords = $this->extract_keywords_from_text($text);
        
        // İçerik analizi
        $analysis = array(
            'word_count' => str_word_count($text),
            'keywords' => array_slice($keywords, 0, 10),
            'readability_score' => $this->calculate_readability($text),
            'sentiment' => $this->analyze_sentiment($text),
            'topics' => $this->identify_topics($text),
            'suggestions' => $this->generate_improvement_suggestions($text, $keywords)
        );
        
        return $analysis;
    }
    
    /**
     * İçerik taslağı oluştur
     */
    private function create_content_outline($topic, $keywords) {
        $outline = array(
            'title_suggestions' => array(
                $topic . ' Hakkında Bilmeniz Gerekenler',
                $topic . ' Nedir? Kapsamlı Rehber',
                $topic . ' Trendleri ve Gelecek Öngörüleri',
                $topic . ' ile İlgili Son Gelişmeler'
            ),
            'content_structure' => array(
                array(
                    'section' => 'Giriş',
                    'content' => $topic . ' konusuna genel bakış ve önemini açıklayan giriş paragrafı.',
                    'word_count' => '150-200 kelime'
                ),
                array(
                    'section' => 'Ana Konular',
                    'content' => $topic . ' ile ilgili temel kavramlar ve detaylı açıklamalar.',
                    'word_count' => '400-500 kelime'
                ),
                array(
                    'section' => 'Güncel Gelişmeler',
                    'content' => $topic . ' alanındaki son haberler ve trendler.',
                    'word_count' => '300-400 kelime'
                ),
                array(
                    'section' => 'Sonuç',
                    'content' => 'Özet ve gelecek beklentileri.',
                    'word_count' => '100-150 kelime'
                )
            ),
            'seo_keywords' => $keywords,
            'meta_description' => $topic . ' hakkında kapsamlı bilgi. Son gelişmeler, trendler ve uzman görüşleri.',
            'estimated_read_time' => '5-7 dakika',
            'target_word_count' => '1000-1200 kelime'
        );
        
        return $outline;
    }
    
    /**
     * Metinden anahtar kelime çıkar
     */
    private function extract_keywords_from_text($text) {
        // Türkçe stop words
        $stop_words = array('ve', 'ile', 'bir', 'bu', 'şu', 'o', 'da', 'de', 'ki', 'mi', 'mu', 'mü', 'için', 'olan', 'olan', 'gibi', 'kadar', 'daha', 'en', 'çok', 'az', 'var', 'yok');
        
        // Metni kelimelere ayır
        $words = str_word_count(strtolower($text), 1, 'çğıöşüÇĞIİÖŞÜ');
        
        // Stop words'leri filtrele
        $words = array_filter($words, function($word) use ($stop_words) {
            return !in_array($word, $stop_words) && strlen($word) > 3;
        });
        
        // Kelime sıklığını hesapla
        $word_freq = array_count_values($words);
        arsort($word_freq);
        
        return array_keys(array_slice($word_freq, 0, 20));
    }
    
    /**
     * Basit anahtar kelime çıkarma
     */
    private function extract_keywords($text) {
        $words = explode(' ', strtolower($text));
        $keywords = array();
        
        foreach ($words as $word) {
            $word = trim($word, '.,!?;:"()[]{}');
            if (strlen($word) > 3) {
                $keywords[] = $word;
            }
        }
        
        return array_unique(array_slice($keywords, 0, 5));
    }
    
    /**
     * Trafik sayısını parse et
     */
    private function parse_traffic($traffic) {
        $traffic = strtolower($traffic);
        $number = (int) filter_var($traffic, FILTER_SANITIZE_NUMBER_INT);
        
        if (strpos($traffic, 'k') !== false) {
            return $number * 1000;
        } elseif (strpos($traffic, 'm') !== false) {
            return $number * 1000000;
        }
        
        return $number;
    }
    
    /**
     * Okunabilirlik skoru hesapla
     */
    private function calculate_readability($text) {
        $sentences = preg_split('/[.!?]+/', $text);
        $words = str_word_count($text);
        $syllables = $this->count_syllables($text);
        
        // Flesch Reading Ease formülü (Türkçe uyarlaması)
        $score = 206.835 - (1.015 * ($words / count($sentences))) - (84.6 * ($syllables / $words));
        
        return max(0, min(100, round($score)));
    }
    
    /**
     * Hece sayısını hesapla (basit)
     */
    private function count_syllables($text) {
        $vowels = 'aeiouAEIOUçğıöşüÇĞIİÖŞÜ';
        $syllables = 0;
        $words = str_word_count($text, 1);
        
        foreach ($words as $word) {
            $word_syllables = 0;
            for ($i = 0; $i < strlen($word); $i++) {
                if (strpos($vowels, $word[$i]) !== false) {
                    $word_syllables++;
                }
            }
            $syllables += max(1, $word_syllables);
        }
        
        return $syllables;
    }
    
    /**
     * Duygu analizi (basit)
     */
    private function analyze_sentiment($text) {
        $positive_words = array('iyi', 'güzel', 'harika', 'mükemmel', 'başarılı', 'olumlu', 'pozitif');
        $negative_words = array('kötü', 'berbat', 'başarısız', 'olumsuz', 'negatif', 'sorun', 'problem');
        
        $text = strtolower($text);
        $positive_count = 0;
        $negative_count = 0;
        
        foreach ($positive_words as $word) {
            $positive_count += substr_count($text, $word);
        }
        
        foreach ($negative_words as $word) {
            $negative_count += substr_count($text, $word);
        }
        
        if ($positive_count > $negative_count) {
            return 'pozitif';
        } elseif ($negative_count > $positive_count) {
            return 'negatif';
        } else {
            return 'nötr';
        }
    }
    
    /**
     * Konu tespiti
     */
    private function identify_topics($text) {
        $tech_keywords = array('teknoloji', 'yazılım', 'donanım', 'bilgisayar', 'internet', 'mobil', 'uygulama');
        $ai_keywords = array('yapay zeka', 'ai', 'makine öğrenmesi', 'deep learning', 'chatgpt');
        $crypto_keywords = array('bitcoin', 'ethereum', 'blockchain', 'kripto', 'nft');
        
        $text = strtolower($text);
        $topics = array();
        
        foreach ($tech_keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $topics[] = 'Teknoloji';
                break;
            }
        }
        
        foreach ($ai_keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $topics[] = 'Yapay Zeka';
                break;
            }
        }
        
        foreach ($crypto_keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $topics[] = 'Kripto Para';
                break;
            }
        }
        
        return array_unique($topics);
    }
    
    /**
     * İyileştirme önerileri
     */
    private function generate_improvement_suggestions($text, $keywords) {
        $suggestions = array();
        $word_count = str_word_count($text);
        
        if ($word_count < 300) {
            $suggestions[] = 'İçerik çok kısa. En az 300 kelime olması önerilir.';
        }
        
        if ($word_count > 2000) {
            $suggestions[] = 'İçerik çok uzun. Daha kısa paragraflar kullanın.';
        }
        
        if (empty($keywords)) {
            $suggestions[] = 'Anahtar kelime yoğunluğu düşük. SEO için anahtar kelimeler ekleyin.';
        }
        
        if (substr_count($text, '.') < $word_count / 20) {
            $suggestions[] = 'Cümleler çok uzun. Daha kısa cümleler kullanın.';
        }
        
        return $suggestions;
    }
    
    /**
     * Günlük haber taraması
     */
    public function daily_news_scan() {
        $trends = $this->fetch_google_trends();
        
        if ($trends) {
            // Trend verilerini kaydet
            update_option('newsbot_daily_trends', $trends);
            
            // E-posta bildirimi gönder (isteğe bağlı)
            $this->send_trends_notification($trends);
        }
    }
    
    /**
     * Trend bildirimi gönder
     */
    private function send_trends_notification($trends) {
        $admin_email = get_option('admin_email');
        $subject = 'NewsBot - Günlük Trend Raporu';
        
        $message = "Bugünün trend konuları:\n\n";
        foreach (array_slice($trends, 0, 5) as $trend) {
            $message .= "• " . $trend['title'] . " (Skor: " . $trend['trend_score'] . ")\n";
        }
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Günlük tarama zamanla
     */
    public function schedule_daily_scan() {
        if (!wp_next_scheduled('newsbot_daily_news_analysis')) {
            wp_schedule_event(time(), 'daily', 'newsbot_daily_news_analysis');
        }
    }
}
?>