<?php

/**
 * Plugin Name: WPSummarize
 * Plugin URI: https://wpsummarize.com/
 * Description: Add a summary of your content in your posts
 * Version: 1.0.16
 * Author: Julian Yanover
 * Author URI: https://wpsummarize.com/
 * Text Domain: wpsummarize
 * License: GPLv2
 * Released under the GNU General Public License (GPL)
 * https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 */
/**
 * WPSummarize main plugin file.
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Define constants for easy access to the plugin's version and path.
 */
define( 'WPSUMMARIZE_VERSION', '1.0.16' );
define( 'WPSUMMARIZE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPSUMMARIZE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
require_once plugin_dir_path( __FILE__ ) . '/vendor/autoload.php';
add_filter( 'cron_schedules', 'wpsummarize_add_every_minute_schedule' );
function wpsummarize_add_every_minute_schedule(  $schedules  ) {
    $schedules['every_minute'] = array(
        'interval' => 60,
        'display'  => esc_html__( 'Every Minute', 'wpsummarize' ),
    );
    return $schedules;
}

/**
 * Include Freemius SDK.
 */
if ( !function_exists( 'wpsummarize_fs' ) ) {
    // Create a helper function for easy SDK access.
    function wpsummarize_fs() {
        global $wpsummarize_fs;
        if ( !isset( $wpsummarize_fs ) ) {
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/vendor/freemius/wordpress-sdk/start.php';
            $wpsummarize_fs = fs_dynamic_init( array(
                'id'             => '16104',
                'slug'           => 'wpsummarize',
                'premium_slug'   => 'wpsummarize-pro',
                'type'           => 'plugin',
                'public_key'     => 'pk_f41ebc2b4fcd4419756e4d91d78e7',
                'is_premium'     => false,
                'premium_suffix' => 'Pro',
                'has_addons'     => false,
                'has_paid_plans' => true,
                'trial'          => array(
                    'days'               => 7,
                    'is_require_payment' => false,
                ),
                'menu'           => array(
                    'slug'    => 'wpsummarize',
                    'support' => false,
                ),
                'is_live'        => true,
            ) );
        }
        return $wpsummarize_fs;
    }

    // Init Freemius.
    wpsummarize_fs();
    // Signal that SDK was initiated.
    do_action( 'wpsummarize_fs_loaded' );
}
/**
 * Include the main plugin functionalities.
 */
require_once WPSUMMARIZE_PLUGIN_DIR . 'includes/main_functions.php';
require_once WPSUMMARIZE_PLUGIN_DIR . 'admin/admin_menu.php';
require_once WPSUMMARIZE_PLUGIN_DIR . 'admin/editor_buttons.php';
require_once WPSUMMARIZE_PLUGIN_DIR . 'admin/settings.php';
require_once WPSUMMARIZE_PLUGIN_DIR . 'admin/apikey_functions.php';
require_once WPSUMMARIZE_PLUGIN_DIR . 'includes/api_handler.php';
require_once WPSUMMARIZE_PLUGIN_DIR . 'admin/meta_boxes.php';
register_activation_hook( __FILE__, 'wpsummarize_activate' );
function wpsummarize_activate() {
    $default_options = array(
        'auto_include'                   => '1',
        'insert_location'                => 'after_first_paragraph',
        'summary_style'                  => 'list',
        'create_on_publish'              => '1',
        'list_style'                     => 'disc',
        'summary_item_count'             => 'range',
        'summary_item_min'               => '3',
        'summary_item_max'               => '5',
        'summary_word_count'             => '125',
        'title_before_summary'           => esc_html__( 'Key takeaways', 'wpsummarize' ),
        'summary_theme'                  => 'classic',
        'hide_summary_behind_button'     => '0',
        'use_tags'                       => '1',
        'wpsummarize_post_types_enabled' => ['post'],
        'tone'                           => 'same_tone',
        'language'                       => get_locale(),
    );
    // Get current options from the database
    $options = get_option( 'wpsummarize_options' );
    // If options do not exist, set them to the defaults
    if ( $options === false ) {
        update_option( 'wpsummarize_options', $default_options );
    } else {
        // Ensure all default options exist
        $updated_options = wp_parse_args( $options, $default_options );
        update_option( 'wpsummarize_options', $updated_options );
    }
    // Get current options from the database
    $openai_api_key = get_option( 'wpsummarize_openai_api_key' );
    // If options do not exist, set them to the defaults
    if ( $openai_api_key === false ) {
        update_option( 'wpsummarize_openai_api_key', '' );
    }
    $schedules = wp_get_schedules();
    if ( isset( $schedules['every_minute'] ) ) {
        if ( !wp_next_scheduled( 'action_scheduler_run_queue' ) ) {
            wp_schedule_event( time(), 'every_minute', 'action_scheduler_run_queue' );
        }
    }
}

if ( !class_exists( 'ActionScheduler' ) ) {
    // Include Action Scheduler
    require_once dirname( __FILE__ ) . '/vendor/woocommerce/action-scheduler/action-scheduler.php';
}
function wpsummarize_enqueue_admin_styles(  $hook  ) {
    wp_enqueue_style(
        'wpsummarize_admin_css',
        plugin_dir_url( __FILE__ ) . 'assets/css/admin-style.css',
        array(),
        WPSUMMARIZE_VERSION,
        'all'
    );
}

add_action( 'admin_enqueue_scripts', 'wpsummarize_enqueue_admin_styles' );
function wpsummarize_initialize_action_scheduler() {
    if ( class_exists( 'ActionScheduler_Versions' ) ) {
        ActionScheduler_Versions::initialize_latest_version();
    }
}

add_action( 'plugins_loaded', 'wpsummarize_initialize_action_scheduler' );
function wpsummarize_load_textdomain() {
    $domain = 'wpsummarize';
    $locale = determine_locale();
    // Define fallback locales
    $fallbacks = array(
        'es' => 'es_ES',
        'fr' => 'fr_FR',
        'pt' => 'pt_BR',
        'de' => 'de_DE',
    );
    // Check if we're dealing with a variant of a language we have a fallback for
    $base_locale = substr( $locale, 0, 2 );
    if ( isset( $fallbacks[$base_locale] ) ) {
        $mofile = $domain . '-' . $fallbacks[$base_locale] . '.mo';
    } else {
        // For other languages, try the full locale first, then fall back to base locale
        $mofile = $domain . '-' . $locale . '.mo';
        $underscored_locale = str_replace( '-', '_', $locale );
        $path = WPSUMMARIZE_PLUGIN_DIR . 'languages/';
        if ( file_exists( $path . $mofile ) ) {
            // Hyphenated version exists
        } elseif ( file_exists( $path . $domain . '-' . $underscored_locale . '.mo' ) ) {
            // Underscored version exists
            $mofile = $domain . '-' . $underscored_locale . '.mo';
        } elseif ( file_exists( $path . $domain . '-' . $base_locale . '.mo' ) ) {
            // Base locale version exists
            $mofile = $domain . '-' . $base_locale . '.mo';
        } else {
            // No suitable file found, will fall back to default text
            return false;
        }
    }
    $path = WPSUMMARIZE_PLUGIN_DIR . 'languages/';
    $loaded = load_textdomain( $domain, $path . $mofile );
    return $loaded;
}

add_action( 'plugins_loaded', 'wpsummarize_load_textdomain' );