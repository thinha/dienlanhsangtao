<?php
include_once "framework/constants.php";
require_once get_stylesheet_directory() . '/includes/commons/setup.php';
require_once get_stylesheet_directory() . '/includes/homepage/setup.php';
require_once get_stylesheet_directory() . '/includes/product-list/setup.php';
require_once get_stylesheet_directory() . '/includes/account/setup.php';
require_once get_stylesheet_directory() . '/includes/cart/setup.php';
require_once get_stylesheet_directory() . '/includes/checkout/setup.php';
require_once get_stylesheet_directory() . '/includes/exam/setup.php';
require_once get_stylesheet_directory() . '/includes/about/setup.php';

// Add custom Theme Functions here
add_filter( 'woocommerce_product_tabs', 'woo_remove_product_tabs', 98 );

function woo_remove_product_tabs( $tabs ) {
    unset( $tabs['additional_information'] );  	// Remove the additional information tab
    return $tabs;
}

add_filter( 'woocommerce_product_tabs', 'woo_rename_tabs', 98 );
function woo_rename_tabs( $tabs ) {
	$tabs['description']['title'] = __(NULL);		// Rename the description tab
	$tabs['reviews']['title'] = __(NULL);				// Rename the reviews tab
	$tabs['additional_information']['title'] = __(NULL);	// Rename the additional information tab
	return $tabs;
}


// always display rating stars
function filter_woocommerce_product_get_rating_html( $rating_html, $rating, $count ) { 
    $rating_html  = '<div class="star-rating">';
    $rating_html .= wc_get_star_rating_html( $rating, $count );
    $rating_html .= '</div>';
    return $rating_html; 
};  
add_filter( 'woocommerce_product_get_rating_html', 'filter_woocommerce_product_get_rating_html', 10, 3 );


/*Sale price by - NguyenThi*/
function dmc_get_shipping_locations() {
	if ( function_exists( 'dmc_tmp_get_shipping_locations' ) ) {
		return dmc_tmp_get_shipping_locations();
	}

	if ( ! defined( 'SHIPPING_FEE' ) ) {
		return [];
	}

	$locations = [];

	foreach ( SHIPPING_FEE as $name => $data ) {
		$fee = (int) ( $data['fee'] ?? 0 );
		$locations[ $name ] = [
			'fee'     => $fee,
			'fee_car' => (int) ( $data['fee_car'] ?? round( $fee * 1.5 ) ),
		];
	}

	return $locations;
}

