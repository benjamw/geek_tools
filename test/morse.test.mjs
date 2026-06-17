// Tests for the vendor/morse port (default Morse\Text settings).
// Run with: node --test
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { toMorse, fromMorse } from '../assets/morse.js';

test('toMorse encodes letters with a single-space separator', () => {
	assert.equal(toMorse('SOS'), '... --- ...');
	assert.equal(toMorse(''), '');
});

test('toMorse separates words with two spaces and is case-insensitive', () => {
	assert.equal(toMorse('A B'), '.-  -...');
	assert.equal(toMorse('ab'), toMorse('AB'));
});

test('toMorse marks unknown characters with #', () => {
	assert.equal(toMorse('~'), '#');
});

test('fromMorse round-trips uppercased text', () => {
	assert.equal(fromMorse('... --- ...'), 'SOS');
	assert.equal(fromMorse(toMorse('HELLO WORLD')), 'HELLO WORLD');
});
