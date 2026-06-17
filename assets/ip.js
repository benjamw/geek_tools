// IPv4 / IPv6 section: each block's inputs hold the same address in different
// notations (text / decimal / hex or RFC1924); editing one re-derives the
// others. Also hosts the two "Toggle Compression" buttons. Ported from the
// jQuery handlers and helpers that lived in process.js.

import { bindWithDelay, triggerShareUpdate, isBlocked, block } from './util.js';

const bindDelay = 500; // ms

// RFC 1924 base-85 alphabet (used for the IPv6 RFC1924 representation).
const base85Chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz!#$%&()*+-;<=>?@^_`{|}~';

function el(id) {
	return document.getElementById(id);
}

function setVal(id, v) {
	const node = el(id);
	if (node && node !== document.activeElement) {
		node.value = v;
	}
}

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

// ---- IPv6 helpers (moved verbatim from process.js) ----

export function expandIPv6(ip) {
	// split on the :: compression marker (at most one is allowed)
	let head, tail;
	if (ip.includes('::')) {
		const halves = ip.split('::');
		head = halves[0] === '' ? [] : halves[0].split(':');
		tail = halves[1] === '' ? [] : halves[1].split(':');
	}
	else {
		head = ip.split(':');
		tail = [];
	}

	const middle = [];
	for (let i = 0, missing = 8 - (head.length + tail.length); i < missing; i++) {
		middle.push('0000');
	}

	return head.concat(middle, tail)
		.map(part => part.padStart(4, '0'))
		.join(':');
}

function compressIPv6(ip) {
	ip = ip.toUpperCase().replace(/:0+/g, ':');

	// Find the longest sequence of zeroes
	let longestZeroSeq = 0;
	let longestZeroStart = -1;
	let currentZeroSeq = 0;
	let currentZeroStart = -1;

	for (let i = 0; i < 8; i++) {
		let part = ip.split(':')[i];
		if (part === '') {
			if (currentZeroSeq === 0) {
				currentZeroStart = i;
			}
			currentZeroSeq++;
		}
		else {
			if (currentZeroSeq > longestZeroSeq) {
				longestZeroSeq = currentZeroSeq;
				longestZeroStart = currentZeroStart;
			}
			currentZeroSeq = 0;
		}
	}

	// Replace the longest sequence with '::'
	if (longestZeroSeq > 1) {
		ip = ip.split(':').slice(0, longestZeroStart).join(':') +
			'::' +
			ip.split(':').slice(longestZeroStart + longestZeroSeq).join(':');
	}

	return ip;
}

function isValidIPv6Dec(val) {
	if (val.includes(":")) {
		let groups = val.split(":");
		for (let i = 0, len = groups.length; i < len; i += 1) {
			if ((parseInt(groups[i], 10) < 0) || (0xFFFF < parseInt(groups[i], 10))) {
				return false;
			}
		}

		return true;
	}

	val = BigInt(val.toString());

	return ((0n <= val) && (val <= 340282366920938463463374607431768211455n));
}

function ipv6ToDecimal(ip) {
	ip = expandIPv6(ip);

	let decimalValue = 0n;
	let hextets = ip.split(':');

	for (let i = 0; i < hextets.length; i++) {
		decimalValue = (decimalValue << 16n) + BigInt(parseInt(hextets[i], 16));
	}

	return decimalValue.toString();
}

function decimalToIPv6(decimal) {
	let hexString = BigInt(decimal).toString(16).toUpperCase();
	let paddedHexString = hexString.padStart(32, '0');
	let groups = paddedHexString.match(/.{1,4}/g);

	return groups.join(':');
}

function ipv6ToRfc1924(ipv6Address) {
	return decimalToRfc1924(ipv6ToDecimal(expandIPv6(ipv6Address)));
}

function decimalToRfc1924(decimal) {
	decimal = BigInt(decimal.toString());

	let base85String = '';
	while (decimal > 0n) {
		const remainder = decimal % 85n;
		base85String = base85Chars[remainder] + base85String;
		decimal = decimal / 85n;
	}

	return `IPv6:${base85String}`;
}

