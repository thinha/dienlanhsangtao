<?php
/**
 * Delivery fees — admin config + frontend helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default shipping locations (used when admin has not configured yet).
 *
 * @return array<string, array{fee: int, fee_car: int}>
 */
function dmc_tmp_shipping_fee_defaults() {
	$motorbike = [
		'Quận 1'                                                          => 90000,
		'Quận 2'                                                          => 150000,
		'Quận 3'                                                          => 110000,
		'Quận 4'                                                          => 70000,
		'Quận 5'                                                          => 100000,
		'Quận 6'                                                          => 120000,
		'Quận 7'                                                          => 150000,
		'Quận 8'                                                          => 100000,
		'Quận 9'                                                          => 170000,
		'Quận 10'                                                         => 120000,
		'Quận 11'                                                         => 130000,
		'Quận 12'                                                         => 150000,
		'Phú Nhuận'                                                       => 150000,
		'Nhà Bè'                                                          => 130000,
		'Hóc Môn'                                                         => 200000,
		'Gò Vấp'                                                          => 150000,
		'Thủ Đức'                                                         => 170000,
		'Tân Bình'                                                        => 150000,
		'Bình Tân'                                                        => 170000,
		'Bình Thạnh'                                                      => 150000,
		'Tân Phú'                                                         => 150000,
		'Tây Ninh'                                                        => 450000,
		'Bình Chánh'                                                      => 150000,
		'Củ Chi'                                                          => 250000,
		'Cần Giờ'                                                         => 300000,
		'Tiền Giang'                                                      => 450000,
		'Đồng Nai (Biên Hòa, Nhơn Trạch)'                                 => 300000,
		'Đồng Nai (Trảng Bom, Long Thành, Thống Nhất)'                    => 400000,
		'Đồng Nai (Định Quán, Cẩm Mỹ, Vĩnh Cửu, Long Khánh)'             => 500000,
		'Vũng Tàu'                                                        => 500000,
		'Bình Dương (Dĩ An)'                                              => 250000,
		'Bình Dương (Thủ Dầu Một, Thuận An)'                              => 300000,
		'Bình Dương (Tân Uyên, Bến Cát, Bắc Tân Uyên)'                    => 350000,
		'Bình Dương (Phú Giáo, Bàu Bàng, Dầu Tiếng)'                      => 400000,
		'Long An (Cần Giuộc, Bến Lức, Phước Lại, Cần Đước)'               => 300000,
		'Long An (Đức Hòa, Hậu Nghĩa, Tân An, Đức Huệ, Thủ Thừa, Châu Thành, Tân Trụ)' => 350000,
		'Long An (Tân Thạnh)'                                             => 400000,
		'Bình Phước'                                                      => 500000,
	];

	$locations = [];
	foreach ( $motorbike as $name => $fee ) {
		$locations[ $name ] = [
			'fee'     => $fee,
			'fee_car' => (int) round( $fee * 1.5 ),
		];
	}

	return $locations;
}

/**
 * Pre-fill ACF repeater with defaults on first open.
 *
 * @param mixed  $value   Field value.
 * @param mixed  $post_id Options page id.
 * @param array  $field   Field config.
 * @return mixed
 */
function dmc_tmp_load_default_shipping_locations( $value, $post_id, $field ) {
	if ( 'options' !== $post_id || ! empty( $value ) ) {
		return $value;
	}

	$rows = [];
	foreach ( dmc_tmp_shipping_fee_defaults() as $name => $data ) {
		$rows[] = [
			'enable'  => 1,
			'name'    => $name,
			'fee'     => $data['fee'],
			'fee_car' => $data['fee_car'],
		];
	}

	return $rows;
}
add_filter( 'acf/load_value/name=tmp_shipping_locations', 'dmc_tmp_load_default_shipping_locations', 10, 3 );

/**
 * Backfill car fee for rows saved before fee_car existed.
 *
 * @param mixed $value   Field value.
 * @param mixed $post_id Options page id.
 * @return mixed
 */
function dmc_tmp_backfill_shipping_fee_car( $value, $post_id, $field ) {
	if ( 'options' !== $post_id || empty( $value ) || ! is_array( $value ) ) {
		return $value;
	}

	foreach ( $value as $index => $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$fee = (int) ( $row['fee'] ?? 0 );
		if ( $fee <= 0 ) {
			continue;
		}

		if ( ! isset( $row['fee_car'] ) || '' === $row['fee_car'] || null === $row['fee_car'] ) {
			$value[ $index ]['fee_car'] = (int) round( $fee * 1.5 );
		}
	}

	return $value;
}
add_filter( 'acf/load_value/name=tmp_shipping_locations', 'dmc_tmp_backfill_shipping_fee_car', 20, 3 );

/**
 * Configured delivery locations for product detail.
 *
 * @return array<string, array{fee: int, fee_car: int}>
 */
function dmc_tmp_get_shipping_locations() {
	$rows = dmc_tmp_option( 'tmp_shipping_locations', null );

	if ( empty( $rows ) || ! is_array( $rows ) ) {
		return dmc_tmp_shipping_fee_defaults();
	}

	$locations = [];

	foreach ( $rows as $row ) {
		if ( isset( $row['enable'] ) && ! $row['enable'] ) {
			continue;
		}

		$name    = trim( (string) ( $row['name'] ?? '' ) );
		$fee     = (int) ( $row['fee'] ?? 0 );
		$fee_car = (int) ( $row['fee_car'] ?? 0 );

		if ( $fee_car <= 0 && $fee > 0 ) {
			$fee_car = (int) round( $fee * 1.5 );
		}

		if ( '' === $name || $fee < 0 || $fee_car < 0 ) {
			continue;
		}

		$locations[ $name ] = [
			'fee'     => $fee,
			'fee_car' => $fee_car,
		];
	}

	return ! empty( $locations ) ? $locations : dmc_tmp_shipping_fee_defaults();
}

/**
 * Resolve shipping fee for a location and delivery type.
 *
 * @param array{fee:int, fee_car:int} $location Location row.
 * @param string                      $type     motorbike|car
 */
function dmc_tmp_resolve_shipping_fee( array $location, $type = 'motorbike' ) {
	if ( 'car' === $type ) {
		return (int) ( $location['fee_car'] ?? 0 );
	}

	return (int) ( $location['fee'] ?? 0 );
}
