// Converters section: the #converters textareas POST to ajax.php (kept as a
// temporary backend) and the returned encodings are written back into the
// sibling fields. Also hosts the base64 URL-safe detection / toggle. Ported
// from the jQuery handlers that lived in process.js.

import { bindWithDelay, triggerShareUpdate, isBlocked, block } from './util.js';

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

	const body = new URLSearchParams({
		encodings: "true",
		val: val,
		from: type
	});

	fetch(window.location.href, {
		method: "POST",
		headers: { "Accept": "application/json" },
		body: body
	}).then(function (resp) {
		if (!resp.ok) {
			throw new Error("HTTP " + resp.status);
		}
		return resp.json();
	}).then(function (data) {
		const caesar = el("caesar");
		if (caesar) {
			caesar.value = 13;
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
	}).catch(function (err) {
		console.error("Converters request failed:", err);
	});
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
}
