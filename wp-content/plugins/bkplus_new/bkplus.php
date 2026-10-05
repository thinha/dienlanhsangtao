<?php
/*
Plugin Name: Baokim Plus
Plugin URI: http://plus.baokim.vn/
Description: Giải pháp thanh toán trực tuyến trên các website thương mại điện tử.
Version: 1.0
Author: PhungHT
Author URI: 
*/
?>
<?php
/**
@ Chèn CSS và Javascript vào theme
@ sử dụng hook wp_enqueue_scripts() để hiển thị nó ra ngoài front-end
**/
function wp_include_bk_css_js() {
	/**
	$handle: Tên của style (Tên này phải đặt duy nhất)
	$src: Đường dẫn đến file CSS
	$deps: Mảng chứa tên style phụ thuộc (Tên style phụ thuộc phải được đăng ký trước. Khi load WordPress sẽ load đối tượng được phụ thuộc trước)
	$ver: Phiên bản của style (Nếu để giá trị false, hệ thống sẽ tự lấy theo phiên bản của WordPress)
	$media: Media của style (Ví dụ: 'all', 'aural', 'braille', 'handheld', 'projection', 'print')
	**/
	wp_enqueue_style( 'bk-popup', 'https://pc.baokim.vn/css/bk.css');

	/**
	$handle: Tên của script (Tên này phải đặt tên duy nhất)
	$src: Đường dẫn tới file js
	$deps: Mảng chứa tên script phụ thuộc (Tên script phụ thuộc phải được đăng ký trước. Khi load WordPress sẽ load đối tượng được phụ thuộc trước)
	$ver: Phiên bản của script (Nếu để giá trị false, hệ thống sẽ tự lấy theo phiên bản của WordPress)
	$in_footer: Chuyển script xuống footer nếu giá trị là true
	**/

// 	wp_enqueue_script('bk-popup', 'https://pc.baokim.vn/js/bk_plus_v2.popup.js', [], false, true);
}
add_action( 'wp_enqueue_scripts', 'wp_include_bk_css_js', 20, 1);

if ( ! defined( 'DLS_BK_MERCHANT_DOMAIN' ) ) {
	define( 'DLS_BK_MERCHANT_DOMAIN', 'dienlanhsangtao.com' );
}

/**
--------------
Trang chi tiết
--------------
**/

/**
Thêm nút btn và modal vào trang chi tiết
**/
function baokim_btn_detail(){
	?>
	<div class="bk-btn dls-bk-installment" style="margin-top: 12px"></div>
	<?php
}
add_action('woocommerce_after_add_to_cart_button','baokim_btn_detail');

add_filter( 'woocommerce_quantity_input_classes', 'dls_bk_quantity_input_classes', 10, 2 );
function dls_bk_quantity_input_classes( $classes, $product ) {
	if ( function_exists( 'is_product' ) && is_product() ) {
		$classes[] = 'bk-product-qty';
	}
	return $classes;
}

