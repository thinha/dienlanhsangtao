<?php
/**
 * Exam — setup, CPT, assets, AJAX.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_stylesheet_directory() . '/includes/exam/helpers.php';
require_once get_stylesheet_directory() . '/includes/exam/acf-fields.php';
require_once get_stylesheet_directory() . '/includes/exam/admin.php';

/**
 * Register submission post type.
 */
function dmc_exam_register_post_type() {
	register_post_type(
		'dmc_exam_submission',
		[
			'labels'              => [
				'name'               => __( 'Kết quả thi', 'flatsome-child' ),
				'singular_name'      => __( 'Kết quả thi', 'flatsome-child' ),
				'menu_name'          => __( 'Kết quả thi', 'flatsome-child' ),
				'all_items'          => __( 'Tất cả kết quả', 'flatsome-child' ),
				'view_item'          => __( 'Xem kết quả', 'flatsome-child' ),
				'search_items'       => __( 'Tìm kết quả', 'flatsome-child' ),
				'not_found'          => __( 'Chưa có kết quả', 'flatsome-child' ),
				'not_found_in_trash' => __( 'Không có kết quả trong thùng rác', 'flatsome-child' ),
			],
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_icon'           => 'dashicons-welcome-learn-more',
			'menu_position'       => 26,
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'hierarchical'        => false,
			'supports'            => [ 'title' ],
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'can_export'          => true,
			'delete_with_user'    => false,
		]
	);
}
add_action( 'init', 'dmc_exam_register_post_type' );

/**
 * Enqueue exam assets.
 */
function dmc_exam_enqueue_assets() {
	if ( ! dmc_is_exam_layout() ) {
		return;
	}

	$theme_dir = get_stylesheet_directory();
	$theme_uri = get_stylesheet_directory_uri();
	$js_file   = $theme_dir . '/assets/js/exam.js';
	$hp_js     = $theme_dir . '/assets/js/homepage.js';

	if ( file_exists( $hp_js ) ) {
		wp_enqueue_script(
			'dmc-homepage',
			$theme_uri . '/assets/js/homepage.js',
			[],
			filemtime( $hp_js ),
			true
		);

		$cart_count = function_exists( 'dmc_cart_item_count' ) ? dmc_cart_item_count() : 0;

		wp_localize_script(
			'dmc-homepage',
			'dmcHomepage',
			[
				'cartCount'  => $cart_count,
				'flashEnd'   => 0,
				'searchUrl'  => home_url( '/' ),
				'shopUrl'    => class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ),
				'slideDelay' => 4000,
				'slideSpeed' => 600,
			]
		);
	}

	wp_enqueue_script(
		'dmc-exam',
		$theme_uri . '/assets/js/exam.js',
		[],
		file_exists( $js_file ) ? filemtime( $js_file ) : '1.0.0',
		true
	);

	$page_id     = get_the_ID();
	$time_limit  = max( 0, (int) dmc_exam_field( 'exam_time_limit', 0 ) );
	$questions   = dmc_exam_get_questions( $page_id );
	$require_name = (bool) dmc_exam_field( 'exam_require_name', true );

	wp_localize_script(
		'dmc-exam',
		'dmcExam',
		[
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( 'dmc_exam_submit' ),
			'pageId'       => $page_id,
			'timeLimitMin' => $time_limit,
			'questionCount'=> count( $questions ),
			'requireName'  => $require_name,
			'messages'     => [
				'required'      => __( 'Vui lòng trả lời tất cả câu hỏi trước khi nộp bài.', 'flatsome-child' ),
				'nameRequired'  => __( 'Vui lòng nhập họ tên thí sinh.', 'flatsome-child' ),
				'submitting'    => __( 'Đang gửi bài...', 'flatsome-child' ),
				'submitError'   => __( 'Không gửi được bài. Vui lòng thử lại.', 'flatsome-child' ),
				'timeUp'        => __( 'Hết giờ làm bài. Bài thi sẽ được nộp tự động.', 'flatsome-child' ),
			],
		]
	);
}
add_action( 'wp_enqueue_scripts', 'dmc_exam_enqueue_assets', 99 );

/**
 * Body class for exam layout.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function dmc_exam_body_class( $classes ) {
	if ( dmc_is_exam_layout() ) {
		$classes[] = 'dmc-exam-page';
	}

	return $classes;
}
add_filter( 'body_class', 'dmc_exam_body_class' );

/**
 * Handle exam submission via AJAX.
 */
