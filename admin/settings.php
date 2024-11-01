<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Exit if accessed directly
function wpsummarize_admin_init() {
    global $wpsummarize_options;
    $wpsummarize_options = get_option( 'wpsummarize_options' );
    // Register settings for both simple and advanced on separate options groups
    register_setting( 'wpsummarize_options', 'wpsummarize_options', 'wpsummarize_sanitize_options' );
    register_setting( 'wpsummarize_options', 'wpsummarize_custom_css', 'wpsummarize_sanitize_custom_css' );
    register_setting( 'wpsummarize_options', 'wpsummarize_openai_api_key', 'wpsummarize_sanitize_openai_api_key' );
    // Simple Settings Section
    add_settings_section(
        'wpsummarize_simple_section',
        esc_html__( 'Basic Settings', 'wpsummarize' ),
        'wpsummarize_simple_section_callback',
        'wpsummarize_options'
    );
    // List of all simple settings fields
    $simple_settings = [
        ['wpsummarize_openai_api_key', esc_html__( 'Enter your OpenAI API Key', 'wpsummarize' ), 'wpsummarize_openai_api_key_callback'],
        ['wpsummarize_create_on_publish', esc_html__( 'Create summary when you publish a new post', 'wpsummarize' ), 'wpsummarize_create_on_publish_callback'],
        ['wpsummarize_auto_include', esc_html__( 'Insert summary in posts by default', 'wpsummarize' ), 'wpsummarize_auto_include_callback'],
        ['wpsummarize_title_before_summary', esc_html__( 'Title before summary', 'wpsummarize' ), 'wpsummarize_title_before_summary_callback'],
        ['wpsummarize_insert_location', esc_html__( 'Where to insert summary on posts', 'wpsummarize' ), 'wpsummarize_insert_location_callback'],
        ['wpsummarize_summary_style', esc_html__( 'Summary type', 'wpsummarize' ), 'wpsummarize_summary_style_callback'],
        ['wpsummarize_language', esc_html__( 'Output language of your summaries', 'wpsummarize' ), 'wpsummarize_language_callback']
    ];
    foreach ( $simple_settings as $setting ) {
        add_settings_field(
            $setting[0],
            $setting[1],
            $setting[2],
            'wpsummarize_options',
            'wpsummarize_simple_section'
        );
    }
    // Advanced Settings Section
    add_settings_section(
        'wpsummarize_save_button_section',
        '',
        'wpsummarize_save_button_callback',
        'wpsummarize_options'
    );
    // Advanced Settings Section
    add_settings_section(
        'wpsummarize_advanced_section',
        esc_html__( 'Advanced Settings', 'wpsummarize' ),
        'wpsummarize_advanced_section_callback',
        'wpsummarize_options'
    );
    // List of all advanced settings fields
    $advanced_settings = [
        ['wpsummarize_post_types', esc_html__( 'Enable WPSummarize on these post types', 'wpsummarize' ), 'wpsummarize_post_types_enabled_callback'],
        ['wpsummarize_summary_item_count', esc_html__( 'Choose how many items in list of key insights summary', 'wpsummarize' ), 'wpsummarize_summary_item_count_callback'],
        ['wpsummarize_summary_word_count', esc_html__( 'Approximate words in text summary', 'wpsummarize' ), 'wpsummarize_summary_word_count_callback'],
        ['wpsummarize_use_tags', esc_html__( 'Use &lt;strong&gt; and &lt;em&gt; tags where relevant', 'wpsummarize' ), 'wpsummarize_use_tags_callback'],
        ['wpsummarize_summary_tone', esc_html__( 'Choose the tone of the summary', 'wpsummarize' ), 'wpsummarize_summary_tone_callback'],
        ['wpsummarize_summary_theme', esc_html__( 'Summary Theme', 'wpsummarize' ), 'wpsummarize_summary_theme_callback'],
        ['wpsummarize_custom_css', esc_html__( 'Custom CSS', 'wpsummarize' ), 'wpsummarize_custom_css_callback'],
        ['wpsummarize_hide_summary_behind_button', esc_html__( 'Unfold summary on click', 'wpsummarize' ), 'wpsummarize_hide_summary_behind_button_callback'],
        ['wpsummarize_hide_temporarily', esc_html__( 'Temporarily hide summaries on all posts', 'wpsummarize' ), 'wpsummarize_hide_temporarily_callback']
    ];
    foreach ( $advanced_settings as $setting ) {
        add_settings_field(
            $setting[0],
            $setting[1],
            $setting[2],
            'wpsummarize_options',
            'wpsummarize_advanced_section'
        );
    }
}

add_action( 'admin_init', 'wpsummarize_admin_init' );
function wpsummarize_sanitize_language(  $language  ) {
    // Remove any characters that aren't letters or underscores
    $sanitized_language = preg_replace( '/[^a-zA-Z_]/', '', $language );
    // Limit to a maximum of 5 characters
    $sanitized_language = substr( $sanitized_language, 0, 5 );
    return $sanitized_language;
}

