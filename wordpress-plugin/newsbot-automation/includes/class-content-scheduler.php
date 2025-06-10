<?php
/**
 * İçerik Planlayıcı sınıfı - İleri tarihli yayınlama ve planlama
 */

if (!defined('ABSPATH')) {
    exit;
}

class NewsBot_Content_Scheduler {
    
    public function __construct() {
        add_action('wp_ajax_newsbot_schedule_content', array($this, 'schedule_content'));
        add_action('wp_ajax_newsbot_get_scheduled_posts', array($this, 'get_scheduled_posts'));
        add_action('wp_ajax_newsbot_update_schedule', array($this, 'update_schedule'));
        add_action('wp_ajax_newsbot_delete_scheduled', array($this, 'delete_scheduled_post'));
        add_action('newsbot_publish_scheduled_content', array($this, 'publish_scheduled_content'));
    }
    
    /**
     * İçeriği planla
     */
    public function schedule_content() {
        check_ajax_referer('newsbot_nonce', 'nonce');
        
        $title = sanitize_text_field($_POST['title']);
        $content = wp_kses_post($_POST['content']);
        $excerpt = sanitize_textarea_field($_POST['excerpt']);
        $publish_date = sanitize_text_field($_POST['publish_date']);
        $publish_time = sanitize_text_field($_POST['publish_time']);
        $category = sanitize_text_field($_POST['category']);
        $tags = array_map('sanitize_text_field', $_POST['tags']);
        $featured_image = esc_url($_POST['featured_image']);
        
        // Yayın tarihini birleştir
        $publish_datetime = $publish_date . ' ' . $publish_time . ':00';
        $publish_timestamp = strtotime($publish_datetime);
        
        if ($publish_timestamp <= time()) {
            wp_send_json_error('Yayın tarihi gelecekte olmalıdır.');
        }
        
        // WordPress'e zamanlanmış yazı olarak ekle
        $post_data = array(
            'post_title' => $title,
            'post_content' => $content,
            'post_excerpt' => $excerpt,
            'post_status' => 'future',
            'post_date' => date('Y-m-d H:i:s', $publish_timestamp),
            'post_date_gmt' => gmdate('Y-m-d H:i:s', $publish_timestamp),
            'post_type' => 'post',
            'post_author' => get_current_user_id()
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            wp_send_json_error('Yazı planlanamadı: ' . $post_id->get_error_message());
        }
        
        // Kategori ata
        if (!empty($category)) {
            $cat_id = $this->get_or_create_category($category);
            wp_set_post_categories($post_id, array($cat_id));
        }
        
        // Etiketleri ata
        if (!empty($tags)) {
            wp_set_post_tags($post_id, $tags);
        }
        
        // Öne çıkan görsel
        if (!empty($featured_image)) {
            $this->set_featured_image_from_url($post_id, $featured_image);
        }
        
        // Meta bilgileri kaydet
        update_post_meta($post_id, 'newsbot_scheduled', true);
        update_post_meta($post_id, 'newsbot_original_date', current_time('mysql'));
        
        wp_send_json_success(array(
            'post_id' => $post_id,
            'edit_url' => admin_url('post.php?post=' . $post_id . '&action=edit'),
            'publish_date' => date('d.m.Y H:i', $publish_timestamp)
        ));
    }
    
    /**
     * Planlanmış yazıları getir
     */
    public function get_scheduled_posts() {
        check_ajax_referer('newsbot_nonce', 'nonce');
        
        $scheduled_posts = get_posts(array(
            'post_status' => 'future',
            'numberposts' => 50,
            'meta_key' => 'newsbot_scheduled',
            'meta_value' => true,
            'orderby' => 'post_date',
            'order' => 'ASC'
        ));
        
        $posts_data = array();
        foreach ($scheduled_posts as $post) {
            $posts_data[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'publish_date' => get_the_date('d.m.Y H:i', $post->ID),
                'category' => $this->get_post_category($post->ID),
                'status' => 'scheduled',
                'edit_url' => admin_url('post.php?post=' . $post->ID . '&action=edit')
            );
        }
        
        wp_send_json_success($posts_data);
    }
    
