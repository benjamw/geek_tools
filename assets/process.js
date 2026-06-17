let bindDelay = 500; // ms

// migration bridges (removed once jQuery is gone in step 3f):
// 1) share the "blocked" semaphore between classic process.js and the
//    vanilla modules via window.__geek.
window.__geek = window.__geek || {};
if (typeof window.__geek.blocked === "undefined") {
	window.__geek.blocked = false;
}

// 2) route jQuery .trigger("share:update") through a native CustomEvent so
//    both jQuery handlers (bound via addEventListener) and vanilla
//    addEventListener handlers receive it.
(function ($) {
	const _trigger = $.fn.trigger;
	$.fn.trigger = function (type) {
		if ("share:update" === type) {
			return this.each(function () {
				this.dispatchEvent(new CustomEvent("share:update"));
			});
		}
		return _trigger.apply(this, arguments);
	};
})(jQuery);

const base85Chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz!#$%&()*+-;<=>?@^_`{|}~';

if (!String.prototype.modPad) {
	String.prototype.modPad = function (num, char) {
		let ret = this;
		let pad = ret.length % num;
		if (pad) {
			if (pad < char.length) {
				char = char.slice(-pad);
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

function check_base64() {
	let b64_string = $("#conv_base64").val();
	$("#b64url").prop("checked", (-1 !== b64_string.indexOf("-") || -1 !== b64_string.indexOf("_")));
}

function expandIPv6(ip) {
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

$("textarea.color").bindWithDelay("change keyup", function (evt) {
	// if there was a modifier pressed (Alt, Ctrl, etc), don't do anything
	// the change event will capture any changes like cuts or pastes
	if (evt.altKey || evt.ctrlKey || evt.metaKey) {
		evt.preventDefault();
		evt.stopPropagation();
		return false;
	}

	if (("change" === evt.type) && window.__geek.blocked) {
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

	if (("change" === evt.type) && window.__geek.blocked) {
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

	if (("change" === evt.type) && window.__geek.blocked) {
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

	window.__geek.blocked = true;
	setTimeout(function () {
		window.__geek.blocked = false;
	}, 500);

	let str = $('#conv_raw').val();
	let amt = $(this).val();

	$("#conv_rot13").val(str.caesar(amt));
});

$("#b64url").on("change click", function (evt) {
	evt.stopPropagation();

	window.__geek.blocked = true;
	setTimeout(function () {
		window.__geek.blocked = false;
	}, 500);

	let str = $("#conv_base64").val();

	if ($("#b64url").prop("checked")) {
		str = str.replace(/\+/g, "-").replace(/\//g, "_");
	}
	else {
		str = str.replace(/-/g, "+").replace(/_/g, "/");
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

	if (("change" === evt.type) && window.__geek.blocked) {
		return;
	}

	let type = $(this).attr("id").split("_")[1];
	let val = $(this).val().trim();

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

	if (("change" === evt.type) && window.__geek.blocked) {
		return;
	}

	let type = $(this).attr("id").split("_")[1];
	let val = $(this).val().trim();

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
	let val = $("#ipv6_text").val().trim();

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

	window.__geek.blocked = true;
	setTimeout(function () {
		window.__geek.blocked = false;
	}, 500);

	$("#ipv6_text").val(ret.toUpperCase());
});

$("#ipv6_dec_toggle").on("click", function (evt) {
	let val = $("#ipv6_dec").val().trim();

	if ("" === val) {
		return;
	}

	let ret = "";

	if (val.includes(":")) {
		// grouped decimal -> single integer
		// largest possible value: 340282366920938463463374607431768211455 (2^128 - 1)
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

	window.__geek.blocked = true;
	setTimeout(function () {
		window.__geek.blocked = false;
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
