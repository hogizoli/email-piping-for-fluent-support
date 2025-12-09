/**
 * Fluent Support Email Piping - Admin JavaScript
 */
(function($) {
    'use strict';

    var FSEP = {
        init: function() {
            this.bindEvents();
            this.loadAccountsData();
        },

        accountsData: {},

        loadAccountsData: function() {
            var dataEl = $('#fsep-accounts-data');
            if (dataEl.length) {
                try {
                    this.accountsData = JSON.parse(dataEl.text());
                } catch (e) {
                    console.error('Failed to parse accounts data:', e);
                }
            }
        },

        bindEvents: function() {
            // Edit account
            $(document).on('click', '.fsep-edit-account', this.handleEditAccount.bind(this));

            // Delete account
            $(document).on('click', '.fsep-delete-account', this.handleDeleteAccount.bind(this));

            // Test connection
            $(document).on('click', '.fsep-test-connection', this.handleTestConnection.bind(this));

            // Fetch now
            $(document).on('click', '.fsep-fetch-now', this.handleFetchNow.bind(this));

            // Cancel edit
            $(document).on('click', '.fsep-cancel-edit', this.handleCancelEdit.bind(this));

            // Protocol change - update port
            $('#account-protocol').on('change', this.handleProtocolChange.bind(this));

            // Encryption change - update port
            $('#account-encryption').on('change', this.handleEncryptionChange.bind(this));
        },

        handleEditAccount: function(e) {
            e.preventDefault();
            var accountId = $(e.currentTarget).data('account-id');
            var account = this.accountsData[accountId];

            if (!account) {
                alert('Account not found');
                return;
            }

            // Populate form
            $('#fsep-account-id').val(accountId);
            $('#account-name').val(account.name || '');
            $('#account-host').val(account.host || '');
            $('#account-port').val(account.port || 993);
            $('#account-protocol').val(account.protocol || 'imap');
            $('#account-encryption').val(account.encryption || 'ssl');
            $('#account-username').val(account.username || '');
            $('#account-password').val(account.password || '');
            $('#account-folder').val(account.folder || 'INBOX');
            $('#account-mailbox').val(account.mailbox_id || '');
            $('#account-fetch-limit').val(account.fetch_limit || 10);
            $('#account-enabled').prop('checked', !!account.enabled);
            $('#account-fetch-read').prop('checked', !!account.fetch_read);
            $('#account-novalidate-cert').prop('checked', !!account.novalidate_cert);

            // Update form title
            $('#fsep-form-title').text(fsepAdmin.i18n.editAccount || 'Edit Email Account');

            // Show cancel button
            $('.fsep-cancel-edit').show();

            // Scroll to form
            $('html, body').animate({
                scrollTop: $('.fsep-account-form-wrapper').offset().top - 50
            }, 300);
        },

        handleDeleteAccount: function(e) {
            e.preventDefault();

            if (!confirm(fsepAdmin.i18n.confirmDelete)) {
                return;
            }

            var accountId = $(e.currentTarget).data('account-id');
            var accounts = $.extend({}, this.accountsData);
            delete accounts[accountId];

            // Save via hidden form or AJAX
            $.post(fsepAdmin.ajaxUrl, {
                action: 'fsep_delete_account',
                nonce: fsepAdmin.nonce,
                account_id: accountId
            }).done(function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || 'Failed to delete account');
                }
            }).fail(function() {
                alert('Request failed');
            });
        },

        handleTestConnection: function(e) {
            e.preventDefault();
            var $button = $(e.currentTarget);
            var accountId = $button.data('account-id');
            var originalText = $button.text();

            $button.prop('disabled', true).text(fsepAdmin.i18n.testing);

            $.post(fsepAdmin.ajaxUrl, {
                action: 'fsep_test_connection',
                nonce: fsepAdmin.nonce,
                account_id: accountId
            }).done(function(response) {
                if (response.success) {
                    alert(fsepAdmin.i18n.success + ' ' + response.data.message);
                } else {
                    alert(fsepAdmin.i18n.error + ' ' + response.data.message);
                }
            }).fail(function() {
                alert(fsepAdmin.i18n.error + ' Request failed');
            }).always(function() {
                $button.prop('disabled', false).text(originalText);
            });
        },

        handleFetchNow: function(e) {
            e.preventDefault();
            var $button = $(e.currentTarget);
            var accountId = $button.data('account-id');
            var originalText = $button.text();

            $button.prop('disabled', true).text(fsepAdmin.i18n.fetching);

            $.post(fsepAdmin.ajaxUrl, {
                action: 'fsep_fetch_now',
                nonce: fsepAdmin.nonce,
                account_id: accountId
            }).done(function(response) {
                if (response.success) {
                    alert(fsepAdmin.i18n.success + ' ' + response.data.message);
                    // Reload to show updated logs
                    if (response.data.results && response.data.results.processed > 0) {
                        location.reload();
                    }
                } else {
                    alert(fsepAdmin.i18n.error + ' ' + response.data.message);
                }
            }).fail(function() {
                alert(fsepAdmin.i18n.error + ' Request failed');
            }).always(function() {
                $button.prop('disabled', false).text(originalText);
            });
        },

        handleCancelEdit: function(e) {
            e.preventDefault();

            // Reset form
            $('#fsep-account-form')[0].reset();
            $('#fsep-account-id').val('');
            $('#fsep-form-title').text('Add New Email Account');
            $('.fsep-cancel-edit').hide();

            // Reset default values
            $('#account-port').val(993);
            $('#account-folder').val('INBOX');
            $('#account-fetch-limit').val(10);
            $('#account-enabled').prop('checked', true);
        },

        handleProtocolChange: function(e) {
            var protocol = $(e.currentTarget).val();
            var encryption = $('#account-encryption').val();

            if (protocol === 'pop3') {
                $('#account-port').val(encryption === 'ssl' ? 995 : 110);
            } else {
                $('#account-port').val(encryption === 'ssl' ? 993 : 143);
            }
        },

        handleEncryptionChange: function(e) {
            var encryption = $(e.currentTarget).val();
            var protocol = $('#account-protocol').val();

            if (protocol === 'pop3') {
                $('#account-port').val(encryption === 'ssl' ? 995 : 110);
            } else {
                $('#account-port').val(encryption === 'ssl' ? 993 : 143);
            }
        }
    };

    $(document).ready(function() {
        FSEP.init();
    });

})(jQuery);