function dmc_exam_ajax_submit() {
	check_ajax_referer( 'dmc_exam_submit', 'nonce' );

	$page_id = isset( $_POST['page_id'] ) ? absint( $_POST['page_id'] ) : 0;

	if ( ! $page_id || 'page-templates/exam.php' !== get_page_template_slug( $page_id ) ) {
		wp_send_json_error(
			[ 'message' => __( 'Bài thi không hợp lệ.', 'flatsome-child' ) ],
			400
		);
	}

	$candidate_name = isset( $_POST['candidate_name'] ) ? sanitize_text_field( wp_unslash( $_POST['candidate_name'] ) ) : '';
	$require_name   = (bool) get_field( 'exam_require_name', $page_id );

	if ( $require_name && '' === trim( $candidate_name ) ) {
		wp_send_json_error(
			[ 'message' => __( 'Vui lòng nhập họ tên thí sinh.', 'flatsome-child' ) ],
			422
		);
	}

	$questions = dmc_exam_get_questions( $page_id );

	if ( empty( $questions ) ) {
		wp_send_json_error(
			[ 'message' => __( 'Bài thi chưa có câu hỏi.', 'flatsome-child' ) ],
			400
		);
	}

	$raw_answers = isset( $_POST['answers'] ) ? wp_unslash( $_POST['answers'] ) : [];
	$answers     = [];

	if ( ! is_array( $raw_answers ) ) {
		wp_send_json_error(
			[ 'message' => __( 'Dữ liệu câu trả lời không hợp lệ.', 'flatsome-child' ) ],
			400
		);
	}

	foreach ( $questions as $question ) {
		$qid = (string) $question['id'];
		$key = 'q_' . $qid;
		$choice = isset( $raw_answers[ $key ] ) ? strtolower( sanitize_text_field( $raw_answers[ $key ] ) ) : '';

		if ( ! in_array( $choice, [ 'a', 'b', 'c', 'd' ], true ) ) {
			wp_send_json_error(
				[
					'message' => sprintf(
						/* translators: %d: question number */
						__( 'Vui lòng chọn đáp án cho câu %d.', 'flatsome-child' ),
						$question['id']
					),
				],
				422
			);
		}

		$answers[ $qid ] = $choice;
	}

	$time_spent   = isset( $_POST['time_spent_seconds'] ) ? max( 0, absint( $_POST['time_spent_seconds'] ) ) : 0;
	$client_ms    = isset( $_POST['client_submitted_unix_ms'] ) ? absint( $_POST['client_submitted_unix_ms'] ) : 0;
	$client_label = isset( $_POST['client_submitted_label'] ) ? sanitize_text_field( wp_unslash( $_POST['client_submitted_label'] ) ) : '';
	$server_now   = dmc_exam_now_with_ms();
	$show_result  = (bool) get_field( 'exam_show_result', $page_id );
	$score        = null;
	$correct      = 0;
	$gradable     = 0;

	foreach ( $questions as $question ) {
		$correct_key = $question['correct'];

		if ( ! in_array( $correct_key, [ 'a', 'b', 'c', 'd' ], true ) ) {
			continue;
		}

		++$gradable;

		if ( ( $answers[ (string) $question['id'] ] ?? '' ) === $correct_key ) {
			++$correct;
		}
	}

	if ( $gradable > 0 ) {
		$score = round( ( $correct / $gradable ) * 100, 1 );
	}

	$exam_title = get_the_title( $page_id );
	$post_title = $candidate_name
		? sprintf( '%s — %s', $candidate_name, $exam_title )
		: sprintf( '%s — %s', $exam_title, $server_now['formatted'] );

	$submission_id = wp_insert_post(
		[
			'post_type'   => 'dmc_exam_submission',
			'post_status' => 'publish',
			'post_title'  => $post_title,
		],
		true
	);

	if ( is_wp_error( $submission_id ) ) {
		wp_send_json_error(
			[ 'message' => __( 'Không lưu được kết quả.', 'flatsome-child' ) ],
			500
		);
	}

	update_post_meta( $submission_id, 'exam_page_id', $page_id );
	update_post_meta( $submission_id, 'exam_page_title', $exam_title );
	update_post_meta( $submission_id, 'candidate_name', $candidate_name );
	update_post_meta( $submission_id, 'answers', wp_json_encode( $answers ) );
	update_post_meta( $submission_id, 'time_spent_seconds', $time_spent );
	update_post_meta( $submission_id, 'submitted_at', $server_now['formatted'] );
	update_post_meta( $submission_id, 'submitted_at_unix_ms', $server_now['unix_ms'] );
	update_post_meta( $submission_id, 'submitted_at_iso', $server_now['iso'] );
	update_post_meta( $submission_id, 'client_submitted_at', $client_label );
	update_post_meta( $submission_id, 'client_submitted_unix_ms', $client_ms );

	if ( null !== $score ) {
		update_post_meta( $submission_id, 'score_percent', $score );
		update_post_meta( $submission_id, 'correct_count', $correct );
		update_post_meta( $submission_id, 'gradable_count', $gradable );
	}

	$response = [
		'message'      => __( 'Đã nộp bài thành công.', 'flatsome-child' ),
		'submitted_at' => $server_now['formatted'],
		'unix_ms'      => $server_now['unix_ms'],
		'submission_id'=> $submission_id,
	];

	if ( $show_result && null !== $score ) {
		$response['score'] = $score;
		$response['correct_count'] = $correct;
		$response['gradable_count'] = $gradable;
	}

	wp_send_json_success( $response );
}
add_action( 'wp_ajax_dmc_exam_submit', 'dmc_exam_ajax_submit' );
add_action( 'wp_ajax_nopriv_dmc_exam_submit', 'dmc_exam_ajax_submit' );
