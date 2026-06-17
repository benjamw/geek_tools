// Shared, framework-free utilities for the vanilla migration.

// Custom event name used to cascade values between fields (replaces the
// jQuery "share:update" event that was fired via .trigger / bound via .on).
export const SHARE_UPDATE = 'share:update';

// Delay invoking fn until `wait` ms have elapsed since the last call.
// General-purpose debounce.
export function debounce(fn, wait) {
	let timer = null;
	return function (...args) {
		const ctx = this;
		clearTimeout(timer);
		timer = setTimeout(function () {
			timer = null;
			fn.apply(ctx, args);
		}, wait);
	};
}

// Bind a debounced handler to one element for one or more (space separated)
// event types, sharing a single timer per element. Replaces the jQuery
// bindWithDelay plugin. The handler is called with `this` === el and receives
// the most recent event (so handlers can still read evt.type / modifier keys).
export function bindWithDelay(el, types, handler, wait) {
	let timer = null;
	const listener = function (evt) {
		clearTimeout(timer);
		timer = setTimeout(function () {
			timer = null;
			handler.call(el, evt);
		}, wait);
	};

	types.trim().split(/\s+/).forEach(function (type) {
		el.addEventListener(type, listener);
	});

	return listener;
}

// Fire a share:update on an element so its bound handler re-runs.
export function triggerShareUpdate(el) {
	el.dispatchEvent(new CustomEvent(SHARE_UPDATE));
}

// Fire a plain DOM event (e.g. "keyup", "change") on an element.
export function triggerEvent(el, type) {
	el.dispatchEvent(new Event(type, { bubbles: true }));
}

// Cross-script migration state. The classic process.js and the vanilla
// modules share the "blocked" semaphore (set briefly after a programmatic
// edit to suppress the resulting change event) via window.__geek.
const shared = (function () {
	if (typeof window === 'undefined') {
		return { blocked: false };
	}
	window.__geek = window.__geek || {};
	if (typeof window.__geek.blocked === 'undefined') {
		window.__geek.blocked = false;
	}
	return window.__geek;
})();

export function isBlocked() {
	return !!shared.blocked;
}

export function block(wait) {
	shared.blocked = true;
	setTimeout(function () {
		shared.blocked = false;
	}, wait);
}

// Left-pad `str` up to the next multiple of `num` using `char`.
// Replaces the String.prototype.modPad extension.
export function modPad(str, num, char) {
	let ret = String(str);
	const pad = ret.length % num;
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

// Caesar / ROT shift the letters of `str` by `amount`, leaving everything
// else untouched. Replaces the String.prototype.caesar extension.
// https://gist.github.com/EvanHahn/2587465
export function caesar(str, amount) {
	str = String(str);
	amount = parseInt(amount, 10);

	if (0 === amount) {
		return str;
	}

	if (amount < 0) {
		return caesar(str, amount + 26);
	}

	let output = '';

	for (let i = 0; i < str.length; i++) {
		let c = str[i];

		if (c.match(/[a-z]/i)) {
			const code = str.charCodeAt(i);

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
}
