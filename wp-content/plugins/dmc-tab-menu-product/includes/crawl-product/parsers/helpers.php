<?php
/**
 * Shared helpers for product page parsers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Empty product data template.
 *
 * @param string $url Source URL.
 * @return array<string, mixed>
 */
function dmc_tmp_crawl_base_product_data( $url ) {
	return [
		'source_url'         => $url,
		'name'               => '',
		'sku'                => '',
		'short_description'  => '',
		'description'        => '',
		'regular_price'      => '',
		'sale_price'         => '',
		'categories'         => [],
		'brand'              => '',
		'featured_image'     => '',
		'gallery_images'     => [],
		'weight'             => '',
		'length'             => '',
		'width'              => '',
		'height'             => '',
		'stock_status'       => 'instock',
		'status'             => 'draft',
		'pl_technical_specs' => '',
		'pl_delivery_type'   => 'motorbike',
	];
}

/**
 * Finalize parsed product data before return.
 *
 * @param array<string, mixed> $data             Parsed data.
 * @param string[]             $image_candidates Image URLs.
 * @return array<string, mixed>
 */
function dmc_tmp_crawl_finalize_product_data( array $data, array $image_candidates = [] ) {
	if ( '' !== ( $data['featured_image'] ?? '' ) ) {
		array_unshift( $image_candidates, $data['featured_image'] );
	}

	$assigned = dmc_tmp_crawl_assign_product_images( $image_candidates, $data['featured_image'] ?? '' );
	$data['featured_image'] = $assigned['featured_image'];
	$data['gallery_images'] = $assigned['gallery_images'];
	$data['name']           = dmc_tmp_crawl_normalize_title( $data['name'] ?? '' );

	if ( is_array( $data['categories'] ?? null ) ) {
		$data['categories'] = implode( ', ', $data['categories'] );
	}

	return $data;
}

/**
 * Decode HTML entities including hex numeric refs.
 *
 * @param string $text Raw text.
 */
function dmc_tmp_crawl_decode_entities( $text ) {
	$text = (string) $text;
	$text = preg_replace_callback(
		'/&#x([0-9A-Fa-f]+);/',
		static function ( $matches ) {
			return mb_chr( hexdec( $matches[1] ), 'UTF-8' );
		},
		$text
	);

	return html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
}

/**
 * Parse JSON-LD Product nodes from HTML.
 *
 * @param string $html Page HTML.
 * @return array<int, array<string, mixed>>
 */
