<?php
class WPMT_API_Handler {
    private $api_key;
    private $api_url = 'https://api-inference.huggingface.co/models/facebook/mbart-large-50-many-to-many-mmt';
    
    public function __construct() {
        $this->api_key = $_ENV['HUGGINGFACE_API_KEY'];
    }

    public function validate_api_key() {
        if (empty($this->api_key)) {
            throw new Exception('HuggingFace API anahtarı bulunamadı.');
        }
        return true;
    }

    public function translate_text($text, $source_lang, $target_lang) {
        try {
            $this->validate_api_key();

            $headers = [
                'Authorization: Bearer ' . $this->api_key,
                'Content-Type: application/json'
            ];

            $data = [
                'inputs' => $text,
                'parameters' => [
                    'src_lang' => $source_lang,
                    'tgt_lang' => $target_lang
                ]
            ];

            $ch = curl_init($this->api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                throw new Exception('Curl hatası: ' . curl_error($ch));
            }
            
            curl_close($ch);

            if ($http_code !== 200) {
                throw new Exception('API yanıt hatası: ' . $http_code);
            }

            $result = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('JSON ayrıştırma hatası');
            }

            return [
                'success' => true,
                'translated_text' => $result[0]['translation_text']
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function get_supported_languages() {
        return [
            'ar_AR' => 'Arabic',
            'cs_CZ' => 'Czech',
            'de_DE' => 'German',
            'en_XX' => 'English',
            'es_XX' => 'Spanish',
            'et_EE' => 'Estonian',
            'fi_FI' => 'Finnish',
            'fr_XX' => 'French',
            'gu_IN' => 'Gujarati',
            'hi_IN' => 'Hindi',
            'it_IT' => 'Italian',
            'ja_XX' => 'Japanese',
            'kk_KZ' => 'Kazakh',
            'ko_KR' => 'Korean',
            'lt_LT' => 'Lithuanian',
            'lv_LV' => 'Latvian',
            'my_MM' => 'Burmese',
            'ne_NP' => 'Nepali',
            'nl_XX' => 'Dutch',
            'ro_RO' => 'Romanian',
            'ru_RU' => 'Russian',
            'si_LK' => 'Sinhala',
            'tr_TR' => 'Turkish',
            'vi_VN' => 'Vietnamese',
            'zh_CN' => 'Chinese'
        ];
    }

    public function is_language_supported($lang_code) {
        return array_key_exists($lang_code, $this->get_supported_languages());
    }
}