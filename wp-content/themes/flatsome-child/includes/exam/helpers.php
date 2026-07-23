<?php
/**
 * Exam — helpers.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Cookie name for active exam attempt. */
const DMC_EXAM_SESSION_COOKIE = 'dmc_exam_sid';

/** Default number of random questions per attempt. */
const DMC_EXAM_DEFAULT_QUESTIONS_PER_ATTEMPT = 5;

/**
 * Whether current request uses exam page template.
 */
function dmc_is_exam_layout() {
	return is_page_template( 'page-templates/exam.php' );
}

/**
 * Get ACF field for current exam page.
 *
 * @param string $key     Field name.
 * @param mixed  $default Default value.
 * @return mixed
 */
function dmc_exam_field( $key, $default = null ) {
	if ( ! function_exists( 'get_field' ) ) {
		return $default;
	}

	$value = get_field( $key );

	return ( null === $value || false === $value || '' === $value ) ? $default : $value;
}

/**
 * Normalized question list for an exam page.
 *
 * @param int $page_id Page ID.
 * @return array<int, array<string, mixed>>
 */
function dmc_exam_get_questions( $page_id = 0 ) {
	$page_id = $page_id ? (int) $page_id : get_the_ID();

	if ( ! $page_id || ! function_exists( 'get_field' ) ) {
		return [];
	}

	$rows = get_field( 'exam_questions', $page_id );

	if ( ! is_array( $rows ) || empty( $rows ) ) {
		return [];
	}

	$questions = [];

	foreach ( $rows as $index => $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$text = trim( (string) ( $row['question_text'] ?? '' ) );

		if ( '' === $text ) {
			continue;
		}

		$questions[] = [
			'id'      => $index + 1,
			'text'    => $text,
			'answers' => [
				'a' => trim( (string) ( $row['answer_a'] ?? '' ) ),
				'b' => trim( (string) ( $row['answer_b'] ?? '' ) ),
				'c' => trim( (string) ( $row['answer_c'] ?? '' ) ),
				'd' => trim( (string) ( $row['answer_d'] ?? '' ) ),
			],
			'correct' => strtolower( trim( (string) ( $row['correct_answer'] ?? '' ) ) ),
		];
	}

	return $questions;
}

/**
 * How many questions each candidate receives per attempt.
 *
 * @param int $page_id Exam page ID.
 * @return int
 */
function dmc_exam_get_questions_per_attempt( $page_id = 0 ) {
	$page_id = $page_id ? (int) $page_id : get_the_ID();

	if ( ! $page_id || ! function_exists( 'get_field' ) ) {
		return DMC_EXAM_DEFAULT_QUESTIONS_PER_ATTEMPT;
	}

	$count = (int) get_field( 'exam_questions_per_attempt', $page_id );

	if ( $count <= 0 ) {
		return DMC_EXAM_DEFAULT_QUESTIONS_PER_ATTEMPT;
	}

	return $count;
}

/**
 * Pick random question IDs from a question pool.
 *
 * @param array<int, array<string, mixed>> $questions Question pool.
 * @param int                              $count     Questions to pick.
 * @return int[]
 */
function dmc_exam_pick_random_question_ids( array $questions, $count ) {
	$count = max( 1, (int) $count );
	$ids   = array_map(
		static function ( $question ) {
			return (int) ( $question['id'] ?? 0 );
		},
		$questions
	);
	$ids = array_values( array_filter( $ids ) );

	if ( empty( $ids ) ) {
		return [];
	}

	shuffle( $ids );

	return array_slice( $ids, 0, min( $count, count( $ids ) ) );
}

/**
 * Filter questions by ID list and optionally assign display numbers.
 *
 * @param array<int, array<string, mixed>> $questions       Full pool.
 * @param int[]                            $ids             Question IDs in display order.
 * @param bool                             $renumber_display Whether to set display_number 1..n.
 * @return array<int, array<string, mixed>>
 */
function dmc_exam_questions_by_ids( array $questions, array $ids, $renumber_display = false ) {
	$map = [];

	foreach ( $questions as $question ) {
		$map[ (int) $question['id'] ] = $question;
	}

	$picked  = [];
	$display = 1;

	foreach ( $ids as $id ) {
		$id = (int) $id;

		if ( ! isset( $map[ $id ] ) ) {
			continue;
		}

		$question = $map[ $id ];

		if ( $renumber_display ) {
			$question['display_number'] = $display++;
		}

		$picked[] = $question;
	}

	return $picked;
}

