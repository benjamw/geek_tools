// Digits section: binary / octal / decimal / hexadecimal conversions and
// the grouped / padded / split checkboxes. Ported from the jQuery handlers
// that lived in process.js.

import { bindWithDelay, triggerShareUpdate, isBlocked, modPad } from './util.js';

const BigInteger = window.BigInteger;

const BIN_REGEX = /[^01]+/img;
const OCT_REGEX = /[^0-7]+/img;
const DEC_REGEX = /[^0-9-]+/img;
const HEX_REGEX = /[^0-9a-f]+/img;

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

// Space-group a string into chunks of `num`, counting from the right by
// default. Ported from process.js.
function group(str, num, reverse) {
	if (!str) {
		return "";
	}
	if ("undefined" === typeof reverse) {
		reverse = true;
	}
	reverse = !!reverse;

	const regex = new RegExp(".{1," + num + "}", "img");
	if (reverse) {
		return str.split("").reverse().join("").match(regex).map(function (s) {
			return s.split("").reverse().join("");
		}).reverse().join(" ");
	}
	const arr = str.match(regex);
	return arr ? arr.join(" ") : "";
}

function parseOne(type, value) {
	switch (type) {
		case "bin":
			return BigInteger.parse(value.replace(/[^01]+/img, ""), 2);
		case "oct":
			return BigInteger.parse(value.replace(/[^0-7]+/img, ""), 8);
		case "dec": {
			let n = BigInteger.parse(value.replace(/[^0-9-]+/img, ""), 10);
			// convert 2s compliment negative numbers to unsigned 256
			if ((-128 <= n) && (n < 0)) {
				n = n.add(256);
			}
			return n;
		}
		case "hex":
			return BigInteger.parse(value.replace(/[^0-9a-f]+/img, ""), 16);
		default:
			return BigInteger.parse("0", 10);
	}
}

function digitsHandler(evt) {
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
	const padded = el("int_padded").checked;
	const grouped = el("int_grouped").checked;

	if ("" === val.replace(/[\s,.]+/g, "")) {
		["conv_bin", "conv_oct", "conv_dec", "conv_hex"].forEach(id => setVal(id, ""));
		if ("share:update" !== evt.type) {
			shareVal("conv_utf8bytes", "");
			shareVal("conv_bytes", "");
		}
		return;
	}

	if (grouped) {
		const converted = parseOne(type, val.replace(/[\s,.]+/g, ""));
		setVal("conv_bin", group(modPad(converted.toString(2), 8, "0"), 8));
		setVal("conv_oct", group(modPad(converted.toString(8), 3, "0"), 3));
		setVal("conv_dec", group(converted.toString(10), 3));
		const hex = group(modPad(converted.toString(16), 2, "0"), 2);
		setVal("conv_hex", hex);

		if ("share:update" !== evt.type) {
			shareVal("conv_utf8bytes", hex);
			shareVal("conv_bytes", hex);
		}
		return;
	}

	const regex = { bin: BIN_REGEX, oct: OCT_REGEX, dec: DEC_REGEX, hex: HEX_REGEX }[type] || null;
	const valArray = val.replace(/^\s+|\s+$/img, "").replace(regex, " ").replace(/\s+/img, " ").split(" ");
	const outvar = { bin: [], oct: [], dec: [], hex: [] };

	for (let i = 0, len = valArray.length; i < len; i += 1) {
		const converted = parseOne(type, valArray[i]);
		outvar.bin.push(converted.toString(2));
		outvar.oct.push(converted.toString(8));
		outvar.dec.push(converted.toString(10));
		outvar.hex.push(converted.toString(16));
	}

	if (padded) {
		// Note: legacy behaviour - dec inherits oct's pad length (3).
		let padlen = 0;
		["bin", "oct", "dec", "hex"].forEach(function (t) {
			if ("bin" === t) { padlen = 8; }
			else if ("oct" === t) { padlen = 3; }
			else if ("hex" === t) { padlen = 2; }
			outvar[t] = outvar[t].map(s => modPad(s, padlen, "0"));
		});

		if ("share:update" !== evt.type) {
			shareVal("conv_utf8bytes", outvar.hex.join(" "));
			shareVal("conv_bytes", outvar.hex.join(" "));
		}
	}

	setVal("conv_bin", outvar.bin.join(" "));
	setVal("conv_oct", outvar.oct.join(" "));
	setVal("conv_dec", outvar.dec.join(" "));
	setVal("conv_hex", outvar.hex.join(" "));
}

// The three mode checkboxes are mutually exclusive; checking one clears the
// other two and re-runs the conversion (unless we got here via share:update).
function bindCheckbox(id, other1, other2) {
	const box = el(id);
	["change", "click", "share:update"].forEach(function (type) {
		box.addEventListener(type, function (evt) {
			el(other1).checked = false;
			el(other2).checked = false;
			if ("share:update" !== evt.type) {
				const first = document.querySelector("textarea.digits");
				if (first) {
					triggerShareUpdate(first);
				}
			}
		});
	});
}

export function initDigits() {
	bindCheckbox("int_grouped", "int_split", "int_padded");
	bindCheckbox("int_padded", "int_split", "int_grouped");
	bindCheckbox("int_split", "int_padded", "int_grouped");

	document.querySelectorAll("textarea.digits").forEach(function (ta) {
		bindWithDelay(ta, "change keyup share:update", digitsHandler, bindDelay);
	});
}
