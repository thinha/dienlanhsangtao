<?php
include_once "framework/constants.php";

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
function flatsome_price_html($product, $is_variation = false){
    $originalSalePrice  = get_post_meta($product->get_id(), 'set_shipping_fee', true);
    if ($originalSalePrice) {
        $product->set_sale_price($originalSalePrice);
        $product->save();
        delete_post_meta($product->get_id(), 'set_shipping_fee');
    }

    ob_start();
    if($product->is_on_sale() &&($product->is_type( 'simple' ) || $product->is_type( 'external' ) || $is_variation) ) {
		$sale_price_customer = $product->get_sale_price();
        $regular_price = $product->get_regular_price();
        if($regular_price) {
            $sale = round(((floatval($regular_price) - floatval($sale_price_customer)) / floatval($regular_price)) * 100);
            $sale_amout = $regular_price - $sale_price_customer;
            ?>
            <div>
				<div>
                    <span>Giá công ty:</span>
                    <del><?php echo wc_price($regular_price); ?></del>
                </div>
                <div>
					<span>Giá Khuyến Mãi:</span>
                    <?php echo wc_price($sale_price_customer); ?>
                </div>
                <div>
                    <span>Tiết kiệm:</span>
                    <span> <?php echo wc_price($sale_amout); ?> (<?php echo $sale; ?>%)</span>
                </div>
                <div>
					<span>Giao hàng đến (Xe Máy)</span>
                    <select class="js-example-basic-single" name="shipping_fee">
                        <option value="" selected>Quý khách vui lòng chọn nơi giao hàng ?</option>
                        <?php foreach (SHIPPING_FEE as $location => $value) : ?>
                        <option value="<?= $value['fee'] ?? null ?>"><?= $location ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="padding: 10px 0;">
					<div class="include-shipping-fee" style="display:none;">Tổng Giá Bán (bao gồm vận chuyển)</div>
                    <div id="price_final" style="color:#D70B00; font-weight:700; font-size: 24px;"></div>
                    <input type="hidden" name="price_final" value="<?= $sale_price_customer ?>"?>
                </div>
                
            </div>
            <script>
                jQuery(document).ready(function($){
                    const overlay = document.createElement('div');
                    const parent = document.querySelector('form.cart');
                    parent.style.position = 'relative';
                    overlay.style.position = 'absolute';
                    overlay.style.top = '0';
                    overlay.style.left = '0';
                    overlay.style.width = '100%';
                    overlay.style.height = '100%';
                    overlay.style.backgroundColor = 'rgba(0, 0, 0, 0)';
                    overlay.style.zIndex = '1000';
                    parent.append(overlay);

                    $(overlay).on('click', function(){
                        const shippingFee = $('select[name="shipping_fee"]').val();
                        if (!shippingFee) {
                            $('.select2-container .select2-selection').css('border','2px solid red');
                            $('html, body').animate({
                                scrollTop: $('select[name="shipping_fee"]').offset().top - (119 + 100)
                            }, "fast");
                        }
                    });

                    $('form.cart').on('click', '.bk-btn-paynow', function(){
                        if (<?= wp_is_mobile() ?>) {
                            setTimeout(function(){
                                window.location.reload();
                            },1000);
                            
                        }
                    });

                    $('select[name="shipping_fee"]').on('input',function(){
                        const price = parseInt($('input[name="price_final"]').val());
                        const shipping_fee = parseInt($(this).val());
                        const priceFinal = parseInt(price+shipping_fee);
                        let formattedNumber = priceFinal.toLocaleString('vi-VN', { style: 'currency', currency: 'VND' });
                        $('#price_final').html(' '+formattedNumber);
                        $('.include-shipping-fee').removeAttr('style');
                        $('.bk-product-price').html(priceFinal);
                        $.ajax({
                            url:  "<?php echo admin_url( 'admin-ajax.php' ); ?>",  // URL của AJAX trong WooCommerce
                            type: 'POST',
                            data: {
                                action: 'update_cart_price_based_on_district',
                                price: price,
                                priceFinal: priceFinal,
                                productId: "<?= $product->get_id() ?>"
                            },
                            success: function(response) {
                                if (response.success) {
                                    $('.select2-container .select2-selection').removeAttr('style');
                                    $(overlay).remove();
                                }
                            }
                        });
                    });
                   
                }); 
            </script>
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
    // Lấy thông tin quận từ AJAX
    if (!isset($_POST['priceFinal']) || !isset($_POST['price']) || !is_numeric($_POST['priceFinal']) || !isset($_POST['productId'])) return wp_send_json_error('Missing data');
    $product = wc_get_product($_POST['productId']);
    if (!$product) return wp_send_json_error('Missing product');

    update_post_meta($_POST['productId'], 'set_shipping_fee', $_POST['price']);
    $product->set_sale_price($_POST['priceFinal']);
    $product->save();
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


// ADVANCE FIELDS CUSSTOM OPTIONS
if( function_exists('acf_add_options_page') ) {
    acf_add_options_page(array(
        'page_title'    => 'Theme General Settings',
        'menu_title'    => 'Theme Settings',
        'menu_slug'     => 'theme-general-settings',
        'capability'    => 'edit_posts',
        'redirect'      => false
    ));
    
    acf_add_options_sub_page(array(
        'page_title'    => 'Theme Header Settings',
        'menu_title'    => 'Header',
        'parent_slug'   => 'theme-general-settings',
    ));
    
    acf_add_options_sub_page(array(
        'page_title'    => 'Theme Footer Settings',
        'menu_title'    => 'Footer',
        'parent_slug'   => 'theme-general-settings',
    ));
}

//CUSTOM SCRIPT
function scripts_init() {
    //Get var flashsale
    $start_hour = get_field('flashsale_hours_start','option');
    $start_minute = get_field('flashsale_minutes_start','option');
    $cron_hour = get_field('flashsale_hours_end','option');
    $cron_minute = get_field('flashsale_minutes_end','option');
    $end_days = get_field('flashsale_days_start','option');
    $cron_days = get_field('flashsale_days_end','option');
    $cron_months = get_field('flashsale_months_end','option');
    
    wp_register_script('flashsale-slick-js', get_stylesheet_directory_uri()  . '/assets/js/slick.js', array('jquery') );
    

    wp_register_script('flashsale-script', get_stylesheet_directory_uri()  . '/assets/js/flashsale.js', array('jquery') );
    wp_enqueue_script( 'flashsale-slick-js' );
    wp_enqueue_script( 'flashsale-script' );
    
    wp_register_style('flashsale-slick-css', get_stylesheet_directory_uri()  . '/assets/css/slick.css' );
    wp_enqueue_style( 'flashsale-slick-css' );
    wp_enqueue_style( 'flashsale-slick-theme-css', get_stylesheet_directory_uri()  . '/assets/css/slick-theme.css');
    wp_enqueue_style( 'flashsale-flashsale-css', get_stylesheet_directory_uri()  . '/assets/css/flashsale.css');
    
    wp_localize_script( 'flashsale-script', 'flashsale_object', array(
        'start_hour'    => $start_hour,
        'start_minute'  => $start_minute,
        'end_days'      => $end_days,
        'cron_hour'     => $cron_hour,
        'cron_minute'   => $cron_minute,
        'cron_days'     => $cron_days,
        'cron_months'   => $cron_months
    ));
}

add_action( 'init', 'scripts_init' );

//SHORT CODE 
function flashsale_func( $atts ){
    ob_start();
    ?>
    <?php
        // Check rows existexists.
        if( have_rows('flashsale_products','option') ):
    ?>
        <section>
            <div class="flashsale">
                <div class="title-info" bis_skin_checked="1">
                    <div class="bg-snow" bis_skin_checked="1"></div>
                    <div class="count-down-time" bis_skin_checked="1">
                        <div class="count-down_name" bis_skin_checked="1">
                            <img width="40" src="<?= get_stylesheet_directory_uri().'/assets/imgs/icons/i-flash-sale-red.png'; ?>" alt="flash sale"><span class="title">FlashSale</span> | Kết Thúc Trong
                        </div>
                        <div id="count_down" bis_skin_checked="1"></div>
                    </div>
                </div>
                <div class="my-flashsale">
    
            <div class="row">
                <div class="multiple-items">
    <?php
            while( have_rows('flashsale_products','option') ) : the_row();
            // Load sub field value.
            $post = get_sub_field('flashsale','option');
            $_product = wc_get_product( $post->ID );
            $_sale_price = $_product->get_sale_price();
            $_regular_price = $_product->get_regular_price();
            $_price = $_product->get_price();
            $thumbnail = get_the_post_thumbnail_url($post->ID);
            $sale = round(((floatval($_regular_price) - floatval($_sale_price)) / floatval($_regular_price)) * 100);
    ?>
            <a href="<?= get_permalink($post->ID) ?>">
                <div class="item">
                    <div class="item__img">
                        <div class="item__discount">
                            <img width="20" src="<?= get_stylesheet_directory_uri().'/assets/imgs/icons/coupon.png'; ?>" alt="discount"/>
                            Giảm sốc<span><?= '-'.$sale.'%' ?></span>
                        </div>
                        <img src="<?= $thumbnail; ?>" alt="<?= $post->post_title ?>" />
                    </div>
                    <div class="item__info">
                        <div class="item__title"><?= $post->post_title ?></div>
                        <div class="item__price">
                            <div class="item__sale-price"><?= number_format($_sale_price,0,'','.').'đ'; ?></div>
                            <div class="item__regular-price"><?= number_format($_regular_price,0,'','.').'đ'; ?></div>
                        </div>
                    </div>
                </div>   
            </a>
    <?php
            endwhile;
    ?>
                </div>
            </div>
                </div>
            </div>
        <section>
    <?php
        endif; 
    ?>
    <?php
    $content = ob_get_contents();
    ob_get_clean();
    return $content;
}
add_shortcode( 'FLASHSALE', 'flashsale_func' );


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