// Tests for the shared utilities ported off jQuery / String.prototype.
// Run with: node --test
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { caesar, modPad, debounce } from '../assets/util.js';

test('caesar shifts only letters and is self-inverse at 13', () => {
	assert.equal(caesar('Hello, World!', 13), 'Uryyb, Jbeyq!');
	assert.equal(caesar(caesar('Hello', 13), 13), 'Hello');
	assert.equal(caesar('abcXYZ', 0), 'abcXYZ');
	assert.equal(caesar('abc', -1), caesar('abc', 25));
});

test('modPad left-pads up to the next multiple of num', () => {
	assert.equal(modPad('1', 8, '0'), '00000001');
	assert.equal(modPad('abcd', 4, '0'), 'abcd');
	assert.equal(modPad('101', 4, '0'), '0101');
});

test('debounce collapses rapid calls into a single trailing call', async () => {
	let calls = 0;
	let last;
	const fn = debounce(function (x) { calls++; last = x; }, 20);
	fn(1); fn(2); fn(3);
	await new Promise(function (r) { setTimeout(r, 50); });
	assert.equal(calls, 1);
	assert.equal(last, 3);
});