function wpsummarize_sanitize_options(  $input  ) {
    if ( !is_array( $input ) ) {
        return array();
    }
    $sanitized_input = array();
    // Basic Settings
    $sanitized_input['create_on_publish'] = ( isset( $input['create_on_publish'] ) ? rest_sanitize_boolean( $input['create_on_publish'] ) : false );
    $sanitized_input['auto_include'] = ( isset( $input['auto_include'] ) ? rest_sanitize_boolean( $input['auto_include'] ) : false );
    $sanitized_input['title_before_summary'] = ( isset( $input['title_before_summary'] ) ? sanitize_text_field( $input['title_before_summary'] ) : '' );
    $sanitized_input['insert_location'] = ( isset( $input['insert_location'] ) ? sanitize_key( $input['insert_location'] ) : '' );
    $sanitized_input['summary_style'] = ( isset( $input['summary_style'] ) ? sanitize_key( $input['summary_style'] ) : '' );
    $sanitized_input['language'] = ( isset( $input['language'] ) ? wpsummarize_sanitize_language( $input['language'] ) : '' );
    // Advanced Settings
    $sanitized_input['wpsummarize_post_types_enabled'] = ( isset( $input['wpsummarize_post_types_enabled'] ) && is_array( $input['wpsummarize_post_types_enabled'] ) ? array_map( 'sanitize_key', $input['wpsummarize_post_types_enabled'] ) : array() );
    $sanitized_input['summary_item_count'] = ( isset( $input['summary_item_count'] ) ? sanitize_key( $input['summary_item_count'] ) : '' );
    $sanitized_input['summary_item_min'] = ( isset( $input['summary_item_min'] ) ? absint( $input['summary_item_min'] ) : 0 );
    $sanitized_input['summary_item_max'] = ( isset( $input['summary_item_max'] ) ? absint( $input['summary_item_max'] ) : 0 );
    $sanitized_input['summary_word_count'] = ( isset( $input['summary_word_count'] ) ? absint( $input['summary_word_count'] ) : 0 );
    $sanitized_input['use_tags'] = ( isset( $input['use_tags'] ) ? rest_sanitize_boolean( $input['use_tags'] ) : false );
    $sanitized_input['summary_tone'] = ( isset( $input['summary_tone'] ) ? sanitize_key( $input['summary_tone'] ) : '' );
    $sanitized_input['summary_theme'] = ( isset( $input['summary_theme'] ) ? sanitize_key( $input['summary_theme'] ) : '' );
    $sanitized_input['hide_summary_behind_button'] = ( isset( $input['hide_summary_behind_button'] ) ? rest_sanitize_boolean( $input['hide_summary_behind_button'] ) : false );
    $sanitized_input['hide_all_summaries'] = ( isset( $input['hide_all_summaries'] ) ? rest_sanitize_boolean( $input['hide_all_summaries'] ) : false );
    return $sanitized_input;
}

function wpsummarize_save_button_callback() {
    $allowed_html = array(
        'p'     => array(
            'class' => array(),
        ),
        'input' => array(
            'type'  => array(),
            'name'  => array(),
            'id'    => array(),
            'class' => array(),
            'value' => array(),
        ),
    );
    echo '<p class="submit">';
    echo wp_kses( get_submit_button(
        esc_html__( 'Save Changes', 'wpsummarize' ),
        'primary',
        'submit',
        false
    ), $allowed_html );
    echo '</p>';
    echo '<br /><hr>';
}

function wpsummarize_advanced_section_callback() {
    echo '<hr>';
    echo '<p>' . esc_html__( 'Adjust the advanced options below.', 'wpsummarize' ) . '</p>';
}

function wpsummarize_simple_section_callback() {
    echo '<hr>';
    echo '<p>' . esc_html__( 'Customize how summaries are generated and displayed.', 'wpsummarize' ) . '</p>';
}

