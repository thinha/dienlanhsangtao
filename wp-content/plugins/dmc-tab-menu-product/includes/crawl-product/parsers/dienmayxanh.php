<?php
/**
 * Parser — dienmayxanh.com / thegioididong CDN.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parse Điện Máy XANH product page.
 *
 * @param string $url  Source URL.
 * @param string $html Page HTML.
 * @return array<string, mixed>
 */
function dmc_tmp_crawl_parse_dienmayxanh( $url, $html ) {
	$data             = dmc_tmp_crawl_base_product_data( $url );
	$image_candidates = [];
	$json_products    = dmc_tmp_crawl_parse_json_ld_products( $html );
	$product_json     = $json_products[0] ?? null;

	if ( ! is_array( $product_json ) ) {
		$product_json = dmc_tmp_crawl_parse_embedded_schema_product( $html );
	}

	if ( is_array( $product_json ) ) {
		$data['name'] = dmc_tmp_crawl_decode_entities( (string) ( $product_json['name'] ?? '' ) );

		$brand = dmc_tmp_crawl_json_ld_brand_name( $product_json['brand'] ?? '' );
		if ( '' !== $brand ) {
			$data['brand'] = $brand;
		}

		if ( ! empty( $product_json['description'] ) ) {
			$data['short_description'] = dmc_tmp_crawl_clean_text( (string) $product_json['description'] );
		}

		$offers = $product_json['offers'] ?? [];
		if ( isset( $offers['price'] ) ) {
			$data['regular_price'] = wc_format_decimal( (string) $offers['price'] );
		} elseif ( is_array( $offers ) && isset( $offers[0]['price'] ) ) {
			$data['regular_price'] = wc_format_decimal( (string) $offers[0]['price'] );
		}

		$image = $product_json['image']['contentUrl'] ?? ( $product_json['image'] ?? '' );
		if ( is_array( $image ) ) {
			$image = $image['contentUrl'] ?? ( $image[0] ?? '' );
			if ( is_array( $image ) ) {
				$image = $image['contentUrl'] ?? '';
			}
		}
		if ( is_string( $image ) && '' !== $image ) {
			$data['featured_image'] = esc_url_raw( $image );
			$image_candidates[]     = $data['featured_image'];
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
		$data['name'] = trim( dmc_tmp_crawl_clean_text( dmc_tmp_crawl_decode_entities( $match[1] ) ) );
	}

	$data['sku'] = dmc_tmp_crawl_extract_model_code(
		$data['name'],
		'/\b(WA|WD|WW|RT|RB|RS|UA|UF|AI|AR)[A-Z0-9\-]+\b/i'
	);

	if ( preg_match( '/"price"\s*:\s*(\d+)/', $html, $match ) ) {
		$price = (int) $match[1];
		if ( $price >= 100000 ) {
			$data['regular_price'] = wc_format_decimal( (string) $price );
		}
	}

	$product_id = '';
	if ( preg_match( '/Products\/Images\/\d+\/(\d+)\//i', $html, $match ) ) {
		$product_id = $match[1];
	}

	if ( $product_id ) {
		if ( preg_match_all( '/https:\/\/cdn\.tgdd\.vn\/Products\/Images\/\d+\/' . preg_quote( $product_id, '/' ) . '\/[^"\']+\.(jpg|jpeg|png|webp)/i', $html, $matches ) ) {
			foreach ( $matches[0] as $img_url ) {
				$image_candidates[] = esc_url_raw( html_entity_decode( $img_url, ENT_QUOTES, 'UTF-8' ) );
			}
		}
		if ( preg_match_all( '/https:\/\/cdnv2\.tgdd\.vn\/mwg-static\/dmx\/Products\/Images\/\d+\/' . preg_quote( $product_id, '/' ) . '\/[^"\']+\.(jpg|jpeg|png|webp)/i', $html, $matches ) ) {
			foreach ( $matches[0] as $img_url ) {
				$image_candidates[] = esc_url_raw( html_entity_decode( $img_url, ENT_QUOTES, 'UTF-8' ) );
			}
		}
	}

	if ( preg_match( '/id="feature"[^>]*>(.*?)(?=class="parameter|<div class="box-rating)/is', $html, $match ) ) {
		$feature_html = $match[1];
		$bullets      = dmc_tmp_crawl_bullets_to_text( $feature_html );
		if ( '' !== $bullets ) {
			$data['short_description'] = $bullets;
		}
	}

	if ( preg_match( '/class="box_left"(.*?)(?=class="box_right"|class="parameter)/is', $html, $match ) ) {
		$left_html = $match[1];
		$left_html = preg_replace( '/<div class="url-seoimg">.*?<\/div>/is', '', $left_html );
		if ( strlen( trim( wp_strip_all_tags( $left_html ) ) ) > 100 ) {
			$data['description'] = trim( $left_html );
		}
		$image_candidates = array_merge( $image_candidates, dmc_tmp_crawl_extract_image_urls( $left_html, $url ) );
	}

	if ( preg_match( '/class="breadcrumb[^"]*"[^>]*>(.*?)<\/(?:nav|ol|ul)>/is', $html, $match ) ) {
		$labels = [];
		if ( preg_match_all( '/>([^<]+)</', $match[1], $links ) ) {
			foreach ( $links[1] as $label ) {
				$label = trim( dmc_tmp_crawl_decode_entities( wp_strip_all_tags( $label ) ) );
				if ( '' === $label || preg_match( '/^(trang chủ|home)$/iu', $label ) ) {
					continue;
				}
				$labels[] = $label;
			}
		}
		$data['categories'] = array_values( array_unique( $labels ) );
	} elseif ( preg_match( '/dienmayxanh\.com\/([^\/\?]+)/i', $url, $match ) ) {
		$data['categories'] = [ ucfirst( str_replace( '-', ' ', $match[1] ) ) ];
	}

	$image_candidates = dmc_tmp_crawl_prefer_full_images( $image_candidates );

	return apply_filters(
		'dmc_tmp_crawl_parsed_dienmayxanh',
		dmc_tmp_crawl_finalize_product_data( $data, $image_candidates ),
		$url,
		$html
	);
}

/**
 * Whether host belongs to Điện Máy XANH group.
 *
 * @param string $host Hostname.
 */
function dmc_tmp_crawl_is_dienmayxanh_host( $host ) {
	$host = strtolower( (string) $host );

	return false !== strpos( $host, 'dienmayxanh.com' )
		|| false !== strpos( $host, 'thegioididong.com' );
}
