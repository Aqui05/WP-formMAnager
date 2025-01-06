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

    public function renderSubmissionsPage() {
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
            $submitted_data = json_decode($submission->submitted_data, true);
    
            // Convertir les données soumises en une chaîne lisible
            $data_string = '';
            if (is_array($submitted_data)) {
                foreach ($submitted_data as $key => $value) {
                    $data_string .= "{$key}: {$value}; ";
                }
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