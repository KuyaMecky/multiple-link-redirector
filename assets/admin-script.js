jQuery(document).ready(function($) {
    const $modal = $('#redirect-modal');
    const $form = $('#redirect-form');
    const $table = $('#redirects-table');

    // Add new redirect button
    $('#add-redirect-btn').on('click', function() {
        $('#modal-title').text('Add New Redirect');
        $('#redirect-index').val(-1);
        $('#original-url, #target-url').val('');
        $modal.show();
    });

    // Edit redirect
    $table.on('click', '.edit-redirect', function() {
        const index = $(this).data('index');
        const $row = $(this).closest('tr');
        const originalUrl = $row.find('.original-url').text();
        const targetUrl = $row.find('.target-url').text();

        $('#modal-title').text('Edit Redirect');
        $('#redirect-index').val(index);
        $('#original-url').val(originalUrl);
        $('#target-url').val(targetUrl);
        $modal.show();
    });

    // Delete redirect
    $table.on('click', '.delete-redirect', function() {
        if (!confirm('Are you sure you want to delete this redirect?')) return;

        const index = $(this).data('index');

        $.ajax({
            url: mlrAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'mlr_delete_redirect',
                nonce: mlrAjax.nonce,
                index: index
            },
            success: function(response) {
                if (response.success) {
                    $(`tr[data-index="${index}"]`).remove();
                }
            }
        });
    });

    // Form submission
    $form.on('submit', function(e) {
        e.preventDefault();

        const index = $('#redirect-index').val();
        const originalUrl = $('#original-url').val();
        const targetUrl = $('#target-url').val();

        $.ajax({
            url: mlrAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'mlr_edit_redirect',
                nonce: mlrAjax.nonce,
                index: index,
                original: originalUrl,
                target: targetUrl
            },
            success: function(response) {
                if (response.success) {
                    $modal.hide();
                    location.reload(); // Simple refresh for now
                }
            }
        });
    });

    // Close modal
    $('.mlr-close-modal').on('click', function() {
        $modal.hide();
    });
});