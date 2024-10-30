=== Mail Debug ===
Contributors: bforchhammer
Tags: email, wp_mail, debug, development, phpmailer, wpmu
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=73KWYTL4D8JEQ
Requires at least: 2.7
Tested up to: 2.7.1
Stable tag: 1.4

Redirects all email sent through wordpress to the user currently logged in or the site administrator.

== Description ==

This plugin when activated automatically redirects all email sent through wp_mail/phpmailer to the user currently logged in or the site administrator.

The redirected email contains information about where the original email would have been sent to. 

Use this plugin if you want to test email-sending features without actually sending emails our to anyone but yourself. I wrote this plugin to ease debugging with subscribe2.

Since Version 1.2 you can choose whether to redirect all email to the currently logged in user (default), the site administrator (only wpmu) or the blog administrator.

== Installation ==

Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation and then activate the Plugin from Plugins page.