<?php
/**
 * Haber Kategorilendirici sınıfı - Her kategori için farklı içerikler
 */

if (!defined('ABSPATH')) {
    exit;
}

class NewsBot_News_Categorizer {
    
    private $categories;
    
    public function __construct() {
        $this->init_categories();
        add_action('wp_ajax_newsbot_get_categorized_news', array($this, 'get_categorized_news'));
        add_action('wp_ajax_newsbot_refresh_category', array($this, 'refresh_category_news'));
    }
    
    /**
     * Kategorileri başlat
     */
    private function init_categories() {
        $this->categories = array(
            'yapay_zeka' => array(
                'name' => 'Yapay Zeka',
                'icon' => '🤖',
                'color' => '#3B82F6',
                'news' => array(
                    'ChatGPT-4 Turbo Yeni Özelliklerle Güncellendi',
                    'Google Gemini Ultra Performans Testleri Şaşırttı',
                    'Microsoft Copilot Enterprise Sürümü Duyuruldu',
                    'OpenAI Sora Video Üretim AI\'sı Beta\'da',
                    'Meta AI Llama 3 Modeli Açık Kaynak Oldu',
                    'Anthropic Claude 3 Opus Rekora Koşuyor',
                    'Midjourney V6 Gerçekçilik Seviyesini Artırdı',
                    'Stability AI Video Üretimde Çığır Açtı',
                    'Perplexity AI Arama Motoru Özelliği Ekledi',
                    'Character.AI Sesli Sohbet Özelliği Getirdi'
                )
            ),
            'blockchain' => array(
                'name' => 'Blockchain & Kripto',
                'icon' => '₿',
                'color' => '#F59E0B',
                'news' => array(
                    'Bitcoin ETF Onayları Piyasayı Hareketlendirdi',
                    'Ethereum Shanghai Güncellemesi Tamamlandı',
                    'Solana Ağında İşlem Hacmi Rekor Kırdı',
                    'Polygon zkEVM Ana Ağa Geçti',
                    'Cardano Chang Hard Fork Tarihi Açıklandı',
                    'Avalanche Subnet Teknolojisi Yaygınlaşıyor',
                    'Chainlink CCIP Köprü Protokolü Aktif',
                    'Uniswap V4 Hook Sistemi Devrim Yaratacak',
                    'Aave V3 Yeni Zincir Entegrasyonları',
                    'The Graph Subgraph Studio Güncellendi'
                )
            ),
            'mobil' => array(
                'name' => 'Mobil Teknoloji',
                'icon' => '📱',
                'color' => '#10B981',
                'news' => array(
                    'iPhone 16 Pro Max Kamera Özellikleri Sızdı',
                    'Samsung Galaxy S25 Ultra Tasarım Değişikliği',
                    'Google Pixel 9 AI Özellikler Paketi',
                    'Xiaomi 15 Pro Snapdragon 8 Gen 4 ile Geliyor',
                    'OnePlus 13 Hızlı Şarj Teknolojisi Rekoru',
                    'Huawei Mate 70 HarmonyOS NEXT ile',
                    'Oppo Find X8 Periskop Kamera Sistemi',
                    'Vivo X200 Pro Zeiss Ortaklığı Devam',
                    'Realme GT 7 Pro Gaming Performansı',
                    'Nothing Phone 3 Şeffaf Tasarım Yeniliği'
                )
            ),
            'oyun' => array(
                'name' => 'Oyun Teknolojisi',
                'icon' => '🎮',
                'color' => '#8B5CF6',
                'news' => array(
                    'PlayStation 6 Geliştirme Kiti Sızdırıldı',
                    'Xbox Series X Pro Söylentileri Güçleniyor',
                    'Nintendo Switch 2 OLED Ekran Detayları',
                    'Steam Deck OLED 2 Performans Artışı',
                    'ASUS ROG Ally X Handheld Konsol Duyuru',
                    'Meta Quest 4 VR Gözlük Özellikleri',
                    'Apple Vision Pro Gaming Desteği Genişliyor',
                    'NVIDIA RTX 5090 Oyun Performans Testleri',
                    'AMD RDNA 4 GPU Mimarisi Detayları',
                    'Intel Arc B580 Orta Segment GPU Lansmanı'
                )
            ),
            'siber_guvenlik' => array(
                'name' => 'Siber Güvenlik',
                'icon' => '🔒',
                'color' => '#EF4444',
                'news' => array(
                    'Microsoft Exchange Zero-Day Açığı Keşfedildi',
                    'Chrome 131 Kritik Güvenlik Güncellemesi',
                    'LastPass Veri Sızıntısı Soruşturması Devam',
                    'Kaspersky Antivirus ABD Yasağı Kararı',
                    'CrowdStrike Falcon Sensor Güncelleme Sorunu',
                    'Fortinet FortiGate Güvenlik Duvarı Açığı',
                    'Palo Alto Networks PAN-OS Güvenlik Yaması',
                    'Cisco IOS XE Backdoor Saldırısı Tespit',
                    'VMware vCenter Server RCE Açığı Uyarısı',
                    'SolarWinds Orion Platform Güvenlik Güncellemesi'
                )
            ),
            'startup' => array(
                'name' => 'Startup & Fintech',
                'icon' => '🚀',
                'color' => '#06B6D4',
                'news' => array(
                    'Türk Fintech Startup\'ı 100M$ Yatırım Aldı',
                    'Klarna IPO Hazırlıkları Hızlandı',
                    'Stripe Kripto Para Ödeme Desteği Ekledi',
                    'Revolut Bankacılık Lisansı Aldı',
                    'Wise Para Transfer Ücretlerini Düşürdü',
                    'PayPal Stablecoin PYUSD Lansmanı',
                    'Square Block Bitcoin Mining Yatırımı',
                    'Robinhood Kripto Wallet Özelliği Aktif',
                    'Coinbase Base L2 Ağı Büyüyor',
                    'Binance Türkiye Operasyonları Genişliyor'
                )
            ),
            'bilim' => array(
                'name' => 'Bilim & Araştırma',
                'icon' => '🔬',
                'color' => '#84CC16',
                'news' => array(
                    'CERN LHC Yeni Parçacık Keşfi Açıklaması',
                    'NASA Artemis 3 Ay Misyonu Güncellemesi',
                    'SpaceX Starship IFT-6 Test Uçuşu Başarılı',
                    'James Webb Teleskop Yeni Galaksi Keşfi',
                    'MIT Quantum Bilgisayar Çığır Açan Gelişme',
                    'Google Quantum Supremacy Yeni Rekor',
                    'TSMC 2nm Çip Üretim Teknolojisi Hazır',
                    'Intel 18A Process Node Geliştirme Süreci',
                    'Samsung 3nm GAA Teknolojisi Yaygınlaşıyor',
                    'ASML EUV Lithography Yeni Nesil Makineler'
                )
            ),
            'sosyal_medya' => array(
                'name' => 'Sosyal Medya',
                'icon' => '📲',
                'color' => '#EC4899',
                'news' => array(
                    'Instagram Threads Kullanıcı Sayısı 200M\'yi Aştı',
                    'X (Twitter) Premium+ Abonelik Modeli Duyuru',
                    'TikTok Shop E-ticaret Entegrasyonu Genişliyor',
                    'YouTube Shorts Monetizasyon Seçenekleri',
                    'LinkedIn AI Destekli İçerik Önerileri',
                    'Facebook Meta AI Chatbot Entegrasyonu',
                    'Snapchat AR Lens Studio Güncellendi',
                    'Discord Nitro Yeni Özellikler Paketi',
                    'Telegram Premium Abonelik Artışı',
                    'WhatsApp Business API Yeni Özellikler'
                )
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
        
        $news = $this->fetch_category_news($category, 15);
        
        wp_send_json_success($news);
    }
    
    /**
     * Kategori haberlerini çek
     */
    private function fetch_category_news($category, $limit) {
        $category_data = $this->categories[$category];
        $news_titles = $category_data['news'];
        
        $news = array();
        $sources = array('TechCrunch', 'The Verge', 'Wired', 'Ars Technica', 'Engadget', 'ZDNet');
        
        // Haberleri karıştır ve sınırla
        shuffle($news_titles);
        $selected_titles = array_slice($news_titles, 0, $limit);
        
        foreach ($selected_titles as $index => $title) {
            $news[] = array(
                'id' => uniqid($category . '_'),
                'title' => $title,
                'source' => $sources[array_rand($sources)],
                'url' => 'https://example.com/news/' . $category . '/' . $index,
                'published_at' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 48) . ' hours')),
                'summary' => $this->generate_news_summary($title, $category),
                'category' => $category_data['name'],
                'reading_time' => rand(2, 8) . ' dk',
                'engagement_score' => rand(60, 95),
                'keywords' => $this->extract_keywords_from_title($title)
            );
        }
        