/**
 * Questions assigned to an active session (random subset per candidate).
 *
 * @param int                  $page_id Exam page ID.
 * @param array<string, mixed> $session Session payload.
 * @return array<int, array<string, mixed>>
 */
function dmc_exam_get_session_questions( $page_id, array $session ) {
	$page_id     = (int) $page_id;
	$all         = dmc_exam_get_questions( $page_id );
	$question_ids = [];

	if ( ! empty( $session['question_ids'] ) && is_array( $session['question_ids'] ) ) {
		$question_ids = array_map( 'intval', $session['question_ids'] );
	}

	if ( empty( $question_ids ) ) {
		return $all;
	}

	return dmc_exam_questions_by_ids( $all, $question_ids, true );
}

/**
 * Questions shown for a saved submission (attempt subset or full pool).
 *
 * @param int $submission_id Submission post ID.
 * @return array<int, array<string, mixed>>
 */
function dmc_exam_get_submission_questions( $submission_id ) {
	$submission_id = (int) $submission_id;
	$page_id         = (int) get_post_meta( $submission_id, 'exam_page_id', true );
	$all             = $page_id ? dmc_exam_get_questions( $page_id ) : [];
	$stored_ids      = json_decode( (string) get_post_meta( $submission_id, 'attempt_question_ids', true ), true );

	if ( ! is_array( $stored_ids ) || empty( $stored_ids ) ) {
		return $all;
	}

	return dmc_exam_questions_by_ids( $all, array_map( 'intval', $stored_ids ), true );
}

/**
 * Get exam page IDs (pages using exam template).
 *
 * @return int[]
 */
function dmc_exam_get_exam_page_ids() {
	$query = new WP_Query(
		[
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'fields'         => 'ids',
			'meta_key'       => '_wp_page_template',
			'meta_value'     => 'page-templates/exam.php',
			'orderby'        => 'title',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		]
	);

	return array_map( 'intval', $query->posts );
}

/**
 * Display name for a job-fair / exam event.
 *
 * @param int $page_id Exam page ID.
 * @return string
 */
function dmc_exam_get_event_name( $page_id ) {
	$page_id = (int) $page_id;

	if ( ! $page_id ) {
		return __( 'Ngày hội việc làm', 'flatsome-child' );
	}

	if ( function_exists( 'get_field' ) ) {
		$custom = trim( (string) get_field( 'exam_event_name', $page_id ) );

		if ( '' !== $custom ) {
			return $custom;
		}
	}

	$title = get_the_title( $page_id );

	return $title ?: __( 'Ngày hội việc làm', 'flatsome-child' );
}

/**
 * Load submission rows for admin list.
 *
 * @param int    $page_id Optional exam page filter.
 * @param string $search  Optional search (name/phone).
 * @param int    $paged   Page number.
 * @param int    $per_page Per page.
 * @return array{items: array<int, array<string, mixed>>, total: int}
 */
function dmc_exam_query_submissions( $page_id = 0, $search = '', $paged = 1, $per_page = 20 ) {
	$page_id  = (int) $page_id;
	$paged    = max( 1, (int) $paged );
	$per_page = (int) $per_page;
	$fetch_all = $per_page < 0;
	$per_page = $fetch_all ? 20 : max( 1, $per_page );
	$search   = trim( (string) $search );

	$args = [
		'post_type'      => 'dmc_exam_submission',
		'post_status'    => 'publish',
		'posts_per_page' => $search ? 300 : ( $fetch_all ? -1 : $per_page ),
		'paged'          => ( $search || $fetch_all ) ? 1 : $paged,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'fields'         => 'ids',
		'no_found_rows'  => false,
	];

	if ( $page_id > 0 ) {
		$args['meta_query'] = [
			[
				'key'   => 'exam_page_id',
				'value' => $page_id,
				'type'  => 'NUMERIC',
			],
		];
	}

	$query = new WP_Query( $args );
	$rows  = [];

	foreach ( $query->posts as $sid ) {
		$rows[] = dmc_exam_get_submission_row( (int) $sid );
	}

	if ( '' !== $search ) {
		$needle = mb_strtolower( $search );
		$rows   = array_values(
			array_filter(
				$rows,
				static function ( $row ) use ( $needle ) {
					$hay = mb_strtolower(
						( $row['name'] ?? '' ) . ' ' .
						( $row['phone'] ?? '' ) . ' ' .
						( $row['department'] ?? '' ) . ' ' .
						( $row['event_name'] ?? '' )
					);

					return false !== mb_strpos( $hay, $needle );
				}
			)
		);

		$total  = count( $rows );
		$offset = ( $paged - 1 ) * $per_page;
		$items  = array_slice( $rows, $offset, $per_page );

		return [
			'items' => $items,
			'total' => $total,
		];
	}

	$items = [];
	foreach ( $query->posts as $sid ) {
		$items[] = dmc_exam_get_submission_row( (int) $sid );
	}

	return [
		'items' => $items,
		'total' => (int) $query->found_posts,
	];
}

