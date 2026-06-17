// Tests for the RFC 3492 Punycode bootstring transform.
// Run with: node --test
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { punyEncode, punyDecode } from '../assets/punycode.js';

// Canonical labels (the ACE form is "xn--" + these).
const VECTORS = [
	['münchen', 'mnchen-3ya'],
	['bücher', 'bcher-kva'],
];

test('punyEncode matches canonical labels', () => {
	for (const [unicode, ace] of VECTORS) {
		assert.equal(punyEncode(unicode), ace);
	}
});

test('punyDecode inverts punyEncode', () => {
	for (const label of ['münchen', 'bücher', '日本語', 'ASCIIonly']) {
		assert.equal(punyDecode(punyEncode(label)), label);
	}
});

test('punyDecode matches canonical labels', () => {
	for (const [unicode, ace] of VECTORS) {
		assert.equal(punyDecode(ace), unicode);
	}
});
