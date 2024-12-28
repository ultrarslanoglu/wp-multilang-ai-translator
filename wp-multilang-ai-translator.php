<?php
/**
 * Plugin Name: WP Multilang AI Translator
 * Plugin URI: https://example.com
 * Description: AI powered translation plugin
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: wp-multilang-ai-translator
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

// Sabit tanımlamaları
define('WPMT_VERSION', '1.0.0');
define('WPMT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPMT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Composer autoload
require_once WPMT_PLUGIN_DIR . 'vendor/autoload.php';

// .env dosyasını yükle
$dotenv = Dotenv\Dotenv::createImmutable(WPMT_PLUGIN_DIR);
$dotenv->load();

// Eklenti sınıfını yükle
require_once WPMT_PLUGIN_DIR . 'includes/class-wp-multilang-ai-translator.php';

// Eklentiyi başlat
function run_wp_multilang_ai_translator() {
    $plugin = new WP_Multilang_AI_Translator();
    $plugin->run();
}
run_wp_multilang_ai_translator();

// Aktivasyon kancası
register_activation_hook(__FILE__, 'wpmt_activate');
function wpmt_activate() {
    // Veritabanı tablolarını oluştur
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpmt_translations (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        language varchar(10) NOT NULL,
        title text,
        content longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY post_id (post_id),
        KEY language (language)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}