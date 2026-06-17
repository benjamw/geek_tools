// UTF-8 section: the #utf8 textareas each represent the same code points in a
// different notation (char / bytes / c-escaped bytes / HTML dec & hex NCR /
// escaped unicode / code point). Editing one re-derives all the others via the
// converters in utf8_conv.js. Ported from the jQuery handler in process.js.

import { bindWithDelay, triggerShareUpdate, isBlocked } from './util.js';
import {
	fromChar, fromBytes, fromCbytes,
	fromHtmldec, fromHtmlhex, fromEsc, fromCode
} from './utf8_conv.js';

const bindDelay = 500; // ms

// Map the textarea id suffix (after the "utf8" prefix) to its converter.
// Replaces the old window["from" + Capitalised] global lookup.
const CONVERTERS = {
	char: fromChar,
	bytes: fromBytes,
	cbytes: fromCbytes,
	htmldec: fromHtmldec,
	htmlhex: fromHtmlhex,
	esc: fromEsc,
	code: fromCode
};

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

// Tick "Padded" and cascade so the digits section renders matching bytes.
function cascadeBytes(evt, hex) {
	if ("share:update" === evt.type) {
		return;
	}
	const padBox = el("int_padded");
	if (padBox) {
		padBox.checked = true;
		triggerShareUpdate(padBox);
	}
	shareVal("conv_hex", hex);
	shareVal("conv_bytes", hex);
}

function utf8Handler(evt) {
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

	const type = this.id.split("_")[1].slice(4);
	const val = this.value;

	if ("" === val) {
		["conv_utf8char", "conv_utf8htmldec", "conv_utf8htmlhex", "conv_utf8bytes",
			"conv_utf8cbytes", "conv_utf8esc", "conv_utf8code"].forEach(id => setVal(id, ""));
		cascadeBytes(evt, "");
		return;
	}

	const convert = CONVERTERS[type];
	if (!convert) {
		return;
	}
	const ret = convert(val);

	// ret = [char, bytes, codePoints]; derive the hex and zero-padded forms.
	ret.push(ret[2].map(x => x.toString(16).toUpperCase()));
	ret.push(ret[3].map(x => x.padStart(4, "0")));

	setVal("conv_utf8char", ret[0]);
	setVal("conv_utf8bytes", ret[1].toUpperCase());
	setVal("conv_utf8cbytes", "\\x" + ret[1].toLowerCase().split(" ").join("\\x"));
	setVal("conv_utf8htmldec", "&#" + ret[2].join(";&#") + ";");
	setVal("conv_utf8htmlhex", "&#x" + ret[3].join(";&#x") + ";");
	setVal("conv_utf8esc", "\\u" + ret[4].join("\\u"));
	setVal("conv_utf8code", "U+" + ret[4].join(" U+"));

	cascadeBytes(evt, ret[1].toUpperCase());
}

export function initUtf8() {
	const section = el("utf8");
	if (!section) {
		return;
	}
	section.querySelectorAll("textarea").forEach(function (ta) {
		bindWithDelay(ta, "change keyup share:update", utf8Handler, bindDelay);
	});
}
