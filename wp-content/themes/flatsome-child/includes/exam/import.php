<?php
/**
 * Exam — import questions from CSV (Excel-compatible).
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CSV column headers for the sample template.
 *
 * @return string[]
 */
function dmc_exam_import_template_headers() {
	return [
		__( 'Nội dung câu hỏi', 'flatsome-child' ),
		__( 'Đáp án A', 'flatsome-child' ),
		__( 'Đáp án B', 'flatsome-child' ),
		__( 'Đáp án C', 'flatsome-child' ),
		__( 'Đáp án D', 'flatsome-child' ),
		__( 'Đáp án đúng', 'flatsome-child' ),
	];
}

/**
 * Sample rows for the downloadable template.
 *
 * @return array<int, string[]>
 */
function dmc_exam_import_template_sample_rows() {
	return [
		[
			__( 'Điện áp danh định của lưới điện sinh hoạt Việt Nam là bao nhiêu?', 'flatsome-child' ),
			'110V',
			'220V',
			'380V',
			'440V',
			'B',
		],
		[
			__( 'Thiết bị nào dùng để bảo vệ quá tải trong mạch điện?', 'flatsome-child' ),
			__( 'Cầu chì', 'flatsome-child' ),
			__( 'Contactor', 'flatsome-child' ),
			__( 'Biến áp', 'flatsome-child' ),
			__( 'Đèn báo', 'flatsome-child' ),
			'A',
		],
	];
}

/**
 * Normalize a CSV header cell for mapping.
 *
 * @param string $header Raw header.
 * @return string
 */
function dmc_exam_import_normalize_header( $header ) {
	$header = str_replace( [ 'đ', 'Đ' ], [ 'd', 'D' ], (string) $header );
	$header = remove_accents( strtolower( trim( $header ) ) );
	$header = preg_replace( '/[^a-z0-9]+/', '_', $header );

	return trim( (string) $header, '_' );
}

/**
 * Map normalized header to ACF field key.
 *
 * @param string $header Normalized header.
 * @return string
 */
function dmc_exam_import_map_header( $header ) {
	$map = [
		'noi_dung_cau_hoi' => 'question_text',
		'cau_hoi'          => 'question_text',
		'question'         => 'question_text',
		'question_text'    => 'question_text',
		'dap_an_a'         => 'answer_a',
		'ap_an_a'          => 'answer_a',
		'answer_a'         => 'answer_a',
		'dap_an_b'         => 'answer_b',
		'ap_an_b'          => 'answer_b',
		'answer_b'         => 'answer_b',
		'dap_an_c'         => 'answer_c',
		'ap_an_c'          => 'answer_c',
		'answer_c'         => 'answer_c',
		'dap_an_d'         => 'answer_d',
		'ap_an_d'          => 'answer_d',
		'answer_d'         => 'answer_d',
		'dap_an_dung'      => 'correct_answer',
		'ap_an_dung'       => 'correct_answer',
		'correct_answer'   => 'correct_answer',
		'dap_an'           => 'correct_answer',
	];

	return $map[ $header ] ?? '';
}

/**
 * Build header index map from parsed CSV headers.
 *
 * @param string[] $headers Header cells.
 * @return array<int, string>
 */
function dmc_exam_import_build_header_map( array $headers ) {
	$map = [];

	foreach ( $headers as $index => $header ) {
		$key = dmc_exam_import_map_header( dmc_exam_import_normalize_header( $header ) );

		if ( '' !== $key ) {
			$map[ $index ] = $key;
		}
	}

	// Fallback: standard 6-column template order (by position).
	$fallback = [
		0 => 'question_text',
		1 => 'answer_a',
		2 => 'answer_b',
		3 => 'answer_c',
		4 => 'answer_d',
		5 => 'correct_answer',
	];

	if ( count( $headers ) >= 6 ) {
		$mapped_keys = array_values( $map );

		foreach ( $fallback as $index => $key ) {
			if ( ! in_array( $key, $mapped_keys, true ) && isset( $headers[ $index ] ) ) {
				$map[ $index ] = $key;
			}
		}
	}

	ksort( $map );

	return $map;
}

