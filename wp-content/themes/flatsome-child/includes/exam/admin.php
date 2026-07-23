<?php
/**
 * Exam — admin list, Theme Settings submenu, detail UI.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Theme Settings submenu: Ngày hội việc làm.
 */
function dmc_exam_register_admin_menu() {
	add_submenu_page(
		'theme-general-settings',
		__( 'Ngày hội việc làm', 'flatsome-child' ),
		__( 'Ngày hội việc làm', 'flatsome-child' ),
		'edit_posts',
		'dmc-job-fair',
		'dmc_exam_render_job_fair_page',
		25
	);
}
add_action( 'admin_menu', 'dmc_exam_register_admin_menu', 99 );

/**
 * Enqueue admin assets for job fair page.
 *
 * @param string $hook Current admin hook.
 */
function dmc_exam_admin_assets( $hook ) {
	if ( false === strpos( (string) $hook, 'dmc-job-fair' ) ) {
		return;
	}

	$css = get_stylesheet_directory() . '/assets/css/exam-admin.css';
	$js  = get_stylesheet_directory() . '/assets/js/exam-admin.js';

	if ( file_exists( $css ) ) {
		wp_enqueue_style(
			'dmc-exam-admin',
			get_stylesheet_directory_uri() . '/assets/css/exam-admin.css',
			[],
			filemtime( $css )
		);
	}

	if ( file_exists( $js ) ) {
		wp_enqueue_script(
			'dmc-exam-admin',
			get_stylesheet_directory_uri() . '/assets/js/exam-admin.js',
			[],
			filemtime( $js ),
			true
		);

		wp_localize_script(
			'dmc-exam-admin',
			'dmcExamAdmin',
			[
				'messages' => [
					'selectItems'  => __( 'Vui lòng chọn ít nhất một kết quả để xóa.', 'flatsome-child' ),
					'confirmDelete'=> __( 'Bạn có chắc muốn xóa các kết quả đã chọn? Hành động này không thể hoàn tác.', 'flatsome-child' ),
				],
			]
		);
	}
}
add_action( 'admin_enqueue_scripts', 'dmc_exam_admin_assets' );

/**
 * Handle Excel/CSV export before admin HTML is sent.
 */
function dmc_exam_maybe_export_submissions() {
	if ( ! is_admin() || ! isset( $_GET['page'] ) || 'dmc-job-fair' !== $_GET['page'] ) {
		return;
	}

	if ( ! isset( $_GET['export'] ) || 'excel' !== $_GET['export'] ) {
		return;
	}

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền xuất dữ liệu.', 'flatsome-child' ) );
	}

	check_admin_referer( 'dmc_exam_export' );

	$page_id = isset( $_GET['exam_id'] ) ? absint( $_GET['exam_id'] ) : 0;
	$search  = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

	dmc_exam_export_submissions_csv( $page_id, $search );
	exit;
}
add_action( 'admin_init', 'dmc_exam_maybe_export_submissions' );

/**
 * Stream submissions as CSV (opens in Excel) with sortable time columns.
 *
 * @param int    $page_id Exam page filter.
 * @param string $search  Search filter.
 */
