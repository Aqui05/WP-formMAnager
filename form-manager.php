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




// Ajouter ceci après les autres require_once
if (!defined('ABSPATH')) {
    exit;
}



// Inclure les fichiers nécessaires
require_once plugin_dir_path(__FILE__) . 'includes/form-styles.php';
require_once plugin_dir_path(__FILE__) . 'includes/form-custom-post-type.php';
require_once plugin_dir_path(__FILE__) . 'includes/form-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/form-submissions-manager.php';

// Enregistrer les scripts et styles
function fm_enqueue_scripts() {
    wp_enqueue_style('form-submissions-style', plugins_url('includes/assets/css/admin-style.css', __FILE__));
    wp_enqueue_script('form-submissions-script', plugins_url('includes/assets/js/admin-script.js', __FILE__), ['jquery'], null, true);

    wp_localize_script('form-submissions-script', 'formSubmissionsAjax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('form_submissions_nonce')
    ]);

            wp_enqueue_style('wp-color-picker');
            wp_enqueue_media();
            wp_enqueue_script('wp-color-picker');
            wp_enqueue_script('form-admin-script', plugins_url('/assets/js/admin-script.js', __FILE__), 
                array('jquery', 'wp-color-picker'), null, true);
        

            // Styles front-end
    wp_enqueue_style('form-submissions-style', plugins_url('/assets/css/form-style.css', __FILE__));
        
}
add_action('wp_enqueue_scripts', 'fm_enqueue_scripts');

// Gestion de l'export CSV des soumissions
add_action('wp_ajax_export_submissions_csv', 'export_submissions_csv_callback');

add_action('admin_enqueue_scripts', 'fm_enqueue_scripts');
add_action('wp_enqueue_scripts', 'fm_enqueue_scripts');



function export_submissions_csv_callback() {
    // Vérification du nonce
    check_ajax_referer('form_submissions_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission refusée');
    }

    // Récupération des IDs des soumissions
    $submission_ids = isset($_POST['submissions_ids']) ? array_map('intval', $_POST['submissions_ids']) : [];
    if (empty($submission_ids)) {
        wp_send_json_error('Aucune soumission sélectionnée.');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'form_submissions';
    $submissions = $wpdb->get_results(
        "SELECT * FROM $table_name WHERE id IN (" . implode(',', $submission_ids) . ")"
    );

    if (empty($submissions)) {
        wp_send_json_error('Aucune soumission trouvée.');
    }

    // Préparation du fichier CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="soumissions_export.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Formulaire', 'Données', 'Date de soumission']); // En-têtes CSV

    foreach ($submissions as $submission) {
        $form = get_post($submission->form_id);
        $submitted_data = json_decode($submission->submitted_data, true);
        $formatted_data = is_array($submitted_data)
            ? json_encode($submitted_data)
            : 'Données non disponibles';

        fputcsv($output, [
            $submission->id,
            $form ? $form->post_title : 'Formulaire supprimé',
            $formatted_data,
            $submission->submitted_at
        ]);
    }

    fclose($output);
    exit;
}



register_activation_hook(__FILE__, 'fm_create_submissions_table');

function fm_create_submissions_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'form_submissions';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
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
