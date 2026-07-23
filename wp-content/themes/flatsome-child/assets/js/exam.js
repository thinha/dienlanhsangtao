(function () {
	'use strict';

	var config = window.dmcExam || {};

	if (!config.ajaxUrl) {
		return;
	}

	var gateForm = document.getElementById('dmc-exam-gate');
	var examForm = document.getElementById('dmc-exam-form');

	function pad(value) {
		return String(value).padStart(2, '0');
	}

	function formatTimer(totalSeconds) {
		totalSeconds = Math.max(0, totalSeconds | 0);
		var hours = Math.floor(totalSeconds / 3600);
		var minutes = Math.floor((totalSeconds % 3600) / 60);
		var seconds = totalSeconds % 60;

		if (hours > 0) {
			return hours + ':' + pad(minutes) + ':' + pad(seconds);
		}

		return pad(minutes) + ':' + pad(seconds);
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

	function showNotice(el, message, type) {
		if (!el) {
			return;
		}

		el.hidden = false;
		el.textContent = message;
		el.className = 'dmc-exam-form__notice dmc-exam-form__notice--' + (type || 'error');
	}

	function hideNotice(el) {
		if (el) {
			el.hidden = true;
			el.textContent = '';
		}
	}

	function redirectToGate(status) {
		var url = new URL(window.location.href);
		url.searchParams.set('exam', status || 'done');
		window.location.replace(url.toString());
	}

	function parseJsonResponse(response, fallbackMessage) {
		var message = fallbackMessage || config.messages.startError || 'Có lỗi xảy ra.';

		return response
			.text()
			.then(function (text) {
				if (!text) {
					throw new Error(message);
				}

				try {
					return JSON.parse(text);
				} catch (error) {
					throw new Error(message);
				}
			})
			.then(function (result) {
				if (!response.ok && result && result.data && result.data.message) {
					throw new Error(result.data.message);
				}

				return result;
			});
	}

	function isValidPhone(phone) {
		var compact = String(phone || '').replace(/\s+/g, '');

		if (/^0[0-9]{9}$/.test(compact)) {
			return true;
		}

		return /^\+84[0-9]{9}$/.test(compact);
	}

	function initGate() {
		if (!gateForm) {
			return;
		}

		var noticeEl = document.getElementById('dmc-exam-gate-notice');
		var startBtn = document.getElementById('dmc-exam-start');
		var nameInput = document.getElementById('dmc-exam-candidate-name');
		var phoneInput = document.getElementById('dmc-exam-candidate-phone');
		var deptInput = document.getElementById('dmc-exam-candidate-department');
		var isStarting = false;

		gateForm.addEventListener('submit', function (event) {
			event.preventDefault();

			if (isStarting) {
				return;
			}

			hideNotice(noticeEl);

			var name = nameInput ? nameInput.value.trim() : '';
			var phone = phoneInput ? phoneInput.value.trim() : '';
			var department = deptInput ? deptInput.value.trim() : '';

			if (!name) {
				showNotice(noticeEl, config.messages.nameRequired || 'Vui lòng nhập họ tên thí sinh.');
				if (nameInput) {
					nameInput.focus();
				}
				return;
			}

			if (!phone) {
				showNotice(noticeEl, config.messages.phoneRequired || 'Vui lòng nhập số điện thoại.');
				if (phoneInput) {
					phoneInput.focus();
				}
				return;
			}

			if (!isValidPhone(phone)) {
				showNotice(
					noticeEl,
					config.messages.phoneInvalid ||
						'Số điện thoại phải là 10 số (VD: 0943980279) hoặc dạng +84 (VD: +84943980279).'
				);
				if (phoneInput) {
					phoneInput.focus();
				}
				return;
			}

			if (!department) {
				showNotice(noticeEl, config.messages.deptRequired || 'Vui lòng nhập khoa.');
				if (deptInput) {
					deptInput.focus();
				}
				return;
			}

			isStarting = true;

			if (startBtn) {
				startBtn.disabled = true;
				startBtn.textContent = config.messages.starting || 'Đang bắt đầu...';
			}

			var payload = new FormData();
			payload.append('action', 'dmc_exam_start');
			payload.append('nonce', config.nonce || '');
			payload.append('page_id', String(config.pageId || ''));
			payload.append('candidate_name', name);
			payload.append('candidate_phone', phone);
			payload.append('candidate_department', department);

			fetch(config.ajaxUrl, {
				method: 'POST',
				body: payload,
				credentials: 'same-origin',
			})
				.then(parseJsonResponse)
				.then(function (result) {
					if (!result || !result.success) {
						throw new Error(
							(result && result.data && result.data.message) ||
								config.messages.startError ||
								'Không bắt đầu được bài thi.'
						);
					}

					var url = new URL(window.location.href);
					url.searchParams.delete('exam');
					window.location.replace(url.toString());
				})
				.catch(function (error) {
					isStarting = false;

					if (startBtn) {
						startBtn.disabled = false;
						startBtn.textContent = 'Bắt đầu làm bài';
					}

					showNotice(noticeEl, error.message || config.messages.startError);
				});
		});
	}

	function initExam() {
		if (!examForm || !config.hasSession) {
			return;
		}

		var timerEl = document.getElementById('dmc-exam-timer');
		var noticeEl = document.getElementById('dmc-exam-notice');
		var resultEl = document.getElementById('dmc-exam-result');
		var submitBtn = document.getElementById('dmc-exam-submit');
		var startedAt = (parseInt(config.startedAt, 10) || 0) * 1000;
		var remainingSeconds = Math.max(0, parseInt(config.remainingSeconds, 10) || 0);
		var timeLimitSeconds = Math.max(0, parseInt(config.timeLimitSeconds, 10) || 0);
		var timerId = null;
		var isSubmitting = false;

		if (!startedAt) {
			startedAt = Date.now() - Math.max(0, timeLimitSeconds - remainingSeconds) * 1000;
		}

		function collectAnswers(requireAll) {
			var answers = {};
			var inputs = examForm.querySelectorAll('.dmc-exam-question');

			for (var i = 0; i < inputs.length; i++) {
				var question = inputs[i];
				var qid = question.getAttribute('data-question-id');
				var selected = question.querySelector('input[type="radio"]:checked');

				if (!selected) {
					if (requireAll) {
						return null;
					}
					continue;
				}

				answers['q_' + qid] = selected.value;
			}

			return answers;
		}

		function renderResult(data) {
			if (!resultEl) {
				return;
			}

			var html = '<h3>' + (data.is_timeout ? 'Hết giờ — đã kết thúc phiên' : 'Đã nộp bài thành công') + '</h3>';
			html += '<p><strong>Thời gian nộp:</strong> ' + (data.submitted_at || '') + '</p>';

			if (typeof data.score !== 'undefined') {
				html +=
					'<p><strong>Điểm:</strong> ' +
					data.score +
					'% (' +
					data.correct_count +
					'/' +
					data.gradable_count +
					')</p>';
			}

			html +=
				'<p>' +
				(config.messages.doneRedirect || 'Đang quay lại màn hình đăng ký...') +
				'</p>';

			resultEl.innerHTML = html;
			resultEl.hidden = false;
		}

		function disableForm() {
			examForm.classList.add('is-submitted');

			var fields = examForm.querySelectorAll('input, button');
			for (var i = 0; i < fields.length; i++) {
				fields[i].disabled = true;
			}
		}

		function submitExam(isAuto) {
			if (isSubmitting) {
				return;
			}

			hideNotice(noticeEl);

			var answers = collectAnswers(!isAuto);

			if (!isAuto && !answers) {
				showNotice(noticeEl, config.messages.required || 'Vui lòng trả lời tất cả câu hỏi trước khi nộp bài.');
				return;
			}

			if (!answers) {
				answers = {};
			}

			if (isAuto) {
				showNotice(
					noticeEl,
					config.messages.timeUp || 'Hết giờ làm bài. Hệ thống sẽ nộp bài và kết thúc phiên.',
					'warning'
				);
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
			payload.append('is_timeout', isAuto ? '1' : '0');
			var elapsedMs = Math.max(0, Date.now() - startedAt);
			payload.append('time_spent_seconds', String(Math.floor(elapsedMs / 1000)));
			payload.append('time_spent_ms', String(elapsedMs));
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
					return parseJsonResponse(response, config.messages.submitError);
				})
				.then(function (result) {
					if (!result || !result.success) {
						var data = (result && result.data) || {};

						if (data.redirect) {
							redirectToGate(isAuto ? 'timeout' : 'done');
							return;
						}

						throw new Error(data.message || config.messages.submitError);
					}

					disableForm();
					renderResult(result.data || {});

					window.setTimeout(function () {
						redirectToGate(isAuto || (result.data && result.data.is_timeout) ? 'timeout' : 'done');
					}, 1800);
				})
				.catch(function (error) {
					isSubmitting = false;

					if (submitBtn) {
						submitBtn.disabled = false;
						submitBtn.textContent = 'Nộp bài';
					}

					showNotice(noticeEl, error.message || config.messages.submitError);
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

		if (timerEl) {
			if (remainingSeconds <= 0 && timeLimitSeconds > 0) {
				submitExam(true);
			} else if (remainingSeconds > 0) {
				timerEl.textContent = formatTimer(remainingSeconds);
				if (remainingSeconds <= 60) {
					timerEl.classList.add('is-warning');
				}
				timerId = window.setInterval(tickTimer, 1000);
			}
		}

		examForm.addEventListener('submit', function (event) {
			event.preventDefault();
			submitExam(false);
		});
	}

	initGate();
	initExam();
})();
