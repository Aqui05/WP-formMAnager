<?php
if (!defined('ABSPATH')) {
    exit;
}

class FormSubmissionsManager {
    private static $instance = null;
    private $table_name;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'form_submissions';
        
        // Actions pour l'initialisation
        add_action('admin_menu', [$this, 'addSubmissionsMenu']);
        add_action('admin_init', [$this, 'createSubmissionsTable']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminStyles']);
        
        // Actions pour la gestion AJAX
        add_action('wp_ajax_delete_submission', [$this, 'deleteSubmission']);
        add_action('wp_ajax_bulk_delete_submissions', [$this, 'bulkDeleteSubmissions']);
    }

    public function createSubmissionsTable() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            form_id mediumint(9) NOT NULL,
            submitted_data longtext NOT NULL,
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'new',
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function addSubmissionsMenu() {
        add_menu_page(
            'Soumissions de formulaires',
            'Soumissions',
            'manage_options',
            'form-submissions',
            [$this, 'renderSubmissionsPage'],
            'dashicons-feedback',
            30
        );
    }

    public function enqueueAdminStyles() {
        wp_enqueue_style('form-submissions-style', plugins_url('assets/css/admin-style.css', __FILE__));
        wp_enqueue_script('form-submissions-script', plugins_url('assets/js/admin-script.js', __FILE__), ['jquery'], null, true);
        wp_localize_script('form-submissions-script', 'formSubmissionsAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('form_submissions_nonce')
        ]);
    }

    private function getAvailableForms() {
        global $wpdb;
        $forms = $wpdb->get_results("
            SELECT DISTINCT f.ID, f.post_title 
            FROM {$wpdb->posts} f 
            INNER JOIN {$this->table_name} s ON f.ID = s.form_id 
            WHERE f.post_type = 'fm_form' 
            AND f.post_status = 'publish'
            ORDER BY f.post_title ASC
        ");
        
        // Si aucun formulaire n'a de soumissions, récupérer tous les formulaires publiés
        if (empty($forms)) {
            $forms = get_posts([
                'post_type' => 'fm_form',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC'
            ]);
        }
        
        return $forms;
    }

    /*public function renderSubmissionsPage() {
        global $wpdb;

        // Gérer les actions en masse
        if (isset($_POST['action']) && $_POST['action'] === 'bulk_export_csv') {
            if (isset($_POST['submissions']) && is_array($_POST['submissions'])) {
                $ids = array_map('intval', $_POST['submissions']);
                $submissions = $wpdb->get_results("SELECT * FROM {$this->table_name} WHERE id IN (" . implode(',', $ids) . ")");
                $this->exportSubmissionsToCSV($submissions);
            }
        } elseif (isset($_POST['action']) && $_POST['action'] === 'bulk_delete') {
            if (isset($_POST['submissions']) && is_array($_POST['submissions'])) {
                $ids = array_map('intval', $_POST['submissions']);
                $wpdb->query("DELETE FROM {$this->table_name} WHERE id IN (" . implode(',', $ids) . ")");
                echo '<div class="notice notice-success"><p>Soumissions supprimées avec succès.</p></div>';
            }
        }


        // Pagination
        $per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;

        // Récupérer les soumissions
        $submissions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} ORDER BY submitted_at DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        );

        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM {$this->table_name}");
        $total_pages = ceil($total_items / $per_page);

        ?>
        <div class="wrap">
            <h1>Soumissions de formulaires</h1>
            
            <form method="post">
                <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <select name="action">
                            <option value="-1">Actions groupées</option>
                            <option value="bulk_delete">Supprimer</option>
                            <option value="bulk_export_csv">Exporter en CSV</option> <!-- Nouvelle action -->
                        </select>

                        <input type="submit" class="button action" value="Appliquer">
                    </div>
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links([
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total' => $total_pages,
                            'current' => $current_page
                        ]);
                        ?>
                    </div>
                </div>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input type="checkbox" id="cb-select-all-1">
                            </td>
                            <th>ID</th>
                            <th>Formulaire</th>
                            <th>Données</th>
                            <th>Date de soumission</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $submission): ?>
                            <?php
                            $form = get_post($submission->form_id);
                            $submitted_data = json_decode($submission->submitted_data, true); // Utilisation de json_decode pour les données JSON
                            ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="submissions[]" value="<?php echo $submission->id; ?>">
                                </th>
                                <td><?php echo $submission->id; ?></td>
                                <td><?php echo $form ? esc_html($form->post_title) : 'Formulaire supprimé'; ?></td>
                                <td>
                                    <?php
                                    if (is_array($submitted_data)) {
                                        echo '<ul>';
                                        foreach ($submitted_data as $key => $value) {
                                            echo '<li><strong>' . esc_html($key) . ':</strong> ' . esc_html($value) . '</li>';
                                        }
                                        echo '</ul>';
                                    } else {
                                        echo 'Données non disponibles ou mal formatées.';
                                    }
                                    ?>
                                </td>
                                <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($submission->submitted_at)); ?></td>
                                <td>
                                    <a href="#" class="delete-submission" data-id="<?php echo $submission->id; ?>">Supprimer</a> |
                                    <a href="#" class="export-submission" data-id="<?php echo $submission->id; ?>">Exporter</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </form>
        </div>
        <?php
    }*/


    public function renderSubmissionsPage() {
        global $wpdb;

        // Récupérer tous les formulaires disponibles
        $forms = $this->getAvailableForms();
        
        // Sélectionner le formulaire actif
        $current_form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : ($forms ? $forms[0]->ID : 0);

        // Gérer les actions en masse
        if (isset($_POST['action'])) {
            if (!empty($_POST['submissions']) && is_array($_POST['submissions'])) {
                $ids = array_map('intval', $_POST['submissions']);
                
                if ($_POST['action'] === 'bulk_export_csv') {
                    $submissions = $wpdb->get_results("SELECT * FROM {$this->table_name} WHERE id IN (" . implode(',', $ids) . ")");
                    $this->exportSubmissionsToCSV($submissions);
                } elseif ($_POST['action'] === 'bulk_delete') {
                    $wpdb->query("DELETE FROM {$this->table_name} WHERE id IN (" . implode(',', $ids) . ")");
                    echo '<div class="notice notice-success"><p>Soumissions supprimées avec succès.</p></div>';
                }
            }
        }

        // Pagination
        $per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;

        // Récupérer les soumissions pour le formulaire sélectionné
        $submissions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE form_id = %d ORDER BY submitted_at DESC LIMIT %d OFFSET %d",
                $current_form_id,
                $per_page,
                $offset
            )
        );

        $total_items = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(id) FROM {$this->table_name} WHERE form_id = %d",
            $current_form_id
        ));
        
        $total_pages = ceil($total_items / $per_page);

        ?>
        <div class="wrap">
            <h1>Soumissions de formulaires</h1>

            <!-- Sélecteur de formulaire -->
            <div class="form-selector" style="margin: 20px 0;">
                <form method="get">
                    <input type="hidden" name="page" value="form-submissions">
                    <select name="form_id" onchange="this.form.submit()">
                        <?php foreach ($forms as $form): ?>
                            <option value="<?php echo $form->ID; ?>" <?php selected($form->ID, $current_form_id); ?>>
                                <?php echo esc_html($form->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            
            <form method="post">
                <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <select name="action">
                            <option value="-1">Actions groupées</option>
                            <option value="bulk_delete">Supprimer</option>
                            <option value="bulk_export_csv">Exporter en CSV</option>
                        </select>
                        <input type="submit" class="button action" value="Appliquer">
                    </div>
                    <div class="tablenav-pages">
                        <?php
                        $pagination_args = [
                            'base' => add_query_arg(['paged' => '%#%', 'form_id' => $current_form_id]),
                            'format' => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total' => $total_pages,
                            'current' => $current_page
                        ];
                        echo paginate_links($pagination_args);
                        ?>
                    </div>
                </div>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input type="checkbox" id="cb-select-all-1">
                            </td>
                            <th>ID</th>
                            <th>Données</th>
                            <th>Date de soumission</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($submissions): ?>
                            <?php foreach ($submissions as $submission): ?>
                                <?php 
                                // Décoder les données JSON stockées
                                $stored_data = json_decode($submission->submitted_data, true);
                                
                                // Vérifier si les données sont dans le bon format
                                if (isset($stored_data['data']) && isset($stored_data['iv'])) {
                                    // Décoder l'IV
                                    $iv = base64_decode($stored_data['iv']);
                                    
                                    // Déchiffrer les données
                                    $decrypted_data = openssl_decrypt(
                                        $stored_data['data'],
                                        'AES-256-CBC',
                                        FM_ENCRYPTION_KEY,
                                        0,
                                        $iv
                                    );
                                    
                                    // Décoder les données JSON déchiffrées
                                    $submitted_data = json_decode($decrypted_data, true);
                                } else {
                                    $submitted_data = null;
                                }
                                ?>
                                <tr>
                                    <th scope="row" class="check-column">
                                        <input type="checkbox" name="submissions[]" value="<?php echo $submission->id; ?>">
                                    </th>
                                    <td><?php echo $submission->id; ?></td>
                                    <td>
                                        <?php
                                        if (is_array($submitted_data)) {
                                            echo '<ul class="submission-data">';
                                            foreach ($submitted_data as $key => $value) {
                                                if ($key === 'uploaded_files' && is_array($value) && !empty($value)) {
                                                    echo '<li><strong>Fichiers uploadés:</strong>';
                                                    echo '<ul class="uploaded-files">';
                                                    foreach ($value as $file_url) {
                                                        echo '<li><a href="' . esc_url($file_url) . '" target="_blank">Voir le fichier</a></li>';
                                                    }
                                                    echo '</ul></li>';
                                                } elseif ($key !== 'uploaded_files') {
                                                    echo '<li><strong>' . esc_html($key) . ':</strong> ' . esc_html($value) . '</li>';
                                                }
                                            }
                                            echo '</ul>';
                                        } else {
                                            echo '<p class="error-message">Erreur lors du déchiffrement des données.</p>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($submission->submitted_at)); ?></td>
                                    <td>
                                        <a href="#" class="delete-submission" data-id="<?php echo $submission->id; ?>">Supprimer</a> |
                                        <a href="#" class="export-submission" data-id="<?php echo $submission->id; ?>">Exporter</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">Aucune soumission trouvée pour ce formulaire.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

<style>
    .submission-data {
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .submission-data li {
        margin: 5px 0;
        padding: 5px;
    }
    .uploaded-files {
        margin-left: 20px;
        padding: 5px 0;
    }
    .error-message {
        color: #dc3232;
        margin: 0;
        padding: 5px;
    }
</style>
            </form>
        </div>
        <?php
    }

    

    
    

    public function deleteSubmission() {
        check_ajax_referer('form_submissions_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $submission_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if (!$submission_id) {
            wp_send_json_error('Invalid submission ID');
        }

        global $wpdb;
        $result = $wpdb->delete(
            $this->table_name,
            ['id' => $submission_id],
            ['%d']
        );

        if ($result) {
            wp_send_json_success('Submission deleted');
        } else {
            wp_send_json_error('Failed to delete submission');
        }
    }

    public function bulkDeleteSubmissions() {
        check_ajax_referer('form_submissions_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $submission_ids = isset($_POST['ids']) ? array_map('intval', $_POST['ids']) : [];
        if (empty($submission_ids)) {
            wp_send_json_error('No submissions selected');
        }

        global $wpdb;
        $ids_string = implode(',', $submission_ids);
        $result = $wpdb->query("DELETE FROM {$this->table_name} WHERE id IN ($ids_string)");

        if ($result) {
            wp_send_json_success('Submissions deleted');
        } else {
            wp_send_json_error('Failed to delete submissions');
        }
    }

    public function exportSubmissionsToCSV($submissions) {
        if (empty($submissions)) {
            return;
        }
    
        // Définir les en-têtes HTTP pour le téléchargement du fichier CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="submissions.csv"');
    
        // Ouvrir un flux en sortie
        $output = fopen('php://output', 'w');
    
        // Ajouter une ligne d'en-têtes au fichier CSV
        fputcsv($output, ['ID', 'Formulaire', 'Données', 'Date de soumission', 'Statut']);
    
        // Ajouter les données des soumissions
        foreach ($submissions as $submission) {
            $form = get_post($submission->form_id);
            $form_title = $form ? $form->post_title : 'Formulaire supprimé';
            
            // Décoder les données JSON stockées
            $stored_data = json_decode($submission->submitted_data, true);
            $data_string = '';
            
            // Vérifier si les données sont dans le bon format
            if (isset($stored_data['data']) && isset($stored_data['iv'])) {
                // Décoder l'IV
                $iv = base64_decode($stored_data['iv']);
                
                // Déchiffrer les données
                $decrypted_data = openssl_decrypt(
                    $stored_data['data'],
                    'AES-256-CBC',
                    FM_ENCRYPTION_KEY,
                    0,
                    $iv
                );
                
                // Décoder les données JSON déchiffrées
                $submitted_data = json_decode($decrypted_data, true);
                
                // Convertir les données déchiffrées en une chaîne lisible
                if (is_array($submitted_data)) {
                    foreach ($submitted_data as $key => $value) {
                        if ($key === 'uploaded_files' && is_array($value) && !empty($value)) {
                            $data_string .= "Fichiers uploadés: " . implode(', ', $value) . "; ";
                        } elseif ($key !== 'uploaded_files') {
                            $data_string .= "{$key}: {$value}; ";
                        }
                    }
                }
            } else {
                $data_string = 'Erreur lors du déchiffrement des données';
            }
    
            // Ajouter une ligne pour chaque soumission
            fputcsv($output, [
                $submission->id,
                $form_title,
                $data_string,
                $submission->submitted_at,
                $submission->status
            ]);
        }
    
        // Fermer le flux et terminer le script
        fclose($output);
        exit;
    }
    
}

// Initialiser le gestionnaire
add_action('plugins_loaded', function() {
    FormSubmissionsManager::getInstance();
});