function wpsummarize_data_is_meta_box(
    $post_settings,
    $meta_key_title,
    $option_header,
    $final_input_value
) {
    if ( !empty( $post_settings ) ) {
        if ( isset( $post_settings[$meta_key_title] ) ) {
            $final_input_value = $post_settings[$meta_key_title];
        }
    }
    $extra_class = "";
    if ( $meta_key_title == "insert_location" || $meta_key_title == "summary_style" || $meta_key_title == "use_tags" || $meta_key_title == "summary_tone" || $meta_key_title == "summary_item_count" || $meta_key_title == "summary_word_count" ) {
        $extra_class = " wpsummarize-watched-input";
    }
    echo '<div class="wpsummarize-customization-control' . esc_attr( $extra_class ) . '">';
    echo '<span class="item-wpsummarize-meta-box">' . esc_html( $option_header ) . '</span>';
    // Determine the checkbox state based on post settings
    $use_default = '1';
    // Assume 'use default' is checked by default
    if ( is_array( $post_settings ) ) {
        // Check if the 'use_default_meta_key_title' key is set and adjust based on its value
        if ( isset( $post_settings["use_default_{$meta_key_title}"] ) ) {
            $use_default = $post_settings["use_default_{$meta_key_title}"];
        } else {
            $use_default = '0';
            // Uncheck if settings are defined but 'use_default' is not
        }
    }
    // Checkbox to choose default setting
    echo '<label><input type="checkbox" class="wpsummarize-default-checkbox" name="_wpsummarize_post_settings[use_default_' . esc_attr( $meta_key_title ) . ']" value="1" ' . checked( $use_default, '1', false ) . '> ' . esc_html__( 'Use Default Setting', 'wpsummarize' ) . '</label>';
    // Link to toggle the visibility of the customization options
    echo '<span class="wpsummarize-toggle-customization"> ' . esc_html__( 'Or customize this post... ', 'wpsummarize' ) . '</span>';
    $display_none = "";
    // Container for additional settings, initially hidden
    echo '<div class="customization-options">';
    return $final_input_value;
}

function wpsummarize_data_is_meta_box_close_divs() {
    echo '</div>';
    // Close the div if it was opened
    echo '</div>';
    // End of customization control wrapper
}

function wpsummarize_openai_api_key_callback(  $wpsummarize_options  ) {
    // Input for openai api key
    wpsummarize_display_api_key_field();
    $openailink = 'https://platform.openai.com/api-keys';
    $link_close = '</a>';
    $text = sprintf( 
        /* translators: %1$s: opening link tag, %2$s: closing link tag */
        esc_html__( 'You can get your OpenAI API key through %1$sthis link%2$s.', 'wpsummarize' ),
        '<a href="' . esc_url( $openailink ) . '" target="_blank" rel="noopener noreferrer">',
        '</a>'
     );
    echo '<br />';
    echo wp_kses( $text, array(
        'a' => array(
            'href'   => array(),
            'target' => array(),
            'rel'    => array(),
        ),
    ) );
    echo '<br />';
    $api_key = wpsummarize_get_api_key();
    if ( $api_key != "" ) {
        $validation_result = wpsummarize_validate_openai_api_key( $api_key );
        if ( $validation_result['is_valid'] ) {
            echo '<span style="color:green">' . esc_html__( 'API key is valid!', 'wpsummarize' ) . '</span>';
        } else {
            echo '<span style="color:red">' . esc_html__( 'API key is invalid. Error: ', 'wpsummarize' ) . esc_html( $validation_result['error'] ) . '</span>';
        }
    }
}

function wpsummarize_post_types_enabled_callback() {
    global $wpsummarize_options;
    // Fetch existing options or set default
    $enabled_post_types = ( isset( $wpsummarize_options['wpsummarize_post_types_enabled'] ) ? $wpsummarize_options['wpsummarize_post_types_enabled'] : [] );
    // Get all public post types including built-in and custom post types
    $args = array(
        'public' => true,
    );
    $post_types = get_post_types( $args, 'objects' );
    // Generate checkboxes for each post type
    foreach ( $post_types as $post_type ) {
        if ( $post_type->name === 'attachment' ) {
            continue;
            // Skip media attachments
        }
        $checked = ( in_array( $post_type->name, $enabled_post_types ) ? 'checked' : '' );
        echo '<label>';
        echo '<input type="checkbox" name="wpsummarize_options[wpsummarize_post_types_enabled][]" value="' . esc_attr( $post_type->name ) . '" ' . esc_attr( $checked ) . '>';
        echo esc_html( $post_type->labels->name );
        echo '</label><br>';
    }
}

function wpsummarize_auto_include_callback(  $wpsummarize_options, $post_settings = null, $is_meta_box = false  ) {
    global $wpsummarize_options;
    $default_value = '';
    $meta_key_title = 'auto_include';
    $option_header = esc_html__( 'Automatically insert summary in the post', 'wpsummarize' );
    $final_input_value = ( isset( $wpsummarize_options[$meta_key_title] ) ? $wpsummarize_options[$meta_key_title] : $default_value );
    if ( $is_meta_box ) {
        $final_input_value = wpsummarize_data_is_meta_box(
            $post_settings,
            $meta_key_title,
            $option_header,
            $final_input_value
        );
    }
    // Input for title before summary
    $name_attribute = ( $is_meta_box ? '_wpsummarize_post_settings[' . esc_attr( $meta_key_title ) . ']' : 'wpsummarize_options[' . esc_attr( $meta_key_title ) . ']' );
    // Echo only the checkbox and its label in the <td>, handle label here for full control
    echo '<input type="hidden" name="' . esc_attr( $name_attribute ) . '" value="0">';
    echo '<input type="checkbox" id="wpsummarize_' . esc_attr( $meta_key_title ) . '" name="' . esc_attr( $name_attribute ) . '" value="1" ' . checked( $final_input_value, '1', false ) . '>';
    echo '<label for="wpsummarize_' . esc_attr( $meta_key_title ) . '">' . esc_html__( 'Add summaries on posts when available', 'wpsummarize' ) . '</label>';
    if ( $is_meta_box ) {
        wpsummarize_data_is_meta_box_close_divs();
    }
}

