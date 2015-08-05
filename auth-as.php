<?php
defined( 'ABSPATH' ) or exit;
/*
Plugin Name: Auth.AS Two-Factor Authentication Plugin
Plugin URI: http://gruz0.ru/
Description: Two-Factor Authentication for WordPress via auth.as
Author: Alexander Gruzov
Author URI: http://gruz0.ru/
Text Domain: auth-as
Version: 0.1
License: GPL2
*/

if ( ! class_exists( 'Auth_As' ) ) {

	// Хуки для активации и деактивации плагина
	register_activation_hook( __FILE__, array( 'Auth_As', 'activate' ) );
	register_deactivation_hook( __FILE__, array( 'Auth_As', 'deactivate' ) );
	register_uninstall_hook( __FILE__, array('Auth_As', 'uninstall' ) );

	include sprintf( "%s/auth-as.class.php", dirname( __FILE__ ) );

	$auth_as = new Auth_As();

	if (isset( $auth_as) ) {
		// Добавляем пункт "Настройки" в раздел плагинов в WordPress
		function auth_as_plugin_settings_link( $links ) {
			$settings_link = '<a href="admin.php?page=auth_as">' . __( 'Settings', L10N_AUTH_AS_PREFIX ) . '</a>';
			array_unshift( $links, $settings_link );
			return $links;
		}

		$plugin = plugin_basename( __FILE__ );
		add_filter( 'plugin_action_links_' . $plugin, 'auth_as_plugin_settings_link' );
	}
}

