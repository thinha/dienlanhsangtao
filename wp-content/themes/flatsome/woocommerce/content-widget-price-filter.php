<?php
/**
 * The template for displaying product price filter widget.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-widget-price-filter.php
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.7.1
 */

defined( 'ABSPATH' ) || exit;

?>
<?php do_action( 'woocommerce_widget_price_filter_start', $args ); ?>

<form method="get" action="<?php echo esc_url( $form_action ); ?>">

	<div class="price_slider_wrapper">
		<div class="price_slider" style="display:none;"></div>
		<div class="price_slider_amount" data-step="<?php echo esc_attr( $step ); ?>">
			<input type="text" id="min_price" name="min_price" value="<?php echo esc_attr( $current_min_price ); ?>" data-min="<?php echo esc_attr( $min_price ); ?>" placeholder="<?php echo esc_attr__( 'Min price', 'woocommerce' ); ?>" />
			<input type="text" id="max_price" name="max_price" value="<?php echo esc_attr( $current_max_price ); ?>" data-max="<?php echo esc_attr( $max_price ); ?>" placeholder="<?php echo esc_attr__( 'Max price', 'woocommerce' ); ?>" />

			<?php /* translators: Filter: verb "to filter" */ ?>
			<button type="submit" class="button"><?php echo esc_html__( 'Filter', 'woocommerce' ); ?></button>
			<div class="price_label" style="display:none;">
				<?php echo esc_html__( 'Price:', 'woocommerce' ); ?> <span class="from"></span> &mdash; <span class="to"></span>
			</div>
			<?php echo wc_query_string_form_fields( null, array( 'min_price', 'max_price', 'paged' ), '', true ); ?>
			<div class="clear"></div>
		</div>
	</div>
</form>

<form method="get" action="<?php echo esc_url( $form_action ); ?>">
	<div class="price_slider_wrapper">
		<div class="price_dynamic">
			<h4>LỌC GIÁ BÁN</h4>
			<div class="is-divider small"></div>
			<div class="check-radio"> 
				<label for="duoi_5_Trieu">
					<input id="duoi_5_Trieu" type="radio" name="gia_ban" value="duoi5" <?php echo (isset($_GET['gia_ban']) && $_GET['gia_ban'] === 'duoi5')?'checked':''; ?> />
				Dưới 5 triệu</label>
			</div>
			<div class="check-radio">
				<label for="tu_5_10_trieu">
					<input id="tu_5_10_trieu" type="radio" name="gia_ban" value="tu_5_10" <?php echo ( isset($_GET['gia_ban']) && $_GET['gia_ban'] === 'tu_5_10')?'checked':''; ?> />	
				Từ 5 - 10 triệu</label>
			</div>
			<div class="check-radio">
				<label for="tu_10_15_trieu">
					<input id="tu_10_15_trieu" type="radio" name="gia_ban" value="tu_10_15" <?php echo (isset($_GET['gia_ban']) && $_GET['gia_ban'] === 'tu_10_15')?'checked':''; ?> />
				Từ 10 - 15 triệu</label>	
			</div>
			<div class="check-radio">
				<label for="tu_15_20_trieu">
					<input id="tu_15_20_trieu" type="radio" name="gia_ban" value="tu_15_20" <?php echo (isset($_GET['gia_ban']) && $_GET['gia_ban'] === 'tu_15_20')?'checked':''; ?> />	
				Từ 15 - 20 triệu</label>
			</div>
			<div class="check-radio">
				<label for="tu_20_25_trieu">
					<input id="tu_20_25_trieu" type="radio" name="gia_ban" value="tu_20_25" <?php echo (isset($_GET['gia_ban']) && $_GET['gia_ban'] === 'tu_20_25')?'checked':''; ?> />
				Trên 20 triệu</label>		
			</div>
		</div>
		<div class="price_slider_amount" data-step="<?php echo esc_attr( $step ); ?>">

			<input type="text" id="gia_ban_min_price" name="min_price" value="<?php echo esc_attr( $current_min_price ); ?>" data-min="<?php echo esc_attr( $min_price ); ?>" placeholder="<?php echo esc_attr__( 'Min price', 'woocommerce' ); ?>" />

			<input type="text" id="gia_ban_max_price" name="max_price" value="<?php echo esc_attr( $current_max_price ); ?>" data-max="<?php echo esc_attr( $max_price ); ?>" placeholder="<?php echo esc_attr__( 'Max price', 'woocommerce' ); ?>" />

			<?php /* translators: Filter: verb "to filter" */ ?>
			<button type="submit" class="button"><?php echo esc_html__( 'Lọc Theo Giá Bán', 'woocommerce' ); ?></button>
			
			<?php //echo wc_query_string_form_fields( null, array( 'min_price', 'max_price', 'paged' ), '', true ); ?>
			<div class="clear"></div>
		</div>
	</div>
</form>

<script>
	jQuery( function( $ ) {
		$('input[type="radio"]').on('change',function(e){
			var currentTarget = e.currentTarget; 
			var gia_ban = $(currentTarget).val();
			var min_min = "<?php echo esc_attr( $min_price ); ?>";
			var max_max = "<?php echo esc_attr( $max_price ); ?>";

			switch(gia_ban){
				case 'duoi5':
					$('#gia_ban_min_price').attr('value',min_min).attr('data-min',min_min);
					$('#gia_ban_max_price').attr('value',5000000).attr('data-max',5000000);
				break;
				case 'tu_5_10':
					$('#gia_ban_min_price').attr('value',5000000).attr('data-min',5000000);
					$('#gia_ban_max_price').attr('value',10000000).attr('data-max',10000000);
				break;
				case 'tu_10_15':
					$('#gia_ban_min_price').attr('value',10000000).attr('data-min',10000000);
					$('#gia_ban_max_price').attr('value',15000000).attr('data-max',15000000);
				break;
				case 'tu_15_20':
					$('#gia_ban_min_price').attr('value',15000000).attr('data-min',15000000);
					$('#gia_ban_max_price').attr('value',20000000).attr('data-max',20000000);
				break;
				case 'tu_20_25':
					$('#gia_ban_min_price').attr('value',20000000).attr('data-min',20000000);
					$('#gia_ban_max_price').attr('value',max_max).attr('data-max',max_max);
				break;
			}

		});
	});
</script>

<?php do_action( 'woocommerce_widget_price_filter_end', $args ); ?>
