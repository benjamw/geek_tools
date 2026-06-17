// Converters section: the #converters textareas decode the edited field back to
// a raw string and re-encode it into every sibling field, entirely client-side
// via encoders.js (a port of ajax.php's do_encodings). Also hosts the base64
// URL-safe detection / toggle. Ported from the jQuery handlers in process.js.

import { bindWithDelay, triggerShareUpdate, isBlocked, block, caesar } from './util.js';
import { decodeToRaw, encodeAll } from './encoders.js';

const bindDelay = 500; // ms

function el(id) {
	return document.getElementById(id);
}

// Set value only when the field isn't focused (mirrors .not(":focus").val()).
function setVal(id, v) {
	const node = el(id);
	if (node && node !== document.activeElement) {
		node.value = v;
	}
}

// Set value (when unfocused) and cascade a share:update, mirroring
// .not(":focus").val(v).trigger("share:update").
function shareVal(id, v) {
	const node = el(id);
	if (node && node !== document.activeElement) {
		node.value = v;
		triggerShareUpdate(node);
	}
}

// Tick the URL-safe checkbox when the base64 field contains - or _.
function checkBase64() {
	const node = el("conv_base64");
	const box = el("b64url");
	if (!node || !box) {
		return;
	}
	const s = node.value;
	box.checked = (-1 !== s.indexOf("-") || -1 !== s.indexOf("_"));
}

function convertersHandler(evt) {
	// if there was a modifier pressed (Alt, Ctrl, etc), don't do anything
	// the change event will capture any changes like cuts or pastes
	if (evt.altKey || evt.ctrlKey || evt.metaKey) {
		evt.preventDefault();
		evt.stopPropagation();
		return;
	}

	if (("change" === evt.type) && isBlocked()) {
		return;
	}

	const type = this.id.split("_")[1];
	const val = this.value;

	if ("base64" === type) {
		checkBase64();
	}

	let data;
	try {
		data = encodeAll(decodeToRaw(type, val));
	}
	catch (err) {
		console.error("Converters encoding failed:", err);
		return;
	}

	const caesarField = el("caesar");
	if (caesarField) {
		caesarField.value = 13;
	}

	for (const enc in data) {
		if (!Object.prototype.hasOwnProperty.call(data, enc)) {
			continue;
		}
		const out = (false === data[enc]) ? "" : data[enc];
		setVal("conv_" + enc, out);

		// pass the bytes along to the other areas
		if (("share:update" !== evt.type) && ("bytes" === enc)) {
			const padBox = el("int_padded");
			if (padBox) {
				padBox.checked = true;
				triggerShareUpdate(padBox);
			}
			shareVal("conv_utf8bytes", out);
			shareVal("conv_hex", out);
		}
	}
}

// Swap base64 <-> base64url alphabet in place. Suppresses the resulting
// change event for 500ms so the converters handler doesn't re-run.
function b64urlHandler(evt) {
	evt.stopPropagation();
	block(500);

	const node = el("conv_base64");
	const box = el("b64url");
	if (!node || !box) {
		return;
	}

	let str = node.value;
	if (box.checked) {
		str = str.replace(/\+/g, "-").replace(/\//g, "_");
	}
	else {
		str = str.replace(/-/g, "+").replace(/_/g, "/");
	}
	node.value = str;
}

// Rot-N (Caesar) field: shift conv_raw into conv_rot13. The block suppresses
// the change event for 500ms so cascading handlers don't re-run.
function caesarHandler(evt) {
	evt.stopPropagation();
	block(500);

	const raw = el("conv_raw");
	const out = el("conv_rot13");
	if (!raw || !out) {
		return;
	}
	out.value = caesar(raw.value, this.value);
}

export function initConverters() {
	const section = el("converters");
	if (section) {
		section.querySelectorAll("textarea").forEach(function (ta) {
			bindWithDelay(ta, "change keyup share:update", convertersHandler, bindDelay);
		});
	}

	const box = el("b64url");
	if (box) {
		box.addEventListener("change", b64urlHandler);
		box.addEventListener("click", b64urlHandler);
	}

	const caesarInput = el("caesar");
	if (caesarInput) {
		caesarInput.addEventListener("change", caesarHandler);
		caesarInput.addEventListener("click", caesarHandler);
	}
}