/**
 * Fetch all submissions for export (no pagination).
 *
 * @param int    $page_id Optional exam page filter.
 * @param string $search  Optional search (name/phone).
 * @return array<int, array<string, mixed>>
 */
function dmc_exam_query_submissions_for_export( $page_id = 0, $search = '' ) {
	$result = dmc_exam_query_submissions( $page_id, $search, 1, -1 );

	return $result['items'];
}

/**
 * Whether a submission answered every gradable question correctly.
 *
 * @param int $correct  Correct count.
 * @param int $gradable Gradable count.
 * @return bool
 */
function dmc_exam_is_all_correct( $correct, $gradable ) {
	return $gradable > 0 && (int) $correct === (int) $gradable;
}

/**
 * Split unix milliseconds into sortable date/time parts (site timezone).
 *
 * @param int $unix_ms Unix timestamp in milliseconds.
 * @return array<string, int|string>
 */
function dmc_exam_split_unix_ms( $unix_ms ) {
	$unix_ms = max( 0, (int) $unix_ms );

	if ( $unix_ms <= 0 ) {
		return [
			'date'        => '',
			'hour'        => '',
			'minute'      => '',
			'second'      => '',
			'millisecond' => '',
			'unix_ms'     => 0,
		];
	}

	$seconds = (int) floor( $unix_ms / 1000 );
	$ms      = $unix_ms % 1000;
	$dt      = new DateTime( '@' . $seconds );
	$dt->setTimezone( wp_timezone() );

	return [
		'date'        => $dt->format( 'd/m/Y' ),
		'hour'        => (int) $dt->format( 'G' ),
		'minute'      => (int) $dt->format( 'i' ),
		'second'      => (int) $dt->format( 's' ),
		'millisecond' => $ms,
		'unix_ms'     => $unix_ms,
	];
}

/**
 * Split a duration in milliseconds into sortable parts.
 *
 * @param int $duration_ms Duration in milliseconds.
 * @return array<string, int>
 */
function dmc_exam_split_duration_ms( $duration_ms ) {
	$duration_ms   = max( 0, (int) $duration_ms );
	$total_seconds = (int) floor( $duration_ms / 1000 );
	$ms            = $duration_ms % 1000;

	return [
		'hour'        => (int) floor( $total_seconds / 3600 ),
		'minute'      => (int) floor( ( $total_seconds % 3600 ) / 60 ),
		'second'      => $total_seconds % 60,
		'millisecond' => $ms,
		'total_ms'    => $duration_ms,
	];
}

/**
 * Normalized submission row for admin UI.
 *
 * @param int $submission_id Submission post ID.
 * @return array<string, mixed>
 */
