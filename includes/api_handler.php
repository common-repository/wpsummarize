<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Exit if accessed directly
function wpsummarize_post_action(
    $post_id,
    $update,
    $wpsummarize_post_settings,
    $update_on_edit
) {
    global $post;
    if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
        return;
    }
    $wpsummarize_options = get_option( 'wpsummarize_options', [] );
    // Defaults to an empty array if not set
    if ( !is_array( $wpsummarize_post_settings ) ) {
        $wpsummarize_post_settings = [];
    }
    // Merge global settings with per-post settings
    $final_settings = array_merge( $wpsummarize_options, $wpsummarize_post_settings );
    // Determine whether to generate summary on publish or update
    $create_on_publish = ( isset( $_POST['_wpsummarize_post_settings']['create_on_publish'] ) ? '1' : '0' );
    // '1' for checked, '0' for unchecked
    $summarySet = get_post_meta( $post_id, '_wpsummarize_summary_set', true );
    if ( $update && 'publish' === $post->post_status && ('1' === $create_on_publish && empty( $summarySet ) || $update_on_edit == '1') ) {
        wpsummarize_add_summary_to_action_scheduler_queue( $post_id );
    } elseif ( isset( $_POST['wpsummarize_summary_content'] ) && !empty( $_POST['wpsummarize_summary_content'] ) ) {
        update_post_meta( $post_id, '_wpsummarize_summary_set', wp_kses_post( wp_unslash( $_POST['wpsummarize_summary_content'] ) ) );
    }
}

function wpsummarize_add_summary_to_action_scheduler_queue(  $post_id  ) {
    global $wpdb;
    update_post_meta( $post_id, 'wpsummarize_running_api', intval( time() ) );
    // Check if there's already a scheduled action for this post
    $existing_actions = as_get_scheduled_actions( array(
        'hook'   => 'wpsummarize_generate_summary_hook',
        'args'   => array($post_id),
        'status' => ActionScheduler_Store::STATUS_PENDING,
    ), 'ids' );
    if ( empty( $existing_actions ) ) {
        // Schedule the new action
        $scheduled = as_schedule_single_action(
            time(),
            'wpsummarize_generate_summary_hook',
            array($post_id),
            'wpsummarize'
        );
        if ( $scheduled ) {
            update_post_meta( $post_id, 'wpsummarize_action_scheduled', intval( time() ) );
            // Run only this specific action
            $action_id = as_get_scheduled_actions( array(
                'hook'   => 'wpsummarize_generate_summary_hook',
                'args'   => array($post_id),
                'status' => ActionScheduler_Store::STATUS_PENDING,
            ), 'ids' );
            if ( !empty( $action_id ) ) {
                $action_id = reset( $action_id );
                // Get the first (and should be only) action ID
                $runner = new ActionScheduler_QueueRunner(ActionScheduler::store());
                $runner->process_action( $action_id );
            }
        } else {
        }
    } else {
        // Action already scheduled, no need to schedule again
    }
}

function wpsummarize_generate_summary(  $post_id  ) {
    $post = get_post( $post_id );
    if ( $post && 'publish' === $post->post_status ) {
        wpsummarize_send_data_to_api( $post );
        delete_post_meta( $post->ID, 'wpsummarize_running_api' );
    }
}

add_action(
    'wpsummarize_generate_summary_hook',
    'wpsummarize_generate_summary',
    10,
    1
);
function wpsummarize_rename_post_meta_key(  $post_id, $old_key, $new_key  ) {
    global $wpdb;
    // Check if the old meta key exists
    $meta_exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s", $post_id, $old_key ) );
    if ( $meta_exists > 0 ) {
        // Update the meta key
        $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s AND post_id = %d",
            $new_key,
            $old_key,
            $post_id
        ) );
    }
}