function wpsummarize_title_before_summary_callback(  $wpsummarize_options, $post_settings = null, $is_meta_box = false  ) {
    global $wpsummarize_options;
    $default_value = esc_html__( 'Key takeaways', 'wpsummarize' );
    $meta_key_title = 'title_before_summary';
    $option_header = esc_html__( 'Title before summary', 'wpsummarize' );
    $final_input_value = ( isset( $wpsummarize_options[$meta_key_title] ) ? $wpsummarize_options[$meta_key_title] : $default_value );
    if ( $is_meta_box ) {
        $final_input_value = wpsummarize_data_is_meta_box(
            $post_settings,
            $meta_key_title,
            $option_header,
            $final_input_value
        );
    }
    // Input for title before summary
    $name_attribute = ( $is_meta_box ? '_wpsummarize_post_settings[' . esc_attr( $meta_key_title ) . ']' : 'wpsummarize_options[' . esc_attr( $meta_key_title ) . ']' );
    echo '<input type="text" id="wpsummarize_' . esc_attr( $meta_key_title ) . '" name="' . esc_attr( $name_attribute ) . '" value="' . esc_attr( $final_input_value ) . '">';
    if ( $is_meta_box ) {
        wpsummarize_data_is_meta_box_close_divs();
    }
}

function wpsummarize_insert_location_callback(  $wpsummarize_options, $post_settings = null, $is_meta_box = false  ) {
    global $wpsummarize_options;
    $default_value = 'after_first_paragraph';
    $meta_key_title = 'insert_location';
    $option_header = esc_html__( 'Where to insert summary', 'wpsummarize' );
    $final_input_value = ( isset( $wpsummarize_options[$meta_key_title] ) ? $wpsummarize_options[$meta_key_title] : $default_value );
    if ( $is_meta_box ) {
        $final_input_value = wpsummarize_data_is_meta_box(
            $post_settings,
            $meta_key_title,
            $option_header,
            $final_input_value
        );
    }
    $name_attribute = ( $is_meta_box ? '_wpsummarize_post_settings[' . esc_attr( $meta_key_title ) . ']' : 'wpsummarize_options[' . esc_attr( $meta_key_title ) . ']' );
    echo '<select id="wpsummarize_' . esc_attr( $meta_key_title ) . '" name="' . esc_attr( $name_attribute ) . '">
            <option value="above_content" ' . selected( $final_input_value, 'above_content', false ) . '>' . esc_html__( 'Above the Content', 'wpsummarize' ) . '</option>
            <option value="below_content" ' . selected( $final_input_value, 'below_content', false ) . '>' . esc_html__( 'Below the Content', 'wpsummarize' ) . '</option>
            <option value="after_first_paragraph" ' . selected( $final_input_value, 'after_first_paragraph', false ) . '>' . esc_html__( 'After First Paragraph', 'wpsummarize' ) . '</option>
            <option value="before_first_heading" ' . selected( $final_input_value, 'before_first_heading', false ) . '>' . esc_html__( 'Before First Heading Tag', 'wpsummarize' ) . '</option>
            <option value="manual_shortcode" ' . selected( $final_input_value, 'manual_shortcode', false ) . '>' . esc_html__( 'Manually Through Shortcode', 'wpsummarize' ) . '</option>
          </select>';
    if ( $is_meta_box ) {
        wpsummarize_data_is_meta_box_close_divs();
    }
}

function wpsummarize_get_language_names_array() {
    require_once ABSPATH . 'wp-admin/includes/translation-install.php';
    $languages = wp_get_available_translations();
    $language_names = array(
        'en_US' => 'English (United States)',
    );
    // Start with English
    foreach ( $languages as $locale => $language_data ) {
        $language_names[$locale] = $language_data['english_name'] . ' (' . $language_data['native_name'] . ')';
    }
    // Sort the array alphabetically by the English names
    asort( $language_names );
    return $language_names;
}

function wpsummarize_display_language_names_dropdown(  $name_id, $selected = ''  ) {
    $language_names = wpsummarize_get_language_names_array();
    // If no language is selected, default to the current language
    if ( empty( $selected ) ) {
        $current_locale = get_locale();
        $selected = $current_locale;
    }
    echo '<select name="' . esc_attr( $name_id ) . '" id="' . esc_attr( $name_id ) . '">';
    foreach ( $language_names as $locale => $display_name ) {
        echo '<option value="' . esc_attr( $locale ) . '"' . selected( $locale, $selected, false ) . '>' . esc_html( $display_name ) . '</option>';
    }
    echo '</select>';
}

