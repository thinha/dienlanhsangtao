<?php
/**
 * Exam — frontend content.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$page_id      = get_the_ID();
$subtitle     = (string) dmc_exam_field( 'exam_subtitle', '' );
$time_limit   = max( 0, (int) dmc_exam_field( 'exam_time_limit', 0 ) );
$require_name = (bool) dmc_exam_field( 'exam_require_name', true );
$questions    = dmc_exam_get_questions( $page_id );
$answer_labels = [
	'a' => 'A',
	'b' => 'B',
	'c' => 'C',
	'd' => 'D',
];
?>

<section class="dmc-exam-content">
	<header class="dmc-exam-content__header">
		<h1 class="dmc-exam-content__title"><?php the_title(); ?></h1>

		<?php if ( $subtitle ) : ?>
			<p class="dmc-exam-content__subtitle"><?php echo esc_html( $subtitle ); ?></p>
		<?php endif; ?>

		<div class="dmc-exam-content__meta">
			<span class="dmc-exam-content__meta-item">
				<?php
				printf(
					/* translators: %d: question count */
					esc_html( _n( '%d câu hỏi', '%d câu hỏi', count( $questions ), 'flatsome-child' ) ),
					count( $questions )
				);
				?>
			</span>

			<?php if ( $time_limit > 0 ) : ?>
				<span class="dmc-exam-content__meta-item dmc-exam-content__meta-item--timer">
					<?php esc_html_e( 'Thời gian:', 'flatsome-child' ); ?>
					<strong id="dmc-exam-timer" data-limit-min="<?php echo esc_attr( (string) $time_limit ); ?>">
						<?php echo esc_html( sprintf( '%02d:00', $time_limit ) ); ?>
					</strong>
				</span>
			<?php endif; ?>
		</div>
	</header>

	<?php if ( empty( $questions ) ) : ?>
		<div class="dmc-exam-empty">
			<p><?php esc_html_e( 'Bài thi chưa có câu hỏi. Vui lòng thêm câu hỏi trong phần Cấu hình bài thi.', 'flatsome-child' ); ?></p>
		</div>
	<?php else : ?>
		<form id="dmc-exam-form" class="dmc-exam-form" novalidate>
			<div class="dmc-exam-form__candidate">
				<label class="dmc-exam-form__label" for="dmc-exam-candidate-name">
					<?php esc_html_e( 'Họ và tên thí sinh', 'flatsome-child' ); ?>
					<?php if ( $require_name ) : ?>
						<span class="dmc-exam-form__required">*</span>
					<?php endif; ?>
				</label>
				<input
					type="text"
					id="dmc-exam-candidate-name"
					name="candidate_name"
					class="dmc-exam-form__input"
					placeholder="<?php esc_attr_e( 'Nhập họ tên của bạn', 'flatsome-child' ); ?>"
					<?php echo $require_name ? 'required' : ''; ?>
				>
			</div>

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
