// https://github.com/uxitten/polyfill/blob/master/string.polyfill.js
// https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/String/padStart
if (!String.prototype.padStart) {
	String.prototype.padStart = function padStart(targetLength, padString) {
		targetLength = targetLength >> 0; //truncate if number or convert non-number to 0;
		padString = String((typeof padString !== 'undefined' ? padString : ' '));
		if (this.length > targetLength) {
			return String(this);
		}
		else {
			targetLength = targetLength - this.length;
			if (targetLength > padString.length) {
				padString += padString.repeat(targetLength / padString.length); //append to original to ensure we are longer than needed
			}
			return padString.slice(0, targetLength) + String(this);
		}
	};
}

let CP = [];

function dh(a, p) {
	p = p || 0;
	a = (a + 0).toString(16).toUpperCase();
	return a.padStart(Math.max(p, a.length), "0");
}

function dh2(a) {
	return dh((a >> 4) & 15) + dh(a & 15)
}

function pi(a) {
	return parseInt(a, 16)
}

function out() {
	return [
		toChar(),
		toBytes(),
		CP
	];
}

function _toChar(_CP) {
	let out = '';
	for (let i = 0; i < _CP.length; i++) {
		let cp = _CP[i];
		if (cp <= 0xFFFF) {
			out += String.fromCharCode(cp);
		}
		else if (cp <= 0x10FFFF) {
			cp -= 0x10000;
			out += String.fromCharCode(0xD800 | (cp >> 10)) + String.fromCharCode(0xDC00 | (cp & 0x3FF));
		}
		else {
			console.warn("_toChar encountered an invalid UTF Code Point: " + cp);
			out += "!E: " + dh(cp) + "! ";
		}
	}

	return out;
}

function toChar() {
	return _toChar(CP);
}

function _toBytes(_CP) {
	let out = "";
	if (0 === _CP.length) return "";
	for (let i = 0; i < _CP.length; i++) {
		let cp = _CP[i];
		if (i > 0) {
			out += " ";
		}

		if (cp < 0) {
			console.warn("_toBytes encountered an invalid Code Point: " + cp);
			out += "!E: " + dh(cp) + "! ";
		}
		else if (cp <= 0x7F) { // 127 - ASCII (1 code byte)
			out += dh2(cp);
		}
		else if (cp <= 0x7FF) { // 2,047 - (2 code bytes)
			out += dh2(0xC0 | ((cp >> 6) & 0x1F)) + " " + dh2(0x80 | (cp & 0x3F));
		}
		else if (cp <= 0xFFFF) { // 65,536 - BMP (3 code bytes)
			out += dh2(0xE0 | ((cp >> 12) & 0x0F)) + " " + dh2(0x80 | ((cp >> 6) & 0x3F)) + " " + dh2(0x80 | (cp & 0x3F));
		}
		else if (cp <= 0x10FFFF) { // 1,114,111 - SP (4 code bytes)
			out += dh2(0xF0 | ((cp >> 18) & 0x07)) + " " + dh2(0x80 | ((cp >> 12) & 0x3F)) + " " + dh2(0x80 | ((cp >> 6) & 0x3F)) + " " + dh2(0x80 | (cp & 0x3F));
		}
		else {
			console.warn("_toBytes encountered an invalid Code Point: " + cp);
			out += "!E: " + dh(cp) + "! ";
		}
	}

	return out
}

function toBytes() {
	return _toBytes(CP);
}

// TODO: clean them up so they're not so obfuscated
// TODO: continue making the from*** functions, and make the to*** functions
// TODO: figure them out, and test them all and make sure they work

function hex2dec(s) {
	let out = [];

	s = s.replace(/\s+/img, " ");
	s = s.replace(/^\s+/img, "").replace(/\s+$/img, "");
	s = s.split(" ");

	for (let i = 0, len = s.length; i < len; i++) {
		out.push(pi(s[i]));
	}

	return out;
}

function _fromChar(s) {
	let _CP = [];
	let high = 0;
	for (let i = 0; i < s.length; i++) {
		let cp = s.charCodeAt(i);
		if (cp < 0 || cp > 65535) {
			console.warn("_fromChar encountered an invalid High UTF-16 surrogate: " + cp);
			b.push(NaN);
		}

		if (0 !== high) {
			if (56320 <= cp && cp <= 57343) {
				_CP.push(65536 + (high - 55296 << 10) + (cp - 56320));
				high = 0;
				continue;
			}
			console.warn("_fromChar encountered an invalid Low UTF-16 surrogate: " + cp);
			b.push(NaN);
			high = 0
		}
		55296 <= cp && cp <= 56319 ? high = cp : _CP.push(cp)
	}

	return _CP;
}

