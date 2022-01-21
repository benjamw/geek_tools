let bindDelay = 500; // ms
let blocked = false;

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
				val = val.replace(/[^01]+/img, "");
				converted = BigInteger.parse(val, 2);
				break;
			case "oct" :
				val = val.replace(/[^0-7]+/img, "");
				converted = BigInteger.parse(val, 8);
				break;
			case "dec" :
				val = val.replace(/[^0-9]+/img, "");
				converted = BigInteger.parse(val, 10);
				break;
			case "hex" :
				val = val.replace(/[^0-9a-f]+/img, "");
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
		let val_array = val.replace(/^\s+|\s+$/img, "").split(" ");

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
					val = val.replace(/[^0-9]+/img, "");
					converted = BigInteger.parse(val, 10);
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

$("button.hash").on("click", function (evt) {
	let val = $(this).parents(".form-group").find("textarea").val();
	let algoname;

	$("#hash_value").val(val);

	// do the ajax hashes
	$.ajax(window.location.href, {
		method: "POST",
		dataType: "json",
		data: {
			hashes: true,
			hash: val
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

$("button.hash_raw").on("click", function (evt) {
	let val = $(this).parents(".form-group").find("textarea").val();
	let algoname;

	$("#hash_value").val(val);
	$("#hash_raw").prop('checked', true);

	// do the ajax hashes
	$.ajax(window.location.href, {
		method: "POST",
		dataType: "json",
		data: {
			hashes: true,
			hash_value: val,
			hash_raw: true,
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

$("button.hash_form").on("click", function (evt) {
	let val = $(this).parents(".form-group").find("textarea").val();
	let algoname;

	$("#hash_value").val(val);

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
