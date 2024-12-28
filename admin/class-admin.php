<?php
class WPMT_Admin {
    private $plugin_name;
    private $version;
    private $api_handler;
    private $translator;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->api_handler = new WPMT_API_Handler();
        $this->translator = new WPMT_Translator();
    }

    public function enqueue_scripts() {
        wp_enqueue_style($this->plugin_name . '-admin', plugin_dir_url(__FILE__) . 'css/admin.css', array(), $this->version);
        wp_enqueue_script($this->plugin_name . '-admin', plugin_dir_url(__FILE__) . 'js/admin.js', array('jquery'), $this->version, true);
        
        wp_localize_script($this->plugin_name . '-admin', 'wpmt_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpmt_admin_nonce')
        ));
    }

    public function add_admin_menu() {
        add_menu_page(
            'WP Multilang AI Translator',
            'AI Translator',
            'manage_options',
            'wpmt-settings',
            array($this, 'display_settings_page'),
            'dashicons-translation',
            100
        );

        add_submenu_page(
            'wpmt-settings',
            'Toplu Çeviri',
            'Toplu Çeviri',
            'manage_options',
            'wpmt-bulk-translate',
            array($this, 'display_bulk_translate_page')
        );
    }

    public function display_settings_page() {
        if (isset($_POST['wpmt_save_settings'])) {
            $this->save_settings();
        }

        $api_key = get_option('wpmt_huggingface_api_key');
        $enabled_languages = get_option('wpmt_enabled_languages', array());
        ?>
        <div class="wrap">
            <h1>WP Multilang AI Translator Ayarları</h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('wpmt_settings_nonce', 'wpmt_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">HuggingFace API Anahtarı</th>
                        <td>
                            <input type="text" name="wpmt_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Aktif Diller</th>
                        <td>
                            <?php
                            $available_languages = $this->api_handler->get_supported_languages();
                            foreach ($available_languages as $code => $name) {
                                $checked = in_array($code, $enabled_languages) ? 'checked' : '';
                                echo sprintf(
                                    '<label><input type="checkbox" name="wpmt_languages[]" value="%s" %s> %s</label><br>',
                                    esc_attr($code),
                                    $checked,
                                    esc_html($name)
                                );
                            }
                            ?>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="wpmt_save_settings" class="button-primary" value="Ayarları Kaydet">
                </p>
            </form>
        </div>
        <?php
    }

    public function display_bulk_translate_page() {
        if (isset($_POST['wpmt_bulk_translate'])) {
            $this->process_bulk_translation();
        }
        ?>
        <div class="wrap">
            <h1>Toplu Çeviri</h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('wpmt_bulk_translate_nonce', 'wpmt_bulk_translate_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">İçerik Türü</th>
                        <td>
                            <select name="wpmt_post_type">
                                <option value="post">Yazılar</option>
                                <option value="page">Sayfalar</option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Hedef Dil</th>
                        <td>
                            <select name="wpmt_target_language">
                                <?php
                                $enabled_languages = get_option('wpmt_enabled_languages', array());
                                foreach ($enabled_languages as $code) {
                                    $name = $this->api_handler->get_supported_languages()[$code];
                                    echo sprintf(
                                        '<option value="%s">%s</option>',
                                        esc_attr($code),
                                        esc_html($name)
                                    );
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="wpmt_bulk_translate" class="button-primary" value="Çeviriye Başla">
                </p>
            </form>
        </div>
        <?php
    }

    private function save_settings() {
        if (!isset($_POST['wpmt_settings_nonce']) || 
            !wp_verify_nonce($_POST['wpmt_settings_nonce'], 'wpmt_settings_nonce')) {
            wp_die('Güvenlik doğrulaması başarısız');
        }

        $api_key = sanitize_text_field($_POST['wpmt_api_key']);
        update_option('wpmt_huggingface_api_key', $api_key);

        $languages = isset($_POST['wpmt_languages']) ? $_POST['wpmt_languages'] : array();
        $languages = array_map('sanitize_text_field', $languages);
        update_option('wpmt_enabled_languages', $languages);

        add_settings_error(
            'wpmt_messages',
            'wpmt_message',
            'Ayarlar başarıyla kaydedildi.',
            'updated'
        );
    }

    private function process_bulk_translation() {
        if (!isset($_POST['wpmt_bulk_translate_nonce']) || 
            !wp_verify_nonce($_POST['wpmt_bulk_translate_nonce'], 'wpmt_bulk_translate_nonce')) {
            wp_die('Güvenlik doğrulaması başarısız');
        }

        $post_type = sanitize_text_field($_POST['wpmt_post_type']);
        $target_language = sanitize_text_field($_POST['wpmt_target_language']);

        $posts = get_posts(array(
            'post_type' => $post_type,
            'posts_per_page' => -1
        ));

        foreach ($posts as $post) {
            $this->translator->translate_post($post->ID, $target_language);
        }

        add_settings_error(
            'wpmt_messages',
            'wpmt_message',
            'Toplu çeviri işlemi tamamlandı.',
            'updated'
        );
    }

    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
}