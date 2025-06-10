<?php
/**
 * Başlık ve Kelime Jeneratörü sınıfı
 */

if (!defined('ABSPATH')) {
    exit;
}

class NewsBot_Title_Generator {
    
    private $title_templates;
    private $power_words;
    private $tech_keywords;
    
    public function __construct() {
        $this->init_templates();
        add_action('wp_ajax_newsbot_generate_titles', array($this, 'generate_titles'));
        add_action('wp_ajax_newsbot_generate_keywords', array($this, 'generate_keywords'));
        add_action('wp_ajax_newsbot_analyze_title', array($this, 'analyze_title'));
    }
    
    /**
     * Şablonları başlat
     */
    private function init_templates() {
        $this->title_templates = array(
            'news' => array(
                '%s: Son Gelişmeler ve Detaylar',
                '%s Hakkında Bilmeniz Gereken %d Şey',
                '%s ile İlgili Çarpıcı Gelişme',
                '%s Dünyasında Devrim Niteliğinde Yenilik',
                '%s Sektöründe Büyük Değişim',
                'Uzmanlar %s Konusunda Uyarıyor',
                '%s Teknolojisinde Yeni Dönem',
                '%s ile Gelen Fırsatlar ve Tehditler'
            ),
            'tutorial' => array(
                '%s Nasıl Yapılır? Adım Adım Rehber',
                '%s için Başlangıç Rehberi',
                '%s Öğrenmek İsteyenler İçin %d İpucu',
                '%s Konusunda Uzman Olmak',
                '%s ile İlgili Bilmeniz Gereken Her Şey',
                '%s Rehberi: Sıfırdan İleri Seviyeye',
                '%s için Pratik Çözümler',
                '%s Konusunda Başarılı Olmanın Yolları'
            ),
            'review' => array(
                '%s İncelemesi: Artıları ve Eksileri',
                '%s Değerlendirmesi: Alınır mı?',
                '%s Hakkında Dürüst İnceleme',
                '%s Test Ettik: İşte Sonuçlar',
                '%s vs Rakipleri: Karşılaştırma',
                '%s İncelemesi: %d Ay Sonra',
                '%s Deneyimi: Kullanıcı Yorumları',
                '%s Analizi: Detaylı Değerlendirme'
            ),
            'listicle' => array(
                '%d En İyi %s Önerisi',
                '%s için %d Harika Alternatif',
                '%d %s Trendi',
                '%s Kategorisinde %d Favori',
                '%d Popüler %s Seçeneği',
                '%s için %d Vazgeçilmez Araç',
                '%d %s İpucu',
                '%s Dünyasından %d Örnek'
            ),
            'question' => array(
                '%s Nedir ve Neden Önemli?',
                '%s Nasıl Çalışır?',
                '%s Güvenli mi?',
                '%s Ne Zaman Kullanılır?',
                '%s Hangi Durumlarda Tercih Edilir?',
                '%s ile Neler Yapılabilir?',
                '%s Kimler İçin Uygun?',
                '%s Geleceği Nasıl Şekillendirecek?'
            )
        );
        
        $this->power_words = array(
            'çarpıcı', 'devrim niteliğinde', 'şaşırtıcı', 'inanılmaz', 'muhteşem',
            'kritik', 'önemli', 'vazgeçilmez', 'etkili', 'güçlü', 'hızlı',
            'kolay', 'basit', 'pratik', 'ücretsiz', 'yeni', 'son', 'güncel',
            'kapsamlı', 'detaylı', 'eksiksiz', 'tam', 'profesyonel', 'uzman'
        );
        
        $this->tech_keywords = array(
            'yapay zeka', 'blockchain', 'kripto para', 'metaverse', 'nft',
            'bulut bilişim', 'siber güvenlik', 'veri analizi', 'makine öğrenmesi',
            'iot', 'robotik', 'otomasyon', 'dijital dönüşüm', 'fintech',
            'e-ticaret', 'sosyal medya', 'mobil uygulama', 'web tasarım',
            'seo', 'dijital pazarlama', 'startup', 'teknoloji', 'inovasyon'
        );
    }
    
    /**
     * Başlık önerileri üret
     */
    public function generate_titles() {
        check_ajax_referer('newsbot_nonce', 'nonce');
        
        $keyword = sanitize_text_field($_POST['keyword']);
        $type = sanitize_text_field($_POST['type']);
        $count = intval($_POST['count']) ?: 10;
        
        $titles = $this->create_title_variations($keyword, $type, $count);
        
        wp_send_json_success($titles);
    }
    
