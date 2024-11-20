let bindDelay = 500; // ms
let blocked = false;

const BIN_REGEX = /[^01]+/img;
const OCT_REGEX = /[^0-7]+/img;
const DEC_REGEX = /[^0-9-]+/img;
const HEX_REGEX = /[^0-9a-f]+/img;

const base85Chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz!#$%&()*+-;<=>?@^_`{|}~';

if (!String.prototype.modPad) {
	String.prototype.modPad = function (num, char) {
		let ret = this;
		let pad = ret.length % num;
		if (pad) {
			if (pad < char.length) {
				char = substr(char, -pad);
			}

			while (ret.length % num) {
				ret = char + ret;
			}
		}

		return ret;
	}
}

// https://gist.github.com/EvanHahn/2587465
if (!String.prototype.caesar) {
	String.prototype.caesar = function (amount) {
		let str = this;
		amount = parseInt(amount, 10);

		if (0 === amount) {
			return str;
		}

		if (amount < 0) {
			return str.caesar(amount + 26);
		}

		let output = '';

		for (let i = 0; i < str.length; i++) {
			let c = str[i];

			if (c.match(/[a-z]/i)) {
				let code = str.charCodeAt(i);

				// Uppercase letters
				if ((code >= 65) && (code <= 90)) {
					c = String.fromCharCode(((code - 65 + amount) % 26) + 65);
				}
				// Lowercase letters
				else if ((code >= 97) && (code <= 122)) {
					c = String.fromCharCode(((code - 97 + amount) % 26) + 97);
				}
			}

			output += c;
		}

		return output;
	};
}

