jQuery(document).ready(function($) {

    // Show modal and populate form with testimonial data
    $('.edit-testimonial').on('click', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        
        // Send AJAX request to fetch testimonial data
        $.post(tp_testimonials_obj.ajaxurl, {
            action: 'tp_get_testimonial',
            testimonial_id: id,
            nonce: tp_testimonials_obj.nonce
        }, function(response) {
            if (response.success) {
                var testimonial = response.data;

                $('#tp_testimonial_id').val(testimonial.id);
                $('#tp_edit_name').val(testimonial.name);
                $('#tp_edit_designation').val(testimonial.designation);
                $('#tp_edit_message').val(testimonial.message);

                $('#edit-testimonial-modal').show();
            } else {
                alert('Error: ' + response.data); // Show the error message
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX request failed:' + error);
        });
    });

    // Close modal
    $('.close').on('click', function() {
        $('#edit-testimonial-modal').hide();
    });

    // Handle form submission
    $('#edit-testimonial-form').on('submit', function(e) {
        e.preventDefault();
        let uid = $('#tp_testimonial_id').val();
        let uname = $('#tp_edit_name').val();
        let udesign = $('#tp_edit_designation').val();
        let umsg = $('#tp_edit_message').val();

        var formData = $(this).serialize();
        $.post(tp_testimonials_obj.ajaxurl, {action: 'tp_handle_testimonial_update',tp_testimonial_id: uid, tp_edit_name: uname, tp_edit_designation: udesign,tp_edit_message:umsg,nonce: tp_testimonials_obj.nonce}, function(response) {
            if (response.success) {
                alert('Testimonial updated successfully');
                location.reload(); // or update UI accordingly
            } else {
                alert('Error: ' + response.data);
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX request failed:', status, error);
            alert('AJAX request failed: ' + error);
        });
    });


    


    $('.delete-testimonial').on('click', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to delete this testimonial?')) {
            var id = $(this).data('id');
            $.post(
                ajaxurl,
                {
                    action: 'tp_delete_testimonial',
                    id: id
                },
                function(response) {
                    alert(response);
                    location.reload();
                }
            );
        }
    });

    $('.testimonial-item').on('click', '.show-more', function(e) {
        e.preventDefault();
        var $this = $(this);
        $this.prev('.full-text').toggle();
        $this.prev().prev('.short-text').toggle();
        $this.text($this.text() === 'Show more' ? 'Show less' : 'Show more');
    });
});
