<?php
/**
 * ACF fields — Trang thi trắc nghiệm.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register exam field group on exam page template.
 */
function dmc_exam_register_acf_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		[
			'key'      => 'group_dmc_exam',
			'title'    => 'Cấu hình bài thi',
			'fields'   => dmc_exam_get_acf_field_definitions(),
			'location' => [
				[
					[
						'param'    => 'page_template',
						'operator' => '==',
						'value'    => 'page-templates/exam.php',
					],
				],
			],
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'active'                => true,
		]
	);
}
add_action( 'acf/init', 'dmc_exam_register_acf_fields' );

/**
 * Field definitions.
 *
 * @return array<int, array<string, mixed>>
 */
function dmc_exam_get_acf_field_definitions() {
	return [
		[
			'key'           => 'field_dmc_exam_tab_general',
			'label'         => 'Thông tin chung',
			'type'          => 'tab',
		],
		[
			'key'           => 'field_dmc_exam_subtitle',
			'label'         => 'Mô tả ngắn',
			'name'          => 'exam_subtitle',
			'type'          => 'textarea',
			'rows'          => 3,
			'instructions'  => 'Hiển thị phía dưới tiêu đề trang.',
		],
		[
			'key'           => 'field_dmc_exam_event_name',
			'label'         => 'Tên ngày hội việc làm',
			'name'          => 'exam_event_name',
			'type'          => 'text',
			'instructions'  => 'Tên hiển thị trong Theme Settings → Ngày hội việc làm. Để trống sẽ dùng tiêu đề trang.',
		],
		[
			'key'           => 'field_dmc_exam_time_limit',
			'label'         => 'Thời gian làm bài — phút',
			'name'          => 'exam_time_limit',
			'type'          => 'number',
			'default_value' => 30,
			'min'           => 0,
			'step'          => 1,
			'wrapper'       => [
				'width' => '50',
			],
			'instructions'  => 'Phần phút của thời gian đếm ngược. Đặt phút = 0 và giây = 0 nếu không giới hạn.',
		],
		[
			'key'           => 'field_dmc_exam_time_limit_seconds',
			'label'         => 'Thời gian làm bài — giây',
			'name'          => 'exam_time_limit_seconds',
			'type'          => 'number',
			'default_value' => 0,
			'min'           => 0,
			'max'           => 59,
			'step'          => 1,
			'wrapper'       => [
				'width' => '50',
			],
			'instructions'  => 'Phần giây (0–59). Ví dụ: 1 phút 30 giây → phút = 1, giây = 30.',
		],
		[
			'key'           => 'field_dmc_exam_show_result',
			'label'         => 'Hiển thị điểm sau khi nộp',
			'name'          => 'exam_show_result',
			'type'          => 'true_false',
			'default_value' => 0,
			'ui'            => 1,
			'instructions'  => 'Chỉ tính điểm khi bạn chọn đáp án đúng cho từng câu.',
		],
		[
			'key'   => 'field_dmc_exam_tab_questions',
			'label' => 'Câu hỏi',
			'type'  => 'tab',
		],
		[
			'key'          => 'field_dmc_exam_questions',
			'label'        => 'Danh sách câu hỏi',
			'name'         => 'exam_questions',
			'type'         => 'repeater',
			'layout'       => 'block',
			'button_label' => 'Thêm câu hỏi',
			'min'          => 1,
			'sub_fields'   => [
				[
					'key'   => 'field_dmc_exam_question_text',
					'label' => 'Nội dung câu hỏi',
					'name'  => 'question_text',
					'type'  => 'textarea',
					'rows'  => 3,
					'required' => 1,
				],
				[
					'key'   => 'field_dmc_exam_answer_a',
					'label' => 'Đáp án A',
					'name'  => 'answer_a',
					'type'  => 'text',
					'required' => 1,
				],
				[
					'key'   => 'field_dmc_exam_answer_b',
					'label' => 'Đáp án B',
					'name'  => 'answer_b',
					'type'  => 'text',
					'required' => 1,
				],
				[
					'key'   => 'field_dmc_exam_answer_c',
					'label' => 'Đáp án C',
					'name'  => 'answer_c',
					'type'  => 'text',
					'required' => 1,
				],
				[
					'key'   => 'field_dmc_exam_answer_d',
					'label' => 'Đáp án D',
					'name'  => 'answer_d',
					'type'  => 'text',
					'required' => 1,
				],
				[
					'key'     => 'field_dmc_exam_correct_answer',
					'label'   => 'Đáp án đúng (tùy chọn)',
					'name'    => 'correct_answer',
					'type'    => 'select',
					'choices' => [
						''  => '— Không chấm điểm —',
						'a' => 'A',
						'b' => 'B',
						'c' => 'C',
						'd' => 'D',
					],
					'allow_null' => 1,
					'ui'         => 1,
				],
			],
		],
	];
}