/**
Dữ liệu sản phẩm để script Baokim đọc giá, tên, ảnh.
Trang chi tiết tùy chỉnh không gọi woocommerce_after_single_product.
**/
function dls_bk_print_product_data() {
	static $printed = false;
	if ( $printed || ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	global $product;
	if ( ! $product instanceof WC_Product ) {
		$product = wc_get_product( get_queried_object_id() );
	}
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$printed = true;
	$id      = $product->get_id();
	$price   = (int) round( (float) $product->get_price() );
	$image   = get_the_post_thumbnail_url( $id, 'medium' );
	if ( ! $image ) {
		$image = wc_placeholder_img_src( 'medium' );
	}

	if ( method_exists( $product, 'get_stock_status' ) ) {
		$stock_status = $product->get_stock_status();
	} else {
		$stock_status = $product->stock_status;
	}
	$list_stock = [
		'instock'     => 'Trong kho',
		'outofstock'  => 'Hết hàng',
		'onbackorder' => 'Đặt trước',
		'contact'     => 'Liên hệ',
		'preorder'    => 'Đặt hàng trước',
	];
	?>
	<div class="dls-bk-product-data" hidden>
		<p class="bk-product-price"><?php echo esc_html( (string) $price ); ?></p>
		<p class="bk-product-name"><?php echo esc_html( $product->get_name() ); ?></p>
		<img class="bk-product-image" src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>">
		<p class="bk-check-out-of-stock"><?php echo esc_html( isset( $list_stock[ $stock_status ] ) ? $list_stock[ $stock_status ] : '' ); ?></p>
	</div>
	<script>
		if (typeof meta === 'undefined') {
			var meta = { product: { id: <?php echo (int) $id; ?> } };
		}
	</script>
	<?php
}

/**
Chèn class vào trang
**/
function hook_javascript_footer() {
	?>
	<script src="https://pc.baokim.vn/js/bk_plus_v2.popup.js"></script>
	<style>
		#bk-btn-paynow, #bk-btn-installment, .bk-btn-paynow, .bk-btn-installment {
			outline: none;
		}
		#bk-modal-close, #bk-modal-notify-close {
			margin: 0;
			padding: 0;
			outline: none;
		}
		.dmc-product-detail-page .dls-bk-installment,
		.dmc-product-detail-page .bk-btn {
			width: 100%;
		}
		.dmc-product-detail-page .bk-btn-box {
			display: block;
			width: 100%;
		}
		.dmc-product-detail-page .bk-btn-paynow {
			display: none !important;
		}
		.dmc-product-detail-page .bk-btn-installment {
			width: 100%;
			min-height: 54px;
			margin: 0;
			padding: 8px 16px;
			border: 0;
			border-radius: 12px;
			box-sizing: border-box;
			cursor: pointer;
			box-shadow: 0 8px 20px rgba(7, 88, 201, 0.22);
		}
		.dmc-product-detail-page .bk-btn-installment strong,
		.dmc-product-detail-page .bk-btn-installment span {
			display: block;
			text-align: center;
		}
		.dmc-product-detail-page .bk-btn-installment strong {
			font-size: 14px;
			font-weight: 900;
			letter-spacing: 0.04em;
			text-transform: uppercase;
			line-height: 1.2;
		}
		.dmc-product-detail-page .bk-btn-installment span {
			margin-top: 3px;
			font-size: 10px;
			font-weight: 600;
			letter-spacing: 0.02em;
			text-transform: uppercase;
			opacity: 0.9;
			line-height: 1.2;
		}
	</style>
	<script type="text/javascript">
		var productQuantityClass = document.getElementsByClassName("product-quantity");
        for(var i = 0; i < productQuantityClass.length; i++) {
            if(productQuantityClass[i].querySelector('.input-text')) {
                productQuantityClass[i].querySelector('.input-text').classList.add("bk-product-qty");
            }
        }
        var singleQty = document.querySelectorAll('form.cart input.qty');
        for (var q = 0; q < singleQty.length; q++) {
            singleQty[q].classList.add('bk-product-qty');
        }
	</script>
	<?php
}
add_action( 'wp_head', 'dls_bk_alias_merchant_domain', 1 );
function dls_bk_alias_merchant_domain() {
	if ( is_admin() ) {
		return;
	}
	$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
	$host = preg_replace( '/:\d+$/', '', $host );
	$host = preg_replace( '/^www\./', '', $host );
	if ( $host === DLS_BK_MERCHANT_DOMAIN ) {
		return;
	}
	$domain = DLS_BK_MERCHANT_DOMAIN;
	?>
	<script>
		(function () {
			var merchantDomain = <?php echo wp_json_encode( $domain ); ?>;
			var originalOpen = XMLHttpRequest.prototype.open;
			var originalSend = XMLHttpRequest.prototype.send;
			XMLHttpRequest.prototype.open = function (method, url) {
				this.__dlsBkUrl = url;
				return originalOpen.apply(this, arguments);
			};
			XMLHttpRequest.prototype.send = function (body) {
				var url = String(this.__dlsBkUrl || '');
				if (url.indexOf('baokim.vn') !== -1) {
					if (typeof FormData !== 'undefined' && body instanceof FormData && body.has('website')) {
						body.set('website', merchantDomain);
					} else if (typeof body === 'string') {
						try {
							var payload = JSON.parse(body);
							if (payload && Object.prototype.hasOwnProperty.call(payload, 'domain')) {
								payload.domain = merchantDomain;
								body = JSON.stringify(payload);
							}
						} catch (error) {}
					}
				}
				return originalSend.call(this, body);
			};
		})();
	</script>
	<?php
}

