<?php
/**
 * Exam — helpers.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
