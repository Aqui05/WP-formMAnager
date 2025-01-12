<?php
fm_display_message();

if (!defined('ABSPATH')) {
    exit;
}


//afficher le formulaire
/*function fm_display_form($atts) {
    $atts = shortcode_atts(['id' => 0], $atts, 'fm_form');
    
    if (!$atts['id']) {
        return 'Formulaire introuvable.';
    }
    
    // Récupérer les styles
    $styles = get_post_meta($atts['id'], '_fm_form_styles', true);
    
    // Commencer le tampon de sortie
    ob_start();
    
    // Ajouter les styles personnalisés
    if ($styles) {
        echo fm_generate_custom_styles($atts['id'], $styles);
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
}*/

function fm_display_form($atts) {
    $atts = shortcode_atts(['id' => 0], $atts, 'fm_form');
    
    if (!$atts['id']) {
        return 'Formulaire introuvable.';
    }
    
    // Récupérer les styles
    $styles = get_post_meta($atts['id'], '_fm_form_styles', true);
    
    // Commencer le tampon de sortie
    ob_start();
    
    // Ajouter les styles personnalisés
    if ($styles) {
        echo fm_generate_custom_styles($atts['id'], $styles);
    }
    
    // Récupérer les champs du formulaire
    $fields = get_post_meta($atts['id'], '_fm_form_fields', true);
    if (empty($fields)) {
        return 'Aucun champ configuré pour ce formulaire.';
    }

    // Ajout de la classe spécifique au formulaire pour le styling
    ?>
    <form method="post" action="" enctype="multipart/form-data" class="fm-form-<?php echo esc_attr($atts['id']); ?>">
        <input type="hidden" name="fm_form_id" value="<?php echo esc_attr($atts['id']); ?>">
        <?php foreach ($fields as $field): ?>
            <div class="fm-form-field">
                <label for="fm_<?php echo esc_attr($field['name']); ?>" class="fm-form-label">
                    <?php echo esc_html($field['name']); ?> :
                </label>
                
                <div class="fm-form-input">
                    <?php
                    switch ($field['type']) {
                        case 'textarea':
                            ?>
                            <textarea id="fm_<?php echo esc_attr($field['name']); ?>" 
                                    name="fm_<?php echo esc_attr($field['name']); ?>" 
                                    class="fm-textarea"
                                    required></textarea>
                            <?php
                            break;

                        case 'select':
                            $options = explode(',', $field['options'] ?? '');
                            ?>
                            <select id="fm_<?php echo esc_attr($field['name']); ?>" 
                                    name="fm_<?php echo esc_attr($field['name']); ?>" 
                                    class="fm-select"
                                    required>
                                <?php foreach ($options as $option): ?>
                                    <option value="<?php echo esc_attr(trim($option)); ?>">
                                        <?php echo esc_html(trim($option)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php
                            break;

                        case 'radio':
                            $options = explode(',', $field['options'] ?? '');
                            ?>
                            <div class="fm-radio-group">
                                <?php foreach ($options as $option): ?>
                                    <label class="fm-radio-label">
                                        <input type="radio" 
                                            name="fm_<?php echo esc_attr($field['name']); ?>" 
                                            value="<?php echo esc_attr(trim($option)); ?>" 
                                            class="fm-radio"
                                            required>
                                        <?php echo esc_html(trim($option)); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <?php
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

                        default:
                            ?>
                            <input type="<?php echo esc_attr($field['type']); ?>" 
                                   id="fm_<?php echo esc_attr($field['name']); ?>" 
                                   name="fm_<?php echo esc_attr($field['name']); ?>" 
                                   class="fm-input"
                                   required>
                            <?php
                            break;
                    }
                    ?>
                </div>
            </div>
        <?php endforeach; ?>

        <button type="submit" name="fm_submit" class="fm-submit-button">Envoyer</button>
    </form>
    <?php
    
    return ob_get_clean();
}


add_shortcode('fm_form', 'fm_display_form');


// Traiter la soumission
/*function fm_handle_submission() {
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
}*/


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
        $user_email = isset($_POST['fm_email']) ? sanitize_email($_POST['fm_email']) : '';

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

        // Envoi de l'email à l'administrateur
        if (!empty($email_config['admin_email'])) {
            $admin_subject = !empty($email_config['admin_subject']) 
                ? $email_config['admin_subject'] 
                : 'Nouvelle soumission de formulaire';

            $admin_headers = ['Content-Type: text/html; charset=UTF-8'];
            
            $sent_to_admin = wp_mail(
                $email_config['admin_email'],
                $admin_subject,
                $email_content['admin'],
                $admin_headers
            );

            if (!$sent_to_admin) {
                error_log('Erreur lors de l\'envoi du mail admin pour le formulaire ' . $form_id);
            }
        }

        // Envoi de l'email à l'utilisateur si une adresse est fournie
        if (!empty($user_email) && !empty($email_config['user_email_enabled'])) {
            $user_subject = !empty($email_config['user_subject']) 
                ? $email_config['user_subject'] 
                : 'Confirmation de votre soumission';

            $user_headers = ['Content-Type: text/html; charset=UTF-8'];
            
            $sent_to_user = wp_mail(
                $user_email,
                $user_subject,
                $email_content['user'],
                $user_headers
            );

            if (!$sent_to_user) {
                error_log('Erreur lors de l\'envoi du mail utilisateur pour le formulaire ' . $form_id);
            }
        }

        // Message de succès et redirection
        fm_set_message('success', !empty($email_config['success_message']) 
            ? $email_config['success_message'] 
            : 'Votre message a été envoyé avec succès.');

        wp_redirect(add_query_arg('fm_submitted', 'true', wp_get_referer()));
        exit;
    }
}