add_action( 'wp_footer', 'dls_bk_product_page_assets', 20 );
function dls_bk_product_page_assets() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	dls_bk_print_product_data();
	hook_javascript_footer();
}

/**
Chèn class vào trang lấy thuộc tính trang chi tiết
**/
add_filter( 'woocommerce_dropdown_variation_attribute_options_args', static function( $args ) {
	$args['class'] = 'bk-product-property';
	return $args;
}, 2 );

add_filter( 'woocommerce_variation_options_pricing', static function( $args ) {
	$args['class'] = 'bk-product-property';
	return $args;
}, 2 );



/**
--------------
Trang giỏ hàng
--------------
**/

/**
Thêm nút btn vào trang giỏ hàng
**/
// woocommerce_after_cart
function baokim_btn_cart(){
	?>
	<div class="bk-btn" style="margin-top: 10px">
	
	</div>
	<?php
}
add_action('woocommerce_proceed_to_checkout','baokim_btn_cart');

/**
Chèn modal, class vào trang cart
**/
function hook_modal_javascript_cart() {
	?>
	<script src="https://pc.baokim.vn/js/bk_plus_v2.popup.js"></script>
	<style>
		#bk-btn-paynow, #bk-btn-installment, .bk-btn-paynow, .bk-btn-installment {
			outline: none;
		}
		#bk-modal-close, #bk-modal-notify-close {
			margin: 0;
			padding: 0;
			outline: none;
		}
	</style>
	<script type="text/javascript">
		var productImageClass = document.getElementsByClassName("product-thumbnail");
        for(var i = 0; i < productImageClass.length; i++) {
            if(productImageClass[i].querySelector('img')) {
                productImageClass[i].querySelector('img').classList.add("bk-product-image");
            }
        }

        var productNameClass = document.getElementsByClassName("product-name");
        for(var i = 0; i < productNameClass.length; i++) {
            if(productNameClass[i].querySelector('a')) {
                productNameClass[i].querySelector('a').classList.add("bk-product-name");
            }
        }

        var productPriceClass = document.getElementsByClassName("product-price");
        for(var i = 0; i < productPriceClass.length; i++) {
            if(productPriceClass[i].querySelector('.amount')) {
                productPriceClass[i].querySelector('.amount').classList.add("bk-product-price");
            }
        }

        var productQuantityClass = document.getElementsByClassName("product-quantity");
        for(var i = 0; i < productQuantityClass.length; i++) {
            if(productQuantityClass[i].querySelector('.input-text')) {
                productQuantityClass[i].querySelector('.input-text').classList.add("bk-product-qty");
            }
        }
	</script>
	<?php
}
add_action('woocommerce_after_cart', 'hook_modal_javascript_cart');

add_action('wp_footer', 'wpshout_action_example'); 
function wpshout_action_example() {
	?>
	<div id='bk-modal'></div>
	<script>
		window.addEventListener("load", function(event) {
			var btnCloseModal = document.getElementById('bk-modal-close');
			if (btnCloseModal) {
				btnCloseModal.addEventListener("click", function(){
					location.reload();
				});
			}
			if (window.jQuery) {
				jQuery( '.variations_form' ).each( function() {
					jQuery(this).on( 'found_variation', function( event, variation ) {
						var priceNode = document.getElementsByClassName('bk-product-price')[0];
						if (priceNode) {
							priceNode.innerHTML = String(Math.round(variation.display_price));
						}
					});
				});
			}
		});
	</script>
	<?php
}