function dmc_exam_get_submission_row( $submission_id ) {
	$submission_id = (int) $submission_id;
	$page_id       = (int) get_post_meta( $submission_id, 'exam_page_id', true );
	$correct       = (int) get_post_meta( $submission_id, 'correct_count', true );
	$gradable      = (int) get_post_meta( $submission_id, 'gradable_count', true );
	$score         = get_post_meta( $submission_id, 'score_percent', true );
	$unix_ms       = (int) get_post_meta( $submission_id, 'submitted_at_unix_ms', true );
	$stored        = (string) get_post_meta( $submission_id, 'submitted_at', true );
	$time_spent_ms = (int) get_post_meta( $submission_id, 'time_spent_ms', true );
	$time_spent    = (int) get_post_meta( $submission_id, 'time_spent_seconds', true );

	if ( $time_spent_ms <= 0 && $time_spent > 0 ) {
		$time_spent_ms = $time_spent * 1000;
	}

	$submitted_parts = dmc_exam_split_unix_ms( $unix_ms );
	$duration_parts  = dmc_exam_split_duration_ms( $time_spent_ms );
	$all_correct     = dmc_exam_is_all_correct( $correct, $gradable );

	return [
		'id'                 => $submission_id,
		'page_id'            => $page_id,
		'event_name'         => dmc_exam_get_event_name( $page_id ),
		'name'               => (string) get_post_meta( $submission_id, 'candidate_name', true ),
		'phone'              => (string) get_post_meta( $submission_id, 'candidate_phone', true ),
		'department'         => (string) get_post_meta( $submission_id, 'candidate_department', true ),
		'correct'            => $correct,
		'gradable'           => $gradable,
		'all_correct'        => $all_correct,
		'score'              => ( '' === $score || null === $score ) ? null : (float) $score,
		'is_timeout'         => (int) get_post_meta( $submission_id, 'is_timeout', true ),
		'time_spent'         => $time_spent,
		'time_spent_ms'      => $time_spent_ms,
		'submitted_at'       => $unix_ms ? dmc_exam_format_unix_ms( $unix_ms ) : ( $stored ?: '—' ),
		'submitted_unix_ms'  => $unix_ms,
		'submitted_date'     => $submitted_parts['date'],
		'submitted_hour'     => $submitted_parts['hour'],
		'submitted_minute'   => $submitted_parts['minute'],
		'submitted_second'   => $submitted_parts['second'],
		'submitted_ms'       => $submitted_parts['millisecond'],
		'duration_hour'      => $duration_parts['hour'],
		'duration_minute'    => $duration_parts['minute'],
		'duration_second'    => $duration_parts['second'],
		'duration_ms'        => $duration_parts['millisecond'],
		'duration_total_ms'  => $duration_parts['total_ms'],
		'answers'            => json_decode( (string) get_post_meta( $submission_id, 'answers', true ), true ) ?: [],
	];
}

/**
 * Total exam time limit in seconds (minutes + seconds from ACF).
 *
 * @param int $page_id Page ID. 0 = current.
 * @return int
 */
function dmc_exam_get_time_limit_seconds( $page_id = 0 ) {
	$page_id = $page_id ? (int) $page_id : get_the_ID();

	if ( ! $page_id || ! function_exists( 'get_field' ) ) {
		return 0;
	}

	$minutes = max( 0, (int) get_field( 'exam_time_limit', $page_id ) );
	$seconds = max( 0, min( 59, (int) get_field( 'exam_time_limit_seconds', $page_id ) ) );

	return ( $minutes * 60 ) + $seconds;
}

/**
 * Resolve time limit seconds from session payload (supports legacy time_limit_min).
 *
 * @param array<string, mixed> $session Session or lock data.
 * @return int
 */
function dmc_exam_session_time_limit_seconds( array $session ) {
	if ( isset( $session['time_limit_seconds'] ) ) {
		return max( 0, (int) $session['time_limit_seconds'] );
	}

	return max( 0, (int) ( $session['time_limit_min'] ?? 0 ) ) * 60;
}

/**
 * Format seconds as MM:SS (or H:MM:SS when >= 1 hour).
 *
 * @param int $total_seconds Seconds.
 * @return string
 */
function dmc_exam_format_countdown( $total_seconds ) {
	$total_seconds = max( 0, (int) $total_seconds );
	$hours         = (int) floor( $total_seconds / 3600 );
	$minutes       = (int) floor( ( $total_seconds % 3600 ) / 60 );
	$seconds       = $total_seconds % 60;

	if ( $hours > 0 ) {
		return sprintf( '%d:%02d:%02d', $hours, $minutes, $seconds );
	}

	return sprintf( '%02d:%02d', $minutes, $seconds );
}

/**
 * Normalize phone for duplicate checks.
 *
 * @param string $phone Raw phone.
 * @return string Digits only (leading 0 kept when present).
 */
function dmc_exam_normalize_phone( $phone ) {
	$digits = preg_replace( '/\D+/', '', (string) $phone );
	$digits = is_string( $digits ) ? $digits : '';

	if ( 0 === strpos( $digits, '84' ) && strlen( $digits ) >= 11 ) {
		$digits = '0' . substr( $digits, 2 );
	}

	return $digits;
}

/**
 * Server timestamp with millisecond precision.
 *
 * @return array{formatted: string, unix_ms: int, iso: string}
 */
