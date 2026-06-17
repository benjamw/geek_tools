// Port of the vendor/morse (benjamw/morse-php) Table + Text classes, using
// the default Morse\Text settings ajax.php relied on: case-insensitive, '#'
// for invalid characters, a two-space word separator, and '-' as the dash.
// Bits use '0' = dot, '1' = dash.

const TABLE = {
	"A": "01", "B": "1000", "C": "1010", "D": "100", "E": "0",
	"F": "0010", "G": "110", "H": "0000", "I": "00", "J": "0111",
	"K": "101", "L": "0100", "M": "11", "N": "10", "O": "111",
	"P": "0110", "Q": "1101", "R": "010", "S": "000", "T": "1",
	"U": "001", "V": "0001", "W": "011", "X": "1001", "Y": "1011",
	"Z": "1100",
	"0": "11111", "1": "01111", "2": "00111", "3": "00011", "4": "00001",
	"5": "00000", "6": "10000", "7": "11000", "8": "11100", "9": "11110",
	".": "010101", ",": "110011", "?": "001100", "'": "011110",
	"!": "101011", "/": "10010", "(": "10110", ")": "101101",
	"&": "01000", ":": "111000", ";": "101010", "=": "10001",
	"+": "01010", "-": "100001", "_": "001101", "\"": "010010",
	"$": "0001001", "@": "011010", "|": "01001"
};

const DOT = ".";
const DASH = "-";
const WORD_SEPARATOR = "  ";
const INVALID = "#";

const REVERSED = (function () {
	const r = {};
	for (const ch in TABLE) {
		r[TABLE[ch]] = ch;
	}
	return r;
})();

function getMorse(ch) {
	return TABLE[ch].replace(/0/g, DOT).replace(/1/g, DASH);
}

function morseCharacter(ch) {
	return (undefined !== TABLE[ch]) ? getMorse(ch) : INVALID;
}

function morseWord(word) {
	let chars = Array.from(word);
	if (0 === chars.length) {
		chars = [""];
	}
	return chars.map(morseCharacter).join(" ");
}

export function toMorse(text) {
	if ("" === text) {
		return "";
	}
	text = text.toUpperCase();
	return text.split(/\s+/).map(morseWord).join(WORD_SEPARATOR);
}

function translateMorseCharacter(morse) {
	const key = morse.replace(/\./g, "0").replace(/-/g, "1");
	const ch = REVERSED[key];
	return (undefined === ch) ? "" : ch;
}

function translateMorseWord(word) {
	return word.split(" ").map(translateMorseCharacter).join("");
}

export function fromMorse(morse) {
	morse = morse.split(INVALID + " ").join("");
	const words = morse.split(/ {2}|\s*[\r\n\t]\s*/);
	return words.map(translateMorseWord).join(" ");
}