function fromChar(s) {
	CP = _fromChar(s);
	return out();
}

function _fromBytes(s) {
	let _CP = [];
	let cp = 0;
	let byte = 0;
	let n = 0;
	let bytes = [];

	s = s.replace(/^\s+/, '').replace(/\s+$/, '');

	if (0 === s.length) {
		return _CP;
	}

	s = s.replace(/\s+/g, ' ');
	bytes = s.split(' ');
	for (let i = 0; i < bytes.length; i++) {
		let b = pi(bytes[i]);
		switch (byte) {
			case 0:
				if (0 <= b && b <= 0x7F) {
					cp = b;
					_CP.push(cp);
				}
				else if (0xC0 <= b && b <= 0xDF) {
					byte = 1;
					n = b & 0x1F;
				}
				else if (0xE0 <= b && b <= 0xEF) {
					byte = 2;
					n = b & 0xF;
				}
				else if (0xF0 <= b && b <= 0xF7) {
					byte = 3;
					n = b & 0x7;
				}
				else {
					console.log("_fromBytes encountered an invalid Code Point byte: " + b);
					_CP.push(NaN);
				}
				break;
			case 1:
				if (b < 0x80 || b > 0xBF) {
					console.log("_fromBytes encountered an invalid Code Point byte: " + b);
					_CP.push(NaN);
					byte--;
					n = 0;
					break;
				}
				byte--;
				cp = ((n << 6) | (b - 0x80));
				n = 0;

				_CP.push(cp);

				break;
			case 2:
			case 3:
				if (b < 0x80 || b > 0xBF) {
					console.log("_fromBytes encountered an invalid Code Point byte: " + b);
					_CP.push(NaN);
				}
				n = (n << 6) | (b - 0x80);
				byte--;
				break;
		}
	}

	return _CP;
}

function fromBytes(s) {
	CP = _fromBytes(s);
	return out();
}

function _fromCbytes(s) {
	s = s.replace(/[\\x]+/img, " ");
	return _fromBytes(s);
}

function fromCbytes(s) {
	CP = _fromCbytes(s);
	return out();
}

function _fromHtmldec(s) {
	s = s.replace(/[&#x;]+/img, " ");
	s = s.replace(/^\s+/img, "").replace(/\s+$/img, "");
	s = s.replace(/\s+/img, " ");
	s = s.split(" ");

	return s.map(x => parseInt(x, 10));
}

function fromHtmldec(s) {
	CP = [];

	let match,
		used = 0;
	let re = /(?:&#\d+;)+/ig;
	while ((match = re.exec(s)) !== null) {
		if (used < match.index) {
			CP = CP.concat(_fromChar(s.slice(used, match.index)));
			used = match.index;
		}

		CP = CP.concat(_fromHtmldec(match[0]));
		used += match[0].length;
	}

	if (used < s.length) {
		CP = CP.concat(_fromChar(s.slice(used)));
	}

	return out();
}

function _fromHtmlhex(s) {
	s = s.replace(/[&#x;]+/img, " ");
	return hex2dec(s);
}

function fromHtmlhex(s) {
	CP = [];

	let match,
		used = 0;
	let re = /(?:&#x[0-9a-f]+;)+/ig;
	while ((match = re.exec(s)) !== null) {
		if (used < match.index) {
			CP = CP.concat(_fromChar(s.slice(used, match.index)));
			used = match.index;
		}

		CP = CP.concat(_fromHtmlhex(match[0]));
		used += match[0].length;
	}

	if (used < s.length) {
		CP = CP.concat(_fromChar(s.slice(used)));
	}

	return out();
}

function _fromEsc(s) {
	s = s.replace(/\\u/img, " ");
	return hex2dec(s);
}

function fromEsc(s) {
	CP = _fromEsc(s);
	return out();
}

function _fromCode(s) {
	s = s.replace(/U\+/img, " ");
	return hex2dec(s);
}

function fromCode(s) {
	CP = _fromCode(s);
	return out();
}
