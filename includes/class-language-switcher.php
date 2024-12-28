<?php
class WPMT_Language_Switcher {
    private $current_language;
    private $default_language = 'en_XX';
    private $cookie_name = 'wpmt_language';
    private $cookie_expire = 2592000; // 30 gün

    public function __construct() {
        $this->init();
    }

    private function init() {
        add_action('init', array($this, 'setup_current_language'));
        add_action('widgets_init', array($this, 'register_widget'));
        add_action('wp_footer', array($this, 'render_language_switcher'));
    }

    public function setup_current_language() {
        $this->current_language = $this->get_user_language();
    }

    public function get_user_language() {
        // Öncelik sırası: 1. Çerez, 2. Tarayıcı dili, 3. Varsayılan dil
        if (isset($_COOKIE[$this->cookie_name])) {
            return $_COOKIE[$this->cookie_name];
        }

        $browser_lang = $this->get_browser_language();
        if ($browser_lang) {
            return $browser_lang;
        }

        return $this->default_language;
    }

    private function get_browser_language() {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return false;
        }

        $browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        $supported_languages = (new WPMT_API_Handler())->get_supported_languages();

        foreach ($supported_languages as $lang_code => $lang_name) {
            if (strpos($lang_code, $browser_lang) === 0) {
                return $lang_code;
            }
        }

        return false;
    }

    public function set_language($lang_code) {
        $api_handler = new WPMT_API_Handler();
        
        if (!$api_handler->is_language_supported($lang_code)) {
            return false;
        }

        setcookie(
            $this->cookie_name,
            $lang_code,
            time() + $this->cookie_expire,
            COOKIEPATH,
            COOKIE_DOMAIN
        );

        $this->current_language = $lang_code;
        return true;
    }

    public function get_current_language() {
        return $this->current_language;
    }

    public function render_language_switcher() {
        $current_lang = $this->get_current_language();
        $supported_languages = (new WPMT_API_Handler())->get_supported_languages();
        
        $html = '<div class="wpmt-language-switcher">';
        $html .= '<select id="wpmt-lang-select">';
        
        foreach ($supported_languages as $code => $name) {
            $selected = ($code === $current_lang) ? 'selected' : '';
            $html .= sprintf(
                '<option value="%s" %s>%s</option>',
                esc_attr($code),
                $selected,
                esc_html($name)
            );
        }
        
        $html .= '</select></div>';
        
        echo $html;
    }

    public function register_widget() {
        register_widget('WPMT_Language_Switcher_Widget');
    }
}

// Dil değiştirici widget sınıfı
class WPMT_Language_Switcher_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'wpmt_language_switcher',
            'WP Multilang Language Switcher',
            array('description' => 'Dil seçici widget')
        );
    }

    public function widget($args, $instance) {
        echo $args['before_widget'];
        $switcher = new WPMT_Language_Switcher();
        $switcher->render_language_switcher();
        echo $args['after_widget'];
    }
}