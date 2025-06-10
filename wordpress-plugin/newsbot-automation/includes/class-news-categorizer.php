<?php
/**
 * Haber Kategorilendirici sınıfı - Sekme bazlı kategori sistemi
 */

if (!defined('ABSPATH')) {
    exit;
}

class NewsBot_News_Categorizer {
    
    private $categories;
    private $news_sources;
    
    public function __construct() {
        $this->init_categories();
        add_action('wp_ajax_newsbot_get_categorized_news', array($this, 'get_categorized_news'));
        add_action('wp_ajax_newsbot_refresh_category', array($this, 'refresh_category_news'));
        add_action('wp_ajax_newsbot_add_custom_category', array($this, 'add_custom_category'));
    }
    
    /**
     * Kategorileri başlat
     */
    private function init_categories() {
        $this->categories = array(
            'yapay_zeka' => array(
                'name' => 'Yapay Zeka',
                'icon' => '🤖',
                'keywords' => array('yapay zeka', 'ai', 'artificial intelligence', 'chatgpt', 'machine learning', 'deep learning'),
                'sources' => array('techcrunch.com', 'venturebeat.com', 'ai.googleblog.com'),
                'color' => '#3B82F6'
            ),
            'blockchain' => array(
                'name' => 'Blockchain & Kripto',
                'icon' => '₿',
                'keywords' => array('blockchain', 'bitcoin', 'ethereum', 'kripto', 'cryptocurrency', 'nft', 'defi'),
                'sources' => array('coindesk.com', 'cointelegraph.com', 'decrypt.co'),
                'color' => '#F59E0B'
            ),
            'mobil' => array(
                'name' => 'Mobil Teknoloji',
                'icon' => '📱',
                'keywords' => array('iphone', 'android', 'samsung', 'xiaomi', 'mobil uygulama', 'ios', 'google play'),
                'sources' => array('gsmarena.com', 'androidcentral.com', '9to5mac.com'),
                'color' => '#10B981'
            ),
            'oyun' => array(
                'name' => 'Oyun Teknolojisi',
                'icon' => '🎮',
                'keywords' => array('gaming', 'playstation', 'xbox', 'nintendo', 'steam', 'esports', 'vr gaming'),
                'sources' => array('ign.com', 'gamespot.com', 'polygon.com'),
                'color' => '#8B5CF6'
            ),
            'siber_guvenlik' => array(
                'name' => 'Siber Güvenlik',
                'icon' => '🔒',
                'keywords' => array('cybersecurity', 'hacking', 'malware', 'ransomware', 'data breach', 'privacy'),
                'sources' => array('krebsonsecurity.com', 'threatpost.com', 'darkreading.com'),
                'color' => '#EF4444'
            ),
            'startup' => array(
                'name' => 'Startup & Fintech',
                'icon' => '🚀',
                'keywords' => array('startup', 'fintech', 'venture capital', 'funding', 'ipo', 'unicorn'),
                'sources' => array('techcrunch.com', 'crunchbase.com', 'pitchbook.com'),
                'color' => '#06B6D4'
            ),
            'bilim' => array(
                'name' => 'Bilim & Araştırma',
                'icon' => '🔬',
                'keywords' => array('research', 'science', 'innovation', 'patent', 'laboratory', 'discovery'),
                'sources' => array('nature.com', 'sciencedaily.com', 'newscientist.com'),
                'color' => '#84CC16'
            ),
            'sosyal_medya' => array(
                'name' => 'Sosyal Medya',
                'icon' => '📲',
                'keywords' => array('facebook', 'instagram', 'twitter', 'tiktok', 'youtube', 'linkedin', 'social media'),
                'sources' => array('socialmediatoday.com', 'sproutsocial.com', 'hootsuite.com'),
                'color' => '#EC4899'
            )
        );
        
        $this->news_sources = array(
            'tr' => array(
                'webtekno.com',
                'shiftdelete.net',
                'donanimhaber.com',
                'teknoblog.com',
                'log.com.tr',
                'chip.com.tr'
            ),
            'global' => array(
                'techcrunch.com',
                'theverge.com',
                'wired.com',
                'arstechnica.com',
                'engadget.com',
                'zdnet.com'
            )
        );
    }
    
    /**
     * Kategorilere göre haberleri getir
     */
    public function get_categorized_news() {
        check_ajax_referer('newsbot_nonce', 'nonce');
        
        $category = sanitize_text_field($_POST['category']);
        $limit = intval($_POST['limit']) ?: 10;
        
        if (!isset($this->categories[$category])) {
            wp_send_json_error('Geçersiz kategori');
        }
        
        $news = $this->fetch_category_news($category, $limit);
        
        wp_send_json_success($news);
    }
    
    /**
     * Kategori haberlerini yenile
     */
    public function refresh_category_news() {
        check_ajax_referer('newsbot_nonce', 'nonce');
        
        $category = sanitize_text_field($_POST['category']);
        
        if (!isset($this->categories[$category])) {
            wp_send_json_error('Geçersiz kategori');
        }
        
        // Cache'i temizle
        delete_transient('newsbot_news_' . $category);
        
        $news = $this->fetch_category_news($category, 15);
        
        wp_send_json_success($news);
    }
    
