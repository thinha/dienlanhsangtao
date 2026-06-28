(function () {
	'use strict';

	var config = window.dmcExam || {};
	var form = document.getElementById('dmc-exam-form');

	if (!form || !config.ajaxUrl) {
		return;
	}

	var timerEl = document.getElementById('dmc-exam-timer');
	var noticeEl = document.getElementById('dmc-exam-notice');
	var resultEl = document.getElementById('dmc-exam-result');
	var submitBtn = document.getElementById('dmc-exam-submit');
	var nameInput = document.getElementById('dmc-exam-candidate-name');
	var startedAt = Date.now();
	var remainingSeconds = Math.max(0, parseInt(config.timeLimitMin, 10) || 0) * 60;
	var timerId = null;
	var isSubmitting = false;

	function pad(value) {
		return String(value).padStart(2, '0');
	}

	function formatTimer(totalSeconds) {
		var minutes = Math.floor(totalSeconds / 60);
		var seconds = totalSeconds % 60;
		return pad(minutes) + ':' + pad(seconds);
	}

	function showNotice(message, type) {
		if (!noticeEl) {
			return;
		}

		noticeEl.hidden = false;
		noticeEl.textContent = message;
		noticeEl.className = 'dmc-exam-form__notice dmc-exam-form__notice--' + (type || 'error');
	}

	function hideNotice() {
		if (noticeEl) {
			noticeEl.hidden = true;
			noticeEl.textContent = '';
		}
	}

	function formatClientTimestamp(date) {
		var day = pad(date.getDate());
		var month = pad(date.getMonth() + 1);
		var year = date.getFullYear();
		var hours = pad(date.getHours());
		var minutes = pad(date.getMinutes());
		var seconds = pad(date.getSeconds());
		var ms = String(date.getMilliseconds()).padStart(3, '0');

		return day + '/' + month + '/' + year + ' ' + hours + ':' + minutes + ':' + seconds + '.' + ms;
	}

	function collectAnswers() {
		var answers = {};
		var inputs = form.querySelectorAll('.dmc-exam-question');

		for (var i = 0; i < inputs.length; i++) {
			var question = inputs[i];
			var qid = question.getAttribute('data-question-id');
			var selected = question.querySelector('input[type="radio"]:checked');

			if (!selected) {
				return null;
			}

			answers['q_' + qid] = selected.value;
		}

		return answers;
	}

	function renderResult(data) {
		if (!resultEl) {
			return;
		}

		var html = '<h3>Đã nộp bài thành công</h3>';
		html += '<p><strong>Thời gian nộp:</strong> ' + (data.submitted_at || '') + '</p>';

		if (typeof data.score !== 'undefined') {
			html += '<p><strong>Điểm:</strong> ' + data.score + '% (' + data.correct_count + '/' + data.gradable_count + ')</p>';
		}

		resultEl.innerHTML = html;
		resultEl.hidden = false;
	}

	function disableForm() {
		form.classList.add('is-submitted');

		var fields = form.querySelectorAll('input, button');
		for (var i = 0; i < fields.length; i++) {
			fields[i].disabled = true;
		}
	}

	function submitExam(isAuto) {
		if (isSubmitting) {
			return;
		}

		hideNotice();

		var candidateName = nameInput ? nameInput.value.trim() : '';

		if (config.requireName && !candidateName) {
			showNotice(config.messages.nameRequired || 'Vui lòng nhập họ tên thí sinh.');
			if (nameInput) {
				nameInput.focus();
			}
			return;
		}

		var answers = collectAnswers();

		if (!answers) {
			showNotice(config.messages.required || 'Vui lòng trả lời tất cả câu hỏi trước khi nộp bài.');
			return;
		}

		if (isAuto) {
			showNotice(config.messages.timeUp || 'Hết giờ làm bài. Bài thi sẽ được nộp tự động.', 'warning');
		}

		isSubmitting = true;

		if (submitBtn) {
			submitBtn.disabled = true;
			submitBtn.textContent = config.messages.submitting || 'Đang gửi bài...';
		}

		var now = new Date();
		var payload = new FormData();
		payload.append('action', 'dmc_exam_submit');
		payload.append('nonce', config.nonce || '');
		payload.append('page_id', String(config.pageId || ''));
		payload.append('candidate_name', candidateName);
		payload.append('time_spent_seconds', String(Math.max(0, Math.floor((Date.now() - startedAt) / 1000))));
		payload.append('client_submitted_unix_ms', String(now.getTime()));
		payload.append('client_submitted_label', formatClientTimestamp(now));

		Object.keys(answers).forEach(function (key) {
			payload.append('answers[' + key + ']', answers[key]);
		});

		fetch(config.ajaxUrl, {
			method: 'POST',
			body: payload,
			credentials: 'same-origin',
		})
			.then(function (response) {
				return response.json();
			})
			.then(function (result) {
				if (!result || !result.success) {
					throw new Error((result && result.data && result.data.message) || config.messages.submitError);
				}

				disableForm();
				renderResult(result.data || {});
			})
			.catch(function (error) {
				isSubmitting = false;

				if (submitBtn) {
					submitBtn.disabled = false;
					submitBtn.textContent = 'Nộp bài';
				}

				showNotice(error.message || config.messages.submitError);
			});
	}

	function tickTimer() {
		if (!timerEl || remainingSeconds <= 0) {
			return;
		}

		remainingSeconds -= 1;
		timerEl.textContent = formatTimer(remainingSeconds);

		if (remainingSeconds <= 60) {
			timerEl.classList.add('is-warning');
		}

		if (remainingSeconds <= 0) {
			window.clearInterval(timerId);
			submitExam(true);
		}
	}

	if (timerEl && remainingSeconds > 0) {
		timerId = window.setInterval(tickTimer, 1000);
	}

	form.addEventListener('submit', function (event) {
		event.preventDefault();
		submitExam(false);
	});
})();
