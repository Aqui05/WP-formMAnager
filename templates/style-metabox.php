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
        
        <!-- Répéter pour chaque option de couleur -->
        
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
