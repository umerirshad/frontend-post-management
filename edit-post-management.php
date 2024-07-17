<?php
// Display the user posts list with edit links
function display_user_posts() {
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $args = [
            'author' => $current_user->ID,
            'post_status' => ['publish', 'pending', 'draft'],
            'post_type' => ['jobs', 'events', 'post'],
            'posts_per_page' => -1,
        ];
        $user_posts = new WP_Query($args);

        if ($user_posts->have_posts()) {
            echo '<table class="user-posts-table">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>Title</th>';
            echo '<th>Status</th>';
            echo '<th>Actions</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            while ($user_posts->have_posts()) {
                $user_posts->the_post();
                $post_status = get_post_status();
                $status_text = ($post_status === 'publish') ? 'Published' : ucfirst($post_status);
                echo '<tr class="user-post-item">';
                echo '<td><a href="' . get_permalink() . '">' . get_the_title() . '</a></td>';
                echo '<td>' . $status_text . '</td>';
                echo '<td>';
                echo '<a href="#" class="edit-post-link" data-post-id="' . get_the_ID() . '">Edit</a> | ';
                echo '<a href="#" class="delete-post-link" data-post-id="' . get_the_ID() . '">Delete</a>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
        } else {
            echo '<p>You have no posts.</p>';
        }
        wp_reset_postdata();
    } else {
        echo '<p>You need to be logged in to view your posts.</p>';
    }
}
add_shortcode('user_posts', 'display_user_posts');

// Add the modal form HTML
function add_modal_form() {
    ?>
    <div id="editPostModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <form id="edit-post-form" method="post" enctype="multipart/form-data">
                <input type="hidden" id="edit_post_id" name="edit_post_id" />
                <label for="edit_post_title">Post Title</label>
                <input type="text" id="edit_post_title" name="post_title" required />

                <label for="edit_post_content">Post Content</label>
                <textarea id="edit_post_content" name="post_content" required></textarea>

                <label for="edit_post_category">Post Category</label>
                <select id="edit_post_category" name="post_category">
                <?php
                    $categories = get_categories();
                    foreach ($categories as $category) {
                        $selected = ($category->term_id == $data['post_category']) ? 'selected' : '';
                        echo '<option value="' . $category->term_id . '" ' . $selected . '>' . $category->name . '</option>';
                    }
                ?>
                </select>

                <label for="edit_featured_image">Featured Image</label>
                <input type="file" id="edit_featured_image" name="featured_image" />

   <!-- ACF Fields for Events -->
   <div id="edit_acf_fields_events" class="edit_acf_fields">
                    <label for="edit_time_of_event">Time Of Event</label>
                    <input type="text" id="edit_time_of_event" name="time_of_event"  />

                    <label for="edit_date_of_event">Date Of Event</label>
                    <input type="date" id="edit_date_of_event" name="date_of_event" value="<?php //$date_string = get_field( 'date' ); $date = DateTime::createFromFormat( 'Ymd', $date_string ); echo $date->format( 'j M Y' ); ?>"/>

                    <label for="edit_event_access">Event Access</label>
                    <select id="edit_event_access" name="event_access">
                        <option value="">Select an option</option>
                        <option value="free_to_members">Free To Members</option>
                        <option value="paid">Paid</option>
                        <option value="discounted">Discounted</option>
                        <option value="free_to_all">Free To All</option>
                    </select>

                    <label for="edit_link">URL</label>
                    <input type="url" id="edit_link" name="link" />
                </div>

                <!-- ACF Fields for Jobs -->
                <div id="edit_acf_fields_jobs" class="edit_acf_fields">
                    <label for="edit_location">Location</label>
                    <input type="text" id="edit_location" name="location" />

                    <label for="edit_duration">Duration</label>
                    <input type="text" id="edit_duration" name="duration" />

                    <label for="edit_rate_range">Rate Range</label>
                    <input type="text" id="edit_rate_range" name="rate_range" />

                    <label for="edit_how_to_apply">How To Apply</label>
                    <input type="text" id="edit_how_to_apply" name="how_to_apply" />
                </div>

                <input type="submit" name="submit_edit" value="Update Post" />
            </form>
        </div>
    </div>
    <?php
}
add_action('wp_footer', 'add_modal_form');

