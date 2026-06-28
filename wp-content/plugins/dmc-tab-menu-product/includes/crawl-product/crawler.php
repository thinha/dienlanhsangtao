<?php
/**
 * Crawl product — fetch & parse external product pages.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Supported crawl hosts => label.
 *
 * @return array<string, string>
 */
function dmc_tmp_crawl_supported_hosts() {
	return apply_filters(
		'dmc_tmp_crawl_supported_hosts',
		[
			'darling.com.vn'         => 'Darling Việt Nam',
			'www.darling.com.vn'     => 'Darling Việt Nam',
			'sanaky.com.vn'          => 'Sanaky Việt Nam',
			'www.sanaky.com.vn'      => 'Sanaky Việt Nam',
			'dienmaycholon.com'      => 'Điện Máy Chợ Lớn',
			'www.dienmaycholon.com'  => 'Điện Máy Chợ Lớn',
			'dienmayxanh.com'        => 'Điện Máy XANH',
			'www.dienmayxanh.com'    => 'Điện Máy XANH',
		]
	);
}

/**
 * Fetch remote HTML.
 *
 * @param string $url Product URL.
 * @return string|WP_Error
 */
function dmc_tmp_crawl_fetch_html( $url ) {
	$response = wp_remote_get(
		$url,
		[
			'timeout'    => 30,
			'user-agent' => 'DMC-CrawlProduct/1.0 WordPress/' . get_bloginfo( 'version' ),
			'headers'    => [
				'Accept'          => 'text/html,application/xhtml+xml',
				'Accept-Language' => 'vi-VN,vi;q=0.9',
			],
		]
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	if ( $code < 200 || $code >= 300 ) {
		return new WP_Error(
			'crawl_http_error',
			sprintf(
				/* translators: %d: HTTP status code */
				__( 'Không tải được trang (HTTP %d).', 'dmc-tab-menu-product' ),
				$code
			)
		);
	}

	$body = wp_remote_retrieve_body( $response );
	if ( '' === trim( $body ) ) {
		return new WP_Error( 'crawl_empty', __( 'Trang trả về nội dung rỗng.', 'dmc-tab-menu-product' ) );
	}

	return $body;
}

/**
 * Resolve relative URL against base.
 *
 * @param string $url  Possibly relative URL.
 * @param string $base Base URL.
 */
function dmc_tmp_crawl_abs_url( $url, $base ) {
	$url = trim( html_entity_decode( (string) $url, ENT_QUOTES, 'UTF-8' ) );
	if ( '' === $url ) {
		return '';
	}

	if ( preg_match( '#^https?://#i', $url ) ) {
		return esc_url_raw( $url );
	}

	$parts = wp_parse_url( $base );
	if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
		return esc_url_raw( $url );
	}

	$root = $parts['scheme'] . '://' . $parts['host'];
	if ( ! empty( $parts['port'] ) ) {
		$root .= ':' . $parts['port'];
	}

	if ( 0 === strpos( $url, '//' ) ) {
		return esc_url_raw( $parts['scheme'] . ':' . $url );
	}

	if ( 0 === strpos( $url, '/' ) ) {
		return esc_url_raw( $root . $url );
	}

	$path = $parts['path'] ?? '/';
	$dir  = preg_replace( '#/[^/]*$#', '/', $path );

	return esc_url_raw( $root . $dir . ltrim( $url, '/' ) );
}

/**
 * Extract meta og/content value.
 *
 * @param string $html HTML.
 * @param string $prop property or name attribute value.
 */
function dmc_tmp_crawl_meta_content( $html, $prop ) {
	if ( preg_match( '/property="' . preg_quote( $prop, '/' ) . '"\s+content="([^"]*)"/i', $html, $m ) ) {
		return html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' );
	}

	if ( preg_match( '/content="([^"]*)"\s+property="' . preg_quote( $prop, '/' ) . '"/i', $html, $m ) ) {
		return html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' );
	}

	return '';
}

/**
 * Normalize image URL for deduplication (strip thumb suffix, query string).
 *
 * @param string $url Image URL.
 */
