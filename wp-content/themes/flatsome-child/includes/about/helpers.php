<?php
/**
 * About page — data helpers.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Company display name.
 */
function dmc_about_get_company_name() {
	return 'CÔNG TY TNHH SỬA CHỮA THIẾT BỊ ĐIỆN SÁNG TẠO';
}

/**
 * Years in business (since 2011).
 */
function dmc_about_get_years_experience() {
	return max( 1, (int) gmdate( 'Y' ) - 2011 );
}

/**
 * Hotline for CTA blocks.
 *
 * @return array{display:string,tel:string}
 */
function dmc_about_get_hotline() {
	$display = function_exists( 'dmc_tmp_get_company_hotline_display' )
		? dmc_tmp_get_company_hotline_display()
		: ( function_exists( 'dmc_web_option' ) ? dmc_web_option( 'web_hotline', '090 686 9344' ) : '090 686 9344' );
	$tel     = function_exists( 'dmc_tmp_get_company_hotline' )
		? dmc_tmp_get_company_hotline()
		: preg_replace( '/\s+/', '', (string) $display );

	return [
		'display' => $display,
		'tel'     => $tel,
	];
}

/**
 * Key stats for hero / highlight row.
 *
 * @return array<int, array{value:string,label:string}>
 */
function dmc_about_get_stats() {
	$years = dmc_about_get_years_experience();

	return [
		[
			'value' => $years . '+',
			'label' => __( 'Năm kinh nghiệm', 'flatsome-child' ),
		],
		[
			'value' => '6',
			'label' => __( 'Showroom & chi nhánh', 'flatsome-child' ),
		],
		[
			'value' => '5+',
			'label' => __( 'Thương hiệu chính hãng', 'flatsome-child' ),
		],
		[
			'value' => '100%',
			'label' => __( 'Sản phẩm chính hãng', 'flatsome-child' ),
		],
	];
}

/**
 * Core value cards.
 *
 * @return array<int, array{icon:string,title:string,text:string}>
 */
function dmc_about_get_values() {
	return [
		[
			'icon'  => 'shield-check',
			'title' => __( 'Chính hãng 100%', 'flatsome-child' ),
			'text'  => __( 'Nhà phân phối Darling được hãng chứng nhận tại TP.HCM, liên kết trực tiếp với nhà sản xuất.', 'flatsome-child' ),
		],
		[
			'icon'  => 'store',
			'title' => __( 'Hệ thống showroom rộng khắp', 'flatsome-child' ),
			'text'  => __( 'Nhiều điểm bán tại Quận 7, Bình Thạnh, Tân Bình và Bình Dương — thuận tiện xem hàng trực tiếp.', 'flatsome-child' ),
		],
		[
			'icon'  => 'warranty',
			'title' => __( 'Bảo hành tận nơi', 'flatsome-child' ),
			'text'  => __( 'Chế độ bảo hành chính hãng, hỗ trợ lắp đặt và sửa chữa nhanh chóng, tận tâm.', 'flatsome-child' ),
		],
		[
			'icon'  => 'delivery-truck',
			'title' => __( 'Giao hàng toàn quốc', 'flatsome-child' ),
			'text'  => __( 'Giao hàng nhanh, kiểm tra sản phẩm trước khi nhận — mua sắm an tâm.', 'flatsome-child' ),
		],
	];
}

/**
 * Product categories highlighted on about page.
 *
 * @return array<int, array{icon:string,title:string,link:string}>
 */
function dmc_about_get_product_lines() {
	return [
		[
			'icon'  => 'snowflake',
			'title' => __( 'Tủ đông', 'flatsome-child' ),
			'link'  => home_url( '/tu-dong/' ),
		],
		[
			'icon'  => 'package',
			'title' => __( 'Tủ mát', 'flatsome-child' ),
			'link'  => home_url( '/tu-mat/' ),
		],
		[
			'icon'  => 'tu-lanh',
			'title' => __( 'Tủ lạnh', 'flatsome-child' ),
			'link'  => home_url( '/tu-lanh/' ),
		],
		[
			'icon'  => 'may-giat',
			'title' => __( 'Máy giặt', 'flatsome-child' ),
			'link'  => home_url( '/may-giat/' ),
		],
		[
			'icon'  => 'dieu-hoa',
			'title' => __( 'Máy lạnh', 'flatsome-child' ),
			'link'  => home_url( '/may-lanh/' ),
		],
	];
}

/**
 * Store / showroom locations.
 *
 * @return array<int, array{label:string,address:string,map_query:string}>
 */