function dmc_exam_now_with_ms() {
	$timezone = wp_timezone();
	$dt       = new DateTime( 'now', $timezone );
	$unix_ms  = (int) round( microtime( true ) * 1000 );

	return [
		'formatted' => $dt->format( 'd/m/Y H:i:s' ) . '.' . substr( (string) $unix_ms, -3 ),
		'unix_ms'   => $unix_ms,
		'iso'       => $dt->format( 'Y-m-d\TH:i:s' ) . '.' . substr( (string) $unix_ms, -3 ) . $dt->format( 'P' ),
	];
}

/**
 * Format stored unix milliseconds for admin display.
 *
 * @param int $unix_ms Unix timestamp in milliseconds.
 * @return string
 */
function dmc_exam_format_unix_ms( $unix_ms ) {
	$unix_ms = (int) $unix_ms;

	if ( $unix_ms <= 0 ) {
		return '—';
	}

	$seconds = (int) floor( $unix_ms / 1000 );
	$ms      = $unix_ms % 1000;
	$dt      = new DateTime( '@' . $seconds );
	$dt->setTimezone( wp_timezone() );

	return $dt->format( 'd/m/Y H:i:s' ) . '.' . str_pad( (string) $ms, 3, '0', STR_PAD_LEFT );
}

/**
 * Transient key for session token.
 *
 * @param string $token Session token.
 * @return string
 */
function dmc_exam_session_transient_key( $token ) {
	return 'dmc_exam_sess_' . sanitize_key( $token );
}

/**
 * Read active exam session for a page (if any).
 *
 * @param int $page_id Exam page ID.
 * @return array<string, mixed>|null
 */
function dmc_exam_get_session( $page_id = 0 ) {
	$page_id = (int) $page_id;
	$token   = isset( $_COOKIE[ DMC_EXAM_SESSION_COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ DMC_EXAM_SESSION_COOKIE ] ) ) : '';

	if ( '' === $token ) {
		return null;
	}

	$data = get_transient( dmc_exam_session_transient_key( $token ) );

	if ( ! is_array( $data ) || empty( $data['page_id'] ) ) {
		dmc_exam_clear_session();
		return null;
	}

	if ( $page_id && (int) $data['page_id'] !== $page_id ) {
		return null;
	}

	$data['token'] = $token;

	return $data;
}

/**
 * Create / refresh exam session cookie + transient.
 *
 * @param array<string, mixed> $data Session payload.
 * @return string Token.
 */
function dmc_exam_set_session( array $data ) {
	$token = wp_generate_password( 32, false, false );
	$ttl   = DAY_IN_SECONDS;

	if ( ! empty( $data['expires_at'] ) ) {
		$ttl = max( MINUTE_IN_SECONDS, ( (int) $data['expires_at'] ) - time() + HOUR_IN_SECONDS );
	}

	set_transient( dmc_exam_session_transient_key( $token ), $data, $ttl );

	$secure   = is_ssl();
	$httponly = true;

	if ( PHP_VERSION_ID >= 70300 ) {
		setcookie(
			DMC_EXAM_SESSION_COOKIE,
			$token,
			[
				'expires'  => time() + $ttl,
				'path'     => COOKIEPATH ? COOKIEPATH : '/',
				'domain'   => COOKIE_DOMAIN,
				'secure'   => $secure,
				'httponly' => $httponly,
				'samesite' => 'Lax',
			]
		);
	} else {
		setcookie( DMC_EXAM_SESSION_COOKIE, $token, time() + $ttl, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, $secure, $httponly );
	}

	$_COOKIE[ DMC_EXAM_SESSION_COOKIE ] = $token;

	return $token;
}

/**
 * Clear exam session cookie + transient.
 */
function dmc_exam_clear_session() {
	$token = isset( $_COOKIE[ DMC_EXAM_SESSION_COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ DMC_EXAM_SESSION_COOKIE ] ) ) : '';

	if ( '' !== $token ) {
		delete_transient( dmc_exam_session_transient_key( $token ) );
	}

	if ( PHP_VERSION_ID >= 70300 ) {
		setcookie(
			DMC_EXAM_SESSION_COOKIE,
			'',
			[
				'expires'  => time() - YEAR_IN_SECONDS,
				'path'     => COOKIEPATH ? COOKIEPATH : '/',
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			]
		);
	} else {
		setcookie( DMC_EXAM_SESSION_COOKIE, '', time() - YEAR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
	}

	unset( $_COOKIE[ DMC_EXAM_SESSION_COOKIE ] );
}