/**
 * Normalize correct answer cell to a/b/c/d.
 *
 * @param string $value Raw value.
 * @return string
 */
function dmc_exam_import_normalize_correct_answer( $value ) {
	$value = strtolower( trim( (string) $value ) );
	$value = preg_replace( '/[^a-d]/', '', $value );

	return in_array( $value, [ 'a', 'b', 'c', 'd' ], true ) ? $value : '';
}

/**
 * Parse uploaded CSV into ACF repeater rows.
 *
 * @param string $file_path Absolute file path.
 * @return array{rows: array<int, array<string, string>>, errors: string[]}
 */
function dmc_exam_parse_questions_csv( $file_path ) {
	$result = [
		'rows'   => [],
		'errors' => [],
	];

	if ( ! is_readable( $file_path ) ) {
		$result['errors'][] = __( 'Không đọc được file import.', 'flatsome-child' );
		return $result;
	}

	$handle = fopen( $file_path, 'r' );

	if ( false === $handle ) {
		$result['errors'][] = __( 'Không mở được file import.', 'flatsome-child' );
		return $result;
	}

	$first_line = fgets( $handle );

	if ( false === $first_line ) {
		fclose( $handle );
		$result['errors'][] = __( 'File import trống.', 'flatsome-child' );
		return $result;
	}

	$first_line = preg_replace( '/^\xEF\xBB\xBF/', '', $first_line );
	$delimiter  = false !== strpos( $first_line, ';' ) ? ';' : ',';
	$headers    = str_getcsv( $first_line, $delimiter );
	$map        = dmc_exam_import_build_header_map( $headers );

	if ( ! in_array( 'question_text', $map, true ) ) {
		fclose( $handle );
		$result['errors'][] = __( 'File thiếu cột "Nội dung câu hỏi".', 'flatsome-child' );
		return $result;
	}

	$line_number = 1;

	while ( ( $cells = fgetcsv( $handle, 0, $delimiter ) ) !== false ) {
		++$line_number;

		if ( 1 === count( $cells ) && '' === trim( (string) $cells[0] ) ) {
			continue;
		}

		$row = [
			'question_text'  => '',
			'answer_a'       => '',
			'answer_b'       => '',
			'answer_c'       => '',
			'answer_d'       => '',
			'correct_answer' => '',
		];

		foreach ( $map as $index => $key ) {
			$row[ $key ] = isset( $cells[ $index ] ) ? trim( (string) $cells[ $index ] ) : '';
		}

		if ( '' === $row['question_text'] ) {
			continue;
		}

		foreach ( [ 'answer_a', 'answer_b', 'answer_c', 'answer_d' ] as $answer_key ) {
			if ( '' === $row[ $answer_key ] ) {
				$result['errors'][] = sprintf(
					/* translators: 1: line number, 2: answer label */
					__( 'Dòng %1$d thiếu %2$s.', 'flatsome-child' ),
					$line_number,
					strtoupper( substr( $answer_key, -1 ) )
				);
				continue 2;
			}
		}

		$correct = dmc_exam_import_normalize_correct_answer( $row['correct_answer'] );

		if ( '' !== trim( (string) $row['correct_answer'] ) && '' === $correct ) {
			$result['errors'][] = sprintf(
				/* translators: %d: line number */
				__( 'Dòng %d: Đáp án đúng phải là A, B, C hoặc D (hoặc để trống).', 'flatsome-child' ),
				$line_number
			);
			continue;
		}

		$row['correct_answer'] = $correct;
		$result['rows'][]      = $row;
	}

	fclose( $handle );

	if ( empty( $result['rows'] ) && empty( $result['errors'] ) ) {
		$result['errors'][] = __( 'Không tìm thấy câu hỏi hợp lệ trong file.', 'flatsome-child' );
	}

	return $result;
}

