<?php
/**
 * Single Product Thumbnails
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/product-thumbnails.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see       https://docs.woocommerce.com/document/template-structure/
 * @package   WooCommerce/Templates
 * @version     3.5.1
 */

defined( 'ABSPATH' ) || exit;

// FL: Disabled, Note: `wc_get_gallery_image_html` was added in WC 3.3.2 and did not exist prior. This check protects against theme overrides being used on older versions of WC.
//if ( ! function_exists( 'wc_get_gallery_image_html' ) ) {
//	return;
//}

global $product;

$attachment_ids = $product->get_gallery_image_ids();
$image_size     = get_theme_mod( 'product_layout' ) == 'gallery-wide' ? 'full' : 'woocommerce_single';


if ( $attachment_ids && $product->get_image_id() ) {
	foreach ( $attachment_ids as $attachment_id ) {
		echo apply_filters( 'woocommerce_single_product_image_thumbnail_html', flatsome_wc_get_gallery_image_html( $attachment_id, $main_image = false, $image_size ), $attachment_id ); // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
	}
	//Var
	$tag_ids = $product->get_tag_ids();
	$categies = $product->get_category_ids();
	$post_id = get_the_ID();
	$terms = get_the_terms($post_id,'product_cat');
	$brands = get_the_terms($post_id,'pwb-brand');
	$data = [];
	$id_excludes = array(204);
	$id_accepts = array(98,77,174,188);
	$cat_sanaky_ids = array(211);
	if( $brands ){
		foreach( $brands as $brand ){
			array_push($data,$brand->term_id);
		}
	}


  	$image_pro = get_field('promotion_img');
  	$pro_img = '';
  	$html = '';
  	$main_image = true;
  	$image_wrapper_class = $main_image ? 'slide first' : 'slide';
	if( !empty( $image_pro ) ){
		foreach($categies as $category){
	      if($category === 98  || $category === 77 || $category === 174 || $category === 188){
	        $image_wrapper_class = $main_image ? 'slide first' : 'slide';
	         $html  .= '<div data-thumb="' . esc_url( $image_pro['url']  ) . '" class="woocommerce-product-gallery__image '.$image_wrapper_class.'"><a href="' . esc_url(  $image_pro['url'] ) . '"><img src="'. $image_pro['url'].'" alt="qua tặng Darling"></a></div>';
	      }
	    }
	} else {
	    foreach($categies as $category_id){
			if(  in_array($category_id, $id_accepts) ){

				//Exclude id 
				foreach( $id_excludes as $id_exclude){
					if ( !in_array($id_exclude, $data) ){
						$pro_img .= '<img src="https://dienlanhsangtao.com/wp-content/uploads/2023/04/mua-darling-tang-sang-tao.png" alt="qua tặng Darling">';
					}
				}

			} else if (in_array($category_id, $cat_sanaky_ids)) {
				$pro_img .= '<img src="https://dienlanhsangtao.com/wp-content/uploads/2022/08/gift-sanaky.jpg" style="width:50px; height: 50px;" alt="qua tặng Sanaky">';
			}
		}
	}
 	echo $html;
}
