<?php
class WPMT_Translator {
    private $api_handler;
    private $db;
    private $cache_duration = 3600; // 1 saat

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->api_handler = new WPMT_API_Handler();
    }

    public function translate_post($post_id, $target_language) {
        // Cache kontrolü
        $cached = $this->get_cached_translation($post_id, $target_language);
        if ($cached) {
            return $cached;
        }

        $post = get_post($post_id);
        if (!$post) {
            return false;
        }

        // Başlık çevirisi
        $translated_title = $this->api_handler->translate_text(
            $post->post_title,
            'en_XX', // Kaynak dil varsayılan olarak İngilizce
            $target_language
        );

        // İçerik çevirisi
        $translated_content = $this->api_handler->translate_text(
            $post->post_content,
            'en_XX',
            $target_language
        );

        if (!$translated_title['success'] || !$translated_content['success']) {
            return false;
        }

        // Veritabanına kaydet
        $result = $this->save_translation(
            $post_id,
            $target_language,
            $translated_title['translated_text'],
            $translated_content['translated_text']
        );

        if ($result) {
            // Cache'e kaydet
            $this->cache_translation(
                $post_id,
                $target_language,
                $translated_title['translated_text'],
                $translated_content['translated_text']
            );
        }

        return $result;
    }

    private function save_translation($post_id, $language, $title, $content) {
        return $this->db->insert(
            $this->db->prefix . 'wpmt_translations',
            array(
                'post_id' => $post_id,
                'language' => $language,
                'title' => $title,
                'content' => $content,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );
    }

    private function get_cached_translation($post_id, $language) {
        $cache_key = "wpmt_translation_{$post_id}_{$language}";
        return get_transient($cache_key);
    }

    private function cache_translation($post_id, $language, $title, $content) {
        $cache_key = "wpmt_translation_{$post_id}_{$language}";
        $translation_data = array(
            'title' => $title,
            'content' => $content
        );
        set_transient($cache_key, $translation_data, $this->cache_duration);
    }

    public function get_translation($post_id, $language) {
        return $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->db->prefix}wpmt_translations 
                WHERE post_id = %d AND language = %s",
                $post_id,
                $language
            )
        );
    }

    public function delete_translation($post_id, $language = null) {
        if ($language) {
            return $this->db->delete(
                $this->db->prefix . 'wpmt_translations',
                array(
                    'post_id' => $post_id,
                    'language' => $language
                ),
                array('%d', '%s')
            );
        }

        return $this->db->delete(
            $this->db->prefix . 'wpmt_translations',
            array('post_id' => $post_id),
            array('%d')
        );
    }

    public function get_available_translations($post_id) {
        return $this->db->get_col(
            $this->db->prepare(
                "SELECT DISTINCT language FROM {$this->db->prefix}wpmt_translations 
                WHERE post_id = %d",
                $post_id
            )
        );
    }

    public function bulk_translate($post_ids, $target_language) {
        $results = array();
        foreach ($post_ids as $post_id) {
            $results[$post_id] = $this->translate_post($post_id, $target_language);
        }
        return $results;
    }
}