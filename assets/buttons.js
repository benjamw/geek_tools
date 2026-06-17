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

// Decode a standard-alphabet Base64 string to bytes, mirroring the server's
// base64_decode of the File field. Whitespace is stripped; invalid input
// throws (caught by the caller).
function base64ToBytes(b64) {
	const bin = atob(b64.replace(/\s+/g, ''));
	const bytes = new Uint8Array(bin.length);
	for (let i = 0; i < bin.length; i++) {
		bytes[i] = bin.charCodeAt(i);
	}
	return bytes;
}

// Leading-byte signatures for the common types the server's finfo would
// detect. Anything unmatched falls back to text/plain or octet-stream.
const MAGIC = [
	{ sig: [0x89, 0x50, 0x4e, 0x47], mime: 'image/png', ext: 'png' },
	{ sig: [0xff, 0xd8, 0xff], mime: 'image/jpeg', ext: 'jpg' },
	{ sig: [0x47, 0x49, 0x46, 0x38], mime: 'image/gif', ext: 'gif' },
	{ sig: [0x42, 0x4d], mime: 'image/bmp', ext: 'bmp' },
	{ sig: [0x49, 0x49, 0x2a, 0x00], mime: 'image/tiff', ext: 'tiff' },
	{ sig: [0x4d, 0x4d, 0x00, 0x2a], mime: 'image/tiff', ext: 'tiff' },
	{ sig: [0x00, 0x00, 0x01, 0x00], mime: 'image/x-icon', ext: 'ico' },
	{ sig: [0x25, 0x50, 0x44, 0x46], mime: 'application/pdf', ext: 'pdf' },
	{ sig: [0x50, 0x4b, 0x03, 0x04], mime: 'application/zip', ext: 'zip' },
	{ sig: [0x1f, 0x8b], mime: 'application/gzip', ext: 'gz' },
	{ sig: [0x37, 0x7a, 0xbc, 0xaf, 0x27, 0x1c], mime: 'application/x-7z-compressed', ext: '7z' },
	{ sig: [0x52, 0x61, 0x72, 0x21], mime: 'application/x-rar', ext: 'rar' },
	{ sig: [0x4f, 0x67, 0x67, 0x53], mime: 'application/ogg', ext: 'ogg' },
	{ sig: [0x49, 0x44, 0x33], mime: 'audio/mpeg', ext: 'mp3' },
	{ sig: [0x7f, 0x45, 0x4c, 0x46], mime: 'application/x-executable', ext: 'elf' },
];

function startsWith(bytes, sig) {
	if (bytes.length < sig.length) {
		return false;
	}
	for (let i = 0; i < sig.length; i++) {
		if (bytes[i] !== sig[i]) {
			return false;
		}
	}
	return true;
}

// RIFF containers share a 'RIFF' header but differ at offset 8.
function sniffRiff(bytes) {
	if (!startsWith(bytes, [0x52, 0x49, 0x46, 0x46]) || bytes.length < 12) {
		return null;
	}
	const tag = String.fromCharCode(bytes[8], bytes[9], bytes[10], bytes[11]);
	if ('WEBP' === tag) {
		return { mime: 'image/webp', ext: 'webp' };
	}
	if ('WAVE' === tag) {
		return { mime: 'audio/x-wav', ext: 'wav' };
	}
	if ('AVI ' === tag) {
		return { mime: 'video/x-msvideo', ext: 'avi' };
	}
	return null;
}

// Reject NUL and most C0 control bytes (allowing tab/newline/CR) the way a
// "looks like text" heuristic would, so plain text gets a .txt extension.
function looksLikeText(bytes) {
	for (let i = 0; i < bytes.length; i++) {
		const b = bytes[i];
		if (0 === b || (b < 0x20 && b !== 0x09 && b !== 0x0a && b !== 0x0d)) {
			return false;
		}
	}
	return true;
}

function sniffType(bytes) {
	const riff = sniffRiff(bytes);
	if (riff) {
		return riff;
	}
	for (const m of MAGIC) {
		if (startsWith(bytes, m.sig)) {
			return { mime: m.mime, ext: m.ext };
		}
	}
	if (0 === bytes.length || looksLikeText(bytes)) {
		return { mime: 'text/plain', ext: 'txt' };
	}
	return { mime: 'application/octet-stream', ext: 'bin' };
}

function downloadBlob(blob, filename) {
	const url = URL.createObjectURL(blob);
	const a = document.createElement('a');
	a.href = url;
	a.download = filename;
	document.body.appendChild(a);
	a.click();
	a.remove();
	URL.revokeObjectURL(url);
}

// Pull the filename out of a Content-Disposition header, if present.
function filenameFromDisposition(header) {
	if (!header) {
		return null;
	}
	const match = /filename="?([^";]+)"?/i.exec(header);
	return match ? match[1] : null;
}

// Fallback path: decode the Base64 locally, sniff the type from its magic
// bytes, and trigger a Blob download (loses Apache's full MIME map).
function downloadLocally(b64, span) {
	let bytes;
	try {
		bytes = base64ToBytes(b64);
	}
	catch (e) {
		flashMsg(span, 'Invalid Base64');
		return;
	}
	const type = sniffType(bytes);
	downloadBlob(new Blob([bytes], { type: type.mime }), 'geek_file.' + type.ext);
}

// Dual-mode: POST the Base64 to ajax.php for server-side finfo + the Apache
// MIME map; if that endpoint is missing (404) or unreachable, fall back to a
// client-side Blob download with magic-byte sniffing.
function handleFile(btn) {
	const text = groupTextarea(btn);
	if (!text) {
		return;
	}
	const span = btn.closest('.form-group').querySelector('span.msg');
	const b64 = text.value;

	const body = new URLSearchParams();
	body.set('file', b64);

	fetch('ajax.php', { method: 'POST', body: body }).then(function (resp) {
		if (!resp.ok) {
			return false;
		}
		return resp.blob().then(function (blob) {
			const name = filenameFromDisposition(resp.headers.get('Content-Disposition')) || 'geek_file';
			downloadBlob(blob, name);
			return true;
		});
	}, function () {
		return false;
	}).then(function (served) {
		if (!served) {
			downloadLocally(b64, span);
		}
	});
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
