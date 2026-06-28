<?php
/**
 * Single product reviews — Shopee-style layout.
 *
 * @package Flatsome_Child
 * @version 4.3.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! comments_open() ) {
	echo '<p class="pl-reviews__empty">' . esc_html__( 'Chưa có đánh giá cho sản phẩm này.', 'flatsome-child' ) . '</p>';
	return;
}

$review_ratings_enabled = wc_review_ratings_enabled();
?>
<div id="reviews" class="woocommerce-Reviews pl-reviews-woo">
	<div id="comments">
		<?php if ( have_comments() ) : ?>
			<ol class="commentlist pl-reviews__items">
				<?php wp_list_comments( apply_filters( 'woocommerce_product_review_list_args', [ 'callback' => 'woocommerce_comments' ] ) ); ?>
			</ol>

			<?php
			if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) :
				echo '<nav class="pl-reviews__pagination woocommerce-pagination">';
				echo paginate_comments_links( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					apply_filters(
						'woocommerce_comment_pagination_args',
						[
							'prev_text' => '&lsaquo;',
							'next_text' => '&rsaquo;',
							'type'      => 'list',
						]
					)
				);
				echo '</nav>';
			endif;
			?>
		<?php else : ?>
			<p class="pl-reviews__empty"><?php esc_html_e( 'Chưa có đánh giá nào. Hãy là người đầu tiên đánh giá sản phẩm.', 'flatsome-child' ); ?></p>
		<?php endif; ?>
	</div>

	<?php if ( get_option( 'woocommerce_review_rating_verification_required' ) === 'no' || wc_customer_bought_product( '', get_current_user_id(), $product->get_id() ) ) : ?>
		<div id="review_form_wrapper" class="pl-reviews__form-wrap">
			<div id="review_form">
				<?php
				$commenter    = wp_get_current_commenter();
				$comment_form = [
					'title_reply'          => have_comments()
						? esc_html__( 'Viết đánh giá', 'flatsome-child' )
						: esc_html__( 'Đánh giá đầu tiên', 'flatsome-child' ),
					'title_reply_before'   => '<h3 id="reply-title" class="pl-reviews__form-title">',
					'title_reply_after'    => '</h3>',
					'comment_notes_before' => '',
					'comment_notes_after'  => '',
					'label_submit'         => esc_html__( 'Gửi đánh giá', 'flatsome-child' ),
					'logged_in_as'         => '',
					'comment_field'        => '',
					'class_form'           => 'pl-reviews__form',
				];

				$name_email_required = (bool) get_option( 'require_name_email', 1 );
				$fields              = [
					'author' => [
						'label'    => __( 'Họ tên', 'flatsome-child' ),
						'type'     => 'text',
						'value'    => $commenter['comment_author'],
						'required' => $name_email_required,
					],
					'email'  => [
						'label'    => __( 'Email', 'flatsome-child' ),
						'type'     => 'email',
						'value'    => $commenter['comment_author_email'],
						'required' => $name_email_required,
					],
				];

				$comment_form['fields'] = [];
				foreach ( $fields as $key => $field ) {
					$comment_form['fields'][ $key ] = sprintf(
						'<p class="comment-form-%1$s"><label for="%1$s">%2$s</label><input id="%1$s" name="%1$s" type="%3$s" value="%4$s" %5$s /></p>',
						esc_attr( $key ),
						esc_html( $field['label'] ),
						esc_attr( $field['type'] ),
						esc_attr( $field['value'] ),
						$field['required'] ? 'required' : ''
					);
				}

				if ( $review_ratings_enabled ) {
					$comment_form['comment_field'] = '<div class="comment-form-rating"><label for="rating">' . esc_html__( 'Chọn số sao', 'flatsome-child' ) . '</label><select name="rating" id="rating" required>
						<option value="">' . esc_html__( 'Chọn...', 'flatsome-child' ) . '</option>
						<option value="5">5</option>
						<option value="4">4</option>
						<option value="3">3</option>
						<option value="2">2</option>
						<option value="1">1</option>
					</select></div>';
				}

				$comment_form['comment_field'] .= '<p class="comment-form-comment"><label for="comment">' . esc_html__( 'Nội dung đánh giá', 'flatsome-child' ) . '</label><textarea id="comment" name="comment" cols="45" rows="5" required></textarea></p>';

				comment_form( apply_filters( 'woocommerce_product_review_comment_form_args', $comment_form ) );
				?>
			</div>
		</div>
	<?php else : ?>
		<p class="pl-reviews__verify"><?php esc_html_e( 'Chỉ khách hàng đã mua sản phẩm mới có thể đánh giá.', 'flatsome-child' ); ?></p>
	<?php endif; ?>
</div>
