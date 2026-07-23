<?php
/**
 * About page — main content sections.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$company_name = dmc_about_get_company_name();
$hotline      = dmc_about_get_hotline();
$stats        = dmc_about_get_stats();
$values       = dmc_about_get_values();
$products     = dmc_about_get_product_lines();
$stores       = dmc_about_get_stores();
$business     = dmc_about_get_business_info();
$media        = dmc_about_get_media();
$brands       = dmc_about_get_brands();
$years        = dmc_about_get_years_experience();
$contact_url  = home_url( '/lien-he/' );
$stores_url   = home_url( '/he-thong-cua-hang/' );
?>
<div class="dmc-about-content">
	<section class="dmc-about-hero">
		<div class="container">
			<nav class="dmc-about-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'flatsome-child' ); ?>">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Trang chủ', 'flatsome-child' ); ?></a>
				<span class="dmc-about-breadcrumb__sep" aria-hidden="true">/</span>
				<span class="dmc-about-breadcrumb__current"><?php esc_html_e( 'Giới thiệu', 'flatsome-child' ); ?></span>
			</nav>

			<div class="dmc-about-hero__inner">
				<div class="dmc-about-hero__copy">
					<p class="dmc-about-hero__eyebrow"><?php esc_html_e( 'Điện Lạnh Sáng Tạo', 'flatsome-child' ); ?></p>
					<h1 class="dmc-about-hero__title"><?php echo esc_html( $company_name ); ?></h1>
					<p class="dmc-about-hero__lead">
						<?php
						printf(
							/* translators: %d: years in business */
							esc_html__(
								'Chuyên cung cấp tủ đông, tủ mát, tủ lạnh, máy giặt, máy lạnh 100%% chính hãng Darling, Aqua, Sanaky, LG, Alaska — phục vụ khách hàng tại TP.HCM và khu vực lân cận hơn %d năm.',
								'flatsome-child'
							),
							(int) $years
						);
						?>
					</p>
					<div class="dmc-about-hero__actions">
						<a class="dmc-about-btn dmc-about-btn--primary" href="<?php echo esc_url( 'tel:' . $hotline['tel'] ); ?>">
							<?php echo dmc_icon( 'phone', [ 'size' => 18, 'variant' => 'white' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span><?php echo esc_html( $hotline['display'] ); ?></span>
						</a>
						<a class="dmc-about-btn dmc-about-btn--ghost" href="<?php echo esc_url( $contact_url ); ?>">
							<?php esc_html_e( 'Liên hệ tư vấn', 'flatsome-child' ); ?>
						</a>
					</div>
				</div>

				<div class="dmc-about-hero__media">
					<div class="dmc-about-hero__image dmc-about-hero__image--main">
						<img
							src="<?php echo esc_url( $media['store_1'] ); ?>"
							alt="<?php esc_attr_e( 'Cửa hàng điện máy Sáng Tạo', 'flatsome-child' ); ?>"
							width="949"
							height="800"
							loading="eager"
							decoding="async"
						>
					</div>
					<div class="dmc-about-hero__badge">
						<strong><?php echo esc_html( $years ); ?>+</strong>
						<span><?php esc_html_e( 'năm uy tín', 'flatsome-child' ); ?></span>
					</div>
				</div>
			</div>
		</div>
	</section>

	<section class="dmc-about-stats" aria-label="<?php esc_attr_e( 'Thành tựu nổi bật', 'flatsome-child' ); ?>">
		<div class="container">
			<div class="dmc-about-stats__grid">
				<?php foreach ( $stats as $stat ) : ?>
					<div class="dmc-about-stat">
						<strong class="dmc-about-stat__value"><?php echo esc_html( $stat['value'] ); ?></strong>
						<span class="dmc-about-stat__label"><?php echo esc_html( $stat['label'] ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="dmc-about-section">
		<div class="container">
			<div class="dmc-about-intro card">
				<div class="dmc-about-intro__content">
					<div class="section-head">
						<h2><?php esc_html_e( 'Về chúng tôi', 'flatsome-child' ); ?></h2>
					</div>
					<p>
						<?php esc_html_e( 'Công Ty TNHH Sửa Chữa Thiết Bị Điện Sáng Tạo được hãng Darling chứng nhận là nhà phân phối chính thức tại thành phố Hồ Chí Minh. Chúng tôi liên kết phân phối trực tiếp với Công Ty TNHH Điện Tử – Điện Lạnh Darling.', 'flatsome-child' ); ?>
					</p>
					<p>
						<?php esc_html_e( 'Với hệ thống showroom và chi nhánh tại Quận 7, Bình Thạnh, Tân Bình và Bình Dương, Sáng Tạo mang đến cho khách hàng trải nghiệm mua sắm thuận tiện, sản phẩm đa dạng và dịch vụ hậu mãi tận tâm.', 'flatsome-child' ); ?>
					</p>
					<ul class="dmc-about-intro__list">
						<li><?php esc_html_e( 'Tủ đông, tủ đông mát, tủ mát các loại', 'flatsome-child' ); ?></li>
						<li><?php esc_html_e( 'Tủ lạnh, máy giặt, máy lạnh chính hãng', 'flatsome-child' ); ?></li>
						<li><?php esc_html_e( 'Dịch vụ sửa chữa, bảo hành và lắp đặt tận nơi', 'flatsome-child' ); ?></li>
					</ul>
				</div>
				<div class="dmc-about-intro__gallery">
					<img
						class="dmc-about-intro__photo"
						src="<?php echo esc_url( $media['store_2'] ); ?>"
						alt="<?php esc_attr_e( 'Showroom Điện Lạnh Sáng Tạo', 'flatsome-child' ); ?>"
						width="856"
						height="800"
						loading="lazy"
						decoding="async"
					>
				</div>
			</div>
		</div>
	</section>

	<section class="dmc-about-section">
		<div class="container">
			<div class="section-head">
				<h2><?php esc_html_e( 'Giá trị cốt lõi', 'flatsome-child' ); ?></h2>
			</div>
			<div class="dmc-about-values">
				<?php foreach ( $values as $value ) : ?>
					<article class="dmc-about-value card">
						<span class="dmc-about-value__icon">
							<?php echo dmc_icon( $value['icon'], [ 'size' => 24, 'variant' => 'blue' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</span>
						<h3><?php echo esc_html( $value['title'] ); ?></h3>
						<p><?php echo esc_html( $value['text'] ); ?></p>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="dmc-about-section">
		<div class="container">
			<div class="section-head">
				<h2><?php esc_html_e( 'Danh mục sản phẩm', 'flatsome-child' ); ?></h2>
			</div>
			<div class="dmc-about-products">
				<?php foreach ( $products as $product ) : ?>
					<a class="dmc-about-product card" href="<?php echo esc_url( $product['link'] ); ?>">
						<span class="dmc-about-product__icon">
							<?php echo dmc_icon( $product['icon'], [ 'size' => 28, 'variant' => 'blue' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</span>
						<span class="dmc-about-product__title"><?php echo esc_html( $product['title'] ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="dmc-about-section">
		<div class="container">
			<div class="dmc-about-partner card">
				<div class="dmc-about-partner__content">
					<div class="section-head">
						<h2><?php esc_html_e( 'Nhà phân phối Darling chính thức', 'flatsome-child' ); ?></h2>
					</div>
					<p>
						<?php esc_html_e( 'Sáng Tạo tự hào là đối tác phân phối được Darling chứng nhận tại TP.HCM, cam kết nguồn hàng chính hãng, đầy đủ tem bảo hành và chính sách hỗ trợ từ nhà sản xuất.', 'flatsome-child' ); ?>
					</p>
					<a class="dmc-about-link" href="<?php echo esc_url( home_url( '/thuong-hieu/darling/' ) ); ?>">
						<?php esc_html_e( 'Xem sản phẩm Darling', 'flatsome-child' ); ?>
						<span aria-hidden="true">→</span>
					</a>
				</div>
				<div class="dmc-about-partner__cert">
					<img
						src="<?php echo esc_url( $media['certificate'] ); ?>"
						alt="<?php esc_attr_e( 'Giấy chứng nhận nhà phân phối Darling', 'flatsome-child' ); ?>"
						width="510"
						height="680"
						loading="lazy"
						decoding="async"
					>
				</div>
			</div>
		</div>
	</section>

	<?php if ( ! empty( $brands ) ) : ?>
		<section class="dmc-about-section">
			<div class="container">
				<?php
				get_template_part(
					'template-parts/homepage/brands',
					null,
					[
						'brands'        => $brands,
						'title'         => __( 'Thương hiệu đồng hành', 'flatsome-child' ),
						'wrapper_class' => 'card dmc-about-brands',
					]
				);
				?>
			</div>
		</section>
	<?php endif; ?>

	<section class="dmc-about-section" id="he-thong-cua-hang">
		<div class="container">
			<div class="dmc-about-stores-head">
				<div class="section-head">
					<h2><?php esc_html_e( 'Hệ thống showroom & chi nhánh', 'flatsome-child' ); ?></h2>
					<p><?php esc_html_e( 'Mở cửa 7:00 – 22:00 hàng ngày. Ghé thăm trực tiếp để được tư vấn và trải nghiệm sản phẩm.', 'flatsome-child' ); ?></p>
				</div>
				<a class="dmc-about-link" href="<?php echo esc_url( $stores_url ); ?>">
					<?php esc_html_e( 'Xem bản đồ hệ thống', 'flatsome-child' ); ?>
					<span aria-hidden="true">→</span>
				</a>
			</div>

			<div class="dmc-about-stores">
				<?php foreach ( $stores as $store ) : ?>
					<article class="dmc-about-store card">
						<div class="dmc-about-store__head">
							<span class="dmc-about-store__icon">
								<?php echo dmc_icon( 'store', [ 'size' => 20, 'variant' => 'blue' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</span>
							<h3><?php echo esc_html( $store['label'] ); ?></h3>
						</div>
						<p class="dmc-about-store__address"><?php echo esc_html( $store['address'] ); ?></p>
						<a
							class="dmc-about-store__map"
							href="<?php echo esc_url( dmc_about_maps_url( $store['map_query'] ) ); ?>"
							target="_blank"
							rel="noopener noreferrer"
						>
							<?php esc_html_e( 'Chỉ đường Google Maps', 'flatsome-child' ); ?>
						</a>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="dmc-about-section">
		<div class="container">
			<div class="dmc-about-legal card">
				<div class="section-head">
					<h2><?php esc_html_e( 'Thông tin doanh nghiệp', 'flatsome-child' ); ?></h2>
				</div>
				<dl class="dmc-about-legal__list">
					<?php foreach ( $business as $label => $value ) : ?>
						<div class="dmc-about-legal__row">
							<dt><?php echo esc_html( $label ); ?></dt>
							<dd><?php echo esc_html( $value ); ?></dd>
						</div>
					<?php endforeach; ?>
				</dl>
			</div>
		</div>
	</section>

	<section class="dmc-about-cta">
		<div class="container">
			<div class="dmc-about-cta__inner">
				<div>
					<h2><?php esc_html_e( 'Cần tư vấn sản phẩm?', 'flatsome-child' ); ?></h2>
					<p><?php esc_html_e( 'Đội ngũ Sáng Tạo sẵn sàng hỗ trợ chọn mẫu phù hợp, báo giá và giao hàng nhanh chóng.', 'flatsome-child' ); ?></p>
				</div>
				<div class="dmc-about-cta__actions">
					<a class="dmc-about-btn dmc-about-btn--primary" href="<?php echo esc_url( 'tel:' . $hotline['tel'] ); ?>">
						<?php echo dmc_icon( 'phone', [ 'size' => 18, 'variant' => 'white' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php echo esc_html( $hotline['display'] ); ?></span>
					</a>
					<a class="dmc-about-btn dmc-about-btn--light" href="<?php echo esc_url( $contact_url ); ?>">
						<?php esc_html_e( 'Gửi yêu cầu tư vấn', 'flatsome-child' ); ?>
					</a>
				</div>
			</div>
		</div>
	</section>
</div>
