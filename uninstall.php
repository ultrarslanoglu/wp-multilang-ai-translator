<?php
// Bu dosya, eklenti kaldırıldığında verilerin temizlenmesi için gerekli mantığı içerir.

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Eklentiye ait verilerin temizlenmesi
global $wpdb;

// Örnek: Eklentiye ait özel veritabanı tablolarını silme
$table_name = $wpdb->prefix . 'your_custom_table_name';
$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

// Örnek: Eklenti ayarlarını silme
delete_option( 'your_plugin_option_name' );
delete_option( 'another_plugin_option_name' );
?>