// Vanilla handlers for the per-textarea action buttons
// (copy / clear / send / html / file). Replaces the jQuery
// button handlers that lived at the bottom of process.js.

import { triggerEvent } from './util.js';

// Copy text to the clipboard using the async Clipboard API
// (replaces the old textarea.select() + document.execCommand("copy")).
function copyText(text) {
	if (navigator.clipboard && navigator.clipboard.writeText) {
		return navigator.clipboard.writeText(text);
	}
	return Promise.reject(new Error('Clipboard API unavailable'));
}

// Briefly show a message in the button group's span.msg, then fade it
// out over 5s and reset it (replaces jQuery .fadeOut).
function flashMsg(span, text) {
	if (!span) {
		return;
	}
	span.textContent = text;
	span.style.opacity = '1';
	const anim = span.animate([{ opacity: 1 }, { opacity: 0 }], { duration: 5000 });
	anim.onfinish = function () {
		span.textContent = '';
		span.style.opacity = '1';
	};
}

// The first textarea in the button's form-group (matches the old
// jQuery .val(), which read the first matched element).
function groupTextarea(btn) {
	const group = btn.closest('.form-group');
	return group ? group.querySelector('textarea') : null;
}

function handleCopy(btn) {
	const text = groupTextarea(btn);
	const span = btn.closest('.form-group').querySelector('span.msg');
	if (!text) {
		return;
	}

	copyText(text.value).then(function () {
		flashMsg(span, 'Copied!');
	}, function () {
		flashMsg(span, 'Copy failed');
	});
}

function handleClear(btn) {
	const text = groupTextarea(btn);
	if (!text) {
		return;
	}
	text.value = '';
	triggerEvent(text, 'keyup');
}

function handleSend(btn) {
	const text = groupTextarea(btn);
	if (!text) {
		return;
	}
	const raw = document.getElementById('conv_raw');
	raw.value = text.value;
	triggerEvent(raw, 'keyup');

	const top = raw.getBoundingClientRect().top + window.pageYOffset - 40;
	window.scrollTo({ top: top, behavior: 'smooth' });
}

function handleHtml(btn) {
	const text = groupTextarea(btn);
	if (!text) {
		return;
	}
	const wndw = window.open();
	wndw.document.title = 'Geek Tools HTML Output';
	wndw.document.write(text.value);
}

function handleFile(btn) {
	const text = groupTextarea(btn);
	if (!text) {
		return;
	}
	const fileInput = document.getElementById('file');
	fileInput.value = text.value;
	fileInput.form.submit();
}

// Single delegated click listener for every action button, so it works
// for the repeated $buttons blocks under each textarea.
export function initButtons() {
	document.addEventListener('click', function (evt) {
		const btn = evt.target.closest('button');
		if (!btn) {
			return;
		}

		if (btn.classList.contains('copy')) {
			handleCopy(btn);
		}
		else if (btn.classList.contains('clear')) {
			handleClear(btn);
		}
		else if (btn.classList.contains('send')) {
			handleSend(btn);
		}
		else if (btn.classList.contains('html')) {
			handleHtml(btn);
		}
		else if (btn.classList.contains('file')) {
			handleFile(btn);
		}
	});
}
