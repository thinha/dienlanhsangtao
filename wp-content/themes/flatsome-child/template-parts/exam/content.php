<?php
/**
 * Exam — frontend content.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$page_id       = get_the_ID();
$subtitle      = (string) dmc_exam_field( 'exam_subtitle', '' );
$time_limit    = dmc_exam_get_time_limit_seconds( $page_id );
$questions     = dmc_exam_get_questions( $page_id );
$session       = dmc_exam_get_session( $page_id );
$has_session   = $session && ! dmc_exam_session_is_expired( $session );
$remaining     = $has_session && $time_limit > 0 ? dmc_exam_remaining_seconds( $session ) : $time_limit;
$answer_labels = [
	'a' => 'A',
	'b' => 'B',
	'c' => 'C',
	'd' => 'D',
];

$flash = isset( $_GET['exam'] ) ? sanitize_key( wp_unslash( $_GET['exam'] ) ) : '';
$flash_message = '';

if ( 'done' === $flash ) {
	$flash_message = __( 'Bạn đã nộp bài thành công. Cảm ơn bạn đã tham gia.', 'flatsome-child' );
} elseif ( 'timeout' === $flash ) {
	$flash_message = __( 'Phiên làm bài đã hết thời gian. Bạn không thể làm lại với cùng thông tin.', 'flatsome-child' );
}
?>

<section class="dmc-exam-content<?php echo $has_session ? ' is-taking' : ' is-gate'; ?>">
	<header class="dmc-exam-content__header">
		<div class="dmc-exam-content__header-top">
			<div class="dmc-exam-content__brand">
				<span class="dmc-exam-content__brand-mark" aria-hidden="true"></span>
				<span class="dmc-exam-content__brand-text"><?php esc_html_e( 'Bài thi trắc nghiệm', 'flatsome-child' ); ?></span>
			</div>

			<?php if ( $has_session && $time_limit > 0 ) : ?>
				<div class="dmc-exam-content__timer" aria-live="polite">
					<span class="dmc-exam-content__timer-label"><?php esc_html_e( 'Thời gian còn lại', 'flatsome-child' ); ?></span>
					<strong
						id="dmc-exam-timer"
						class="dmc-exam-content__timer-value"
						data-remaining="<?php echo esc_attr( (string) $remaining ); ?>"
						data-limit-seconds="<?php echo esc_attr( (string) $time_limit ); ?>"
					>
						<?php echo esc_html( dmc_exam_format_countdown( $remaining ) ); ?>
					</strong>
				</div>
			<?php endif; ?>
		</div>

		<h1 class="dmc-exam-content__title"><?php the_title(); ?></h1>

		<?php if ( $subtitle ) : ?>
			<p class="dmc-exam-content__subtitle"><?php echo esc_html( $subtitle ); ?></p>
		<?php endif; ?>

		<?php if ( $has_session ) : ?>
			<div class="dmc-exam-content__meta">
				<span class="dmc-exam-content__meta-item">
					<span class="dmc-exam-content__meta-label"><?php esc_html_e( 'Câu hỏi', 'flatsome-child' ); ?></span>
					<strong>
						<?php
						printf(
							/* translators: %d: question count */
							esc_html( _n( '%d câu', '%d câu', count( $questions ), 'flatsome-child' ) ),
							count( $questions )
						);
						?>
					</strong>
				</span>

				<span class="dmc-exam-content__meta-item">
					<span class="dmc-exam-content__meta-label"><?php esc_html_e( 'Thí sinh', 'flatsome-child' ); ?></span>
					<strong><?php echo esc_html( (string) ( $session['name'] ?? '' ) ); ?></strong>
				</span>

				<?php if ( ! empty( $session['department'] ) ) : ?>
					<span class="dmc-exam-content__meta-item">
						<span class="dmc-exam-content__meta-label"><?php esc_html_e( 'Khoa', 'flatsome-child' ); ?></span>
						<strong><?php echo esc_html( (string) $session['department'] ); ?></strong>
					</span>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</header>

	<?php if ( empty( $questions ) ) : ?>
		<div class="dmc-exam-empty">
			<p><?php esc_html_e( 'Bài thi chưa có câu hỏi. Vui lòng thêm câu hỏi trong phần Cấu hình bài thi.', 'flatsome-child' ); ?></p>
		</div>
	<?php elseif ( ! $has_session ) : ?>
		<form id="dmc-exam-gate" class="dmc-exam-gate" novalidate>
			<h2 class="dmc-exam-gate__title"><?php esc_html_e( 'Thông tin thí sinh', 'flatsome-child' ); ?></h2>
			<p class="dmc-exam-gate__desc">
				<?php esc_html_e( 'Vui lòng nhập đầy đủ thông tin trước khi vào làm bài. Mỗi số điện thoại chỉ được làm một lần.', 'flatsome-child' ); ?>
			</p>

			<?php if ( $flash_message ) : ?>
				<div class="dmc-exam-form__notice dmc-exam-form__notice--warning" id="dmc-exam-gate-flash">
					<?php echo esc_html( $flash_message ); ?>
				</div>
			<?php endif; ?>

			<div class="dmc-exam-gate__fields">
				<div class="dmc-exam-form__field">
					<label class="dmc-exam-form__label" for="dmc-exam-candidate-name">
						<?php esc_html_e( 'Họ và tên', 'flatsome-child' ); ?>
						<span class="dmc-exam-form__required">*</span>
					</label>
					<input
						type="text"
						id="dmc-exam-candidate-name"
						name="candidate_name"
						class="dmc-exam-form__input"
						autocomplete="name"
						placeholder="<?php esc_attr_e( 'Nhập họ tên của bạn', 'flatsome-child' ); ?>"
						required
					>
				</div>

				<div class="dmc-exam-form__field">
					<label class="dmc-exam-form__label" for="dmc-exam-candidate-phone">
						<?php esc_html_e( 'Số điện thoại', 'flatsome-child' ); ?>
						<span class="dmc-exam-form__required">*</span>
					</label>
					<input
						type="tel"
						id="dmc-exam-candidate-phone"
						name="candidate_phone"
						class="dmc-exam-form__input"
						autocomplete="tel"
						inputmode="numeric"
						placeholder="<?php esc_attr_e( 'VD: 0912345678', 'flatsome-child' ); ?>"
						required
					>
				</div>

				<div class="dmc-exam-form__field">
					<label class="dmc-exam-form__label" for="dmc-exam-candidate-department">
						<?php esc_html_e( 'Thuộc khoa', 'flatsome-child' ); ?>
						<span class="dmc-exam-form__required">*</span>
					</label>
					<input
						type="text"
						id="dmc-exam-candidate-department"
						name="candidate_department"
						class="dmc-exam-form__input"
						autocomplete="organization-title"
						placeholder="<?php esc_attr_e( 'VD: Khoa Cơ khí', 'flatsome-child' ); ?>"
						required
					>
				</div>
			</div>

			<div class="dmc-exam-form__actions">
				<button type="submit" class="dmc-exam-form__submit" id="dmc-exam-start">
					<?php esc_html_e( 'Bắt đầu làm bài', 'flatsome-child' ); ?>
				</button>
			</div>

			<div class="dmc-exam-form__notice" id="dmc-exam-gate-notice" hidden></div>
		</form>
	<?php else : ?>
		<form id="dmc-exam-form" class="dmc-exam-form" novalidate>
			<div class="dmc-exam-questions">
				<?php foreach ( $questions as $question ) : ?>
					<article class="dmc-exam-question" data-question-id="<?php echo esc_attr( (string) $question['id'] ); ?>">
						<h2 class="dmc-exam-question__title">
							<span class="dmc-exam-question__number"><?php echo esc_html( (string) $question['id'] ); ?>.</span>
							<?php echo esc_html( $question['text'] ); ?>
						</h2>

						<div class="dmc-exam-question__answers">
							<?php foreach ( $answer_labels as $key => $letter ) : ?>
								<?php
								$input_id = 'dmc-exam-q' . $question['id'] . '-' . $key;
								$answer   = $question['answers'][ $key ] ?? '';
								?>
								<label class="dmc-exam-answer" for="<?php echo esc_attr( $input_id ); ?>">
									<input
										type="radio"
										id="<?php echo esc_attr( $input_id ); ?>"
										name="q_<?php echo esc_attr( (string) $question['id'] ); ?>"
										value="<?php echo esc_attr( $key ); ?>"
										class="dmc-exam-answer__input"
									>
									<span class="dmc-exam-answer__badge"><?php echo esc_html( $letter ); ?></span>
									<span class="dmc-exam-answer__text"><?php echo esc_html( $answer ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>
					</article>
				<?php endforeach; ?>
			</div>

			<div class="dmc-exam-form__actions">
				<button type="submit" class="dmc-exam-form__submit" id="dmc-exam-submit">
					<?php esc_html_e( 'Nộp bài', 'flatsome-child' ); ?>
				</button>
			</div>

			<div class="dmc-exam-form__notice" id="dmc-exam-notice" hidden></div>
			<div class="dmc-exam-form__result" id="dmc-exam-result" hidden></div>
		</form>
	<?php endif; ?>
</section>
