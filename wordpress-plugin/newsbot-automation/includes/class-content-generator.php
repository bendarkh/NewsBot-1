<?php
/**
 * İçerik Üretici sınıfı - Otomatik içerik önerileri ve şablonları
 */

if (!defined('ABSPATH')) {
    exit;
}

class NewsBot_Content_Generator {
    
    public function __construct() {
        add_action('wp_ajax_newsbot_generate_post', array($this, 'generate_post_draft'));
        add_action('wp_ajax_newsbot_get_templates', array($this, 'get_content_templates'));
        add_action('wp_ajax_newsbot_optimize_content', array($this, 'optimize_existing_content'));
    }
    
    /**
     * Yazı taslağı oluştur
     */
    public function generate_post_draft() {
        check_ajax_referer('newsbot_nonce', 'nonce');
        
        $topic = sanitize_text_field($_POST['topic']);
        $keywords = array_map('sanitize_text_field', $_POST['keywords']);
        $template_type = sanitize_text_field($_POST['template_type']);
        
        $draft = $this->create_post_draft($topic, $keywords, $template_type);
        
        wp_send_json_success($draft);
    }
    
    /**
     * İçerik şablonlarını getir
     */
    public function get_content_templates() {
        check_ajax_referer('newsbot_nonce', 'nonce');
        
        $templates = array(
            'news_article' => array(
                'name' => 'Haber Makalesi',
                'description' => 'Güncel teknoloji haberleri için',
                'structure' => array(
                    'Başlık',
                    'Öne Çıkan Görsel',
                    'Giriş Paragrafı',
                    'Ana Haber İçeriği',
                    'Uzman Görüşleri',
                    'Sonuç ve Değerlendirme'
                )
            ),
            'tutorial' => array(
                'name' => 'Eğitim İçeriği',
                'description' => 'Adım adım rehberler için',
                'structure' => array(
                    'Başlık',
                    'Giriş ve Gereksinimler',
                    'Adım 1: Hazırlık',
                    'Adım 2: Uygulama',
                    'Adım 3: Test',
                    'Sonuç ve İpuçları'
                )
            ),
            'review' => array(
                'name' => 'İnceleme',
                'description' => 'Ürün ve hizmet incelemeleri için',
                'structure' => array(
                    'Başlık',
                    'Ürün Özeti',
                    'Özellikler',
                    'Artılar ve Eksiler',
                    'Performans Testleri',
                    'Sonuç ve Puan'
                )
            ),
            'trend_analysis' => array(
                'name' => 'Trend Analizi',
                'description' => 'Sektör trendleri için',
                'structure' => array(
                    'Başlık',
                    'Mevcut Durum',
                    'Trend Verileri',
                    'Pazar Analizi',
                    'Gelecek Öngörüleri',
                    'Sonuç'
                )
            )
        );
        
        wp_send_json_success($templates);
    }
    
    /**
     * Mevcut içeriği optimize et
     */
    public function optimize_existing_content() {
        check_ajax_referer('newsbot_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error('Yazı bulunamadı');
        }
        
        $optimization = $this->analyze_and_optimize($post);
        
        wp_send_json_success($optimization);
    }
    
    /**
     * Yazı taslağı oluştur
     */
    private function create_post_draft($topic, $keywords, $template_type) {
        $templates = $this->get_template_content($template_type);
        
        $draft = array(
            'title' => $this->generate_title($topic, $keywords),
            'content' => $this->generate_content($topic, $keywords, $templates),
            'excerpt' => $this->generate_excerpt($topic),
            'meta_description' => $this->generate_meta_description($topic, $keywords),
            'tags' => $keywords,
            'category' => $this->suggest_category($topic),
            'featured_image_suggestions' => $this->suggest_images($topic),
            'seo_score' => $this->calculate_seo_score($topic, $keywords),
            'estimated_read_time' => $this->estimate_read_time($templates['content'])
        );
        
        return $draft;
    }
    
    /**
     * Başlık önerileri oluştur
     */
    private function generate_title($topic, $keywords) {
        $title_templates = array(
            '%s: Kapsamlı Rehber ve Son Gelişmeler',
            '%s Hakkında Bilmeniz Gereken Her Şey',
            '%s Nedir? Detaylı İnceleme ve Analiz',
            '%s ile İlgili Son Haberler ve Trendler',
            '%s: Uzman Görüşleri ve Öneriler',
            '2024\'te %s: Güncel Durum ve Gelecek',
            '%s Teknolojisi: Avantajlar ve Dezavantajlar',
            '%s Rehberi: Başlangıçtan İleri Seviyeye'
        );
        
        $template = $title_templates[array_rand($title_templates)];
        return sprintf($template, ucfirst($topic));
    }
    