function dmc_exam_export_submissions_csv( $page_id = 0, $search = '' ) {
	$items       = dmc_exam_query_submissions_for_export( $page_id, $search );
	$event_name  = $page_id ? dmc_exam_get_event_name( $page_id ) : 'tat-ca';
	$slug        = sanitize_title( $event_name );
	$filename    = 'ket-qua-thi-' . ( $slug ?: 'tat-ca' ) . '-' . gmdate( 'Y-m-d-His' ) . '.csv';

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

	$output = fopen( 'php://output', 'w' );

	if ( false === $output ) {
		wp_die( esc_html__( 'Không tạo được file xuất.', 'flatsome-child' ) );
	}

	// UTF-8 BOM helps Excel display Vietnamese correctly.
	fprintf( $output, "\xEF\xBB\xBF" );

	$headers = [
		__( 'STT', 'flatsome-child' ),
		__( 'Họ và tên', 'flatsome-child' ),
		__( 'Số điện thoại', 'flatsome-child' ),
		__( 'Khoa', 'flatsome-child' ),
		__( 'Sự kiện', 'flatsome-child' ),
		__( 'Đúng hết', 'flatsome-child' ),
		__( 'Số câu đúng', 'flatsome-child' ),
		__( 'Tổng câu', 'flatsome-child' ),
		__( 'Điểm (%)', 'flatsome-child' ),
		__( 'Hết giờ', 'flatsome-child' ),
		__( 'Ngày nộp', 'flatsome-child' ),
		__( 'Giờ nộp', 'flatsome-child' ),
		__( 'Phút nộp', 'flatsome-child' ),
		__( 'Giây nộp', 'flatsome-child' ),
		__( 'Mili giây nộp', 'flatsome-child' ),
		__( 'Unix ms (nộp)', 'flatsome-child' ),
		__( 'Thời gian làm - Giờ', 'flatsome-child' ),
		__( 'Thời gian làm - Phút', 'flatsome-child' ),
		__( 'Thời gian làm - Giây', 'flatsome-child' ),
		__( 'Thời gian làm - Mili giây', 'flatsome-child' ),
		__( 'Thời gian làm (tổng ms)', 'flatsome-child' ),
		__( 'Thời gian nộp (đầy đủ)', 'flatsome-child' ),
	];

	fputcsv( $output, $headers );

	$index = 0;

	foreach ( $items as $row ) {
		++$index;

		fputcsv(
			$output,
			[
				$index,
				$row['name'] ?? '',
				$row['phone'] ?? '',
				$row['department'] ?? '',
				$row['event_name'] ?? '',
				! empty( $row['all_correct'] ) ? __( 'Có', 'flatsome-child' ) : __( 'Không', 'flatsome-child' ),
				(int) ( $row['correct'] ?? 0 ),
				(int) ( $row['gradable'] ?? 0 ),
				null !== ( $row['score'] ?? null ) ? (float) $row['score'] : '',
				! empty( $row['is_timeout'] ) ? __( 'Có', 'flatsome-child' ) : __( 'Không', 'flatsome-child' ),
				$row['submitted_date'] ?? '',
				'' === (string) ( $row['submitted_hour'] ?? '' ) ? '' : (int) $row['submitted_hour'],
				'' === (string) ( $row['submitted_minute'] ?? '' ) ? '' : (int) $row['submitted_minute'],
				'' === (string) ( $row['submitted_second'] ?? '' ) ? '' : (int) $row['submitted_second'],
				'' === (string) ( $row['submitted_ms'] ?? '' ) ? '' : (int) $row['submitted_ms'],
				(int) ( $row['submitted_unix_ms'] ?? 0 ),
				(int) ( $row['duration_hour'] ?? 0 ),
				(int) ( $row['duration_minute'] ?? 0 ),
				(int) ( $row['duration_second'] ?? 0 ),
				(int) ( $row['duration_ms'] ?? 0 ),
				(int) ( $row['duration_total_ms'] ?? 0 ),
				$row['submitted_at'] ?? '',
			]
		);
	}

	fclose( $output );
}

/**
 * Permanently delete exam submission posts.
 *
 * @param int[] $ids Submission post IDs.
 * @return int Number of deleted rows.
 */
function dmc_exam_delete_submissions( array $ids ) {
	$deleted = 0;

	foreach ( $ids as $id ) {
		$id = (int) $id;

		if ( $id <= 0 ) {
			continue;
		}

		$post = get_post( $id );

		if ( ! $post || 'dmc_exam_submission' !== $post->post_type ) {
			continue;
		}

		if ( wp_delete_post( $id, true ) ) {
			++$deleted;
		}
	}

	return $deleted;
}

/**
 * Handle bulk delete on job fair list.
 */
