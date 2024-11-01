<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Exit if accessed directly
function wpsummarize_enqueue_scripts() {
    wp_enqueue_script(
        'wpsummarize-ajax',
        plugin_dir_url( __DIR__ ) . 'assets/js/wpsummarize-scripts.js',
        array('jquery'),
        WPSUMMARIZE_VERSION,
        true
    );
    wp_localize_script( 'wpsummarize-ajax', 'wpSummarizeAjax', array(
        'ajaxurl'               => admin_url( 'admin-ajax.php' ),
        'nonce'                 => wp_create_nonce( 'wpsummarize_nonce_ajax' ),
        'dismiss_notice_action' => 'wpsummarize_dismiss_notice',
    ) );
}

add_action( 'admin_enqueue_scripts', 'wpsummarize_enqueue_scripts' );
function wpsummarize_meta_box_callback(  $post  ) {
    echo '<div id="wpsummarize-meta-box">';
    // if a new summary is being generated, dont show the summary that is about to be replaced
    if ( wpsummarize_check_if_summary_is_generating( $post->ID ) !== false ) {
        echo '<div style="margin-bottom:30px"><p> <div id="wpsummarize_loadingIndicator"></div> ' . esc_html__( 'A summary is currently being generated. Please wait a few moments and check again.', 'wpsummarize' ) . '</p></div>';
    } else {
        // Use nonce for verification to ensure data comes from this form
        wp_nonce_field( 'wpsummarize_save_post_meta', 'wpsummarize_nonce' );
        // Display settings fields
        $wpsummarize_options = get_option( 'wpsummarize_options' );
        $wpsummarize_post_settings_serialized = get_post_meta( $post->ID, '_wpsummarize_post_settings', true );
        $wpsummarize_post_settings = maybe_unserialize( $wpsummarize_post_settings_serialized );
        $summary = wpsummarize_get_latest_summary( $post->ID );
        // Sanitize summary
        $summary = wp_kses_post( $summary );
        if ( $summary != "" ) {
            echo $summary;
        }
        $api_key = wpsummarize_get_api_key();
        if ( empty( $api_key ) ) {
            $setting_page_url = admin_url( 'admin.php?page=wpsummarize_settings' );
            $notice = sprintf( 
                /* translators: %1$s: opening link tag, %2$s: closing link tag */
                esc_html__( 'Please %1$sset your OpenAI API key%2$s to start using WPSummarize.', 'wpsummarize' ),
                '<a href="' . esc_url( $setting_page_url ) . '">',
                '</a>'
             );
            echo wp_kses( $notice, array(
                'a' => array(
                    'href' => array(),
                ),
            ) );
            echo '<br /><br />';
        } else {
            if ( !empty( $summary ) || $post->post_status === 'publish' ) {
                $message = ( !empty( $summary ) ? esc_html__( 'Get a new summary when you save this post', 'wpsummarize' ) : esc_html__( 'Create a summary when you save this post', 'wpsummarize' ) );
                wpsummarize_meta_update_on_edit_callback( $post->ID, $message );
            } else {
                wpsummarize_create_on_publish_callback( $wpsummarize_options, $wpsummarize_post_settings, true );
            }
        }
        if ( wpsummarize_fs()->is_not_paying() and $summary != "" ) {
            echo '<a href="' . esc_url( wpsummarize_fs()->get_upgrade_url() ) . '">' . esc_html__( 'Upgrade to manually edit this summary, add strong and em tags, and adjust settings for this post.', 'wpsummarize' ) . '</a>';
        }
        echo '</div>';
        wp_add_inline_script( 'wpsummarize-ajax', "\r\ndocument.addEventListener('DOMContentLoaded', function() {\r\n    wpsummarize_initializeToggleEditor();\r\n    wpsummarize_initializeToggleLinksGeneral();\r\n});\r\n" );
    }
}

function wpsummarize_delete_meta_ajax() {
    if ( isset( $_POST['post_id'] ) ) {
        $post_id = intval( $_POST['post_id'] );
        $post = get_post( $post_id );
        $post_type = $post->post_type;
        $post_type_object = get_post_type_object( $post_type );
        if ( !$post_type_object ) {
            wp_send_json_error( 'Invalid nonce' );
            // Break out if the post type is not valid
        }
        if ( !current_user_can( $post_type_object->cap->edit_post, $post_id ) || !isset( $_POST['nonce'] ) || !wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpsummarize_nonce_ajax' ) ) {
            wp_send_json_error( 'Invalid nonce' );
        }
        delete_post_meta( $post_id, 'wpsummarize_running_api' );
        // Fetch updated meta box content
        ob_start();
        wpsummarize_meta_box_callback( $post );
        $html = ob_get_clean();
        wp_send_json_success( array(
            'html' => $html,
        ) );
    } else {
        wp_send_json_error( 'Post ID not provided' );
    }
}

