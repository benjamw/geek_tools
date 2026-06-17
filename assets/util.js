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
