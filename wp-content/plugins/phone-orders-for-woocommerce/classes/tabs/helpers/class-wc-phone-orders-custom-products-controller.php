<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Phone_Orders_Custom_Products_Controller {
	/**
	 * @return WC_Product_Simple
	 */
	public function create_custom_product() {
		$custom_product = new WC_Product_Simple();
		$custom_product->set_props( array(
			'name'               => "Custom PO product",
			'slug'               => "",
			'status'             => 'publish',
			'catalog_visibility' => 'hidden',
			'sku'                => "",
			'regular_price'      => "",
			'sale_price'         => "",
			'price'              => "",
			'total_sales'        => '0',
			'stock_status'       => 'instock',
			'weight'             => "",
			'length'             => "",
			'width'              => "",
			'height'             => "",
			'purchase_note'      => "",
			'virtual'            => 'no',
			'downloadable'       => 'no',
		) );

		$custom_product->save();

		return $custom_product;
	}

	/**
	 * @param WC_Product $product
	 *
	 * @return bool
	 */
	public function is_custom_product( $product ) {
		return false;
	}
}