function dmc_exam_handle_bulk_actions() {
	if ( ! is_admin() || 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		return;
	}

	if ( ! isset( $_POST['dmc_exam_bulk_nonce'] ) ) {
		return;
	}

	$page = isset( $_POST['page'] ) ? sanitize_key( wp_unslash( $_POST['page'] ) ) : '';

	if ( 'dmc-job-fair' !== $page ) {
		return;
	}

	if ( ! current_user_can( 'delete_posts' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền xóa kết quả thi.', 'flatsome-child' ) );
	}

	check_admin_referer( 'dmc_exam_bulk_action', 'dmc_exam_bulk_nonce' );

	$action = isset( $_POST['bulk_action'] ) ? sanitize_key( wp_unslash( $_POST['bulk_action'] ) ) : '';

	if ( 'delete' !== $action ) {
		return;
	}

	$ids = isset( $_POST['submission_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['submission_ids'] ) ) : [];
	$ids = array_values( array_filter( $ids ) );

	$redirect_args = [
		'page' => 'dmc-job-fair',
	];

	if ( ! empty( $_POST['exam_id'] ) ) {
		$redirect_args['exam_id'] = absint( $_POST['exam_id'] );
	}

	if ( ! empty( $_POST['s'] ) ) {
		$redirect_args['s'] = sanitize_text_field( wp_unslash( $_POST['s'] ) );
	}

	if ( ! empty( $_POST['paged'] ) ) {
		$redirect_args['paged'] = max( 1, absint( $_POST['paged'] ) );
	}

	if ( empty( $ids ) ) {
		$redirect_args['bulk_error'] = 'no_selection';
		wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
		exit;
	}

	$deleted = dmc_exam_delete_submissions( $ids );

	$redirect_args['deleted'] = $deleted;
	wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_init', 'dmc_exam_handle_bulk_actions' );

/**
 * Render job fair admin page.
 */
function dmc_exam_render_job_fair_page() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền xem trang này.', 'flatsome-child' ) );
	}

	$submission_id = isset( $_GET['submission'] ) ? absint( $_GET['submission'] ) : 0;

	if ( $submission_id ) {
		dmc_exam_render_job_fair_detail( $submission_id );
		return;
	}

	dmc_exam_render_job_fair_list();
}

/**
 * List registrants.
 */
function dmc_exam_render_job_fair_list() {
	$exam_pages = dmc_exam_get_exam_page_ids();
	$page_id    = isset( $_GET['exam_id'] ) ? absint( $_GET['exam_id'] ) : 0;
	$search     = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	$paged      = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
	$per_page   = 20;

	if ( ! $page_id && count( $exam_pages ) === 1 ) {
		$page_id = (int) $exam_pages[0];
	}

	$result = dmc_exam_query_submissions( $page_id, $search, $paged, $per_page );
	$items  = $result['items'];
	$total  = $result['total'];
	$pages  = max( 1, (int) ceil( $total / $per_page ) );
	$base_url = admin_url( 'admin.php?page=dmc-job-fair' );

	$event_name = $page_id ? dmc_exam_get_event_name( $page_id ) : __( 'Tất cả sự kiện', 'flatsome-child' );
	$export_url = wp_nonce_url(
		add_query_arg(
			array_filter(
				[
					'page'    => 'dmc-job-fair',
					'export'  => 'excel',
					'exam_id' => $page_id ?: null,
					's'       => $search ?: null,
				]
			),
			admin_url( 'admin.php' )
		),
		'dmc_exam_export'
	);
	?>
	<div class="wrap dmc-jf">
		<header class="dmc-jf__header">
			<div>
				<p class="dmc-jf__eyebrow"><?php esc_html_e( 'Theme Settings', 'flatsome-child' ); ?></p>
				<h1 class="dmc-jf__title"><?php esc_html_e( 'Ngày hội việc làm', 'flatsome-child' ); ?></h1>
				<p class="dmc-jf__subtitle">
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: event name, 2: total count */
							__( 'Đang xem: %1$s — %2$d người đăng ký', 'flatsome-child' ),
							$event_name,
							$total
						)
					);
					?>
				</p>
			</div>
		</header>

		<?php if ( ! empty( $exam_pages ) ) : ?>
			<nav class="dmc-jf__tabs" aria-label="<?php esc_attr_e( 'Chọn sự kiện', 'flatsome-child' ); ?>">
				<a
					class="dmc-jf__tab<?php echo 0 === $page_id ? ' is-active' : ''; ?>"
					href="<?php echo esc_url( $base_url ); ?>"
				>
					<?php esc_html_e( 'Tất cả', 'flatsome-child' ); ?>
				</a>
				<?php foreach ( $exam_pages as $eid ) : ?>
					<a
						class="dmc-jf__tab<?php echo (int) $eid === $page_id ? ' is-active' : ''; ?>"
						href="<?php echo esc_url( add_query_arg( 'exam_id', $eid, $base_url ) ); ?>"
					>
						<?php echo esc_html( dmc_exam_get_event_name( $eid ) ); ?>
					</a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>

		<?php
		if ( isset( $_GET['deleted'] ) ) {
			$deleted_count = absint( $_GET['deleted'] );

			if ( $deleted_count > 0 ) {
				printf(
					'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
					esc_html(
						sprintf(
							/* translators: %d: number of deleted submissions */
							_n( 'Đã xóa %d kết quả.', 'Đã xóa %d kết quả.', $deleted_count, 'flatsome-child' ),
							$deleted_count
						)
					)
				);
			}
		}

		if ( isset( $_GET['bulk_error'] ) && 'no_selection' === $_GET['bulk_error'] ) {
			printf(
				'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
				esc_html__( 'Vui lòng chọn ít nhất một kết quả trước khi xóa.', 'flatsome-child' )
			);
		}
		?>

		<form class="dmc-jf__toolbar" method="get" action="">
			<input type="hidden" name="page" value="dmc-job-fair">
			<?php if ( $page_id ) : ?>
				<input type="hidden" name="exam_id" value="<?php echo esc_attr( (string) $page_id ); ?>">
			<?php endif; ?>
			<label class="screen-reader-text" for="dmc-jf-search"><?php esc_html_e( 'Tìm kiếm', 'flatsome-child' ); ?></label>
			<input
				type="search"
				id="dmc-jf-search"
				name="s"
				value="<?php echo esc_attr( $search ); ?>"
				placeholder="<?php esc_attr_e( 'Tìm theo họ tên hoặc số điện thoại…', 'flatsome-child' ); ?>"
				class="dmc-jf__search"
			>
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Tìm kiếm', 'flatsome-child' ); ?></button>
			<?php if ( $search ) : ?>
				<a class="button" href="<?php echo esc_url( $page_id ? add_query_arg( 'exam_id', $page_id, $base_url ) : $base_url ); ?>">
					<?php esc_html_e( 'Xóa bộ lọc', 'flatsome-child' ); ?>
				</a>
			<?php endif; ?>
			<a class="button dmc-jf__export" href="<?php echo esc_url( $export_url ); ?>">
				<?php esc_html_e( 'Xuất Excel', 'flatsome-child' ); ?>
			</a>
		</form>

		<?php if ( empty( $items ) ) : ?>
			<div class="dmc-jf__empty">
				<strong><?php esc_html_e( 'Chưa có người đăng ký', 'flatsome-child' ); ?></strong>
				<p><?php esc_html_e( 'Khi thí sinh hoàn thành bài trắc nghiệm, thông tin sẽ hiện tại đây.', 'flatsome-child' ); ?></p>
			</div>
		<?php else : ?>
			<form id="dmc-jf-bulk-form" class="dmc-jf__bulk-form" method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
				<?php wp_nonce_field( 'dmc_exam_bulk_action', 'dmc_exam_bulk_nonce' ); ?>
				<input type="hidden" name="page" value="dmc-job-fair">
				<?php if ( $page_id ) : ?>
					<input type="hidden" name="exam_id" value="<?php echo esc_attr( (string) $page_id ); ?>">
				<?php endif; ?>
				<?php if ( $search ) : ?>
					<input type="hidden" name="s" value="<?php echo esc_attr( $search ); ?>">
				<?php endif; ?>
				<?php if ( $paged > 1 ) : ?>
					<input type="hidden" name="paged" value="<?php echo esc_attr( (string) $paged ); ?>">
				<?php endif; ?>

				<div class="dmc-jf__bulk">
					<label class="screen-reader-text" for="dmc-jf-bulk-action"><?php esc_html_e( 'Hành động hàng loạt', 'flatsome-child' ); ?></label>
					<select id="dmc-jf-bulk-action" name="bulk_action">
						<option value=""><?php esc_html_e( 'Hành động hàng loạt', 'flatsome-child' ); ?></option>
						<option value="delete"><?php esc_html_e( 'Xóa', 'flatsome-child' ); ?></option>
					</select>
					<button type="submit" class="button"><?php esc_html_e( 'Áp dụng', 'flatsome-child' ); ?></button>
					<span class="dmc-jf__bulk-count" id="dmc-jf-bulk-count" hidden></span>
				</div>

			<div class="dmc-jf__table-wrap">
				<table class="dmc-jf__table">
					<thead>
						<tr>
							<th class="dmc-jf__check-col">
								<input type="checkbox" id="dmc-jf-select-all" aria-label="<?php esc_attr_e( 'Chọn tất cả', 'flatsome-child' ); ?>">
							</th>
							<th><?php esc_html_e( 'Họ và tên', 'flatsome-child' ); ?></th>
							<th><?php esc_html_e( 'Số điện thoại', 'flatsome-child' ); ?></th>
							<th><?php esc_html_e( 'Khoa', 'flatsome-child' ); ?></th>
							<?php if ( ! $page_id ) : ?>
								<th><?php esc_html_e( 'Sự kiện', 'flatsome-child' ); ?></th>
							<?php endif; ?>
							<th><?php esc_html_e( 'Số câu đúng', 'flatsome-child' ); ?></th>
							<th><?php esc_html_e( 'Đúng hết', 'flatsome-child' ); ?></th>
							<th><?php esc_html_e( 'Thời gian nộp', 'flatsome-child' ); ?></th>
							<th><?php esc_html_e( 'Chi tiết', 'flatsome-child' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $items as $row ) : ?>
							<?php
							$detail_url = add_query_arg(
								array_filter(
									[
										'page'       => 'dmc-job-fair',
										'submission' => $row['id'],
										'exam_id'    => $page_id ?: null,
										's'          => $search ?: null,
									]
								),
								admin_url( 'admin.php' )
							);
							?>
							<tr>
								<td class="dmc-jf__check-col">
									<input
										type="checkbox"
										class="dmc-jf__row-check"
										name="submission_ids[]"
										value="<?php echo esc_attr( (string) $row['id'] ); ?>"
										aria-label="<?php echo esc_attr( sprintf( __( 'Chọn %s', 'flatsome-child' ), $row['name'] ?: __( 'thí sinh', 'flatsome-child' ) ) ); ?>"
									>
								</td>
								<td>
									<strong class="dmc-jf__name"><?php echo esc_html( $row['name'] ?: '—' ); ?></strong>
									<?php if ( ! empty( $row['is_timeout'] ) ) : ?>
										<span class="dmc-jf__badge dmc-jf__badge--warn"><?php esc_html_e( 'Hết giờ', 'flatsome-child' ); ?></span>
									<?php endif; ?>
								</td>
								<td>
									<a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $row['phone'] ) ); ?>">
										<?php echo esc_html( $row['phone'] ?: '—' ); ?>
									</a>
								</td>
								<td><?php echo esc_html( $row['department'] ?: '—' ); ?></td>
								<?php if ( ! $page_id ) : ?>
									<td><?php echo esc_html( $row['event_name'] ); ?></td>
								<?php endif; ?>
								<td>
									<span class="dmc-jf__score">
										<?php
										if ( $row['gradable'] > 0 ) {
											echo esc_html( $row['correct'] . '/' . $row['gradable'] );
											if ( null !== $row['score'] ) {
												echo ' <small>(' . esc_html( (string) $row['score'] ) . '%)</small>';
											}
										} else {
											echo '—';
										}
										?>
									</span>
								</td>
								<td>
									<?php if ( ! empty( $row['all_correct'] ) ) : ?>
										<span class="dmc-jf__badge dmc-jf__badge--ok"><?php esc_html_e( 'Có', 'flatsome-child' ); ?></span>
									<?php else : ?>
										<span class="dmc-jf__badge"><?php esc_html_e( 'Không', 'flatsome-child' ); ?></span>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( $row['submitted_at'] ); ?></td>
								<td>
									<a class="button button-small" href="<?php echo esc_url( $detail_url ); ?>">
										<?php esc_html_e( 'Xem bài làm', 'flatsome-child' ); ?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			</form>

			<?php if ( $pages > 1 ) : ?>
				<div class="dmc-jf__pagination">
					<?php
					echo wp_kses_post(
						paginate_links(
							[
								'base'      => add_query_arg(
									[
										'page'    => 'dmc-job-fair',
										'exam_id' => $page_id ?: false,
										's'       => $search ?: false,
										'paged'   => '%#%',
									],
									admin_url( 'admin.php' )
								),
								'format'    => '',
								'current'   => $paged,
								'total'     => $pages,
								'prev_text' => '&laquo;',
								'next_text' => '&raquo;',
							]
						)
					);
					?>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Detail view for one submission.
 *
 * @param int $submission_id Submission ID.
 */
