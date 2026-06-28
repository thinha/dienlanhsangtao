<?php
/**
 * Exam — admin list & detail.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin columns for submissions.
 *
 * @param string[] $columns Columns.
 * @return string[]
 */
function dmc_exam_submission_columns( $columns ) {
	$new = [];

	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;

		if ( 'title' === $key ) {
			$new['exam_page']     = __( 'Bài thi', 'flatsome-child' );
			$new['candidate']     = __( 'Thí sinh', 'flatsome-child' );
			$new['submitted_at']  = __( 'Thời gian nộp', 'flatsome-child' );
			$new['time_spent']    = __( 'Thời gian làm', 'flatsome-child' );
			$new['score']         = __( 'Điểm', 'flatsome-child' );
		}
	}

	return $new;
}
add_filter( 'manage_dmc_exam_submission_posts_columns', 'dmc_exam_submission_columns' );

/**
 * Render custom admin columns.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function dmc_exam_submission_column_content( $column, $post_id ) {
	switch ( $column ) {
		case 'exam_page':
			$page_id = (int) get_post_meta( $post_id, 'exam_page_id', true );
			$title   = (string) get_post_meta( $post_id, 'exam_page_title', true );

			if ( $page_id && get_post( $page_id ) ) {
				echo '<a href="' . esc_url( get_edit_post_link( $page_id ) ) . '">' . esc_html( $title ?: get_the_title( $page_id ) ) . '</a>';
			} else {
				echo esc_html( $title ?: '—' );
			}
			break;

		case 'candidate':
			$name = (string) get_post_meta( $post_id, 'candidate_name', true );
			echo esc_html( $name ?: '—' );
			break;

		case 'submitted_at':
			$unix_ms = (int) get_post_meta( $post_id, 'submitted_at_unix_ms', true );
			$stored  = (string) get_post_meta( $post_id, 'submitted_at', true );
			echo esc_html( $unix_ms ? dmc_exam_format_unix_ms( $unix_ms ) : ( $stored ?: '—' ) );
			break;

		case 'time_spent':
			$seconds = (int) get_post_meta( $post_id, 'time_spent_seconds', true );

			if ( $seconds <= 0 ) {
				echo '—';
				break;
			}

			$minutes = floor( $seconds / 60 );
			$remain  = $seconds % 60;
			echo esc_html( sprintf( '%02d:%02d', $minutes, $remain ) );
			break;

		case 'score':
			$score = get_post_meta( $post_id, 'score_percent', true );

			if ( '' === $score || null === $score ) {
				echo '—';
				break;
			}

			$correct   = (int) get_post_meta( $post_id, 'correct_count', true );
			$gradable  = (int) get_post_meta( $post_id, 'gradable_count', true );
			echo esc_html( $score . '% (' . $correct . '/' . $gradable . ')' );
			break;
	}
}
add_action( 'manage_dmc_exam_submission_posts_custom_column', 'dmc_exam_submission_column_content', 10, 2 );

/**
 * Meta box with submission detail.
 */
function dmc_exam_register_meta_boxes() {
	add_meta_box(
		'dmc_exam_submission_detail',
		__( 'Chi tiết bài nộp', 'flatsome-child' ),
		'dmc_exam_render_submission_meta_box',
		'dmc_exam_submission',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'dmc_exam_register_meta_boxes' );

/**
 * Render submission meta box.
 *
 * @param WP_Post $post Post object.
 */
function dmc_exam_render_submission_meta_box( $post ) {
	$page_id      = (int) get_post_meta( $post->ID, 'exam_page_id', true );
	$candidate    = (string) get_post_meta( $post->ID, 'candidate_name', true );
	$submitted    = (string) get_post_meta( $post->ID, 'submitted_at', true );
	$unix_ms      = (int) get_post_meta( $post->ID, 'submitted_at_unix_ms', true );
	$client_label = (string) get_post_meta( $post->ID, 'client_submitted_at', true );
	$client_ms    = (int) get_post_meta( $post->ID, 'client_submitted_unix_ms', true );
	$time_spent   = (int) get_post_meta( $post->ID, 'time_spent_seconds', true );
	$answers_json = (string) get_post_meta( $post->ID, 'answers', true );
	$answers      = json_decode( $answers_json, true );
	$questions    = $page_id ? dmc_exam_get_questions( $page_id ) : [];

	?>
	<table class="widefat striped" style="margin-bottom:16px;">
		<tbody>
			<tr>
				<th style="width:220px;"><?php esc_html_e( 'Thí sinh', 'flatsome-child' ); ?></th>
				<td><?php echo esc_html( $candidate ?: '—' ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Thời gian nộp (server)', 'flatsome-child' ); ?></th>
				<td><?php echo esc_html( $unix_ms ? dmc_exam_format_unix_ms( $unix_ms ) : $submitted ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Thời gian nộp (trình duyệt)', 'flatsome-child' ); ?></th>
				<td>
					<?php
					if ( $client_label ) {
						echo esc_html( $client_label );
					} elseif ( $client_ms ) {
						echo esc_html( dmc_exam_format_unix_ms( $client_ms ) );
					} else {
						echo '—';
					}
					?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Thời gian làm bài', 'flatsome-child' ); ?></th>
				<td>
					<?php
					if ( $time_spent > 0 ) {
						echo esc_html( sprintf( '%02d:%02d', floor( $time_spent / 60 ), $time_spent % 60 ) );
					} else {
						echo '—';
					}
					?>
				</td>
			</tr>
		</tbody>
	</table>

	<?php if ( ! empty( $questions ) && is_array( $answers ) ) : ?>
		<ol style="margin:0;padding-left:20px;">
			<?php foreach ( $questions as $question ) : ?>
				<?php
				$qid    = (string) $question['id'];
				$choice = $answers[ $qid ] ?? '';
				$labels = [
					'a' => $question['answers']['a'],
					'b' => $question['answers']['b'],
					'c' => $question['answers']['c'],
					'd' => $question['answers']['d'],
				];
				?>
				<li style="margin-bottom:12px;">
					<strong><?php echo esc_html( $question['text'] ); ?></strong><br>
					<?php
					printf(
						/* translators: 1: selected letter, 2: answer text */
						esc_html__( 'Đã chọn: %1$s — %2$s', 'flatsome-child' ),
						esc_html( strtoupper( $choice ) ),
						esc_html( $labels[ $choice ] ?? '—' )
					);
					?>
				</li>
			<?php endforeach; ?>
		</ol>
	<?php else : ?>
		<p><?php esc_html_e( 'Không có dữ liệu câu trả lời.', 'flatsome-child' ); ?></p>
	<?php endif; ?>
	<?php
}