    /**
     * İçerik oluştur
     */
    private function generate_content($topic, $keywords, $template) {
        $content = '';
        
        // Giriş paragrafı
        $content .= $this->generate_introduction($topic, $keywords);
        
        // Ana içerik bölümleri
        foreach ($template['sections'] as $section) {
            $content .= "\n\n<h2>" . $section['title'] . "</h2>\n\n";
            $content .= $section['content'];
        }
        
        // Sonuç paragrafı
        $content .= $this->generate_conclusion($topic);
        
        return $content;
    }
    
    /**
     * Giriş paragrafı oluştur
     */
    private function generate_introduction($topic, $keywords) {
        $intro_templates = array(
            "%s günümüzde teknoloji dünyasının en önemli konularından biri haline gelmiştir. Bu kapsamlı rehberde, %s ile ilgili bilmeniz gereken tüm detayları ele alacağız.",
            "Son yıllarda %s alanında yaşanan gelişmeler, sektörde büyük bir dönüşüme yol açmıştır. Bu makalede %s konusunu derinlemesine inceleyeceğiz.",
            "%s teknolojisi, modern dünyada giderek daha fazla önem kazanmaktadır. İşte %s hakkında bilmeniz gereken temel bilgiler.",
            "Teknoloji dünyasında %s konusu son dönemde büyük ilgi görmektedir. Bu yazıda %s ile ilgili güncel gelişmeleri ve önemli noktaları paylaşacağız."
        );
        
        $template = $intro_templates[array_rand($intro_templates)];
        return sprintf($template, ucfirst($topic), $topic);
    }
    
    /**
     * Sonuç paragrafı oluştur
     */
    private function generate_conclusion($topic) {
        $conclusion_templates = array(
            "Sonuç olarak, %s konusu günümüzde büyük önem taşımaktadır. Gelecekte bu alanda daha fazla gelişme beklenmektedir.",
            "%s teknolojisi hızla gelişmeye devam etmektedir. Bu gelişmeleri takip etmek, sektördeki değişimleri anlamak açısından kritik önem taşımaktadır.",
            "Bu makalede %s konusunu detaylı olarak ele aldık. Konuyla ilgili güncel gelişmeleri takip etmeyi unutmayın.",
            "%s alanındaki bu gelişmeler, teknoloji dünyasında yeni fırsatlar yaratmaktadır. Bu fırsatları değerlendirmek için konuyu yakından takip etmek önemlidir."
        );
        
        $template = $conclusion_templates[array_rand($conclusion_templates)];
        return sprintf($template, $topic);
    }
    
    /**
     * Özet oluştur
     */
    private function generate_excerpt($topic) {
        return ucfirst($topic) . " hakkında kapsamlı bilgi, son gelişmeler ve uzman görüşleri. Detaylı analiz ve öneriler için okumaya devam edin.";
    }
    
    /**
     * Meta açıklama oluştur
     */
    private function generate_meta_description($topic, $keywords) {
        $keyword_string = implode(', ', array_slice($keywords, 0, 3));
        return ucfirst($topic) . " rehberi. " . $keyword_string . " konularında güncel bilgiler ve uzman analizleri.";
    }
    
    /**
     * Kategori öner
     */
    private function suggest_category($topic) {
        $tech_keywords = array('yazılım', 'donanım', 'bilgisayar', 'internet', 'mobil');
        $ai_keywords = array('yapay zeka', 'ai', 'makine öğrenmesi', 'chatgpt');
        $crypto_keywords = array('bitcoin', 'ethereum', 'blockchain', 'kripto');
        
        $topic_lower = strtolower($topic);
        
        foreach ($ai_keywords as $keyword) {
            if (strpos($topic_lower, $keyword) !== false) {
                return 'Yapay Zeka';
            }
        }
        
        foreach ($crypto_keywords as $keyword) {
            if (strpos($topic_lower, $keyword) !== false) {
                return 'Kripto Para';
            }
        }
        
        foreach ($tech_keywords as $keyword) {
            if (strpos($topic_lower, $keyword) !== false) {
                return 'Teknoloji';
            }
        }
        
        return 'Genel';
    }
    
