<?php
/**
 * User voucher wallet (saved vouchers).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DMC_Voucher_User_Wallet {

	const META_KEY  = 'dmc_saved_vouchers';
	const COOKIE_KEY = 'dmc_saved_vouchers';

	public static function init() {
		// Reserved for future hooks.
	}

	/**
	 * @param int $voucher_id Voucher post ID.
	 */
	public static function remove( $voucher_id ) {
		$voucher_id = (int) $voucher_id;
		$ids        = self::get_saved_ids();

		if ( ! in_array( $voucher_id, $ids, true ) ) {
			return false;
		}

		$ids = array_values( array_diff( $ids, [ $voucher_id ] ) );
		return self::persist( $ids );
	}

	/**
	 * @return array<int>
	 */
	public static function get_saved_ids() {
		if ( is_user_logged_in() ) {
			$ids = get_user_meta( get_current_user_id(), self::META_KEY, true );
			return array_values( array_filter( array_map( 'intval', (array) $ids ) ) );
		}

		$raw = isset( $_COOKIE[ self::COOKIE_KEY ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_KEY ] ) ) : '';
		if ( '' === $raw ) {
			return [];
		}

		$ids = json_decode( $raw, true );
		return array_values( array_filter( array_map( 'intval', (array) $ids ) ) );
	}

	/**
	 * @param int $voucher_id Voucher post ID.
	 * @return bool
	 */
	public static function save( $voucher_id ) {
		$voucher_id = (int) $voucher_id;
		if ( ! DMC_Voucher_Engine::is_voucher_active( $voucher_id ) ) {
			return false;
		}

		$ids = self::get_saved_ids();
		if ( ! in_array( $voucher_id, $ids, true ) ) {
			$ids[] = $voucher_id;
		}

		return self::persist( $ids );
	}

	/**
	 * @param array<int> $ids Voucher IDs.
	 */
	private static function persist( $ids ) {
		$ids = array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );

		if ( is_user_logged_in() ) {
			update_user_meta( get_current_user_id(), self::META_KEY, $ids );
			return true;
		}

		$json = wp_json_encode( $ids );
		if ( ! $json ) {
			return false;
		}

		setcookie( self::COOKIE_KEY, $json, time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
		$_COOKIE[ self::COOKIE_KEY ] = $json;

		return true;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_saved_cards() {
		$cards = [];
		foreach ( self::get_saved_ids() as $voucher_id ) {
			if ( ! DMC_Voucher_Engine::is_voucher_active( $voucher_id ) ) {
				continue;
			}
			$cards[] = DMC_Voucher_Engine::format_voucher_card( $voucher_id );
		}

		return $cards;
	}
}