    /**
     * Planlamayı güncelle
     */
    public function update_schedule() {
        check_ajax_referer('newsbot_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $new_date = sanitize_text_field($_POST['new_date']);
        $new_time = sanitize_text_field($_POST['new_time']);
        
        $new_datetime = $new_date . ' ' . $new_time . ':00';
        $new_timestamp = strtotime($new_datetime);
        
        if ($new_timestamp <= time()) {
            wp_send_json_error('Yeni tarih gelecekte olmalıdır.');
        }
        
        $updated = wp_update_post(array(
            'ID' => $post_id,
            'post_date' => date('Y-m-d H:i:s', $new_timestamp),
            'post_date_gmt' => gmdate('Y-m-d H:i:s', $new_timestamp)
        ));
        
        if ($updated) {
            wp_send_json_success('Planlama güncellendi.');
        } else {
            wp_send_json_error('Güncelleme başarısız.');
        }
    }
    
    /**
     * Planlanmış yazıyı sil
     */
    public function delete_scheduled_post() {
        check_ajax_referer('newsbot_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        
        $deleted = wp_delete_post($post_id, true);
        
        if ($deleted) {
            wp_send_json_success('Planlanmış yazı silindi.');
        } else {
            wp_send_json_error('Silme işlemi başarısız.');
        }
    }
    
    /**
     * Kategori al veya oluştur
     */
    private function get_or_create_category($category_name) {
        $category = get_term_by('name', $category_name, 'category');
        
        if ($category) {
            return $category->term_id;
        }
        
        $new_category = wp_insert_term($category_name, 'category');
        
        if (is_wp_error($new_category)) {
            return 1; // Varsayılan kategori
        }
        
        return $new_category['term_id'];
    }
    
    /**
     * URL'den öne çıkan görsel ayarla
     */
    private function set_featured_image_from_url($post_id, $image_url) {
        $upload_dir = wp_upload_dir();
        $image_data = file_get_contents($image_url);
        
        if ($image_data === false) {
            return false;
        }
        
        $filename = basename($image_url);
        $file = $upload_dir['path'] . '/' . $filename;
        
        file_put_contents($file, $image_data);
        
        $wp_filetype = wp_check_filetype($filename, null);
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        $attach_id = wp_insert_attachment($attachment, $file, $post_id);
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $file);
        wp_update_attachment_metadata($attach_id, $attach_data);
        
        set_post_thumbnail($post_id, $attach_id);
        
        return $attach_id;
    }
    
    /**
     * Yazının kategorisini al
     */
    private function get_post_category($post_id) {
        $categories = get_the_category($post_id);
        return !empty($categories) ? $categories[0]->name : 'Genel';
    }
    
    /**
     * Toplu planlama
     */
    public function bulk_schedule_content($contents, $start_date, $interval_hours = 24) {
        $current_timestamp = strtotime($start_date);
        $scheduled_posts = array();
        
        foreach ($contents as $content) {
            $post_data = array(
                'post_title' => $content['title'],
                'post_content' => $content['content'],
                'post_excerpt' => $content['excerpt'],
                'post_status' => 'future',
                'post_date' => date('Y-m-d H:i:s', $current_timestamp),
                'post_date_gmt' => gmdate('Y-m-d H:i:s', $current_timestamp),
                'post_type' => 'post',
                'post_author' => get_current_user_id()
            );
            
            $post_id = wp_insert_post($post_data);
            
            if (!is_wp_error($post_id)) {
                update_post_meta($post_id, 'newsbot_scheduled', true);
                $scheduled_posts[] = $post_id;
            }
            
            $current_timestamp += ($interval_hours * 3600);
        }
        
        return $scheduled_posts;
    }
}
?>