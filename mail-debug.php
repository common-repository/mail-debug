<?php
/*
Plugin Name: Mail Debug
Plugin URI: http://wordpress.org/extend/plugins/mail-debug/
Description: Redirects all email sent through wordpress to the used currently logged in.
Version: 1.4
Author: Benedikt Forchhammer
Author URI: http://mind2.de
Text Domain: mail_debug
*/
/*  Copyright 2009 Benedikt Forchhammer

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
if(basename($_SERVER['SCRIPT_FILENAME']) == basename(__FILE__)) { die(); }

define('MAIL_DEBUG_VERSION', '1.4');
define('MAIL_DEBUG_DBVERSION', 1);

add_action('init', 'mail_debug_init');
function mail_debug_init() {
	load_plugin_textdomain( 'mail_debug', false, basename(dirname(__FILE__)) );
	
	if (MAIL_DEBUG_VERSION != get_option('mail_debug_version')) mail_debug_install();

	if (!get_option('mail_debug_adminonly') || current_user_can('level_10')) {
		add_action('phpmailer_init', 'mail_debug_phpmailer_init');
	}
}

function mail_debug_install() {
	$dbversion = (int) get_option('mail_debug_dbversion');

	while ($dbversion < MAIL_DEBUG_DBVERSION) {
		$dbversion++;
		mail_debug_update_db($dbversion);
	}
	update_option('mail_debug_version', MAIL_DEBUG_VERSION);
}

function mail_debug_update_db($new_version=0) {
	switch ($new_version) {
		case 1:
			add_option('mail_debug_adminonly', '0');
			add_option('mail_debug_redirect_to', 'currentuser');
			break;
	}
	update_option('mail_debug_dbversion', $new_version);
}

function mail_debug_phpmailer_init(&$phpmailer) {
	$to = $phpmailer->to;
	$phpmailer->to = array();
	$cc = $phpmailer->cc;
	$phpmailer->cc = array();
	$bcc = $phpmailer->bcc;
	$phpmailer->bcc = array();
	
	global $current_user;
	get_currentuserinfo();
	
	switch (get_option('mail_debug_redirect_to')) {
		case 'admin':
			$phpmailer->addAddress(get_bloginfo('admin_email'), get_bloginfo('name'));
			break;
		case 'siteadmin':
			if (function_exists('get_site_option')) {
				$phpmailer->addAddress(get_site_option('admin_email'), get_site_option('site_name'));
			}
			else { // fallback to admin
				$phpmailer->addAddress(get_bloginfo('admin_email'), get_bloginfo('name'));
			}
			break;
		case 'currentuser':
		default:
			$phpmailer->addAddress($current_user->user_email, $current_user->display_name);
			break;
	}

	
	
	$txt = mail_debug_format_debuginfo($to, $cc, $bcc);
	if ($phpmailer->ContentType == 'text/html') {
		$phpmailer->Body = '<pre>' . $txt . '</pre>' . $phpmailer->Body;
		$phpmailer->AltBody = $txt . $phpmailer->AltBody;
	}
	else {
		$phpmailer->Body = $txt . $phpmailer->Body;
	}
		
	$phpmailer->Subject .= ' (DEBUG EMAIL)';
}

function mail_debug_format_addresses($addresses=array()) {
	if (empty($addresses) || !is_array($addresses)) return "\t - \n";
	
	$str = '';
	foreach ($addresses as $address) {
		$email = $address[0];
		$name = $address[1];
		if (!empty($name)) $email = ' <'. $email .'>';
		$str .= "\t" . $name . $email . "\n";
	}
	
	return $str;
}

function mail_debug_format_debuginfo($to, $cc, $bcc) {
	$msg = '';

	$msg .= __('This message would have been sent as follows: ', 'mail_debug');
	$msg .= "\n" . 'TO: ' . mail_debug_format_addresses($to);
	$msg .= "\n" . 'CC: ' . mail_debug_format_addresses($cc);
	$msg .= "\n" . 'BCC: ' . mail_debug_format_addresses($bcc);
	$msg .= "\n\n" . '------------------------------------------------------------' . "\n";
	
	return $msg;
}

/////////////// ADMIN STUFF //////////////////

add_action('admin_init', 'mail_debug_admin_init');
function mail_debug_admin_init() {
	register_setting('mail_debug', 'mail_debug_adminonly');
	register_setting('mail_debug', 'mail_debug_redirect_to');
}

add_action('plugin_action_links', 'mail_debug_plugin_action_links', 10, 2);
function mail_debug_plugin_action_links($action_links, $plugin_file) {
	if ($plugin_file == 'mail-debug/mail-debug.php') {
		array_unshift($action_links, '<strong><a href="options-general.php?page=mail_debug">Settings</a></strong>');
	}
	return $action_links;
}

add_action('admin_menu', 'mail_debug_admin_actions');
function mail_debug_admin_actions() {  
	add_options_page("Mail Debug Settings", "Mail Debug", 'level_10', "mail_debug", "mail_debug_admin");
} 

function mail_debug_admin() {
?>
<div class="wrap">
	<h2>Mail Debug</h2>

	<form method="post" action="options.php">

		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e('Plugin access', 'mail_debug'); ?></th>
				<td><input type="checkbox" name="mail_debug_adminonly" id="mail_debug_adminonly" <?php echo get_option('mail_debug_adminonly') ? ' checked="checked"' : '' ?> value="1" /> <label for="mail_debug_adminonly"><?php _e('Only redirect emails for administrators', 'mail_debug') ?></label></td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Email Recipient', 'mail_debug'); ?></th>
				<td>
					<p><input type="radio" name="mail_debug_redirect_to" id="mail_debug_redirect_to_currentuser" value="currentuser" <?php echo get_option('mail_debug_redirect_to') == 'currentuser' ? ' checked="checked"' : '' ?> /> <label for="mail_debug_redirect_to_currentuser"><?php _e('Redirect each user\'s emails to themselves', 'mail_debug') ?></label></p>
					<p><input type="radio" name="mail_debug_redirect_to" id="mail_debug_redirect_to_admin" value="admin" <?php echo get_option('mail_debug_redirect_to') == 'admin' ? ' checked="checked"' : '' ?> /> <label for="mail_debug_redirect_to_admin"><?php _e('Redirect all emails to blog admin', 'mail_debug') ?></label></p>
					<?php if (function_exists('get_site_option')): ?>
					<p><input type="radio" name="mail_debug_redirect_to" id="mail_debug_redirect_to_siteadmin" value="siteadmin" <?php echo get_option('mail_debug_redirect_to') == 'siteadmin' ? ' checked="checked"' : '' ?> v/> <label for="mail_debug_redirect_to_siteadmin"><?php _e('Redirect all emails to site admin (WPMU)', 'mail_debug') ?></label></p>
					<?php endif; ?>
				</td>
			</tr>
		</table>

		<?php settings_fields('mail_debug'); ?>

		<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
		</p>

	</form>
</div>
<?php
}
?>