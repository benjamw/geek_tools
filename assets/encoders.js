// Client-side port of ajax.php's do_encodings(). The input is decoded from
// its source form into a `raw` string, then re-encoded into every supported
// form. Codecs are added incrementally (4a-4d); the DECODERS/ENCODERS maps
// drive both the per-field decode and the encode-all output.

import { caesar } from './util.js';
import { toMorse, fromMorse } from './morse.js';
import { punyEncode, punyDecode } from './punycode.js';

const encoder = new TextEncoder();
// Non-fatal: invalid byte sequences become U+FFFD (PHP instead flagged them
// in do_encodings' json_encode retry loop).
const decoder = new TextDecoder();

function toUtf8Bytes(str) {
	return encoder.encode(str);
}

function fromUtf8Bytes(bytes) {
	return decoder.decode(bytes);
}

// ---- bytes (hex) -------------------------------------------------------
export function toBytes(str) {
	return Array.from(toUtf8Bytes(str), function (b) {
		return b.toString(16).toUpperCase().padStart(2, "0");
	}).join(" ");
}

export function fromBytes(val) {
	const clean = val.replace(/\s+/g, "").toUpperCase();
	if ("" === clean) {
		return "";
	}
	if ((clean.length % 2) || !/^[0-9A-F]+$/.test(clean)) {
		return "ERROR: invalid binary string";
	}
	const bytes = new Uint8Array(clean.length / 2);
	for (let i = 0; i < bytes.length; i++) {
		bytes[i] = parseInt(clean.slice(i * 2, i * 2 + 2), 16);
	}
	return fromUtf8Bytes(bytes);
}

// ---- code (A<->1) ------------------------------------------------------
export function toCode(val) {
	const out = [];
	for (const ch of val.toUpperCase()) {
		if (/[A-Z]/.test(ch)) {
			out.push(ch.charCodeAt(0) - 64);
		}
		else if (/[0-9]/.test(ch) && Number(ch) > 0) {
			out.push(String.fromCharCode(Number(ch) + 64));
		}
		else if ("" !== ch) {
			out.push(".");
		}
	}
	return out.join(" ");
}

export function fromCode(val) {
	// array_filter(explode(" ", ...)) drops "" and "0" (falsy) segments.
	const arr = val.split(" ").filter(function (s) { return "" !== s && "0" !== s; });
	let out = "";
	for (const seg of arr) {
		const n = parseInt(seg, 10);
		out += (n >= 1 && n <= 26) ? String.fromCharCode(n + 64) : "-";
	}
	return out;
}

// ---- rot13 / reverse (self-inverse) ------------------------------------
export function rot13(val) {
	return caesar(val, 13);
}

export function reverse(val) {
	return Array.from(val).reverse().join("");
}

// ---- base64 (with base64url-both decode) -------------------------------
function bytesToBase64(bytes) {
	let bin = "";
	for (const b of bytes) {
		bin += String.fromCharCode(b);
	}
	return btoa(bin);
}

function base64ToBytes(b64) {
	const bin = atob(b64);
	const bytes = new Uint8Array(bin.length);
	for (let i = 0; i < bin.length; i++) {
		bytes[i] = bin.charCodeAt(i);
	}
	return bytes;
}

export function toBase64(str) {
	return bytesToBase64(toUtf8Bytes(str));
}

export function fromBase64(val) {
	let data = val.replace(/\s+/g, "");
	if (data.includes("-") || data.includes("_")) {
		data = data.replace(/-/g, "+").replace(/_/g, "/");
	}
	try {
		return fromUtf8Bytes(base64ToBytes(data));
	}
	catch (e) {
		return "";
	}
}

// ---- url (PHP urlencode: space -> +, unreserved -_.) --------------------
export function toUrl(str) {
	let out = "";
	for (const b of toUtf8Bytes(str)) {
		if ((b >= 0x30 && b <= 0x39) || (b >= 0x41 && b <= 0x5A)
			|| (b >= 0x61 && b <= 0x7A) || 0x2D === b || 0x5F === b || 0x2E === b) {
			out += String.fromCharCode(b);
		}
		else if (0x20 === b) {
			out += "+";
		}
		else {
			out += "%" + b.toString(16).toUpperCase().padStart(2, "0");
		}
	}
	return out;
}

