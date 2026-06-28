<?php
/**
 * My Account — login panel (members only, no registration).
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="dmc-account-login">
	<div class="dmc-account-login__card">
		<div class="dmc-account-login__intro">
			<h1><?php esc_html_e( 'Đăng nhập thành viên', 'flatsome-child' ); ?></h1>
			<p><?php esc_html_e( 'Đăng nhập để xem đơn hàng, quản lý tài khoản và danh sách yêu thích.', 'flatsome-child' ); ?></p>
		</div>

		<?php wc_print_notices(); ?>

		<form class="woocommerce-form woocommerce-form-login login dmc-login-form" method="post">
			<?php do_action( 'woocommerce_before_customer_login_form' ); ?>
			<?php do_action( 'woocommerce_login_form_start' ); ?>

			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="username"><?php esc_html_e( 'Email hoặc tên đăng nhập', 'flatsome-child' ); ?> <span class="required">*</span></label>
				<input
					type="text"
					class="woocommerce-Input woocommerce-Input--text input-text"
					name="username"
					id="username"
					autocomplete="username"
					value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized ?>"
				/>
			</p>

			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="password"><?php esc_html_e( 'Mật khẩu', 'flatsome-child' ); ?> <span class="required">*</span></label>
				<input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" />
			</p>

			<?php do_action( 'woocommerce_login_form' ); ?>

			<p class="form-row dmc-login-form__actions">
				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
					<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" />
					<span><?php esc_html_e( 'Ghi nhớ đăng nhập', 'flatsome-child' ); ?></span>
				</label>
				<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
				<button type="submit" class="dmc-btn dmc-btn--primary woocommerce-button button woocommerce-form-login__submit" name="login" value="<?php esc_attr_e( 'Đăng nhập', 'flatsome-child' ); ?>">
					<?php esc_html_e( 'Đăng nhập', 'flatsome-child' ); ?>
				</button>
			</p>

			<p class="woocommerce-LostPassword lost_password">
				<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Quên mật khẩu?', 'flatsome-child' ); ?></a>
			</p>

			<?php do_action( 'woocommerce_login_form_end' ); ?>
			<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
		</form>
	</div>
</section>
