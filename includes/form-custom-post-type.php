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
                    <option value="tel" <?php selected($field['type'], 'tel'); ?>>Téléphone</option>
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