function rfc1924ToDecimal(val) {
	let decimal = 0n;
	while (val.length) {
		const char = val.substring(0, 1);
		const ord = base85Chars.indexOf(char);

		decimal = (decimal * 85n) + BigInt(ord.toString());

		val = val.substring(1);
	}

	return decimal.toString();
}

function ipv6ExpandedDecimalToDecimal(expandedDecimal) {
	return ipv6ToDecimal(
		expandedDecimal.split(":")
			.map(x => x
				.toString(16)
				.toUpperCase())
			.join(":")
	);
}

// ---- handlers ----

function ipv4Handler(evt) {
	if (evt.altKey || evt.ctrlKey || evt.metaKey) {
		evt.preventDefault();
		evt.stopPropagation();
		return;
	}

	if (("change" === evt.type) && isBlocked()) {
		return;
	}

	const type = this.id.split("_")[1];
	const val = this.value.trim();

	if ("" === val) {
		["ipv4_text", "ipv4_dec", "ipv4_hex"].forEach(id => setVal(id, ""));
		cascadeBytes(evt, "");
		return;
	}

	const ret = [];

	switch (type) {
		case "text": {
			const ip_reg = /^((25[0-5]|(2[0-4]|1\d|[1-9]|)\d)\.?\b){4}$/;
			if (!ip_reg.test(val)) {
				ret[0] = val; ret[1] = "---"; ret[2] = "---";
				break;
			}
			ret[0] = val;
			ret[1] = val.split(".").reduce((ipInt, octet) => (ipInt << 8) + parseInt(octet, 10), 0) >>> 0;
			ret[2] = val.split(".").map(octet => parseInt(octet, 10).toString(16).toUpperCase().padStart(2, "0")).join(" ");
			break;
		}
		case "dec": {
			if ((val < 0) || (4294967295 < val)) {
				ret[0] = "---"; ret[1] = val; ret[2] = "---";
				break;
			}
			ret[0] = ((val >>> 24) + '.' + (val >> 16 & 255) + '.' + (val >> 8 & 255) + '.' + (val & 255));
			ret[1] = val;
			ret[2] = ret[0].split(".").map(octet => parseInt(octet, 10).toString(16).toUpperCase().padStart(2, "0")).join(" ");
			break;
		}
		case "hex": {
			const hex_reg = /^(?:0x\s*)?([0-9a-f]{2})\s*([0-9a-f]{2})\s*([0-9a-f]{2})\s*([0-9a-f]{2})\s*$/ig;
			let hexets;
			if (null === (hexets = hex_reg.exec(val))) {
				ret[0] = "---"; ret[1] = "---"; ret[2] = val;
				break;
			}
			hexets.shift(); // remove the global match
			ret[0] = hexets.map(hex => parseInt(hex, 16)).join(".");
			ret[1] = hexets.reduce((ipInt, hex) => (ipInt << 8) + parseInt(hex, 16), 0) >>> 0;
			ret[2] = hexets.join(" ");
			break;
		}
	}

	setVal("ipv4_text", ret[0]);
	setVal("ipv4_dec", ret[1]);
	setVal("ipv4_hex", ret[2]);

	cascadeBytes(evt, ret[2]);
}

