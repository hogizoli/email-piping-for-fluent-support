<?php
/**
 * Admin settings template
 *
 * @var string $activeTab
 * @var array $settings
 * @var array $accounts
 * @var array $logs
 * @var int $lastCheck
 * @var array $mailboxes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap fsep-settings">
    <h1><?php esc_html_e( 'Email Piping for Fluent Support', 'fluent-support-email-piping' ); ?></h1>

    <?php if ( isset( $_GET['message'] ) && $_GET['message'] === 'saved' ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'Settings saved successfully.', 'fluent-support-email-piping' ); ?></p>
        </div>
    <?php endif; ?>

    <nav class="nav-tab-wrapper">
        <a href="?page=fsep-settings&tab=accounts" class="nav-tab <?php echo $activeTab === 'accounts' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Email Accounts', 'fluent-support-email-piping' ); ?>
        </a>
        <a href="?page=fsep-settings&tab=settings" class="nav-tab <?php echo $activeTab === 'settings' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Settings', 'fluent-support-email-piping' ); ?>
        </a>
        <a href="?page=fsep-settings&tab=logs" class="nav-tab <?php echo $activeTab === 'logs' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Logs', 'fluent-support-email-piping' ); ?>
        </a>
    </nav>

    <div class="tab-content">
        <?php if ( $activeTab === 'accounts' ) : ?>
            <!-- Email Accounts Tab -->
            <div class="fsep-accounts-section">
                <h2><?php esc_html_e( 'Email Accounts', 'fluent-support-email-piping' ); ?></h2>
                <p class="description">
                    <?php esc_html_e( 'Configure IMAP/POP3 accounts to fetch emails and pipe them to Fluent Support Business Inboxes.', 'fluent-support-email-piping' ); ?>
                </p>

                <?php if ( empty( $mailboxes ) ) : ?>
                    <div class="notice notice-warning inline">
                        <p>
                            <?php esc_html_e( 'No email-type Business Inboxes found in Fluent Support. Please create one first.', 'fluent-support-email-piping' ); ?>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=fluent-support#/settings/business-inbox' ) ); ?>">
                                <?php esc_html_e( 'Create Business Inbox', 'fluent-support-email-piping' ); ?>
                            </a>
                        </p>
                    </div>
                <?php endif; ?>

                <!-- Existing Accounts -->
                <?php if ( ! empty( $accounts ) ) : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Name', 'fluent-support-email-piping' ); ?></th>
                                <th><?php esc_html_e( 'Server', 'fluent-support-email-piping' ); ?></th>
                                <th><?php esc_html_e( 'Business Inbox', 'fluent-support-email-piping' ); ?></th>
                                <th><?php esc_html_e( 'Status', 'fluent-support-email-piping' ); ?></th>
                                <th><?php esc_html_e( 'Actions', 'fluent-support-email-piping' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $accounts as $accountId => $account ) : ?>
                                <?php
                                $mailboxName = '';
                                foreach ( $mailboxes as $mailbox ) {
                                    if ( $mailbox['id'] == ( $account['mailbox_id'] ?? 0 ) ) {
                                        $mailboxName = $mailbox['name'];
                                        break;
                                    }
                                }
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html( $account['name'] ?? __( 'Unnamed', 'fluent-support-email-piping' ) ); ?></strong>
                                        <br>
                                        <small><?php echo esc_html( $account['username'] ?? '' ); ?></small>
                                    </td>
                                    <td>
                                        <?php echo esc_html( $account['host'] ?? '' ); ?>:<?php echo esc_html( $account['port'] ?? 993 ); ?>
                                        <br>
                                        <small><?php echo esc_html( strtoupper( $account['protocol'] ?? 'IMAP' ) ); ?> / <?php echo esc_html( strtoupper( $account['encryption'] ?? 'SSL' ) ); ?></small>
                                    </td>
                                    <td><?php echo esc_html( $mailboxName ?: __( 'Not assigned', 'fluent-support-email-piping' ) ); ?></td>
                                    <td>
                                        <?php if ( ! empty( $account['enabled'] ) ) : ?>
                                            <span class="fsep-status fsep-status-active"><?php esc_html_e( 'Active', 'fluent-support-email-piping' ); ?></span>
                                        <?php else : ?>
                                            <span class="fsep-status fsep-status-inactive"><?php esc_html_e( 'Inactive', 'fluent-support-email-piping' ); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="button fsep-edit-account" data-account-id="<?php echo esc_attr( $accountId ); ?>">
                                            <?php esc_html_e( 'Edit', 'fluent-support-email-piping' ); ?>
                                        </button>
                                        <button type="button" class="button fsep-test-connection" data-account-id="<?php echo esc_attr( $accountId ); ?>">
                                            <?php esc_html_e( 'Test', 'fluent-support-email-piping' ); ?>
                                        </button>
                                        <button type="button" class="button fsep-fetch-now" data-account-id="<?php echo esc_attr( $accountId ); ?>">
                                            <?php esc_html_e( 'Fetch Now', 'fluent-support-email-piping' ); ?>
                                        </button>
                                        <button type="button" class="button button-link-delete fsep-delete-account" data-account-id="<?php echo esc_attr( $accountId ); ?>">
                                            <?php esc_html_e( 'Delete', 'fluent-support-email-piping' ); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <!-- Add/Edit Account Form -->
                <div class="fsep-account-form-wrapper">
                    <h3 id="fsep-form-title"><?php esc_html_e( 'Add New Email Account', 'fluent-support-email-piping' ); ?></h3>

                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="fsep-account-form">
                        <?php wp_nonce_field( 'fsep_save_account' ); ?>
                        <input type="hidden" name="action" value="fsep_save_account">
                        <input type="hidden" name="account_id" id="fsep-account-id" value="">

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="account-name"><?php esc_html_e( 'Account Name', 'fluent-support-email-piping' ); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="account[name]" id="account-name" class="regular-text" required>
                                    <p class="description"><?php esc_html_e( 'A friendly name for this account', 'fluent-support-email-piping' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="account-host"><?php esc_html_e( 'Mail Server', 'fluent-support-email-piping' ); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="account[host]" id="account-host" class="regular-text" placeholder="imap.gmail.com" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="account-port"><?php esc_html_e( 'Port', 'fluent-support-email-piping' ); ?></label>
                                </th>
                                <td>
                                    <input type="number" name="account[port]" id="account-port" class="small-text" value="993" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="account-protocol"><?php esc_html_e( 'Protocol', 'fluent-support-email-piping' ); ?></label>
                                </th>
                                <td>
                                    <?php $hasImapExtension = extension_loaded( 'imap' ); ?>
                                    <select name="account[protocol]" id="account-protocol">
                                        <option value="imap">IMAP</option>
                                        <option value="pop3" <?php echo ! $hasImapExtension ? 'disabled' : ''; ?>>
                                            POP3 <?php echo ! $hasImapExtension ? __( '(requires ext-imap)', 'fluent-support-email-piping' ) : ''; ?>
                                        </option>
                                    </select>
                                    <?php if ( ! $hasImapExtension ) : ?>
                                        <p class="description">
                                            <?php esc_html_e( 'POP3 requires the PHP IMAP extension. IMAP protocol works without it.', 'fluent-support-email-piping' ); ?>
                                        </p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="account-encryption"><?php esc_html_e( 'Encryption', 'fluent-support-email-piping' ); ?></label>
                                </th>
                                <td>
                                    <select name="account[encryption]" id="account-encryption">
                                        <option value="ssl">SSL</option>
                                        <option value="tls">TLS</option>
                                        <option value="none"><?php esc_html_e( 'None', 'fluent-support-email-piping' ); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="account-username"><?php esc_html_e( 'Username / Email', 'fluent-support-email-piping' ); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="account[username]" id="account-username" class="regular-text" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="account-password"><?php esc_html_e( 'Password / App Password', 'fluent-support-email-piping' ); ?></label>
                                </th>
                                <td>
                                    <input type="password" name="account[password]" id="account-password" class="regular-text" required>
                                    <p class="description"><?php esc_html_e( 'For Gmail, use an App Password instead of your regular password.', 'fluent-support-email-piping' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="account-folder"><?php esc_html_e( 'Folder', 'fluent-support-email-piping' ); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="account[folder]" id="account-folder" class="regular-text" value="INBOX">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="account-mailbox"><?php esc_html_e( 'Fluent Support Inbox', 'fluent-support-email-piping' ); ?></label>
                                </th>
                                <td>
                                    <select name="account[mailbox_id]" id="account-mailbox" required>
                                        <option value=""><?php esc_html_e( '-- Select Business Inbox --', 'fluent-support-email-piping' ); ?></option>
                                        <?php foreach ( $mailboxes as $mailbox ) : ?>
                                            <option value="<?php echo esc_attr( $mailbox['id'] ); ?>">
                                                <?php echo esc_html( $mailbox['name'] ); ?> (<?php echo esc_html( $mailbox['email'] ); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="account-fetch-limit"><?php esc_html_e( 'Fetch Limit', 'fluent-support-email-piping' ); ?></label>
                                </th>
                                <td>
                                    <input type="number" name="account[fetch_limit]" id="account-fetch-limit" class="small-text" value="10" min="1" max="50">
                                    <p class="description"><?php esc_html_e( 'Maximum emails to fetch per check', 'fluent-support-email-piping' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Options', 'fluent-support-email-piping' ); ?></th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" name="account[enabled]" id="account-enabled" value="1" checked>
                                            <?php esc_html_e( 'Enable this account', 'fluent-support-email-piping' ); ?>
                                        </label>
                                        <br>
                                        <label>
                                            <input type="checkbox" name="account[fetch_read]" id="account-fetch-read" value="1">
                                            <?php esc_html_e( 'Also fetch already read emails', 'fluent-support-email-piping' ); ?>
                                        </label>
                                        <br>
                                        <label>
                                            <input type="checkbox" name="account[novalidate_cert]" id="account-novalidate-cert" value="1">
                                            <?php esc_html_e( 'Skip SSL certificate validation (not recommended)', 'fluent-support-email-piping' ); ?>
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>
                        </table>

                        <p class="submit">
                            <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Account', 'fluent-support-email-piping' ); ?></button>
                            <button type="button" class="button fsep-cancel-edit" style="display: none;"><?php esc_html_e( 'Cancel', 'fluent-support-email-piping' ); ?></button>
                        </p>
                    </form>
                </div>

                <!-- Account data for JS -->
                <script type="application/json" id="fsep-accounts-data">
                    <?php echo wp_json_encode( $accounts ); ?>
                </script>
            </div>

        <?php elseif ( $activeTab === 'settings' ) : ?>
            <!-- General Settings Tab -->
            <form method="post" action="options.php">
                <?php settings_fields( 'fsep_settings_group' ); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="check-interval"><?php esc_html_e( 'Check Interval', 'fluent-support-email-piping' ); ?></label>
                        </th>
                        <td>
                            <input type="number" name="fsep_settings[check_interval]" id="check-interval" class="small-text" value="<?php echo esc_attr( $settings['check_interval'] ?? 5 ); ?>" min="1" max="60">
                            <?php esc_html_e( 'minutes', 'fluent-support-email-piping' ); ?>
                            <p class="description"><?php esc_html_e( 'How often to check for new emails (requires cron to be working)', 'fluent-support-email-piping' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'After Import', 'fluent-support-email-piping' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="fsep_settings[delete_after_import]" value="1" <?php checked( ! empty( $settings['delete_after_import'] ) ); ?>>
                                <?php esc_html_e( 'Delete emails from server after successful import', 'fluent-support-email-piping' ); ?>
                            </label>
                            <p class="description"><?php esc_html_e( 'If unchecked, emails will be marked as read instead', 'fluent-support-email-piping' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Debug Mode', 'fluent-support-email-piping' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="fsep_settings[debug_mode]" value="1" <?php checked( ! empty( $settings['debug_mode'] ) ); ?>>
                                <?php esc_html_e( 'Enable debug logging', 'fluent-support-email-piping' ); ?>
                            </label>
                            <p class="description"><?php esc_html_e( 'Logs will be written to wp-content/debug.log', 'fluent-support-email-piping' ); ?></p>
                        </td>
                    </tr>
                </table>

                <h3><?php esc_html_e( 'Status', 'fluent-support-email-piping' ); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Last Check', 'fluent-support-email-piping' ); ?></th>
                        <td>
                            <?php if ( $lastCheck ) : ?>
                                <?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $lastCheck ) ); ?>
                            <?php else : ?>
                                <?php esc_html_e( 'Never', 'fluent-support-email-piping' ); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Next Scheduled Check', 'fluent-support-email-piping' ); ?></th>
                        <td>
                            <?php
                            $nextScheduled = wp_next_scheduled( 'fsep_check_emails' );
                            if ( $nextScheduled ) :
                                echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $nextScheduled ) );
                            else :
                                esc_html_e( 'Not scheduled', 'fluent-support-email-piping' );
                            endif;
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'PHP IMAP Extension', 'fluent-support-email-piping' ); ?></th>
                        <td>
                            <?php if ( extension_loaded( 'imap' ) ) : ?>
                                <span class="fsep-status fsep-status-active"><?php esc_html_e( 'Installed', 'fluent-support-email-piping' ); ?></span>
                                <p class="description"><?php esc_html_e( 'Both IMAP and POP3 protocols are available.', 'fluent-support-email-piping' ); ?></p>
                            <?php else : ?>
                                <span class="fsep-status fsep-status-inactive"><?php esc_html_e( 'Not installed', 'fluent-support-email-piping' ); ?></span>
                                <p class="description"><?php esc_html_e( 'IMAP protocol works without ext-imap. POP3 requires the extension.', 'fluent-support-email-piping' ); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

        <?php elseif ( $activeTab === 'logs' ) : ?>
            <!-- Logs Tab -->
            <h2><?php esc_html_e( 'Email Processing Logs', 'fluent-support-email-piping' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Last 100 processed emails', 'fluent-support-email-piping' ); ?></p>

            <?php if ( empty( $logs ) ) : ?>
                <p><?php esc_html_e( 'No emails have been processed yet.', 'fluent-support-email-piping' ); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Time', 'fluent-support-email-piping' ); ?></th>
                            <th><?php esc_html_e( 'From', 'fluent-support-email-piping' ); ?></th>
                            <th><?php esc_html_e( 'Subject', 'fluent-support-email-piping' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'fluent-support-email-piping' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( array_reverse( $logs ) as $log ) : ?>
                            <tr>
                                <td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $log['timestamp'] ) ); ?></td>
                                <td><?php echo esc_html( $log['from'] ); ?></td>
                                <td><?php echo esc_html( $log['subject'] ); ?></td>
                                <td>
                                    <?php if ( $log['status'] === 'success' ) : ?>
                                        <span class="fsep-status fsep-status-active"><?php esc_html_e( 'Success', 'fluent-support-email-piping' ); ?></span>
                                    <?php else : ?>
                                        <span class="fsep-status fsep-status-inactive"><?php esc_html_e( 'Failed', 'fluent-support-email-piping' ); ?></span>
                                        <?php if ( ! empty( $log['error_message'] ) ) : ?>
                                            <br><small><?php echo esc_html( $log['error_message'] ); ?></small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
