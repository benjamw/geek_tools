// Hashes section: the per-textarea "Hash" / "Hash Bytes" buttons and the
// "Submit" button in the #hashes form POST to ajax.php (kept as a temporary
// backend) and the returned digests are written into the #hash_* cells.
// Ported from the jQuery handler that lived in process.js.

function el(id) {
	return document.getElementById(id);
}

// Normalise an algorithm name into the id suffix used on the output cells,
// matching the slug() the server uses for id="hash_<name>".
function slug(name) {
	return name.replace(/[\\\\/*+-]/img, "_");
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

	const body = new URLSearchParams({
		hashes: "true",
		hash_value: val,
		hash_raw: hashRaw ? hashRaw.checked : false
	});

	fetch(window.location.href, {
		method: "POST",
		headers: { "Accept": "application/json" },
		body: body
	}).then(function (resp) {
		if (!resp.ok) {
			throw new Error("HTTP " + resp.status);
		}
		return resp.json();
	}).then(function (data) {
		for (const algo in data) {
			if (!Object.prototype.hasOwnProperty.call(data, algo)) {
				continue;
			}
			const cell = el("hash_" + slug(algo));
			if (cell) {
				cell.textContent = data[algo];
			}
		}

		window.location.hash = "";
		window.location.hash = "#hashes";
	}).catch(function (err) {
		console.error("Hash request failed:", err);
	});
}

// Single delegated click listener covers every hash button (one per textarea
// block plus the two in the #hashes form).
export function initHashes() {
	document.addEventListener("click", hashHandler);
}