export function fromUrl(val) {
	const s = val.replace(/\+/g, " ");
	const bytes = [];
	for (let i = 0; i < s.length;) {
		if ("%" === s[i] && /^[0-9A-Fa-f]{2}$/.test(s.slice(i + 1, i + 3))) {
			bytes.push(parseInt(s.slice(i + 1, i + 3), 16));
			i += 3;
		}
		else {
			for (const b of toUtf8Bytes(s[i])) {
				bytes.push(b);
			}
			i++;
		}
	}
	return fromUtf8Bytes(new Uint8Array(bytes));
}

// ---- quoted-printable (RFC 2045, mirroring PHP ext/standard) ------------
const QP_MAXL = 75;
const HEX = "0123456789ABCDEF";

export function quotedPrintableEncode(str) {
	const bytes = toUtf8Bytes(str);
	const out = [];
	let lp = 0;
	for (let i = 0; i < bytes.length; i++) {
		const c = bytes[i];
		const next = (i + 1 < bytes.length) ? bytes[i + 1] : -1;
		if (0x0D === c && 0x0A === next) {
			out.push(0x0D, 0x0A);
			i++;
			lp = 0;
			continue;
		}
		const isCntrl = (c <= 0x1F) || (0x7F === c);
		if (isCntrl || (c & 0x80) || (0x3D === c) || (0x20 === c && 0x0D === next)) {
			if ((lp += 3) > QP_MAXL) {
				out.push(0x3D, 0x0D, 0x0A);
				lp = 3;
			}
			out.push(0x3D, HEX.charCodeAt(c >> 4), HEX.charCodeAt(c & 0xF));
		}
		else {
			if ((++lp) > QP_MAXL) {
				out.push(0x3D, 0x0D, 0x0A);
				lp = 1;
			}
			out.push(c);
		}
	}
	return out.map(function (b) { return String.fromCharCode(b); }).join("");
}

export function quotedPrintableDecode(val) {
	const bytes = [];
	for (let i = 0; i < val.length;) {
		const ch = val[i];
		if ("=" === ch) {
			const pair = val.slice(i + 1, i + 3);
			if (/^[0-9A-Fa-f]{2}$/.test(pair)) {
				bytes.push(parseInt(pair, 16));
				i += 3;
			}
			else if ("\r" === val[i + 1] && "\n" === val[i + 2]) {
				i += 3;
			}
			else if ("\n" === val[i + 1] || "\r" === val[i + 1]) {
				i += 2;
			}
			else {
				bytes.push(0x3D);
				i++;
			}
		}
		else {
			for (const b of toUtf8Bytes(ch)) {
				bytes.push(b);
			}
			i++;
		}
	}
	return fromUtf8Bytes(new Uint8Array(bytes));
}

// ---- Base85 / Z85 (ports of vendor/Base85.php + vendor/Z85.php) ---------
// Faithful ports, including PHP's quirks: Base85's 'z'/'y' shortcuts and the
// padding swap, plus the decode() bug where an encoded length that is an exact
// multiple of 5 (i.e. the source was a multiple of 4 bytes) yields '' via
// substr($ret, 0, -0).
const BASE85_CHARS =
	"!\"#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstu";
const Z85_CHARS =
	"0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ.-:+=^!/*?&<>()[]{}@%$#";

function base85Encode(str, chars, isBase85) {
	const bytes = Array.from(toUtf8Bytes(str));
	let padding = 4 - (bytes.length % 4);
	if (bytes.length % 4 === 0) { padding = 0; }
	for (let i = padding; i > 0; i--) { bytes.push(0); }
	let ret = "";
	for (let c = 0; c < bytes.length; c += 4) {
		let tmp = 0;
		for (let j = 0; j < 4; j++) { tmp = (tmp * 256) + bytes[c + j]; }
		if (isBase85 && 0 === tmp) { ret += "z"; continue; }
		if (isBase85 && 538976288 === tmp) { ret += "y"; continue; }
		let div = 85 * 85 * 85 * 85;
		while (div >= 1) {
			ret += chars[Math.floor(tmp / div) % 85];
			div /= 85;
		}
	}
	if (padding) {
		if (isBase85) { ret = ret.replace(/z$/, "!!!!!"); }
		ret = ret.slice(0, -padding);
	}
	return ret;
}