    /**
     * Görsel önerileri
     */
    private function suggest_images($topic) {
        return array(
            'https://images.pexels.com/photos/373543/pexels-photo-373543.jpeg', // Technology
            'https://images.pexels.com/photos/1181467/pexels-photo-1181467.jpeg', // AI/Robot
            'https://images.pexels.com/photos/159711/books-bookstore-book-reading-159711.jpeg', // Education
            'https://images.pexels.com/photos/577585/pexels-photo-577585.jpeg' // Business
        );
    }
    
    /**
     * SEO skoru hesapla
     */
    private function calculate_seo_score($topic, $keywords) {
        $score = 0;
        
        // Başlık kontrolü
        if (strlen($topic) >= 30 && strlen($topic) <= 60) {
            $score += 20;
        }
        
        // Anahtar kelime sayısı
        if (count($keywords) >= 3 && count($keywords) <= 7) {
            $score += 20;
        }
        
        // Temel SEO kriterleri
        $score += 60; // Diğer kriterler için sabit puan
        
        return min(100, $score);
    }
    
    /**
     * Okuma süresi tahmin et
     */
    private function estimate_read_time($content) {
        $word_count = str_word_count(strip_tags($content));
        $minutes = ceil($word_count / 200); // Dakikada 200 kelime
        
        return $minutes . ' dakika';
    }
    
    /**
     * Şablon içeriği getir
     */
    private function get_template_content($template_type) {
        $templates = array(
            'news_article' => array(
                'sections' => array(
                    array(
                        'title' => 'Son Gelişmeler',
                        'content' => 'Bu bölümde konuyla ilgili en güncel gelişmeleri ve haberleri ele alacağız.'
                    ),
                    array(
                        'title' => 'Detaylı Analiz',
                        'content' => 'Konunun teknik detaylarını ve önemli noktalarını inceleyeceğiz.'
                    ),
                    array(
                        'title' => 'Uzman Görüşleri',
                        'content' => 'Sektör uzmanlarının konuyla ilgili değerlendirmelerini paylaşacağız.'
                    )
                )
            ),
            'tutorial' => array(
                'sections' => array(
                    array(
                        'title' => 'Gereksinimler',
                        'content' => 'Bu rehberi takip etmek için ihtiyacınız olan araçlar ve bilgiler.'
                    ),
                    array(
                        'title' => 'Adım Adım Uygulama',
                        'content' => 'Konuyu uygulamalı olarak öğrenmek için takip edilecek adımlar.'
                    ),
                    array(
                        'title' => 'İpuçları ve Öneriler',
                        'content' => 'Daha iyi sonuçlar almak için faydalı ipuçları.'
                    )
                )
            ),
            'review' => array(
                'sections' => array(
                    array(
                        'title' => 'Genel Bakış',
                        'content' => 'İncelenen konunun genel özellikleri ve temel bilgileri.'
                    ),
                    array(
                        'title' => 'Artılar ve Eksiler',
                        'content' => 'Konunun güçlü ve zayıf yönlerinin objektif değerlendirmesi.'
                    ),
                    array(
                        'title' => 'Sonuç ve Öneriler',
                        'content' => 'Genel değerlendirme ve kimler için uygun olduğu.'
                    )
                )
            )
        );
        
        return isset($templates[$template_type]) ? $templates[$template_type] : $templates['news_article'];
    }
    
    /**
     * Mevcut içeriği analiz et ve optimize et
     */
    private function analyze_and_optimize($post) {
        $content = $post->post_content;
        $title = $post->post_title;
        
        $analysis = array(
            'current_word_count' => str_word_count(strip_tags($content)),
            'current_seo_score' => $this->analyze_seo($post),
            'readability_score' => $this->calculate_readability($content),
            'keyword_density' => $this->analyze_keyword_density($content),
            'suggestions' => $this->generate_optimization_suggestions($post),
            'improved_title' => $this->improve_title($title),
            'improved_meta' => $this->improve_meta_description($post),
            'additional_keywords' => $this->suggest_additional_keywords($content)
        );
        
        return $analysis;
    }
    
    /**
     * SEO analizi
     */
    private function analyze_seo($post) {
        $score = 0;
        $content = $post->post_content;
        $title = $post->post_title;
        
        // Başlık uzunluğu
        if (strlen($title) >= 30 && strlen($title) <= 60) {
            $score += 15;
        }
        
        // İçerik uzunluğu
        $word_count = str_word_count(strip_tags($content));
        if ($word_count >= 300) {
            $score += 15;
        }
        
        // H2 başlık varlığı
        if (strpos($content, '<h2>') !== false) {
            $score += 10;
        }
        
        // Meta açıklama
        $meta_desc = get_post_meta($post->ID, '_yoast_wpseo_metadesc', true);
        if (!empty($meta_desc)) {
            $score += 10;
        }
        
        // Görsel varlığı
        if (has_post_thumbnail($post->ID) || strpos($content, '<img') !== false) {
            $score += 10;
        }
        
        // Temel puan
        $score += 40;
        
        return min(100, $score);
    }
    
