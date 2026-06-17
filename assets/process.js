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
