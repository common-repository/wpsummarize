<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function wpsummarize_get_or_generate_site_key() {
    $site_key = get_option('wpsummarize_site_key');
    if (!$site_key) {
        $site_key = wp_generate_password(64, true, true);
        update_option('wpsummarize_site_key', $site_key);
    }
    return $site_key;
}

function wpsummarize_encrypt_for_transit($data) {
    $site_key = wpsummarize_get_or_generate_site_key();
    $method = "AES-256-CBC";
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
    $encrypted = openssl_encrypt($data, $method, $site_key, 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

function wpsummarize_encrypt($data) {
    $key = wp_salt('auth');
    $method = "AES-256-CBC";
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
    $encrypted = openssl_encrypt($data, $method, $key, 0, $iv);
    if ($encrypted === false) {
        return false;
    }
    return base64_encode($encrypted . '::' . $iv);
}

function wpsummarize_decrypt($data) {
    $key = wp_salt('auth');
    $method = "AES-256-CBC";
    list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
    $decrypted = openssl_decrypt($encrypted_data, $method, $key, 0, $iv);
    if ($decrypted === false) {
        return false;
    }
    return $decrypted;
}


function wpsummarize_get_api_key() {
    $encrypted_key = get_option('wpsummarize_openai_api_key');
    if (empty($encrypted_key)) {
        return false;
    }
    
    $decrypted_key = wpsummarize_decrypt($encrypted_key);
    if ($decrypted_key === false) {
        return false;
    }
    
    return $decrypted_key;
}



function wpsummarize_validate_openai_api_key($api_key) {
    $response = wp_remote_get('https://api.openai.com/v1/models', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        )
    ));

    $result = array(
        'is_valid' => false,
        'response_code' => null,
        'body' => '',
        'error' => ''
    );

    if (is_wp_error($response)) {
        $result['error'] = $response->get_error_message();
        return $result;
    }

    $result['response_code'] = wp_remote_retrieve_response_code($response);
    $result['body'] = wp_remote_retrieve_body($response);
    $data = json_decode($result['body'], true);

    $result['is_valid'] = ($result['response_code'] === 200 && isset($data['data']) && is_array($data['data']));

    if (!$result['is_valid'] && isset($data['error'])) {
        $result['error'] = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error occurred';
    }

    return $result;
}


// Usage in sanitization function

function wpsummarize_sanitize_openai_api_key($input) {

    if (empty($input)) {
        return '';
    }

    $sanitized_input = sanitize_text_field($input);
    $old_encrypted_key = get_option('wpsummarize_openai_api_key');
    $old_key = $old_encrypted_key ? wpsummarize_decrypt($old_encrypted_key) : '';
    $masked_old_key = wpsummarize_mask_api_key($old_key);

    // Check if the input matches the masked version of the old key
    if ($sanitized_input === $masked_old_key) {
        return $old_encrypted_key;
    }

    // If the input is different from both the old key and its masked version, encrypt it
    if ($sanitized_input !== $old_key) {
        $encrypted_key = wpsummarize_encrypt($sanitized_input);
        
        if ($encrypted_key === false) {
            add_settings_error('wpsummarize_options', 'encryption_failed', 'Failed to securely store the API key. Please try again.', 'error');
            return $old_encrypted_key;
        }
        
        return $encrypted_key;
    }

    return $old_encrypted_key;
}

// Make sure you have this function defined
function wpsummarize_mask_api_key($api_key) {
    if (strlen($api_key) > 8) {
        return substr($api_key, 0, 4) . str_repeat('*', strlen($api_key) - 8) . substr($api_key, -4);
    }
    return str_repeat('*', strlen($api_key));
}

// Function to display the masked API key in the admin
function wpsummarize_display_api_key_field() {
    $api_key = wpsummarize_get_api_key();
    $masked_key = $api_key ? wpsummarize_mask_api_key($api_key) : '';
    ?>
    <input type="text" name="wpsummarize_openai_api_key" value="<?php echo esc_attr($masked_key); ?>" class="regular-text" />
    <?php
}



function wpsummarize_api_key_notice() {
    // Check if the notice has been dismissed
    if (get_user_meta(get_current_user_id(), 'wpsummarize_api_key_notice_dismissed', true)) {
        return;
    }
    
    // Check if the API key is set
    $api_key = wpsummarize_get_api_key();
    
    if (!$api_key) {
        $setting_page_url = admin_url('admin.php?page=wpsummarize_settings');
        $notice = sprintf(
            /* translators: %1$s: opening link tag, %2$s: closing link tag */
            esc_html__('Please %1$sset your OpenAI API key%2$s to start using WPSummarize.', 'wpsummarize'),
            '<a href="' . esc_url($setting_page_url) . '">',
            '</a>'
        );
        
        ?>
        <div class="notice notice-warning is-dismissible" id="wpsummarize-api-key-notice">
            <p><?php echo wp_kses($notice, array('a' => array('href' => array()))); ?></p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'wpsummarize_api_key_notice');

function wpsummarize_dismiss_admin_notice() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized user');
    }

    check_ajax_referer('wpsummarize_nonce_ajax', 'nonce');

    $user_id = get_current_user_id();
    update_user_meta($user_id, 'wpsummarize_api_key_notice_dismissed', true);

    wp_send_json_success();
}
add_action('wp_ajax_wpsummarize_dismiss_notice', 'wpsummarize_dismiss_admin_notice');

function wpsummarize_reset_api_key_notice() {
    delete_metadata('user', 0, 'wpsummarize_api_key_notice_dismissed', '', true);
}
register_deactivation_hook(__FILE__, 'wpsummarize_reset_api_key_notice');


?>