    /**
     * Okunabilirlik hesapla
     */
    private function calculate_readability($content) {
        $text = strip_tags($content);
        $sentences = preg_split('/[.!?]+/', $text);
        $words = str_word_count($text);
        
        if (count($sentences) == 0 || $words == 0) {
            return 0;
        }
        
        $avg_sentence_length = $words / count($sentences);
        
        // Basit okunabilirlik skoru
        if ($avg_sentence_length <= 15) {
            return 90;
        } elseif ($avg_sentence_length <= 20) {
            return 70;
        } elseif ($avg_sentence_length <= 25) {
            return 50;
        } else {
            return 30;
        }
    }
    
    /**
     * Anahtar kelime yoğunluğu analizi
     */
    private function analyze_keyword_density($content) {
        $text = strtolower(strip_tags($content));
        $words = str_word_count($text, 1);
        $total_words = count($words);
        
        if ($total_words == 0) {
            return array();
        }
        
        $word_freq = array_count_values($words);
        arsort($word_freq);
        
        $density = array();
        foreach (array_slice($word_freq, 0, 10) as $word => $count) {
            if (strlen($word) > 3) {
                $density[$word] = round(($count / $total_words) * 100, 2);
            }
        }
        
        return $density;
    }
    
    /**
     * Optimizasyon önerileri
     */
    private function generate_optimization_suggestions($post) {
        $suggestions = array();
        $content = $post->post_content;
        $title = $post->post_title;
        $word_count = str_word_count(strip_tags($content));
        
        // İçerik uzunluğu
        if ($word_count < 300) {
            $suggestions[] = 'İçerik çok kısa. En az 300 kelime olması SEO için önemlidir.';
        }
        
        // Başlık optimizasyonu
        if (strlen($title) < 30) {
            $suggestions[] = 'Başlık çok kısa. 30-60 karakter arası olması önerilir.';
        }
        
        if (strlen($title) > 60) {
            $suggestions[] = 'Başlık çok uzun. 60 karakteri geçmemesi önerilir.';
        }
        
        // H2 başlık kontrolü
        if (strpos($content, '<h2>') === false) {
            $suggestions[] = 'İçeriğe H2 başlıkları ekleyin. Bu SEO için önemlidir.';
        }
        
        // Görsel kontrolü
        if (!has_post_thumbnail($post->ID) && strpos($content, '<img') === false) {
            $suggestions[] = 'İçeriğe görsel ekleyin. Öne çıkan görsel ayarlayın.';
        }
        
        // Meta açıklama
        $meta_desc = get_post_meta($post->ID, '_yoast_wpseo_metadesc', true);
        if (empty($meta_desc)) {
            $suggestions[] = 'Meta açıklama ekleyin. Bu arama sonuçlarında görünür.';
        }
        
        return $suggestions;
    }
    
    /**
     * Başlığı iyileştir
     */
    private function improve_title($title) {
        $improved_titles = array(
            $title . ' - Kapsamlı Rehber',
            $title . ': Son Gelişmeler ve Analiz',
            $title . ' Hakkında Bilmeniz Gerekenler',
            '2024 ' . $title . ' Rehberi'
        );
        
        return $improved_titles;
    }
    
    /**
     * Meta açıklamayı iyileştir
     */
    private function improve_meta_description($post) {
        $title = $post->post_title;
        $content = strip_tags($post->post_content);
        $excerpt = wp_trim_words($content, 20);
        
        return $title . ' hakkında detaylı bilgi. ' . $excerpt . ' Daha fazlası için okumaya devam edin.';
    }
    
    /**
     * Ek anahtar kelime öner
     */
    private function suggest_additional_keywords($content) {
        $text = strtolower(strip_tags($content));
        $tech_keywords = array('teknoloji', 'yazılım', 'donanım', 'internet', 'mobil', 'uygulama', 'sistem', 'platform');
        
        $suggested = array();
        foreach ($tech_keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $suggested[] = $keyword;
            }
        }
        
        return array_slice($suggested, 0, 5);
    }
}
?>