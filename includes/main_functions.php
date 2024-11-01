<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Exit if accessed directly
function wpsummarize_sanitize_html_input(  $input  ) {
    // Define the allowed HTML tags
    $allowed_html = array(
        'ul'     => array(),
        'ol'     => array(),
        'li'     => array(),
        'p'      => array(),
        'strong' => array(),
        'em'     => array(),
        'b'      => array(),
        'i'      => array(),
        'span'   => array(
            'style' => array(),
        ),
        'a'      => array(
            'href'  => array(),
            'title' => array(),
        ),
        'br'     => array(),
    );
    // Sanitize the input
    $sanitized_input = wp_kses( $input, $allowed_html );
    // Additional sanitization if needed
    $sanitized_input = stripslashes( $sanitized_input );
    return $sanitized_input;
}

function wpsummarize_enqueue_public_css() {
    // Check if the style has already been enqueued
    if ( !wp_style_is( 'wpsummarize-style', 'enqueued' ) ) {
        $summary_theme = "classic";
        if ( is_singular() ) {
            wp_enqueue_style(
                'wpsummarize-style',
                plugin_dir_url( __DIR__ ) . 'assets/css/wpsummarize_' . $summary_theme . '.css',
                array(),
                WPSUMMARIZE_VERSION
            );
        }
    }
}

add_action( 'wp_enqueue_scripts', 'wpsummarize_enqueue_public_css' );
function wpsummarize_enqueue_custom_css() {
    if ( is_singular() ) {
        $custom_css = get_option( 'wpsummarize_custom_css', '' );
        if ( !empty( $custom_css ) ) {
            // Enqueue a dummy stylesheet
            wp_register_style( 'wpsummarize-custom-style', false );
            wp_enqueue_style( 'wpsummarize-custom-style' );
            // Add the custom CSS
            wp_add_inline_style( 'wpsummarize-custom-style', esc_html( $custom_css ) );
        }
    }
}

add_action( 'wp_enqueue_scripts', 'wpsummarize_enqueue_custom_css' );
function wpsummarize_format_style(  $summary  ) {
    $edited_summary = strip_tags( $summary, "<p><ul><li>" );
    return $edited_summary;
}

function wpsummarize_add_to_content(  $content, $summary, $post_id  ) {
    $wpsummarize_options = get_option( 'wpsummarize_options' );
    // Get the insertion location from options
    $insert_location = ( isset( $wpsummarize_options['insert_location'] ) ? $wpsummarize_options['insert_location'] : 'after_first_paragraph' );
    // Get the insertion location from options
    $hide_summary_behind_button = ( isset( $wpsummarize_options['hide_summary_behind_button'] ) ? $wpsummarize_options['hide_summary_behind_button'] : '0' );
    $title_before_summary = ( isset( $wpsummarize_options['title_before_summary'] ) ? $wpsummarize_options['title_before_summary'] : '' );
    $summary_theme = ( isset( $wpsummarize_options['summary_theme'] ) ? $wpsummarize_options['summary_theme'] : 'classic' );
    $summary_box = '<div class="wpsummary_box design-' . $summary_theme . '">';
    if ( $hide_summary_behind_button != 1 ) {
        $summary_box .= '<span class="wpsummarize_title">' . $title_before_summary . '</span>';
        $summary_box .= wpsummarize_format_style( $summary );
    }
    $summary_box .= '</div>';
    // If the insertion method is 'shortcode', return the summary box
    if ( $insert_location === 'manual_shortcode' and $content == "shortcode_call" ) {
        return $summary_box;
    }
    // Handle different page builders and content structures
    $page_builder_content = wpsummarize_get_page_builder_content( $post_id );
    $content_to_modify = ( $page_builder_content ?: $content );
    // Process blocks if it's a block-based theme or uses Gutenberg
    if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() || function_exists( 'use_block_editor_for_post' ) && use_block_editor_for_post( $post_id ) ) {
        $blocks = parse_blocks( $content_to_modify );
        $content_to_modify = '';
        foreach ( $blocks as $block ) {
            $content_to_modify .= render_block( $block );
        }
    }
    // Insert the custom content based on the specified location
    switch ( $insert_location ) {
        case 'above_content':
            $modified_content = $summary_box . $content_to_modify;
            break;
        case 'below_content':
            $modified_content = $content_to_modify . $summary_box;
            break;
        case 'after_first_paragraph':
            $pos = strpos( $content_to_modify, '</p>' );
            if ( $pos !== false ) {
                $modified_content = substr_replace(
                    $content_to_modify,
                    $summary_box,
                    $pos + 4,
                    0
                );
            } else {
                $modified_content = $content_to_modify . $summary_box;
                // Fallback to appending
            }
            break;
        case 'before_first_heading':
            $pattern = '/<h[1-6][^>]*>/i';
            if ( preg_match(
                $pattern,
                $content_to_modify,
                $matches,
                PREG_OFFSET_CAPTURE
            ) ) {
                $pos = $matches[0][1];
                $modified_content = substr_replace(
                    $content_to_modify,
                    $summary_box,
                    $pos,
                    0
                );
            } else {
                $modified_content = $summary_box . $content_to_modify;
                // Fallback to prepending
            }
            break;
        default:
            $modified_content = $content_to_modify;
    }
    return $modified_content;
}

