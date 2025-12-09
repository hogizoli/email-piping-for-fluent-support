=== Email Piping for Fluent Support ===
Contributors: trueqap
Tags: fluent support, email piping, imap, pop3, gdpr, helpdesk, support tickets
Requires at least: 5.6
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Local IMAP/POP3 email piping for Fluent Support - a GDPR compliant alternative that keeps your email data on your own server.

== Description ==

**Keep your customer emails private and GDPR compliant!**

Fluent Support Pro's built-in email piping routes your emails through WPManageNinja's external servers at `pipes.fluentsupport.com`. This means a third party has access to all your customer support emails - a significant privacy and GDPR compliance concern.

**Email Piping for Fluent Support** solves this by connecting directly to your mail server via IMAP/POP3, keeping all email data on your own infrastructure.

= The Problem with Default Email Piping =

When using Fluent Support's built-in email piping:

* Your emails are forwarded to external servers
* A third party processes and has access to all your customer emails
* For EU businesses, this may violate GDPR regulations
* You have no control over how your data is handled

= The Solution =

With this plugin:

* Emails stay in your mailbox until fetched
* Your WordPress server connects directly to your mail server
* All processing happens on your own infrastructure
* **No third party ever sees your customer emails**

= Features =

* **Direct IMAP/POP3 connection** - No external servers involved
* **Multiple email accounts** - Different accounts for different Business Inboxes
* **No ext-imap required** - IMAP works with native PHP sockets
* **Automatic fetching** - Configurable cron interval
* **Attachment support** - Handles email attachments
* **Debug logging** - Optional logging for troubleshooting

= Requirements =

* Fluent Support Pro
* PHP 7.4 or higher
* IMAP or POP3 access to your email server

== Installation ==

1. Download the latest release from GitHub
2. Upload the plugin folder to `/wp-content/plugins/`
3. Add to your `wp-config.php`:
   `define('FLUENTSUPPORT_ENABLE_CUSTOM_PIPE', true);`
4. Activate the plugin through the 'Plugins' menu
5. Go to **Fluent Support → Email Piping** to configure

Note: All dependencies are included. No composer install required.

== Frequently Asked Questions ==

= Does this replace Fluent Support's built-in email piping? =

Yes, this is a privacy-focused alternative. When using this plugin, you should disable any email forwarding to `pipes.fluentsupport.com`.

= Why is POP3 showing as disabled? =

POP3 requires the PHP IMAP extension. IMAP protocol works without it using native PHP sockets. We recommend using IMAP as most mail servers support it.

= How do I use this with Gmail? =

For Gmail, you need to:
1. Enable 2-Factor Authentication
2. Create an App Password at https://myaccount.google.com/apppasswords
3. Use the App Password instead of your regular password

= How often are emails checked? =

By default, every 5 minutes. You can adjust this in the Settings tab.

= Is this GDPR compliant? =

Yes! That's the main reason this plugin exists. All email processing happens on your own server - no data is sent to third parties.

== Screenshots ==

1. Email Accounts configuration
2. Settings page with status information
3. Email processing logs

== Changelog ==

= 1.0.0 =
* Initial release
* IMAP support without ext-imap requirement
* POP3 support (requires ext-imap)
* Multiple account support
* Automatic email fetching via WP-Cron
* Attachment handling
* Debug logging option

== Upgrade Notice ==

= 1.0.0 =
Initial release - GDPR compliant email piping for Fluent Support.

== Disclaimer ==

This is an **unofficial plugin** and is **not affiliated with, endorsed by, or connected to WPManageNinja or Fluent Support** in any way. "Fluent Support" is a trademark of WPManageNinja. This plugin is an independent, third-party solution created to address privacy concerns with the official email piping feature.
