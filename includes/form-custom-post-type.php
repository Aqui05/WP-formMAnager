<?php

if (!defined('ABSPATH')) {
    exit;
}

// Création du Custom Post Type "Formulaires"
function fm_create_custom_post_type() {
    register_post_type('fm_form', [
        'labels' => [
            'name' => 'Formulaires',
            'singular_name' => 'Formulaire',
            'add_new' => 'Ajouter un formulaire',
            'add_new_item' => 'Ajouter un nouveau formulaire',
            'edit_item' => 'Modifier le formulaire',
            'new_item' => 'Nouveau formulaire',
            'view_item' => 'Voir le formulaire',
            'search_items' => 'Rechercher des formulaires',
            'not_found' => 'Aucun formulaire trouvé',
            'not_found_in_trash' => 'Aucun formulaire trouvé dans la corbeille',
        ],
        'public' => true,
        'has_archive' => true,
        'supports' => ['title', 'editor'],
        'menu_icon' => 'dashicons-feedback',
    ]);
}
add_action('init', 'fm_create_custom_post_type');


// Ajouter une action "Dupliquer" dans la liste des formulaires
function fm_add_duplicate_action($actions, $post) {
    // Vérifier si le post est du type 'fm_form'
    if ($post->post_type === 'fm_form') {
        $duplicate_link = admin_url('admin.php?action=fm_duplicate_form&post=' . $post->ID);
        $actions['duplicate'] = '<a href="' . esc_url($duplicate_link) . '" title="Dupliquer ce formulaire">Dupliquer</a>';
    }
    return $actions;
}
add_filter('post_row_actions', 'fm_add_duplicate_action', 10, 2);

// Traiter l'action "Dupliquer"
function fm_duplicate_form() {
    // Vérifier les permissions
    if (!current_user_can('edit_posts')) {
        wp_die('Vous n’avez pas la permission de dupliquer ce formulaire.');
    }

    // Vérifier les paramètres
    if (!isset($_GET['post']) || !is_numeric($_GET['post'])) {
        wp_die('Aucun formulaire spécifié.');
    }

    $post_id = intval($_GET['post']);
    $original_post = get_post($post_id);

    // Vérifier si le formulaire existe
    if (!$original_post || $original_post->post_type !== 'fm_form') {
        wp_die('Formulaire non valide.');
    }

    // Dupliquer le formulaire
    $new_post = [
        'post_title'   => $original_post->post_title . ' (Copie)',
        'post_content' => $original_post->post_content,
        'post_status'  => 'draft',
        'post_type'    => 'fm_form',
    ];

    // Insérer le nouveau formulaire
    $new_post_id = wp_insert_post($new_post);

    // Copier les métadonnées associées
    $meta_data = get_post_meta($post_id);
    foreach ($meta_data as $meta_key => $meta_value) {
        if (is_array($meta_value)) {
            foreach ($meta_value as $value) {
                add_post_meta($new_post_id, $meta_key, maybe_unserialize($value));
            }
        } else {
            add_post_meta($new_post_id, $meta_key, maybe_unserialize($meta_value));
        }
    }

    // Rediriger vers la liste des formulaires
    wp_redirect(admin_url('edit.php?post_type=fm_form'));
    exit;
}
add_action('admin_action_fm_duplicate_form', 'fm_duplicate_form');


// Ajouter les colonnes personnalisées
function fm_add_custom_columns($columns) {
    $columns['shortcode'] = 'Shortcode';
    $columns['submissions'] = 'Nombre de soumissions';
    return $columns;
}
add_filter('manage_edit-fm_form_columns', 'fm_add_custom_columns');

// Afficher les données des colonnes personnalisées
function fm_render_custom_columns($column, $post_id) {
    global $wpdb;

    switch ($column) {
        case 'shortcode':
            echo '[fm_form id="' . $post_id . '"]';
            break;

        case 'submissions':
            $table_name = $wpdb->prefix . 'form_submissions';
            $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE form_id = %d", $post_id));
            echo $count ? $count : '0';
            break;
    }
}
add_action('manage_fm_form_posts_custom_column', 'fm_render_custom_columns', 10, 2);

