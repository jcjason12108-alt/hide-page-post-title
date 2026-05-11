<?php
/*
Version: 1.3.4
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$hpt_legacy_plugin = plugin_basename( __FILE__ );
$hpt_current_plugin = dirname( $hpt_legacy_plugin ) . '/hide_page_and_title.php';

if ( './hide_page_and_title.php' === $hpt_current_plugin ) {
	$hpt_current_plugin = 'hide_page_and_title.php';
}

$hpt_active_plugins = (array) get_option( 'active_plugins', [] );
$hpt_legacy_index = array_search( $hpt_legacy_plugin, $hpt_active_plugins, true );

if ( false !== $hpt_legacy_index ) {
	$hpt_active_plugins[ $hpt_legacy_index ] = $hpt_current_plugin;
	$hpt_active_plugins = array_values( array_unique( $hpt_active_plugins ) );
	update_option( 'active_plugins', $hpt_active_plugins );
}

if ( is_multisite() ) {
	$hpt_sitewide_plugins = (array) get_site_option( 'active_sitewide_plugins', [] );

	if ( isset( $hpt_sitewide_plugins[ $hpt_legacy_plugin ] ) ) {
		$hpt_activation_time = $hpt_sitewide_plugins[ $hpt_legacy_plugin ];
		unset( $hpt_sitewide_plugins[ $hpt_legacy_plugin ] );
		$hpt_sitewide_plugins[ $hpt_current_plugin ] = $hpt_activation_time;
		update_site_option( 'active_sitewide_plugins', $hpt_sitewide_plugins );
	}
}

require_once __DIR__ . '/hide_page_and_title.php';
