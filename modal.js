jQuery(document).ready(function($) {

    $('.edit-post-link').click(function(e) {
        e.preventDefault();
        var postId = $(this).data('post-id');
        var modal = $('#editPostModal');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_post_data',
                post_id: postId
            },
            success: function(response) {
                if (response.success) {
                    var postData = response.data;

                    $('#edit_post_id').val(postData.post_id);
                    $('#edit_post_title').val(postData.post_title);
                    $('#edit_post_content').val(postData.post_content);

                    // Populate categories
                    var categorySelect = $('#edit_post_category');
                    categorySelect.empty();
                    postData.categories.forEach(function(category) {
                        var option = $('<option></option>').attr('value', category.term_id).text(category.name);
                        if (category.term_id == postData.post_category) {
                            option.attr('selected', 'selected');
                        }
                        categorySelect.append(option);
                    });

                    // Hide all ACF fields initially
                    $('.edit_acf_fields').hide();

                    // Show relevant ACF fields based on post type
                    if (postData.post_type == 'events') {
                        $('#edit_time_of_event').val(postData.acf_fields.time_of_event);
                        $('#edit_date_of_event').val(postData.acf_fields.date_of_event);
                        $('#edit_event_access').val(postData.acf_fields.event_access);
                        $('#edit_link').val(postData.acf_fields.link);
                        $('#edit_acf_fields_events').show();
                    } else if (postData.post_type == 'jobs') {
                        $('#edit_location').val(postData.acf_fields.location);
                        $('#edit_duration').val(postData.acf_fields.duration);
                        $('#edit_rate_range').val(postData.acf_fields.rate_range);
                        $('#edit_how_to_apply').val(postData.acf_fields.how_to_apply);
                        $('#edit_acf_fields_jobs').show();
                    }

                    modal.show();
                }
            }
        });
    });

    $('.delete-post-link').click(function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to delete this post?')) {
            var postId = $(this).data('post-id');
            var postItem = $(this).closest('tr');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_post',
                    post_id: postId
                },
                success: function(response) {
                    if (response.success) {
                        postItem.remove();
                    } else {
                        alert('Failed to delete the post.');
                    }
                }
            });
        }
    });

    $('.close').click(function() {
        $('#editPostModal').hide();
    });

    $(window).click(function(event) {
        if (event.target.id == 'editPostModal') {
            $('#editPostModal').hide();
        }
    });
});
