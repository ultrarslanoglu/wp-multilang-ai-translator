(function($) {
    'use strict';

    // Dil değiştirici için ana obje
    const LanguageSwitcher = {
        init: function() {
            this.bindEvents();
            this.detectBrowserLanguage();
        },

        bindEvents: function() {
            // Dil değiştirme butonları için olay dinleyici
            $('.wpmt-language-switcher .language-item').on('click', this.changeLanguage);
        },

        changeLanguage: function(e) {
            e.preventDefault();
            const lang = $(this).data('lang');
            
            $.ajax({
                url: wpmt_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpmt_change_language',
                    language: lang,
                    nonce: wpmt_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Çerezi ayarla
                        LanguageSwitcher.setCookie('wpmt_language', lang, 30);
                        // Sayfayı yenile
                        window.location.reload();
                    }
                }
            });
        },

        detectBrowserLanguage: function() {
            const savedLang = this.getCookie('wpmt_language');
            if (!savedLang) {
                const browserLang = navigator.language || navigator.userLanguage;
                const shortLang = browserLang.split('-')[0];
                
                if (wpmt_ajax.available_languages.includes(shortLang)) {
                    this.changeLanguage(null, shortLang);
                }
            }
        },

        setCookie: function(name, value, days) {
            let expires = '';
            if (days) {
                const date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = '; expires=' + date.toUTCString();
            }
            document.cookie = name + '=' + value + expires + '; path=/';
        },

        getCookie: function(name) {
            const nameEQ = name + '=';
            const ca = document.cookie.split(';');
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) === ' ') {
                    c = c.substring(1, c.length);
                }
                if (c.indexOf(nameEQ) === 0) {
                    return c.substring(nameEQ.length, c.length);
                }
            }
            return null;
        }
    };

    // DOM hazır olduğunda başlat
    $(document).ready(function() {
        LanguageSwitcher.init();
    });

})(jQuery);