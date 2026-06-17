// Hashes section: the per-textarea "Hash" / "Hash Bytes" buttons and the
// "Submit" button in the #hashes form compute every digest client-side via
// hash-wasm and write them into the #hash_* cells. The table rows are built
// here too (a port of ajax.php's do_hashes + the index.php render loop).

import * as hashwasm from "https://cdn.jsdelivr.net/npm/hash-wasm@4/dist/index.esm.js";

// Registry of every algorithm rendered in the table. The first block mirrors
// the algorithms PHP's hash_algos() exposed that hash-wasm can reproduce
// byte-for-byte; the second block is hash-wasm's extra algorithms. Algorithms
// PHP offered but hash-wasm cannot reproduce are intentionally omitted.
const ALGOS = [
	{ name: "md4", cls: "table-danger", fn: (h, b) => h.md4(b) },
	{ name: "md5", cls: "table-danger", fn: (h, b) => h.md5(b) },
	{ name: "sha1", cls: "table-danger", fn: (h, b) => h.sha1(b) },
	{ name: "sha224", cls: "", fn: (h, b) => h.sha224(b) },
	{ name: "sha256", cls: "table-warning", fn: (h, b) => h.sha256(b) },
	{ name: "sha384", cls: "", fn: (h, b) => h.sha384(b) },
	{ name: "sha512", cls: "table-warning", fn: (h, b) => h.sha512(b) },
	{ name: "sha3-224", cls: "", fn: (h, b) => h.sha3(b, 224) },
	{ name: "sha3-256", cls: "", fn: (h, b) => h.sha3(b, 256) },
	{ name: "sha3-384", cls: "", fn: (h, b) => h.sha3(b, 384) },
	{ name: "sha3-512", cls: "table-success", fn: (h, b) => h.sha3(b, 512) },
	{ name: "ripemd160", cls: "", fn: (h, b) => h.ripemd160(b) },
	{ name: "whirlpool", cls: "", fn: (h, b) => h.whirlpool(b) },
	{ name: "adler32", cls: "", fn: (h, b) => h.adler32(b) },
	{ name: "crc32b", cls: "", fn: (h, b) => h.crc32(b) },
	{ name: "crc32c", cls: "", fn: (h, b) => h.crc32(b, 0x82f63b78) },
	{ name: "xxh32", cls: "", fn: (h, b) => h.xxhash32(b, 0) },
	{ name: "xxh64", cls: "", fn: (h, b) => h.xxhash64(b, 0, 0) },
	{ name: "xxh3", cls: "", fn: (h, b) => h.xxhash3(b, 0, 0) },
	{ name: "xxh128", cls: "", fn: (h, b) => h.xxhash128(b, 0, 0) },
	{ name: "blake2b", cls: "", fn: (h, b) => h.blake2b(b) },
	{ name: "blake2s", cls: "", fn: (h, b) => h.blake2s(b) },
	{ name: "blake3", cls: "", fn: (h, b) => h.blake3(b) },
	{ name: "crc64", cls: "", fn: (h, b) => h.crc64(b) },
	{ name: "keccak-224", cls: "", fn: (h, b) => h.keccak(b, 224) },
	{ name: "keccak-256", cls: "", fn: (h, b) => h.keccak(b, 256) },
	{ name: "keccak-384", cls: "", fn: (h, b) => h.keccak(b, 384) },
	{ name: "keccak-512", cls: "", fn: (h, b) => h.keccak(b, 512) },
	{ name: "sm3", cls: "", fn: (h, b) => h.sm3(b) }
];

function el(id) {
	return document.getElementById(id);
}

// Normalise an algorithm name into the id suffix used on the output cells,
// matching the slug() the server used for id="hash_<name>".
function slug(name) {
	return name.replace(/[\\/+*-]/gim, "_");
}

// Build the #hash_rows tbody once from the registry above.
function buildTable() {
	const tbody = el("hash_rows");
	if (!tbody || tbody.childElementCount) {
		return;
	}

	const frag = document.createDocumentFragment();
	for (const algo of ALGOS) {
		const tr = document.createElement("tr");
		if (algo.cls) {
			tr.className = algo.cls;
		}
		const th = document.createElement("th");
		th.textContent = algo.name;
		const td = document.createElement("td");
		td.id = "hash_" + slug(algo.name);
		td.className = "hash_out";
		tr.append(th, td);
		frag.append(tr);
	}
	tbody.append(frag);
}

// Parse a (space-separated) hex string into bytes. Mirrors PHP's
// hex2bin(str_replace(' ', '', ...)): invalid / odd input yields no bytes.
function hexToBytes(str) {
	const hex = str.replace(/ /g, "");
	if (hex.length % 2 !== 0 || /[^0-9a-f]/i.test(hex)) {
		return new Uint8Array(0);
	}
	const out = new Uint8Array(hex.length / 2);
	for (let i = 0; i < out.length; i++) {
		out[i] = parseInt(hex.substr(i * 2, 2), 16);
	}
	return out;
}

// Compute every digest for the given bytes and fill the table cells.
function computeHashes(bytes) {
	return Promise.all(ALGOS.map(async function (algo) {
		const cell = el("hash_" + slug(algo.name));
		if (!cell) {
			return;
		}
		try {
			cell.textContent = await algo.fn(hashwasm, bytes);
		}
		catch (err) {
			cell.textContent = "";
			console.error("Hash failed for " + algo.name + ":", err);
		}
	}));
}

function hashHandler(evt) {
	const btn = evt.target.closest("button");
	if (!btn) {
		return;
	}

	const isHashForm = btn.classList.contains("hash_form");
	const isHashRaw = btn.classList.contains("hash_raw");
	const isHash = btn.classList.contains("hash");
	if (!isHash && !isHashRaw && !isHashForm) {
		return;
	}

	const group = btn.closest(".form-group");
	const ta = group ? group.querySelector("textarea") : null;
	const val = ta ? ta.value : "";

	const hashValue = el("hash_value");
	const hashRaw = el("hash_raw");
	if (hashValue) {
		hashValue.value = val;
	}
	if (hashRaw && !isHashForm) {
		hashRaw.checked = isHashRaw;
	}

	const raw = hashRaw ? hashRaw.checked : false;
	const bytes = raw ? hexToBytes(val) : new TextEncoder().encode(val);

	computeHashes(bytes).then(function () {
		window.location.hash = "";
		window.location.hash = "#hashes";
	});
}

// Build the table, wire one delegated click listener for every hash button,
// and populate the initial (empty-input) digests as the server used to.
export function initHashes() {
	buildTable();
	document.addEventListener("click", hashHandler);
	computeHashes(new TextEncoder().encode("")).catch(function (err) {
		console.error("Initial hash failed:", err);
	});
}
