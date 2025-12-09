<?php

namespace TrueQAP\FluentEmailPiping;

use TrueQAP\FluentEmailPiping\Admin\Settings;
use TrueQAP\FluentEmailPiping\Cron\EmailChecker;

/**
 * Main plugin class - Singleton
 */
class Plugin {

    /**
     * @var Plugin|null
     */
    private static ?Plugin $instance = null;

    /**
     * @var Settings
     */
    private Settings $settings;

    /**
     * @var EmailChecker
     */
    private EmailChecker $emailChecker;

    /**
     * Get singleton instance
     */
    public static function getInstance(): Plugin {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Initialize plugin components
     */
    private function init(): void {
        // Admin settings
        $this->settings = new Settings();

        // Email checker (cron)
        $this->emailChecker = new EmailChecker();

        // Load text domain
        add_action( 'init', [ $this, 'loadTextDomain' ] );

        // AJAX handlers
        $this->registerAjaxHandlers();
    }

    /**
     * Load plugin text domain
     */
    public function loadTextDomain(): void {
        load_plugin_textdomain(
            'fluent-support-email-piping',
            false,
            dirname( FSEP_PLUGIN_BASENAME ) . '/languages'
        );
    }

    /**
     * Register AJAX handlers
     */
    private function registerAjaxHandlers(): void {
        add_action( 'wp_ajax_fsep_test_connection', [ $this, 'ajaxTestConnection' ] );
        add_action( 'wp_ajax_fsep_fetch_now', [ $this, 'ajaxFetchNow' ] );
        add_action( 'wp_ajax_fsep_delete_account', [ $this, 'ajaxDeleteAccount' ] );
        add_action( 'admin_post_fsep_save_account', [ $this, 'handleSaveAccount' ] );
    }

    /**
     * AJAX: Test email connection
     */
    public function ajaxTestConnection(): void {
        check_ajax_referer( 'fsep_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'fluent-support-email-piping' ) ] );
        }

        $account_id = isset( $_POST['account_id'] ) ? absint( $_POST['account_id'] ) : 0;

        if ( ! $account_id ) {
            wp_send_json_error( [ 'message' => __( 'Invalid account ID.', 'fluent-support-email-piping' ) ] );
        }

        $accounts = get_option( 'fsep_email_accounts', [] );

        if ( ! isset( $accounts[ $account_id ] ) ) {
            wp_send_json_error( [ 'message' => __( 'Account not found.', 'fluent-support-email-piping' ) ] );
        }

        $account = $accounts[ $account_id ];

        try {
            $fetcher = new Email\Fetcher( $account );
            $result = $fetcher->testConnection();

            if ( $result['success'] ) {
                wp_send_json_success( [
                    'message' => sprintf(
                        /* translators: %d: number of emails */
                        __( 'Connection successful! Found %d emails in inbox.', 'fluent-support-email-piping' ),
                        $result['count']
                    ),
                ] );
            } else {
                wp_send_json_error( [ 'message' => $result['message'] ] );
            }
        } catch ( \Exception $e ) {
            wp_send_json_error( [ 'message' => $e->getMessage() ] );
        }
    }

    /**
     * AJAX: Fetch emails now
     */
    public function ajaxFetchNow(): void {
        check_ajax_referer( 'fsep_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'fluent-support-email-piping' ) ] );
        }

        $account_id = isset( $_POST['account_id'] ) ? absint( $_POST['account_id'] ) : 0;

        try {
            $results = $this->emailChecker->checkEmails( $account_id ?: null );
            wp_send_json_success( [
                'message' => sprintf(
                    /* translators: %d: number of processed emails */
                    __( 'Processed %d emails.', 'fluent-support-email-piping' ),
                    $results['processed']
                ),
                'results' => $results,
            ] );
        } catch ( \Exception $e ) {
            wp_send_json_error( [ 'message' => $e->getMessage() ] );
        }
    }

    /**
     * AJAX: Delete account
     */
    public function ajaxDeleteAccount(): void {
        check_ajax_referer( 'fsep_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'fluent-support-email-piping' ) ] );
        }

        $account_id = isset( $_POST['account_id'] ) ? absint( $_POST['account_id'] ) : 0;

        if ( ! $account_id ) {
            wp_send_json_error( [ 'message' => __( 'Invalid account ID.', 'fluent-support-email-piping' ) ] );
        }

        $accounts = get_option( 'fsep_email_accounts', [] );

        if ( ! isset( $accounts[ $account_id ] ) ) {
            wp_send_json_error( [ 'message' => __( 'Account not found.', 'fluent-support-email-piping' ) ] );
        }

        unset( $accounts[ $account_id ] );
        update_option( 'fsep_email_accounts', $accounts );

        wp_send_json_success( [ 'message' => __( 'Account deleted.', 'fluent-support-email-piping' ) ] );
    }

    /**
     * Handle account save from form
     */
    public function handleSaveAccount(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Permission denied.', 'fluent-support-email-piping' ) );
        }

        check_admin_referer( 'fsep_save_account' );

        $accounts = get_option( 'fsep_email_accounts', [] );
        $account_id = isset( $_POST['account_id'] ) && $_POST['account_id'] !== ''
            ? absint( $_POST['account_id'] )
            : 0;

        // Generate new ID if needed
        if ( ! $account_id ) {
            $account_id = empty( $accounts ) ? 1 : max( array_keys( $accounts ) ) + 1;
        }

        $account = $_POST['account'] ?? [];

        $accounts[ $account_id ] = [
            'name'            => sanitize_text_field( $account['name'] ?? '' ),
            'host'            => sanitize_text_field( $account['host'] ?? '' ),
            'port'            => absint( $account['port'] ?? 993 ),
            'protocol'        => in_array( $account['protocol'] ?? 'imap', [ 'imap', 'pop3' ], true )
                ? $account['protocol']
                : 'imap',
            'encryption'      => in_array( $account['encryption'] ?? 'ssl', [ 'ssl', 'tls', 'none' ], true )
                ? $account['encryption']
                : 'ssl',
            'username'        => sanitize_text_field( $account['username'] ?? '' ),
            'password'        => $account['password'] ?? '',
            'folder'          => sanitize_text_field( $account['folder'] ?? 'INBOX' ),
            'mailbox_id'      => absint( $account['mailbox_id'] ?? 0 ),
            'enabled'         => ! empty( $account['enabled'] ),
            'fetch_limit'     => absint( $account['fetch_limit'] ?? 10 ),
            'fetch_read'      => ! empty( $account['fetch_read'] ),
            'novalidate_cert' => ! empty( $account['novalidate_cert'] ),
        ];

        update_option( 'fsep_email_accounts', $accounts );

        wp_redirect( admin_url( 'admin.php?page=fsep-settings&tab=accounts&message=saved' ) );
        exit;
    }

    /**
     * Get settings instance
     */
    public function getSettings(): Settings {
        return $this->settings;
    }

    /**
     * Get email checker instance
     */
    public function getEmailChecker(): EmailChecker {
        return $this->emailChecker;
    }
}