function slug(name) {
	return name.replace(/[\\\\/*+-]/img, "_")
}

function group(str, num, reverse) {
	if (!str) {
		return "";
	}

	if ("undefined" === typeof reverse) {
		reverse = true;
	}
	reverse = !!reverse;

	let regex = new RegExp(".{1," + num + "}", "img");
	if (reverse) {
		return str.split("").reverse().join("").match(regex).map(function (s) {
			return s.split("").reverse().join("");
		}).reverse().join(" ");
	}
	else {
		let arr = str.match(regex);
		if (arr) {
			return arr.join(" ");
		}
		else {
			return "";
		}
	}
}

function check_base64() {
	let b64_string = $("#conv_base64").val();
	$("#b64url").prop("checked", (-1 !== b64_string.indexOf("-") || -1 !== b64_string.indexOf("_")));
}

function expandIPv6(ip) {
	const parts = ip.split(':');
	const fullParts = [];

	for (const part of parts) {
		if (part === '') {
			const missingParts = 8 - parts.length + 1;
			for (let i = 0; i < missingParts; i++) {
				fullParts.push('0000');
			}
		}
		else {
			fullParts.push(part.padStart(4, '0'));
		}
	}

	return fullParts.join(':');
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
			if ((parseInt(group[i], 10) < 0) || (0xFFFF < parseInt(group[i], 10))) {
				return false;
			}
		}

		return true;
	}

	val = BigInt(val.toString());

	return ((0n <= val) && (val <= BigInt(340282366920938463463374607431768211456)));
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

$("#int_grouped").on("change click share:update", function (evt) {
	$("#int_split").prop("checked", false);
	$("#int_padded").prop("checked", false);
	if ("share:update" !== evt.type) {
		$("textarea.digits:first").trigger("share:update");
	}
});

$("#int_padded").on("change click share:update", function (evt) {
	$("#int_split").prop("checked", false);
	$("#int_grouped").prop("checked", false);
	if ("share:update" !== evt.type) {
		$("textarea.digits:first").trigger("share:update");
	}
});

$("#int_split").on("change click share:update", function (evt) {
	$("#int_padded").prop("checked", false);
	$("#int_grouped").prop("checked", false);
	if ("share:update" !== evt.type) {
		$("textarea.digits:first").trigger("share:update");
	}
});

$("textarea.digits").bindWithDelay("change keyup share:update", function (evt) {
	// if there was a modifier pressed (Alt, Ctrl, etc), don't do anything
	// the change event will capture any changes like cuts or pastes
	if (evt.altKey || evt.ctrlKey || evt.metaKey) {
		evt.preventDefault();
		evt.stopPropagation();
		return false;
	}

	if (("change" === evt.type) && blocked) {
		return;
	}

	// do the simple base conversions
	let type = $(this).attr("id").split("_")[1];
	let val = $(this).val();
	let converted = "";
	let split = $("#int_split").prop("checked");
	let padded = $("#int_padded").prop("checked");
	let grouped = $("#int_grouped").prop("checked");

	if ("" === val.replace(/[\s,.]+/g, "")) {
		$("#conv_bin").not(":focus").val("");
		$("#conv_oct").not(":focus").val("");
		$("#conv_dec").not(":focus").val("");
		$("#conv_hex").not(":focus").val("");

		// pass the emptiness along to the other areas
		if ("share:update" !== evt.type) {
			$("#conv_utf8bytes").not(":focus").val("").trigger("share:update");
			$("#conv_bytes").not(":focus").val("").trigger("share:update");
		}

		return;
	}

	if (grouped) {
		val = val.replace(/[\s,.]+/g, "");

		switch (type) {
			case "bin" :
				val = val.replace(BIN_REGEX, "");
				converted = BigInteger.parse(val, 2);
				break;
			case "oct" :
				val = val.replace(OCT_REGEX, "");
				converted = BigInteger.parse(val, 8);
				break;
			case "dec" :
				val = val.replace(DEC_REGEX, "");
				converted = BigInteger.parse(val, 10);
				// convert 2s compliment negative numbers to unsigned 256
				if ((-128 <= converted) && (converted < 0)) {
					converted = converted.add(256);
				}
				break;
			case "hex" :
				val = val.replace(HEX_REGEX, "");
				converted = BigInteger.parse(val, 16);
				break;
			default :
				// do nothing
				break;
		}

		// convert the converted back to the rest
		$("#conv_bin").not(":focus").val(group(converted.toString(2).modPad(8, "0"), 8));
		$("#conv_oct").not(":focus").val(group(converted.toString(8).modPad(3, "0"), 3));
		$("#conv_dec").not(":focus").val(group(converted.toString(10), 3));
		$("#conv_hex").not(":focus").val(group(converted.toString(16).modPad(2, "0"), 2));

		// pass the bytes along to the other areas
		if ("share:update" !== evt.type) {
			$("#conv_utf8bytes").not(":focus").val(group(converted.toString(16).modPad(2, "0"), 2)).trigger("share:update");
			$("#conv_bytes").not(":focus").val(group(converted.toString(16).modPad(2, "0"), 2)).trigger("share:update");
		}
	}
	else {
		let regex = null;
		switch (type) {
			case "bin" :
				regex = BIN_REGEX;
				break;
			case "oct" :
				regex = OCT_REGEX;
				break;
			case "dec" :
				regex = DEC_REGEX;
				break;
			case "hex" :
				regex = HEX_REGEX;
				break;
		}

		let val_array = val.replace(/^\s+|\s+$/img, "").replace(regex, " ").replace(/\s+/img, " ").split(" ");

		let outvar = {
			"bin": [],
			"oct": [],
			"dec": [],
			"hex": []
		};
		for (let i = 0, len = val_array.length; i < len; i += 1) {
			val = val_array[i];

			switch (type) {
				case "bin" :
					val = val.replace(/[^01]+/img, "");
					converted = BigInteger.parse(val, 2);
					break;
				case "oct" :
					val = val.replace(/[^0-7]+/img, "");
					converted = BigInteger.parse(val, 8);
					break;
				case "dec" :
					val = val.replace(/[^0-9-]+/img, "");
					converted = BigInteger.parse(val, 10);
					// convert 2s compliment negative numbers to unsigned 256
					if ((-128 <= converted) && (converted < 0)) {
						converted = converted.add(256);
					}
					break;
				case "hex" :
					val = val.replace(/[^0-9a-f]+/img, "");
					converted = BigInteger.parse(val, 16);
					break;
				default :
					// do nothing
					break;
			}

			outvar.bin.push(converted.toString(2));
			outvar.oct.push(converted.toString(8));
			outvar.dec.push(converted.toString(10));
			outvar.hex.push(converted.toString(16));
		}

		if (padded) {
			let padlen = 0;
			for (let type in outvar) {
				if (outvar.hasOwnProperty(type)) {
					switch (type) {
						case "bin" :
							padlen = 8;
							break;
						case "oct" :
							padlen = 3;
							break;
						case "hex" :
							padlen = 2;
							break;
					}

					for (let i = 0; i < outvar[type].length; i += 1) {
						outvar[type][i] = outvar[type][i].modPad(padlen, "0")
					}
				}
			}

			// pass the bytes along to the other areas
			// but only if padded
			if ("share:update" !== evt.type) {
				$("#conv_utf8bytes").not(":focus").val(outvar.hex.join(" ")).trigger("share:update");
				$("#conv_bytes").not(":focus").val(outvar.hex.join(" ")).trigger("share:update");
			}
		}

		$("#conv_bin").not(":focus").val(outvar.bin.join(" "));
		$("#conv_oct").not(":focus").val(outvar.oct.join(" "));
		$("#conv_dec").not(":focus").val(outvar.dec.join(" "));
		$("#conv_hex").not(":focus").val(outvar.hex.join(" "));
	}
}, bindDelay);

$("textarea.color").bindWithDelay("change keyup", function (evt) {
	// if there was a modifier pressed (Alt, Ctrl, etc), don't do anything
	// the change event will capture any changes like cuts or pastes
	if (evt.altKey || evt.ctrlKey || evt.metaKey) {
		evt.preventDefault();
		evt.stopPropagation();
		return false;
	}

	if (("change" === evt.type) && blocked) {
		return;
	}

	// do the simple base conversions
	let type = $(this).attr("id").split("_")[1];
	let val = $(this).val();
	let converted = "";

	if ("" === val.replace(/[\s,.]+/g, "")) {
		$("#color_dec").val("");
		$("#color_hex").val("");

		return;
	}

	val = val.replace(/[\s,.]+/g, "");

	let half = false;
	let full = false;
	switch (type) {
		case "dec" :
			val = val.replace(/[^0-9]+/img, "");
			converted = BigInteger.parse(val, 10);
			break;
		case "hex" :
			val = val.replace(/[^0-9a-f]+/img, "");

			if (1 === val.length) {
				half = true;
				converted = BigInteger.parse(val, 16);
			}
			else if (2 < val.length) {
				full = true;
				// TODO: split this and invert the color across the brightness value
			}
			else {
				converted = BigInteger.parse(val, 16);
			}

			break;
		default :
			// do nothing
			break;
	}

	if (half) {
		// do a simple hex inversion
		converted = 15 - converted;

		// convert the converted back to the rest
		$("#color_dec").val(converted.toString(10));
		$("#color_hex").val(converted.toString(16).modPad(2, "0"));
	}
	else if (full) {
		// convert the converted back to the rest
		$("#color_hex").val(converted.toString(16));
	}
	else {
		// do a simple color inversion
		converted = 255 - converted;

		// convert the converted back to the rest
		$("#color_dec").val(converted.toString(10));
		$("#color_hex").val(converted.toString(16).modPad(2, "0"));
	}
}, bindDelay * 2);

$("#converters").find("textarea").bindWithDelay("change keyup share:update", function (evt) {
	// if there was a modifier pressed (Alt, Ctrl, etc), don't do anything
	// the change event will capture any changes like cuts or pastes
	if (evt.altKey || evt.ctrlKey || evt.metaKey) {
		evt.preventDefault();
		evt.stopPropagation();
		return false;
	}

	if (("change" === evt.type) && blocked) {
		return;
	}

	let type = $(this).attr("id").split("_")[1];
	let val = $(this).val();

	if (type === "base64") {
		check_base64();
	}

	// do the ajax conversions
	$.ajax(window.location.href, {
		method: "POST",
		dataType: "json",
		data: {
			encodings: true,
			val: val,
			from: type
		},
		success: function (data) {
			$("#caesar").val(13);
			for (let enc in data) {
				if (data.hasOwnProperty(enc)) {
					if (false === data[enc]) {
						data[enc] = "";
					}

					$("#conv_" + enc).not(":focus").val(data[enc]);

					// pass the bytes along to the other areas
					if (("share:update" !== evt.type) && ("bytes" === enc)) {
						$("#int_padded").prop("checked", true).trigger("share:update");
						$("#conv_utf8bytes").not(":focus").val(data[enc]).trigger("share:update");
						$("#conv_hex").not(":focus").val(data[enc]).trigger("share:update");
					}
				}
			}
		}
	});
}, bindDelay);

$("#utf8").find("textarea").bindWithDelay("change keyup share:update", function (evt) {
	// if there was a modifier pressed (Alt, Ctrl, etc), don't do anything
	// the change event will capture any changes like cuts or pastes
	if (evt.altKey || evt.ctrlKey || evt.metaKey) {
		evt.preventDefault();
		evt.stopPropagation();
		return false;
	}

	if (("change" === evt.type) && blocked) {
		return;
	}

	let type = $(this).attr("id").split("_")[1].slice(4);
	let val = $(this).val();

	if ("" === val) {
		// fill the textareas with the empty string
		$("#conv_utf8char").not(":focus").val("");
		$("#conv_utf8htmldec").not(":focus").val("");
		$("#conv_utf8htmlhex").not(":focus").val("");
		$("#conv_utf8bytes").not(":focus").val("");
		$("#conv_utf8cbytes").not(":focus").val("");
		$("#conv_utf8esc").not(":focus").val("");
		$("#conv_utf8code").not(":focus").val("");

		// pass the emptiness along to the other areas
		if ("share:update" !== evt.type) {
			$("#int_padded").prop("checked", true).trigger("share:update");
			$("#conv_hex").not(":focus").val("").trigger("share:update");
			$("#conv_bytes").not(":focus").val("").trigger("share:update");
		}

		return
	}

	let funcName = "from" + type.charAt(0).toUpperCase() + type.slice(1);
	let ret = window[funcName](val);

	// do conversions
	ret.push(ret[2].map(x => x.toString(16).toUpperCase()));
	ret.push(ret[3].map(x => x.padStart(4, "0")));

	// fill the textareas with the returned values
	$("#conv_utf8char").not(":focus").val(ret[0]);
	$("#conv_utf8bytes").not(":focus").val(ret[1].toUpperCase());
	$("#conv_utf8cbytes").not(":focus").val("\\x" + ret[1].toLowerCase().split(" ").join("\\x"));
	$("#conv_utf8htmldec").not(":focus").val("&#" + ret[2].join(";&#") + ";");
	$("#conv_utf8htmlhex").not(":focus").val("&#x" + ret[3].join(";&#x") + ";");
	$("#conv_utf8esc").not(":focus").val("\\u" + ret[4].join("\\u"));
	$("#conv_utf8code").not(":focus").val("U+" + ret[4].join(" U+"));

	// pass the bytes along to the other areas
	if ("share:update" !== evt.type) {
		$("#int_padded").prop("checked", true).trigger("share:update");
		$("#conv_hex").not(":focus").val(ret[1].toUpperCase()).trigger("share:update");
		$("#conv_bytes").not(":focus").val(ret[1].toUpperCase()).trigger("share:update");
	}
}, bindDelay);

$("#caesar").on("change click", function (evt) {
	evt.stopPropagation();

	blocked = true;
	setTimeout(function () {
		blocked = false;
	}, 500);

	let str = $('#conv_raw').val();
	let amt = $(this).val();

	$("#conv_rot13").val(str.caesar(amt));
});

$("#b64url").on("change click", function (evt) {
	evt.stopPropagation();

	blocked = true;
	setTimeout(function () {
		blocked = false;
	}, 500);

	let str = $("#conv_base64").val();

	if ($("#b64url").prop("checked")) {
		str = str.replace("+", "-").replace("/", "_");
	}
	else {
		str = str.replace("-", "+").replace("_", "/");
	}

	$("#conv_base64").val(str);
});

$("#ipv4_wrap").find("input").bindWithDelay("change keyup share:update", function (evt) {
	// if there was a modifier pressed (Alt, Ctrl, etc), don't do anything
	// the change event will capture any changes like cuts or pastes
	if (evt.altKey || evt.ctrlKey || evt.metaKey) {
		evt.preventDefault();
		evt.stopPropagation();
		return false;
	}

	if (("change" === evt.type) && blocked) {
		return;
	}

	let type = $(this).attr("id").split("_")[1];
	let val = $(this).val();

	if ("" === val) {
		// fill the inputs with the empty string
		$("#ipv4_text").not(":focus").val("");
		$("#ipv4_dec").not(":focus").val("");
		$("#ipv4_hex").not(":focus").val("");

		// pass the emptiness along to the other areas
		if ("share:update" !== evt.type) {
			$("#int_padded").prop("checked", true).trigger("share:update");
			$("#conv_hex").not(":focus").val("").trigger("share:update");
			$("#conv_bytes").not(":focus").val("").trigger("share:update");
		}

		return;
	}

	let ret = [];

	switch (type) {
		case "text" :
			// ensure val is a valid IP address
			let ip_reg = /^((25[0-5]|(2[0-4]|1\d|[1-9]|)\d)\.?\b){4}$/
			if ( ! ip_reg.test(val)) {
				ret[0] = val;
				ret[1] = "---";
				ret[2] = "---";
				break;
			}

			ret[0] = val;
			ret[1] = val.split(".").reduce(function (ipInt, octet) {
					return (ipInt << 8) + parseInt(octet, 10)
				}, 0) >>> 0;
			ret[2] = val.split(".").map(function (octet) {
					return parseInt(octet, 10)
						.toString(16)
						.toUpperCase()
						.padStart(2, "0")
				}).join(" ");
			break;
		case "dec" :
			// make sure the value is between 0 and 4294967295
			if ((val < 0) || (4294967295 < val)) {
				ret[0] = "---";
				ret[1] = val;
				ret[2] = "---";
				break;
			}

			ret[0] = ((val >>> 24) + '.' + (val >> 16 & 255) + '.' + (val >> 8 & 255) + '.' + (val & 255))
			ret[1] = val;
			ret[2] = ret[0].split(".").map(function (octet) {
					return parseInt(octet, 10)
						.toString(16)
						.toUpperCase()
						.padStart(2, "0")
				}).join(" ");
			break;
		case "hex" :
			// make sure there are only 4 bytes
			let hex_reg = /^(?:0x\s*)?([0-9a-f]{2})\s*([0-9a-f]{2})\s*([0-9a-f]{2})\s*([0-9a-f]{2})\s*$/ig;
			let hexets;
			if (null === (hexets = hex_reg.exec(val))) {
				ret[0] = "---";
				ret[1] = "---";
				ret[2] = val;
				break;
			}

			hexets.shift(); // remove the global match
			ret[0] = hexets.map(function (hex) {
					return parseInt(hex, 16);
				}).join(".")
			ret[1] = hexets.reduce(function (ipInt, hex) {
					return (ipInt << 8) + parseInt(hex, 16)
				}, 0) >>> 0;
			ret[2] = hexets.join(" ");
			break;
	}

	// fill the inputs with the returned values
	$("#ipv4_text").not(":focus").val(ret[0]);
	$("#ipv4_dec").not(":focus").val(ret[1]);
	$("#ipv4_hex").not(":focus").val(ret[2]);

	// pass the bytes along to the other areas
	if ("share:update" !== evt.type) {
		$("#int_padded").prop("checked", true).trigger("share:update");
		$("#conv_hex").not(":focus").val(ret[2]).trigger("share:update");
		$("#conv_bytes").not(":focus").val(ret[2]).trigger("share:update");
	}

}, bindDelay);


$("#ipv6_wrap").find("input").bindWithDelay("change keyup share:update", function (evt) {
	// if there was a modifier pressed (Alt, Ctrl, etc), don't do anything
	// the change event will capture any changes like cuts or pastes
	if (evt.altKey || evt.ctrlKey || evt.metaKey) {
		evt.preventDefault();
		evt.stopPropagation();
		return false;
	}

	if (("change" === evt.type) && blocked) {
		return;
	}

	let type = $(this).attr("id").split("_")[1];
	let val = $(this).val();

	if ("" === val) {
		// fill the inputs with the empty string
		$("#ipv6_text").not(":focus").val("");
		$("#ipv6_dec").not(":focus").val("");
		$("#ipv6_rfc1924").not(":focus").val("");

		return;
	}

	let ret = [];

	switch (type) {
		case "text" :
			val = expandIPv6(val);
			// ensure val is a valid IP address
			let ip_reg = /^(?:[0-9a-f]{4}:){7}[0-9a-f]{4}$/i;
			if ( ! ip_reg.test(val)) {
				ret[0] = val;
				ret[1] = "---";
				ret[2] = "---";
				break;
			}

			ret[0] = val;
			ret[1] = ipv6ToDecimal(val);
			ret[2] = ipv6ToRfc1924(val);
			break;
		case "dec" :
			// make sure the value is between 0 and 340282366920938463463374607431768211456
			// or it's a decimal notation
			if ( ! isValidIPv6Dec(val)) {
				ret[0] = "---";
				ret[1] = val;
				ret[2] = "---";
				break;
			}

			if (val.includes(":")) {
				val = ipv6ExpandedDecimalToDecimal(val);
			}

			ret[0] = compressIPv6(decimalToIPv6(val));
			ret[1] = val;
			ret[2] = decimalToRfc1924(val);

			break;
		case "rfc1924" :
			if (val.toUpperCase().startsWith("IPV6:")) {
				val = val.substring("IPv6:".length);
			}

			let decimal = rfc1924ToDecimal(val);

			ret[0] = decimalToIPv6(decimal);
			ret[1] = decimal;
			ret[2] = val;

			break;
	}

	// fill the inputs with the returned values
	$("#ipv6_text").not(":focus").val(ret[0]);
	$("#ipv6_dec").not(":focus").val(ret[1]);
	$("#ipv6_rfc1924").not(":focus").val(ret[2]);

}, bindDelay);

$("#ipv6_text_toggle").on("click", function (evt) {
	let val = $("#ipv6_text").val();

	// NOTE: these regular expressions are not meant to be complete or validation
	// they are simply a quick way to determine which type of compression is being passed in
	const full_regex = /^(?:[0-9a-f]{4}:){7}[0-9a-f]{4}$/i; // 2001:0db8:0000:0000:0000:3f62:e2a0:523f
	const semi_full_regex = /^(?:0:|[0-9a-f]{4}:){7}(?:0|[0-9a-f]{4})$/i; // 2001:0db8:0:0:0:3f62:e2a0:523f (note leading 0 in second group)
	const semi_compressed_regex = /^(?:[0-9a-f]{1,4}:){7}[0-9a-f]{1,4}$/i // 2001:db8:0:0:0:3f62:e2a0:523f (note missing leading 0 in second group)

	let ret = "";

	// check for fully compressed value
	if (val.includes("::")) { // 2001:db8::3f62:e2a0:523f
		// semi expand the value to no leading 0s, and no ::
		ret = expandIPv6(val)
			.replaceAll("0000", 'O') // note capital Oh
			.replace(/:0+/g, ':') // so it doesn't get killed here
			.replaceAll("O", '0'); // put lone zeros back
	}
	else if (full_regex.test(val)) {
		// fully compress the value
		ret = compressIPv6(val);
	}
	else if (semi_full_regex.test(val)) {
		// fully expand the value by adding leading zeros to all parts
		ret = expandIPv6(val);
	}
	else if (semi_compressed_regex.test(val)) {
		// expand the value further by adding leading zeros to non-zero parts
		ret = expandIPv6(val)
			.replaceAll('0000', '0');
	}
	else {
		// do nothing, it's not a valid regex
		alert('Non-valid IPv6 address');
		ret = val;
	}

	blocked = true;
	setTimeout(function () {
		blocked = false;
	}, 500);

	$("#ipv6_text").val(ret.toUpperCase());
});

$("#ipv6_dec_toggle").on("click", function (evt) {
	let val = $("#ipv6_dec").val();

	const parts_regex = /^(?:[0-9]{4}:){7}[0-9]{4}$/;
	const full_regex = /a/;

	let ret = "";

	if (val.includes(":")) {
		// grab the ipv6 from the text version
		// largest possible value: 340282366920938463463374607431768211456 (2^128)
		ret = ipv6ToDecimal($("#ipv6_text").val())
	}
	else {
		// grab the ipv6 from the text version
		let ip = expandIPv6($("#ipv6_text").val());
		let dec_seg = ip.split(':').map(segment => parseInt(segment, 16));
		ret = dec_seg.join(':');
	}

	blocked = true;
	setTimeout(function () {
		blocked = false;
	}, 500);

	$("#ipv6_dec").val(ret);
});

$("button.hash, button.hash_raw, button.hash_form").on("click", function (evt) {
	let val = $(this).parents(".form-group").find("textarea").val();
	let algoname;

	$("#hash_value").val(val);
	if ( ! $(evt.target).is('button.hash_form')) {
		$("#hash_raw").prop('checked', $(evt.target).is('button.hash_raw'));
	}

	// do the ajax hashes
	$.ajax(window.location.href, {
		method: "POST",
		dataType: "json",
		data: {
			hashes: true,
			hash_value: val,
			hash_raw: $('#hash_raw').is(':checked'),
		},
		success: function (data) {
			for (let algo in data) {
				if (data.hasOwnProperty(algo)) {
					algoname = slug(algo);
					$("#hash_" + algoname).text(data[algo]);
				}
			}

			window.location.hash = "";
			window.location.hash = "#hashes";
		}
	});
});

$("button.copy").on("click", function (evt) {
	let $text = $(this).parents(".form-group").find("textarea");
	let $span = $text.parents(".form-group").find("span.msg");

	$text.select();
	document.execCommand("copy");

	$span.text("Copied!").fadeOut(5000, function () {
		$span.text("").show();
	});
});

$("button.clear").on("click", function (evt) {
	let $text = $(this).parents(".form-group").find("textarea");
	$text.val("").trigger("keyup");
});

$("button.send").on("click", function (evt) {
	let val = $(this).parents(".form-group").find("textarea").val();
	$("#conv_raw").val(val).trigger("keyup");
	$([
		document.documentElement,
		document.body
	]).animate({
		scrollTop: $("#conv_raw").offset().top - 40
	}, 500);
});

$("button.html").on("click", function (evt) {
	let val = $(this).parents(".form-group").find("textarea").val();
	let wndw = window.open();
	wndw.document.title = "Geek Tools HTML Output";
	wndw.document.write(val);
});

$("button.file").on("click", function (evt) {
	let val = $(this).parents(".form-group").find("textarea").val();

	$('#file').val(val)
		.parent().submit();
});

$("#rand_ipv4").val(generateRandomIP(4));
$("#rand_ipv6").val(generateRandomIP(6));
