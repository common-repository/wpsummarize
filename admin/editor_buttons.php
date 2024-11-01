<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function wpsummarize_add_editor_button() {
    if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') ) {
        return;
    }
    if ( get_user_option('rich_editing') == 'true' ) {
        add_filter( 'mce_external_plugins', 'wpsummarize_add_tinymce_plugin' );
        add_filter( 'mce_buttons', 'wpsummarize_register_button' );
    }
}
add_action('admin_init', 'wpsummarize_add_editor_button');

function wpsummarize_add_tinymce_plugin( $plugin_array ) {
    $plugin_array['wpsummarize_button'] = plugin_dir_url(__DIR__) . 'assets/js/wpsummarize-button.js';
    return $plugin_array;
}

function wpsummarize_register_button( $buttons ) {
    array_push( $buttons, 'wpsummarize_button' );
    return $buttons;
}


function wpsummarize_register_block() {
    wp_register_script(
        'wpsummarize-block',
        plugin_dir_url(__DIR__) . 'assets/js/wpsummarize-block.js',
        array( 'wp-blocks', 'wp-element', 'wp-block-editor' ),
        WPSUMMARIZE_VERSION,
        true
    );
    register_block_type( 'wpsummarize/wpsummarize', array(
        'editor_script' => 'wpsummarize-block',
    ) );
}
add_action( 'init', 'wpsummarize_register_block' );



function wpsummarize_enqueue_quicktags() {
    // Only add to post edit screen
    $current_screen = get_current_screen();
    if (!$current_screen || !in_array($current_screen->base, array('post', 'page'))) {
        return;
    }

    // Enqueue the quicktags script
    wp_enqueue_script('quicktags');

    // Enqueue custom script
    wp_enqueue_script(
        'wpsummarize-quicktags',
        plugin_dir_url(__DIR__) . 'assets/js/wpsummarize-quicktags.js',
        array('quicktags', 'wp-dom-ready'),
        WPSUMMARIZE_VERSION,
        true
    );

    // Add inline script
    wp_add_inline_script('wpsummarize-quicktags', '
        wp.domReady(function() {
            if (typeof QTags !== "undefined") {
                QTags.addButton("wpsummarize_shortcode", "WP Summarize", "[wpsummarize]", "", "", "Insert WPSummarize shortcode", 999);
            }
        });
    ');
}
add_action('admin_enqueue_scripts', 'wpsummarize_enqueue_quicktags');
?>