// Fonction pour générer le contenu des emails
function fm_generate_email_content($data, $form_id) {
    // Récupérer la configuration des emails
    $email_config = get_post_meta($form_id, '_fm_form_email_config', true);

    // Contenu pour l'administrateur
    $admin_content = '<html><body>';
    $admin_content .= '<h2>Nouvelle soumission de formulaire</h2>';
    $admin_content .= '<table style="width: 100%; border-collapse: collapse;">';
    
    foreach ($data as $key => $value) {
        $field_name = str_replace('fm_', '', $key);
        $admin_content .= '<tr>';
        $admin_content .= '<th style="text-align: left; padding: 8px; border: 1px solid #ddd;">' . 
            ucfirst($field_name) . '</th>';
        $admin_content .= '<td style="padding: 8px; border: 1px solid #ddd;">' . 
            (is_array($value) ? implode(', ', $value) : $value) . '</td>';
        $admin_content .= '</tr>';
    }
    
    $admin_content .= '</table></body></html>';

    // Contenu pour l'utilisateur
    $user_content = '<html><body>';
    $user_content .= '<h2>' . (!empty($email_config['user_email_title']) 
        ? $email_config['user_email_title'] 
        : 'Confirmation de votre soumission') . '</h2>';
    
    if (!empty($email_config['user_email_message'])) {
        $user_content .= '<p>' . $email_config['user_email_message'] . '</p>';
    }

    $user_content .= '<h3>Récapitulatif de vos informations :</h3>';
    $user_content .= '<table style="width: 100%; border-collapse: collapse;">';
    
    foreach ($data as $key => $value) {
        $field_name = str_replace('fm_', '', $key);
        $user_content .= '<tr>';
        $user_content .= '<th style="text-align: left; padding: 8px; border: 1px solid #ddd;">' . 
            ucfirst($field_name) . '</th>';
        $user_content .= '<td style="padding: 8px; border: 1px solid #ddd;">' . 
            (is_array($value) ? implode(', ', $value) : $value) . '</td>';
        $user_content .= '</tr>';
    }
    
    $user_content .= '</table></body></html>';

    return [
        'admin' => $admin_content,
        'user' => $user_content
    ];
}

