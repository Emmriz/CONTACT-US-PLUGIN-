jQuery(function($){
    // Select all checkbox
    $('#emmriztech-select-all').on('change', function(){
        $('input[name="ids[]"]').prop('checked', $(this).prop('checked'));
    });

    // Open modal and fetch message
    $(document).on('click', '.emmriztech-view-message', function(e){
        e.preventDefault();
        var id = $(this).data('id');
        if (!id) return;

        // show loading
        $('#emmriztech-modal-content').html('<p>Loading…</p>');
        $('#emmriztech-modal').show();

        $.post(EmmrizTechAdmin.ajax_url, {
            action: 'emmriztech_get_message',
            id: id,
            nonce: EmmrizTechAdmin.nonce
        }, function(response){
            if (response.success) {
                $('#emmriztech-modal-content').html(response.data.html);
            } else {
                $('#emmriztech-modal-content').html('<p>Error loading message.</p>');
            }
        }).fail(function(){
            $('#emmriztech-modal-content').html('<p>Request failed.</p>');
        });
    });

    // Close modal (close button)
    $(document).on('click', '#emmriztech-modal-close, .emmriztech-modal-close-inline', function(e){
        e.preventDefault();
        $('#emmriztech-modal').hide();
        $('#emmriztech-modal-content').empty();
    });

    // Delete message (from table or modal)
    $(document).on('click', '.emmriztech-delete-message', function(e){
        e.preventDefault();
        if (!confirm(EmmrizTechAdmin.confirm_delete)) return;

        var id = $(this).data('id');
        if (!id) return;

        var $row = $('input[name="ids[]"][value="'+id+'"]').closest('tr');

        $.post(EmmrizTechAdmin.ajax_url, {
            action: 'emmriztech_delete_message',
            id: id,
            nonce: EmmrizTechAdmin.nonce
        }, function(response){
            if (response.success) {
                // remove row and hide modal if open
                if ($row.length) $row.fadeOut(200, function(){ $(this).remove(); });
                $('#emmriztech-modal').hide();
                $('#emmriztech-modal-content').empty();
            } else {
                alert('Delete failed.');
            }
        }).fail(function(){
            alert('Request failed.');
        });
    });

    // Close modal if clicked outside the inner box
    $(document).on('click', '#emmriztech-modal', function(e){
        if (e.target.id === 'emmriztech-modal') {
            $('#emmriztech-modal').hide();
            $('#emmriztech-modal-content').empty();
        }
    });
});