    /**
     * Özel kategori ekle
     */
    public function add_custom_category() {
        check_ajax_referer('newsbot_nonce', 'nonce');
        
        $name = sanitize_text_field($_POST['name']);
        $keywords = array_map('sanitize_text_field', $_POST['keywords']);
        $icon = sanitize_text_field($_POST['icon']);
        $color = sanitize_hex_color($_POST['color']);
        
        $custom_categories = get_option('newsbot_custom_categories', array());
        $category_key = sanitize_key($name);
        
        $custom_categories[$category_key] = array(
            'name' => $name,
            'icon' => $icon,
            'keywords' => $keywords,
            'color' => $color,
            'custom' => true
        );
        
        update_option('newsbot_custom_categories', $custom_categories);
        
        wp_send_json_success('Kategori eklendi');
    }
    
    /**
     * Kategori haberlerini çek
     */
    private function fetch_category_news($category, $limit) {
        // Cache kontrolü
        $cache_key = 'newsbot_news_' . $category;
        $cached_news = get_transient($cache_key);
        
        if ($cached_news !== false) {
            return array_slice($cached_news, 0, $limit);
        }
        
        $category_data = $this->categories[$category];
        $news = array();
        
        // Google News API simülasyonu
        $sample_news = $this->generate_sample_news($category_data);
        
        // Gerçek API entegrasyonu için
        // $news = $this->fetch_from_news_api($category_data);
        
        $news = $sample_news;
        
        // Cache'e kaydet (1 saat)
        set_transient($cache_key, $news, HOUR_IN_SECONDS);
        
        return array_slice($news, 0, $limit);
    }
    