/**
 * Remaining seconds for a session (0 if expired / unlimited).
 *
 * @param array<string, mixed> $session Session data.
 * @return int
 */
function dmc_exam_remaining_seconds( array $session ) {
	$limit_seconds = dmc_exam_session_time_limit_seconds( $session );

	if ( $limit_seconds <= 0 ) {
		return 0;
	}

	$started = (int) ( $session['started_at'] ?? 0 );
	$elapsed = max( 0, time() - $started );

	return max( 0, $limit_seconds - $elapsed );
}

/**
 * Whether session time has expired.
 *
 * @param array<string, mixed> $session Session data.
 * @return bool
 */
function dmc_exam_session_is_expired( array $session ) {
	$limit_seconds = dmc_exam_session_time_limit_seconds( $session );

	if ( $limit_seconds <= 0 ) {
		return false;
	}

	return dmc_exam_remaining_seconds( $session ) <= 0;
}

/**
 * Transient key locking a phone for one exam attempt.
 *
 * @param int    $page_id Exam page ID.
 * @param string $phone   Normalized phone.
 * @return string
 */
function dmc_exam_phone_lock_key( $page_id, $phone ) {
	return 'dmc_exam_lock_' . (int) $page_id . '_' . md5( (string) $phone );
}

/**
 * Read active phone lock for an exam.
 *
 * @param int    $page_id Exam page ID.
 * @param string $phone   Raw or normalized phone.
 * @return array<string, mixed>|null
 */
function dmc_exam_get_phone_lock( $page_id, $phone ) {
	$phone_n = dmc_exam_normalize_phone( $phone );

	if ( ! $page_id || '' === $phone_n ) {
		return null;
	}

	$lock = get_transient( dmc_exam_phone_lock_key( $page_id, $phone_n ) );

	return is_array( $lock ) ? $lock : null;
}

/**
 * Create / refresh phone lock for an exam attempt.
 *
 * @param int                  $page_id Exam page ID.
 * @param array<string, mixed> $data    Lock payload.
 */
function dmc_exam_set_phone_lock( $page_id, array $data ) {
	$phone_n = dmc_exam_normalize_phone( (string) ( $data['phone'] ?? '' ) );

	if ( ! $page_id || '' === $phone_n ) {
		return;
	}

	$expires_at = (int) ( $data['expires_at'] ?? ( time() + HOUR_IN_SECONDS ) );
	$ttl        = max( MINUTE_IN_SECONDS, $expires_at - time() + HOUR_IN_SECONDS );

	set_transient( dmc_exam_phone_lock_key( $page_id, $phone_n ), $data, $ttl );
}

/**
 * Clear phone lock.
 *
 * @param int    $page_id Exam page ID.
 * @param string $phone   Raw or normalized phone.
 */
function dmc_exam_clear_phone_lock( $page_id, $phone ) {
	$phone_n = dmc_exam_normalize_phone( $phone );

	if ( ! $page_id || '' === $phone_n ) {
		return;
	}

	delete_transient( dmc_exam_phone_lock_key( $page_id, $phone_n ) );
}

/**
 * If a phone lock expired without submission, finalize timeout attempt.
 *
 * @param int    $page_id Exam page ID.
 * @param string $phone   Phone.
 * @param string $name    Name.
 * @param string $department Department.
 * @return bool True when a timeout submission was created.
 */
function dmc_exam_finalize_expired_phone_lock( $page_id, $phone, $name = '', $department = '' ) {
	$lock = dmc_exam_get_phone_lock( $page_id, $phone );

	if ( ! $lock ) {
		return false;
	}

	$expires_at = (int) ( $lock['expires_at'] ?? 0 );
	$limit_seconds = dmc_exam_session_time_limit_seconds( $lock );

	if ( $limit_seconds <= 0 || $expires_at <= 0 || time() < $expires_at ) {
		return false;
	}

	$name       = $name ?: (string) ( $lock['name'] ?? '' );
	$phone      = $phone ?: (string) ( $lock['phone'] ?? '' );
	$department = $department ?: (string) ( $lock['department'] ?? '' );

	if ( ! dmc_exam_find_existing_submission( $page_id, $phone, $name ) ) {
		$limit_seconds = dmc_exam_session_time_limit_seconds( $lock );
		$questions     = dmc_exam_get_session_questions( $page_id, $lock );

		dmc_exam_save_submission(
			$page_id,
			[
				'name'       => $name,
				'phone'      => $phone,
				'department' => $department,
			],
			[],
			$limit_seconds,
			true,
			[],
			$questions
		);
	}

	dmc_exam_clear_phone_lock( $page_id, $phone );

	return true;
}