function base85Decode(str, chars, isBase85) {
	str = str.replace(/\s+/g, "");
	if (/^<~[\s\S]*~>$/.test(str)) { str = str.slice(2, -2); }
	if (isBase85) {
		str = str.split("z").join("!!!!!").split("y").join("+<VdL/");
	}
	const idx = [];
	for (const ch of str) { idx.push(chars.indexOf(ch)); }
	let padding = 5 - (idx.length % 5);
	if (idx.length % 5 === 0) { padding = 0; }
	for (let i = padding; i > 0; i--) { idx.push(84); }
	const out = [];
	for (let c = 0; c < idx.length; c += 5) {
		let tmp = 0;
		for (let j = 0; j < 5; j++) { tmp = (tmp * 85) + idx[c + j]; }
		let div = 256 * 256 * 256;
		while (div >= 1) {
			out.push(Math.floor(tmp / div) % 256);
			div /= 256;
		}
	}
	// PHP: substr($ret, 0, -$padding); when padding === 0 this is substr(...,0,0).
	if (0 === padding) { return ""; }
	return fromUtf8Bytes(new Uint8Array(out.slice(0, out.length - padding)));
}

export function toBase85(str) { return base85Encode(str, BASE85_CHARS, true); }
export function fromBase85(val) { return base85Decode(val, BASE85_CHARS, true); }
export function toZ85(str) { return base85Encode(str, Z85_CHARS, false); }
export function fromZ85(val) { return base85Decode(val, Z85_CHARS, false); }

// ---- uuencode (verbatim port of PHP ext/standard/uuencode.c) -----------
function uuEnc(c) { c = c & 0x3f; return c ? (c + 0x20) : 0x60; }
function uuDec(c) { return (((c || 0) - 0x20) & 0x3f); }

export function uuencode(str) {
	const s = Array.from(toUtf8Bytes(str));
	const e = s.length;
	const b = function (k) { return (k >= 0 && k < e) ? s[k] : 0; };
	const c2 = function (k) { return uuEnc(((b(k) << 4) & 0x30) | ((b(k + 1) >> 4) & 0x0f)); };
	const c3 = function (k) { return uuEnc(((b(k + 1) << 2) & 0x3c) | ((b(k + 2) >> 6) & 0x03)); };
	const out = [];
	let len = 45;
	let p = 0;
	while ((p + 3) < e) {
		let ee = p + len;
		if (ee > e) {
			ee = e;
			len = ee - p;
			if (len % 3) { ee = p + Math.floor(len / 3) * 3; }
		}
		out.push(uuEnc(len));
		while (p < ee) {
			out.push(uuEnc(b(p) >> 2), c2(p), c3(p), uuEnc(b(p + 2) & 0x3f));
			p += 3;
		}
		if (45 === len) { out.push(0x0A); }
	}
	if (p < e) {
		if (45 === len) { out.push(uuEnc(e - p)); len = 0; }
		out.push(uuEnc(b(p) >> 2), c2(p));
		out.push(((e - p) > 1) ? c3(p) : uuEnc(0));
		out.push(((e - p) > 2) ? uuEnc(b(p + 2) & 0x3f) : uuEnc(0));
	}
	if (len < 45) { out.push(0x0A); }
	out.push(uuEnc(0), 0x0A);
	return out.map(function (x) { return String.fromCharCode(x); }).join("");
}

export function uudecode(str) {
	const e = str.length;
	if (0 === e) { return false; }
	const s = [];
	for (let i = 0; i < e; i++) { s.push(str.charCodeAt(i) & 0xff); }
	const out = [];
	let totalLen = 0;
	let p = 0;
	let len = 0;
	while (p < e) {
		len = uuDec(s[p++]);
		if (0 === len) { break; }
		if (len > e) { return false; }
		totalLen += len;
		const ee = p + ((45 === len) ? 60 : Math.floor(len * 1.33));
		if (ee > e) { return false; }
		while (p < ee) {
			if (p + 4 > e) { return false; }
			out.push((uuDec(s[p]) << 2 | uuDec(s[p + 1]) >> 4) & 0xff);
			out.push((uuDec(s[p + 1]) << 4 | uuDec(s[p + 2]) >> 2) & 0xff);
			out.push((uuDec(s[p + 2]) << 6 | uuDec(s[p + 3])) & 0xff);
			p += 4;
		}
		if (len < 45) { break; }
		p++;
	}
	len = totalLen;
	if (len > out.length) {
		out.push((uuDec(s[p]) << 2 | uuDec(s[p + 1]) >> 4) & 0xff);
		if (len > 1) {
			out.push((uuDec(s[p + 1]) << 4 | uuDec(s[p + 2]) >> 2) & 0xff);
			if (len > 2) {
				out.push((uuDec(s[p + 2]) << 6 | uuDec(s[p + 3])) & 0xff);
			}
		}
	}
	out.length = totalLen;
	return fromUtf8Bytes(new Uint8Array(out));
}

