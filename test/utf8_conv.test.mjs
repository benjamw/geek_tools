// Tests for the UTF-8 / code-point converters. Each from*() returns
// [char, bytes, codePoints].
// Run with: node --test
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { fromChar, fromBytes, fromCode, fromEsc, fromHtmlhex } from '../assets/utf8_conv.js';

test('fromChar derives bytes + code points for BMP and astral chars', () => {
	assert.deepEqual(fromChar('π'), ['π', 'CF 80', [960]]);
	assert.deepEqual(fromChar('😀'), ['😀', 'F0 9F 98 80', [128512]]);
});

test('fromBytes decodes a UTF-8 byte sequence', () => {
	assert.deepEqual(fromBytes('41 42'), ['AB', '41 42', [65, 66]]);
	assert.deepEqual(fromBytes('CF 80'), ['π', 'CF 80', [960]]);
});

test('fromCode parses U+ notation', () => {
	assert.deepEqual(fromCode('U+1F600'), ['😀', 'F0 9F 98 80', [128512]]);
});

test('fromEsc and fromHtmlhex agree on the same code point', () => {
	assert.deepEqual(fromEsc('\\u3C0'), ['π', 'CF 80', [960]]);
	assert.deepEqual(fromHtmlhex('&#x3C0;'), ['π', 'CF 80', [960]]);
});