/**
 * Save imported questions to an exam page.
 *
 * @param int                             $page_id Exam page ID.
 * @param array<int, array<string, string>> $rows  Parsed rows.
 * @param string                          $mode    replace|append.
 * @return int|WP_Error Number of questions saved.
 */
function dmc_exam_save_imported_questions( $page_id, array $rows, $mode = 'replace' ) {
	$page_id = (int) $page_id;

	if ( ! $page_id || 'page-templates/exam.php' !== get_page_template_slug( $page_id ) ) {
		return new WP_Error( 'invalid_exam', __( 'Bài thi không hợp lệ.', 'flatsome-child' ) );
	}

	if ( empty( $rows ) ) {
		return new WP_Error( 'empty_import', __( 'Không có câu hỏi để import.', 'flatsome-child' ) );
	}

	if ( ! function_exists( 'update_field' ) ) {
		return new WP_Error( 'acf_missing', __( 'ACF chưa được kích hoạt.', 'flatsome-child' ) );
	}

	$payload = $rows;

	if ( 'append' === $mode ) {
		$existing = get_field( 'exam_questions', $page_id );
		$existing = is_array( $existing ) ? $existing : [];
		$payload  = array_merge( $existing, $rows );
	}

	$updated = update_field( 'exam_questions', $payload, $page_id );

	if ( ! $updated && ! empty( $payload ) ) {
		// update_field may return false when values are unchanged; verify count.
		$stored = get_field( 'exam_questions', $page_id );
		if ( ! is_array( $stored ) || count( $stored ) !== count( $payload ) ) {
			return new WP_Error( 'save_failed', __( 'Không lưu được câu hỏi import.', 'flatsome-child' ) );
		}
	}

	return count( $payload );
}

/**
 * Stream the sample CSV template.
 *
 * @param int $page_id Optional exam page ID for filename.
 */
function dmc_exam_download_questions_template( $page_id = 0 ) {
	$page_id  = (int) $page_id;
	$slug     = $page_id ? sanitize_title( dmc_exam_get_event_name( $page_id ) ) : 'bai-thi';
	$filename = 'mau-cau-hoi-' . ( $slug ?: 'bai-thi' ) . '.csv';

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

	$output = fopen( 'php://output', 'w' );

	if ( false === $output ) {
		wp_die( esc_html__( 'Không tạo được file mẫu.', 'flatsome-child' ) );
	}

	fprintf( $output, "\xEF\xBB\xBF" );
	fputcsv( $output, dmc_exam_import_template_headers() );

	foreach ( dmc_exam_import_template_sample_rows() as $row ) {
		fputcsv( $output, $row );
	}

	fclose( $output );
}

/**
 * Convert normalized question rows to CSV cells.
 *
 * @param array<int, array<string, mixed>> $questions Question list.
 * @return array<int, string[]>
 */
function dmc_exam_questions_to_csv_rows( array $questions ) {
	$rows = [];

	foreach ( $questions as $question ) {
		$correct = strtolower( trim( (string) ( $question['correct'] ?? '' ) ) );
		$answers = is_array( $question['answers'] ?? null ) ? $question['answers'] : [];

		$rows[] = [
			(string) ( $question['text'] ?? '' ),
			(string) ( $answers['a'] ?? '' ),
			(string) ( $answers['b'] ?? '' ),
			(string) ( $answers['c'] ?? '' ),
			(string) ( $answers['d'] ?? '' ),
			in_array( $correct, [ 'a', 'b', 'c', 'd' ], true ) ? strtoupper( $correct ) : '',
		];
	}

	return $rows;
}

/**
 * Stream all exam questions as CSV (Excel-compatible).
 *
 * @param int $page_id Exam page ID.
 */
