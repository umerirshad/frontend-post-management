<?php
/*
Plugin Name: Frontend Post Management
Description: A plugin to manage frontend posts with edit and delete functionalities.
Version: 1.0
Author: Codes Fix
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

register_activation_hook(__FILE__, 'tp_create_testimonials_table');

function tp_create_testimonials_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'testimonials';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        name tinytext NOT NULL,
        designation tinytext NOT NULL,
        message text NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Enqueue the modal scripts and styles
function enqueue_modal_scripts() {
    wp_enqueue_style('modal-styles', plugin_dir_url(__FILE__) . 'modal.css');
    wp_enqueue_script('modal-scripts', plugin_dir_url(__FILE__) . 'modal.js', ['jquery'], null, true);
    wp_localize_script('modal-scripts', 'ajaxurl', admin_url('admin-ajax.php'));

    wp_enqueue_style('slick-carousel-css', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.css');
    wp_enqueue_script('slick-carousel-js', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.js', ['jquery'], null, true);
    wp_enqueue_script('tp-carousel-init', plugin_dir_url(__FILE__) . 'carousel-init.js', ['slick-carousel-js'], null, true);
    wp_enqueue_script('testimonials-js', plugin_dir_url(__FILE__) . 'testimonials.js', ['jquery'], null, true);
    wp_localize_script('testimonials-js', 'tp_testimonials_obj',  [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('tp_testimonials_nonce'),
    ]);

    wp_enqueue_script( 'testimonials-js' );
    // Enqueue Font Awesome
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css');
}
add_action('wp_enqueue_scripts', 'enqueue_modal_scripts');

require_once plugin_dir_path(__FILE__) . 'testimonials.php';
require_once plugin_dir_path(__FILE__) . 'edit-post-management.php';

