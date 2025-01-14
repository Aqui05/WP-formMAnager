<?php




function fm_handle_submission() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fm_submit'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'form_submissions';

        // ID du formulaire
        $form_id = isset($_POST['fm_form_id']) ? intval($_POST['fm_form_id']) : 0;

        // Vérification du formulaire
        if (empty($form_id)) {
            fm_set_message('error', 'Formulaire invalide.');
            wp_redirect(add_query_arg('fm_error', 'invalid_form', wp_get_referer()));
            exit;
        }

        // Récupérer la configuration des emails
        $email_config = get_post_meta($form_id, '_fm_form_email_config', true);

        // Récupérer toutes les données soumises
        $submitted_data = $_POST;
        unset($submitted_data['fm_form_id'], $submitted_data['fm_submit']);

        // Nettoyage des données
        $cleaned_data = [];
        foreach ($submitted_data as $key => $value) {
            $cleaned_data[sanitize_text_field($key)] = is_array($value)
                ? array_map('sanitize_text_field', $value)
                : sanitize_text_field($value);
        }

        // Préparer les données pour la base de données
        $data = [
            'form_id' => $form_id,
            'submitted_data' => wp_json_encode($cleaned_data),
            'status' => 'new',
            'submitted_at' => current_time('mysql'),
        ];

        // Insertion dans la base de données
        $result = $wpdb->insert($table_name, $data);

        if ($result === false) {
            fm_set_message('error', 'Erreur lors de l\'enregistrement des données.');
            wp_redirect(add_query_arg('fm_error', 'db_error', wp_get_referer()));
            exit;
        }

        // Préparation du contenu de l'email
        $email_content = fm_generate_email_content($cleaned_data, $form_id);

        // Déterminer l'adresse email de l'admin
        $admin_email = !empty($email_config['admin_email']) 
            ? $email_config['admin_email'] 
            : 'kikissagbeaquilas@gmail.com';

        // Envoi de l'email à l'administrateur
        $admin_subject = !empty($email_config['admin_subject']) 
            ? $email_config['admin_subject'] 
            : 'Nouvelle soumission de formulaire';

        $admin_headers = ['Content-Type: text/html; charset=UTF-8'];

        $sent_to_admin = wp_mail(
            $admin_email,
            $admin_subject,
            $email_content,
            $admin_headers
        );

        if (!$sent_to_admin) {
            error_log('Erreur lors de l\'envoi du mail admin pour le formulaire ' . $form_id . ' à l\'adresse ' . $admin_email);
        }

        // Message de succès et redirection
        fm_set_message('success', !empty($email_config['success_message']) 
            ? $email_config['success_message'] 
            : 'Votre message a été envoyé avec succès.');

        wp_redirect(add_query_arg('fm_submitted', 'true', wp_get_referer()));
        exit;
    }
}




 function renderSubmissionsPage() {
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



    
        // Récupérer la liste des formulaires
        $forms = $wpdb->get_results("SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = 'form' AND post_status = 'publish'");
    
        // Vérifier si un formulaire est sélectionné
        $selected_form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : (isset($forms[0]) ? $forms[0]->ID : 0);
    
        // Pagination
        $per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;
    
        // Récupérer les soumissions pour le formulaire sélectionné
        $submissions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE form_id = %d ORDER BY submitted_at DESC LIMIT %d OFFSET %d",
                $selected_form_id,
                $per_page,
                $offset
            )
        );
    
        $total_items = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM {$this->table_name} WHERE form_id = %d", $selected_form_id));
        $total_pages = ceil($total_items / $per_page);
    
        ?>
        <div class="wrap">
            <h1>Soumissions de formulaires</h1>
            
            <form method="get">
                <input type="hidden" name="page" value="form-submissions">
                <label for="form_id">Sélectionnez un formulaire :</label>
                <select name="form_id" id="form_id" onchange="this.form.submit()">
                    <?php foreach ($forms as $form): ?>
                        <option value="<?php echo esc_attr($form->ID); ?>" <?php selected($form->ID, $selected_form_id); ?>>
                            <?php echo esc_html($form->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
    
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
                        echo paginate_links([
                            'base' => add_query_arg(['paged' => '%#%', 'form_id' => $selected_form_id]),
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
                        <?php if (empty($submissions)): ?>
                            <tr>
                                <td colspan="5">Aucune soumission trouvée pour ce formulaire.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($submissions as $submission): ?>
                                <?php
                                $form = get_post($submission->form_id);
                                $submitted_data = json_decode($submission->submitted_data, true);
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
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>
        </div>
        <?php
    }