add_action( 'wp_ajax_delete_wpsummarize_meta', 'wpsummarize_delete_meta_ajax' );
add_action( 'wp_ajax_check_summary_status', 'wpsummarize_check_summary_status' );
function wpsummarize_check_summary_status() {
    $post_id = ( isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0 );
    $post = get_post( $post_id );
    $summary_running = get_post_meta( $post_id, 'wpsummarize_running_api', true );
    // Fetch updated meta box content
    ob_start();
    wpsummarize_meta_box_callback( $post );
    $html = ob_get_clean();
    if ( empty( $summary_running ) ) {
        wp_send_json_success( [
            'summary_running' => false,
            'data'            => [
                'html_completed' => $html,
            ],
        ] );
    } else {
        wp_send_json_success( [
            'summary_running' => true,
            'data'            => [
                'html_running' => $html,
            ],
        ] );
    }
}

function wpsummarize_save_post_meta(  $post_id, $post, $update  ) {
    if ( !isset( $_POST['wpsummarize_nonce'] ) || !wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpsummarize_nonce'] ) ), 'wpsummarize_save_post_meta' ) || defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    // Get the post type from POST data and verify if current user has permission to edit it.
    $post_type = sanitize_text_field( wp_unslash( $_POST['post_type'] ) );
    $post_type_object = get_post_type_object( $post_type );
    if ( !$post_type_object ) {
        return;
        // Break out if the post type is not valid
    }
    // Check if the user has the capability to edit posts of this post type
    if ( !current_user_can( $post_type_object->cap->edit_post, $post_id ) ) {
        return;
    }
    // Assuming all settings are under a single form field array
    if ( isset( $_POST['_wpsummarize_post_settings'] ) ) {
        $settings = array_map( 'sanitize_text_field', wp_unslash( $_POST['_wpsummarize_post_settings'] ) );
        // Apply sanitization
        // Check auto include global option to set 1 to post setting hide summary
        $wpsummarize_options = get_option( 'wpsummarize_options' );
        // sets hide summary to 1 if global settings are set to that, and if no post settings are saved yet, so this happens the first time a summary is generated
        if ( isset( $wpsummarize_options ) && empty( $wpsummarize_options['auto_include'] ) ) {
            if ( !get_post_meta( $post_id, '_wpsummarize_post_settings', true ) ) {
                $settings['hide_summary'] = 1;
            }
        }
        update_post_meta( $post_id, '_wpsummarize_post_settings', serialize( $settings ) );
        // Serialize and save
    }
    $update_on_edit = ( isset( $_POST['wpsummarize_update_on_edit'] ) ? '1' : '0' );
    // '1' for checked, '0' for unchecked
    wpsummarize_post_action(
        $post_id,
        $update,
        $settings,
        $update_on_edit
    );
}

add_action(
    'save_post',
    'wpsummarize_save_post_meta',
    10,
    3
);
// Add meta box
function wpsummarize_add_meta_box() {
    $wpsummarize_options = get_option( 'wpsummarize_options' );
    // Fetch existing options or set default
    $enabled_post_types = ( isset( $wpsummarize_options['wpsummarize_post_types_enabled'] ) ? $wpsummarize_options['wpsummarize_post_types_enabled'] : [] );
    add_meta_box(
        'wpsummarize_meta_box',
        // ID of the meta box
        esc_html__( 'WP Summarize', 'wpsummarize' ),
        // Title of the meta box
        'wpsummarize_meta_box_callback',
        // Callback function to display the meta box content
        $enabled_post_types,
        // Post type where the meta box will appear
        'advanced',
        // Context where the box will appear ('normal', 'side', 'advanced')
        'high'
    );
}

add_action( 'add_meta_boxes', 'wpsummarize_add_meta_box' );
function wpsummarize_get_latest_summary(  $post_id  ) {
    return get_post_meta( $post_id, '_wpsummarize_summary_set', true );
}

function wpsummarize_meta_update_on_edit_callback(  $post_id, $message  ) {
    echo '<label style="margin-bottom: 25px;display:block;"><input type="checkbox" name="wpsummarize_update_on_edit" value="1"> <strong>' . esc_html( $message ) . '</strong></label>';
}

function wpsummarize_check_if_summary_is_generating(  $post_id  ) {
    $running_since = get_post_meta( $post_id, 'wpsummarize_running_api', true );
    if ( $running_since ) {
        $current_time = time();
        $time_difference = $current_time - intval( $running_since );
        $max_execution_time = 10 * 60;
        // 10 minutes in seconds
        if ( $time_difference > $max_execution_time ) {
            // The process has been running for too long, consider it stuck
            delete_post_meta( $post_id, 'wpsummarize_running_api' );
            return false;
        }
        // Summary generation is still running within the acceptable time frame
        return true;
    }
    // No summary generation is running
    return false;
}
