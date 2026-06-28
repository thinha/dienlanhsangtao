<?php
/**
 * Parser — sanaky.com.vn (WooCommerce).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parse Sanaky product page.
 *
 * @param string $url  Source URL.
 * @param string $html Page HTML.
 * @return array<string, mixed>
 */
function dmc_tmp_crawl_parse_sanaky( $url, $html ) {
	$data             = dmc_tmp_crawl_base_product_data( $url );
	$data['brand']    = 'Sanaky';
	$image_candidates = [];

	$title = dmc_tmp_crawl_meta_content( $html, 'og:title' );
	if ( '' === $title && preg_match( '/<title>([^<]+)<\/title>/i', $html, $match ) ) {
		$title = trim( dmc_tmp_crawl_decode_entities( $match[1] ) );
	}
	$data['name'] = trim( dmc_tmp_crawl_decode_entities( $title ) );

	if ( preg_match( '/<h1[^>]*>\s*(.*?)\s*<\/h1>/is', $html, $match ) ) {
		$h1 = trim( dmc_tmp_crawl_clean_text( $match[1] ) );
		if ( '' !== $h1 ) {
			$data['name'] = $h1;
		}
	}

	$data['sku'] = dmc_tmp_crawl_extract_model_code(
		$data['name'],
		'/\b(VH|SR|SRF|VSK|NAD|DL)[\-A-Z0-9]+\b/i'
	);

	$og_image = dmc_tmp_crawl_meta_content( $html, 'og:image' );
	if ( '' !== $og_image ) {
		$data['featured_image'] = dmc_tmp_crawl_abs_url( $og_image, $url );
	}

	if ( preg_match( '/Đặc điểm nổi bật(.*?)Mua ngay/is', $html, $match ) ) {
		$data['short_description'] = dmc_tmp_crawl_bullets_to_text( $match[1] );
	}

	if ( preg_match( '/class="(?:rank-math-)?breadcrumb[^"]*"[^>]*>(.*?)<\/(?:nav|div|p)>/is', $html, $match ) ) {
		$labels = [];
		if ( preg_match_all( '/>([^<]+)</', $match[1], $links ) ) {
			foreach ( $links[1] as $label ) {
				$label = trim( html_entity_decode( wp_strip_all_tags( $label ), ENT_QUOTES, 'UTF-8' ) );
				if ( '' === $label || in_array( $label, [ '»', '›', 'Trang chủ' ], true ) ) {
					continue;
				}
				if ( 0 === strcasecmp( $label, $data['name'] ) ) {
					continue;
				}
				$labels[] = $label;
			}
		}
		$data['categories'] = array_values( array_unique( $labels ) );
	}

	$sku_tokens = [];
	if ( ! empty( $data['sku'] ) ) {
		$sku_tokens[] = strtolower( preg_replace( '/[^a-z0-9]/', '', $data['sku'] ) );
		if ( preg_match( '/(\d+[a-z0-9]+)$/i', $data['sku'], $code_match ) ) {
			$sku_tokens[] = strtolower( $code_match[1] );
		}
	}
	$sku_tokens = array_values( array_unique( array_filter( $sku_tokens ) ) );

	if ( preg_match_all( '/https:\/\/sanaky\.com\.vn\/wp-content\/uploads\/[^"\']+\.(jpg|jpeg|png|webp)/i', $html, $matches ) ) {
		foreach ( $matches[0] as $img_url ) {
			if ( dmc_tmp_crawl_should_skip_image( $img_url ) ) {
				continue;
			}
			if ( ! empty( $sku_tokens ) ) {
				$matched = false;
				foreach ( $sku_tokens as $token ) {
					if ( false !== stripos( $img_url, $token ) ) {
						$matched = true;
						break;
					}
				}
				if ( ! $matched ) {
					continue;
				}
			}
			$image_candidates[] = esc_url_raw( $img_url );
		}
	}

	if ( preg_match( '/id="tongquan"(.*?)(?=id="thongso")/is', $html, $match ) ) {
		$data['description'] = trim( $match[1] );
		$image_candidates    = array_merge( $image_candidates, dmc_tmp_crawl_extract_image_urls( $match[1], $url ) );
	}

	if ( preg_match( '/id="thongso"(.*?)(?=id="trogiup")/is', $html, $match ) ) {
		$spec_block = $match[1];
		if ( preg_match( '/<table[^>]*>.*?<\/table>/is', $spec_block, $table_match ) ) {
			$specs_html = $table_match[0];
			$rows       = dmc_tmp_crawl_parse_specs_table_html( $specs_html );
			if ( function_exists( 'dmc_pl_normalize_technical_specs_html' ) ) {
				$data['pl_technical_specs'] = dmc_pl_normalize_technical_specs_html( $specs_html );
			} else {
				$data['pl_technical_specs'] = $specs_html;
			}
			if ( empty( $data['sku'] ) && ! empty( $rows['Model'] ) ) {
				$data['sku'] = dmc_tmp_crawl_extract_model_code( $rows['Model'], '/\b([A-Z0-9\-]+)\b/i' );
			}
			dmc_tmp_crawl_apply_specs_dimensions( $data, $rows );
		}
	}

	if ( '' === $data['short_description'] ) {
		$og_desc = dmc_tmp_crawl_meta_content( $html, 'og:description' );
		if ( '' !== $og_desc ) {
			$data['short_description'] = dmc_tmp_crawl_clean_text( $og_desc );
		}
	}

	$image_candidates = dmc_tmp_crawl_prefer_full_images( $image_candidates );

	return apply_filters(
		'dmc_tmp_crawl_parsed_sanaky',
		dmc_tmp_crawl_finalize_product_data( $data, $image_candidates ),
		$url,
		$html
	);
}
