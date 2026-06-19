<?php

global $post, $product;

$attachment_ids = $product->get_gallery_image_ids();
$thumb_count    = count( $attachment_ids ) + 1;

// Disable thumbnails if there is only one extra image.
if ( $thumb_count == 1 ) {
	return;
}

$rtl              = 'false';
$thumb_cell_align = 'left';

if ( is_rtl() ) {
	$rtl              = 'true';
	$thumb_cell_align = 'right';
}

if ( $attachment_ids ) {
	$loop          = 0;
	$columns       = apply_filters( 'woocommerce_product_thumbnails_columns', 4 );
	$image_size    = 'thumbnail';
	$gallery_class = array( 'product-thumbnails', 'thumbnails' );

	// Check if custom gallery thumbnail size is set and use that.
	$image_check = wc_get_image_size( 'gallery_thumbnail' );
	if ( $image_check['width'] !== 100 ) {
		$image_size = 'gallery_thumbnail';
	}

	$gallery_thumbnail = wc_get_image_size( apply_filters( 'woocommerce_gallery_thumbnail_size', 'woocommerce_' . $image_size ) );

	if ( $thumb_count <= 5 ) {
		$gallery_class[] = 'slider-no-arrows';
	}

	$gallery_class[] = 'slider row row-small row-slider slider-nav-small small-columns-4';
	?>
	<div class="<?php echo implode( ' ', $gallery_class ); ?>"
		data-flickity-options='{
			"cellAlign": "<?php echo $thumb_cell_align; ?>",
			"wrapAround": false,
			"autoPlay": false,
			"prevNextButtons": true,
			"asNavFor": ".product-gallery-slider",
			"percentPosition": true,
			"imagesLoaded": true,
			"pageDots": false,
			"rightToLeft": <?php echo $rtl; ?>,
			"contain": true
		}'>
		<?php


		if ( has_post_thumbnail() ) :
			?>
			<div class="col is-nav-selected first">
				<a>
					<?php
					$image_id  = get_post_thumbnail_id( $post->ID );
					$image     = wp_get_attachment_image_src( $image_id, apply_filters( 'woocommerce_gallery_thumbnail_size', 'woocommerce_' . $image_size ) );
					$image_alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
					$image     = '<img src="' . $image[0] . '" alt="' . $image_alt . '" width="' . $gallery_thumbnail['width'] . '" height="' . $gallery_thumbnail['height'] . '" class="attachment-woocommerce_thumbnail" />';

					echo $image;
					?>
				</a>
			</div>
			<?php
		endif;

		foreach ( $attachment_ids as $attachment_id ) {

			$classes     = array( '' );
			$image_class = esc_attr( implode( ' ', $classes ) );
			$image       = wp_get_attachment_image_src( $attachment_id, apply_filters( 'woocommerce_gallery_thumbnail_size', 'woocommerce_' . $image_size ) );
			$image_alt   = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
			$image       = '<img src="' . $image[0] . '" alt="' . $image_alt . '" width="' . $gallery_thumbnail['width'] . '" height="' . $gallery_thumbnail['height'] . '"  class="attachment-woocommerce_thumbnail" />';

			echo apply_filters( 'woocommerce_single_product_image_thumbnail_html', sprintf( '<div class="col"><a>%s</a></div>', $image ), $attachment_id, $post->ID, $image_class );

			$loop ++;
		}

		?>
		<div class="col is-nav-selected first">
			<a>
				<?php
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
					if( !empty( $image_pro ) ){
					    $pro_img .= '<img src="'.esc_url($image_pro['url']) .'" alt="'.esc_attr($image_pro['alt']).'" />';
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
            						$pro_img .= '<img src="https://dienlanhsangtao.com/wp-content/uploads/2022/08/gift-sanaky.jpg" alt="qua tặng Sanaky">';
            				}
						}
					}

					echo $pro_img;
				?>
			</a>
		</div>
	</div>
	<?php
} ?>
