<?php
/**
 * Plugin Name: Email Piping for Fluent Support
 * Plugin URI: https://github.com/trueqap/email-piping-for-fluent-support
 * Description: Local IMAP/POP3 email piping for Fluent Support - GDPR compliant alternative that keeps your data on your server.
 * Version: 1.0.0
 * Author: TrueQAP
 * Author URI: https://github.com/trueqap
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: fluent-support-email-piping
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * Requires Plugins: fluent-support-pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'FSEP_VERSION', '1.0.0' );
define( 'FSEP_PLUGIN_FILE', __FILE__ );
define( 'FSEP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'FSEP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FSEP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Composer autoloader
if ( file_exists( FSEP_PLUGIN_PATH . 'vendor/autoload.php' ) ) {
    require_once FSEP_PLUGIN_PATH . 'vendor/autoload.php';
}

/**
 * Initialize the plugin
 */
function fsep_init() {
    // Check if Fluent Support is active
    if ( ! defined( 'FLUENT_SUPPORT_VERSION' ) ) {
        add_action( 'admin_notices', 'fsep_missing_fluent_support_notice' );
        return;
    }

    // Check if custom piping is enabled
    if ( ! defined( 'FLUENTSUPPORT_ENABLE_CUSTOM_PIPE' ) || ! FLUENTSUPPORT_ENABLE_CUSTOM_PIPE ) {
        add_action( 'admin_notices', 'fsep_custom_pipe_not_enabled_notice' );
    }

    // Boot the plugin
    \TrueQAP\FluentEmailPiping\Plugin::getInstance();
}
add_action( 'plugins_loaded', 'fsep_init', 20 );

/**
 * Admin notice: Fluent Support not active
 */
function fsep_missing_fluent_support_notice() {
    ?>
    <div class="notice notice-error">
        <p>
            <strong><?php esc_html_e( 'Email Piping for Fluent Support', 'fluent-support-email-piping' ); ?>:</strong>
            <?php esc_html_e( 'This plugin requires Fluent Support Pro to be installed and activated.', 'fluent-support-email-piping' ); ?>
        </p>
    </div>
    <?php
}

/**
 * Admin notice: Custom piping not enabled
 */
function fsep_custom_pipe_not_enabled_notice() {
    ?>
    <div class="notice notice-warning">
        <p>
            <strong><?php esc_html_e( 'Email Piping for Fluent Support', 'fluent-support-email-piping' ); ?>:</strong>
            <?php
            printf(
                /* translators: %s: code snippet */
                esc_html__( 'Please add %s to your wp-config.php to enable custom email piping.', 'fluent-support-email-piping' ),
                '<code>define(\'FLUENTSUPPORT_ENABLE_CUSTOM_PIPE\', true);</code>'
            );
            ?>
        </p>
    </div>
    <?php
}

/**
 * Activation hook
 */
function fsep_activate() {
    // Create default options
    if ( ! get_option( 'fsep_settings' ) ) {
        add_option( 'fsep_settings', [
            'check_interval' => 5, // minutes
            'delete_after_import' => false,
            'debug_mode' => false,
        ] );
    }

    // Schedule cron
    if ( ! wp_next_scheduled( 'fsep_check_emails' ) ) {
        wp_schedule_event( time(), 'fsep_interval', 'fsep_check_emails' );
    }
}
register_activation_hook( __FILE__, 'fsep_activate' );

/**
 * Deactivation hook
 */
function fsep_deactivate() {
    wp_clear_scheduled_hook( 'fsep_check_emails' );
}
register_deactivation_hook( __FILE__, 'fsep_deactivate' );

/**
 * Add custom cron interval
 */
function fsep_cron_schedules( $schedules ) {
    $settings = get_option( 'fsep_settings', [] );
    $interval = isset( $settings['check_interval'] ) ? absint( $settings['check_interval'] ) : 5;

    $schedules['fsep_interval'] = [
        'interval' => $interval * 60,
        'display'  => sprintf(
            /* translators: %d: number of minutes */
            __( 'Every %d minutes', 'fluent-support-email-piping' ),
            $interval
        ),
    ];

    return $schedules;
}
add_filter( 'cron_schedules', 'fsep_cron_schedules' );