// Handle the AJAX request to get post data
function get_post_data() {
    if (isset($_POST['post_id']) && is_user_logged_in()) {
        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);

        if ($post->post_author == get_current_user_id()) {
            $post_type = get_post_type($post_id);
            $acf_fields = [];

            if ($post_type == 'events') {
                $acf_fields = [
                    'time_of_event' => get_field('time_of_event', $post_id),
                    'date_of_event' => get_field('date_of_event', $post_id),
                    'event_access' => get_field('event_access', $post_id),
                    'link' => get_field('link', $post_id),
                ];
                $taxonomy = 'events_cat';
            } elseif ($post_type == 'jobs') {
                $acf_fields = [
                    'location' => get_field('location', $post_id),
                    'duration' => get_field('duration', $post_id),
                    'rate_range' => get_field('rate_range', $post_id),
                    'how_to_apply' => get_field('how_to_apply', $post_id),
                ];
                $taxonomy = 'jobs_cat';
            } else {
                $taxonomy = 'category'; // Default to standard categories
            }

            $categories = get_categories(['taxonomy' => $taxonomy, 'hide_empty' => false]);

            $response = [
                'post_id' => $post->ID,
                'post_title' => $post->post_title,
                'post_content' => $post->post_content,
                'post_category' => get_the_category($post_id)[0]->term_id,
                'post_type' => $post_type,
                'acf_fields' => $acf_fields,
                'categories' => $categories,
            ];
            wp_send_json_success($response);
        } else {
            wp_send_json_error('Permission denied');
        }
    }
    wp_die();
}
add_action('wp_ajax_get_post_data', 'get_post_data');



// Handle the form submission for editing a post
function handle_edit_post_submission() {
    if (isset($_POST['submit_edit']) && is_user_logged_in()) {
        $post_id = intval($_POST['edit_post_id']);
        $post = get_post($post_id);

        if ($post->post_author != get_current_user_id()) {
            wp_die('You do not have permission to edit this post.');
        }

        $post_title = sanitize_text_field($_POST['post_title']);
        $post_content = sanitize_textarea_field($_POST['post_content']);
        $post_category = intval($_POST['post_category']);

        $updated_post = [
            'ID' => $post_id,
            'post_title' => $post_title,
            'post_content' => $post_content,
            'post_category' => [$post_category]
        ];

        wp_update_post($updated_post);

        // Update ACF fields based on post type
        $post_type = get_post_type($post_id);

        if ($post_type == 'events') {
            if (isset($_POST['time_of_event'])) {
                update_field('time_of_event', sanitize_text_field($_POST['time_of_event']), $post_id);
            }
            if (isset($_POST['date_of_event'])) {
                update_field('date_of_event', sanitize_text_field($_POST['date_of_event']), $post_id);
            }
            if (isset($_POST['event_access'])) {
                update_field('event_access', sanitize_text_field($_POST['event_access']), $post_id);
            }
            if (isset($_POST['link'])) {
                update_field('link', esc_url_raw($_POST['link']), $post_id);
            }
        } elseif ($post_type == 'jobs') {
            if (isset($_POST['location'])) {
                update_field('location', sanitize_text_field($_POST['location']), $post_id);
            }
            if (isset($_POST['duration'])) {
                update_field('duration', sanitize_text_field($_POST['duration']), $post_id);
            }
            if (isset($_POST['rate_range'])) {
                update_field('rate_range', sanitize_text_field($_POST['rate_range']), $post_id);
            }
            if (isset($_POST['how_to_apply'])) {
                update_field('how_to_apply', sanitize_text_field($_POST['how_to_apply']), $post_id);
            }
        }

        if (!empty($_FILES['featured_image']['name'])) {
            if (!function_exists('wp_handle_upload')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            }

            $uploadedfile = $_FILES['featured_image'];
            $upload_overrides = ['test_form' => false];
            $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

            if ($movefile && !isset($movefile['error'])) {
                $attachment = [
                    'guid' => $movefile['url'],
                    'post_mime_type' => $movefile['type'],
                    'post_title' => basename($movefile['file']),
                    'post_content' => '',
                    'post_status' => 'inherit'
                ];

                $attachment_id = wp_insert_attachment($attachment, $movefile['file'], $post_id);
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attach_data = wp_generate_attachment_metadata($attachment_id, $movefile['file']);
                wp_update_attachment_metadata($attachment_id, $attach_data);

                set_post_thumbnail($post_id, $attachment_id);
            }
        }

        wp_redirect(remove_query_arg('edit_post_id'));
        exit;
    }
}
add_action('template_redirect', 'handle_edit_post_submission');

// Handle the AJAX request to delete a post
function delete_post() {
    if (isset($_POST['post_id']) && is_user_logged_in()) {
        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);

        if ($post->post_author == get_current_user_id()) {
            wp_delete_post($post_id);
            wp_send_json_success('Post deleted');
        } else {
            wp_send_json_error('Permission denied');
        }
    }
    wp_die();
}
add_action('wp_ajax_delete_post', 'delete_post');
