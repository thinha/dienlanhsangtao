<?php
/**
 * Plugin Name: PHP 8.2+ Compatibility
 * Description: Suppresses premature textdomain notices from legacy plugins on WordPress 6.7+.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'doing_it_wrong_trigger_error',
	static function ( $trigger, $function_name ) {
		if ( '_load_textdomain_just_in_time' === $function_name ) {
			return false;
		}

		if ( 'WP_Dependencies->add_data' === $function_name ) {
			return false;
		}

		return $trigger;
	},
	10,
	2
);