function wpsummarize_language_callback(  $wpsummarize_options, $post_settings = null, $is_meta_box = false  ) {
    global $wpsummarize_options;
    $default_value = "";
    $meta_key_title = 'language';
    $option_header = esc_html__( 'Choose the output language of your summaries', 'wpsummarize' );
    $final_input_value = ( isset( $wpsummarize_options[$meta_key_title] ) ? $wpsummarize_options[$meta_key_title] : $default_value );
    if ( $is_meta_box ) {
        $final_input_value = wpsummarize_data_is_meta_box(
            $post_settings,
            $meta_key_title,
            $option_header,
            $final_input_value
        );
    }
    $name_attribute = ( $is_meta_box ? '_wpsummarize_post_settings[' . esc_attr( $meta_key_title ) . ']' : 'wpsummarize_options[' . esc_attr( $meta_key_title ) . ']' );
    wpsummarize_display_language_names_dropdown( $name_attribute, $final_input_value );
    if ( $is_meta_box ) {
        wpsummarize_data_is_meta_box_close_divs();
    }
}

function wpsummarize_summary_tone_callback(  $wpsummarize_options, $post_settings = null, $is_meta_box = false  ) {
    global $wpsummarize_options;
    $default_value = 'same_tone';
    $meta_key_title = 'summary_tone';
    $option_header = esc_html__( 'Choose the tone of the summary', 'wpsummarize' );
    $final_input_value = ( isset( $wpsummarize_options[$meta_key_title] ) ? $wpsummarize_options[$meta_key_title] : $default_value );
    if ( $is_meta_box ) {
        $final_input_value = wpsummarize_data_is_meta_box(
            $post_settings,
            $meta_key_title,
            $option_header,
            $final_input_value
        );
    }
    $name_attribute = ( $is_meta_box ? '_wpsummarize_post_settings[' . esc_attr( $meta_key_title ) . ']' : 'wpsummarize_options[' . esc_attr( $meta_key_title ) . ']' );
    echo '<select id="wpsummarize_summary_tone" name="' . esc_attr( $name_attribute ) . '"' . '>
    <option value="same_tone" ' . selected( $final_input_value, 'same_tone', false ) . '>' . esc_html__( 'Same tone as the article', 'wpsummarize' ) . '</option>';
    echo '</select>';
    if ( wpsummarize_fs()->is_not_paying() ) {
        echo '<br /><a href="' . esc_url( wpsummarize_fs()->get_upgrade_url() ) . '">' . esc_html__( 'Upgrade to adjust this option', 'wpsummarize' ) . '</a>';
    }
    if ( $is_meta_box ) {
        wpsummarize_data_is_meta_box_close_divs();
    }
}

function wpsummarize_summary_style_callback(  $wpsummarize_options, $post_settings = null, $is_meta_box = false  ) {
    global $wpsummarize_options;
    $default_value = '';
    $meta_key_title = 'summary_style';
    $option_header = esc_html__( 'Summary type', 'wpsummarize' );
    $final_input_value = ( isset( $wpsummarize_options[$meta_key_title] ) ? $wpsummarize_options[$meta_key_title] : $default_value );
    if ( $is_meta_box ) {
        $final_input_value = wpsummarize_data_is_meta_box(
            $post_settings,
            $meta_key_title,
            $option_header,
            $final_input_value
        );
    }
    // Input for title before summary
    $name_attribute = ( $is_meta_box ? '_wpsummarize_post_settings[' . esc_attr( $meta_key_title ) . ']' : 'wpsummarize_options[' . esc_attr( $meta_key_title ) . ']' );
    echo '<label for="list_style">';
    echo '<input type="radio" id="list_style" name="' . esc_attr( $name_attribute ) . '" value="list" ' . checked( $final_input_value, 'list', false ) . '>';
    echo esc_html__( 'List of key insights (recommended)', 'wpsummarize' ) . '</label><br>';
    echo '<label for="text_summary_style">';
    echo '<input type="radio" id="text_summary_style" name="' . esc_attr( $name_attribute ) . '" value="text_summary" ' . checked( $final_input_value, 'text_summary', false ) . '>';
    echo esc_html__( 'Text summary', 'wpsummarize' ) . '</label>';
    if ( $is_meta_box ) {
        wpsummarize_data_is_meta_box_close_divs();
    }
}

