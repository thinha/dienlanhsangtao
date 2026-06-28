<?php
/**
 * ACF admin validation — clearer Vietnamese error messages on Product settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Map field keys to tab / parent repeater metadata.
 *
 * @return array<string, array{label:string, tab:string, parent:string}>
 */
function dmc_tmp_acf_field_meta_map() {
	static $map = null;

	if ( null !== $map ) {
		return $map;
	}

	$map         = [];
	$current_tab = '';

	$walk = static function ( array $fields ) use ( &$map, &$walk, &$current_tab ) {
		foreach ( $fields as $field ) {
			if ( 'tab' === ( $field['type'] ?? '' ) ) {
				$current_tab = (string) ( $field['label'] ?? '' );
				continue;
			}

			$key = (string) ( $field['key'] ?? '' );
			if ( '' === $key ) {
				continue;
			}

			$map[ $key ] = [
				'label'  => (string) ( $field['label'] ?? $key ),
				'tab'    => $current_tab,
				'parent' => '',
			];

			if ( ! empty( $field['sub_fields'] ) && is_array( $field['sub_fields'] ) ) {
				$parent_label = (string) ( $field['label'] ?? '' );
				foreach ( $field['sub_fields'] as $sub_field ) {
					if ( 'tab' === ( $sub_field['type'] ?? '' ) ) {
						continue;
					}

					$sub_key = (string) ( $sub_field['key'] ?? '' );
					if ( '' === $sub_key ) {
						continue;
					}

					$map[ $sub_key ] = [
						'label'  => (string) ( $sub_field['label'] ?? $sub_key ),
						'tab'    => $current_tab,
						'parent' => $parent_label,
					];
				}
			}
		}
	};

	$walk( dmc_tmp_acf_field_definitions() );

	return $map;
}

/**
 * Parse repeater row index from ACF input name.
 *
 * @return array{parent_key:string, row:int}|null
 */
function dmc_tmp_acf_parse_repeater_row_from_input( $input ) {
	if ( ! is_string( $input ) || '' === $input ) {
		return null;
	}

	if ( ! preg_match( '/\[([^\]]+)\]\[(row-\d+|acfcloneindex)\]\[([^\]]+)\]$/', $input, $matches ) ) {
		return null;
	}

	$row = 0;
	if ( preg_match( '/row-(\d+)/', $matches[2], $row_match ) ) {
		$row = (int) $row_match[1];
	}

	return [
		'parent_key' => $matches[1],
		'row'        => $row,
	];
}

/**
 * Build a contextual Vietnamese validation message.
 */
function dmc_tmp_acf_build_validation_message( array $field, $input, $reason = 'required' ) {
	$meta  = dmc_tmp_acf_field_meta_map();
	$key   = (string) ( $field['key'] ?? '' );
	$info  = $meta[ $key ] ?? null;
	$label = $info['label'] ?? (string) ( $field['label'] ?? $key );

	$parts = [];

	if ( ! empty( $info['tab'] ) ) {
		$parts[] = 'Tab "' . $info['tab'] . '"';
	}

	$repeater = dmc_tmp_acf_parse_repeater_row_from_input( $input );
	if ( $repeater ) {
		$parent_info   = $meta[ $repeater['parent_key'] ] ?? null;
		$parent_label  = $parent_info['label'] ?? ( $info['parent'] ?? '' );
		if ( $parent_label ) {
			$parts[] = $parent_label;
		}
		$parts[] = 'dòng ' . ( $repeater['row'] + 1 );
	} elseif ( ! empty( $info['parent'] ) ) {
		$parts[] = $info['parent'];
	}

	$parts[] = '"' . $label . '"';

	$path = implode( ' › ', $parts );

	if ( 'min_rows' === $reason ) {
		$min = (int) ( $field['min'] ?? 1 );
		return $path . sprintf( ' cần ít nhất %d dòng.', $min );
	}

	if ( 'min_selection' === $reason ) {
		$min = (int) ( $field['min'] ?? 1 );
		return $path . sprintf( ' cần chọn ít nhất %d sản phẩm.', $min );
	}

	return $path . ' là bắt buộc.';
}

/**
 * Customize ACF validation messages for Product settings fields.
 *
 * @param mixed $valid
 */
function dmc_tmp_acf_validate_value_message( $valid, $value, $field, $input ) {
	$key = (string) ( $field['key'] ?? '' );
	if ( 0 !== strpos( $key, 'field_tmp_' ) ) {
		return $valid;
	}

	if ( is_string( $valid ) && '' !== $valid ) {
		if ( false !== strpos( $valid, 'Minimum rows reached' ) ) {
			return dmc_tmp_acf_build_validation_message( $field, $input, 'min_rows' );
		}

		if ( false !== strpos( $valid, 'requires at least' ) ) {
			return dmc_tmp_acf_build_validation_message( $field, $input, 'min_selection' );
		}

		return $valid;
	}

	if ( false !== $valid ) {
		return $valid;
	}

	return dmc_tmp_acf_build_validation_message( $field, $input );
}
add_filter( 'acf/validate_value', 'dmc_tmp_acf_validate_value_message', 20, 4 );

/**
 * Enqueue admin script on Product options page only.
 */
function dmc_tmp_admin_validation_assets( $hook_suffix ) {
	if ( ! function_exists( 'acf' ) ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || false === strpos( (string) $screen->id, 'dmc-tab-menu-product-settings' ) ) {
		return;
	}

	wp_enqueue_script(
		'dmc-tmp-admin-validation',
		DMC_TMP_URL . 'assets/js/admin-validation.js',
		[ 'acf-input' ],
		DMC_TMP_VERSION,
		true
	);

	wp_enqueue_style(
		'dmc-tmp-admin-validation',
		DMC_TMP_URL . 'assets/css/admin-shipping-bulk.css',
		[],
		DMC_TMP_VERSION
	);
}
add_action( 'acf/input/admin_enqueue_scripts', 'dmc_tmp_admin_validation_assets' );