function wpsummarize_get_page_builder_content(  $post_id  ) {
    // Elementor
    if ( did_action( 'elementor/loaded' ) && function_exists( '\\Elementor\\Plugin::instance' ) ) {
        $document = \Elementor\Plugin::instance()->documents->get( $post_id );
        if ( $document && $document->is_built_with_elementor() ) {
            return $document->get_content();
        }
    }
    // Divi
    if ( function_exists( 'et_builder_init_global_settings' ) && function_exists( 'et_pb_is_pagebuilder_used' ) && et_pb_is_pagebuilder_used( $post_id ) ) {
        return do_shortcode( get_post_field( 'post_content', $post_id ) );
    }
    // Oxygen
    if ( function_exists( 'ct_template_output' ) ) {
        $oxygen_content = get_post_meta( $post_id, 'ct_builder_json', true );
        if ( $oxygen_content ) {
            return do_shortcode( wpsummarize_oxygen_render_json_for_content( $oxygen_content ) );
        }
    }
    // WPBakery Page Builder
    if ( function_exists( 'vc_is_inline' ) && function_exists( 'vc_get_page_as_array' ) ) {
        $wpbakery_data = vc_get_page_as_array( $post_id );
        if ( $wpbakery_data ) {
            return do_shortcode( vc_get_page_as_string( $wpbakery_data ) );
        }
    }
    // Beaver Builder
    if ( class_exists( 'FLBuilderModel' ) && method_exists( 'FLBuilderModel', 'is_builder_enabled' ) && FLBuilderModel::is_builder_enabled( $post_id ) ) {
        return FLBuilder::render_content_by_id( $post_id );
    }
    // Avada (Fusion Builder)
    if ( function_exists( 'fusion_is_builder_enabled' ) && function_exists( 'fusion_builder_get_content' ) && fusion_is_builder_enabled( $post_id ) ) {
        return do_shortcode( fusion_builder_get_content( $post_id ) );
    }
    // SiteOrigin Page Builder
    if ( class_exists( 'SiteOrigin_Panels' ) && function_exists( 'siteorigin_panels_render' ) ) {
        $panels_data = get_post_meta( $post_id, 'panels_data', true );
        if ( $panels_data ) {
            return siteorigin_panels_render( $post_id, false, $panels_data );
        }
    }
    // Thrive Architect
    if ( function_exists( 'tve_get_post_meta' ) ) {
        $tve_content = tve_get_post_meta( $post_id, 'tve_updated_post' );
        if ( $tve_content ) {
            return do_shortcode( $tve_content );
        }
    }
    // OptimizePress 3
    if ( function_exists( 'op3_get_page_data' ) ) {
        $op3_data = op3_get_page_data( $post_id );
        if ( $op3_data ) {
            return do_shortcode( op3_get_rendered_content( $post_id ) );
        }
    }
    // Brizy
    if ( class_exists( 'Brizy_Editor_Post' ) && method_exists( 'Brizy_Editor_Post', 'get' ) ) {
        $brizy_post = Brizy_Editor_Post::get( $post_id );
        if ( $brizy_post && $brizy_post->uses_editor() ) {
            return do_shortcode( $brizy_post->get_compiled_html() );
        }
    }
    return '';
    // Return empty string if no page builder content is found
}

// Helper function for Oxygen
function wpsummarize_oxygen_render_json_for_content(  $json  ) {
    $content = '';
    $data = json_decode( $json, true );
    if ( is_array( $data ) ) {
        foreach ( $data as $element ) {
            if ( !empty( $element['children'] ) ) {
                $content .= wpsummarize_oxygen_render_json_for_content( wp_json_encode( $element['children'] ) );
            } elseif ( !empty( $element['options']['ct_content'] ) ) {
                $content .= $element['options']['ct_content'];
            }
        }
    }
    return $content;
}

// Hook to modify the content
add_filter( 'the_content', 'wpsummarize_insert_summary' );
function wpsummarize_insert_summary(  $content  ) {
    // Check if we're on a singular post and in the main query
    if ( !is_singular() || !is_main_query() ) {
        return $content;
    }
    $post_id = get_the_ID();
    // Check if there's a summary in wp_postmeta
    $summary = get_post_meta( $post_id, '_wpsummarize_summary_set', true );
    if ( empty( $summary ) ) {
        return $content;
    }
    // Get plugin options
    $options = get_option( 'wpsummarize_options', array() );
    // Check if summaries are globally hidden
    if ( isset( $options['hide_all_summaries'] ) && $options['hide_all_summaries'] == 1 ) {
        return $content;
    }
    // Check if the current post type is enabled for summaries
    $current_post_type = get_post_type( $post_id );
    $enabled_post_types = ( isset( $options['wpsummarize_post_types_enabled'] ) ? $options['wpsummarize_post_types_enabled'] : array() );
    if ( !in_array( $current_post_type, $enabled_post_types ) ) {
        return $content;
    }
    // If all checks pass, insert the summary
    return wpsummarize_add_to_content( $content, $summary, $post_id );
}

// Shortcode handler
function wpsummarize_shortcode(  $atts  ) {
    $post_id = get_the_ID();
    //$post = get_post($post_id);
    $shortcode = wpsummarize_insert_summary( "shortcode_call" );
    return $shortcode;
}

add_shortcode( 'wpsummarize', 'wpsummarize_shortcode' );
// 3. AJAX handler to fetch the summary (remains the same)
function wpsummarize_get_summary() {
    check_ajax_referer( 'wpsummarize_nonce', 'nonce' );
    $post_id = ( isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0 );
    if ( !$post_id ) {
        wp_send_json_error( 'Invalid post ID' );
    }
    $summary = get_post_meta( $post_id, '_wpsummarize_summary_set', true );
    if ( empty( $summary ) ) {
        wp_send_json_error( 'No summary available' );
    }
    $summary_box = wpsummary_format_style( $summary );
    wp_send_json_success( $summary_box );
}

add_action( 'wp_ajax_wpsummarize_get_summary', 'wpsummarize_get_summary' );
add_action( 'wp_ajax_nopriv_wpsummarize_get_summary', 'wpsummarize_get_summary' );