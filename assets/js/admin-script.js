
/* assets/js/admin-script.js */
jQuery(document).ready(function($) {
    // Gestion de la suppression individuelle
    $('.delete-submission').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette soumission ?')) {
            return;
        }

        var $row = $(this).closest('tr');
        var submissionId = $(this).data('id');

        $.ajax({
            url: formSubmissionsAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_submission',
                id: submissionId,
                nonce: formSubmissionsAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(400, function() {
                        $(this).remove();
                    });
                } else {
                    alert('Erreur lors de la suppression.');
                }
            }
        });
    });

    // Gestion de la case à cocher principale
    $('#cb-select-all-1').on('change', function() {
        var isChecked = $(this).prop('checked');
        $('input[name="submissions[]"]').prop('checked', isChecked);
    });
});