        return $news;
    }
    
    /**
     * Haber özeti oluştur
     */
    private function generate_news_summary($title, $category) {
        $summaries = array(
            'yapay_zeka' => array(
                'AI teknolojisinde yaşanan bu gelişme, sektörde büyük yankı uyandırdı.',
                'Makine öğrenmesi alanındaki bu yenilik, kullanıcı deneyimini değiştirecek.',
                'Yapay zeka modellerinde kaydedilen bu ilerleme, rekabeti kızıştıracak.',
                'Deep learning teknolojisindeki bu atılım, yeni fırsatlar yaratıyor.'
            ),
            'blockchain' => array(
                'Blockchain ekosistemindeki bu gelişme, yatırımcıları heyecanlandırdı.',
                'Kripto para piyasasında yaşanan bu hareket, analisti şaşırttı.',
                'DeFi protokollerindeki bu yenilik, likidite havuzlarını etkileyecek.',
                'Web3 teknolojisindeki bu adım, merkeziyetsizliği güçlendiriyor.'
            ),
            'mobil' => array(
                'Mobil teknolojide yaşanan bu gelişme, kullanıcı beklentilerini artırdı.',
                'Smartphone pazarındaki bu yenilik, rekabeti yeniden şekillendirecek.',
                'Mobil işlemci teknolojisindeki bu atılım, performans sınırlarını zorluyor.',
                'Kamera teknolojisindeki bu gelişme, fotoğrafçılığı değiştirecek.'
            ),
            'oyun' => array(
                'Gaming endüstrisindeki bu gelişme, oyuncuları heyecanlandırdı.',
                'Konsol pazarındaki bu yenilik, next-gen deneyimi tanımlayacak.',
                'GPU teknolojisindeki bu atılım, grafik kalitesini artıracak.',
                'VR gaming alanındaki bu adım, immersive deneyimi güçlendiriyor.'
            ),
            'siber_guvenlik' => array(
                'Siber güvenlik alanındaki bu gelişme, uzmanları endişelendirdi.',
                'Güvenlik açığı konusundaki bu keşif, acil önlem gerektiriyor.',
                'Malware teknolojisindeki bu evrim, savunma stratejilerini zorluyor.',
                'Veri koruma alanındaki bu yenilik, privacy standartlarını artırıyor.'
            ),
            'startup' => array(
                'Startup ekosistemindeki bu gelişme, girişimcileri umutlandırdı.',
                'Fintech sektöründeki bu yenilik, finansal hizmetleri dönüştürecek.',
                'Venture capital piyasasındaki bu hareket, yatırım trendlerini belirliyor.',
                'Teknoloji girişimlerindeki bu başarı, sektöre ilham veriyor.'
            ),
            'bilim' => array(
                'Bilimsel araştırmadaki bu keşif, akademik dünyayı heyecanlandırdı.',
                'Teknoloji geliştirmedeki bu atılım, endüstriyel uygulamaları etkileyecek.',
                'Quantum teknolojisindeki bu ilerleme, hesaplama gücünü devrimleştirecek.',
                'Uzay teknolojisindeki bu başarı, keşif misyonlarını hızlandıracak.'
            ),
            'sosyal_medya' => array(
                'Sosyal medya platformundaki bu yenilik, kullanıcı etkileşimini artıracak.',
                'İçerik üretimi alanındaki bu gelişme, creator ekonomisini etkileyecek.',
                'Platform algoritmasındaki bu değişiklik, reach oranlarını değiştirecek.',
                'Sosyal ticaret alanındaki bu adım, e-commerce\'i dönüştürüyor.'
            )
        );
        
        $category_summaries = isset($summaries[$category]) ? $summaries[$category] : $summaries['yapay_zeka'];
        return $category_summaries[array_rand($category_summaries)];
    }
    
    /**
     * Başlıktan anahtar kelime çıkar
     */
    private function extract_keywords_from_title($title) {
        $keywords = array();
        $words = explode(' ', strtolower($title));
        
        foreach ($words as $word) {
            $word = trim($word, '.,!?;:"()[]{}');
            if (strlen($word) > 3) {
                $keywords[] = $word;
            }
        }
        
        return array_slice($keywords, 0, 3);
    }
    
    /**
     * Tüm kategorileri getir
     */
    public function get_all_categories() {
        return $this->categories;
    }
}
?>