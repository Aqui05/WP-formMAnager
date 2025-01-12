jQuery(document).ready(function($) {
    // Initialiser les color pickers
    $('.fm-color-picker').wpColorPicker();
    
    // Gestionnaire pour l'upload d'image
    $('#fm_upload_image').click(function(e) {
        e.preventDefault();
        
        var image_frame;
        if (image_frame) {
            image_frame.open();
            return;
        }
        
        image_frame = wp.media({
            title: 'Sélectionner une image de fond',
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        image_frame.on('select', function() {
            var attachment = image_frame.state().get('selection').first().toJSON();
            $('#fm_background_image').val(attachment.url);
        });
        
        image_frame.open();
    });
});