function dmc_exam_render_job_fair_detail( $submission_id ) {
	$post = get_post( $submission_id );

	if ( ! $post || 'dmc_exam_submission' !== $post->post_type ) {
		echo '<div class="wrap"><div class="notice notice-error"><p>' . esc_html__( 'Không tìm thấy bài nộp.', 'flatsome-child' ) . '</p></div></div>';
		return;
	}

	$row       = dmc_exam_get_submission_row( $submission_id );
	$questions = $row['page_id'] ? dmc_exam_get_questions( $row['page_id'] ) : [];
	$answers   = is_array( $row['answers'] ) ? $row['answers'] : [];
	$back_args = [ 'page' => 'dmc-job-fair' ];

	if ( ! empty( $_GET['exam_id'] ) ) {
		$back_args['exam_id'] = absint( $_GET['exam_id'] );
	}
	if ( ! empty( $_GET['s'] ) ) {
		$back_args['s'] = sanitize_text_field( wp_unslash( $_GET['s'] ) );
	}

	$back_url = add_query_arg( $back_args, admin_url( 'admin.php' ) );
	$labels   = [ 'a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D' ];
	?>
	<div class="wrap dmc-jf">
		<p class="dmc-jf__back">
			<a href="<?php echo esc_url( $back_url ); ?>">&larr; <?php esc_html_e( 'Quay lại danh sách', 'flatsome-child' ); ?></a>
		</p>

		<header class="dmc-jf__header dmc-jf__header--detail">
			<div>
				<p class="dmc-jf__eyebrow"><?php echo esc_html( $row['event_name'] ); ?></p>
				<h1 class="dmc-jf__title"><?php echo esc_html( $row['name'] ?: __( 'Thí sinh', 'flatsome-child' ) ); ?></h1>
				<p class="dmc-jf__subtitle">
					<?php
					$bits = array_filter(
						[
							$row['phone'],
							$row['department'],
							$row['submitted_at'],
						]
					);
					echo esc_html( implode( ' · ', $bits ) );
					?>
				</p>
			</div>
			<div class="dmc-jf__summary">
				<div class="dmc-jf__stat">
					<span class="dmc-jf__stat-label"><?php esc_html_e( 'Số câu đúng', 'flatsome-child' ); ?></span>
					<strong class="dmc-jf__stat-value">
						<?php
						echo $row['gradable'] > 0
							? esc_html( $row['correct'] . '/' . $row['gradable'] )
							: '—';
						?>
					</strong>
				</div>
				<div class="dmc-jf__stat">
					<span class="dmc-jf__stat-label"><?php esc_html_e( 'Điểm', 'flatsome-child' ); ?></span>
					<strong class="dmc-jf__stat-value">
						<?php echo null !== $row['score'] ? esc_html( (string) $row['score'] ) . '%' : '—'; ?>
					</strong>
				</div>
				<div class="dmc-jf__stat">
					<span class="dmc-jf__stat-label"><?php esc_html_e( 'Trạng thái', 'flatsome-child' ); ?></span>
					<strong class="dmc-jf__stat-value">
						<?php
						echo ! empty( $row['is_timeout'] )
							? esc_html__( 'Hết giờ', 'flatsome-child' )
							: esc_html__( 'Nộp bài', 'flatsome-child' );
						?>
					</strong>
				</div>
			</div>
		</header>

		<section class="dmc-jf__profile">
			<div><span><?php esc_html_e( 'Họ và tên', 'flatsome-child' ); ?></span><strong><?php echo esc_html( $row['name'] ?: '—' ); ?></strong></div>
			<div><span><?php esc_html_e( 'Số điện thoại', 'flatsome-child' ); ?></span><strong><?php echo esc_html( $row['phone'] ?: '—' ); ?></strong></div>
			<div><span><?php esc_html_e( 'Khoa', 'flatsome-child' ); ?></span><strong><?php echo esc_html( $row['department'] ?: '—' ); ?></strong></div>
			<div><span><?php esc_html_e( 'Sự kiện', 'flatsome-child' ); ?></span><strong><?php echo esc_html( $row['event_name'] ); ?></strong></div>
		</section>

		<section class="dmc-jf__answers">
			<h2><?php esc_html_e( 'Chi tiết câu trả lời', 'flatsome-child' ); ?></h2>

			<?php if ( empty( $questions ) ) : ?>
				<p class="dmc-jf__empty-inline"><?php esc_html_e( 'Không có dữ liệu câu hỏi.', 'flatsome-child' ); ?></p>
			<?php else : ?>
				<ol class="dmc-jf__questions">
					<?php foreach ( $questions as $question ) : ?>
						<?php
						$qid     = (string) $question['id'];
						$choice  = isset( $answers[ $qid ] ) ? strtolower( (string) $answers[ $qid ] ) : '';
						$correct = in_array( $question['correct'], [ 'a', 'b', 'c', 'd' ], true ) ? $question['correct'] : '';
						$is_right = $correct && $choice === $correct;
						$is_wrong = $correct && $choice && $choice !== $correct;
						$status_class = 'is-ungraded';

						if ( $is_right ) {
							$status_class = 'is-correct';
						} elseif ( $is_wrong ) {
							$status_class = 'is-wrong';
						} elseif ( '' === $choice ) {
							$status_class = 'is-empty';
						}
						?>
						<li class="dmc-jf-q <?php echo esc_attr( $status_class ); ?>">
							<div class="dmc-jf-q__head">
								<span class="dmc-jf-q__num"><?php echo esc_html( (string) $question['id'] ); ?></span>
								<div class="dmc-jf-q__meta">
									<strong class="dmc-jf-q__title"><?php echo esc_html( $question['text'] ); ?></strong>
									<span class="dmc-jf-q__status">
										<?php
										if ( $is_right ) {
											esc_html_e( 'Trả lời đúng', 'flatsome-child' );
										} elseif ( $is_wrong ) {
											esc_html_e( 'Trả lời sai', 'flatsome-child' );
										} elseif ( '' === $choice ) {
											esc_html_e( 'Chưa chọn', 'flatsome-child' );
										} else {
											esc_html_e( 'Không chấm điểm', 'flatsome-child' );
										}
										?>
									</span>
								</div>
							</div>

							<ul class="dmc-jf-q__options">
								<?php foreach ( $labels as $key => $letter ) : ?>
									<?php
									$text = (string) ( $question['answers'][ $key ] ?? '' );
									$classes = [ 'dmc-jf-q__option' ];

									if ( $choice === $key ) {
										$classes[] = 'is-selected';
									}
									if ( $correct === $key ) {
										$classes[] = 'is-answer';
									}
									?>
									<li class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
										<span class="dmc-jf-q__letter"><?php echo esc_html( $letter ); ?></span>
										<span class="dmc-jf-q__text"><?php echo esc_html( $text ); ?></span>
										<span class="dmc-jf-q__flags">
											<?php if ( $choice === $key ) : ?>
												<em><?php esc_html_e( 'Đã chọn', 'flatsome-child' ); ?></em>
											<?php endif; ?>
											<?php if ( $correct === $key ) : ?>
												<em class="is-answer-flag"><?php esc_html_e( 'Đáp án đúng', 'flatsome-child' ); ?></em>
											<?php endif; ?>
										</span>
									</li>
								<?php endforeach; ?>
							</ul>
						</li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>
		</section>
	</div>
	<?php
}

