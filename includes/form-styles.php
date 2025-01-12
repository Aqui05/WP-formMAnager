<?php
// includes/form-styles.php

// Afficher la metabox des styles
function fm_display_style_metabox($post) {
    // Sécurité
    wp_nonce_field('fm_save_styles', 'fm_style_nonce');
    
    // Récupérer les styles existants ou utiliser les valeurs par défaut
    $styles = get_post_meta($post->ID, '_fm_form_styles', true) ?: [
        'background_color' => '#ffffff',
        'text_color' => '#000000',
        'border_color' => '#cccccc',
        'button_color' => '#4CAF50',
        'button_text_color' => '#ffffff',
        'background_image' => '',
        'custom_css' => ''
    ];
    ?>
    <div class="fm-style-settings">
        <table class="form-table">
            <tr>
                <th><label for="fm_background_color">Couleur de fond</label></th>
                <td>
                    <input type="text" 
                           id="fm_background_color" 
                           name="fm_styles[background_color]" 
                           value="<?php echo esc_attr($styles['background_color']); ?>"
                           class="fm-color-picker">
                </td>
            </tr>
            <tr>
                <th><label for="fm_text_color">Couleur du texte</label></th>
                <td>
                    <input type="text" 
                           id="fm_text_color" 
                           name="fm_styles[text_color]" 
                           value="<?php echo esc_attr($styles['text_color']); ?>"
                           class="fm-color-picker">
                </td>
            </tr>
            <tr>
                <th><label for="fm_border_color">Couleur des bordures</label></th>
                <td>
                    <input type="text" 
                           id="fm_border_color" 
                           name="fm_styles[border_color]" 
                           value="<?php echo esc_attr($styles['border_color']); ?>"
                           class="fm-color-picker">
                </td>
            </tr>
            <tr>
                <th><label for="fm_button_color">Couleur des boutons</label></th>
                <td>
                    <input type="text" 
                           id="fm_button_color" 
                           name="fm_styles[button_color]" 
                           value="<?php echo esc_attr($styles['button_color']); ?>"
                           class="fm-color-picker">
                </td>
            </tr>
            <tr>
                <th><label for="fm_button_text_color">Couleur du texte des boutons</label></th>
                <td>
                    <input type="text" 
                           id="fm_button_text_color" 
                           name="fm_styles[button_text_color]" 
                           value="<?php echo esc_attr($styles['button_text_color']); ?>"
                           class="fm-color-picker">
                </td>
            </tr>
            <tr>
                <th><label for="fm_background_image">Image de fond</label></th>
                <td>
                    <input type="url" 
                           id="fm_background_image" 
                           name="fm_styles[background_image]" 
                           value="<?php echo esc_url($styles['background_image']); ?>"
                           class="regular-text">
                    <button type="button" class="button" id="fm_upload_image">Choisir une image</button>
                </td>
            </tr>
            <tr>
                <th><label for="fm_custom_css">CSS personnalisé</label></th>
                <td>
                    <textarea id="fm_custom_css" 
                              name="fm_styles[custom_css]" 
                              rows="5" 
                              class="large-text code"><?php echo esc_textarea($styles['custom_css']); ?></textarea>
                </td>
            </tr>
        </table>
    </div>
    <?php
}

// Sauvegarder les styles
function fm_save_form_styles($post_id) {
    // Vérifications de sécurité
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['fm_style_nonce']) || !wp_verify_nonce($_POST['fm_style_nonce'], 'fm_save_styles')) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    // Sauvegarder les styles
    if (isset($_POST['fm_styles'])) {
        $styles = [
            'background_color' => sanitize_hex_color($_POST['fm_styles']['background_color']),
            'text_color' => sanitize_hex_color($_POST['fm_styles']['text_color']),
            'border_color' => sanitize_hex_color($_POST['fm_styles']['border_color']),
            'button_color' => sanitize_hex_color($_POST['fm_styles']['button_color']),
            'button_text_color' => sanitize_hex_color($_POST['fm_styles']['button_text_color']),
            'background_image' => esc_url_raw($_POST['fm_styles']['background_image']),
            'custom_css' => sanitize_textarea_field($_POST['fm_styles']['custom_css'])
        ];
        
        update_post_meta($post_id, '_fm_form_styles', $styles);
    }
}
add_action('save_post', 'fm_save_form_styles');

// Enregistrer les scripts et styles nécessaires
function fm_enqueue_admin_scripts($hook) {
    // Ne charger que sur les pages d'édition de formulaire
    global $post;
    if ($hook != 'post.php' && $hook != 'post-new.php') return;
    if (get_post_type($post) != 'fm_form') return;
    
    // Color picker
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
    
    // Media uploader
    wp_enqueue_media();
    
    // Notre script personnalisé
    wp_enqueue_script('fm-admin-script', plugins_url('/assets/js/admin-script.js', dirname(__FILE__)), 
        array('jquery', 'wp-color-picker'), null, true);
}
add_action('admin_enqueue_scripts', 'fm_enqueue_admin_scripts');