function dmc_tmp_crawl_parse_json_ld_products( $html ) {
	$products = [];

	if ( ! preg_match_all( '/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', (string) $html, $matches ) ) {
		return $products;
	}

	foreach ( $matches[1] as $json ) {
		$decoded = json_decode( trim( html_entity_decode( $json, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ), true );
		if ( ! is_array( $decoded ) ) {
			continue;
		}

		$nodes = [];
		if ( ! empty( $decoded['@graph'] ) && is_array( $decoded['@graph'] ) ) {
			$nodes = $decoded['@graph'];
		} elseif ( isset( $decoded['@type'] ) ) {
			$nodes = [ $decoded ];
		} else {
			$nodes = $decoded;
		}

		foreach ( $nodes as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$type = $item['@type'] ?? '';
			if ( is_array( $type ) ) {
				$type = implode( ' ', $type );
			}

			if ( false !== stripos( (string) $type, 'Product' ) ) {
				$products[] = $item;
			}
		}
	}

	return $products;
}

/**
 * Parse embedded schema.org Product JSON (Điện Máy XANH).
 *
 * @param string $html Page HTML.
 * @return array<string, mixed>|null
 */
function dmc_tmp_crawl_parse_embedded_schema_product( $html ) {
	if ( ! preg_match( '/\{"@context":"https:\\/\\/schema\.org","@type":"Product".*?\}(?=,"|\s*<)/s', (string) $html, $match ) ) {
		return null;
	}

	$json = $match[0];
	$json = str_replace( '\\/', '/', $json );
	$json = preg_replace( '/,\s*$/', '', $json );

	$data = json_decode( $json, true );

	return is_array( $data ) ? $data : null;
}

/**
 * Build specs table HTML for ACF field.
 *
 * @param array<string, string> $rows Label => value.
 */
function dmc_tmp_crawl_build_specs_table( array $rows ) {
	if ( empty( $rows ) ) {
		return '';
	}

	$html = '<table class="thong-so-ky-thuat"><tbody>';
	foreach ( $rows as $label => $value ) {
		if ( '' === trim( (string) $label ) || '' === trim( (string) $value ) ) {
			continue;
		}
		$html .= '<tr><td>' . esc_html( $label ) . '</td><td>' . wp_kses_post( $value ) . '</td></tr>';
	}
	$html .= '</tbody></table>';

	if ( function_exists( 'dmc_pl_normalize_technical_specs_html' ) ) {
		return dmc_pl_normalize_technical_specs_html( $html );
	}

	return $html;
}

/**
 * Parse generic HTML specs table to rows.
 *
 * @param string $table_html Table HTML.
 * @return array<string, string>
 */
function dmc_tmp_crawl_parse_specs_table_html( $table_html ) {
	$rows = [];

	if ( ! preg_match_all( '/<tr[^>]*>(.*?)<\/tr>/is', (string) $table_html, $tr_matches ) ) {
		return $rows;
	}

	foreach ( $tr_matches[1] as $tr ) {
		if ( ! preg_match_all( '/<t[dh][^>]*>(.*?)<\/t[dh]>/is', $tr, $cells ) || count( $cells[1] ) < 2 ) {
			continue;
		}

		$label = dmc_tmp_crawl_clean_text( $cells[1][0] );
		$value = dmc_tmp_crawl_clean_text( $cells[1][1] );

		if ( '' === $label ) {
			continue;
		}

		$rows[ $label ] = $value;
	}

	return $rows;
}

/**
 * Extract first matching model token from title.
 *
 * @param string $title    Product title.
 * @param string $pattern  Regex with capture group 1.
 */
function dmc_tmp_crawl_extract_model_code( $title, $pattern ) {
	if ( preg_match( $pattern, (string) $title, $matches ) ) {
		return strtoupper( trim( $matches[1] ) );
	}

	return '';
}

/**
 * Convert bullet list HTML to plain short description.
 *
 * @param string $html List HTML.
 */
function dmc_tmp_crawl_bullets_to_text( $html ) {
	$items = [];

	if ( preg_match_all( '/<li[^>]*>(.*?)<\/li>/is', (string) $html, $matches ) ) {
		foreach ( $matches[1] as $item ) {
			$text = trim( dmc_tmp_crawl_clean_text( $item ) );
			if ( '' !== $text ) {
				$items[] = '✔ ' . $text;
			}
		}
	}

	return implode( "\n", $items );
}

/**
 * Brand name from JSON-LD brand node.
 *
 * @param mixed $brand Brand node.
 */
function dmc_tmp_crawl_json_ld_brand_name( $brand ) {
	if ( is_string( $brand ) ) {
		return trim( $brand );
	}

	if ( is_array( $brand ) ) {
		if ( ! empty( $brand['name'] ) ) {
			$name = $brand['name'];
			return is_array( $name ) ? trim( (string) ( $name[0] ?? '' ) ) : trim( (string) $name );
		}
	}

	return '';
}

/**
 * Apply weight/dimensions from specs rows.
 *
 * @param array<string, mixed>  $data Parsed data (by ref).
 * @param array<string, string> $rows Spec rows.
 */
function dmc_tmp_crawl_apply_specs_dimensions( array &$data, array $rows ) {
	$dimension_keys = [
		'Kích thước',
		'Kich thuoc',
		'Kích thước (R*S*C)',
		'Kích thước (R×S×C)',
	];

	$weight_keys = [
		'Trọng lượng',
		'Trong luong',
		'Khối lượng tủ',
	];

	foreach ( $dimension_keys as $key ) {
		if ( empty( $data['length'] ) && ! empty( $rows[ $key ] ) ) {
			$dims = dmc_tmp_crawl_parse_dimensions( $rows[ $key ] );
			$data['length'] = $dims['length'];
			$data['width']  = $dims['width'];
			$data['height'] = $dims['height'];
			break;
		}
	}

	foreach ( $weight_keys as $key ) {
		if ( empty( $data['weight'] ) && ! empty( $rows[ $key ] ) ) {
			$data['weight'] = dmc_tmp_crawl_parse_weight( $rows[ $key ] );
			break;
		}
	}
}

/**
 * Prefer full-size image URL (drop thumb/webp variants).
 *
 * @param string[] $urls Image URLs.
 * @return string[]
 */
function dmc_tmp_crawl_prefer_full_images( array $urls ) {
	$filtered = [];

	foreach ( $urls as $url ) {
		$url_l = strtolower( (string) $url );
		if ( preg_match( '/[-_]\d+x\d+\.(jpg|jpeg|png|webp)$/', $url_l ) ) {
			continue;
		}
		if ( preg_match( '/_\d+\.png\.webp$/', $url_l ) ) {
			continue;
		}
		if ( preg_match( '/[-_]450\.png\.webp$/', $url_l ) ) {
			continue;
		}
		if ( preg_match( '/[-_]200\.png\.webp$/', $url_l ) ) {
			continue;
		}
		$filtered[] = $url;
	}

	return array_values( array_unique( $filtered ) );
}
