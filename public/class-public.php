<?php
class WPMT_Public {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function enqueue_scripts() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/public.css', array(), $this->version);
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/public.js', array('jquery'), $this->version, true);
        
        wp_localize_script($this->plugin_name, 'wpmt_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpmt_nonce'),
            'available_languages' => $this->get_available_languages()
        ));
    }

    public function register_shortcodes() {
        add_shortcode('wpmt_language_switcher', array($this, 'language_switcher_shortcode'));
    }

    public function language_switcher_shortcode() {
        $current_lang = isset($_COOKIE['wpmt_language']) ? $_COOKIE['wpmt_language'] : 'en';
        $available_languages = $this->get_available_languages();
        
        ob_start();
        ?>
        <div class="wpmt-language-switcher">
            <?php foreach ($available_languages as $lang => $name): ?>
                <button class="language-item <?php echo $current_lang === $lang ? 'active' : ''; ?>" 
                        data-lang="<?php echo esc_attr($lang); ?>">
                    <?php echo esc_html($name); ?>
                </button>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_change_language() {
        check_ajax_referer('wpmt_nonce', 'nonce');
        
        $language = sanitize_text_field($_POST['language']);
        if (!in_array($language, array_keys($this->get_available_languages()))) {
            wp_send_json_error('Invalid language');
        }
        
        setcookie('wpmt_language', $language, time() + (DAY_IN_SECONDS * 30), COOKIEPATH, COOKIE_DOMAIN);
        wp_send_json_success();
    }

    public function filter_post_content($content) {
        global $post;
        $current_lang = isset($_COOKIE['wpmt_language']) ? $_COOKIE['wpmt_language'] : 'en';
        
        if ($current_lang === 'en') {
            return $content;
        }

        global $wpdb;
        $translation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpmt_translations 
            WHERE post_id = %d AND language = %s",
            $post->ID,
            $current_lang
        ));

        return $translation ? $translation->content : $content;
    }

    private function get_available_languages() {
        return array(
            'en' => 'English',
            'tr' => 'Türkçe',
            'es' => 'Español',
            'fr' => 'Français',
            'de' => 'Deutsch'
        );
    }

    public function init() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('init', array($this, 'register_shortcodes'));
        add_action('wp_ajax_wpmt_change_language', array($this, 'ajax_change_language'));
        add_action('wp_ajax_nopriv_wpmt_change_language', array($this, 'ajax_change_language'));
        add_filter('the_content', array($this, 'filter_post_content'));
    }
}