function dmc_tmp_crawl_image_dedupe_key( $url ) {
	$url = strtolower( rawurldecode( (string) $url ) );
	$url = preg_replace( '/[?#].*$/', '', $url );
	$url = preg_replace( '/_\d+x\d+(?=\.[a-z]+$)/i', '', $url );
	$url = preg_replace( '#/\d+x\d+x\d+/#', '/', $url );

	return $url;
}

/**
 * Whether an image URL should be skipped for product gallery.
 *
 * @param string $url Absolute image URL.
 */
function dmc_tmp_crawl_should_skip_image( $url ) {
	$url_l = strtolower( rawurldecode( (string) $url ) );

	$skip_patterns = [
		'/duong[\s\-_%20]*ngang/i',
		'/hinhanh\/log/i',
		'/icons\.png/i',
		'/mua[\s\-_%20]*ngay/i',
		'/banner[\s\-_%20]*quang[\s\-_%20]*cao/i',
		'/images\/top\.png/i',
		'/fax\.png/i',
		'/clock\.png/i',
		'/phone\.png/i',
		'/holine\.png/i',
		'/tel\.png/i',
		'/icon[\s\-_%20]*mail/i',
		'/logo[\s\-_%20]*darling/i',
		'/favicon/i',
		'/logo\.png/i',
		'/cropped-favicon/i',
		'/\.gif(\?|$)/i',
		'/data:image/i',
	];

	foreach ( $skip_patterns as $pattern ) {
		if ( preg_match( $pattern, $url_l ) ) {
			return true;
		}
	}

	return (bool) apply_filters( 'dmc_tmp_crawl_skip_image', false, $url );
}

/**
 * Whether URL looks like a product/content image on Darling.
 *
 * @param string $url Absolute image URL.
 */
function dmc_tmp_crawl_is_product_image( $url ) {
	if ( dmc_tmp_crawl_should_skip_image( $url ) ) {
		return false;
	}

	$url_l = strtolower( rawurldecode( (string) $url ) );

	return (bool) preg_match( '#/(upload/(product|images)/|upload/product/)#i', $url_l );
}

/**
 * Extract image URLs from HTML chunk.
 *
 * @param string $html     HTML fragment.
 * @param string $base_url Base page URL.
 * @return string[] Absolute URLs in document order.
 */
function dmc_tmp_crawl_extract_image_urls( $html, $base_url ) {
	$found = [];

	if ( preg_match_all( '/(?:src|data-image|data-zoom-image|href)\s*=\s*"([^"]+)"/i', (string) $html, $matches ) ) {
		foreach ( $matches[1] as $raw ) {
			if ( preg_match( '/\.(jpe?g|png|webp)(\?|$)/i', $raw ) || preg_match( '#upload/(product|images)/#i', $raw ) ) {
				$abs = dmc_tmp_crawl_abs_url( $raw, $base_url );
				if ( $abs && dmc_tmp_crawl_is_product_image( $abs ) ) {
					$found[] = $abs;
				}
			}
		}
	}

	return $found;
}

/**
 * Merge image candidates: featured + product gallery (deduped, prefer full size).
 *
 * @param string[] $candidates Image URLs in priority order.
 * @param string   $preferred  Preferred featured image URL.
 * @return array{featured_image:string,gallery_images:string[]}
 */