// Fonction pour gérer les messages
function fm_set_message($type, $message) {
    if (!session_id()) {
        session_start();
    }
    $_SESSION['fm_message'] = [
        'type' => $type,
        'text' => $message
    ];
}

// Fonction pour afficher les messages
function fm_display_message() {
    if (!session_id()) {
        session_start();
    }

    if (isset($_SESSION['fm_message'])) {
        $message = $_SESSION['fm_message'];
        $class = $message['type'] === 'error' ? 'fm-error' : 'fm-success';
        
        echo '<div class="fm-message ' . esc_attr($class) . '">' . 
            esc_html($message['text']) . 
            '</div>';
        
        unset($_SESSION['fm_message']);
    }
}

// Ajouter les styles pour les messages
function fm_add_message_styles() {
    ?>
    <style>
        .fm-message {
            padding: 10px 15px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .fm-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .fm-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
    <?php
}
add_action('wp_head', 'fm_add_message_styles');

function fm_generate_custom_styles($form_id, $styles) {
    $css = '<style>';
    
    // Styles de base du formulaire
    $css .= sprintf('.fm-form-%d {', $form_id);
    $css .= 'padding: 20px;';
    $css .= 'margin: 0 auto;';
    $css .= 'max-width: 600px;';
    if (!empty($styles['background_color'])) {
        $css .= sprintf('background-color: %s;', esc_attr($styles['background_color']));
    }
    if (!empty($styles['text_color'])) {
        $css .= sprintf('color: %s;', esc_attr($styles['text_color']));
    }
    if (!empty($styles['background_image'])) {
        $css .= sprintf('background-image: url("%s");', esc_url($styles['background_image']));
        $css .= 'background-size: cover;';
        $css .= 'background-position: center;';
    }
    $css .= '}';

    // Styles des champs
    $css .= sprintf('.fm-form-%d .fm-form-field {', $form_id);
    $css .= 'margin-bottom: 15px;';
    $css .= '}';

    // Styles des labels
    $css .= sprintf('.fm-form-%d .fm-form-label {', $form_id);
    $css .= 'display: block;';
    $css .= 'margin-bottom: 5px;';
    $css .= 'font-weight: bold;';
    if (!empty($styles['text_color'])) {
        $css .= sprintf('color: %s;', esc_attr($styles['text_color']));
    }
    $css .= '}';

    // Styles des inputs et textarea
    $css .= sprintf('.fm-form-%d .fm-input,', $form_id);
    $css .= sprintf('.fm-form-%d .fm-textarea,', $form_id);
    $css .= sprintf('.fm-form-%d .fm-select {', $form_id);
    $css .= 'width: 100%;';
    $css .= 'padding: 8px;';
    $css .= 'border-radius: 4px;';
    if (!empty($styles['border_color'])) {
        $css .= sprintf('border: 1px solid %s;', esc_attr($styles['border_color']));
    }
    if (!empty($styles['text_color'])) {
        $css .= sprintf('color: %s;', esc_attr($styles['text_color']));
    }
    $css .= '}';

    // Styles du bouton submit
    $css .= sprintf('.fm-form-%d .fm-submit-button {', $form_id);
    $css .= 'padding: 10px 20px;';
    $css .= 'border: none;';
    $css .= 'border-radius: 4px;';
    $css .= 'cursor: pointer;';
    if (!empty($styles['button_color'])) {
        $css .= sprintf('background-color: %s;', esc_attr($styles['button_color']));
    }
    if (!empty($styles['button_text_color'])) {
        $css .= sprintf('color: %s;', esc_attr($styles['button_text_color']));
    }
    $css .= '}';

    // Ajouter le CSS personnalisé
    if (!empty($styles['custom_css'])) {
        $custom_css = str_replace(
            '.fm-form',
            sprintf('.fm-form-%d', $form_id),
            $styles['custom_css']
        );
        $css .= $custom_css;
    }
    
    $css .= '</style>';
    
    return $css;
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
