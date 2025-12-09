# Email Piping for Fluent Support

Local IMAP/POP3 email piping for Fluent Support - a GDPR compliant alternative that keeps your email data on your own server.

## Why This Plugin?

Fluent Support Pro's built-in email piping feature routes your emails through WPManageNinja's external servers. This means:

- **Your emails pass through third-party servers** - Every customer email is processed by external infrastructure
- **Privacy concerns** - A third party has access to all your support emails
- **GDPR compliance issues** - For EU businesses, routing customer data through external servers may violate data protection regulations

**This plugin solves these problems** by fetching emails directly from your mail server using IMAP/POP3, keeping all data on your own infrastructure.

## Features

- **Direct IMAP connection** - Fetches emails directly from your mail server
- **POP3 support** - Available when PHP IMAP extension is installed
- **Multiple accounts** - Configure different email accounts for different Business Inboxes
- **No external dependencies** - Works without the php-imap extension (for IMAP protocol)
- **Automatic fetching** - Configurable cron interval for checking new emails
- **Attachment support** - Handles email attachments
- **Forwarded email detection** - Extracts original sender from forwarded emails
- **Debug logging** - Optional logging for troubleshooting

## Requirements

- WordPress 5.6+
- PHP 7.4+
- Fluent Support Pro
- `FLUENTSUPPORT_ENABLE_CUSTOM_PIPE` constant enabled

## Installation

1. Download the latest release from [GitHub](https://github.com/trueqap/email-piping-for-fluent-support/releases)
2. Upload the plugin folder to `/wp-content/plugins/`
3. Add to your `wp-config.php`:
   ```php
   define('FLUENTSUPPORT_ENABLE_CUSTOM_PIPE', true);
   ```
4. Activate the plugin
5. Go to **Fluent Support → Email Piping** to configure your email accounts

> **Note:** All dependencies are included. No `composer install` required.

## Configuration

### Adding an Email Account

1. Navigate to **Fluent Support → Email Piping → Email Accounts**
2. Fill in your mail server details:
   - **Mail Server**: Your IMAP server (e.g., `imap.gmail.com`, `mail.yourdomain.com`)
   - **Port**: Usually 993 for IMAP with SSL
   - **Protocol**: IMAP (recommended) or POP3
   - **Encryption**: SSL (recommended), TLS, or None
   - **Username/Password**: Your email credentials
   - **Fluent Support Inbox**: Select which Business Inbox should receive the emails
3. Click **Test** to verify the connection
4. Save the account

### Gmail App Password

If using Gmail, you need to create an App Password:

1. Enable 2-Factor Authentication on your Google account
2. Go to [Google App Passwords](https://myaccount.google.com/apppasswords)
3. Generate a new App Password for "Mail"
4. Use this password instead of your regular Gmail password

## Protocol Support

| Protocol | PHP ext-imap Required | Notes |
|----------|----------------------|-------|
| IMAP | No | Recommended - uses native PHP sockets |
| POP3 | Yes | Requires php-imap extension |

## GDPR Compliance

This plugin was specifically created to address GDPR concerns with Fluent Support's default email piping:

### The Problem

Fluent Support Pro's built-in email piping works by:
1. You configure an email forwarding rule to send emails to `*@pipes.fluentsupport.com`
2. WPManageNinja's servers receive and process your emails
3. The processed email is then sent to your WordPress site

This means **all your customer support emails are processed by a third party**, which raises significant privacy and compliance concerns for businesses operating under GDPR.

### The Solution

With this plugin:
1. Emails stay in your mailbox
2. Your WordPress server connects directly to your mail server via IMAP
3. Emails are fetched and processed entirely on your own infrastructure
4. **No third party ever sees your customer emails**

## Frequently Asked Questions

### Does this replace Fluent Support's email piping?

Yes, this is an alternative to the built-in piping feature. You should disable any email forwarding to `pipes.fluentsupport.com` when using this plugin.

### Why is POP3 disabled on my server?

POP3 requires the PHP IMAP extension (`ext-imap`). IMAP protocol works without it using native PHP sockets. Most mail servers support both protocols, so we recommend using IMAP.

### How often are emails checked?

By default, every 5 minutes. You can adjust this in **Settings → Check Interval**. Note that this depends on WordPress cron being functional.

### Are attachments supported?

Yes, email attachments are downloaded and stored in `/wp-content/uploads/fsep-attachments/`.

## Changelog

### 1.0.0
- Initial release
- IMAP support without ext-imap requirement
- POP3 support with ext-imap
- Multiple account support
- Automatic email fetching via WP-Cron
- Debug logging

## License

GPL-2.0-or-later

## Credits

- Uses [webklex/php-imap](https://github.com/Webklex/php-imap) for IMAP communication
- Developed by [TrueQAP](https://github.com/trueqap/email-piping-for-fluent-support)

## Disclaimer

This is an **unofficial plugin** and is **not affiliated with, endorsed by, or connected to WPManageNinja or Fluent Support** in any way. "Fluent Support" is a trademark of WPManageNinja. This plugin is an independent, third-party solution created to address privacy concerns with the official email piping feature.