function dmc_tmp_crawl_assign_product_images( array $candidates, $preferred = '' ) {
	$unique   = [];
	$by_key   = [];

	foreach ( $candidates as $url ) {
		$url = esc_url_raw( trim( (string) $url ) );
		if ( '' === $url || ! dmc_tmp_crawl_is_product_image( $url ) ) {
			continue;
		}

		$key = dmc_tmp_crawl_image_dedupe_key( $url );
		if ( isset( $by_key[ $key ] ) ) {
			// Prefer URL without thumbnail suffix (longer path usually = full size).
			if ( strlen( $url ) > strlen( $by_key[ $key ] ) ) {
				$by_key[ $key ] = $url;
			}
			continue;
		}

		$by_key[ $key ] = $url;
		$unique[]       = $url;
	}

	// Rebuild ordered list preserving first-seen order with best URL per key.
	$ordered = [];
	$seen    = [];
	foreach ( $candidates as $url ) {
		$key = dmc_tmp_crawl_image_dedupe_key( $url );
		if ( isset( $seen[ $key ] ) || ! isset( $by_key[ $key ] ) ) {
			continue;
		}
		$seen[ $key ] = true;
		$ordered[]    = $by_key[ $key ];
	}

	if ( empty( $ordered ) ) {
		return [
			'featured_image' => '',
			'gallery_images' => [],
		];
	}

	$featured = '';
	if ( '' !== $preferred ) {
		$pref_key = dmc_tmp_crawl_image_dedupe_key( $preferred );
		foreach ( $ordered as $url ) {
			if ( dmc_tmp_crawl_image_dedupe_key( $url ) === $pref_key ) {
				$featured = $url;
				break;
			}
		}
	}

	if ( '' === $featured ) {
		$featured = $ordered[0];
	}

	$featured_key = dmc_tmp_crawl_image_dedupe_key( $featured );
	$gallery      = [];

	foreach ( $ordered as $url ) {
		if ( dmc_tmp_crawl_image_dedupe_key( $url ) === $featured_key ) {
			continue;
		}
		$gallery[] = $url;
	}

	return [
		'featured_image' => $featured,
		'gallery_images' => array_values( $gallery ),
	];
}

/**
 * Whether a string is mostly uppercase (ALL CAPS source titles).
 *
 * @param string $text Text to check.
 */
function dmc_tmp_crawl_is_mostly_uppercase( $text ) {
	$letters = preg_replace( '/[^[:alpha:]]/u', '', (string) $text );
	if ( '' === $letters ) {
		return false;
	}

	$upper = preg_replace( '/[^[:upper:]]/u', '', $letters );
	$ratio = mb_strlen( $upper, 'UTF-8' ) / mb_strlen( $letters, 'UTF-8' );

	return $ratio >= 0.75;
}

/**
 * Normalize ALL CAPS product title → sentence case (chữ cái đầu).
 * Giữ nguyên mã model / dung tích (DMF-3079ASK, 300L…).
 *
 * @param string $title Raw title.
 */
function dmc_tmp_crawl_normalize_title( $title ) {
	$title = trim( preg_replace( '/\s+/u', ' ', (string) $title ) );
	if ( '' === $title ) {
		return '';
	}

	if ( ! dmc_tmp_crawl_is_mostly_uppercase( $title ) ) {
		return $title;
	}

	$normalized = mb_strtolower( $title, 'UTF-8' );
	$normalized = mb_strtoupper( mb_substr( $normalized, 0, 1, 'UTF-8' ), 'UTF-8' )
		. mb_substr( $normalized, 1, null, 'UTF-8' );

	// Thương hiệu Darling.
	$normalized = preg_replace( '/\bdarling\b/u', 'Darling', $normalized );

	// Mã model / dung tích / inch — giữ dạng viết hoa chuẩn.
	$normalized = preg_replace_callback(
		'/\b(\d+\s*l|\d+\s*lit|\d+hp|\d+\s*hp|\d+inch|\d+\s*inch|\d{2}hd[\w\-]*|dmf[\-\w]+|dmr[\-\w]+|nad[\-\w]+|dl[\-\w]+)\b/iu',
		static function ( $matches ) {
			return mb_strtoupper( str_replace( ' ', '', $matches[1] ), 'UTF-8' );
		},
		$normalized
	);

	return apply_filters( 'dmc_tmp_crawl_normalize_title', $normalized, $title );
}

/**
 * Strip tags but keep basic structure for short desc.
 */
function dmc_tmp_crawl_clean_text( $html ) {
	$text = wp_strip_all_tags( (string) $html, true );
	$text = preg_replace( "/\r\n|\r/", "\n", $text );
	$text = preg_replace( "/\n{3,}/", "\n\n", $text );

	return trim( $text );
}

/**
 * Parse Darling product page.
 *
 * @param string $url  Source URL.
 * @param string $html Page HTML.
 * @return array<string, mixed>|WP_Error
 */
