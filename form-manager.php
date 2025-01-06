<?php
/**
 * Plugin Name: Form Manager
 * Description: Un plugin WordPress pour gérer les formulaires et leurs soumissions.
 * Version: 1.0
 * Author: Votre Nom
 */

if (!defined('ABSPATH')) {
    exit; // Empêche l'accès direct au fichier.
}

// Inclure les fichiers nécessaires
require_once plugin_dir_path(__FILE__) . 'includes/form-custom-post-type.php';
require_once plugin_dir_path(__FILE__) . 'includes/form-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/form-submissions-manager.php';

// Enregistrer les scripts et styles
function fm_enqueue_scripts() {
    wp_enqueue_style('fm-styles', plugin_dir_url(__FILE__) . 'assets/style.css');
    wp_enqueue_script('fm-scripts', plugin_dir_url(__FILE__) . 'assets/script.js', ['jquery'], null, true);
}
add_action('wp_enqueue_scripts', 'fm_enqueue_scripts');


register_activation_hook(__FILE__, 'fm_create_submissions_table');

function fm_create_submissions_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'form_submissions';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        form_id mediumint(9) NOT NULL,
        submitted_data longtext NOT NULL,
        submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
        status varchar(20) DEFAULT 'new',
        PRIMARY KEY (id)
    ) $charset_collate;";



    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