// Ajouter des styles pour les colonnes (facultatif)
function fm_custom_column_styles() {
    echo '<style>
        .column-shortcode, .column-submissions {
            text-align: center;
            width: 150px;
        }
    </style>';
}
add_action('admin_head', 'fm_custom_column_styles');



// Ajouter une métabox pour les champs du formulaire
function fm_add_form_fields_metabox() {
    add_meta_box(
        'fm_form_fields',
        'Champs du formulaire',
        'fm_render_form_fields_metabox',
        'fm_form',
        'normal',
        'high'
    );

    add_meta_box(
        'fm_form_styles',
        'Personnalisation du style',
        'fm_display_style_metabox',
        'fm_form',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'fm_add_form_fields_metabox');

// Rendu de la métabox
function fm_render_form_fields_metabox($post) {
    // Récupérer les valeurs enregistrées
    $fields = get_post_meta($post->ID, '_fm_form_fields', true);

    if (empty($fields)) {
        $fields = [];
    }

    ?>
    <div id="fm-fields-container">
        <?php foreach ($fields as $index => $field): ?>
            <div class="fm-field">
                <label>Type de champ :</label>
                <select name="fm_form_fields[<?php echo $index; ?>][type]">
                    <option value="text" <?php selected($field['type'], 'text'); ?>>Texte (simple ligne)</option>
                    <option value="textarea" <?php selected($field['type'], 'textarea'); ?>>Texte (multi-lignes)</option>
                    <option value="email" <?php selected($field['type'], 'email'); ?>>Email</option>
                    <option value="number" <?php selected($field['type'], 'number'); ?>>Numéro</option>
                    <option value="map" <?php selected($field['type'], 'map'); ?>>Localisation (Google Maps)</option>
                    <option value="select" <?php selected($field['type'], 'select'); ?>>Liste déroulante</option>
                    <option value="radio" <?php selected($field['type'], 'radio'); ?>>Boutons radio</option>
                    <option value="checkbox" <?php selected($field['type'], 'checkbox'); ?>>Cases à cocher</option>
                    <option value="file" <?php selected($field['type'], 'file'); ?>>Téléchargement de fichiers</option>
                    <option value="captcha" <?php selected($field['type'], 'captcha'); ?>>CAPTCHA/Recaptcha</option>
                </select>
                <label>Nom du champ :</label>
                <input type="text" name="fm_form_fields[<?php echo $index; ?>][name]" value="<?php echo esc_attr($field['name']); ?>" />

                <?php if (in_array($field['type'], ['select', 'radio', 'checkbox'])): ?>
                    <label>Options (séparées par une virgule) :</label>
                    <input type="text" name="fm_form_fields[<?php echo $index; ?>][options]" value="<?php echo esc_attr($field['options'] ?? ''); ?>" />
                <?php endif; ?>

                <button type="button" class="button fm-remove-field">Supprimer</button>
            </div>
        <?php endforeach; ?>
    </div>
    <button type="button" id="fm-add-field" class="button">Ajouter un champ</button>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('fm-fields-container');
            const addFieldButton = document.getElementById('fm-add-field');
            let fieldIndex = <?php echo count($fields); ?>;

            addFieldButton.addEventListener('click', function () {
                const fieldHTML = `
                    <div class="fm-field">
                        <label>Type de champ :</label>
                        <select name="fm_form_fields[${fieldIndex}][type]">
                            <option value="text">Texte (simple ligne)</option>
                            <option value="textarea">Texte (multi-lignes)</option>
                            <option value="email">Email</option>
                            <option value="tel">Téléphone</option>
                            <option value="map">Localisation (Google Maps)</option>
                            <option value="select">Liste déroulante</option>
                            <option value="radio">Boutons radio</option>
                            <option value="checkbox">Cases à cocher</option>
                            <option value="file">Téléchargement de fichiers</option>
                            <option value="captcha">CAPTCHA/Recaptcha</option>
                        </select>
                        <label>Nom du champ :</label>
                        <input type="text" name="fm_form_fields[${fieldIndex}][name]" />
                        
                        <div class="conditional-options" style="display:none;">
                            <label>Options (séparées par une virgule) :</label>
                            <input type="text" name="fm_form_fields[${fieldIndex}][options]" />
                        </div>
                        
                        <button type="button" class="button fm-remove-field">Supprimer</button>
                    </div>`;
                container.insertAdjacentHTML('beforeend', fieldHTML);

                const newField = container.lastElementChild;
                const typeSelect = newField.querySelector('select[name^="fm_form_fields"]');
                const optionsInput = newField.querySelector('.conditional-options');

                typeSelect.addEventListener('change', function () {
                    if (['select', 'radio', 'checkbox'].includes(this.value)) {
                        optionsInput.style.display = 'block';
                    } else {
                        optionsInput.style.display = 'none';
                    }
                });

                fieldIndex++;
            });

            container.addEventListener('click', function (e) {
                if (e.target.classList.contains('fm-remove-field')) {
                    e.target.parentElement.remove();
                }
            });
        });
    </script>
    <style>
        .fm-field {
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #ccc;
            background: #f9f9f9;
        }
        .fm-field label {
            display: block;
            margin-bottom: 5px;
        }
        .fm-field input, .fm-field select {
            width: 100%;
            margin-bottom: 5px;
        }
        .fm-field button.fm-remove-field {
            margin-top: 5px;
            background-color: #dc3545;
            color: #fff;
        }
    </style>
    <?php
}


