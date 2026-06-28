<?php
/**
 * Voucher custom post type.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DMC_Voucher_Post_Type {

	public static function init() {
		add_action( 'init', [ __CLASS__, 'register' ] );
		add_filter( 'manage_voucher_posts_columns', [ __CLASS__, 'columns' ] );
		add_action( 'manage_voucher_posts_custom_column', [ __CLASS__, 'column_content' ], 10, 2 );
	}

	public static function register() {
		$labels = [
			'name'               => __( 'Voucher', 'dmc-voucher' ),
			'singular_name'      => __( 'Voucher', 'dmc-voucher' ),
			'menu_name'          => __( 'Voucher', 'dmc-voucher' ),
			'add_new'            => __( 'Thêm voucher', 'dmc-voucher' ),
			'add_new_item'       => __( 'Thêm voucher mới', 'dmc-voucher' ),
			'edit_item'          => __( 'Sửa voucher', 'dmc-voucher' ),
			'new_item'           => __( 'Voucher mới', 'dmc-voucher' ),
			'view_item'          => __( 'Xem voucher', 'dmc-voucher' ),
			'search_items'       => __( 'Tìm voucher', 'dmc-voucher' ),
			'not_found'          => __( 'Không có voucher', 'dmc-voucher' ),
			'not_found_in_trash' => __( 'Không có voucher trong thùng rác', 'dmc-voucher' ),
		];

		register_post_type(
			'voucher',
			[
				'labels'              => $labels,
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'menu_position'       => 56,
				'menu_icon'           => 'dashicons-tickets-alt',
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'hierarchical'        => false,
				'supports'            => [ 'title' ],
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'show_in_rest'        => false,
			]
		);
	}

	/**
	 * @param array<string, string> $columns Columns.
	 * @return array<string, string>
	 */
	public static function columns( $columns ) {
		$new = [];
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['voucher_code']    = __( 'Mã', 'dmc-voucher' );
				$new['voucher_type']    = __( 'Loại', 'dmc-voucher' );
				$new['voucher_value']   = __( 'Giá trị', 'dmc-voucher' );
				$new['voucher_min']     = __( 'Đơn tối thiểu', 'dmc-voucher' );
				$new['voucher_status']  = __( 'Trạng thái', 'dmc-voucher' );
				$new['voucher_usage']   = __( 'Đã dùng', 'dmc-voucher' );
			}
		}

		return $new;
	}

	/**
	 * @param string $column Column key.
	 * @param int    $post_id Post ID.
	 */
	public static function column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'voucher_code':
				echo esc_html( get_post_meta( $post_id, '_dmc_voucher_code', true ) );
				break;
			case 'voucher_type':
				$type = get_post_meta( $post_id, '_dmc_voucher_type', true );
				echo 'percent' === $type ? esc_html__( 'Phần trăm', 'dmc-voucher' ) : esc_html__( 'Cố định', 'dmc-voucher' );
				break;
			case 'voucher_value':
				echo wp_kses_post( DMC_Voucher_Engine::format_voucher_amount( $post_id ) );
				break;
			case 'voucher_min':
				$min = (float) get_post_meta( $post_id, '_dmc_voucher_min_spend', true );
				echo $min > 0 ? wp_kses_post( wc_price( $min ) ) : '—';
				break;
			case 'voucher_status':
				$active = DMC_Voucher_Engine::is_voucher_active( $post_id );
				echo $active
					? '<span style="color:#227a1b;">' . esc_html__( 'Đang hoạt động', 'dmc-voucher' ) . '</span>'
					: '<span style="color:#b32d2e;">' . esc_html__( 'Không hoạt động', 'dmc-voucher' ) . '</span>';
				break;
			case 'voucher_usage':
				$used  = (int) get_post_meta( $post_id, '_dmc_voucher_usage_count', true );
				$limit = (int) get_post_meta( $post_id, '_dmc_voucher_usage_limit', true );
				echo esc_html( $limit > 0 ? "{$used}/{$limit}" : (string) $used );
				break;
		}
	}
}