/**
 * Find existing submission by phone (and optionally name) for an exam page.
 *
 * @param int    $page_id Exam page ID.
 * @param string $phone   Phone number.
 * @param string $name    Optional candidate name.
 * @return int Submission post ID or 0.
 */
function dmc_exam_find_existing_submission( $page_id, $phone, $name = '' ) {
	$page_id = (int) $page_id;
	$phone_n = dmc_exam_normalize_phone( $phone );
	$name_n  = mb_strtolower( trim( (string) $name ) );

	if ( ! $page_id || '' === $phone_n ) {
		return 0;
	}

	$query = new WP_Query(
		[
			'post_type'      => 'dmc_exam_submission',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'fields'         => 'ids',
			'meta_query'     => [
				[
					'key'   => 'exam_page_id',
					'value' => $page_id,
					'type'  => 'NUMERIC',
				],
			],
			'no_found_rows'  => true,
		]
	);

	foreach ( $query->posts as $submission_id ) {
		$stored_phone = dmc_exam_normalize_phone( (string) get_post_meta( $submission_id, 'candidate_phone', true ) );

		if ( $stored_phone === $phone_n ) {
			return (int) $submission_id;
		}

		// Fallback for older submissions without phone: match name + empty phone.
		if ( '' === $stored_phone && '' !== $name_n ) {
			$stored_name = mb_strtolower( trim( (string) get_post_meta( $submission_id, 'candidate_name', true ) ) );

			if ( $stored_name === $name_n ) {
				return (int) $submission_id;
			}
		}
	}

	return 0;
}

/**
 * Persist a submission record.
 *
 * @param int                  $page_id     Exam page ID.
 * @param array<string, mixed> $candidate   name/phone/department.
 * @param array<string, string> $answers    Answer map qid => choice.
 * @param int                  $time_spent  Seconds spent.
 * @param bool                 $is_timeout  Timed out flag.
 * @param array<string, mixed> $client      Optional client timestamps.
 * @param array<int, array<string, mixed>>|null $questions Questions for this attempt.
 * @return array<string, mixed>|WP_Error
 */