function dmc_about_get_stores() {
	return [
		[
			'label'      => __( 'Trụ sở chính', 'flatsome-child' ),
			'address'    => '339 Trần Xuân Soạn, Phường Tân Kiểng, Quận 7, TP.HCM',
			'map_query'  => '339 Trần Xuân Soạn, Tân Kiểng, Quận 7, TP.HCM',
		],
		[
			'label'      => __( 'Chi nhánh 1', 'flatsome-child' ),
			'address'    => '437 Trần Xuân Soạn, Phường Tân Kiểng, Quận 7, TP.HCM',
			'map_query'  => '437 Trần Xuân Soạn, Tân Kiểng, Quận 7, TP.HCM',
		],
		[
			'label'      => __( 'Chi nhánh 2', 'flatsome-child' ),
			'address'    => '99 Nguyễn Thị Xiếu, Phường Tân Thuận Tây, Quận 7, TP.HCM',
			'map_query'  => '99 Nguyễn Thị Xiếu, Tân Thuận Tây, Quận 7, TP.HCM',
		],
		[
			'label'      => __( 'Showroom Alaska', 'flatsome-child' ),
			'address'    => '551 Điện Biên Phủ, Phường 25, Quận Bình Thạnh, TP.HCM',
			'map_query'  => '551 Điện Biên Phủ, Bình Thạnh, TP.HCM',
		],
		[
			'label'      => __( 'Showroom Sanaky', 'flatsome-child' ),
			'address'    => '485-487 Hoàng Văn Thụ, Phường 4, Quận Tân Bình, TP.HCM',
			'map_query'  => '485 Hoàng Văn Thụ, Tân Bình, TP.HCM',
		],
		[
			'label'      => __( 'Showroom Darling', 'flatsome-child' ),
			'address'    => '46 Hai Bà Trưng, Phường Đông Hòa, TP. Dĩ An, Bình Dương',
			'map_query'  => '46 Hai Bà Trưng, Dĩ An, Bình Dương',
		],
	];
}

/**
 * Legal / business registration details.
 *
 * @return array<string, string>
 */
function dmc_about_get_business_info() {
	return [
		__( 'Tên doanh nghiệp', 'flatsome-child' ) => dmc_about_get_company_name(),
		__( 'Mã số thuế', 'flatsome-child' )       => '0311084605',
		__( 'Địa chỉ', 'flatsome-child' )          => '339 Trần Xuân Soạn, Phường Tân Kiểng, Quận 7, TP.HCM, Việt Nam',
		__( 'Người đại diện', 'flatsome-child' )    => 'VŨ VĂN SÁNG',
		__( 'Điện thoại', 'flatsome-child' )        => dmc_about_get_hotline()['display'],
		__( 'Ngày hoạt động', 'flatsome-child' )    => '22/08/2011',
		__( 'Quản lý bởi', 'flatsome-child' )       => __( 'Chi cục Thuế khu vực Quận 7', 'flatsome-child' ),
		__( 'Loại hình DN', 'flatsome-child' )      => __( 'Công ty trách nhiệm hữu hạn ngoài NN', 'flatsome-child' ),
		__( 'Tình trạng', 'flatsome-child' )        => __( 'Đang hoạt động (đã được cấp GCN ĐKT)', 'flatsome-child' ),
	];
}

/**
 * About page media URLs (existing uploads).
 *
 * @return array<string, string>
 */
function dmc_about_get_media() {
	$uploads = content_url( '/uploads' );

	return [
		'store_1'      => $uploads . '/2022/05/cua-hang-dien-may-SANG-TAO.jpg',
		'store_2'      => $uploads . '/2022/05/sang-tao-5.jpg',
		'certificate'  => $uploads . '/2021/01/sang-tao-phan-phoi-darling.jpg',
	];
}

/**
 * Default brand logos when Setting Web has none.
 *
 * @return array<int, array{url:string,alt:string,link:string}>
 */
function dmc_about_get_default_brands() {
	$uploads = content_url( '/uploads' );

	return [
		[
			'url'  => $uploads . '/2022/06/logo-darling.webp',
			'alt'  => 'Darling',
			'link' => home_url( '/thuong-hieu/darling/' ),
		],
		[
			'url'  => $uploads . '/2022/06/logo-sanaky.webp',
			'alt'  => 'Sanaky',
			'link' => home_url( '/thuong-hieu/sanaky/' ),
		],
	];
}

/**
 * Brands for about page (ACF first, then defaults).
 *
 * @return array<int, array{url:string,alt:string,link:string}>
 */
function dmc_about_get_brands() {
	if ( function_exists( 'dmc_homepage_get_featured_brands' ) ) {
		$brands = dmc_homepage_get_featured_brands();
		if ( ! empty( $brands ) ) {
			return $brands;
		}
	}

	return dmc_about_get_default_brands();
}

/**
 * Google Maps search URL for an address.
 *
 * @param string $query Address query.
 */
function dmc_about_maps_url( $query ) {
	return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $query );
}
