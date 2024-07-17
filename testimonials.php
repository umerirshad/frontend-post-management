<?php

// Display testimonial submission form
function tp_testimonial_form() {
    if (!is_user_logged_in()) {
        return '<p>You must be logged in to submit a testimonial.</p>';
    }

    // Check for the query parameter to display a thank you message
    if (isset($_GET['testimonial_submitted']) && $_GET['testimonial_submitted'] == 'true') {
        return '<p>Thank you for your testimonial!</p>';
    }

    ob_start();
    ?>
    <form id="testimonial-form" method="post" action="">
        <h2>Add Testimonials</h2>
        <input type="text" name="tp_name" placeholder="Your Name" required>
        <input type="text" name="tp_designation" placeholder="Designation" required>
        <textarea name="tp_message" placeholder="Your Testimonial" required></textarea>
        <input type="submit" name="tp_submit" value="Submit">
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('testimonial_form', 'tp_testimonial_form');

// Handle testimonial submission
function tp_handle_testimonial_submission() {
    if (isset($_POST['tp_submit']) && is_user_logged_in()) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'testimonials';

        $user_id = get_current_user_id();
        $name = sanitize_text_field($_POST['tp_name']);
        $designation = sanitize_text_field($_POST['tp_designation']);
        $message = sanitize_textarea_field($_POST['tp_message']);

        $wpdb->insert(
            $table_name,
            [
                'user_id' => $user_id,
                'name' => $name,
                'designation' => $designation,
                'message' => $message,
            ]
        );

         // Redirect to avoid form resubmission
         wp_redirect(add_query_arg('testimonial_submitted', 'true', wp_get_referer()));
         exit;
    }
}
add_action('template_redirect', 'tp_handle_testimonial_submission');

// Display testimonials in a carousel
function tp_display_testimonials() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'testimonials';
    $testimonials = $wpdb->get_results("SELECT * FROM $table_name");

    ob_start();
    ?>
    <div class="testimonial-carousel">
        <?php foreach ($testimonials as $testimonial) : ?>
            <div class="testimonial-item">
                <p><strong><?php echo esc_html($testimonial->name); ?></strong></p>
                <p><?php echo esc_html($testimonial->designation); ?></p>
                <p class="testimonial-message">
                    <?php
                    $message = esc_html($testimonial->message);
                    if (strlen($message) > 100) {
                        $short_message = substr($message, 0, 100) . '...';
                        echo '<span class="short-text">' . $short_message . '</span>';
                        echo '<span class="full-text" style="display:none;">' . $message . '</span>';
                        echo '<a href="#" class="show-more">Show more</a>';
                    } else {
                        echo $message;
                    }
                    ?>
                </p>

            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('display_testimonials', 'tp_display_testimonials');

// Display user's testimonials
function tp_user_testimonials() {
    if (!is_user_logged_in()) {
        return '<p>You must be logged in to view your testimonials.</p>';
    }

    global $wpdb;
    $user_id = get_current_user_id();
    $table_name = $wpdb->prefix . 'testimonials';
    $testimonials = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id));

    ob_start();
    ?>
    <div class="user-testimonials">
    <div class="testimonial-heading"><h2>Testimonials</h2></div>
        <?php foreach ($testimonials as $testimonial) : ?>
           
            <div class="testimonial-item" data-id="<?php echo $testimonial->id; ?>">
                <p><?php echo esc_html($testimonial->message); ?></p>
                <p><strong><?php echo esc_html($testimonial->name); ?></strong></p>
                <p><strong><?php echo esc_html($testimonial->designation); ?></strong></p>
                
                <a href="#" class="edit-testimonial" data-id="<?php echo $testimonial->id; ?>">Edit</a>
                <a href="#" class="delete-testimonial" data-id="<?php echo $testimonial->id; ?>">Delete</a>
            </div>
        <?php endforeach; ?>
    </div>
    <!-- Update Modal -->
    <div id="edit-testimonial-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <form id="edit-testimonial-form" method="post">
            <h2>Update Testimonials</h2>
                <input type="hidden" name="tp_testimonial_id" id="tp_testimonial_id" value="">
           
                    <label for="tp_edit_name">Name</label>
                    <input type="text" name="tp_edit_name" id="tp_edit_name" required>
          
       
                    <label for="tp_edit_designation">Designation</label>
                    <input type="text" name="tp_edit_designation" id="tp_edit_designation" required>
      
                    <label for="tp_edit_message">Message</label>
                    <textarea name="tp_edit_message" id="tp_edit_message" required></textarea>
    
                    <input type="submit" name="tp_update" value="Update Testimonial">
           
            </form>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('user_testimonials', 'tp_user_testimonials');

// Handle testimonial update
function tp_handle_testimonial_update() {
   // var_dump($_POST['frmData']);
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tp_testimonials_nonce')) {
        wp_send_json_error('Nonce verification failed');
    }

    if (!isset($_POST['tp_edit_name']) && !isset($_POST['tp_edit_designation']) ) {
        wp_send_json_error('Missing tp_update parameter');
    }

    if (is_user_logged_in()) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'testimonials';

        $testimonial_id = intval($_POST['tp_testimonial_id']);
        $user_id = get_current_user_id();
        $name = sanitize_text_field($_POST['tp_edit_name']);
        $designation = sanitize_text_field($_POST['tp_edit_designation']);
        $message = sanitize_textarea_field($_POST['tp_edit_message']);

        $updated = $wpdb->update(
            $table_name,
            [
                'name' => $name,
                'designation' => $designation,
                'message' => $message,
            ],
            [
                'id' => $testimonial_id,
                'user_id' => $user_id,
            ]
        );

        if ($updated !== false) {
            wp_send_json_success('Testimonial updated successfully');
        } else {
            wp_send_json_error('Error updating testimonial: ' . $wpdb->last_error);
        }
    } else {
        wp_send_json_error('Invalid request');
    }
    wp_die();
}
add_action('wp_ajax_tp_handle_testimonial_update', 'tp_handle_testimonial_update');


// AJAX handler to fetch testimonial data
function tp_get_testimonial() {
    check_ajax_referer('tp_testimonials_nonce', 'nonce');

    if (!isset($_POST['testimonial_id'])) {
        wp_send_json_error('Testimonial ID is required');
    }

    $testimonial_id = intval($_POST['testimonial_id']);
    $user_id = get_current_user_id();
    global $wpdb;
    $table_name = $wpdb->prefix . 'testimonials';
    $testimonial = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d AND user_id = %d", $testimonial_id, $user_id));

    if ($testimonial) {
        wp_send_json_success($testimonial);
    } else {
        wp_send_json_error('Testimonial not found or you do not have permission to access it');
    }
}
add_action('wp_ajax_tp_get_testimonial', 'tp_get_testimonial');


function tp_delete_testimonial() {
    if (is_user_logged_in() && isset($_POST['id'])) {
        global $wpdb;
        $user_id = get_current_user_id();
        $id = intval($_POST['id']);

        $table_name = $wpdb->prefix . 'testimonials';
        $wpdb->delete($table_name, ['id' => $id, 'user_id' => $user_id]);

        echo 'Testimonial deleted successfully';
    }
    wp_die();
}
add_action('wp_ajax_tp_delete_testimonial', 'tp_delete_testimonial');
