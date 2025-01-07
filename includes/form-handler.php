<?php

if (!defined('ABSPATH')) {
    exit;
}

// Afficher le formulaire
function fm_display_form($atts) {
    $atts = shortcode_atts(['id' => 0], $atts, 'fm_form');

    if (!$atts['id']) {
        return 'Formulaire introuvable.';
    }

    // Récupérer les champs du formulaire
    $fields = get_post_meta($atts['id'], '_fm_form_fields', true);
    if (empty($fields)) {
        return 'Aucun champ configuré pour ce formulaire.';
    }

    ob_start();
    ?>
    <form method="post" action="" enctype="multipart/form-data">
        <input type="hidden" name="fm_form_id" value="<?php echo esc_attr($atts['id']); ?>">
        <?php foreach ($fields as $field): ?>
            <div>
                <label for="fm_<?php echo esc_attr($field['name']); ?>">
                    <?php echo esc_html($field['name']); ?> :
                </label>
                
                <?php
                switch ($field['type']) {
                    case 'textarea': // Champ texte multiligne
                        ?>
                        <textarea id="fm_<?php echo esc_attr($field['name']); ?>" 
                                  name="fm_<?php echo esc_attr($field['name']); ?>" 
                                  required></textarea>
                        <?php
                        break;

                    case 'select': // Liste déroulante
                        $options = explode(',', $field['options'] ?? '');
                        ?>
                        <select id="fm_<?php echo esc_attr($field['name']); ?>" 
                                name="fm_<?php echo esc_attr($field['name']); ?>" required>
                            <?php foreach ($options as $option): ?>
                                <option value="<?php echo esc_attr(trim($option)); ?>">
                                    <?php echo esc_html(trim($option)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php
                        break;

                    case 'radio': // Boutons radio
                        $options = explode(',', $field['options'] ?? '');
                        foreach ($options as $option): ?>
                            <label>
                                <input type="radio" 
                                       name="fm_<?php echo esc_attr($field['name']); ?>" 
                                       value="<?php echo esc_attr(trim($option)); ?>" 
                                       required>
                                <?php echo esc_html(trim($option)); ?>
                            </label>
                        <?php endforeach;
                        break;

                    case 'checkbox': // Cases à cocher
                        $options = explode(',', $field['options'] ?? '');
                        foreach ($options as $option): ?>
                            <label>
                                <input type="checkbox" 
                                       name="fm_<?php echo esc_attr($field['name']); ?>[]" 
                                       value="<?php echo esc_attr(trim($option)); ?>">
                                <?php echo esc_html(trim($option)); ?>
                            </label>
                        <?php endforeach;
                        break;

                    case 'file': // Téléchargement de fichiers
                        ?>
                        <input type="file" 
                               id="fm_<?php echo esc_attr($field['name']); ?>" 
                               name="fm_<?php echo esc_attr($field['name']); ?>" 
                               required>
                        <?php
                        break;

                    case 'captcha': // CAPTCHA/Recaptcha
                        ?>
                        <div id="recaptcha-container">
                            <div class="g-recaptcha" 
                                 data-sitekey="6LdifKYqAAAAAG7s49WzjqhQ9kinCF-bGFfpPb_N"></div>
                        </div>
                        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                        <?php
                        break;

                        
                        case 'map':
                            ?>
                            <input type="text" 
                                   id="fm_search_<?php echo esc_attr($field['name']); ?>" 
                                   placeholder="Rechercher une adresse"
                                   style="width: 100%; margin-bottom: 10px;">
                            
                            <input type="text" 
                                   id="fm_<?php echo esc_attr($field['name']); ?>" 
                                   name="fm_<?php echo esc_attr($field['name']); ?>" 
                                   class="map-coordinates"
                                   placeholder="Latitude, Longitude"
                                   readonly>
                            
                            <iframe 
                                id="map_<?php echo esc_attr($field['name']); ?>"
                                width="100%" 
                                height="300"
                                style="border:0; margin-top: 10px;"
                                src="https://maps.google.com/maps?q=48.8566,2.3522&t=&z=13&ie=UTF8&iwloc=&output=embed"
                                allowfullscreen>
                            </iframe>
                        
                            <script>
                                (function() {
                                    const searchInput = document.getElementById('fm_search_<?php echo esc_attr($field['name']); ?>');
                                    const coordInput = document.getElementById('fm_<?php echo esc_attr($field['name']); ?>');
                                    const mapFrame = document.getElementById('map_<?php echo esc_attr($field['name']); ?>');
                        
                                    // Géolocalisation initiale
                                    if (navigator.geolocation) {
                                        navigator.geolocation.getCurrentPosition(function(position) {
                                            updateMap(position.coords.latitude, position.coords.longitude);
                                        });
                                    }
                        
                                    // Fonction de mise à jour de la carte
                                    function updateMap(lat, lng) {
                                        coordInput.value = lat.toFixed(6) + ', ' + lng.toFixed(6);
                                        mapFrame.src = 'https://maps.google.com/maps?q=' + lat + ',' + lng + '&t=&z=13&ie=UTF8&iwloc=&output=embed';
                                    }
                        
                                    // Recherche d'adresse
                                    searchInput.addEventListener('keypress', function(e) {
                                        if (e.key === 'Enter') {
                                            e.preventDefault();
                                            const address = encodeURIComponent(this.value);
                                            
                                            // Utiliser l'API de géocodage Nominatim (OpenStreetMap)
                                            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${address}`)
                                                .then(response => response.json())
                                                .then(data => {
                                                    if (data.length > 0) {
                                                        const result = data[0];
                                                        updateMap(parseFloat(result.lat), parseFloat(result.lon));
                                                    } else {
                                                        alert('Adresse non trouvée');
                                                    }
                                                })
                                                .catch(error => {
                                                    console.error('Erreur:', error);
                                                    alert('Erreur lors de la recherche');
                                                });
                                        }
                                    });
                        
                                    // Permettre le clic sur la carte (en utilisant postMessage)
                                    window.addEventListener('message', function(e) {
                                        if (e.data.type === 'mapClick') {
                                            updateMap(e.data.lat, e.data.lng);
                                        }
                                    });
                                })();
                            </script>
                            <?php
                            break;
                    

                    default: // Champ texte ou autre
                        ?>
                        <input type="<?php echo esc_attr($field['type']); ?>" 
                               id="fm_<?php echo esc_attr($field['name']); ?>" 
                               name="fm_<?php echo esc_attr($field['name']); ?>" 
                               required>
                        <?php
                        break;
                }
                ?>
            </div>
        <?php endforeach; ?>

        <button type="submit" name="fm_submit">Envoyer</button>
    </form>
    <?php

    return ob_get_clean();
}

add_shortcode('fm_form', 'fm_display_form');


// Traiter la soumission
function fm_handle_submission() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fm_submit'])) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'form_submissions';

        // ID du formulaire
        $form_id = isset($_POST['fm_form_id']) ? intval($_POST['fm_form_id']) : 0;

        if (empty($form_id)) {
            wp_redirect(add_query_arg('fm_error', 'invalid_form', wp_get_referer()));
            exit;
        }

        // Récupérer toutes les données soumises, sauf les champs spécifiques
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
            'submitted_data' => wp_json_encode($cleaned_data), // Convertir en JSON
            'status' => 'new', // Statut par défaut
            'submitted_at' => current_time('mysql'),
        ];

        // Insertion dans la base de données
        $wpdb->insert($table_name, $data);

        // Redirection après soumission
        wp_redirect(add_query_arg('fm_submitted', 'true', wp_get_referer()));
        exit;
    }
}

add_action('init', 'fm_handle_submission');


// * Ajouter une page dans le menu admin
/*function fm_register_admin_page() {
    add_menu_page(
        'Soumissions',
        'Soumissions',
        'manage_options',
        'fm-submissions',
        'fm_display_submissions',
        'dashicons-list-view',
        20
    );
}
add_action('admin_menu', 'fm_register_admin_page');*/

// * Afficher les soumissions
/*function fm_display_submissions() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'form_submissions';
    $results = $wpdb->get_results("SELECT * FROM $table_name");

    if (empty($results)) {
        echo '<p>Aucune soumission trouvée.</p>';
        return;
    }

    echo '<table>';
    echo '<tr><th>ID</th><th>Form ID</th><th>Submitted Data</th><th>Status</th><th>Submitted At</th></tr>';

    foreach ($results as $row) {
        $submitted_data = json_decode($row->submitted_data, true);

        echo '<tr>';
        echo '<td>' . esc_html($row->id) . '</td>';
        echo '<td>' . esc_html($row->form_id) . '</td>';
        echo '<td>';
        foreach ($submitted_data as $key => $value) {
            echo '<strong>' . esc_html($key) . '</strong>: ' . esc_html($value) . '<br>';
        }
        echo '</td>';
        echo '<td>' . esc_html($row->status) . '</td>';
        echo '<td>' . esc_html($row->submitted_at) . '</td>';
        echo '</tr>';
    }

    echo '</table>';
}*/