function dmc_exam_export_questions_csv( $page_id ) {
	$page_id   = (int) $page_id;
	$questions = dmc_exam_get_questions( $page_id );
	$slug      = sanitize_title( dmc_exam_get_event_name( $page_id ) );
	$filename  = 'cau-hoi-' . ( $slug ?: 'bai-thi' ) . '-' . gmdate( 'Y-m-d-His' ) . '.csv';

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

	$output = fopen( 'php://output', 'w' );

	if ( false === $output ) {
		wp_die( esc_html__( 'Không tạo được file export.', 'flatsome-child' ) );
	}

	fprintf( $output, "\xEF\xBB\xBF" );
	fputcsv( $output, dmc_exam_import_template_headers() );

	foreach ( dmc_exam_questions_to_csv_rows( $questions ) as $row ) {
		fputcsv( $output, $row );
	}

	fclose( $output );
}

/**
 * Build questions export URL for job fair screen.
 *
 * @param int $page_id Exam page ID.
 * @return string
 */
function dmc_exam_get_questions_export_url( $page_id ) {
	return wp_nonce_url(
		add_query_arg(
			[
				'page'    => 'dmc-job-fair',
				'action'  => 'dmc_exam_export_questions',
				'exam_id' => (int) $page_id,
			],
			admin_url( 'admin.php' )
		),
		'dmc_exam_export_questions_' . (int) $page_id
	);
}

/**
 * Build questions export URL for page edit screen.
 *
 * @param int $page_id Page ID.
 * @return string
 */
function dmc_exam_get_page_questions_export_url( $page_id ) {
	return wp_nonce_url(
		add_query_arg(
			[
				'action'  => 'dmc_exam_export_questions',
				'exam_id' => (int) $page_id,
			],
			admin_url( 'admin.php' )
		),
		'dmc_exam_export_questions_' . (int) $page_id
	);
}

/**
 * Handle template download requests.
 */
