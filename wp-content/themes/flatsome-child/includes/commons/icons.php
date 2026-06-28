<?php
/**
 * Lucide SVG icon helpers (ISC license — assets/imgs/icons/lucide/LICENSE).
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Icon file URL.
 *
 * @param string $name Icon basename without extension.
 */
function dmc_icon_url( $name ) {
	$name = sanitize_file_name( $name );
	$path = get_stylesheet_directory() . '/assets/imgs/icons/' . $name . '.svg';

	if ( ! file_exists( $path ) ) {
		return '';
	}

	return get_stylesheet_directory_uri() . '/assets/imgs/icons/' . $name . '.svg';
}

/**
 * WooCommerce category slug → icon basename.
 *
 * @param string $slug Term slug.
 */
function dmc_homepage_category_icon_slug( $slug ) {
	$map = [
		'tivi'       => 'tivi',
		'tu-lanh'    => 'tu-lanh',
		'may-giat'   => 'may-giat',
		'dieu-hoa'   => 'dieu-hoa',
		'gia-dung'   => 'gia-dung',
		'dien-thoai' => 'dien-thoai',
		'laptop'     => 'laptop',
		'am-thanh'   => 'am-thanh',
		'phu-kien'   => 'phu-kien',
	];

	return $map[ $slug ] ?? 'package';
}

/**
 * Guess icon from Vietnamese category label (mega menu fallback).
 *
 * @param string $label Display label.
 */
function dmc_icon_slug_from_label( $label ) {
	$label = mb_strtolower( $label, 'UTF-8' );

	$rules = [
		'tivi'       => [ 'tivi', 'tv' ],
		'tu-lanh'    => [ 'tủ lạnh', 'tu lanh', 'tủ đông', 'tu dong', 'tủ mát', 'tu mat' ],
		'may-giat'   => [ 'máy giặt', 'may giat', 'máy sấy', 'may say' ],
		'dieu-hoa'   => [ 'máy lạnh', 'may lanh', 'điều hòa', 'dieu hoa' ],
		'gia-dung'   => [ 'nồi', 'noi', 'bếp', 'bep', 'lò', 'lo ', 'quạt', 'quat', 'hút bụi', 'hut bui', 'gia dụng', 'gia dung' ],
		'dien-thoai' => [ 'iphone', 'samsung', 'điện thoại', 'dien thoai', 'tablet' ],
		'laptop'     => [ 'laptop', 'macbook' ],
		'am-thanh'   => [ 'loa', 'âm thanh', 'am thanh', 'karaoke', 'dàn âm thanh', 'dan am thanh' ],
		'phu-kien'   => [ 'phụ kiện', 'phu kien', 'tai nghe' ],
	];

	foreach ( $rules as $slug => $needles ) {
		foreach ( $needles as $needle ) {
			if ( str_contains( $label, $needle ) ) {
				return $slug;
			}
		}
	}

	return 'package';
}

/**
 * Inline Lucide SVG (currentColor via CSS variant classes).
 *
 * @param string $name Icon basename.
 * @param array  $args {
 *     @type string $class   Extra CSS classes.
 *     @type int    $size    Pixel width/height.
 *     @type string $variant Color preset: blue|white|deep|yellow|muted|ink|accent.
 *     @type string $title   Optional title attribute.
 * }
 */
function dmc_icon( $name, $args = [] ) {
	$args = wp_parse_args(
		$args,
		[
			'class'   => '',
			'size'    => 24,
			'variant' => 'blue',
			'title'   => '',
		]
	);

	$name = sanitize_file_name( $name );
	$path = get_stylesheet_directory() . '/assets/imgs/icons/' . $name . '.svg';

	if ( ! file_exists( $path ) ) {
		$path = get_stylesheet_directory() . '/assets/imgs/icons/package.svg';
		if ( ! file_exists( $path ) ) {
			return '';
		}
	}

	static $cache = [];

	if ( ! isset( $cache[ $name ] ) ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$cache[ $name ] = file_get_contents( $path );
	}

	$svg = preg_replace( '/\s(width|height)="[^"]*"/', '', $cache[ $name ] );

	$size    = max( 12, min( 56, (int) $args['size'] ) );
	$variant = sanitize_html_class( $args['variant'] );
	$classes = trim( 'dmc-icon dmc-icon--' . $variant . ' ' . $args['class'] );
	$title   = $args['title'] ? ' title="' . esc_attr( $args['title'] ) . '"' : '';

	return sprintf(
		'<span class="%s" style="width:%dpx;height:%dpx"%s aria-hidden="true">%s</span>',
		esc_attr( $classes ),
		$size,
		$size,
		$title,
		$svg // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme SVG asset.
	);
}

/**
 * Category term icon: WooCommerce thumbnail or Lucide fallback.
 *
 * @param WP_Term|int $term  Term object or ID.
 * @param array       $args  Passed to dmc_icon().
 */
function dmc_homepage_term_icon_html( $term, $args = [] ) {
	if ( is_numeric( $term ) ) {
		$term = get_term( (int) $term, 'product_cat' );
	}

	$icon_args = wp_parse_args(
		$args,
		[
			'size'    => 22,
			'variant' => 'blue',
			'class'   => 'dmc-icon--category',
		]
	);

	if ( ! $term || is_wp_error( $term ) ) {
		return dmc_icon( 'package', $icon_args );
	}

	if ( function_exists( 'dmc_homepage_term_thumbnail_id' ) ) {
		$thumb_id = dmc_homepage_term_thumbnail_id( $term );
		if ( $thumb_id ) {
			$image = wp_get_attachment_image(
				$thumb_id,
				'thumbnail',
				false,
				[
					'class'   => 'term-thumb',
					'alt'     => $term->name,
					'loading' => 'lazy',
				]
			);

			if ( $image ) {
				return $image;
			}
		}
	}

	return dmc_icon( dmc_homepage_category_icon_slug( $term->slug ), $icon_args );
}
