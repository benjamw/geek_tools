// RFC 3492 Punycode core (single-label encode/decode). Used by encoders.js to
// reproduce PHP's intl idn_to_ascii / idn_to_utf8 (UTS46) behaviour: callers
// handle the per-label split, the UTS46 mapping (lowercase + NFC) and the
// "xn--" prefix; this module only does the bootstring transform.

const BASE = 36;
const TMIN = 1;
const TMAX = 26;
const SKEW = 38;
const DAMP = 700;
const INITIAL_BIAS = 72;
const INITIAL_N = 128;
const DELIMITER = "-";

function adapt(delta, numPoints, firstTime) {
	delta = firstTime ? Math.floor(delta / DAMP) : (delta >> 1);
	delta += Math.floor(delta / numPoints);
	let k = 0;
	while (delta > (((BASE - TMIN) * TMAX) >> 1)) {
		delta = Math.floor(delta / (BASE - TMIN));
		k += BASE;
	}
	return Math.floor(k + ((BASE - TMIN + 1) * delta) / (delta + SKEW));
}

// digit (0..35) -> ASCII code: 0..25 -> 'a'..'z', 26..35 -> '0'..'9'
function digitToBasic(d) {
	return d + 22 + (75 * (d < 26 ? 1 : 0));
}

// ASCII code -> digit (0..35); BASE (36) marks an invalid digit.
function basicToDigit(cp) {
	if (cp - 48 < 10) { return cp - 22; }
	if (cp - 65 < 26) { return cp - 65; }
	if (cp - 97 < 26) { return cp - 97; }
	return BASE;
}

export function punyEncode(input) {
	const cps = Array.from(input).map(function (c) { return c.codePointAt(0); });
	const output = [];
	for (const cp of cps) {
		if (cp < 0x80) { output.push(String.fromCharCode(cp)); }
	}
	const basicLength = output.length;
	let handled = basicLength;
	if (basicLength > 0) { output.push(DELIMITER); }

	let n = INITIAL_N;
	let delta = 0;
	let bias = INITIAL_BIAS;
	while (handled < cps.length) {
		let m = Infinity;
		for (const cp of cps) {
			if (cp >= n && cp < m) { m = cp; }
		}
		delta += (m - n) * (handled + 1);
		n = m;
		for (const cp of cps) {
			if (cp < n) { delta++; }
			if (cp === n) {
				let q = delta;
				for (let k = BASE; ; k += BASE) {
					const t = (k <= bias) ? TMIN : ((k >= bias + TMAX) ? TMAX : (k - bias));
					if (q < t) { break; }
					output.push(String.fromCharCode(digitToBasic(t + ((q - t) % (BASE - t)))));
					q = Math.floor((q - t) / (BASE - t));
				}
				output.push(String.fromCharCode(digitToBasic(q)));
				bias = adapt(delta, handled + 1, handled === basicLength);
				delta = 0;
				handled++;
			}
		}
		delta++;
		n++;
	}
	return output.join("");
}

export function punyDecode(input) {
	const output = [];
	let basic = input.lastIndexOf(DELIMITER);
	if (basic < 0) { basic = 0; }
	for (let j = 0; j < basic; j++) {
		output.push(input.charCodeAt(j));
	}

	let n = INITIAL_N;
	let i = 0;
	let bias = INITIAL_BIAS;
	let index = (basic > 0) ? (basic + 1) : 0;
	while (index < input.length) {
		const oldi = i;
		let w = 1;
		for (let k = BASE; ; k += BASE) {
			const digit = basicToDigit(input.charCodeAt(index++));
			i += digit * w;
			const t = (k <= bias) ? TMIN : ((k >= bias + TMAX) ? TMAX : (k - bias));
			if (digit < t) { break; }
			w *= (BASE - t);
		}
		const out = output.length + 1;
		bias = adapt(i - oldi, out, oldi === 0);
		n += Math.floor(i / out);
		i %= out;
		output.splice(i, 0, n);
		i++;
	}
	return output.map(function (cp) { return String.fromCodePoint(cp); }).join("");
}