/**
 * Keep classic CPT columns/meta for power users (hidden from main menu).
 *
 * @param string[] $columns Columns.
 * @return string[]
 */
function dmc_exam_submission_columns( $columns ) {
	$new = [];

	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;

		if ( 'title' === $key ) {
			$new['exam_page']    = __( 'Bài thi', 'flatsome-child' );
			$new['candidate']    = __( 'Thí sinh', 'flatsome-child' );
			$new['phone']        = __( 'SĐT', 'flatsome-child' );
			$new['department']   = __( 'Khoa', 'flatsome-child' );
			$new['submitted_at'] = __( 'Thời gian nộp', 'flatsome-child' );
			$new['time_spent']   = __( 'Thời gian làm', 'flatsome-child' );
			$new['score']        = __( 'Điểm', 'flatsome-child' );
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
	$row = dmc_exam_get_submission_row( $post_id );

	switch ( $column ) {
		case 'exam_page':
			echo esc_html( $row['event_name'] );
			break;
		case 'candidate':
			echo esc_html( $row['name'] ?: '—' );
			break;
		case 'phone':
			echo esc_html( $row['phone'] ?: '—' );
			break;
		case 'department':
			echo esc_html( $row['department'] ?: '—' );
			if ( ! empty( $row['is_timeout'] ) ) {
				echo '<br><small style="color:#b32d2e;">' . esc_html__( 'Hết giờ', 'flatsome-child' ) . '</small>';
			}
			break;
		case 'submitted_at':
			echo esc_html( $row['submitted_at'] );
			break;
		case 'time_spent':
			$seconds = (int) $row['time_spent'];
			echo $seconds > 0
				? esc_html( sprintf( '%02d:%02d', floor( $seconds / 60 ), $seconds % 60 ) )
				: '—';
			break;
		case 'score':
			if ( $row['gradable'] > 0 ) {
				echo esc_html( $row['correct'] . '/' . $row['gradable'] );
				if ( null !== $row['score'] ) {
					echo esc_html( ' (' . $row['score'] . '%)' );
				}
			} else {
				echo '—';
			}
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
 * Render submission meta box (legacy CPT screen).
 *
 * @param WP_Post $post Post object.
 */
function dmc_exam_render_submission_meta_box( $post ) {
	$row = dmc_exam_get_submission_row( $post->ID );
	$detail_url = add_query_arg(
		[
			'page'       => 'dmc-job-fair',
			'submission' => $post->ID,
		],
		admin_url( 'admin.php' )
	);
	?>
	<p>
		<a class="button button-primary" href="<?php echo esc_url( $detail_url ); ?>">
			<?php esc_html_e( 'Mở giao diện Ngày hội việc làm', 'flatsome-child' ); ?>
		</a>
	</p>
	<table class="widefat striped">
		<tbody>
			<tr><th style="width:180px;"><?php esc_html_e( 'Họ tên', 'flatsome-child' ); ?></th><td><?php echo esc_html( $row['name'] ?: '—' ); ?></td></tr>
			<tr><th><?php esc_html_e( 'SĐT', 'flatsome-child' ); ?></th><td><?php echo esc_html( $row['phone'] ?: '—' ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Khoa', 'flatsome-child' ); ?></th><td><?php echo esc_html( $row['department'] ?: '—' ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Số câu đúng', 'flatsome-child' ); ?></th><td><?php echo $row['gradable'] > 0 ? esc_html( $row['correct'] . '/' . $row['gradable'] ) : '—'; ?></td></tr>
			<tr><th><?php esc_html_e( 'Thời gian nộp', 'flatsome-child' ); ?></th><td><?php echo esc_html( $row['submitted_at'] ); ?></td></tr>
		</tbody>
	</table>
	<?php
}
