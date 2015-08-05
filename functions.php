<?php
defined( 'ABSPATH' ) or exit;

define( 'AUTH_AS_PREFIX', 'auth-as-' );
define( 'L10N_AUTH_AS_PREFIX', 'auth-as' ); // textdomain

// Одним запросом загружаем все настройки плагина
$all_options = wp_load_alloptions();
$auth_as_options = array();
foreach( $all_options as $name => $value ) {
	if ( stristr( $name, AUTH_AS_PREFIX ) ) $auth_as_options[$name] = $value;
}

// Сделаем wrapper для get_option, чтобы каждый раз не ходить в базу за настройками
function get_auth_as_option( $name ) {
	global $auth_as_options;
	$option_name = AUTH_AS_PREFIX . $name;
	return isset( $auth_as_options[$option_name] ) ? $auth_as_options[$option_name] : null;
}

