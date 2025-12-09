<?php

namespace TrueQAP\FluentEmailPiping\Cron;

use TrueQAP\FluentEmailPiping\Email\Fetcher;
use TrueQAP\FluentEmailPiping\Webhook\Sender;

/**
 * Cron handler for checking emails periodically
 */
class EmailChecker {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'fsep_check_emails', [ $this, 'checkAllAccounts' ] );
    }

    /**
     * Check all configured email accounts
     *
     * @return array Results summary
     */
    public function checkAllAccounts(): array {
        $accounts = get_option( 'fsep_email_accounts', [] );
        $settings = get_option( 'fsep_settings', [] );

        $results = [
            'total_accounts'  => count( $accounts ),
            'total_processed' => 0,
            'total_success'   => 0,
            'total_failed'    => 0,
            'accounts'        => [],
        ];

        foreach ( $accounts as $accountId => $account ) {
            if ( empty( $account['enabled'] ) ) {
                continue;
            }

            $accountResult = $this->checkAccount( $accountId, $account, $settings );
            $results['accounts'][ $accountId ] = $accountResult;
            $results['total_processed'] += $accountResult['processed'];
            $results['total_success'] += $accountResult['success'];
            $results['total_failed'] += $accountResult['failed'];
        }

        // Update last check time
        update_option( 'fsep_last_check', current_time( 'timestamp' ) );

        // Log results if debug mode
        if ( ! empty( $settings['debug_mode'] ) ) {
            error_log( sprintf(
                '[Fluent Support Email Piping] Checked %d accounts, processed %d emails (%d success, %d failed)',
                $results['total_accounts'],
                $results['total_processed'],
                $results['total_success'],
                $results['total_failed']
            ) );
        }

        return $results;
    }

    /**
     * Check emails for a specific account (or all if null)
     *
     * @param int|null $accountId Specific account ID or null for all
     * @return array Results
     */
    public function checkEmails( ?int $accountId = null ): array {
        if ( $accountId !== null ) {
            $accounts = get_option( 'fsep_email_accounts', [] );
            $settings = get_option( 'fsep_settings', [] );

            if ( ! isset( $accounts[ $accountId ] ) ) {
                return [
                    'processed' => 0,
                    'success'   => 0,
                    'failed'    => 0,
                    'error'     => __( 'Account not found', 'fluent-support-email-piping' ),
                ];
            }

            return $this->checkAccount( $accountId, $accounts[ $accountId ], $settings );
        }

        return $this->checkAllAccounts();
    }

    /**
     * Check a single email account
     *
     * @param int $accountId
     * @param array $account
     * @param array $settings
     * @return array Results
     */
    private function checkAccount( int $accountId, array $account, array $settings ): array {
        $result = [
            'processed' => 0,
            'success'   => 0,
            'failed'    => 0,
            'errors'    => [],
        ];

        // Validate account config
        if ( empty( $account['host'] ) || empty( $account['username'] ) || empty( $account['password'] ) ) {
            $result['errors'][] = __( 'Incomplete account configuration', 'fluent-support-email-piping' );
            return $result;
        }

        // Get mailbox ID and webhook URL
        $mailboxId = $account['mailbox_id'] ?? 0;

        if ( ! $mailboxId ) {
            $result['errors'][] = __( 'No Fluent Support mailbox assigned', 'fluent-support-email-piping' );
            return $result;
        }

        $webhookUrl = Sender::getWebhookUrlForMailbox( $mailboxId );

        if ( ! $webhookUrl ) {
            $result['errors'][] = __( 'Could not get webhook URL for mailbox', 'fluent-support-email-piping' );
            return $result;
        }

        try {
            // Fetch emails
            $fetcher = new Fetcher( $account );
            $limit = $account['fetch_limit'] ?? 10;
            $emails = $fetcher->fetchEmails( $limit );

            if ( empty( $emails ) ) {
                return $result;
            }

            // Send each email to webhook
            $sender = new Sender( $webhookUrl );
            $deleteAfter = ! empty( $settings['delete_after_import'] );

            foreach ( $emails as $email ) {
                $result['processed']++;

                $sendResult = $sender->send( $email );

                if ( $sendResult['success'] ) {
                    $result['success']++;

                    // Mark as read or delete
                    if ( $deleteAfter ) {
                        $fetcher->deleteEmail( $email['mail_id'] );
                    } else {
                        $fetcher->markAsRead( $email['mail_id'] );
                    }

                    // Log success
                    $this->logProcessedEmail( $accountId, $email, 'success' );
                } else {
                    $result['failed']++;
                    $result['errors'][] = sprintf(
                        /* translators: 1: email subject, 2: error message */
                        __( 'Failed to process "%1$s": %2$s', 'fluent-support-email-piping' ),
                        $email['subject'] ?? 'Unknown',
                        $sendResult['message'] ?? 'Unknown error'
                    );

                    // Log failure
                    $this->logProcessedEmail( $accountId, $email, 'failed', $sendResult['message'] ?? '' );
                }
            }

            $fetcher->disconnect();

        } catch ( \Exception $e ) {
            $result['errors'][] = $e->getMessage();

            if ( ! empty( $settings['debug_mode'] ) ) {
                error_log( '[Fluent Support Email Piping] Error: ' . $e->getMessage() );
            }
        }

        return $result;
    }

    /**
     * Log processed email for history
     *
     * @param int $accountId
     * @param array $email
     * @param string $status
     * @param string $errorMessage
     */
    private function logProcessedEmail( int $accountId, array $email, string $status, string $errorMessage = '' ): void {
        $logs = get_option( 'fsep_email_logs', [] );

        // Keep only last 100 entries
        if ( count( $logs ) >= 100 ) {
            $logs = array_slice( $logs, -99 );
        }

        $logs[] = [
            'timestamp'     => current_time( 'timestamp' ),
            'account_id'    => $accountId,
            'message_id'    => $email['message_id'] ?? '',
            'subject'       => $email['subject'] ?? '',
            'from'          => $email['from']['value'][0]['address'] ?? '',
            'status'        => $status,
            'error_message' => $errorMessage,
        ];

        update_option( 'fsep_email_logs', $logs );
    }
}
