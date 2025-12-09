<?php

namespace TrueQAP\FluentEmailPiping\Admin;

use TrueQAP\FluentEmailPiping\Webhook\Sender;

/**
 * Admin settings page
 */
class Settings {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'addMenuPage' ] );
        add_action( 'admin_init', [ $this, 'registerSettings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAssets' ] );
    }

    /**
     * Add admin menu page
     */
    public function addMenuPage(): void {
        add_submenu_page(
            'fluent-support',
            __( 'Email Piping', 'fluent-support-email-piping' ),
            __( 'Email Piping', 'fluent-support-email-piping' ),
            'manage_options',
            'fsep-settings',
            [ $this, 'renderSettingsPage' ]
        );
    }

    /**
     * Register settings
     */
    public function registerSettings(): void {
        register_setting( 'fsep_settings_group', 'fsep_settings', [
            'sanitize_callback' => [ $this, 'sanitizeSettings' ],
        ] );

        register_setting( 'fsep_accounts_group', 'fsep_email_accounts', [
            'sanitize_callback' => [ $this, 'sanitizeAccounts' ],
        ] );
    }

    /**
     * Sanitize general settings
     */
    public function sanitizeSettings( $input ): array {
        return [
            'check_interval'      => absint( $input['check_interval'] ?? 5 ),
            'delete_after_import' => ! empty( $input['delete_after_import'] ),
            'debug_mode'          => ! empty( $input['debug_mode'] ),
        ];
    }

    /**
     * Sanitize email accounts
     */
    public function sanitizeAccounts( $input ): array {
        if ( ! is_array( $input ) ) {
            return [];
        }

        $sanitized = [];

        foreach ( $input as $id => $account ) {
            $sanitized[ absint( $id ) ] = [
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
                'password'        => $account['password'] ?? '', // Don't sanitize password
                'folder'          => sanitize_text_field( $account['folder'] ?? 'INBOX' ),
                'mailbox_id'      => absint( $account['mailbox_id'] ?? 0 ),
                'enabled'         => ! empty( $account['enabled'] ),
                'fetch_limit'     => absint( $account['fetch_limit'] ?? 10 ),
                'fetch_read'      => ! empty( $account['fetch_read'] ),
                'novalidate_cert' => ! empty( $account['novalidate_cert'] ),
            ];
        }

        return $sanitized;
    }

    /**
     * Enqueue admin assets
     */
    public function enqueueAssets( string $hook ): void {
        if ( $hook !== 'fluent-support_page_fsep-settings' ) {
            return;
        }

        wp_enqueue_style(
            'fsep-admin',
            FSEP_PLUGIN_URL . 'assets/css/admin.css',
            [],
            FSEP_VERSION
        );

        wp_enqueue_script(
            'fsep-admin',
            FSEP_PLUGIN_URL . 'assets/js/admin.js',
            [ 'jquery' ],
            FSEP_VERSION,
            true
        );

        wp_localize_script( 'fsep-admin', 'fsepAdmin', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'fsep_admin_nonce' ),
            'i18n'    => [
                'confirmDelete'  => __( 'Are you sure you want to delete this account?', 'fluent-support-email-piping' ),
                'testing'        => __( 'Testing connection...', 'fluent-support-email-piping' ),
                'fetching'       => __( 'Fetching emails...', 'fluent-support-email-piping' ),
                'success'        => __( 'Success!', 'fluent-support-email-piping' ),
                'error'          => __( 'Error:', 'fluent-support-email-piping' ),
            ],
        ] );
    }

    /**
     * Render settings page
     */
    public function renderSettingsPage(): void {
        $activeTab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'accounts';
        $settings = get_option( 'fsep_settings', [] );
        $accounts = get_option( 'fsep_email_accounts', [] );
        $logs = get_option( 'fsep_email_logs', [] );
        $lastCheck = get_option( 'fsep_last_check', 0 );
        $mailboxes = Sender::getAvailableMailboxes();

        include FSEP_PLUGIN_PATH . 'templates/admin-settings.php';
    }

    /**
     * Handle account save via AJAX or form
     */
    public function handleSaveAccount(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Check nonce
        if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'fsep_save_account' ) ) {
            return;
        }

        $accounts = get_option( 'fsep_email_accounts', [] );
        $accountId = isset( $_POST['account_id'] ) ? absint( $_POST['account_id'] ) : 0;

        // Generate new ID if needed
        if ( ! $accountId ) {
            $accountId = empty( $accounts ) ? 1 : max( array_keys( $accounts ) ) + 1;
        }

        $accounts[ $accountId ] = $this->sanitizeAccounts( [ $accountId => $_POST['account'] ] )[ $accountId ];

        update_option( 'fsep_email_accounts', $accounts );

        wp_redirect( admin_url( 'admin.php?page=fsep-settings&tab=accounts&message=saved' ) );
        exit;
    }
}
