<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Exit if accessed directly
function wpsummarize_admin_menu() {
    // Main menu page
    add_menu_page(
        esc_html__( 'WPSummarize Dashboard', 'wpsummarize' ),
        // Page title
        esc_html__( 'WPSummarize', 'wpsummarize' ),
        // Menu title
        'manage_options',
        // Capability
        'wpsummarize',
        // Menu slug
        'wpsummarize_dashboard_page',
        // Function to display the dashboard page
        'data:image/svg+xml;base64,' . base64_encode( wpsummarize_sanitize_svg( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#a7aaad"><path d="M4 4c0-1.1.9-2 2-2h12c1.1 0 2 .9 2 2v16c0 1.1-.9 2-2 2H6c-1.1 0-2-.9-2-2V4zm2 0v16h12V4H6zm2 3h8v2H8V7zm0 4h8v2H8v-2zm0 4h8v2H8v-2z"/></svg>' ) )
    );
    // Manually add the Dashboard submenu page that matches the main menu
    add_submenu_page(
        'wpsummarize',
        // Parent slug, matching the main menu slug
        esc_html__( 'WPSummarize Dashboard', 'wpsummarize' ),
        // Page title
        esc_html__( 'Dashboard', 'wpsummarize' ),
        // Menu title
        'manage_options',
        // Capability
        'wpsummarize',
        // Menu slug, same as main menu to actually link to the main menu item
        'wpsummarize_dashboard_page'
    );
    // Additional submenu page for settings
    add_submenu_page(
        'wpsummarize',
        // Parent slug, matching the main menu slug
        esc_html__( 'WPSummarize Settings', 'wpsummarize' ),
        // Page title
        esc_html__( 'Settings', 'wpsummarize' ),
        // Menu title
        'manage_options',
        // Capability
        'wpsummarize_settings',
        // Menu slug
        'wpsummarize_settings_page'
    );
}

function wpsummarize_settings_page() {
    ?>
    <div class="wrap wpsummarize_settings_form">
        <h2 class="wpsummarize_settings_title"><?php 
    echo esc_html__( 'WPSummarize Settings', 'wpsummarize' );
    ?></h2>
        
        <!-- Begin Form -->
        <form method="post" action="options.php">
            <?php 
    settings_fields( 'wpsummarize_options' );
    // Unified option group
    do_settings_sections( 'wpsummarize_options' );
    // Display all sections
    submit_button( esc_html__( 'Save Changes', 'wpsummarize' ) );
    ?>
        </form>
    </div>
    <?php 
}

function wpsummarize_dashboard_page() {
    global $wpdb;
    ?>
    <div class="wrap wpsummarize_settings_form">
        <h2 class="wpsummarize_settings_title"><?php 
    echo esc_html__( 'WPSummarize Dashboard', 'wpsummarize' );
    ?></h2>
        <?php 
    $tokens_data = wpsummarize_api_check_tokens( 'dashboard' );
    $tokens_text = sprintf( 
        // translators: %1$d is the number of credits used, %2$d is the total number of credits available for the month
        esc_html__( 'Tokens used this month: %1$s / %2$s', 'wpsummarize' ),
        esc_html( $tokens_data['tokens_used_month'] ),
        esc_html( $tokens_data['token_limit'] )
     );
    $allowed_html = array(
        'div'  => array(
            'class' => array(),
            'id'    => array(),
        ),
        'span' => array(
            'class' => array(),
        ),
    );
    echo wp_kses( '<div class="wpsummarize_dashboard_tokens">' . $tokens_text . '</div>', $allowed_html );
    echo '<table class="wp-list-table widefat fixed bookmarks" style="padding-left:0">
        <thead>
        <tr>
            <th><strong>' . esc_html__( 'Today', 'wpsummarize' ) . '</strong></th>
            <th><strong>' . esc_html__( 'Yesterday', 'wpsummarize' ) . '</strong></th>
            <th><strong>' . esc_html__( 'This Week', 'wpsummarize' ) . '</strong></th>
            <th><strong>' . esc_html__( 'This Month', 'wpsummarize' ) . '</strong></th>
            <th><strong>' . esc_html__( 'All Time', 'wpsummarize' ) . '</strong></th>        
        </tr>
    <thead>
     <tbody>
                        <tr>
                    <td>' . esc_html( $tokens_data['tokens_used_today'] ) . '</td>
                    <td>' . esc_html( $tokens_data['tokens_used_yesterday'] ) . '</td>
                    <td>' . esc_html( $tokens_data['tokens_used_week'] ) . '</td>
                    <td>' . esc_html( $tokens_data['tokens_used_month'] ) . '</td>
                    <td>' . esc_html( $tokens_data['tokens_used_all_time'] ) . '</td>        
                </tr>

            
    </tbody>
</table>';
    ?>

<h2 class="wpsummarize_settings_title subtitle"><?php 
    echo esc_html__( 'Latest summaries', 'wpsummarize' );
    ?></h2>
        <?php 
    // Query to get the last 15 summaries from postmeta
    $summaries = $wpdb->get_results( $wpdb->prepare( "\r\n    SELECT post_id, meta_value AS summary\r\n    FROM {$wpdb->postmeta} \r\n    WHERE meta_key = %s\r\n    ORDER BY meta_id DESC\r\n    LIMIT %d\r\n", '_wpsummarize_summary_set', 15 ) );
    // Start table
    echo '<table class="wp-list-table widefat fixed bookmarks" style="padding-left:0;margin-top: 20px;">
<tr>
<th style="width:20%"><strong>' . esc_html__( 'Post', 'wpsummarize' ) . '</strong></th>
<th><strong>' . esc_html__( 'Summary', 'wpsummarize' ) . '</strong></th>
</tr>';
    // Loop through the summaries and display them
    foreach ( $summaries as $summary ) {
        $post = get_post( $summary->post_id );
        // Retrieve the post object based on post_id
        $link = get_permalink( $summary->post_id );
        echo '<tr>
    <td><a href="' . esc_url( $link ) . '">' . esc_html( $post->post_title ) . '</a></td>
    <td>' . wp_kses_post( $summary->summary ) . '</td>
    </tr>';
    }
    // Close table
    echo '</table>';
    ?>
    </div>    
        
    <?php 
}

// Ensure this hooks into the admin_menu
add_action( 'admin_menu', 'wpsummarize_admin_menu' );
function wpsummarize_sanitize_svg(  $svg  ) {
    $allowed_tags = array(
        'svg'  => array(
            'xmlns'   => array(),
            'viewbox' => array(),
            'fill'    => array(),
        ),
        'path' => array(
            'd' => array(),
        ),
    );
    return wp_kses( $svg, $allowed_tags );
}