function wpsummarize_create_on_publish_callback(  $wpsummarize_options, $post_settings = null, $is_meta_box = false  ) {
    global $wpsummarize_options;
    $default_value = '';
    $meta_key_title = 'create_on_publish';
    $option_header = esc_html__( 'Create summary when you publish this post', 'wpsummarize' );
    $final_input_value = ( isset( $wpsummarize_options[$meta_key_title] ) ? $wpsummarize_options[$meta_key_title] : $default_value );
    if ( $is_meta_box ) {
        $final_input_value = wpsummarize_data_is_meta_box(
            $post_settings,
            $meta_key_title,
            $option_header,
            $final_input_value
        );
    }
    // Input for title before summary
    $name_attribute = ( $is_meta_box ? '_wpsummarize_post_settings[' . esc_attr( $meta_key_title ) . ']' : 'wpsummarize_options[' . esc_attr( $meta_key_title ) . ']' );
    // Echo only the checkbox and its label in the <td>, handle label here for full control
    echo '<input type="hidden" name="' . esc_attr( $name_attribute ) . '" value="0">';
    echo '<input type="checkbox" id="wpsummarize_' . esc_attr( $meta_key_title ) . '" name="' . esc_attr( $name_attribute ) . '" value="1" ' . checked( $final_input_value, '1', false ) . '> ';
    echo '<label for="wpsummarize_' . esc_attr( $meta_key_title ) . '">' . esc_html__( 'Create on publish', 'wpsummarize' ) . '</label>';
    if ( $is_meta_box ) {
        wpsummarize_data_is_meta_box_close_divs();
    }
}

function wpsummarize_update_on_edit_callback(  $wpsummarize_options, $post_settings = null, $is_meta_box = false  ) {
    global $wpsummarize_options;
    $default_value = '0';
    $meta_key_title = 'update_on_edit';
    $option_header = esc_html__( 'Update summary when a post is updated', 'wpsummarize' );
    $final_input_value = ( isset( $wpsummarize_options[$meta_key_title] ) ? $wpsummarize_options[$meta_key_title] : $default_value );
    if ( $is_meta_box ) {
        $final_input_value = wpsummarize_data_is_meta_box(
            $post_settings,
            $meta_key_title,
            $option_header,
            $final_input_value
        );
    }
    // Input for title before summary
    $name_attribute = ( $is_meta_box ? '_wpsummarize_post_settings[' . esc_attr( $meta_key_title ) . ']' : 'wpsummarize_options[' . esc_attr( $meta_key_title ) . ']' );
    // Echo only the checkbox and its label in the <td>, handle label here for full control
    echo '<input type="hidden" name="' . esc_attr( $name_attribute ) . '" value="0">';
    echo '<input type="checkbox" id="wpsummarize_' . esc_attr( $meta_key_title ) . '" name="' . esc_attr( $name_attribute ) . '" value="1" ' . checked( $final_input_value, '1', false ) . '>';
    if ( $is_meta_box ) {
        wpsummarize_data_is_meta_box_close_divs();
    }
}

function wpsummarize_use_tags_callback(  $wpsummarize_options, $post_settings = null, $is_meta_box = false  ) {
    global $wpsummarize_options;
    $default_value = '';
    $meta_key_title = 'use_tags';
    $option_header = esc_html__( 'Use &lt;strong&gt; and &lt;em&gt; tags when relevant', 'wpsummarize' );
    $final_input_value = ( isset( $wpsummarize_options[$meta_key_title] ) ? $wpsummarize_options[$meta_key_title] : $default_value );
    if ( $is_meta_box ) {
        $final_input_value = wpsummarize_data_is_meta_box(
            $post_settings,
            $meta_key_title,
            $option_header,
            $final_input_value
        );
    }
    // Input for title before summary
    $name_attribute = ( $is_meta_box ? '_wpsummarize_post_settings[' . esc_attr( $meta_key_title ) . ']' : 'wpsummarize_options[' . esc_attr( $meta_key_title ) . ']' );
    // Echo only the checkbox and its label in the <td>, handle label here for full control
    echo '<input type="hidden" name="' . esc_attr( $name_attribute ) . '" value="0">';
    $output = esc_html__( 'No', 'wpsummarize' );
    if ( !empty( $output ) ) {
        echo '<input type="hidden" name="' . esc_attr( $name_attribute ) . '" value="0">';
        echo esc_html( $output );
    }
    if ( wpsummarize_fs()->is_not_paying() ) {
        echo '<br /><a href="' . esc_url( wpsummarize_fs()->get_upgrade_url() ) . '">' . esc_html__( 'Upgrade to enable style tags', 'wpsummarize' ) . '</a>';
    }
    if ( $is_meta_box ) {
        wpsummarize_data_is_meta_box_close_divs();
    }
}