    /**
     * Anahtar kelime önerileri üret
     */
    public function generate_keywords() {
        check_ajax_referer('newsbot_nonce', 'nonce');
        
        $topic = sanitize_text_field($_POST['topic']);
        $count = intval($_POST['count']) ?: 20;
        
        $keywords = $this->create_keyword_variations($topic, $count);
        
        wp_send_json_success($keywords);
    }
    
    /**
     * Başlık analizi
     */
    public function analyze_title() {
        check_ajax_referer('newsbot_nonce', 'nonce');
        
        $title = sanitize_text_field($_POST['title']);
        $analysis = $this->analyze_title_effectiveness($title);
        
        wp_send_json_success($analysis);
    }
    
    /**
     * Başlık varyasyonları oluştur
     */
    private function create_title_variations($keyword, $type, $count) {
        $titles = array();
        $templates = isset($this->title_templates[$type]) ? $this->title_templates[$type] : $this->title_templates['news'];
        
        // Temel şablonlar
        foreach ($templates as $template) {
            if (strpos($template, '%d') !== false) {
                $number = rand(3, 15);
                $titles[] = sprintf($template, $keyword, $number);
            } else {
                $titles[] = sprintf($template, $keyword);
            }
        }
        
        // Power words ile kombinasyonlar
        foreach (array_slice($this->power_words, 0, 5) as $power_word) {
            $titles[] = ucfirst($power_word) . ' ' . $keyword . ' Rehberi';
            $titles[] = $keyword . ': ' . ucfirst($power_word) . ' Bilgiler';
        }
        
        // Sayı bazlı başlıklar
        for ($i = 0; $i < 3; $i++) {
            $number = rand(5, 20);
            $titles[] = $keyword . ' için ' . $number . ' İpucu';
            $titles[] = $number . ' Adımda ' . $keyword . ' Öğrenin';
        }
        
        // Yıl bazlı başlıklar
        $current_year = date('Y');
        $titles[] = $current_year . ' ' . $keyword . ' Trendleri';
        $titles[] = $current_year . '\'te ' . $keyword . ' Nasıl Kullanılır?';
        
        // Karşılaştırma başlıkları
        $competitors = array('alternatif', 'rakip', 'seçenek');
        foreach ($competitors as $comp) {
            $titles[] = $keyword . ' vs ' . ucfirst($comp) . 'leri';
        }
        
        // Benzersiz başlıklar oluştur
        $titles = array_unique($titles);
        shuffle($titles);
        
        return array_slice($titles, 0, $count);
    }
    
    /**
     * Anahtar kelime varyasyonları oluştur
     */
    private function create_keyword_variations($topic, $count) {
        $keywords = array();
        
        // Ana kelime
        $keywords[] = $topic;
        
        // Uzun kuyruk anahtar kelimeler
        $modifiers = array(
            'nedir', 'nasıl', 'ne demek', 'örnekleri', 'türleri', 'çeşitleri',
            'avantajları', 'dezavantajları', 'kullanımı', 'uygulaması',
            'rehberi', 'kılavuzu', 'eğitimi', 'kursu', 'öğrenme',
            'fiyatları', 'maliyeti', 'ücretsiz', 'en iyi', 'tavsiye'
        );
        
        foreach ($modifiers as $modifier) {
            $keywords[] = $topic . ' ' . $modifier;
            $keywords[] = $modifier . ' ' . $topic;
        }
        
        // Teknoloji kombinasyonları
        foreach ($this->tech_keywords as $tech_keyword) {
            if (stripos($tech_keyword, $topic) === false && stripos($topic, $tech_keyword) === false) {
                $keywords[] = $topic . ' ' . $tech_keyword;
                $keywords[] = $tech_keyword . ' ' . $topic;
            }
        }
        
        // Soru formatları
        $question_words = array('nasıl', 'neden', 'ne zaman', 'nerede', 'hangi');
        foreach ($question_words as $q_word) {
            $keywords[] = $q_word . ' ' . $topic;
        }
        
        // Yıl bazlı
        $current_year = date('Y');
        $keywords[] = $current_year . ' ' . $topic;
        $keywords[] = $topic . ' ' . $current_year;
        
        // Benzersiz anahtar kelimeler
        $keywords = array_unique($keywords);
        $keywords = array_filter($keywords, function($keyword) {
            return strlen($keyword) >= 3 && strlen($keyword) <= 60;
        });
        
        shuffle($keywords);
        
        return array_slice($keywords, 0, $count);
    }
    