    /**
     * Örnek haber verisi oluştur
     */
    private function generate_sample_news($category_data) {
        $news_templates = array(
            'yapay_zeka' => array(
                'ChatGPT\'ye Yeni Özellikler Eklendi',
                'Google\'ın Yeni AI Modeli Gemini Tanıtıldı',
                'Microsoft Copilot\'a Büyük Güncelleme',
                'OpenAI\'dan Devrim Niteliğinde Açıklama',
                'Yapay Zeka Etiği Konusunda Yeni Düzenlemeler',
                'AI Destekli Kod Yazma Araçları Popülerleşiyor',
                'Makine Öğrenmesi ile Hastalık Teşhisi',
                'Otonom Araçlarda AI Teknolojisi Gelişiyor'
            ),
            'blockchain' => array(
                'Bitcoin Fiyatında Büyük Hareket',
                'Ethereum 2.0 Güncellemesi Tamamlandı',
                'Yeni NFT Projesi Rekor Kırdı',
                'DeFi Protokollerinde Güvenlik Açığı',
                'Merkez Bankası Dijital Para Birimi Açıklaması',
                'Blockchain Teknolojisi Sağlık Sektöründe',
                'Kripto Para Düzenlemeleri Güncellendi',
                'Web3 Projeleri Yatırım Çekmeye Devam Ediyor'
            ),
            'mobil' => array(
                'iPhone 16 Özellikleri Sızdırıldı',
                'Samsung Galaxy S24 Tanıtım Tarihi Açıklandı',
                'Android 15 Beta Sürümü Yayınlandı',
                'Xiaomi\'nin Yeni Flagship Modeli Geliyor',
                'Google Pixel 8 Kamera Testleri',
                'iOS 18 Gizli Özellikleri Keşfedildi',
                'Katlanabilir Telefon Pazarı Büyüyor',
                '5G Teknolojisi Yaygınlaşmaya Devam Ediyor'
            ),
            'oyun' => array(
                'PlayStation 6 Geliştirme Süreci Başladı',
                'Xbox Game Pass\'e Yeni Oyunlar Eklendi',
                'Nintendo Switch 2 Söylentileri Güçleniyor',
                'Steam Deck OLED Modeli Duyuruldu',
                'Epic Games Store Ücretsiz Oyun Kampanyası',
                'VR Oyun Pazarı 2024 Tahminleri',
                'Esports Turnuva Ödül Havuzu Rekor Kırdı',
                'Cloud Gaming Servisleri Karşılaştırması'
            ),
            'siber_guvenlik' => array(
                'Büyük Şirkette Veri Sızıntısı Tespit Edildi',
                'Yeni Ransomware Türü Keşfedildi',
                'Siber Güvenlik Bütçeleri Artırılıyor',
                'Phishing Saldırıları %300 Arttı',
                'Zero-Day Açığı Acil Güncelleme Gerektiriyor',
                'Quantum Bilgisayarlar ve Şifreleme Güvenliği',
                'IoT Cihazlarda Güvenlik Açıkları',
                'Biometric Kimlik Doğrulama Sistemleri'
            ),
            'startup' => array(
                'Türk Startup\'ı 50 Milyon Dolar Yatırım Aldı',
                'Fintech Unicorn\'u Halka Arz Hazırlığında',
                'Venture Capital Fonları Rekor Büyüklükte',
                'Y Combinator\'dan Yeni Mezunlar',
                'Startup Ekosistemi 2024 Raporu Yayınlandı',
                'Girişimcilik Destekleri Artırıldı',
                'Teknoloji Transfer Ofisleri Güçlendiriliyor',
                'Angel Yatırımcı Ağları Genişliyor'
            ),
            'bilim' => array(
                'Quantum Bilgisayarda Yeni Rekor',
                'Mars Misyonunda Önemli Keşif',
                'Gen Terapisinde Çığır Açan Gelişme',
                'Yenilenebilir Enerji Teknolojileri',
                'Nanotteknoloji Uygulamaları Genişliyor',
                'İklim Değişikliği Araştırmaları',
                'Uzay Teknolojilerinde Yenilik',
                'Biyomedikal Mühendisliği Atılımları'
            ),
            'sosyal_medya' => array(
                'Instagram\'a Yeni Özellik Geldi',
                'Twitter X Rebrand\'i Tamamlandı',
                'TikTok Algoritması Güncellendi',
                'YouTube Shorts Monetizasyon Seçenekleri',
                'LinkedIn\'de AI Destekli Özellikler',
                'Facebook Meta Verse Yatırımları',
                'Sosyal Medya Düzenlemeleri Sıkılaştırıldı',
                'İçerik Üreticileri için Yeni Araçlar'
            )
        );
        
        $sources = array('TechCrunch', 'The Verge', 'Wired', 'Ars Technica', 'Engadget', 'ZDNet', 'WebTekno', 'ShiftDelete');
        
        $news = array();
        $templates = isset($news_templates[$category]) ? $news_templates[$category] : $news_templates['yapay_zeka'];
        
        foreach ($templates as $index => $title) {
            $news[] = array(
                'id' => uniqid(),
                'title' => $title,
                'source' => $sources[array_rand($sources)],
                'url' => 'https://example.com/news/' . $index,
                'published_at' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 24) . ' hours')),
                'summary' => $this->generate_news_summary($title),
                'category' => $category_data['name'],
                'image' => 'https://images.pexels.com/photos/373543/pexels-photo-373543.jpeg',
                'reading_time' => rand(2, 8) . ' dk',
                'engagement_score' => rand(60, 95)
            );
        }
        
        return $news;
    }
    
    /**
     * Haber özeti oluştur
     */
    private function generate_news_summary($title) {
        $summaries = array(
            'Son gelişmeler teknoloji dünyasında büyük yankı uyandırdı. Uzmanlar konuyla ilgili önemli açıklamalarda bulundu.',
            'Sektörde yaşanan bu gelişme, kullanıcılar ve yatırımcılar tarafından büyük ilgiyle karşılandı.',
            'Teknoloji devlerinin bu alandaki rekabeti giderek kızışıyor. Yeni açıklamalar bekleniyor.',
            'Araştırmacılar ve sektör uzmanları bu gelişmenin etkilerini değerlendirmeye devam ediyor.',
            'Pazar analisti raporlarına göre bu trend önümüzdeki dönemde de devam edecek.'
        );
        
        return $summaries[array_rand($summaries)];
    }
    
    /**
     * Tüm kategorileri getir
     */
    public function get_all_categories() {
        $custom_categories = get_option('newsbot_custom_categories', array());
        return array_merge($this->categories, $custom_categories);
    }
    
    /**
     * Kategori istatistikleri
     */
    public function get_category_stats($category) {
        $news = $this->fetch_category_news($category, 50);
        
        $stats = array(
            'total_news' => count($news),
            'avg_engagement' => 0,
            'top_sources' => array(),
            'trending_keywords' => array(),
            'last_updated' => current_time('mysql')
        );
        
        if (!empty($news)) {
            $total_engagement = array_sum(array_column($news, 'engagement_score'));
            $stats['avg_engagement'] = round($total_engagement / count($news));
            
            // En çok haber yapan kaynaklar
            $sources = array_count_values(array_column($news, 'source'));
            arsort($sources);
            $stats['top_sources'] = array_slice($sources, 0, 5, true);
        }
        
        return $stats;
    }
    
    /**
     * Haber filtreleme
     */
    public function filter_news($category, $filters) {
        $news = $this->fetch_category_news($category, 100);
        
        // Tarih filtresi
        if (isset($filters['date_from'])) {
            $news = array_filter($news, function($item) use ($filters) {
                return strtotime($item['published_at']) >= strtotime($filters['date_from']);
            });
        }
        
        // Kaynak filtresi
        if (isset($filters['source']) && !empty($filters['source'])) {
            $news = array_filter($news, function($item) use ($filters) {
                return $item['source'] === $filters['source'];
            });
        }
        
        // Engagement skoru filtresi
        if (isset($filters['min_engagement'])) {
            $news = array_filter($news, function($item) use ($filters) {
                return $item['engagement_score'] >= $filters['min_engagement'];
            });
        }
        
        return array_values($news);
    }
}
?>