<?php
/**
 * Voucher session — applied code on product page / cart.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DMC_Voucher_Session {

	const SESSION_KEY = 'dmc_applied_voucher_code';

	public static function init() {
		add_action( 'woocommerce_init', [ __CLASS__, 'ensure_session' ] );
	}

	public static function ensure_session() {
		if ( ! WC()->session ) {
			return;
		}

		if ( null === WC()->session->get( self::SESSION_KEY ) ) {
			WC()->session->set( self::SESSION_KEY, '' );
		}
	}

	/**
	 * @return string
	 */
	public static function get_applied_code() {
		if ( ! WC()->session ) {
			return '';
		}

		return DMC_Voucher_Engine::sanitize_code( (string) WC()->session->get( self::SESSION_KEY ) );
	}

	/**
	 * @param string $code Voucher code.
	 */
	public static function set_applied_code( $code ) {
		if ( ! WC()->session ) {
			return;
		}

		WC()->session->set( self::SESSION_KEY, DMC_Voucher_Engine::sanitize_code( $code ) );
	}

	public static function clear_applied_code() {
		self::set_applied_code( '' );
	}
}
