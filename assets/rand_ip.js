/**
 * These both are keyed off the prefix portion of the documentation
 * addresses, with the number of subsequent portions remaining
 * to be generated randomly as the value
 */
const ipv4_doc_addresses = {
	'192.0.2': 1,
	'198.51.100': 1,
	'203.0.113': 1,
};
const ipv6_doc_addresses = {
	'2001:db8': 6,
	'3fff': 7,
};

function rand(min, max) {
	min = Math.ceil(min);
	max = Math.floor(max);
	return Math.floor(Math.random() * (max - min)) + min; // The min is inclusive and the max is exclusive
}

Array.prototype.rand = function () {
	return this[Math.floor(Math.random() * this.length)]
}

function generateRandomIP(v) {
	let prefixes = ipv4_doc_addresses;
	let elemSize = 255;
	let hex = false;
	let sep = '.';
	if (6 === parseInt(v, 10)) {
		prefixes = ipv6_doc_addresses;
		elemSize = 65535;
		hex = true;
		sep = ':';
	}

	// pick the prefix to use
	let prefix = Object.keys(prefixes).rand();

	// how many groups should be created?
	let p = rand(1, prefixes[prefix]);

	// create the groups
	let groups = [];
	for (; p > 0; p -= 1) {
		groups.push(rand(0, elemSize));
	}

	// concat the prefix with the generated groups
	let addr = prefix;
	for (let i = prefixes[prefix]; i > groups.length; i -= 1) {
		addr += sep + '0';
	}

	for (let i = 0; i < groups.length; i += 1) {
		let g = rand(0, elemSize);
		if (hex) {
			g = g.toString(16);
		}
		addr += sep + g;
	}

	// group and remove leading zeros in IPv6
	addr = addr.replace(/:00*/img, ':0').replace(/(?::0){2,}/, ':');

	return addr.toLowerCase();
}