// ---- punycode / IDN (mirrors PHP intl idn_to_ascii / idn_to_utf8, UTS46
// with STD3 rules off: per-label, lowercase + NFC mapping, "xn--" prefix). ---
function isAscii(s) {
	for (const ch of s) { if (ch.codePointAt(0) > 0x7F) { return false; } }
	return true;
}

function uts46Map(label) {
	return label.toLowerCase().normalize("NFC");
}

// Split a domain into labels, allowing a single trailing root dot but
// rejecting leading/interior empty labels (PHP returns false for those).
// Returns null to signal the false case.
function splitLabels(str) {
	let labels = str.split(".");
	let trailingDot = false;
	if (labels.length > 1 && "" === labels[labels.length - 1]) {
		trailingDot = true;
		labels = labels.slice(0, -1);
	}
	for (const l of labels) {
		if ("" === l) { return null; }
	}
	return { labels: labels, trailingDot: trailingDot };
}

// UTS46 CheckHyphens: a label must not begin or end with a hyphen, nor carry a
// hyphen in both the third and fourth positions (the "xn--" ACE prefix excepted).
function checkHyphens(label) {
	if ("-" === label[0] || "-" === label[label.length - 1]) { return false; }
	if ("-" === label[2] && "-" === label[3] && 0 !== label.indexOf("xn--")) { return false; }
	return true;
}

export function toPuny(str) {
	const parts = splitLabels(str);
	if (null === parts) { return false; }
	const out = [];
	for (const label of parts.labels) {
		const mapped = uts46Map(label);
		const ascii = isAscii(mapped) ? mapped : ("xn--" + punyEncode(mapped));
		// idn_to_ascii enforces a 1..63 octet label length.
		if (ascii.length < 1 || ascii.length > 63) { return false; }
		if (!checkHyphens(ascii)) { return false; }
		out.push(ascii);
	}
	const domain = out.join(".");
	if (domain.length > 253) { return false; } // VerifyDnsLength
	return parts.trailingDot ? (domain + ".") : domain;
}

export function fromPuny(str) {
	const parts = splitLabels(str);
	if (null === parts) { return false; }
	const out = parts.labels.map(function (label) {
		const mapped = uts46Map(label);
		return (0 === mapped.indexOf("xn--")) ? punyDecode(mapped.slice(4)) : mapped;
	});
	const domain = out.join(".");
	return parts.trailingDot ? (domain + ".") : domain;
}

// xxencode is a no-op stub in ajax.php (convert_xxencode/xxdecode return $str).
function identity(v) { return v; }

// ---- dispatch ----------------------------------------------------------
const DECODERS = {
	bytes: fromBytes, code: fromCode, rot13: rot13, rev: reverse,
	morse: fromMorse, base64: fromBase64, base85: fromBase85, z85: fromZ85,
	uuencode: uudecode, xxencode: identity, quoted: quotedPrintableDecode,
	url: fromUrl, puny: fromPuny, raw: identity
};

const ENCODERS = {
	bytes: toBytes, code: toCode, rot13: rot13, rev: reverse,
	morse: toMorse, base64: toBase64, base85: toBase85, z85: toZ85,
	uuencode: uuencode, xxencode: identity, quoted: quotedPrintableEncode,
	url: toUrl, puny: toPuny, raw: identity
};

export function decodeToRaw(from, val) {
	const raw = (DECODERS[from] || DECODERS.raw)(val);
	// PHP coerces a failed decode (false from convert_uudecode / idn_to_utf8)
	// to '' when it feeds the result through the encoders.
	return (false === raw || null === raw || undefined === raw) ? "" : raw;
}

export function encodeAll(raw) {
	const out = {};
	for (const key in ENCODERS) {
		out[key] = ENCODERS[key](raw);
	}
	return out;
}