// Sauvegarder les champs du formulaire
function fm_save_form_fields($post_id) {
    if (array_key_exists('fm_form_fields', $_POST)) {
        update_post_meta($post_id, '_fm_form_fields', $_POST['fm_form_fields']);
    }
}
add_action('save_post', 'fm_save_form_fields');



// Filtrer le contenu pour les formulaires
function fm_filter_form_content($content) {
    // Vérifier si nous sommes sur un post de type fm_form
    if (is_singular('fm_form') && in_the_loop() && is_main_query()) {
        global $post;
        
        // Récupérer le shortcode du formulaire
        $shortcode = '[fm_form id="' . $post->ID . '"]';
        
        // Ajouter une description si nécessaire
        $description = '';
        if (!empty($post->post_content)) {
            $description = '<div class="form-description">' . $post->post_content . '</div>';
        }
        
        // Construire le contenu complet
        $form_content = sprintf(
            '<div class="form-container">
                %s
                %s
            </div>',
            $description,
            do_shortcode($shortcode)
        );
        
        // Ajouter des styles personnalisés
        $form_content .= '
        <style>
            .form-container {
                max-width: 800px;
                margin: 2em auto;
                padding: 20px;
            }
            .form-description {
                margin-bottom: 2em;
                padding: 15px;
                background: #f9f9f9;
                border-left: 4px solid #0073aa;
            }
        </style>';
        
        return $form_content;
    }
    
    return $content;
}
add_filter('the_content', 'fm_filter_form_content');

// Modifier le template pour les formulaires
function fm_form_template($template) {
    if (is_singular('fm_form')) {
        // Utiliser le template de page par défaut si disponible
        $new_template = locate_template(array('page.php', 'single.php', 'index.php'));
        if (!empty($new_template)) {
            return $new_template;
        }
    }
    return $template;
}
add_filter('single_template', 'fm_form_template');

// Ajouter le support des styles aux formulaires
function fm_add_form_theme_support() {
    add_theme_support('post-thumbnails', array('fm_form'));
    add_theme_support('custom-background', array('fm_form'));
}
add_action('after_setup_theme', 'fm_add_form_theme_support');

// Modifier le titre pour la vue unique et la prévisualisation
function fm_modify_form_title($title, $post_id = null) {
    if (is_singular('fm_form') && in_the_loop() && is_main_query()) {
        $post = get_post($post_id);
        if ($post && $post->post_type === 'fm_form') {
            return sprintf(
                '%s %s',
                __('Formulaire :', 'text-domain'),
                $title
            );
        }
    }
    return $title;
}
add_filter('the_title', 'fm_modify_form_title', 10, 2);