function wpsummarize_summary_item_count_callback(  $wpsummarize_options, $post_settings = null, $is_meta_box = false  ) {
    global $wpsummarize_options;
    $default_value = 'range';
    $default_value_range_min = 3;
    $default_value_range_max = 5;
    $meta_key_title = 'summary_item_count';
    $meta_key_title_range_min = 'summary_item_min';
    $meta_key_title_range_max = 'summary_item_max';
    $option_header = esc_html__( 'Choose how many items in list of key insights summary', 'wpsummarize' );
    $final_input_value = ( isset( $wpsummarize_options[$meta_key_title] ) ? $wpsummarize_options[$meta_key_title] : $default_value );
    $final_input_value_range_min = ( isset( $wpsummarize_options[$meta_key_title_range_min] ) ? $wpsummarize_options[$meta_key_title_range_min] : $default_value_range_min );
    $final_input_value_range_max = ( isset( $wpsummarize_options[$meta_key_title_range_max] ) ? $wpsummarize_options[$meta_key_title_range_max] : $default_value_range_max );
    if ( $is_meta_box ) {
        $final_input_value = wpsummarize_data_is_meta_box(
            $post_settings,
            $meta_key_title,
            $option_header,
            $final_input_value
        );
        // Check if post-specific settings are available and override the global setting if applicable
        if ( !empty( $post_settings ) && isset( $post_settings[$meta_key_title_range_min] ) ) {
            $final_input_value_range_min = $post_settings[$meta_key_title_range_min];
        }
        // Check if post-specific settings are available and override the global setting if applicable
        if ( !empty( $post_settings ) && isset( $post_settings[$meta_key_title_range_max] ) ) {
            $final_input_value_range_max = $post_settings[$meta_key_title_range_max];
        }
    }
    // name ids for options
    $name_attribute = ( $is_meta_box ? '_wpsummarize_post_settings[' . $meta_key_title . ']' : 'wpsummarize_options[' . $meta_key_title . ']' );
    $name_attribute_range_min = ( $is_meta_box ? '_wpsummarize_post_settings[' . $meta_key_title_range_min . ']' : 'wpsummarize_options[' . $meta_key_title_range_min . ']' );
    $name_attribute_range_max = ( $is_meta_box ? '_wpsummarize_post_settings[' . $meta_key_title_range_max . ']' : 'wpsummarize_options[' . $meta_key_title_range_max . ']' );
    $output = esc_html__( 'Between', 'wpsummarize' ) . esc_html( ' 3 ' ) . esc_html__( ' and ', 'wpsummarize' ) . esc_html( ' 5 ' );
    if ( !empty( $output ) ) {
        echo '<input type="hidden" name="' . esc_attr( $name_attribute ) . '" value="range">';
        echo '<input type="hidden" name="' . esc_attr( $name_attribute_range_min ) . '" value="3">';
        echo '<input type="hidden" name="' . esc_attr( $name_attribute_range_max ) . '" value="5">';
        echo esc_html( $output );
    }
    if ( wpsummarize_fs()->is_not_paying() ) {
        echo '<br /><a href="' . esc_url( wpsummarize_fs()->get_upgrade_url() ) . '">' . esc_html__( 'Upgrade to adjust this option', 'wpsummarize' ) . '</a>';
    }
    if ( $is_meta_box ) {
        wpsummarize_data_is_meta_box_close_divs();
    }
}

function wpsummarize_summary_word_count_callback(  $wpsummarize_options, $post_settings = null, $is_meta_box = false  ) {
    global $wpsummarize_options;
    $default_value = '125';
    $meta_key_title = 'summary_word_count';
    $option_header = esc_html__( 'Approximate words in text summary', 'wpsummarize' );
    $final_input_value = ( isset( $wpsummarize_options[$meta_key_title] ) ? $wpsummarize_options[$meta_key_title] : $default_value );
    if ( $is_meta_box ) {
        $final_input_value = wpsummarize_data_is_meta_box(
            $post_settings,
            $meta_key_title,
            $option_header,
            $final_input_value
        );
    }
    // Input for title before summary
    $name_attribute = ( $is_meta_box ? '_wpsummarize_post_settings[' . $meta_key_title . ']' : 'wpsummarize_options[' . $meta_key_title . ']' );
    $output = absint( $default_value );
    if ( !empty( $output ) ) {
        echo '<input type="hidden" name="' . esc_attr( $name_attribute ) . '" value="125">';
        echo esc_html( $output );
    }
    if ( wpsummarize_fs()->is_not_paying() ) {
        echo '<br /><a href="' . esc_url( wpsummarize_fs()->get_upgrade_url() ) . '">' . esc_html__( 'Upgrade to adjust this option', 'wpsummarize' ) . '</a>';
    }
    if ( $is_meta_box ) {
        wpsummarize_data_is_meta_box_close_divs();
    }
}