function dmc_exam_maybe_download_questions_template() {
	if ( ! is_admin() || ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	$action = '';

	if ( isset( $_GET['action'] ) ) {
		$action = sanitize_key( wp_unslash( $_GET['action'] ) );
	}

	if ( 'dmc_exam_download_template' !== $action && 'dmc_exam_export_questions' !== $action ) {
		return;
	}

	$page_id = isset( $_GET['exam_id'] ) ? absint( $_GET['exam_id'] ) : 0;

	if ( ! $page_id || ! dmc_exam_is_exam_page( $page_id ) ) {
		wp_die( esc_html__( 'Bài thi không hợp lệ.', 'flatsome-child' ) );
	}

	if ( 'dmc_exam_download_template' === $action ) {
		check_admin_referer( 'dmc_exam_download_template_' . $page_id );
		dmc_exam_download_questions_template( $page_id );
		exit;
	}

	check_admin_referer( 'dmc_exam_export_questions_' . $page_id );
	dmc_exam_export_questions_csv( $page_id );
	exit;
}
add_action( 'admin_init', 'dmc_exam_maybe_download_questions_template' );

/**
 * Whether a page uses the exam template.
 *
 * @param int $page_id Page ID.
 * @return bool
 */
function dmc_exam_is_exam_page( $page_id ) {
	return (int) $page_id > 0 && 'page-templates/exam.php' === get_page_template_slug( $page_id );
}

/**
 * Build template download URL.
 *
 * @param int $page_id Exam page ID.
 * @return string
 */
function dmc_exam_get_template_download_url( $page_id ) {
	return wp_nonce_url(
		add_query_arg(
			[
				'page'    => 'dmc-job-fair',
				'action'  => 'dmc_exam_download_template',
				'exam_id' => (int) $page_id,
			],
			admin_url( 'admin.php' )
		),
		'dmc_exam_download_template_' . (int) $page_id
	);
}

/**
 * Build template download URL for page edit screen.
 *
 * @param int $page_id Page ID.
 * @return string
 */
function dmc_exam_get_page_template_download_url( $page_id ) {
	return wp_nonce_url(
		add_query_arg(
			[
				'action'  => 'dmc_exam_download_template',
				'exam_id' => (int) $page_id,
			],
			admin_url( 'admin.php' )
		),
		'dmc_exam_download_template_' . (int) $page_id
	);
}

/**
 * Build redirect URL after import actions.
 *
 * @param int                  $page_id Exam page ID.
 * @param string               $context job_fair|page.
 * @param array<string, mixed> $args    Query args.
 * @return string
 */
function dmc_exam_import_redirect_url( $page_id, $context, array $args = [] ) {
	$page_id = (int) $page_id;

	if ( 'page' === $context ) {
		return add_query_arg( $args, get_edit_post_link( $page_id, 'raw' ) );
	}

	$args['page']    = 'dmc-job-fair';
	$args['exam_id'] = $page_id;

	return add_query_arg( $args, admin_url( 'admin.php' ) );
}

/**
 * Form element ID for page-edit import (avoids nested forms in #post).
 *
 * @param int $page_id Exam page ID.
 * @return string
 */
function dmc_exam_import_form_id( $page_id ) {
	return 'dmc-exam-import-form-' . (int) $page_id;
}

/**
 * Import form POST target.
 *
 * @return string
 */
function dmc_exam_import_form_action_url() {
	return admin_url( 'admin-post.php' );
}

/**
 * Human-readable upload error for import failures.
 *
 * @return string
 */
function dmc_exam_import_upload_error_message() {
	if ( empty( $_FILES['exam_questions_file'] ) || ! is_array( $_FILES['exam_questions_file'] ) ) {
		if ( empty( $_POST ) && ! empty( $_SERVER['CONTENT_LENGTH'] ) ) {
			return __( 'File quá lớn hoặc vượt giới hạn upload của server (post_max_size / upload_max_filesize).', 'flatsome-child' );
		}

		return __( 'Vui lòng chọn file CSV để import.', 'flatsome-child' );
	}

	$error = (int) ( $_FILES['exam_questions_file']['error'] ?? 0 );

	if ( UPLOAD_ERR_OK === $error ) {
		return __( 'Upload file thất bại. Vui lòng thử lại.', 'flatsome-child' );
	}

	$messages = [
		UPLOAD_ERR_INI_SIZE   => __( 'File vượt quá upload_max_filesize trên server.', 'flatsome-child' ),
		UPLOAD_ERR_FORM_SIZE  => __( 'File vượt quá giới hạn kích thước form.', 'flatsome-child' ),
		UPLOAD_ERR_PARTIAL    => __( 'File chỉ upload được một phần. Vui lòng thử lại.', 'flatsome-child' ),
		UPLOAD_ERR_NO_FILE    => __( 'Vui lòng chọn file CSV để import.', 'flatsome-child' ),
		UPLOAD_ERR_NO_TMP_DIR => __( 'Server thiếu thư mục tạm để nhận file upload.', 'flatsome-child' ),
		UPLOAD_ERR_CANT_WRITE => __( 'Server không ghi được file upload.', 'flatsome-child' ),
		UPLOAD_ERR_EXTENSION  => __( 'Upload bị chặn bởi extension PHP trên server.', 'flatsome-child' ),
	];

	return $messages[ $error ] ?? __( 'Upload file thất bại. Vui lòng thử lại.', 'flatsome-child' );
}

/**
 * Handle question import upload.
 */
function dmc_exam_handle_import_questions() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền import câu hỏi.', 'flatsome-child' ) );
	}

	$page_id = isset( $_POST['exam_id'] ) ? absint( $_POST['exam_id'] ) : 0;
	$context = isset( $_POST['import_context'] ) ? sanitize_key( wp_unslash( $_POST['import_context'] ) ) : 'job_fair';

	check_admin_referer( 'dmc_exam_import_' . $page_id, 'dmc_exam_import_nonce' );

	if ( ! $page_id || ! dmc_exam_is_exam_page( $page_id ) ) {
		wp_safe_redirect( dmc_exam_import_redirect_url( $page_id, $context, [ 'import_error' => 'invalid' ] ) );
		exit;
	}

	$file = isset( $_FILES['exam_questions_file'] ) && is_array( $_FILES['exam_questions_file'] )
		? $_FILES['exam_questions_file']
		: [];

	if ( empty( $file['name'] ) ) {
		set_transient(
			'dmc_exam_import_errors_' . get_current_user_id(),
			[ dmc_exam_import_upload_error_message() ],
			MINUTE_IN_SECONDS
		);
		wp_safe_redirect( dmc_exam_import_redirect_url( $page_id, $context, [ 'import_error' => 'no_file' ] ) );
		exit;
	}

	if ( ! empty( $file['error'] ) && UPLOAD_ERR_OK !== (int) $file['error'] ) {
		set_transient(
			'dmc_exam_import_errors_' . get_current_user_id(),
			[ dmc_exam_import_upload_error_message() ],
			MINUTE_IN_SECONDS
		);
		wp_safe_redirect( dmc_exam_import_redirect_url( $page_id, $context, [ 'import_error' => 'upload' ] ) );
		exit;
	}

	$extension = strtolower( pathinfo( (string) $file['name'], PATHINFO_EXTENSION ) );

	if ( ! in_array( $extension, [ 'csv', 'txt' ], true ) ) {
		wp_safe_redirect( dmc_exam_import_redirect_url( $page_id, $context, [ 'import_error' => 'type' ] ) );
		exit;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';

	$upload = wp_handle_upload(
		$file,
		[
			'test_form' => false,
			'test_type' => false,
			'mimes'     => [
				'csv' => 'text/csv',
				'txt' => 'text/plain',
			],
		]
	);

	if ( ! empty( $upload['error'] ) ) {
		set_transient(
			'dmc_exam_import_errors_' . get_current_user_id(),
			[ (string) $upload['error'] ],
			MINUTE_IN_SECONDS
		);
		wp_safe_redirect( dmc_exam_import_redirect_url( $page_id, $context, [ 'import_error' => 'upload' ] ) );
		exit;
	}

	$parsed = dmc_exam_parse_questions_csv( $upload['file'] );
	@unlink( $upload['file'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

	if ( ! empty( $parsed['errors'] ) ) {
		set_transient( 'dmc_exam_import_errors_' . get_current_user_id(), $parsed['errors'], MINUTE_IN_SECONDS );
		wp_safe_redirect( dmc_exam_import_redirect_url( $page_id, $context, [ 'import_error' => 'parse' ] ) );
		exit;
	}

	$mode   = isset( $_POST['import_mode'] ) && 'replace' === $_POST['import_mode'] ? 'replace' : 'append';
	$result = dmc_exam_save_imported_questions( $page_id, $parsed['rows'], $mode );

	if ( is_wp_error( $result ) ) {
		set_transient( 'dmc_exam_import_errors_' . get_current_user_id(), [ $result->get_error_message() ], MINUTE_IN_SECONDS );
		wp_safe_redirect( dmc_exam_import_redirect_url( $page_id, $context, [ 'import_error' => 'save' ] ) );
		exit;
	}

	wp_safe_redirect( dmc_exam_import_redirect_url( $page_id, $context, [ 'imported' => (int) $result ] ) );
	exit;
}
add_action( 'admin_post_dmc_exam_import_questions', 'dmc_exam_handle_import_questions' );

/**
 * Hidden import form shell on page edit (outside #post to avoid nested forms).
 */
function dmc_exam_render_page_import_form_shell() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( ! $screen || 'page' !== $screen->post_type || ! in_array( $screen->base, [ 'post' ], true ) ) {
		return;
	}

	global $post;

	if ( ! $post || ! dmc_exam_is_exam_page( $post->ID ) ) {
		return;
	}

	$page_id = (int) $post->ID;
	$form_id = dmc_exam_import_form_id( $page_id );
	?>
	<form
		id="<?php echo esc_attr( $form_id ); ?>"
		class="dmc-exam-import-form-shell"
		method="post"
		enctype="multipart/form-data"
		action="<?php echo esc_url( dmc_exam_import_form_action_url() ); ?>"
		hidden
	>
		<input type="hidden" name="action" value="dmc_exam_import_questions">
		<?php wp_nonce_field( 'dmc_exam_import_' . $page_id, 'dmc_exam_import_nonce' ); ?>
		<input type="hidden" name="exam_id" value="<?php echo esc_attr( (string) $page_id ); ?>">
		<input type="hidden" name="import_context" value="page">
	</form>
	<?php
}
add_action( 'admin_footer', 'dmc_exam_render_page_import_form_shell' );

/**
 * Render import notices.
 *
 * @param string $context job_fair|page.
 */
function dmc_exam_render_import_notices( $context = 'job_fair' ) {
	$user_id = get_current_user_id();
	$errors  = get_transient( 'dmc_exam_import_errors_' . $user_id );

	if ( is_array( $errors ) && ! empty( $errors ) ) {
		echo '<div class="notice notice-error is-dismissible"><p><strong>' . esc_html__( 'Import thất bại:', 'flatsome-child' ) . '</strong></p><ul style="margin:0 0 0 1.2em;">';
		foreach ( $errors as $error ) {
			echo '<li>' . esc_html( $error ) . '</li>';
		}
		echo '</ul></div>';
		delete_transient( 'dmc_exam_import_errors_' . $user_id );
	}

	if ( isset( $_GET['imported'] ) ) {
		$count = absint( $_GET['imported'] );

		if ( $count > 0 ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: %d: total questions after import */
						_n(
							'Import thành công. Bài thi hiện có %d câu hỏi.',
							'Import thành công. Bài thi hiện có %d câu hỏi.',
							$count,
							'flatsome-child'
						),
						$count
					)
				)
			);
		}
	}

	if ( empty( $_GET['import_error'] ) ) {
		return;
	}

	$messages = [
		'no_file' => __( 'Vui lòng chọn file CSV để import.', 'flatsome-child' ),
		'type'    => __( 'Chỉ hỗ trợ file .csv (mở/sửa bằng Excel).', 'flatsome-child' ),
		'upload'  => __( 'Upload file thất bại. Vui lòng thử lại.', 'flatsome-child' ),
		'parse'   => __( 'Không đọc được dữ liệu trong file. Kiểm tra lại file mẫu.', 'flatsome-child' ),
		'save'    => __( 'Không lưu được câu hỏi import.', 'flatsome-child' ),
		'invalid' => __( 'Bài thi không hợp lệ.', 'flatsome-child' ),
	];

	$code = sanitize_key( wp_unslash( $_GET['import_error'] ) );

	if ( isset( $messages[ $code ] ) && ! in_array( $code, [ 'parse', 'save', 'no_file', 'upload' ], true ) ) {
		printf(
			'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
			esc_html( $messages[ $code ] )
		);
	}
}