function dmc_tmp_crawl_parse_darling( $url, $html ) {
	$data = [
		'source_url'         => $url,
		'name'               => '',
		'sku'                => '',
		'short_description'  => '',
		'description'        => '',
		'regular_price'      => '',
		'sale_price'         => '',
		'categories'         => [],
		'brand'              => 'Darling',
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

	$og_title = dmc_tmp_crawl_meta_content( $html, 'og:title' );
	$title    = $og_title;

	if ( '' === $title && preg_match( '/<title>([^<]+)<\/title>/i', $html, $m ) ) {
		$title = trim( html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' ) );
		$title = preg_replace( '/\s*-\s*Darling Việt Nam\s*$/iu', '', $title );
	}

	$data['name'] = trim( $title );

	$og_desc = dmc_tmp_crawl_meta_content( $html, 'og:description' );
	if ( '' !== $og_desc ) {
		$data['short_description'] = dmc_tmp_crawl_clean_text( $og_desc );
	}

	$og_image = dmc_tmp_crawl_meta_content( $html, 'og:image' );
	if ( '' !== $og_image ) {
		$data['featured_image'] = dmc_tmp_crawl_abs_url( $og_image, $url );
	}

	// Visible H1 in product area.
	if ( preg_match( '/id="product-detail"[^>]*>.*?<h1[^>]*>\s*(.*?)\s*<\/h1>/is', $html, $m ) ) {
		$h1 = dmc_tmp_crawl_clean_text( $m[1] );
		if ( '' !== $h1 && mb_strlen( $h1, 'UTF-8' ) > 5 ) {
			$data['name'] = $h1;
		}
	}

	// Model / SKU.
	if ( preg_match( '/Model:\s*([A-Za-z0-9\-\/]+)/iu', $html, $m ) ) {
		$data['sku'] = strtoupper( trim( $m[1] ) );
	} elseif ( preg_match( '/\b(DMF[\-\w]+|DMR[\-\w]+|DL[\-\w]+|NAD[\-\w]+|\d{2}HD\d+\w*)\b/i', $data['name'], $m ) ) {
		$data['sku'] = strtoupper( $m[1] );
	}

	// Breadcrumb categories (.brebum).
	if ( preg_match( '/class="brebum[^"]*"[^>]*>(.*?)<\/div>/is', $html, $m ) ) {
		if ( preg_match_all( '/<a[^>]*title="([^"]*)"[^>]*>(.*?)<\/a>/is', $m[1], $links, PREG_SET_ORDER ) ) {
			$cats = [];
			foreach ( $links as $link ) {
				$label = dmc_tmp_crawl_clean_text( $link[2] );
				if ( '' === $label ) {
					$label = dmc_tmp_crawl_clean_text( $link[1] );
				}
				$label = trim( preg_replace( '/\s+/', ' ', $label ) );
				if ( '' === $label || preg_match( '/^(trang chủ|sản phẩm)$/iu', $label ) ) {
					continue;
				}
				$cats[] = $label;
			}
			$data['categories'] = array_values( array_unique( $cats ) );
		}
	}

	$image_candidates = [];

	// Carousel / zoom images (#main-detail + mobile owl).
	if ( preg_match( '/id="product-detail"(.*?)(?=wrap-all-product|id="product-wrap"|id="content-footer")/is', $html, $product_block ) ) {
		$block = $product_block[1];

		if ( preg_match( '/id="main-detail"(.*?)(?=main-product-detail|id="detail")/is', $block, $m ) ) {
			$image_candidates = array_merge( $image_candidates, dmc_tmp_crawl_extract_image_urls( $m[1], $url ) );
		}

		if ( preg_match( '/owl-hinhsp[^>]*>(.*?)(?=<\/div>\s*<div class="col-xs-12 col-md-5)/is', $block, $m ) ) {
			$image_candidates = array_merge( $image_candidates, dmc_tmp_crawl_extract_image_urls( $m[1], $url ) );
		}
	}

	// Long description from #detail (exclude specs table).
	if ( preg_match( '/id="detail"(.*?)(?=wrap-all-product|id="product-wrap"|id="content-footer")/is', $html, $m ) ) {
		$detail_html = $m[1];
		$image_candidates = array_merge( $image_candidates, dmc_tmp_crawl_extract_image_urls( $detail_html, $url ) );

		// Extract specs table first.
		if ( preg_match( '/<table[^>]*>.*?THÔNG SỐ KỸ THUẬT.*?<\/table>/is', $detail_html, $spec_match ) ) {
			$specs_html = $spec_match[0];
			if ( function_exists( 'dmc_pl_normalize_technical_specs_html' ) ) {
				$data['pl_technical_specs'] = dmc_pl_normalize_technical_specs_html( $specs_html );
			} else {
				$data['pl_technical_specs'] = $specs_html;
			}

			$spec_rows = dmc_tmp_crawl_parse_darling_specs_table( $specs_html );
			if ( ! empty( $spec_rows['Kích thước'] ) ) {
				$dims = dmc_tmp_crawl_parse_dimensions( $spec_rows['Kích thước'] );
				$data['length'] = $dims['length'];
				$data['width']  = $dims['width'];
				$data['height'] = $dims['height'];
			}
			if ( ! empty( $spec_rows['Khối lượng tủ'] ) ) {
				$data['weight'] = dmc_tmp_crawl_parse_weight( $spec_rows['Khối lượng tủ'] );
			}
			if ( empty( $data['sku'] ) && ! empty( $spec_rows['MODEL'] ) ) {
				if ( preg_match( '/\b([A-Z0-9\-]+)\b/i', $spec_rows['MODEL'], $sku_m ) ) {
					$data['sku'] = strtoupper( $sku_m[1] );
				}
			}

			$detail_html = str_replace( $specs_html, '', $detail_html );
		}

		// Remove disclaimer / HDSD footers.
		$detail_html = preg_replace( '/<h1[^>]*pro-detail-title[^>]*>.*?<\/h1>/is', '', $detail_html );
		$detail_html = preg_replace( '/Sẽ có những thông tin.*?<\/h1>/is', '', $detail_html );
		$detail_html = preg_replace( '/HDSD tủ đông.*?<\/h1>/is', '', $detail_html );

		$data['description'] = trim( $detail_html );
	}

	if ( '' === $data['short_description'] && '' !== $data['description'] ) {
		$data['short_description'] = wp_trim_words( dmc_tmp_crawl_clean_text( $data['description'] ), 40, '…' );
	}

	// Featured image + product gallery (WooCommerce "Thư viện ảnh sản phẩm").
	if ( '' !== $data['featured_image'] ) {
		array_unshift( $image_candidates, $data['featured_image'] );
	}

	return apply_filters(
		'dmc_tmp_crawl_parsed_darling',
		dmc_tmp_crawl_finalize_product_data( $data, $image_candidates ),
		$url,
		$html
	);
}

/**
 * Parse Darling specs table rows.
 *
 * @param string $table_html Table HTML.
 * @return array<string, string>
 */
function dmc_tmp_crawl_parse_darling_specs_table( $table_html ) {
	$rows = [];

	if ( ! preg_match_all( '/<tr[^>]*>(.*?)<\/tr>/is', $table_html, $tr_matches ) ) {
		return $rows;
	}

	foreach ( $tr_matches[1] as $tr ) {
		if ( ! preg_match_all( '/<t[dh][^>]*>(.*?)<\/t[dh]>/is', $tr, $cells ) || count( $cells[1] ) < 2 ) {
			continue;
		}

		$label = dmc_tmp_crawl_clean_text( $cells[1][0] );
		$value = dmc_tmp_crawl_clean_text( $cells[1][1] );

		if ( '' === $label || preg_match( '/THÔNG SỐ KỸ THUẬT/i', $label ) ) {
			continue;
		}

		$rows[ $label ] = $value;
	}

	return $rows;
}

/**
 * Parse dimension string like 1100x600mmx870mm to cm.
 *
 * @param string $raw Raw dimension.
 * @return array{length:string,width:string,height:string}
 */
function dmc_tmp_crawl_parse_dimensions( $raw ) {
	$out = [
		'length' => '',
		'width'  => '',
		'height' => '',
	];

	if ( ! preg_match_all( '/(\d+(?:\.\d+)?)\s*(?:mm|cm)?/i', (string) $raw, $nums ) || count( $nums[1] ) < 3 ) {
		return $out;
	}

	$values = array_map( 'floatval', array_slice( $nums[1], 0, 3 ) );
	$is_mm  = stripos( $raw, 'mm' ) !== false;

	foreach ( [ 'length', 'width', 'height' ] as $i => $key ) {
		if ( ! isset( $values[ $i ] ) ) {
			continue;
		}
		$val = $values[ $i ];
		if ( $is_mm ) {
			$val = round( $val / 10, 1 );
		}
		$out[ $key ] = (string) $val;
	}

	return $out;
}

/**
 * Parse weight string to kg.
 *
 * @param string $raw Raw weight.
 */
function dmc_tmp_crawl_parse_weight( $raw ) {
	if ( preg_match( '/(\d+(?:\.\d+)?)\s*(kg|g)?/i', (string) $raw, $m ) ) {
		$val = (float) $m[1];
		if ( ! empty( $m[2] ) && 'g' === strtolower( $m[2] ) ) {
			$val = round( $val / 1000, 2 );
		}
		return (string) $val;
	}

	return '';
}

/**
 * Detect parser by URL host.
 *
 * @param string $url Product URL.
 */
function dmc_tmp_crawl_detect_parser( $url ) {
	$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );

	if ( false !== strpos( $host, 'darling.com.vn' ) ) {
		return 'darling';
	}

	if ( false !== strpos( $host, 'sanaky.com.vn' ) ) {
		return 'sanaky';
	}

	if ( false !== strpos( $host, 'dienmaycholon.com' ) ) {
		return 'dienmaycholon';
	}

	if ( function_exists( 'dmc_tmp_crawl_is_dienmayxanh_host' ) && dmc_tmp_crawl_is_dienmayxanh_host( $host ) ) {
		return 'dienmayxanh';
	}

	return '';
}

/**
 * Crawl a product URL.
 *
 * @param string $url Product URL.
 * @return array<string, mixed>|WP_Error
 */
function dmc_tmp_crawl_product( $url ) {
	$url = esc_url_raw( trim( (string) $url ) );
	if ( '' === $url || ! wp_http_validate_url( $url ) ) {
		return new WP_Error( 'invalid_url', __( 'URL không hợp lệ.', 'dmc-tab-menu-product' ) );
	}

	$parser = dmc_tmp_crawl_detect_parser( $url );
	if ( '' === $parser ) {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		return new WP_Error(
			'unsupported_host',
			sprintf(
				/* translators: %s: hostname */
				__( 'Chưa hỗ trợ crawl từ %s. Hiện hỗ trợ: Darling, Sanaky, Điện Máy Chợ Lớn, Điện Máy XANH.', 'dmc-tab-menu-product' ),
				esc_html( (string) $host )
			)
		);
	}

	$html = dmc_tmp_crawl_fetch_html( $url );
	if ( is_wp_error( $html ) ) {
		return $html;
	}

	switch ( $parser ) {
		case 'darling':
			$data = dmc_tmp_crawl_parse_darling( $url, $html );
			break;
		case 'sanaky':
			$data = dmc_tmp_crawl_parse_sanaky( $url, $html );
			break;
		case 'dienmaycholon':
			$data = dmc_tmp_crawl_parse_dienmaycholon( $url, $html );
			break;
		case 'dienmayxanh':
			$data = dmc_tmp_crawl_parse_dienmayxanh( $url, $html );
			break;
		default:
			return new WP_Error( 'no_parser', __( 'Không tìm thấy parser phù hợp.', 'dmc-tab-menu-product' ) );
	}

	if ( is_wp_error( $data ) ) {
		return $data;
	}

	$summary = dmc_tmp_crawl_status_summary( $data );

	return [
		'parser'  => $parser,
		'data'    => $data,
		'summary' => $summary,
	];
}