function wpsummarize_send_data_to_api(  $post  ) {
    if ( !$post || !is_a( $post, 'WP_Post' ) ) {
        return;
    }
    $wpsummarize_options = get_option( 'wpsummarize_options', [] );
    $wpsummarize_post_settings = get_post_meta( $post->ID, '_wpsummarize_post_settings', true );
    // Ensure settings are arrays
    $wpsummarize_options = ( is_array( $wpsummarize_options ) ? $wpsummarize_options : [] );
    $wpsummarize_post_settings = maybe_unserialize( $wpsummarize_post_settings );
    $wpsummarize_post_settings = ( is_array( $wpsummarize_post_settings ) ? $wpsummarize_post_settings : [] );
    $final_settings = array_merge( $wpsummarize_options, $wpsummarize_post_settings );
    // Get the API key
    $api_key = wpsummarize_get_api_key();
    // Prepare the data, ensuring all values are set
    $data = [
        'post_content'      => $post->post_content,
        'final_settings'    => $final_settings,
        'locale'            => get_locale(),
        'license_id'        => '',
        'install_id'        => '',
        'site_private_key'  => '',
        'url'               => '',
        'wp_url'            => get_site_url(),
        'trialtf'           => false,
        'encrypted_api_key' => ( $api_key ? wpsummarize_encrypt_for_transit( $api_key ) : '' ),
    ];
    // Safely get Freemius data
    if ( function_exists( 'wpsummarize_fs' ) ) {
        $fs = wpsummarize_fs();
        if ( $fs ) {
            $license = $fs->_get_license();
            $site = $fs->get_site();
            if ( $license ) {
                $data['license_id'] = $license->id;
            }
            if ( $site ) {
                $data['install_id'] = $site->id;
                $data['site_private_key'] = $site->secret_key;
                $data['url'] = $site->url;
            }
        }
    }
    // Encode data to JSON, handling potential encoding issues
    $body = wp_json_encode( $data );
    if ( $body === false ) {
        return;
    }
    $api_url = 'https://wpsummarize.com/api_connect_wpsum.php';
    $args = [
        'body'        => $body,
        'headers'     => [
            'Content-Type'           => 'application/json',
            'X-WPSummarize-Site-Key' => wpsummarize_get_or_generate_site_key(),
        ],
        'data_format' => 'body',
        'timeout'     => 60,
    ];
    $response = wp_remote_post( $api_url, $args );
    $response_code = wp_remote_retrieve_response_code( $response );
    if ( $response_code != 200 ) {
        return;
    }
    // Check if response body is empty
    $response_body = wp_remote_retrieve_body( $response );
    if ( empty( $response_body ) ) {
        return;
    }
    // Decode the API response
    $api_response = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( isset( $api_response['success'] ) && $api_response['success'] === true ) {
        // rename the set summary to make it a revision, available to get it back as set
        wpsummarize_rename_post_meta_key( $post->ID, '_wpsummarize_summary_set', '_wpsummarize_summary_revision' );
        // add the post meta with the summary
        update_post_meta( $post->ID, '_wpsummarize_summary_set', wp_kses_post( $api_response['summary'] ) );
    } else {
        // Handle failed API response
    }
}

function wpsummarize_update_post_modified_date(  $post_id  ) {
    $post_data = array(
        'ID'                => $post_id,
        'post_modified'     => current_time( 'mysql' ),
        'post_modified_gmt' => current_time( 'mysql', 1 ),
    );
    wp_update_post( $post_data );
}

function wpsummarize_api_check_tokens(  $type  ) {
    // Prepare the data to be sent to the API
    $api_url = 'https://wpsummarize.com/api_connect_wpsum.php';
    // Set this to your API endpoint URL
    $body = [];
    if ( function_exists( 'wpsummarize_fs' ) ) {
        $fs = wpsummarize_fs();
        if ( $fs && method_exists( $fs, '_get_license' ) ) {
            $license = $fs->_get_license();
            if ( $license && isset( $license->id ) ) {
                $body['license_id'] = $license->id;
            }
        }
        if ( $fs && method_exists( $fs, 'get_site' ) ) {
            $site = $fs->get_site();
            if ( $site ) {
                if ( isset( $site->id ) ) {
                    $body['install_id'] = $site->id;
                }
                if ( isset( $site->secret_key ) ) {
                    $body['site_private_key'] = $site->secret_key;
                }
                if ( isset( $site->url ) ) {
                    $body['url'] = $site->url;
                }
            }
        }
        // additional url info check
        $body['wp_url'] = get_site_url();
    }
    $body['trialtf'] = false;
    if ( isset( $type ) ) {
        $body['type'] = $type;
    }
    $json_body = wp_json_encode( $body );
    // Set up the request headers
    $args = [
        'body'        => $json_body,
        'headers'     => [
            'Content-Type' => 'application/json',
        ],
        'data_format' => 'body',
        'timeout'     => 60,
    ];
    // Send the request
    $response = wp_remote_post( $api_url, $args );
    // Check for errors in the response
    if ( is_wp_error( $response ) ) {
        return;
    }
    $response_code = wp_remote_retrieve_response_code( $response );
    if ( $response_code != 200 ) {
        return;
    }
    // Check if response body is empty
    $response_body = wp_remote_retrieve_body( $response );
    if ( empty( $response_body ) ) {
        return;
    }
    // Decode the API response
    $api_response = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( isset( $api_response['success'] ) && $api_response['success'] === true ) {
        return $api_response;
    } else {
        // Handle failed API response
    }
}