function ipv6Handler(evt) {
	if (evt.altKey || evt.ctrlKey || evt.metaKey) {
		evt.preventDefault();
		evt.stopPropagation();
		return;
	}

	if (("change" === evt.type) && isBlocked()) {
		return;
	}

	const type = this.id.split("_")[1];
	let val = this.value.trim();

	if ("" === val) {
		["ipv6_text", "ipv6_dec", "ipv6_rfc1924"].forEach(id => setVal(id, ""));
		return;
	}

	const ret = [];

	switch (type) {
		case "text": {
			val = expandIPv6(val);
			const ip_reg = /^(?:[0-9a-f]{4}:){7}[0-9a-f]{4}$/i;
			if (!ip_reg.test(val)) {
				ret[0] = val; ret[1] = "---"; ret[2] = "---";
				break;
			}
			ret[0] = val;
			ret[1] = ipv6ToDecimal(val);
			ret[2] = ipv6ToRfc1924(val);
			break;
		}
		case "dec": {
			if (!isValidIPv6Dec(val)) {
				ret[0] = "---"; ret[1] = val; ret[2] = "---";
				break;
			}
			if (val.includes(":")) {
				val = ipv6ExpandedDecimalToDecimal(val);
			}
			ret[0] = compressIPv6(decimalToIPv6(val));
			ret[1] = val;
			ret[2] = decimalToRfc1924(val);
			break;
		}
		case "rfc1924": {
			if (val.toUpperCase().startsWith("IPV6:")) {
				val = val.substring("IPv6:".length);
			}
			const decimal = rfc1924ToDecimal(val);
			ret[0] = decimalToIPv6(decimal);
			ret[1] = decimal;
			ret[2] = val;
			break;
		}
	}

	setVal("ipv6_text", ret[0]);
	setVal("ipv6_dec", ret[1]);
	setVal("ipv6_rfc1924", ret[2]);
}

function ipv6TextToggle() {
	const val = el("ipv6_text").value.trim();

	// NOTE: these regular expressions are not meant to be complete or validation
	// they are simply a quick way to determine which type of compression is being passed in
	const full_regex = /^(?:[0-9a-f]{4}:){7}[0-9a-f]{4}$/i;
	const semi_full_regex = /^(?:0:|[0-9a-f]{4}:){7}(?:0|[0-9a-f]{4})$/i;
	const semi_compressed_regex = /^(?:[0-9a-f]{1,4}:){7}[0-9a-f]{1,4}$/i;

	let ret = "";

	if (val.includes("::")) {
		// semi expand the value to no leading 0s, and no ::
		ret = expandIPv6(val)
			.replaceAll("0000", 'O') // note capital Oh
			.replace(/:0+/g, ':') // so it doesn't get killed here
			.replaceAll("O", '0'); // put lone zeros back
	}
	else if (full_regex.test(val)) {
		ret = compressIPv6(val);
	}
	else if (semi_full_regex.test(val)) {
		ret = expandIPv6(val);
	}
	else if (semi_compressed_regex.test(val)) {
		ret = expandIPv6(val).replaceAll('0000', '0');
	}
	else {
		alert('Non-valid IPv6 address');
		ret = val;
	}

	block(500);
	el("ipv6_text").value = ret.toUpperCase();
}

function ipv6DecToggle() {
	const val = el("ipv6_dec").value.trim();

	if ("" === val) {
		return;
	}

	let ret = "";

	if (val.includes(":")) {
		// grouped decimal -> single integer (max 2^128 - 1)
		ret = val.split(":").reduce(function (acc, seg) {
			return (acc << 16n) + BigInt(parseInt(seg, 10) || 0);
		}, 0n).toString();
	}
	else {
		// single integer -> grouped decimal (each hextet as a decimal number)
		ret = decimalToIPv6(val)
			.split(':')
			.map(segment => parseInt(segment, 16))
			.join(':');
	}

	block(500);
	el("ipv6_dec").value = ret;
}

export function initIp() {
	const v4 = el("ipv4_wrap");
	if (v4) {
		v4.querySelectorAll("input").forEach(function (input) {
			bindWithDelay(input, "change keyup share:update", ipv4Handler, bindDelay);
		});
	}

	const v6 = el("ipv6_wrap");
	if (v6) {
		v6.querySelectorAll("input").forEach(function (input) {
			bindWithDelay(input, "change keyup share:update", ipv6Handler, bindDelay);
		});
	}

	const textToggle = el("ipv6_text_toggle");
	if (textToggle) {
		textToggle.addEventListener("click", ipv6TextToggle);
	}

	const decToggle = el("ipv6_dec_toggle");
	if (decToggle) {
		decToggle.addEventListener("click", ipv6DecToggle);
	}
}
