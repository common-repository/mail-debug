<?php
// exit if we are not uninstalling the plugin...
if( !defined( 'ABSPATH' ) && !defined( 'WP_UNINSTALL_PLUGIN' ) ) exit();

// delete plugin options
delete_option('mail_debug_adminonly');
?>