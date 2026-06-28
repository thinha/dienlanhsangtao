<?php
/**
 * Parser — dienmaycholon.com.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parse Điện Máy Chợ Lớn product page.
 *
 * @param string $url  Source URL.
 * @param string $html Page HTML.
 * @return array<string, mixed>
 */
function dmc_tmp_crawl_parse_dienmaycholon( $url, $html ) {
	$data             = dmc_tmp_crawl_base_product_data( $url );
	$image_candidates = [];
	$json_products    = dmc_tmp_crawl_parse_json_ld_products( $html );
	$product_json     = $json_products[0] ?? null;

	if ( is_array( $product_json ) ) {
		$data['name'] = dmc_tmp_crawl_decode_entities( (string) ( $product_json['name'] ?? '' ) );

		$brand = dmc_tmp_crawl_json_ld_brand_name( $product_json['brand'] ?? '' );
		if ( '' !== $brand ) {
			$data['brand'] = $brand;
		}

		$data['sku'] = dmc_tmp_crawl_extract_model_code(
			$data['name'],
			'/\b(BD|SR|VH|NA|ES|DL|AW|FW)[\-A-Z0-9]+\b/i'
		);
		if ( '' === $data['sku'] && ! empty( $product_json['mpn'] ) ) {
			$data['sku'] = strtoupper( (string) $product_json['mpn'] );
		}

		if ( ! empty( $product_json['description'] ) ) {
			$data['short_description'] = dmc_tmp_crawl_clean_text( (string) $product_json['description'] );
		}

		if ( ! empty( $product_json['offers']['price'] ) ) {
			$data['regular_price'] = wc_format_decimal( (string) $product_json['offers']['price'] );
		}

		$images = $product_json['image'] ?? [];
		if ( is_string( $images ) ) {
			$images = [ $images ];
		}
		foreach ( (array) $images as $img ) {
			$abs = dmc_tmp_crawl_abs_url( $img, $url );
			if ( $abs ) {
				$image_candidates[] = $abs;
				if ( '' === $data['featured_image'] ) {
					$data['featured_image'] = $abs;
				}
			}
		}

		$spec_rows = [];
		foreach ( (array) ( $product_json['additionalProperty'] ?? [] ) as $prop ) {
			if ( empty( $prop['name'] ) ) {
				continue;
			}
			$spec_rows[ (string) $prop['name'] ] = (string) ( $prop['value'] ?? '' );
		}
		if ( ! empty( $spec_rows ) ) {
			$data['pl_technical_specs'] = dmc_tmp_crawl_build_specs_table( $spec_rows );
			dmc_tmp_crawl_apply_specs_dimensions( $data, $spec_rows );
		}
	}

	if ( '' === $data['name'] && preg_match( '/<h1[^>]*>\s*(.*?)\s*<\/h1>/is', $html, $match ) ) {
		$data['name'] = trim( dmc_tmp_crawl_clean_text( $match[1] ) );
	}

	if ( '' === $data['sku'] ) {
		$data['sku'] = dmc_tmp_crawl_extract_model_code(
			$data['name'],
			'/\b(BD|SR|VH|NA|ES|DL|AW|FW)[\-A-Z0-9]+\b/i'
		);
	}

	$slug = '';
	if ( preg_match( '/dienmaycholon\.com\/(.+?)(?:\?|$)/i', $url, $match ) ) {
		$slug = basename( untrailingslashit( $match[1] ) );
	}

	if ( preg_match_all( '/https:\/\/cdn11\.dienmaycholon\.vn\/filewebdmclnew\/DMCL21\/Picture\/Apro\/Apro_product_\d+\/[^"\']+/i', $html, $matches ) ) {
		foreach ( $matches[0] as $img_url ) {
			$img_url = str_replace( '/Picture//Apro/', '/Picture/Apro/', $img_url );
			if ( $slug && false === stripos( $img_url, $slug ) ) {
				continue;
			}
			$image_candidates[] = esc_url_raw( $img_url );
		}
	}

	if ( preg_match( '/class="des_pro_item"(.*?)(?=class="des_pro_item"|Thông số kỹ thuật|<div class="product-spec)/is', $html, $match ) ) {
		$data['description'] = trim( $match[1] );
		$image_candidates    = array_merge( $image_candidates, dmc_tmp_crawl_extract_image_urls( $match[1], $url ) );
	}

	if ( preg_match( '/Tính năng nổi bật:(.*?)(?=Độc quyền|MUA NGAY|<\/ul>)/is', $html, $match ) ) {
		$bullets = dmc_tmp_crawl_bullets_to_text( $match[1] );
		if ( '' !== $bullets ) {
			$data['short_description'] = $bullets;
		}
	}

	if ( preg_match( '/class="breadcrumb[^"]*"[^>]*>(.*?)<\/(?:nav|ol|ul)>/is', $html, $match ) ) {
		$labels = [];
		if ( preg_match_all( '/>([^<]+)</', $match[1], $links ) ) {
			foreach ( $links[1] as $label ) {
				$label = trim( wp_strip_all_tags( html_entity_decode( $label, ENT_QUOTES, 'UTF-8' ) ) );
				if ( '' === $label || preg_match( '/^(trang chủ|home)$/iu', $label ) ) {
					continue;
				}
				$labels[] = $label;
			}
		}
		$data['categories'] = array_values( array_unique( $labels ) );
	} elseif ( $slug ) {
		$parts = explode( '-', $slug );
		if ( count( $parts ) >= 2 ) {
			$data['categories'] = [ ucfirst( str_replace( '-', ' ', $parts[0] ) ) ];
		}
	}

	$image_candidates = dmc_tmp_crawl_prefer_full_images( $image_candidates );

	return apply_filters(
		'dmc_tmp_crawl_parsed_dienmaycholon',
		dmc_tmp_crawl_finalize_product_data( $data, $image_candidates ),
		$url,
		$html
	);
}
