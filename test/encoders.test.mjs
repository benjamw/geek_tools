// Parity + round-trip tests for the client-side port of ajax.php's
// do_encodings(). Vectors were confirmed against the PHP originals.
// Run with: node --test
import { test } from 'node:test';
import assert from 'node:assert/strict';
import * as enc from '../assets/encoders.js';

test('bytes (hex) encode/decode', () => {
	assert.equal(enc.toBytes('Aπ'), '41 CF 80');
	assert.equal(enc.fromBytes('41 CF 80'), 'Aπ');
	assert.equal(enc.fromBytes(''), '');
	assert.equal(enc.fromBytes('zz'), 'ERROR: invalid binary string');
	assert.equal(enc.fromBytes('414'), 'ERROR: invalid binary string');
});

test('code (A<->1) encode/decode', () => {
	assert.equal(enc.toCode('ABC'), '1 2 3');
	assert.equal(enc.fromCode('1 2 3'), 'ABC');
});

test('rot13 and reverse are self-inverse', () => {
	assert.equal(enc.rot13('Hello'), 'Uryyb');
	assert.equal(enc.rot13(enc.rot13('Hello')), 'Hello');
	assert.equal(enc.reverse('a😀b'), 'b😀a');
	assert.equal(enc.reverse(enc.reverse('a😀b')), 'a😀b');
});

test('base64 encodes and decodes both standard and url alphabets', () => {
	assert.equal(enc.toBase64('Hello, World!'), 'SGVsbG8sIFdvcmxkIQ==');
	assert.equal(enc.fromBase64('SGVsbG8sIFdvcmxkIQ=='), 'Hello, World!');
	assert.equal(enc.fromBase64('Pj4+Pw=='), '>>>?');
	assert.equal(enc.fromBase64('Pj4-Pw=='), '>>>?');
	assert.equal(enc.fromBase64('!!not base64!!'), '');
});

test('url uses PHP urlencode semantics (space -> +)', () => {
	assert.equal(enc.toUrl('a b+c'), 'a+b%2Bc');
	assert.equal(enc.fromUrl('a+b%2Bc'), 'a b+c');
	assert.equal(enc.toUrl('π'), '%CF%80');
});

test('quoted-printable encode/decode', () => {
	assert.equal(enc.quotedPrintableEncode('héllo'), 'h=C3=A9llo');
	assert.equal(enc.quotedPrintableDecode('h=C3=A9llo'), 'héllo');
});

test('base85 (ascii85) round-trips and matches a known vector', () => {
	assert.equal(enc.toBase85('hello'), 'BOu!rDZ');
	assert.equal(enc.fromBase85('BOu!rDZ'), 'hello');
	assert.equal(enc.fromBase85(enc.toBase85('Hi there!')), 'Hi there!');
});

test('z85 round-trips and matches a known vector', () => {
	assert.equal(enc.toZ85('hello'), 'xK#0@zV');
	assert.equal(enc.fromZ85('xK#0@zV'), 'hello');
	assert.equal(enc.fromZ85(enc.toZ85('Hi there!')), 'Hi there!');
});

test('uuencode round-trips', () => {
	assert.equal(enc.uudecode(enc.uuencode('Hello')), 'Hello');
	assert.equal(enc.uudecode(enc.uuencode('The quick brown fox')), 'The quick brown fox');
});

test('punycode/IDN encode/decode with the xn-- prefix', () => {
	assert.equal(enc.toPuny('bücher.com'), 'xn--bcher-kva.com');
	assert.equal(enc.fromPuny('xn--bcher-kva.com'), 'bücher.com');
	assert.equal(enc.toPuny('plain.example.com'), 'plain.example.com');
});

test('toPuny rejects malformed domains (PHP returned false)', () => {
	assert.equal(enc.toPuny('.bad..'), false);
});

test('decodeToRaw -> encodeAll dispatch round-trips through raw', () => {
	const raw = 'Hello, World!';
	const all = enc.encodeAll(raw);
	assert.equal(enc.decodeToRaw('base64', all.base64), raw);
	assert.equal(enc.decodeToRaw('bytes', all.bytes), raw);
	assert.equal(enc.decodeToRaw('url', all.url), raw);
	assert.equal(enc.decodeToRaw('quoted', all.quoted), raw);
	assert.equal(enc.decodeToRaw('raw', raw), raw);
});

test('decodeToRaw coerces a failed decode to empty string', () => {
	assert.equal(enc.decodeToRaw('puny', '.bad..'), '');
});