function wpsummarize_summary_theme_callback(  $wpsummarize_options, $post_settings = null, $is_meta_box = false  ) {
    global $wpsummarize_options;
    $default_value = 'classic';
    $meta_key_title = 'summary_theme';
    $option_header = esc_html__( 'Summary theme', 'wpsummarize' );
    $final_input_value = ( isset( $wpsummarize_options[$meta_key_title] ) ? $wpsummarize_options[$meta_key_title] : $default_value );
    if ( $is_meta_box ) {
        $final_input_value = wpsummarize_data_is_meta_box(
            $post_settings,
            $meta_key_title,
            $option_header,
            $final_input_value
        );
    }
    // Input for title before summary
    $name_attribute = ( $is_meta_box ? '_wpsummarize_post_settings[' . $meta_key_title . ']' : 'wpsummarize_options[' . $meta_key_title . ']' );
    $themes = [
        'classic' => esc_html__( 'Classic', 'wpsummarize' ),
    ];
    echo '<div class="wrap_themes">';
    foreach ( $themes as $key => $style_name ) {
        echo '<label>';
        echo '<input type="radio" name="' . esc_attr( $name_attribute ) . '" value="' . esc_attr( $key ) . '" ' . checked( $final_input_value, $key, false ) . '>';
        echo '<span>' . esc_html( $style_name ) . '</span>';
        if ( !$is_meta_box ) {
            echo '<img src="' . esc_url( plugin_dir_url( __DIR__ ) . 'assets/images/' . $key . '.png' ) . '" alt="' . esc_attr( $style_name ) . '" style="width: 100px; height: auto; margin-left: 10px;">';
        }
        echo '</label><br>';
    }
    echo '</div>';
    if ( wpsummarize_fs()->is_not_paying() ) {
        echo '<br /><a href="' . esc_url( wpsummarize_fs()->get_upgrade_url() ) . '">' . esc_html__( 'Upgrade to choose from more themes', 'wpsummarize' ) . '</a>';
    }
    if ( $is_meta_box ) {
        wpsummarize_data_is_meta_box_close_divs();
    }
}

function wpsummarize_custom_css_callback() {
    $wpsummarize_custom_css = get_option( 'wpsummarize_custom_css' );
    $final_input_value = ( isset( $wpsummarize_custom_css ) ? $wpsummarize_custom_css : '' );
    // Input for title before summary
    echo '<textarea name="wpsummarize_custom_css" rows="10" cols="50">' . esc_textarea( esc_html( $final_input_value ) ) . '</textarea>';
}

function wpsummarize_sanitize_custom_css(  $input  ) {
    return esc_html( $input );
}

function wpsummarize_hide_summary_behind_button_callback(  $wpsummarize_options, $post_settings = null, $is_meta_box = false  ) {
    global $wpsummarize_options;
    $default_value = '0';
    $meta_key_title = 'hide_summary_behind_button';
    $option_header = esc_html__( 'Unfold summary on click', 'wpsummarize' );
    $final_input_value = ( isset( $wpsummarize_options[$meta_key_title] ) ? $wpsummarize_options[$meta_key_title] : $default_value );
    if ( $is_meta_box ) {
        $final_input_value = wpsummarize_data_is_meta_box(
            $post_settings,
            $meta_key_title,
            $option_header,
            $final_input_value
        );
    }
    // Input for title before summary
    $name_attribute = ( $is_meta_box ? '_wpsummarize_post_settings[' . $meta_key_title . ']' : 'wpsummarize_options[' . $meta_key_title . ']' );
    $output = esc_html__( 'No', 'wpsummarize' );
    if ( !empty( $output ) ) {
        echo '<input type="hidden" name="' . esc_attr( $name_attribute ) . '" value="0">';
        echo esc_html( $output );
    }
    if ( wpsummarize_fs()->is_not_paying() ) {
        echo '<br /><a href="' . esc_url( wpsummarize_fs()->get_upgrade_url() ) . '">' . esc_html__( 'Upgrade to enable this option', 'wpsummarize' ) . '</a>';
    }
    if ( $is_meta_box ) {
        wpsummarize_data_is_meta_box_close_divs();
    }
}

function wpsummarize_hide_temporarily_callback() {
    global $wpsummarize_options;
    $default_value = '0';
    $meta_key_title = 'hide_all_summaries';
    $final_input_value = ( isset( $wpsummarize_options[$meta_key_title] ) ? $wpsummarize_options[$meta_key_title] : $default_value );
    // Input for title before summary
    $name_attribute = 'wpsummarize_options[' . $meta_key_title . ']';
    echo '<input type="hidden" name="' . esc_attr( $name_attribute ) . '" value="0">';
    echo '<input type="checkbox" id="wpsummarize_' . esc_attr( $meta_key_title ) . '" name="' . esc_attr( $name_attribute ) . '" value="1" ' . checked( $final_input_value, '1', false ) . '>';
    echo '<label for="wpsummarize_' . esc_attr( $meta_key_title ) . '">' . esc_html__( 'Hide all summaries in all posts regardless of other configuration', 'wpsummarize' ) . '</label>';
}
