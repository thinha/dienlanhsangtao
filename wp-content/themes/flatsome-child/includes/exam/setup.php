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
require_once get_stylesheet_directory() . '/includes/exam/import.php';
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
			'show_in_menu'        => false,
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
 * Finalize expired sessions early on exam pages.
 */
function dmc_exam_bootstrap_session() {
	if ( is_admin() || ! dmc_is_exam_layout() || wp_doing_ajax() ) {
		return;
	}

	$page_id = get_queried_object_id();

	if ( ! $page_id ) {
		return;
	}

	$flash = dmc_exam_finalize_expired_session( $page_id );

	if ( 'timeout' === $flash && empty( $_GET['exam'] ) ) {
		$redirect = add_query_arg( 'exam', 'timeout', get_permalink( $page_id ) );
		wp_safe_redirect( $redirect );
		exit;
	}
}
add_action( 'template_redirect', 'dmc_exam_bootstrap_session', 5 );

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

	wp_enqueue_script(
		'dmc-exam',
		$theme_uri . '/assets/js/exam.js',
		[],
		file_exists( $js_file ) ? filemtime( $js_file ) : '1.0.0',
		true
	);

	wp_register_style( 'dmc-exam', false, [], '1.0.0' );
	wp_enqueue_style( 'dmc-exam' );
	wp_add_inline_style(
		'dmc-exam',
		'.dmc-exam-question__link{color:#1565c0;text-decoration:underline;word-break:break-word}.dmc-exam-question__link:hover,.dmc-exam-question__link:focus{color:#0d47a1}'
	);

	$page_id     = get_the_ID();
	$time_limit  = dmc_exam_get_time_limit_seconds( $page_id );
	$session     = dmc_exam_get_session( $page_id );
	$remaining   = 0;
	$has_session = false;

	if ( $session && ! dmc_exam_session_is_expired( $session ) ) {
		$has_session = true;
		$remaining   = $time_limit > 0 ? dmc_exam_remaining_seconds( $session ) : 0;
	}

	wp_localize_script(
		'dmc-exam',
		'dmcExam',
		[
			'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
			'nonce'            => wp_create_nonce( 'dmc_exam' ),
			'pageId'           => $page_id,
			'timeLimitSeconds' => $time_limit,
			'remainingSeconds' => $remaining,
			'hasSession'       => $has_session,
			'startedAt'        => $has_session ? (int) ( $session['started_at'] ?? 0 ) : 0,
			'messages'         => [
				'required'       => __( 'Vui lòng trả lời tất cả câu hỏi trước khi nộp bài.', 'flatsome-child' ),
				'nameRequired'   => __( 'Vui lòng nhập họ tên thí sinh.', 'flatsome-child' ),
				'phoneRequired'  => __( 'Vui lòng nhập số điện thoại.', 'flatsome-child' ),
				'phoneInvalid'   => __( 'Số điện thoại phải là 10 số (VD: 0943980279) hoặc dạng +84 (VD: +84943980279).', 'flatsome-child' ),
				'deptRequired'   => __( 'Vui lòng nhập khoa.', 'flatsome-child' ),
				'alreadyDone'    => __( 'Bạn đã làm bài thi này rồi. Không thể làm lại.', 'flatsome-child' ),
				'submitting'     => __( 'Đang gửi bài...', 'flatsome-child' ),
				'starting'       => __( 'Đang bắt đầu...', 'flatsome-child' ),
				'submitError'    => __( 'Không gửi được bài. Vui lòng thử lại.', 'flatsome-child' ),
				'startError'     => __( 'Không bắt đầu được bài thi. Vui lòng thử lại.', 'flatsome-child' ),
				'timeUp'         => __( 'Hết giờ làm bài. Hệ thống sẽ nộp bài và kết thúc phiên.', 'flatsome-child' ),
				'sessionExpired' => __( 'Phiên làm bài đã hết. Vui lòng đăng ký lại nếu còn quyền thi.', 'flatsome-child' ),
				'doneRedirect'   => __( 'Đã nộp bài. Đang quay lại màn hình đăng ký...', 'flatsome-child' ),
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
 * AJAX: start exam session after gate form.
 */
function dmc_exam_ajax_start() {
	check_ajax_referer( 'dmc_exam', 'nonce' );

	$page_id = isset( $_POST['page_id'] ) ? absint( $_POST['page_id'] ) : 0;

	if ( ! $page_id || ! dmc_exam_is_exam_page( $page_id ) ) {
		wp_send_json_error(
			[ 'message' => __( 'Bài thi không hợp lệ.', 'flatsome-child' ) ],
			400
		);
	}

	$name       = isset( $_POST['candidate_name'] ) ? sanitize_text_field( wp_unslash( $_POST['candidate_name'] ) ) : '';
	$phone      = isset( $_POST['candidate_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['candidate_phone'] ) ) : '';
	$department = isset( $_POST['candidate_department'] ) ? sanitize_text_field( wp_unslash( $_POST['candidate_department'] ) ) : '';

	if ( '' === trim( $name ) ) {
		wp_send_json_error(
			[ 'message' => __( 'Vui lòng nhập họ tên thí sinh.', 'flatsome-child' ) ],
			422
		);
	}

	if ( '' === trim( $phone ) ) {
		wp_send_json_error(
			[ 'message' => __( 'Vui lòng nhập số điện thoại.', 'flatsome-child' ) ],
			422
		);
	}

	if ( ! dmc_exam_is_valid_phone( $phone ) ) {
		wp_send_json_error(
			[ 'message' => __( 'Số điện thoại không hợp lệ.', 'flatsome-child' ) ],
			422
		);
	}

	if ( '' === trim( $department ) ) {
		wp_send_json_error(
			[ 'message' => __( 'Vui lòng nhập khoa.', 'flatsome-child' ) ],
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

	if ( dmc_exam_find_existing_submission( $page_id, $phone, $name ) ) {
		wp_send_json_error(
			[ 'message' => __( 'Họ tên / số điện thoại này đã làm bài thi rồi. Không thể làm lại.', 'flatsome-child' ) ],
			403
		);
	}

	// Expired lock without cookie → treat as already finished.
	if ( dmc_exam_finalize_expired_phone_lock( $page_id, $phone, $name, $department ) ) {
		wp_send_json_error(
			[ 'message' => __( 'Họ tên / số điện thoại này đã hết phiên làm bài. Không thể làm lại.', 'flatsome-child' ) ],
			403
		);
	}

	$existing_lock   = dmc_exam_get_phone_lock( $page_id, $phone );
	$current_session = dmc_exam_get_session( $page_id );
	$time_limit      = dmc_exam_get_time_limit_seconds( $page_id );

	if ( $current_session && ! dmc_exam_session_is_expired( $current_session ) ) {
		if ( dmc_exam_lock_matches_candidate( $current_session, $name, $phone ) ) {
			wp_send_json_success(
				[
					'message'          => __( 'Tiếp tục làm bài.', 'flatsome-child' ),
					'remainingSeconds' => $time_limit > 0 ? dmc_exam_remaining_seconds( $current_session ) : 0,
					'reload'           => true,
				]
			);
		}

		dmc_exam_clear_session();
	}

	if ( $existing_lock && dmc_exam_phone_lock_is_active( $existing_lock ) ) {
		if ( dmc_exam_lock_matches_candidate( $existing_lock, $name, $phone ) ) {
			$resumed = dmc_exam_resume_session_from_lock( $page_id, $existing_lock );

			if ( $resumed ) {
				wp_send_json_success(
					[
						'message'          => __( 'Tiếp tục làm bài.', 'flatsome-child' ),
						'remainingSeconds' => $time_limit > 0 ? dmc_exam_remaining_seconds( $resumed ) : 0,
						'reload'           => true,
					]
				);
			}

			dmc_exam_clear_phone_lock( $page_id, $phone );
		} else {
			wp_send_json_error(
				[ 'message' => __( 'Số điện thoại này đang có phiên làm bài. Không thể bắt đầu lại.', 'flatsome-child' ) ],
				403
			);
		}
	}

	if ( $existing_lock ) {
		dmc_exam_clear_phone_lock( $page_id, $phone );
	}

	// Clear any stale browser session before starting fresh.
	dmc_exam_clear_session();

	$started_at = time();
	$expires_at = $time_limit > 0 ? ( $started_at + $time_limit ) : ( $started_at + DAY_IN_SECONDS );
	$per_attempt = dmc_exam_get_questions_per_attempt( $page_id );
	$question_ids = dmc_exam_pick_random_question_ids( $questions, $per_attempt );

	if ( empty( $question_ids ) ) {
		wp_send_json_error(
			[ 'message' => __( 'Bài thi chưa có câu hỏi.', 'flatsome-child' ) ],
			400
		);
	}

	$session_data = [
		'page_id'            => $page_id,
		'name'               => $name,
		'phone'              => $phone,
		'phone_normalized'   => dmc_exam_normalize_phone( $phone ),
		'department'         => $department,
		'started_at'         => $started_at,
		'time_limit_seconds' => $time_limit,
		'time_limit_min'     => (int) floor( $time_limit / 60 ),
		'expires_at'         => $expires_at,
		'question_ids'       => $question_ids,
	];

	dmc_exam_set_session( $session_data );
	dmc_exam_set_phone_lock( $page_id, $session_data );

	wp_send_json_success(
		[
			'message'          => __( 'Bắt đầu làm bài.', 'flatsome-child' ),
			'remainingSeconds' => $time_limit,
			'reload'           => true,
		]
	);
}
add_action( 'wp_ajax_dmc_exam_start', 'dmc_exam_ajax_start' );
add_action( 'wp_ajax_nopriv_dmc_exam_start', 'dmc_exam_ajax_start' );

/**
 * Handle exam submission via AJAX.
 */
function dmc_exam_ajax_submit() {
	check_ajax_referer( 'dmc_exam', 'nonce' );

	$page_id = isset( $_POST['page_id'] ) ? absint( $_POST['page_id'] ) : 0;

	if ( ! $page_id || ! dmc_exam_is_exam_page( $page_id ) ) {
		wp_send_json_error(
			[ 'message' => __( 'Bài thi không hợp lệ.', 'flatsome-child' ) ],
			400
		);
	}

	$session = dmc_exam_get_session( $page_id );

	if ( ! $session ) {
		wp_send_json_error(
			[
				'message'  => __( 'Phiên làm bài không hợp lệ hoặc đã hết. Vui lòng đăng ký lại.', 'flatsome-child' ),
				'redirect' => true,
			],
			403
		);
	}

	$is_timeout = ! empty( $_POST['is_timeout'] );
	$expired    = dmc_exam_session_is_expired( $session );

	if ( $expired ) {
		$is_timeout = true;
	}

	$candidate_name = (string) ( $session['name'] ?? '' );
	$candidate_phone = (string) ( $session['phone'] ?? '' );
	$candidate_dept  = (string) ( $session['department'] ?? '' );

	if ( dmc_exam_find_existing_submission( $page_id, $candidate_phone, $candidate_name ) ) {
		dmc_exam_clear_session();
		wp_send_json_error(
			[
				'message'  => __( 'Bạn đã nộp bài trước đó. Không thể nộp lại.', 'flatsome-child' ),
				'redirect' => true,
			],
			403
		);
	}

	$questions = dmc_exam_get_session_questions( $page_id, $session );

	if ( empty( $questions ) ) {
		wp_send_json_error(
			[ 'message' => __( 'Bài thi chưa có câu hỏi.', 'flatsome-child' ) ],
			400
		);
	}

	$raw_answers = isset( $_POST['answers'] ) ? wp_unslash( $_POST['answers'] ) : [];
	$answers     = [];

	if ( ! is_array( $raw_answers ) ) {
		$raw_answers = [];
	}

	foreach ( $questions as $question ) {
		$qid            = (string) $question['id'];
		$key            = 'q_' . $qid;
		$display_number = (int) ( $question['display_number'] ?? $question['id'] );
		$choice         = isset( $raw_answers[ $key ] ) ? strtolower( sanitize_text_field( $raw_answers[ $key ] ) ) : '';

		if ( in_array( $choice, [ 'a', 'b', 'c', 'd' ], true ) ) {
			$answers[ $qid ] = $choice;
			continue;
		}

		if ( ! $is_timeout ) {
			wp_send_json_error(
				[
					'message' => sprintf(
						/* translators: %d: question number */
						__( 'Vui lòng chọn đáp án cho câu %d.', 'flatsome-child' ),
						$display_number
					),
				],
				422
			);
		}
	}

	$started_at    = (int) ( $session['started_at'] ?? time() );
	$time_spent    = max( 0, time() - $started_at );
	$client_ms     = isset( $_POST['client_submitted_unix_ms'] ) ? absint( $_POST['client_submitted_unix_ms'] ) : 0;
	$client_label  = isset( $_POST['client_submitted_label'] ) ? sanitize_text_field( wp_unslash( $_POST['client_submitted_label'] ) ) : '';
	$time_spent_ms = isset( $_POST['time_spent_ms'] ) ? absint( $_POST['time_spent_ms'] ) : 0;

	if ( $time_spent_ms <= 0 && $client_ms > 0 && $started_at > 0 ) {
		$time_spent_ms = max( 0, $client_ms - ( $started_at * 1000 ) );
	}

	if ( $time_spent_ms <= 0 && $time_spent > 0 ) {
		$time_spent_ms = $time_spent * 1000;
	}

	$result = dmc_exam_save_submission(
		$page_id,
		[
			'name'       => $candidate_name,
			'phone'      => $candidate_phone,
			'department' => $candidate_dept,
		],
		$answers,
		$time_spent,
		$is_timeout,
		[
			'label'         => $client_label,
			'unix_ms'       => $client_ms,
			'time_spent_ms' => $time_spent_ms,
		],
		$questions
	);

	if ( is_wp_error( $result ) ) {
		wp_send_json_error(
			[ 'message' => $result->get_error_message() ?: __( 'Không lưu được kết quả.', 'flatsome-child' ) ],
			500
		);
	}

	dmc_exam_clear_session();
	dmc_exam_clear_phone_lock( $page_id, $candidate_phone );

	wp_send_json_success( $result );
}
add_action( 'wp_ajax_dmc_exam_submit', 'dmc_exam_ajax_submit' );
add_action( 'wp_ajax_nopriv_dmc_exam_submit', 'dmc_exam_ajax_submit' );
