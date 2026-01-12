=== Email Log ===
Contributors: Joan Dev & Tech
Tags: email, log, email tracking, debug email, audit emails, wordpress email
Requires PHP: 7.3
Requires at least: 4.0
Tested up to: 6.8
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Log and view all outgoing emails from WordPress. Free plugin for debugging email issues and storing sent emails for auditing purposes.

== Description ==

**Email Log** is a free WordPress plugin that allows you to easily log and view all emails sent from your WordPress site.

This plugin has been modified and maintained by **Joan Dev & Tech** based on an existing email logging solution. It's completely free and designed to help you debug email-related problems in your WordPress site or store sent emails for auditing purposes.

**Perfect for:**
✅ Debugging email delivery issues
✅ Auditing emails sent from your site
✅ Monitoring WooCommerce or Easy Digital Downloads transactional emails
✅ Tracking contact form submissions
✅ Verifying that emails are being sent correctly

**Compatible with:**
🔌 WordPress Multisite
🔌 WooCommerce
🔌 Easy Digital Downloads
🔌 Contact Form 7
🔌 Gravity Forms
🔌 And any plugin that uses the standard WordPress `wp_mail()` function

### 🎯 Core Features (Free)

📧 **Email Logging**
All emails sent through WordPress are automatically logged to a separate database table, capturing:
• Email recipient (To)
• Subject line
• Email content (HTML and plain text)
• Date and time sent
• IP address of the request that triggered the email

👀 **View Logged Emails**
Access all logged emails from a clean admin interface where you can:
• View email content in both HTML and plain text formats
• Filter emails by date range
• Search emails by recipient, subject, or content
• Sort by any column (date, recipient, subject)

🗑️ **Delete Logged Emails**
Manage your email logs efficiently:
• Delete emails individually
• Bulk delete multiple emails at once
• Filter and delete emails by date, recipient, or subject

⚙️ **Screen Options**
Customize your viewing experience:
• Choose which columns to display
• Set the number of emails per page
• Adjust the interface to your preferences

📊 **Dashboard Widget**
Get a quick overview of your email logs:
• See total number of logged emails
• View recent email activity
• Access logs directly from the dashboard

### 🔒 Privacy & Security

🛡️ Only users with appropriate capabilities can view email logs
🛡️ All data is stored securely in your WordPress database
🛡️ Email content is properly escaped before display
🛡️ Nonce verification for all actions

### 🚀 Future Development

This plugin is under active development. Future updates will include:
🔜 Additional filtering options
🔜 Enhanced search capabilities
🔜 Performance optimizations
🔜 UI/UX improvements

### 💬 Support & Documentation

For issues, questions, or feature requests, please visit:
🌐 Plugin URI: https://joandev.com/email-log/
🐙 GitHub: https://github.com/JoanDevandTech/email-log
👨‍💻 Author: Joan Dev & Tech
📍 Location: Galiza, Spain

== Installation ==

### ⚡ Automatic Installation (Recommended)

1. Log in to your WordPress admin panel
2. Navigate to Plugins → Add New
3. Search for "Email Log"
4. Click "Install Now" and then "Activate"

### 📦 Manual Installation

1. Download the plugin zip file
2. Log in to your WordPress admin panel
3. Navigate to Plugins → Add New → Upload Plugin
4. Choose the downloaded zip file and click "Install Now"
5. After installation, click "Activate Plugin"

### 🔧 FTP Installation

1. Download and extract the plugin zip file
2. Upload the `email-log` folder to the `/wp-content/plugins/` directory via FTP
3. Log in to your WordPress admin panel
4. Navigate to Plugins and activate the "Email Log" plugin

### ✨ After Activation

Once activated, the plugin will automatically start logging all emails sent from WordPress. You can view the logs by navigating to **Tools → Email Log** in your WordPress admin panel.

== Frequently Asked Questions ==

= Where can I view the logged emails? =

After activating the plugin, go to **Tools → Email Log** in your WordPress admin panel. All logged emails will be displayed there.

= Does this plugin prevent emails from being sent? =

No, this plugin only logs emails. It does not interfere with the actual sending of emails. All emails are sent normally through WordPress.

= How much storage space will the email logs use? =

The storage space depends on the volume of emails your site sends. Each email log entry stores the recipient, subject, content, and timestamp. You can delete old logs at any time to free up space.

= Can I automatically delete old email logs? =

Currently, you need to manually delete logs. Automatic deletion based on age is planned for a future update.

= Does this work with WooCommerce emails? =

Yes! This plugin works with any plugin that uses the standard WordPress `wp_mail()` function, including WooCommerce, Easy Digital Downloads, Contact Form 7, Gravity Forms, and more.

= Does this work with WordPress Multisite? =

Yes, the plugin is fully compatible with WordPress Multisite installations.

= Can I export the email logs? =

Export functionality is planned for a future update. Currently, you can view and delete logs from the admin interface.

= Who can view the email logs? =

By default, only administrators can view email logs. You can configure which user roles have access to email logs in the plugin settings.

= Does this plugin work with SMTP plugins? =

Yes, this plugin works alongside SMTP plugins. It logs emails before they are handed off to the SMTP service.

= The email content is not being logged when using certain plugins =

Some plugins may modify how emails are sent. If you experience issues with specific plugins, please report them so we can add compatibility fixes.

= How do I uninstall the plugin? =

Simply deactivate and delete the plugin from the Plugins page. You can configure whether to keep or delete the email logs table during uninstallation in the plugin settings.

== Screenshots ==

1. Email log list view showing all logged emails with filtering and search options
2. Screen options panel to customize which columns are displayed
3. HTML preview of a logged email
4. Plain text preview of a logged email
5. Date filter to search emails by date range
6. Dashboard widget showing email log summary

== Changelog ==

= v1.0.1 – 2025-01-12 =
- Initial release by Joan Dev & Tech
- Modified from existing email log plugin
- Updated for WordPress 6.8 compatibility
- Requires PHP 7.3 or higher
- Code cleanup and modernization
- Updated branding and documentation
- Removed PRO version references (this is a free plugin)
- Spanish translation improvements

= v1.0.0 – 2025-01-10 =
- Forked from original Email Log plugin
- Initial modifications by Joan Dev & Tech

== Upgrade Notice ==

= 1.0.1 =
Initial release of the modified free version by Joan Dev & Tech. Fully compatible with WordPress 6.8 and PHP 7.3+.

== Credits ==

This plugin is based on the original Email Log plugin and has been modified and maintained by Joan Dev & Tech (Galiza, Spain).

Original plugin concept and architecture by Sudar Muthu.

== License ==

This plugin is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or any later version.

This plugin is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this plugin. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
