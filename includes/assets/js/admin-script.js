
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

    // Gestion de l'exportation individuelle
    $('.export-submission').on('click', function(e) {
        e.preventDefault();

        console.log('Exporting submission...');
        const submissionId = $(this).data('id');

        $.ajax({
            url: formSubmissionsAjax.ajaxurl,
            method: 'POST',
            data: {
                action: 'export_submissions_csv',
                submissions_ids: [submissionId],
                nonce: formSubmissionsAjax.nonce
            },
            xhrFields: {
                responseType: 'blob'
            },
            success: function(response) {
                const blob = new Blob([response], { type: 'text/csv' });
                const downloadUrl = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = downloadUrl;
                a.download = 'soumission_export_' + submissionId + '_' + new Date().toISOString().split('T')[0] + '.csv';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(downloadUrl);
            },
            error: function() {
                alert('Une erreur s\'est produite lors de l\'exportation des données.');
            }
        });
    });
});
       