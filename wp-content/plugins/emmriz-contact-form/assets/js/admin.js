jQuery(function($) {
    $(document).on('click', '.view-message', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        $('#ecf-modal-content').html('<p>Loading...</p>');
        $('#ecf-modal').fadeIn();

        $.post(ECFAdmin.ajax_url, {
            action: 'ecf_get_message',
            id: id,
            nonce: ECFAdmin.nonce
        }, function(res) {
            if (res.success) {
                $('#ecf-modal-content').html(res.data.html);
            } else {
                $('#ecf-modal-content').html('<p>Error loading message.</p>');
            }
        });
    });

    $(document).on('click', '.modal-close', function() {
        $('#ecf-modal').fadeOut();
    });

    $(document).on('click', '.delete-message', function(e) {
        e.preventDefault();
        if (!confirm(ECFAdmin.confirm_delete)) return;
        const id = $(this).data('id');
        const $row = $(this).closest('tr');

        $.post(ECFAdmin.ajax_url, {
            action: 'ecf_delete_message',
            id: id,
            nonce: ECFAdmin.nonce
        }, function(res) {
            if (res.success) {
                $row.fadeOut();
                $('#ecf-modal').fadeOut();
            } else {
                alert('Delete failed.');
            }
        });
    });

    // click outside modal
    $(document).on('click', '#ecf-modal', function(e) {
        if (e.target.id === 'ecf-modal') $('#ecf-modal').fadeOut();
    });
});