    /**
     * Başlık etkinliği analizi
     */
    private function analyze_title_effectiveness($title) {
        $analysis = array(
            'score' => 0,
            'length' => strlen($title),
            'word_count' => str_word_count($title),
            'has_power_words' => false,
            'has_numbers' => false,
            'has_question' => false,
            'emotional_impact' => 'düşük',
            'seo_score' => 0,
            'suggestions' => array()
        );
        
        // Uzunluk kontrolü
        if ($analysis['length'] >= 30 && $analysis['length'] <= 60) {
            $analysis['score'] += 20;
        } else {
            $analysis['suggestions'][] = 'Başlık 30-60 karakter arasında olmalı (şu an: ' . $analysis['length'] . ')';
        }
        
        // Kelime sayısı
        if ($analysis['word_count'] >= 4 && $analysis['word_count'] <= 12) {
            $analysis['score'] += 15;
        } else {
            $analysis['suggestions'][] = 'Başlık 4-12 kelime arasında olmalı';
        }
        
        // Power words kontrolü
        foreach ($this->power_words as $power_word) {
            if (stripos($title, $power_word) !== false) {
                $analysis['has_power_words'] = true;
                $analysis['score'] += 15;
                break;
            }
        }
        
        if (!$analysis['has_power_words']) {
            $analysis['suggestions'][] = 'Güçlü kelimeler ekleyin (örn: ' . implode(', ', array_slice($this->power_words, 0, 3)) . ')';
        }
        
        // Sayı kontrolü
        if (preg_match('/\d+/', $title)) {
            $analysis['has_numbers'] = true;
            $analysis['score'] += 10;
        } else {
            $analysis['suggestions'][] = 'Sayılar tıklama oranını artırır';
        }
        
        // Soru kontrolü
        $question_indicators = array('?', 'nasıl', 'neden', 'ne', 'hangi', 'kim', 'nerede');
        foreach ($question_indicators as $indicator) {
            if (stripos($title, $indicator) !== false) {
                $analysis['has_question'] = true;
                $analysis['score'] += 10;
                break;
            }
        }
        
        // Duygusal etki
        $emotional_words = array('şaşırtıcı', 'inanılmaz', 'gizli', 'sır', 'tehlike', 'uyarı');
        foreach ($emotional_words as $emotional_word) {
            if (stripos($title, $emotional_word) !== false) {
                $analysis['emotional_impact'] = 'yüksek';
                $analysis['score'] += 15;
                break;
            }
        }
        
        // SEO skoru
        $analysis['seo_score'] = min(100, $analysis['score'] + 25);
        
        // Genel değerlendirme
        if ($analysis['score'] >= 70) {
            $analysis['rating'] = 'Mükemmel';
        } elseif ($analysis['score'] >= 50) {
            $analysis['rating'] = 'İyi';
        } elseif ($analysis['score'] >= 30) {
            $analysis['rating'] = 'Orta';
        } else {
            $analysis['rating'] = 'Zayıf';
        }
        
        return $analysis;
    }
    
    /**
     * Trend bazlı başlık öner
     */
    public function suggest_trending_titles($topic) {
        $trending_formats = array(
            $topic . ' 2024: Yeni Trendler',
            $topic . ' ile Gelen Devrim',
            $topic . ' Hakkında Şaşırtıcı Gerçekler',
            $topic . ' Dünyasında Son Dakika',
            $topic . ' için Uzman Tavsiyeleri',
            $topic . ' Rehberi: Baştan Sona',
            $topic . ' ile Para Kazanmak',
            $topic . ' Geleceği Nasıl Şekillendirecek?'
        );
        
        return $trending_formats;
    }
    
    /**
     * A/B test başlıkları
     */
    public function create_ab_test_titles($base_title) {
        $variations = array();
        
        // Orijinal
        $variations['original'] = $base_title;
        
        // Sayı ekle
        $variations['with_number'] = preg_replace('/(\w+)/', '5 $1', $base_title, 1);
        
        // Soru formatı
        $variations['question'] = $base_title . ' Nasıl Yapılır?';
        
        // Power word ekle
        $power_word = $this->power_words[array_rand($this->power_words)];
        $variations['with_power_word'] = ucfirst($power_word) . ' ' . $base_title;
        
        // Yıl ekle
        $variations['with_year'] = '2024 ' . $base_title . ' Rehberi';
        
        return $variations;
    }
}
?>