/**
 * Render import panel markup.
 *
 * @param int    $page_id Exam page ID.
 * @param string $context job_fair|page.
 */
function dmc_exam_render_import_panel( $page_id, $context = 'job_fair' ) {
	$page_id = (int) $page_id;

	if ( ! $page_id || ! dmc_exam_is_exam_page( $page_id ) ) {
		return;
	}

	$question_count = count( dmc_exam_get_questions( $page_id ) );
	$download_url   = 'page' === $context
		? dmc_exam_get_page_template_download_url( $page_id )
		: dmc_exam_get_template_download_url( $page_id );
	$export_url     = 'page' === $context
		? dmc_exam_get_page_questions_export_url( $page_id )
		: dmc_exam_get_questions_export_url( $page_id );
	$form_id        = dmc_exam_import_form_id( $page_id );
	$file_input_id  = 'dmc-exam-import-file-' . $page_id;
	$mode_select_id = 'dmc-exam-import-mode-' . $page_id;
	$use_form_attr  = 'page' === $context;
	$form_attr      = $use_form_attr ? ' form="' . esc_attr( $form_id ) . '"' : '';
	?>
	<section class="dmc-jf__import">
		<div class="dmc-jf__import-head">
			<div>
				<h2><?php esc_html_e( 'Import / Export câu hỏi Excel', 'flatsome-child' ); ?></h2>
				<p>
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: event name, 2: current question count */
							__( 'Bài thi: %1$s — hiện có %2$d câu trong ngân hàng.', 'flatsome-child' ),
							dmc_exam_get_event_name( $page_id ),
							$question_count
						)
					);
					?>
				</p>
			</div>
			<div class="dmc-jf__import-buttons">
				<a class="button" href="<?php echo esc_url( $download_url ); ?>">
					<?php esc_html_e( 'Tải file mẫu', 'flatsome-child' ); ?>
				</a>
				<?php if ( $question_count > 0 ) : ?>
					<a class="button button-primary" href="<?php echo esc_url( $export_url ); ?>">
						<?php esc_html_e( 'Xuất Excel câu hỏi', 'flatsome-child' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<?php if ( $use_form_attr ) : ?>
			<div class="dmc-jf__import-form">
		<?php else : ?>
			<form class="dmc-jf__import-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( dmc_exam_import_form_action_url() ); ?>">
				<input type="hidden" name="action" value="dmc_exam_import_questions">
				<?php wp_nonce_field( 'dmc_exam_import_' . $page_id, 'dmc_exam_import_nonce' ); ?>
				<input type="hidden" name="exam_id" value="<?php echo esc_attr( (string) $page_id ); ?>">
				<input type="hidden" name="import_context" value="<?php echo esc_attr( $context ); ?>">
				<input type="hidden" name="page" value="dmc-job-fair">
		<?php endif; ?>

			<div class="dmc-jf__import-grid">
				<div class="dmc-jf__import-field">
					<label for="<?php echo esc_attr( $file_input_id ); ?>"><?php esc_html_e( 'Chọn file CSV', 'flatsome-child' ); ?></label>
					<input
						type="file"
						id="<?php echo esc_attr( $file_input_id ); ?>"
						name="exam_questions_file"
						accept=".csv,.txt,text/csv,text/plain"
						required<?php echo $form_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					>
					<p class="description">
						<?php esc_html_e( 'Dùng file mẫu, điền câu hỏi trong Excel rồi lưu dạng CSV UTF-8.', 'flatsome-child' ); ?>
					</p>
				</div>

				<div class="dmc-jf__import-field">
					<label for="<?php echo esc_attr( $mode_select_id ); ?>"><?php esc_html_e( 'Cách import', 'flatsome-child' ); ?></label>
					<select id="<?php echo esc_attr( $mode_select_id ); ?>" name="import_mode"<?php echo $form_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
						<option value="replace"><?php esc_html_e( 'Thay thế toàn bộ câu hỏi hiện có', 'flatsome-child' ); ?></option>
						<option value="append" selected><?php esc_html_e( 'Thêm vào cuối danh sách hiện có', 'flatsome-child' ); ?></option>
					</select>
				</div>
			</div>

			<p class="dmc-jf__import-actions">
				<button type="submit" class="button button-primary"<?php echo $form_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php esc_html_e( 'Import câu hỏi', 'flatsome-child' ); ?></button>
			</p>

		<?php if ( $use_form_attr ) : ?>
			</div>
		<?php else : ?>
			</form>
		<?php endif; ?>
	</section>
	<?php
}

/**
 * Meta box on exam page edit screen.
 */
function dmc_exam_register_import_meta_box() {
	add_meta_box(
		'dmc_exam_import_questions',
		__( 'Import / Export câu hỏi Excel', 'flatsome-child' ),
		'dmc_exam_render_import_meta_box',
		'page',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'dmc_exam_register_import_meta_box' );

/**
 * Render import meta box.
 *
 * @param WP_Post $post Current post.
 */
function dmc_exam_render_import_meta_box( $post ) {
	if ( ! dmc_exam_is_exam_page( $post->ID ) ) {
		echo '<p>' . esc_html__( 'Chọn template "Bài thi trắc nghiệm" để import câu hỏi.', 'flatsome-child' ) . '</p>';
		return;
	}

	dmc_exam_render_import_notices( 'page' );
	dmc_exam_render_import_panel( (int) $post->ID, 'page' );
}