function flatsome_price_html($product, $is_variation = false){
    $originalSalePrice  = get_post_meta($product->get_id(), 'set_shipping_fee', true);
    if ($originalSalePrice) {
        $product->set_sale_price($originalSalePrice);
        $product->save();
        delete_post_meta($product->get_id(), 'set_shipping_fee');
    }

    ob_start();
    if($product->is_on_sale() &&($product->is_type( 'simple' ) || $product->is_type( 'external' ) || $is_variation) ) {
		$raw_sale_price      = (float) $product->get_sale_price();
		$sale_price_customer = $raw_sale_price;
		$voucher_discount    = 0;
		$voucher_code        = '';
		if ( class_exists( 'DMC_Voucher_Session' ) && class_exists( 'DMC_Voucher_Engine' ) ) {
			$voucher_code = DMC_Voucher_Session::get_applied_code();
			if ( $voucher_code ) {
				$voucher = DMC_Voucher_Engine::get_voucher_by_code( $voucher_code );
				if ( $voucher ) {
					$valid = DMC_Voucher_Engine::validate_for_product( $voucher->ID, $product->get_id(), $raw_sale_price );
					if ( ! is_wp_error( $valid ) ) {
						$voucher_discount    = DMC_Voucher_Engine::calculate_discount( $voucher->ID, $raw_sale_price, 1 );
						$sale_price_customer = max( 0, $raw_sale_price - $voucher_discount );
					}
				}
			}
		}
        $regular_price = $product->get_regular_price();
        if($regular_price) {
            $sale        = round( ( ( floatval( $regular_price ) - floatval( $sale_price_customer ) ) / floatval( $regular_price ) ) * 100 );
            $sale_amout  = $regular_price - $sale_price_customer;
            $shipping_id        = 'shipping_fee_' . $product->get_id();
            $shipping_locations = dmc_get_shipping_locations();
            $delivery_type      = dmc_pl_get_delivery_type( $product->get_id() );
            $delivery_label     = dmc_pl_get_delivery_type_label( $delivery_type );
            $delivery_icon      = dmc_pl_get_delivery_icon_slug( $delivery_type );
            $fee_key            = dmc_pl_get_delivery_fee_key( $delivery_type );
            $fee_label_prefix   = sprintf( __( 'Phí giao hàng (%s)', 'flatsome-child' ), $delivery_label );
            ?>
            <div class="pl-price pl-price--delivery-<?php echo esc_attr( $delivery_type ); ?>" data-base-price="<?php echo esc_attr( $raw_sale_price ); ?>" data-original-base-price="<?php echo esc_attr( $raw_sale_price ); ?>" data-voucher-discount="<?php echo esc_attr( $voucher_discount ); ?>" data-voucher-code="<?php echo esc_attr( $voucher_code ); ?>" data-delivery-type="<?php echo esc_attr( $delivery_type ); ?>" data-product-id="<?php echo esc_attr( (string) $product->get_id() ); ?>" data-fee-label-prefix="<?php echo esc_attr( $fee_label_prefix ); ?>">
                <p class="pl-price__preview-note"><?php esc_html_e( 'Ước tính cho sản phẩm này — voucher & phí ship chính thức được áp dụng tại giỏ hàng.', 'flatsome-child' ); ?></p>
                <div class="pl-price__main">
                    <div class="pl-price__row pl-price__row--regular">
                        <span class="pl-price__label"><?php esc_html_e( 'Giá niêm yết', 'flatsome-child' ); ?></span>
                        <del class="pl-price__value pl-price__value--old"><?php echo wc_price( $regular_price ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></del>
                    </div>
                    <div class="pl-price__row pl-price__row--sale">
                        <span class="pl-price__label"><?php esc_html_e( 'Giá khuyến mãi', 'flatsome-child' ); ?></span>
                        <span class="pl-price__value pl-price__value--sale" id="pl-base-sale-price"><?php echo wc_price( $raw_sale_price ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                    </div>
                    <div class="pl-price__savings">
                        <span class="pl-price__savings-label"><?php esc_html_e( 'Tiết kiệm', 'flatsome-child' ); ?></span>
                        <span class="pl-price__savings-value"><?php echo wc_price( $sale_amout ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <em>(<?php echo esc_html( $sale ); ?>%)</em></span>
                    </div>
                </div>

                <div class="pl-price__shipping">
                    <label class="pl-price__shipping-label" for="<?php echo esc_attr( $shipping_id ); ?>">
                        <?php echo dmc_icon( $delivery_icon, [ 'size' => 16, 'variant' => 'blue', 'class' => 'pl-price__shipping-icon' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <span class="pl-price__shipping-text"><?php esc_html_e( 'Giao hàng đến', 'flatsome-child' ); ?></span>
                        <?php dmc_pl_render_delivery_badge( $product->get_id(), 'pl-price__delivery-badge' ); ?>
                    </label>
                    <select class="js-example-basic-single pl-price__shipping-select" id="<?php echo esc_attr( $shipping_id ); ?>" name="shipping_fee" data-delivery-type="<?php echo esc_attr( $delivery_type ); ?>">
                        <option value="" selected><?php echo esc_html( sprintf( __( 'Chọn khu vực — %s', 'flatsome-child' ), $delivery_label ) ); ?></option>
                        <?php foreach ( $shipping_locations as $location => $value ) : ?>
                            <?php $location_fee = dmc_pl_resolve_location_fee( $value, $delivery_type ); ?>
                            <option value="<?php echo esc_attr( (string) $location_fee ); ?>" data-location="<?php echo esc_attr( $location ); ?>">
                                <?php
                                echo esc_html( $location );
                                echo ' — ';
                                echo wp_strip_all_tags( wc_price( $location_fee ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="pl-price__breakdown" id="pl-price-breakdown"<?php echo $voucher_discount > 0 ? '' : ' hidden'; ?>>
                    <div class="pl-price__row pl-price__row--item">
                        <span class="pl-price__label"><?php esc_html_e( 'Giá khuyến mãi', 'flatsome-child' ); ?></span>
                        <span class="pl-price__value pl-price__value--item" id="pl-breakdown-sale"><?php echo wc_price( $raw_sale_price ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                    </div>
                    <div class="pl-price__row pl-price__row--item pl-price__row--voucher" id="pl-voucher-discount-row"<?php echo $voucher_discount > 0 ? '' : ' hidden'; ?>>
                        <span class="pl-price__label" id="pl-voucher-discount-label">
                            <?php
                            echo esc_html(
                                $voucher_code
                                    ? sprintf( __( 'Voucher (%s)', 'flatsome-child' ), $voucher_code )
                                    : __( 'Giảm voucher', 'flatsome-child' )
                            );
                            ?>
                        </span>
                        <span class="pl-price__value pl-price__value--item pl-price__value--voucher" id="pl-voucher-discount-value">
                            <?php echo $voucher_discount > 0 ? '-' . wp_strip_all_tags( wc_price( $voucher_discount ) ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </span>
                    </div>
                    <div class="pl-price__row pl-price__row--item pl-price__row--shipping" id="pl-shipping-fee-row" hidden>
                        <span class="pl-price__label" id="pl-shipping-fee-label"><?php echo esc_html( $fee_label_prefix ); ?></span>
                        <span class="pl-price__value pl-price__value--item pl-price__value--shipping" id="pl-shipping-fee-value"></span>
                    </div>
                    <div class="pl-price__row pl-price__row--total">
                        <span class="pl-price__label pl-price__label--total"><?php esc_html_e( 'Tổng giá bán', 'flatsome-child' ); ?></span>
                        <span class="pl-price__value pl-price__value--total" id="price_final"></span>
                    </div>
                </div>

                <input type="hidden" name="price_final" value="<?php echo esc_attr( $sale_price_customer ); ?>">
                <input type="hidden" name="shipping_location" value="">
                <input type="hidden" name="delivery_type" value="<?php echo esc_attr( $delivery_type ); ?>">
            </div>
            <?php
        }
    }else{
        ?>
        <p class="<?php echo esc_attr( apply_filters( 'woocommerce_product_price_class', 'price' ) );?>"><?php echo $product->get_price_html(); ?></p>
        <?php
    }
    return ob_get_clean();
}

function update_cart_price_based_on_district() {
    // Preview-only: giá hiển thị được tính ở JS; không ghi đè sale_price sản phẩm.
    if ( ! isset( $_POST['priceFinal'], $_POST['price'], $_POST['productId'] ) || ! is_numeric( $_POST['priceFinal'] ) ) {
        wp_send_json_error( 'Missing data' );
    }

    $product = wc_get_product( (int) $_POST['productId'] );
    if ( ! $product ) {
        wp_send_json_error( 'Missing product' );
    }

    wp_send_json_success();
}
add_action('wp_ajax_update_cart_price_based_on_district', 'update_cart_price_based_on_district');
add_action('wp_ajax_nopriv_update_cart_price_based_on_district', 'update_cart_price_based_on_district');

function woocommerce_template_single_price(){
    global $product;
    echo flatsome_price_html($product);
}
 
add_filter('woocommerce_available_variation','flatsome_woocommerce_available_variation', 10, 3);
function flatsome_woocommerce_available_variation($args, $thisC, $variation){
    $old_price_html = $args['price_html'];
    if($old_price_html){
        $args['price_html'] = flatsome_price_html($variation, true);
    }
    return $args;
}


/*add_action( 'woocommerce_shop_loop_item_title', 'woocommerce_before_shop_loop_item_sku_in_cart', 20, 1);
function woocommerce_before_shop_loop_item_sku_in_cart($template)  {
	global $product;
	$sku = $product->get_sku();
	echo '<div class="sku-wrapper"><span class="sku"> ('.$sku.') </span></div>';
}*/

add_filter( 'woocommerce_reviews_title', 'filter_function_name_3028', 10, 3 );
function filter_function_name_3028( $reviews_title, $count, $product ){
	// filter...
	$reviews_title = "Thông tin đánh giá khách hàng";
	return $reviews_title;
}

add_action('wp_footer','devvn_readmore_flatsome');
function devvn_readmore_flatsome(){
    ?>
    <style>
        .single-product div.product-section {
            overflow: hidden;
            position: relative;
        }
        .single-product .tab-panels div.panel.entry-content:not(.active) {
            height: 0 !important;
        }
        .devvn_readmore_flatsome {
            text-align: center;
            cursor: pointer;
            position: absolute;
            z-index: 9999;
            bottom: 0;
            width: 100%;
            background: #023466;
        }
        .devvn_readmore_flatsome:before {
            height: 126px;
			margin-top: -90px;
            content: "";
            background: -moz-linear-gradient(top, rgba(255,255,255,0) 0%, rgba(255,255,255,1) 30%);
            background: -webkit-linear-gradient(top, rgba(255,255,255,0) 0%,rgba(255,255,255,1) 30%);
            background: linear-gradient(to bottom, rgba(255,255,255,0) 0%,rgba(255,255,255,1) 70%);
            filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ffffff00', endColorstr='#ffffff',GradientType=0 );
            display: block;
        }
        .devvn_readmore_flatsome a {
            color: #fff;
			padding: 9px 0px 9px 0px;
            display: block;
        }
        .devvn_readmore_flatsome a:after {
            content: '';
            width: 0;
            right: 0;
            border-top: 6px solid #318A00;
            border-left: 6px solid transparent;
            border-right: 6px solid transparent;
            display: inline-block;
            vertical-align: middle;
            margin: -2px 0 0 5px;
        }
    </style>
    <script>
        (function($){
            $(document).ready(function(){
                $(window).load(function(){
                    if($('#tab-0').length > 0){
                        var wrap = $('#tab-0');
                        var current_height = wrap.height();
                        var your_height = 450;
                        if(current_height > your_height){
                            wrap.css('height', your_height+'px');
                            wrap.append(function(){
                                return '<div class="devvn_readmore_flatsome"><a title="XEM ĐẦY ĐỦ CHI TIẾT" href="javascript:void(0);">XEM ĐẦY ĐỦ CHI TIẾT</a></div>';
                            });
                            $('body').on('click','.devvn_readmore_flatsome', function(){
                                wrap.removeAttr('style');
                                $('body .devvn_readmore_flatsome').remove();
                            });
                        }
                    }
                });
            })
        })(jQuery)
    </script>
    <?php
}



// add_action('wp_footer','devvn_readmore_taxonomy_flatsome');
function devvn_readmore_taxonomy_flatsome(){
    if(is_woocommerce() && is_tax('product_cat')):
        ?>
        <style>
            .tax-product_cat.woocommerce .shop-container .term-description {
                overflow: hidden;
                position: relative;
                margin-bottom: 20px;
                padding-bottom: 25px;
            }
            .devvn_readmore_taxonomy_flatsome {
                text-align: center;
                cursor: pointer;
                position: absolute;
                z-index: 10;
                bottom: 0;
                width: 100%;
                background: #fff;
            }
            .devvn_readmore_taxonomy_flatsome:before {
                height: 126px;
				margin-top: -90px;
                content: "";
                background: -moz-linear-gradient(top, rgba(255,255,255,0) 0%, rgba(255,255,255,1) 100%);
                background: -webkit-linear-gradient(top, rgba(255,255,255,0) 0%,rgba(255,255,255,1) 100%);
                background: linear-gradient(to bottom, rgba(255,255,255,0) 0%,rgba(255,255,255,1) 100%);
                filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ffffff00', endColorstr='#ffffff',GradientType=0 );
                display: block;
            }
            .devvn_readmore_taxonomy_flatsome a {
                color: #318A00;
				padding: 9px 0px 9px 0px;
                display: block;
            }
            .devvn_readmore_taxonomy_flatsome a:after {
                content: '';
                width: 0;
                right: 0;
                border-top: 6px solid #318A00;
                border-left: 6px solid transparent;
                border-right: 6px solid transparent;
                display: inline-block;
                vertical-align: middle;
                margin: -2px 0 0 5px;
            }
            .devvn_readmore_taxonomy_flatsome_less:before {
                display: none;
            }
            .devvn_readmore_taxonomy_flatsome_less a:after {
                border-top: 0;
                border-left: 6px solid transparent;
                border-right: 6px solid transparent;
                border-bottom: 6px solid #318A00;
            }
        </style>
        <script>
            (function($){
                $(document).ready(function(){
                    $(window).load(function(){
                        if($('.tax-product_cat.woocommerce .shop-container .term-description').length > 0){
                            var wrap = $('.tax-product_cat.woocommerce .shop-container .term-description');
                            var current_height = wrap.height();
                            var your_height = 300;
                            if(current_height > your_height){
                                wrap.css('height', your_height+'px');
                                wrap.append(function(){
                                    return '<div class="devvn_readmore_taxonomy_flatsome devvn_readmore_taxonomy_flatsome_show"><a title="Xem thêm" href="javascript:void(0);">Xem thêm</a></div>';
                                });
                                wrap.append(function(){
                                    return '<div class="devvn_readmore_taxonomy_flatsome devvn_readmore_taxonomy_flatsome_less" style="display: none"><a title="Thu gọn" href="javascript:void(0);">Thu gọn</a></div>';
                                });
                                $('body').on('click','.devvn_readmore_taxonomy_flatsome_show', function(){
                                    wrap.removeAttr('style');
                                    $('body .devvn_readmore_taxonomy_flatsome_show').hide();
                                    $('body .devvn_readmore_taxonomy_flatsome_less').show();
                                });
                                $('body').on('click','.devvn_readmore_taxonomy_flatsome_less', function(){
                                    wrap.css('height', your_height+'px');
                                    $('body .devvn_readmore_taxonomy_flatsome_show').show();
                                    $('body .devvn_readmore_taxonomy_flatsome_less').hide();
                                });
                            }
                        }
                    });
                })
            })(jQuery)
        </script>
    <?php
    endif;
}

add_filter( 'woocommerce_checkout_fields' , 'hidden_field_checkout' );
function hidden_field_checkout( $fields ) {

    $fields['billing']['billing_address_1'] = array(
        'label'       => __( 'Address', 'woocommerce' ),
        'placeholder'     => 'số nhà, đường, phường, quận, tỉnh/thành phố',
        'required'  => true,
        'class'     => array('form-row-wide'),
        'clear'     => true
    );  
    $fields['billing']['billing_email'] = array(
        'label'       => __( 'Email', 'woocommerce' ),
        'placeholder'     => '',
        'required'  => true,
        'class'     => array('form-row-wide'),
        'clear'     => true
    ); 

    $fields['shipping']['shipping_address_1'] = array(
        'label'       => __( 'Address', 'woocommerce' ),
        'placeholder'     => 'số nhà, đường, phường, quận, tỉnh/thành phố',
        'required'  => true,
        'class'     => array('form-row-wide'),
        'clear'     => true
    );  

    unset($fields['billing']['billing_city']);
    unset($fields['billing']['billing_first_name']);
    unset($fields['shipping']['shipping_city']);
    unset($fields['shipping']['shipping_first_name']);

return $fields;
}

//** *Enable upload for webp image files.*/
function topawi_upload_mimes($existing_mimes) {
    $existing_mimes['webp'] = 'image/webp';
    return $existing_mimes;
}
add_filter('mime_types', 'topawi_upload_mimes');

//** * Enable preview / thumbnail for webp image files.*/
function topawi_is_displayable($result, $path) {
    if ($result === false) {
        $displayable_image_types = array( IMAGETYPE_WEBP );
        $info = @getimagesize( $path );

        if (empty($info)) {
            $result = false;
        } elseif (!in_array($info[2], $displayable_image_types)) {
            $result = false;
        } else {
            $result = true;
        }
    }

    return $result;
}
add_filter('file_is_displayable_image', 'topawi_is_displayable', 10, 2);


function add_menu_brand() {

    $term = get_queried_object();
    $slug = $term->slug;

    if( $slug === 'tu-dong' ) {

        ob_start();
        $content =  '
            <ul class="brand-list">
                <li>
                    <div class="item">
                        <a href="/collections/tu-dong-darling">
                            <img src="https://dienlanhsangtao.com/wp-content/uploads/2022/06/logo-darling.webp" alt="Sanaky" />
                        </a>
                    </div>
                </li>
                 <li>
                    <div class="item">
                        <a href="/collections/tu-dong-sanaky">
                            <img src="https://dienlanhsangtao.com/wp-content/uploads/2022/06/logo-sanaky.webp" alt="Sanaky" />
                        </a>
                    </div>
                </li>
            </ul>
        ';

        echo $content;
        $page = ob_get_contents();
        ob_end_clean();
        echo $page;

    }
}
add_action('woocommerce_before_main_content', 'add_menu_brand', 100);


// ADVANCE FIELDS CUSTOM OPTIONS
function dmc_register_acf_options_pages() {
	if ( ! function_exists( 'acf_add_options_page' ) ) {
		return;
	}

	acf_add_options_page(
		array(
			'page_title' => 'Theme General Settings',
			'menu_title' => 'Theme Settings',
			'menu_slug'  => 'theme-general-settings',
			'capability' => 'edit_posts',
			'redirect'   => false,
		)
	);

	acf_add_options_sub_page(
		array(
			'page_title'  => 'Theme Header Settings',
			'menu_title'  => 'Header',
			'parent_slug' => 'theme-general-settings',
		)
	);

	acf_add_options_sub_page(
		array(
			'page_title'  => 'Theme Footer Settings',
			'menu_title'  => 'Footer',
			'parent_slug' => 'theme-general-settings',
		)
	);
}
add_action( 'acf/init', 'dmc_register_acf_options_pages' );

function enqueue_select2_assets() {
    wp_enqueue_style(
        'select2-css', 
        'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', 
        array(), 
        null
    );
    wp_enqueue_script(
        'select2-js', 
        'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', 
        array('jquery'), 
        null, 
        true
    );
    wp_enqueue_script(
        'myscript-js', 
        get_stylesheet_directory_uri()  . '/assets/js/myscript.js', 
        array('jquery'), 
        null, 
        true
    );
}
add_action('wp_enqueue_scripts', 'enqueue_select2_assets');