function dmc_exam_save_submission( $page_id, array $candidate, array $answers, $time_spent = 0, $is_timeout = false, array $client = [], $questions = null ) {
	$page_id = (int) $page_id;

	if ( null === $questions || ! is_array( $questions ) || empty( $questions ) ) {
		$questions = dmc_exam_get_questions( $page_id );
	}

	if ( ! $page_id || empty( $questions ) ) {
		return new WP_Error( 'invalid_exam', __( 'Bài thi không hợp lệ.', 'flatsome-child' ) );
	}

	$candidate_name = sanitize_text_field( (string) ( $candidate['name'] ?? '' ) );
	$candidate_phone = sanitize_text_field( (string) ( $candidate['phone'] ?? '' ) );
	$candidate_dept  = sanitize_text_field( (string) ( $candidate['department'] ?? '' ) );
	$phone_normalized = dmc_exam_normalize_phone( $candidate_phone );

	$server_now  = dmc_exam_now_with_ms();
	$show_result = (bool) get_field( 'exam_show_result', $page_id );
	$score       = null;
	$correct     = 0;
	$gradable    = 0;

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
		return $submission_id;
	}

	update_post_meta( $submission_id, 'exam_page_id', $page_id );
	update_post_meta( $submission_id, 'exam_page_title', $exam_title );
	update_post_meta( $submission_id, 'candidate_name', $candidate_name );
	update_post_meta( $submission_id, 'candidate_phone', $candidate_phone );
	update_post_meta( $submission_id, 'candidate_phone_normalized', $phone_normalized );
	update_post_meta( $submission_id, 'candidate_department', $candidate_dept );
	update_post_meta( $submission_id, 'answers', wp_json_encode( $answers ) );
	update_post_meta(
		$submission_id,
		'attempt_question_ids',
		wp_json_encode( array_map( 'intval', array_column( $questions, 'id' ) ) )
	);
	$time_spent_ms = absint( $client['time_spent_ms'] ?? 0 );

	if ( $time_spent_ms <= 0 && $time_spent > 0 ) {
		$time_spent_ms = max( 0, (int) $time_spent ) * 1000;
	}

	$submitted_parts = dmc_exam_split_unix_ms( $server_now['unix_ms'] );
	$duration_parts  = dmc_exam_split_duration_ms( $time_spent_ms );
	$all_correct     = dmc_exam_is_all_correct( $correct, $gradable );

	update_post_meta( $submission_id, 'time_spent_seconds', max( 0, (int) $time_spent ) );
	update_post_meta( $submission_id, 'time_spent_ms', $time_spent_ms );
	update_post_meta( $submission_id, 'is_timeout', $is_timeout ? 1 : 0 );
	update_post_meta( $submission_id, 'submitted_at', $server_now['formatted'] );
	update_post_meta( $submission_id, 'submitted_at_unix_ms', $server_now['unix_ms'] );
	update_post_meta( $submission_id, 'submitted_at_iso', $server_now['iso'] );
	update_post_meta( $submission_id, 'submitted_date', $submitted_parts['date'] );
	update_post_meta( $submission_id, 'submitted_hour', $submitted_parts['hour'] );
	update_post_meta( $submission_id, 'submitted_minute', $submitted_parts['minute'] );
	update_post_meta( $submission_id, 'submitted_second', $submitted_parts['second'] );
	update_post_meta( $submission_id, 'submitted_millisecond', $submitted_parts['millisecond'] );
	update_post_meta( $submission_id, 'duration_hour', $duration_parts['hour'] );
	update_post_meta( $submission_id, 'duration_minute', $duration_parts['minute'] );
	update_post_meta( $submission_id, 'duration_second', $duration_parts['second'] );
	update_post_meta( $submission_id, 'duration_millisecond', $duration_parts['millisecond'] );
	update_post_meta( $submission_id, 'duration_total_ms', $duration_parts['total_ms'] );
	update_post_meta( $submission_id, 'client_submitted_at', sanitize_text_field( (string) ( $client['label'] ?? '' ) ) );
	update_post_meta( $submission_id, 'client_submitted_unix_ms', absint( $client['unix_ms'] ?? 0 ) );

	if ( null !== $score ) {
		update_post_meta( $submission_id, 'score_percent', $score );
		update_post_meta( $submission_id, 'correct_count', $correct );
		update_post_meta( $submission_id, 'gradable_count', $gradable );
		update_post_meta( $submission_id, 'all_correct', $all_correct ? 1 : 0 );
	}

	$response = [
		'message'       => $is_timeout
			? __( 'Hết giờ làm bài. Phiên thi đã kết thúc.', 'flatsome-child' )
			: __( 'Đã nộp bài thành công.', 'flatsome-child' ),
		'submitted_at'  => $server_now['formatted'],
		'unix_ms'       => $server_now['unix_ms'],
		'submission_id' => $submission_id,
		'is_timeout'    => $is_timeout,
		'redirect'      => true,
	];

	if ( $show_result && null !== $score ) {
		$response['score']          = $score;
		$response['correct_count']  = $correct;
		$response['gradable_count'] = $gradable;
	}

	return $response;
}

/**
 * If session expired without submit, finalize empty/partial attempt and clear session.
 *
 * @param int $page_id Exam page ID.
 * @return string|null Flash message key, or null.
 */
function dmc_exam_finalize_expired_session( $page_id ) {
	$session = dmc_exam_get_session( $page_id );

	if ( ! $session || ! dmc_exam_session_is_expired( $session ) ) {
		return null;
	}

	$phone = (string) ( $session['phone'] ?? '' );
	$name  = (string) ( $session['name'] ?? '' );

	if ( ! dmc_exam_find_existing_submission( $page_id, $phone, $name ) ) {
		$limit_seconds = dmc_exam_session_time_limit_seconds( $session );
		$questions     = dmc_exam_get_session_questions( $page_id, $session );

		dmc_exam_save_submission(
			$page_id,
			[
				'name'       => $name,
				'phone'      => $phone,
				'department' => (string) ( $session['department'] ?? '' ),
			],
			[],
			$limit_seconds,
			true,
			[],
			$questions
		);
	}

	dmc_exam_clear_phone_lock( $page_id, $phone );
	dmc_exam_clear_session();

	return 'timeout';
}
