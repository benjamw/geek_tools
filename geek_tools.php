<?php

	// find out what caused the window[funcName] to fail saying that window[funcName] was not a function

	// TODO: create IPv6 tools
		// Expand/contract (zeros and :)
		// convert to/from decimal (with :)
		// convert to/from int (no :, but padding)
		// convert to/from binary (no :, but padding)
		// Convert to/from RFC 1924 (ASCII85)
			// 0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz!#$%&()*+-;<=>?@^_`{|}~
			// this may be different from normal ASCII85 encoding
			/*
	The conversion process is a simple one of division, taking the
	remainders at each step, and dividing the quotient again, then
	reading up the page, as is done for any other base conversion.

	For example, consider the address shown above

		1080:0:0:0:8:800:200C:417A

	In decimal, considered as a 128 bit number, that is
	21932261930451111902915077091070067066.

	As we divide that successively by 85 the following remainders emerge:
	51, 34, 65, 57, 58, 0, 75, 53, 37, 4, 19, 61, 31, 63, 12, 66, 46, 70,
	68, 4.

	Thus in base85 the address is:

		4-68-70-46-66-12-63-31-61-19-4-37-53-75-0-58-57-65-34-51.

	Then, when encoded as specified above, this becomes:

		4)+k&C#VzJ4br>0wv%Yp

	This procedure is trivially reversed to produce the binary form of
	the address from textually encoded format.
	*/

	// TODO: add functionality to be able to change the rotation in the ROT-13 field
	//		but prevent the new string from being sent to the translators (unless the send button is pressed)
	//		and just do the translation in the rot-13 box from the contents of the raw box


	// TODO: Test all encoding and decoding functions to make sure they are working both ways
	// TODO: get xxencoding working
	// TODO: add a checkbox to add the <~ ... ~> from the ends of the ASCII85 encoded string, <~n=Q)*n=Q)-n=Q)Y~>
	// TODO: do the above but removing automatically if found
	// TODO: add a checkbox to add any demarcation characters for the encoded strings (UUEncode, Z85, etc)
	// TODO: remove any of the above automatically if found


	// sorry about these classes being up here, but PHP is being a whiny little bitch about not finding classes that implement ArrayAccess...
	/**
	 * Morse code table
	 *
	 * @author    Espen Hovlandsdal <espen@hovlandsdal.com>
	 * @copyright Copyright (c) Espen Hovlandsdal
	 * @license   http://www.opensource.org/licenses/mit-license MIT License
	 * @link      https://github.com/skillcoder/morse-php
	 */
	class MorseTable implements ArrayAccess
	{

		/**
		 * An array of predefined codes
		 *
		 * @var array
		 */
		private $predefinedCodes;

		/**
		 * A reverse copy of the table (morse => character)
		 *
		 * @var array
		 */
		private $reversedTable;

		/**
		 * A table of predefined morse code mappings
		 *
		 * @var array
		 */
		private $table = [
			'A' => '01',
			'B' => '1000',
			'C' => '1010',
			'D' => '100',
			'E' => '0',
			'F' => '0010',
			'G' => '110',
			'H' => '0000',
			'I' => '00',
			'J' => '0111',
			'K' => '101',
			// Ready to Receive (Over)
			'L' => '0100',
			'M' => '11',
			'N' => '10',
			'O' => '111',
			'P' => '0110',
			'Q' => '1101',
			'R' => '010',
			// Message Received
			'S' => '000',
			'T' => '1',
			'U' => '001',
			'V' => '0001',
			'W' => '011',
			'X' => '1001',
			'Y' => '1011',
			'Z' => '1100',

			'0' => '11111',
			'1' => '01111',
			'2' => '00111',
			'3' => '00011',
			'4' => '00001',
			'5' => '00000',
			'6' => '10000',
			'7' => '11000',
			'8' => '11100',
			'9' => '11110',

			// https://en.wikipedia.org/wiki/Morse_code_mnemonics
			// From: A contemporary Morse code chart: https://en.wikipedia.org/wiki/Morse_code_mnemonics#/media/File:Morse_Crib_Sheet.png
			// * - mark non standart symbol
			'.' => '010101',
			// Full stop
			',' => '110011',
			// Comma
			'?' => '001100',
			// Interrogation mark
			"'" => '011110',
			// Apostrophe
			'!' => '101011',
			//
			'/' => '10010',
			// Fraction Bar (Division Sign)
			'(' => '10110',
			//
			')' => '101101',
			// Brackets [()] (transmited before and after the word or words affected)
			'&' => '01000',
			// Interval (Wait)
			':' => '111000',
			// Colon
			';' => '101010',
			//
			'=' => '10001',
			// Break || Double dash (=)
			'+' => '01010',
			// * End of message
			'-' => '100001',
			// Hyphen || Dash
			'_' => '001101',
			// Underline (transmited before and after the word or words affected)
			'"' => '010010',
			// Quotation mark
			'$' => '0001001',
			'@' => '011010',
			'|' => '01001',
			// * Separation Sign (between whole number and fraction)
			// '' => '00010',        // * Roger
			// '' => '10101',        // * Starting signal
			// '' => '000101',       // * Closing down (End ok)
			// chr(8) => '00000000', // * Erase || Error
			// 'SOS' => '000111000', // * Distress Call || SOS
		];

		private $dash = '-';

		/**
		 * Constructs a new instance of the table
		 *
		 * @param string $dash_char
		 */
		public function __construct($dash_char = '-') {
			$this->dash = $dash_char;
			$this->predefinedCodes = array_keys($this->table);
			$this->reversedTable = array_flip($this->table);
		}

		/**
		 * Returns whether the given offset (character) exists
		 *
		 * @param mixed $offset
		 *
		 * @return boolean
		 */
		public function offsetExists($offset) {
			return isset($this->table[ $offset ]);
		}

		/**
		 * Get the morse code for the given offset (character)
		 *
		 * @param mixed $offset
		 *
		 * @return string
		 */
		public function offsetGet($offset) {
			return $this->table[ $offset ];
		}

		/**
		 * Add a morse code mapping for the given offset (character)
		 *
		 * @param mixed  $offset
		 * @param string $value
		 *
		 * @throws Exception
		 */
		public function offsetSet($offset, $value) {
			if ($this->offsetExists($offset)) {
				throw new Exception('Can\'t override predefined character');
			}
			elseif ( ! preg_match('#^[01]+$#', $value)) {
				throw new Exception('Value must be a string of zeroes and ones (0/1)');
			}
			elseif (isset($this->reversedTable[ $value ])) {
				throw new Exception('There is already a character with value ' . $value);
			}

			$this->table[ $offset ] = $value;
			$this->reversedTable[ $value ] = $offset;
		}

		/**
		 * Remove a morse code mapping for the given offset (character)
		 *
		 * @param mixed $offset
		 *
		 * @throws Exception
		 */
		public function offsetUnset($offset) {
			if (in_array($offset, $this->predefinedCodes, true)) {
				throw new Exception('Can\'t unset a predefined morse code');
			}

			unset($this->table[ $offset ]);
		}

		/**
		 * Get morse code (dit/dah) for a given character
		 *
		 * @param string $character
		 *
		 * @return string
		 */
		public function getMorse($character) {
			return strtr($this->offsetGet($character), '01', '.' . $this->dash);
		}

		/**
		 * Get character for given morse code
		 *
		 * @param string $morse
		 *
		 * @return string
		 */
		public function getCharacter($morse) {
			$key = strtr($morse, '.' . $this->dash, '01');

			return isset($this->reversedTable[ $key ]) ? $this->reversedTable[ $key ] : false;
		}
	} // end of MorseTable class

	/**
	 * Morse code text
	 *
	 * @author    Espen Hovlandsdal <espen@hovlandsdal.com>
	 * @copyright Copyright (c) Espen Hovlandsdal
	 * @license   http://www.opensource.org/licenses/mit-license MIT License
	 * @link      https://github.com/skillcoder/morse-php
	 */
	class MorseText
	{

		/**
		 * Array of morse code mappings
		 *
		 * @var array
		 */
		protected $table;

		/**
		 * Character that will be used in place when encountering invalid characters
		 *
		 * @var string
		 */
		protected $invalidCharacterReplacement = '#';

		/**
		 * Separator to put in between words
		 *
		 * @var string
		 */
		protected $wordSeparator = '  ';

		protected $lowerCaseModificator = '&';

		protected $upperCaseModificator = '+';

		private $is_case_sense = false;

		private $upperMod = true;

		/**
		 * @param ?array $table Optional morse code table to use
		 */
		public function __construct($table = null) {
			$this->table = $table ? $table : new MorseTable();
		}

		public function setCaseSense($is_case_sense) {
			$this->is_case_sense = $is_case_sense;
		}

		public function setUpperCaseMod($is_upper_mod) {
			$this->upperMod = $is_upper_mod;
		}

		/**
		 * Set the replacement that will be used when encountering invalid characters
		 *
		 * @param string $replacement
		 *
		 * @return MorseText
		 */
		public function setInvalidCharacterReplacement($replacement) {
			$this->invalidCharacterReplacement = $replacement;

			return $this;
		}

		/**
		 * Set the character/string to separate words with
		 *
		 * @param string $separator
		 *
		 * @return MorseText
		 */
		public function setWordSeparator($separator) {
			$this->wordSeparator = $separator;

			return $this;
		}

		/**
		 * Translate the given text to morse code
		 *
		 * @param string $text
		 *
		 * @return string
		 */
		public function toMorse($text) {
			if ( ! $this->is_case_sense) {
				$text = strtoupper($text);
			}

			$words = preg_split('#\s+#', $text);
			$morse = array_map([$this, 'morseWord'], $words);

			return implode($this->wordSeparator, $morse);
		}

		/**
		 * Translate the given morse code to text
		 *
		 * @param string $morse
		 *
		 * @return string
		 */
		public function fromMorse($morse) {
			$morse = str_replace($this->invalidCharacterReplacement . ' ', '', $morse);
			$words = explode($this->wordSeparator, $morse);
			$morse = array_map([$this, 'translateMorseWord'], $words);

			return implode(' ', $morse);
		}

		/**
		 * Translate lowercase with modifiers to upper
		 *
		 * @param array $characters
		 *
		 * @return array
		 */
		private function toUppercase($characters) {
			$cnt = count($characters);
			$result = [];
			$i = 0;
			while ($i < $cnt) {
				$char = $characters[ $i ];
				if ($char === $this->upperCaseModificator) {
					$i++;
					$result[] = mb_strtoupper($characters[ $i ]);
				}
				else {
					$result[] = mb_strtolower($char);
				}

				$i++;
			}

			return $result;
		}

		/**
		 * Translate uppercase with modifers to lower
		 *
		 * @param array $characters
		 *
		 * @return array
		 */
		private function toLowercase($characters) {
			$cnt = count($characters);
			$result = [];
			$i = 0;
			while ($i < $cnt) {
				$char = $characters[ $i ];
				if ($char === $this->lowerCaseModificator) {
					$i++;
					$result[] = mb_strtolower($characters[ $i ]);
				}
				else {
					$result[] = mb_strtoupper($char);
				}

				$i++;
			}

			return $result;
		}

		/**
		 * Translate a "morse word" to text
		 *
		 * @param string $morse
		 *
		 * @return string
		 */
		private function translateMorseWord($morse) {
			$morseChars = explode(' ', $morse);
			$characters = array_map([$this, 'translateMorseCharacter'], $morseChars);
			if ($this->is_case_sense) {
				if ($this->upperMod) {
					$characters = $this->toUppercase($characters);
				}
				else {
					$characters = $this->toLowercase($characters);
				}
			}

			return implode('', $characters);
		}

		/**
		 * Get the character for a given morse code
		 *
		 * @param string $morse
		 *
		 * @return string
		 */
		private function translateMorseCharacter($morse) {
			return $this->table->getCharacter($morse);
		}

		/**
		 * Return the morse code for this word
		 *
		 * @param string $word
		 *
		 * @return string
		 */
		private function morseWord($word) {
			$chars = $this->strSplit($word);
			$morse = array_map([$this, 'morseCharacter'], $chars);

			return implode(' ', $morse);
		}

		private function getMorseCaseModifierCharacter($char, $is_lower, $is_letter) {
			if ( ! $is_letter || ($this->upperMod && $is_lower) || ( ! $this->upperMod && ! $is_lower)) {
				return $this->table->getMorse($char);
			}

			$modifer = $this->lowerCaseModificator;
			if ($this->upperMod) {
				$modifer = $this->upperCaseModificator;
			}

			return $this->table->getMorse($modifer) . ' ' . $this->table->getMorse($char);
		}

		/**
		 * Return the morse code for this character
		 *
		 * @param string $char
		 *
		 * @return string
		 */
		private function morseCharacter($char) {
			if ($this->is_case_sense) {
				$is_lower = false;
				$is_letter = false;
				if (preg_match('/^[a-zа-яё]$/', $char)) {
					$char = strtoupper($char);
					$is_lower = true;
					$is_letter = true;
				}
				elseif (preg_match('/^[A-ZА-ЯЁ]$/', $char)) {
					$is_letter = true;
				}

				if ( ! isset($this->table[ $char ])) {
					return $this->invalidCharacterReplacement;
				}

				return $this->getMorseCaseModifierCharacter($char, $is_lower, $is_letter);
			}
			else {
				if ( ! isset($this->table[ $char ])) {
					return $this->invalidCharacterReplacement;
				}

				return $this->table->getMorse($char);
			}
		}

		/**
		 * Split a string into individual characters
		 *
		 * @param string $str
		 * @param int $l
		 *
		 * @return array
		 */
		private function strSplit($str, $l = 0) {
			return preg_split(
				'#(.{' . $l . '})#us',
				$str,
				-1,
				PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE
			) ?: [''];
		}
	} // end of MorseText class

	$algos = hash_algos( );
	$skip_algos = [];
	$hashes = do_hashes($skip_algos); // see bottom of file

	$version = phpversion();

	if ( ! empty($_POST['encodings'])) {
		$encodings = do_encodings( ); // see bottom of file
		$return = json_encode($encodings);
		while (json_last_error()) {
			// remove malformed UTF-8 from the result set
			// usually caused by reverse or rot13
			foreach ($encodings as $key => $value) {
				json_encode($value);
				if (json_last_error()) {
					$encodings[$key] = '-- '.json_last_error_msg()." -- Adjusted data follows:\n" . mb_convert_encoding($value, 'UTF-8', 'UTF-8');;
				}
			}
			$return = json_encode($encodings);
		}
		echo $return;
		exit;
	}

	if ( ! empty($_POST['hashes'])) {
		$return = json_encode($hashes);
		echo $return;
		exit;
	}

	if ( ! empty($_POST['file'])) {
		$content = base64_decode($_POST['file']);

		$finfo = new finfo(FILEINFO_MIME);
		$type = $finfo->buffer($content, FILEINFO_MIME_TYPE);

		define('APACHE_MIME_TYPES_URL', 'http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types');

		function generateUpToDateMimeArray($url = APACHE_MIME_TYPES_URL) {
			$s = [];
			foreach (@explode("\n", @file_get_contents($url)) as $x) {
				if (
					isset($x[0]) && $x[0] !== '#' 
					&& preg_match_all('#([^\s]+)#', $x, $out) 
					&& isset($out[1])
					&& ($c = count($out[1])) > 1
				) {
					for ($i = 1; $i < $c; $i++) {
						$s[$out[1][$i]] = $out[1][0];
					}
				}
			}

			return $s;
		}

		$mimes = generateUpToDateMimeArray();
		$exts = array_flip($mimes);

		header('Content-Disposition: attachment; filename="geek_file.'.$exts[$type].'"');
		header('Content-Type: '.$type);
		echo $content;
		exit;
	}

	$buttons = <<< EOHTML
		<button type="button" class="copy btn btn-sm btn-info">Copy</button>
		<button type="button" class="hash btn btn-sm btn-success">Hash</button>
		<span class="msg"></span>
		<span class="float-right">
			<button type="button" class="send btn btn-sm btn-secondary">Send to 'Raw'</button>
			<button type="button" class="clear btn btn-sm btn-secondary">Clear</button>
		</span>
EOHTML;

?>
<!doctype html>
<html lang="en">
<head>

	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta http-equiv="x-ua-compatible" content="ie=edge">

	<meta name="author" content="Benjam Welker">
	<meta name="description" content="A page to convert strings to various forms, numbers between bases, and UTF-8 encodings.">

	<title>Geek Tools</title>

	<!-- Bootstrap: Latest compiled and minified CSS -->
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css" integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2" crossorigin="anonymous">
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootswatch/4.5.2/darkly/bootstrap.min.css" integrity="sha384-nNK9n28pDUDDgIiIqZ/MiyO3F4/9vsMtReZK39klb/MtkZI3/LtjSjlmyVPS3KdN" crossorigin="anonymous">

	<style type="text/css">
		abbr {
			text-decoration: none;
		}

		.container-fluid textarea {
			height: 150px;
			padding: 0 4px;
			font-family: monospace;
		}

		#conv_utf8char {
			font-family: sans-serif;
		}

		.copy,
		.hash,
		.hash_raw,
		.html,
		.send,
		.file {
			margin-right: 1ex;
		}

		.hash_out,
		.example {
			font-family: monospace;
			font-size: larger;
		}

		.example {
			font-size: 100%;
		}

		#ip {
			position: absolute;
			top: 10px;
			right: 10px;
			color: lightgray;
			z-index: 2;
			text-align: right;
		}

		#ip span {
			font-family: monospace;
			font-size: larger;
			margin-left: 1ex;
		}

		.input-xs {
			height: 22px;
			font-size: 12px !important;
			padding-left: 5px !important;
			width: 50%;
		}

		.hidden {
			display: none;
		}

		@media only all {
			#divInput {
				line-height: 30px;
			}

			#formula {
				border-left: 2px solid #5441f1;
				margin: 5px;
				font-size: 14px;
				padding: 5px 0 5px 10px;
			}

			#formula ul {
				padding-left: 20px;
				margin: 0;
			}

			#ruler {
				border: 1px solid gray;
			}
		}

		@media only screen and (max-width: 500px) {
			#divInput {
				line-height: normal;
			}

			#formula {
				width: 100%
			}
		}
	</style>

	<!-- jQuery -->
	<script src="https://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>

	<!-- BigInteger -->
	<script>
		/*
		 JavaScript BigInteger library
		 http://silentmatt.com/biginteger/
		 https://github.com/silentmatt/javascript-biginteger/blob/master/lib/impl/native.js f1d33f9 on Sep 2, 2019
		*/
		!function(a){"use strict";function e(a,b){if(b!==g){if(a instanceof e)return a;return"undefined"==typeof a?h:e.parse(a)}this.value=BigInt(a)}function f(a){if(1===a.length)return"00"+a;if(2===a.length)return"0"+a;if(3===a.length)return a;throw new Error("Unexpected length in pad3("+a+")")}var g={};e._construct=function(a,b){return new e(BigInt(a)*(0>b?-1n:1n),g)};e.base=10000000,e.base_log10=7;var h=new e(0n,g);e.ZERO=h;var i=new e(1n,g);e.ONE=i;var j=new e(-1n,g);e.M_ONE=j,e._0=h,e._1=i,e.small=[h,i,new e(2n,g),new e(3n,g),new e(4n,g),new e(5n,g),new e(6n,g),new e(7n,g),new e(8n,g),new e(9n,g),new e(10n,g),new e(11n,g),new e(12n,g),new e(13n,g),new e(14n,g),new e(15n,g),new e(16n,g),new e(17n,g),new e(18n,g),new e(19n,g),new e(20n,g),new e(21n,g),new e(22n,g),new e(23n,g),new e(24n,g),new e(25n,g),new e(26n,g),new e(27n,g),new e(28n,g),new e(29n,g),new e(30n,g),new e(31n,g),new e(32n,g),new e(33n,g),new e(34n,g),new e(35n,g),new e(36n,g)],e.digits=["0","1","2","3","4","5","6","7","8","9","A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z"],e.prototype.toString=function(a){if(a=+a||10,2>a||36<a)throw new Error("illegal radix "+a+".");return this.value.toString(a).toUpperCase()},e.radixRegex=[/^$/,/^$/,/^[01]*$/,/^[012]*$/,/^[0-3]*$/,/^[0-4]*$/,/^[0-5]*$/,/^[0-6]*$/,/^[0-7]*$/,/^[0-8]*$/,/^[0-9]*$/,/^[0-9aA]*$/,/^[0-9abAB]*$/,/^[0-9abcABC]*$/,/^[0-9a-dA-D]*$/,/^[0-9a-eA-E]*$/,/^[0-9a-fA-F]*$/,/^[0-9a-gA-G]*$/,/^[0-9a-hA-H]*$/,/^[0-9a-iA-I]*$/,/^[0-9a-jA-J]*$/,/^[0-9a-kA-K]*$/,/^[0-9a-lA-L]*$/,/^[0-9a-mA-M]*$/,/^[0-9a-nA-N]*$/,/^[0-9a-oA-O]*$/,/^[0-9a-pA-P]*$/,/^[0-9a-qA-Q]*$/,/^[0-9a-rA-R]*$/,/^[0-9a-sA-S]*$/,/^[0-9a-tA-T]*$/,/^[0-9a-uA-U]*$/,/^[0-9a-vA-V]*$/,/^[0-9a-wA-W]*$/,/^[0-9a-xA-X]*$/,/^[0-9a-yA-Y]*$/,/^[0-9a-zA-Z]*$/],e.parse=function(a,b){a=a.toString(),("undefined"==typeof b||10==+b)&&(a=function(a){return a=a.replace(/\s*[*xX]\s*10\s*(\^|\*\*)\s*/,"e"),a.replace(/^([+\-])?(\d+)\.?(\d*)[eE]([+\-]?\d+)$/,function(a,b,d,e,f){f=+f;var g=0>f,h=d.length+f;a=(g?d:e).length,f=(f=Math.abs(f))>=a?f-a+g:0;var j=Array(f+1).join("0"),k=d+e;return(b||"")+(g?k=j+k:k+=j).substr(0,h+=g?j.length:0)+(h<k.length?"."+k.substr(h):"")})}(a));var c="undefined"==typeof b?"0[xcb]":16==b?"0x":8==b?"0c":2==b?"0b":"";var j=new RegExp("^([+\\-]?)("+c+")?([0-9a-z]*)(?:\\.\\d*)?$","i").exec(a);if(j){var k=j[1]||"+",l=j[2]||"",m=j[3]||"";if("undefined"==typeof b)b="0x"===l||"0X"===l?16:"0c"===l||"0C"===l?8:"0b"===l||"0B"===l?2:10;else if(2>b||36<b)throw new Error("Illegal radix "+b+".");if(b=+b,!e.radixRegex[b].test(m))throw new Error("Bad digit for radix "+b);if(m=m.replace(/^0+/,""),0===m.length)return h;var n="-"==k?-1n:1n;if(10==b)return new e(n*BigInt(m),g);if(2===b)return new e(n*BigInt("0b"+m),g);if(8===b)return m=m.replace(/\d/g,function(a){return f((+a).toString(2))}),new e(n*BigInt("0b"+m),g);if(16===b)return new e(n*BigInt("0x"+m),g);var o=0n;b=e.small[b].value;var p=e.small;b=BigInt(b);for(var q=0;q<m.length;q++)o=o*b+p[parseInt(m[q],36)].value;return new e(n*o,g)}throw new Error("Invalid BigInteger format: "+a)},e.prototype.add=function(a){return this.isZero()?e(a):(a=e(a),a.isZero()?this:new e(this.value+a.value,g))},e.prototype.negate=function(){return new e(-this.value,g)},e.prototype.abs=function(){return 0n>this.value?this.negate():this},e.prototype.subtract=function(a){return this.isZero()?e(a).negate():(a=e(a),a.isZero()?this:new e(this.value-a.value,g))},e.prototype.next=function(){return this.add(i)},e.prototype.prev=function(){return this.subtract(i)},e.prototype.compareAbs=function(c){if(this===c)return 0;if(!(c instanceof e)){if(!isFinite(c))return isNaN(c)?c:-1;c=e(c)}var d=0>this.value?-this.value:this.value,a=0>c.value?-c.value:c.value;return d<a?-1:d>a?1:0},e.prototype.compare=function(c){if(this===c)return 0;c=e(c);var d=this.value,a=c.value;return d<a?-1:d>a?1:0},e.prototype.isUnit=function(){return 1n===this.value||-1n===this.value},e.prototype.multiply=function(a){return this.isZero()?h:(a=e(a),0===a.isZero()?h:new e(this.value*a.value,g))},e.prototype.multiplySingleDigit=function(a){return 0===a||this.isZero()?h:1===a?this:new e(this.value*BigInt(a),g)},e.prototype.square=function(){return this.isZero()?h:this.isUnit()?i:new e(this.value**2n,g)},e.prototype.quotient=function(a){return a=e(a),new e(this.value/a.value,g)},e.prototype.divide=e.prototype.quotient,e.prototype.remainder=function(a){return a=e(a),new e(this.value%a.value,g)},e.prototype.divRem=function(a){if(a=e(a),a.isZero())throw new Error("Divide by zero");if(this.isZero())return[h,h];switch(this.compareAbs(a)){case 0:return[this.sign()===a.sign()?i:j,h];case-1:return[h,this];}return[new e(this.value/a.value,g),new e(this.value%a.value,g)]},e.prototype.divRemSmall=function(a){if(a=+a,0===a)throw new Error("Divide by zero");var b=0>a?-1:1,c=this.sign()*b;if(a=Math.abs(a),1>a||10000000<=a)throw new Error("Argument out of range");if(this.isZero())return[h,h];if(1===a||-1===a)return[1===c?this.abs():-1===c?this.negate():h,h];var d=BigInt(c),f=BigInt(a),i=this.abs().value;return[new e(d*(i/f),g),new e(d*(i%f),g)]},e.prototype.isEven=function(){return 0n===(1n&this.value)},e.prototype.isOdd=function(){return 1n===(1n&this.value)},e.prototype.sign=function(){return 0n>this.value?-1:0n<this.value?1:0},e.prototype.isPositive=function(){return 0n<this.value},e.prototype.isNegative=function(){return 0n>this.value},e.prototype.isZero=function(){return 0n===this.value},e.prototype.exp10=function(a){if(a=+a,0===a)return this;if(Math.abs(a)>+l)throw new Error("exponent too large in BigInteger.exp10");if(this.isZero())return h;if(0<a)return new e(this.value*10n**BigInt(a),g);var b=new e(this.value/10n**BigInt(-a),g);return b.isZero()?h:b},e.prototype.pow=function(a){if(this.isUnit())return 0n<this.value?this:e(a).isOdd()?this:this.negate();if(a=e(a),a.isZero())return i;if(0n>a.value)if(this.isZero())throw new Error("Divide by zero");else return h;if(this.isZero())return h;if(a.isUnit())return this;if(0<a.compareAbs(l))throw new Error("exponent too large in BigInteger.pow");return new e(this.value**a.value,g)},e.prototype.modPow=function(a,b){var c=1n,d=this.value;if(a=e(a).value,b=e(b).value,0n===b&&0n<a)throw new Error("Divide by zero");for(;0n<a;)1n&a&&(c=c*d%b),a>>=1n,0n<a&&(d=d*d%b);return new e(c,g)},e.prototype.log=function(){switch(this.sign()){case 0:return-Infinity;case-1:return NaN;default:}var a=this.abs().value.toString(),b=a.length;if(30>b)return Math.log(this.valueOf());var c=a.slice(0,30);return Math.log(new e(c,g).valueOf())+(b-30)*Math.log(10)},e.prototype.valueOf=function(){return+this.toString()},e.prototype.toJSValue=function(){return+this.toString()};var l=e(2147483647);e.MAX_EXP=l,function(){function a(b){return function(c){return b.call(e(c))}}function b(c){return function(d,a){return c.call(e(d),e(a))}}function c(d){return function(f,a,b){return d.call(e(f),e(a),e(b))}}(function(){var d,f,g=["toJSValue","isEven","isOdd","sign","isZero","isNegative","abs","isUnit","square","negate","isPositive","toString","next","prev","log"],h=["compare","remainder","divRem","subtract","add","quotient","divide","multiply","pow","compareAbs"],j=["modPow"];for(d=0;d<g.length;d++)f=g[d],e[f]=a(e.prototype[f]);for(d=0;d<h.length;d++)f=h[d],e[f]=b(e.prototype[f]);for(d=0;d<j.length;d++)f=j[d],e[f]=c(e.prototype[f]);e.exp10=function(a,b){return e(a).exp10(b)}})()}(),a.BigInteger=e}("undefined"==typeof exports?this:exports);
	</script>

	<!-- bindWithDelay -->
	<script>
		// https://github.com/bgrins/bindWithDelay/blob/master/bindWithDelay.js
		!function(a){a.fn.bindWithDelay=function(b,c,d,e,f){return a.isFunction(c)&&(f=e,e=d,d=c,c=void 0),d.guid=d.guid||a.guid&&a.guid++,this.each(function(){function h(){var b=a.extend(!0,{},arguments[0]),c=this,h=function(){g=null,d.apply(c,[b])};f||(clearTimeout(g),g=null),g||(g=setTimeout(h,e))}var g=null;h.guid=d.guid,a(this).bind(b,c,h)})}}(jQuery);
	</script>

	<!-- UTF8 Converter -->
	<!-- original from http://www.endmemo.com/unicode/unicodeconverter.php : http://www.endmemo.com/unicode/script/convertuni.js -->
	<script>
		// https://github.com/uxitten/polyfill/blob/master/string.polyfill.js
		// https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/String/padStart
		if (!String.prototype.padStart) {
			String.prototype.padStart = function padStart(targetLength,padString) {
				targetLength = targetLength>>0; //truncate if number or convert non-number to 0;
				padString = String((typeof padString !== 'undefined' ? padString : ' '));
				if (this.length > targetLength) {
					return String(this);
				}
				else {
					targetLength = targetLength-this.length;
					if (targetLength > padString.length) {
						padString += padString.repeat(targetLength/padString.length); //append to original to ensure we are longer than needed
					}
					return padString.slice(0,targetLength) + String(this);
				}
			};
		}

		let CP = [];

		function dh(a,p) {
			p = p || 0;
			a = (a + 0).toString(16).toUpperCase();
			return a.padStart(Math.max(p,a.length),"0");
		}

		function dh2(a) {
			return dh((a >> 4) & 15) + dh(a & 15)
		}

		function pi(a) {
			return parseInt(a, 16)
		}

		function out() {
			return [toChar(), toBytes(), CP];
		}

		function _toChar(_CP) {
			let out = '';
			for (let i = 0; i < _CP.length; i++) {
				let cp = _CP[i];
				if (cp <= 0xFFFF) {
					out += String.fromCharCode(cp);
				}
				else if (cp <= 0x10FFFF) {
					cp -= 0x10000;
					out += String.fromCharCode(0xD800 | (cp >> 10)) + String.fromCharCode(0xDC00 | (cp & 0x3FF));
				}
				else {
					console.warn("_toChar encountered an invalid UTF Code Point: "+cp);
					out += "!E: " + dh(cp) + "! ";
				}
			}

			return out;
		}

		function toChar() {
			return _toChar(CP);
		}

		function _toBytes(_CP) {
			let out = "";
			if (0 === _CP.length) return "";
			for (let i = 0; i < _CP.length; i++) {
				let cp = _CP[i];
				if (i > 0) {
					out += " ";
				}

				if (cp < 0) {
					console.warn("_toBytes encountered an invalid Code Point: " + cp);
					out += "!E: " + dh(cp) + "! ";
				}
				else if (cp <= 0x7F) { // 127 - ASCII (1 code byte)
					out += dh2(cp);
				}
				else if (cp <= 0x7FF) { // 2,047 - (2 code bytes)
					out += dh2(0xC0 | ((cp >>  6) & 0x1F)) + " " + dh2(0x80 | (cp         & 0x3F));
				}
				else if (cp <= 0xFFFF) { // 65,536 - BMP (3 code bytes)
					out += dh2(0xE0 | ((cp >> 12) & 0x0F)) + " " + dh2(0x80 | ((cp >>  6) & 0x3F)) + " " + dh2(0x80 | (cp        & 0x3F));
				}
				else if (cp <= 0x10FFFF) { // 1,114,111 - SP (4 code bytes)
					out += dh2(0xF0 | ((cp >> 18) & 0x07)) + " " + dh2(0x80 | ((cp >> 12) & 0x3F)) + " " + dh2(0x80 | ((cp >> 6) & 0x3F)) + " " + dh2(0x80 | (cp & 0x3F));
				}
				else {
					console.warn("_toBytes encountered an invalid Code Point: " + cp);
					out += "!E: " + dh(cp) + "! ";
				}
			}

			return out
		}

		function toBytes() {
			return _toBytes(CP);
		}

		// TODO: clean them up so they're not so obfuscated
		// TODO: continue making the from*** functions, and make the to*** functions
		// TODO: figure them out, and test them all and make sure they work

		function hex2dec(s) {
			let out = [];

			s = s.replace(/\s+/img, " ");
			s = s.replace(/^\s+/img, "").replace(/\s+$/img, "");
			s = s.split(" ");

			for (let i = 0, len = s.length; i < len; i++) {
				out.push(pi(s[i]));
			}

			return out;
		}

		function _fromChar(s) {
			let _CP = [];
			let high = 0;
			for (let i = 0; i < s.length; i++) {
				let cp = s.charCodeAt(i);
				if (cp < 0 || cp > 65535) {
					console.warn("_fromChar encountered an invalid High UTF-16 surrogate: " + cp);
					b.push(NaN);
				}

				if (0 !== high) {
					if (56320 <= cp && cp <= 57343) {
						_CP.push(65536 + (high - 55296 << 10) + (cp - 56320));
						high = 0;
						continue;
					}
					console.warn("_fromChar encountered an invalid Low UTF-16 surrogate: " + cp);
					b.push(NaN);
					high = 0
				}
				55296 <= cp && cp <= 56319 ? high = cp : _CP.push(cp)
			}

			return _CP;
		}

		function fromChar(s) {
			CP = _fromChar(s);
			return out();
		}

		function _fromBytes(s) {
			let _CP = [];
			let cp = 0;
			let byte = 0;
			let n = 0;
			let bytes = [];

			s = s.replace(/^\s+/, '').replace(/\s+$/, '');

			if (0 === s.length) {
				return _CP;
			}

			s = s.replace(/\s+/g, ' ');
			bytes = s.split(' ');
			for (let i = 0; i < bytes.length; i++) {
				let b = pi(bytes[i]);
				switch (byte) {
					case 0:
						if (0 <= b && b <= 0x7F) {
							cp = b;
							_CP.push(cp);
						}
						else if (0xC0 <= b && b <= 0xDF) {
							byte = 1;
							n = b & 0x1F;
						}
						else if (0xE0 <= b && b <= 0xEF) {
							byte = 2;
							n = b & 0xF;
						}
						else if (0xF0 <= b && b <= 0xF7) {
							byte = 3;
							n = b & 0x7;
						}
						else {
							console.log("_fromBytes encountered an invalid Code Point byte: " + b);
							_CP.push(NaN);
						}
						break;
					case 1:
						if (b < 0x80 || b > 0xBF) {
							console.log("_fromBytes encountered an invalid Code Point byte: " + b);
							_CP.push(NaN);
							byte--;
							n = 0;
							break;
						}
						byte--;
						cp = ((n << 6) | (b - 0x80));
						n = 0;

						_CP.push(cp);

						break;
					case 2:
					case 3:
						if (b < 0x80 || b > 0xBF) {
							console.log("_fromBytes encountered an invalid Code Point byte: " + b);
							_CP.push(NaN);
						}
						n = (n << 6) | (b - 0x80);
						byte--;
						break;
				}
			}

			return _CP;
		}

		function fromBytes(s) {
			CP = _fromBytes(s);
			return out();
		}

		function _fromCbytes(s) {
			s = s.replace(/[\\x]+/img, " ");
			return _fromBytes(s);
		}

		function fromCbytes(s) {
			CP = _fromCbytes(s);
			return out();
		}

		function _fromHtmldec(s) {
			s = s.replace(/[&#x;]+/img, " ");
			s = s.replace(/^\s+/img, "").replace(/\s+$/img, "");
			s = s.replace(/\s+/img, " ");
			s = s.split(" ");

			return s.map(x => parseInt(x, 10));
		}

		function fromHtmldec(s) {
			CP = [];

			let match,
				used = 0;
			let re = /(?:&#\d+;)+/ig;
			while ((match = re.exec(s)) !== null) {
				if (used < match.index) {
					CP = CP.concat(_fromChar(s.slice(used, match.index)));
					used = match.index;
				}

				CP = CP.concat(_fromHtmldec(match[0]));
				used += match[0].length;
			}

			if (used < s.length) {
				CP = CP.concat(_fromChar(s.slice(used)));
			}

			return out();
		}

		function _fromHtmlhex(s) {
			s = s.replace(/[&#x;]+/img, " ");
			return hex2dec(s);
		}

		function fromHtmlhex(s) {
			CP = [];

			let match,
				used = 0;
			let re = /(?:&#x[0-9a-f]+;)+/ig;
			while ((match = re.exec(s)) !== null) {
				if (used < match.index) {
					CP = CP.concat(_fromChar(s.slice(used, match.index)));
					used = match.index;
				}

				CP = CP.concat(_fromHtmlhex(match[0]));
				used += match[0].length;
			}

			if (used < s.length) {
				CP = CP.concat(_fromChar(s.slice(used)));
			}

			return out();
		}

		function _fromEsc(s) {
			s = s.replace(/\\u/img, " ");
			return hex2dec(s);
		}

		function fromEsc(s) {
			CP = _fromEsc(s);
			return out();
		}

		function _fromCode(s) {
			s = s.replace(/U\+/img, " ");
			return hex2dec(s);
		}

		function fromCode(s) {
			CP = _fromCode(s);
			return out();
		}
	</script>

	<script>
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

	</script>
	<script>
		// length conversion
		let Ruler = class {
			constructor() {
				this.ofractions = document.getElementById('fractions');
				this.oinch = document.getElementById('inch');
				this.ofinch = document.getElementById('finch');
				this.omm = document.getElementById('mm');
				this.ocm = document.getElementById('cm');
				this.omsg = document.getElementById('msg');
				this.oformula = document.getElementById('formula');

				this.dpi_x = 102.4; // for my monitor... YMMV

				this.ppcm = this.dpi_x / 2.54;
				this.c = document.getElementById("ruler");
				this.cxt = this.c.getContext("2d");
				this.w = this.c.clientWidth;
				this.begin_x = 20;
				this.BL_cm = 0.5;
				this.BL_inch = 129.5;
				this.begin_cm = 0;
			}

			set_fractional_inch(inch) {
				let fractions = this.ofractions.value;
				let fra_in = Math.floor(inch);

				let numerator = Math.round((inch - fra_in) / (1 / fractions));
				let denominator = fractions;

				let sTemp;

				while ((0 === numerator % 2) && (0 === denominator % 2)) {
					numerator /= 2;
					denominator /= 2;
				}

				if (numerator == 1 && denominator == 1) {
					fra_in += 1;
					numerator = 0;
				}

				if (fra_in > 0) {
					sTemp = fra_in;
				}
				else {
					sTemp = '';
				}

				if (numerator > 0) {
					if (fra_in > 0) {
						sTemp += ' ';
					}

					sTemp += numerator + '/' + denominator
				}

				this.ofinch.value = sTemp;
			}

			set_inch(inch) {
				if (isNaN(inch)) {
					this.oinch.value = '';
					this.omm.value = '';
					this.ocm.value = '';
				}
				else {
					this.oinch.value = inch;
					this.omm.value = Math.round(inch * 25.4 * 10) / 10;
					this.ocm.value = Math.round(inch * 2.54 * 100) / 100;
				}
			}

			frac_to_dec(frac) {
				let f, numerator, denominator, ir;
				let fa, finch2, fa2;

				frac = frac.trim();
				if (frac.indexOf(' ') < 0) {
					f = frac.split('/');

					if (f.length == 1) {
						return parseInt(frac);
					}
					else if (f.length == 2) {
						numerator = f[0];
						denominator = f[1];
						if ((numerator != '') && (denominator != '') && parseInt(denominator) > 0 && parseInt(numerator) > 0) {
							ir = Math.round((numerator / denominator) * 10000000) / 10000000;
							return ir;
						}
					}
				}
				else {
					fa = frac.split(" ");
					ir = parseInt(fa[0]);
					finch2 = fa[1];
					fa2 = finch2.split('/');

					if (fa2.length == 2) {
						numerator = fa2[0];
						denominator = fa2[1];
						if ((numerator != '') && (denominator != '') && parseInt(denominator) > 0 && parseInt(numerator) > 0) {
							ir = Math.round((ir + numerator / denominator) * 10000000) / 10000000;
						}
					}

					return ir;
				}

				return 0;
			}

			calc(t) {
				let patFraction = /\d\/\d/;
				let v = t.value.trim().replace('/"/g', '');
				let short_over, inch2, sFinch, sTmpf, sTmp, cm;

				if (patFraction.test(v)) {
					v = this.frac_to_dec(v);
				}
				if (t == this.omm) { // input mm
					if (v == '' || isNaN(v)) {
						this.ocm.value = '';
						this.oinch.value = '';
						this.ofinch.value = '';
						this.oformula.style.display = "none";
						this.omsg.innerHTML = 'Please enter a valid millimeter number';
					}
					else {
						this.ocm.value = Math.round(v / 10 * 1000000000000) / 1000000000000;
						let inch = Math.round(v / 25.4 * 100) / 100;
						this.oinch.value = inch;
						this.set_fractional_inch(inch);
						if (patFraction.test(this.ofinch.value) && (this.ofinch.value !== this.oinch.value)) {
							//short or over fraction
							short_over = "";
							let idxSpace = this.ofinch.value.indexOf(' ');
							if (idxSpace > -1) {
								let wholenumber = parseFloat(this.ofinch.value.substring(0, idxSpace));
								let arrFrac = this.ofinch.value.substring(idxSpace + 1).split("/", 2);
								inch2 = wholenumber + arrFrac[0] / arrFrac[1];
								let middle = 1 / this.ofractions.value / 3;
								if (inch > inch2 && inch - inch2 > middle) {
									short_over = "little over ";
								}
								else if (inch < inch2 && inch2 - inch > middle) {
									short_over = "just short of ";
								}
							}
							else {
								let arrFrac = this.ofinch.value.split("/", 2);
								inch2 = arrFrac[0] / arrFrac[1];
								let middle = 1 / this.ofractions.value / 3;
								if (inch > inch2 && inch - inch2 > middle) {
									short_over = "little over ";
								}
								else if (inch < inch2 && inch2 - inch > middle) {
									short_over = "just short of ";
								}
							}
							sFinch = " &nbsp; = &nbsp; " + short_over + this.ofinch.value + " inch" + (this.ofinch.value == "1" ? "" : "es");
						}
						else {
							sFinch = "";
						}
						this.omsg.innerHTML = this.omm.value + ' mm &nbsp; = &nbsp; ' + this.ocm.value + ' cm &nbsp; = &nbsp; ' + this.oinch.value + ' inch' + (this.oinch.value == "1" ? "" : "es") + sFinch;
						sTmpf = '<li>' + this.omm.value + ' mm &divide; 10 = ' + this.ocm.value + ' cm</li>';
						sTmpf += '<li>' + this.omm.value + ' mm &divide; 25.4 = ' + (this.omm.value / 25.4) + ' in</li>';
						this.oformula.innerHTML = '<ul>' + sTmpf + '</ul>';
						this.oformula.style.display = "block";
					}
				}
				else if (t == this.ocm) { // input cm
					if (v == '' || isNaN(v)) {
						this.omm.value = '';
						this.oinch.value = '';
						this.ofinch.value = '';
						this.oformula.style.display = "none";
						this.omsg.innerHTML = 'Please enter a valid centimeter number';
					}
					else {
						this.omm.value = Math.round(v * 10 * 1000000000000) / 1000000000000;
						let inch = Math.round(v / 2.54 * 100) / 100;
						this.oinch.value = inch;
						this.set_fractional_inch(inch);
						if (patFraction.test(this.ofinch.value) && (this.ofinch.value != this.oinch.value)) {
							sFinch = " &nbsp; = &nbsp; " + this.ofinch.value + " inch" + (this.ofinch.value == "1" ? "" : "es");
						}
						else {
							sFinch = "";
						}
						this.omsg.innerHTML = this.ocm.value + ' cm &nbsp; = &nbsp; ' + this.omm.value + ' mm &nbsp; = &nbsp; ' + this.oinch.value + ' inch' + (this.oinch.value == "1" ? "" : "es") + sFinch;
						sTmpf = '<li>' + this.ocm.value + ' cm &times; 10 mm = ' + this.omm.value + ' mm</li>';
						sTmpf += '<li>' + this.ocm.value + ' cm &divide; 2.54 in = ' + (this.ocm.value / 2.54) + ' in</li>';
						this.oformula.innerHTML = '<ul>' + sTmpf + '</ul>';
						this.oformula.style.display = "block";
					}
				}
				else if (t == this.oinch) { // input inch
					if (v == '' || isNaN(v)) {
						this.omm.value = '';
						this.ocm.value = '';
						this.ofinch.value = '';
						this.oformula.style.display = "none";
						this.omsg.innerHTML = 'Please enter a valid inch number';
					}
					else {
						this.omm.value = Math.round(v * 25.4 * 10) / 10;
						this.ocm.value = Math.round(v * 2.54 * 100) / 100;
						this.set_fractional_inch(v);
						if (patFraction.test(this.ofinch.value) && (this.ofinch.value != this.oinch.value)) {
							sFinch = " &nbsp; = &nbsp; " + this.ofinch.value + " inch" + (this.ofinch.value == "1" ? "" : "es");
						}
						else {
							sFinch = "";
						}
						this.omsg.innerHTML = this.oinch.value + ' inch' + (this.oinch.value == "1" ? "" : "es") + sFinch + " &nbsp; = &nbsp; " + this.omm.value + ' mm &nbsp; = &nbsp; ' + this.ocm.value + ' cm';
						sTmpf = '<li>' + this.oinch.value + ' in &times; 25.4 = ' + this.omm.value + ' mm</li>';
						sTmpf += '<li>' + this.oinch.value + ' in &times; 2.54  = ' + (this.oinch.value * 2.54) + ' cm</li>';
						this.oformula.innerHTML = '<ul>' + sTmpf + '</ul>';
						this.oformula.style.display = "block";
					}
				}
				else if (t == this.ofinch) {
					if (v == '' || isNaN(v)) {
						this.omm.value = '';
						this.ocm.value = '';
						this.oinch.value = '';
						this.oformula.style.display = "none";
						this.omsg.innerHTML = 'Please enter a valid inch number';
					}
					else {
						this.set_inch(v);
						this.omsg.innerHTML = this.ofinch.value + ' inch' + (this.ofinch.value == "1" ? "" : "es") + (this.oinch.value == this.ofinch.value ? "" : " &nbsp; = &nbsp; " + this.oinch.value + " inch" + (this.oinch.value == "1" ? "" : "es")) + " &nbsp; = &nbsp; " + this.omm.value + ' mm &nbsp; = &nbsp; ' + this.ocm.value + ' cm';
						if (this.ofinch.value != this.oinch.value) {
							sTmp = ' &nbsp; = &nbsp; ' + this.oinch.value + ' in';
						}
						else {
							sTmp = '';
						}
						sTmpf = '<li>' + this.ofinch.value + ' in' + sTmp + ' &times; 25.4  = ' + this.omm.value + ' mm</li>';
						sTmpf += '<li>' + this.ofinch.value + ' in' + sTmp + ' &times; 2.54  = ' + (this.oinch.value * 2.54) + ' cm</li>';
						this.oformula.innerHTML = '<ul>' + sTmpf + '</ul>';
						this.oformula.style.display = "block";
					}
				}
				this.draw();
				cm = parseFloat(this.ocm.value);
				if ((cm != 'NaN') && (cm > 0)) {
					this.mark((this.ocm.value - this.begin_cm) * this.ppcm);
				}
			}

			draw() {
				let ruler_length, cm, Lh, s2, s10, s4, s8, s16, s32, begin_inch, inch_offset;

				//move to mark of current
				ruler_length = this.c.width / this.ppcm;
				cm = parseInt(document.getElementById('cm').value);
				if ((cm != 'NaN') && (cm > ruler_length - 1)) {
					this.begin_cm = cm - Math.floor(ruler_length / 2 - 5);
				}
				else {
					this.begin_cm = 0;
				}
				this.cxt.setTransform(1, 0, 0, 1, 0, 0);
				this.cxt.clearRect(0, 0, this.c.width, this.c.height);
				//ruler for cm
				this.cxt.strokeStyle = '#ffffff';
				this.cxt.lineWidth = 1;
				this.cxt.beginPath();
				this.cxt.moveTo(0, this.BL_cm);
				this.cxt.lineTo(this.w, this.BL_cm);
				this.cxt.stroke();
				for (let i = this.begin_x, j = this.begin_cm; i <= this.w; i = i + this.ppcm, j++) {
					Lh = this.BL_cm + 35;
					this.cxt.beginPath();
					this.cxt.strokeStyle = '#ffffff';
					this.cxt.fillStyle = '#ffffff';
					this.cxt.lineWidth = 1;
					this.cxt.moveTo(i, Lh);
					this.cxt.lineTo(i, this.BL_cm);
					this.cxt.stroke();
					this.cxt.font = "20px Arial";
					if (j < 10) {
						this.cxt.fillText(j, i - 6, Lh + 20);
					}
					else {
						this.cxt.fillText(j, i - 11, Lh + 20);
					}
				}
				s2 = this.ppcm / 2;
				for (let i = this.begin_x, j = 0; i <= this.w; i = i + s2, j++) {
					if (j % 2 == 0) continue;
					Lh = this.BL_cm + 25;
					this.cxt.beginPath();
					this.cxt.strokeStyle = '#ffffff';
					this.cxt.lineWidth = 1;
					this.cxt.moveTo(i, Lh);
					this.cxt.lineTo(i, this.BL_cm);
					this.cxt.stroke();
				}
				s10 = this.ppcm / 10;
				for (let i = this.begin_x, j = 0; i <= this.w; i = i + s10, j++) {
					if ((j % 5 == 0) || (j % 10 == 0)) continue;
					Lh = this.BL_cm + 15;
					this.cxt.beginPath();
					this.cxt.strokeStyle = '#ffffff';
					this.cxt.lineWidth = 1;
					this.cxt.moveTo(i, Lh);
					this.cxt.lineTo(i, this.BL_cm);
					this.cxt.stroke();
				}
				//ruler for inch
				this.cxt.strokeStyle = '#ffffff';
				this.cxt.lineWidth = 1;
				this.cxt.beginPath();
				this.cxt.moveTo(0, this.BL_inch);
				this.cxt.lineTo(this.w, this.BL_inch);
				this.cxt.stroke();
				if (this.begin_cm != 0) {
					begin_inch = Math.ceil(this.begin_cm / 2.54);
					inch_offset = begin_inch * this.dpi_x - this.begin_cm * this.ppcm;
				}
				else {
					begin_inch = 0;
					inch_offset = 0;
				}
				for (let i = this.begin_x + inch_offset, j = begin_inch; i <= this.w; i = i + this.dpi_x, j++) {
					Lh = this.BL_inch - 35;
					this.cxt.beginPath();
					this.cxt.fillStyle = '#ffffff';
					this.cxt.lineWidth = 1;
					this.cxt.moveTo(i, Lh);
					this.cxt.lineTo(i, this.BL_inch);
					this.cxt.stroke();
					this.cxt.font = "20px Arial";
					if (j < 10) {
						this.cxt.fillText(j, i - 6, Lh - 5);
					}
					else {
						this.cxt.fillText(j, i - 12, Lh - 5);
					}
				}
				s2 = this.dpi_x / 2;
				for (let i = this.begin_x + inch_offset, j = 0; i <= this.w; i = i + s2, j++) {
					if (j % 2 == 0) continue;
					Lh = this.BL_inch - 30;
					this.cxt.beginPath();
					this.cxt.fillStyle = '#ffffff';
					this.cxt.lineWidth = 1;
					this.cxt.moveTo(i, Lh);
					this.cxt.lineTo(i, this.BL_inch);
					this.cxt.stroke();
					this.cxt.font = "16px Arial";
					this.cxt.fillText('½', i - 7, Lh - 5);
				}
				s4 = this.dpi_x / 4;
				for (let i = this.begin_x + inch_offset, j = 0; i <= this.w; i = i + s4, j++) {
					if ((j % 2 == 0) || (j % 4 == 0)) continue;
					Lh = this.BL_inch - 25;
					this.cxt.beginPath();
					this.cxt.fillStyle = '#ffffff';
					this.cxt.lineWidth = 1;
					this.cxt.moveTo(i, Lh);
					this.cxt.lineTo(i, this.BL_inch);
					this.cxt.stroke();
					this.cxt.font = "12px Arial";
					if (j % 4 == 1) {
						this.cxt.fillText('¼', i - 7, Lh - 5);
					}
					else if (j % 4 == 3) {
						this.cxt.fillText('¾', i - 7, Lh - 5);
					}
				}
				s8 = this.dpi_x / 8;
				for (let i = this.begin_x + inch_offset, j = 0; i <= this.w; i = i + s8, j++) {
					if ((j % 2 == 0) || (j % 4 == 0)) continue;
					Lh = this.BL_inch - 18;
					this.cxt.beginPath();
					this.cxt.fillStyle = '#ffffff';
					this.cxt.lineWidth = 1;
					this.cxt.moveTo(i, Lh);
					this.cxt.lineTo(i, this.BL_inch);
					this.cxt.stroke();
					if (document.getElementById('mark18').checked == true) {
						this.cxt.save();
						this.cxt.font = "12px Arial";
						this.cxt.scale(0.8, 1);
						if (j % 8 == 1) {
							this.cxt.fillText('⅛', (i - 7) / 0.8, Lh - 1);
						}
						else if (j % 8 == 3) {
							this.cxt.fillText('⅜', (i - 6) / 0.8, Lh - 1);
						}
						else if (j % 8 == 5) {
							this.cxt.fillText('⅝', (i - 6) / 0.8, Lh - 1);
						}
						else if (j % 8 == 7) {
							this.cxt.fillText('⅞', (i - 6) / 0.8, Lh - 1);
						}
						this.cxt.restore();
					}
				}
				if (document.getElementById('fractions').value > 8) {
					s16 = this.dpi_x / 16;
					for (let i = this.begin_x + inch_offset, j = 0; i <= this.w; i = i + s16, j++) {
						if ((j % 2 == 0) || (j % 4 == 0) || (j % 8 == 0)) continue;
						Lh = this.BL_inch - 15;
						this.cxt.beginPath();
						this.cxt.fillStyle = '#ffffff';
						this.cxt.lineWidth = 1;
						this.cxt.moveTo(i, Lh);
						this.cxt.lineTo(i, this.BL_inch);
						this.cxt.stroke();
					}
				}
				if (document.getElementById('fractions').value > 16) {
					s32 = this.dpi_x / 32;
					for (let i = this.begin_x + inch_offset, j = 0; i <= this.w; i = i + s32, j++) {
						if ((j % 2 == 0) || (j % 4 == 0) || (j % 8 == 0) || (j % 16 == 0)) continue;
						Lh = this.BL_inch - 10;
						this.cxt.beginPath();
						this.cxt.fillStyle = '#ffffff';
						this.cxt.lineWidth = 1;
						this.cxt.moveTo(i, Lh);
						this.cxt.lineTo(i, this.BL_inch);
						this.cxt.stroke();
					}
				}
				this.cxt.save();
				this.cxt.translate(0, 0);
				this.cxt.rotate(90 * Math.PI / 180);
				this.cxt.fillStyle = '#ffffff';
				this.cxt.font = "12px Arial";
				this.cxt.fillText('MM CM', 3, -2);
				this.cxt.fillText("INCH", 94, -2);
				this.cxt.restore();
				this.cxt.closePath();
			}

			mark(px) {
				this.cxt.strokeStyle = '#00ff00';
				this.cxt.lineWidth = 1;
				this.cxt.beginPath();
				this.cxt.moveTo(this.begin_x + px, 0);
				this.cxt.lineTo(this.begin_x + px, 130);
				this.cxt.stroke();
			}
		}

		let r;
	</script>
</head>
<body>

<div class="container-fluid">
	<!-- PHP Version: <?= $version ?> -->

	<div id="ip">
		Your IP: <span><?= $_SERVER['REMOTE_ADDR']; ?></span><br>
		Random Doc IPv4: <span><script>document.write(generateRandomIP(4));</script></span><br>
		Random Doc IPv6: <span><script>document.write(generateRandomIP(6));</script></span><br>
	</div>

	<div class="row">

		<div class="col-md">
			<h2>Converters</h2>

			<section id="converters" class="card">
				<div class="card-body">
					<div class="row">
						<div class="form-group col-md">
							<label for="conv_raw">Raw:</label>
							<textarea id="conv_raw" class="form-control"></textarea>
							<textarea id="conv_bytes" class="form-control bytes hidden"></textarea>
							<button type="button" class="btn btn-sm btn-warning html" title="Open as HTML in new window">HTML</button>
							<?= $buttons ?>
						</div>
						<div class="form-group col-md">
							<label for="conv_code">A &rarr; 1:</label>
							<textarea id="conv_code" class="form-control"></textarea>
							<?= $buttons ?>
						</div>
					</div>
					<div class="row">
						<div class="form-group col-md">
							<label for="conv_rot13" class="form-inline">Rot&mdash;<input type="number" id="caesar" class="form-control input-xs" max="26" min="-26" step="1" value="13"> (Caesar cipher):</label>
							<textarea id="conv_rot13" class="form-control"></textarea>
							<?= $buttons ?>
						</div>
						<div class="form-group col-md">
							<label for="conv_rev">Reverse:</label>
							<textarea id="conv_rev" class="form-control"></textarea>
							<?= $buttons ?>
						</div>
					</div>
					<div class="row">
						<div class="form-group col-md">
							<label for="conv_base64">Base64: ( <span class="example">+</span> and <span class="example">/</span> )</label>
							<textarea id="conv_base64" class="form-control"></textarea>
							<form method="post" style="display:inline;">
								<input type="hidden" name="file" id="file">
								<button type="button" class="btn btn-sm btn-warning file" title="Download File">File</button>
							</form>
							<?= $buttons ?>
						</div>
						<div class="form-group col-md">
							<label for="conv_base64url">Base64 <abbr title="Uniform Resource Locator">URL</abbr>: ( <span class="example">-</span> and <span class="example">_</span> )</label>
							<textarea id="conv_base64url" class="form-control"></textarea>
							<?= $buttons ?>
						</div>
					</div>
					<div class="row">
						<div class="form-group col-md">
							<label for="conv_quoted">Quoted Printable:</label>
							<textarea id="conv_quoted" class="form-control"></textarea>
							<?= $buttons ?>
						</div>
						<div class="form-group col-md">
							<label for="conv_url"><abbr title="Uniform Resource Locator">URL</abbr> Encoded:</label>
							<textarea id="conv_url" class="form-control"></textarea>
							<?= $buttons ?>
						</div>
					</div>
					<div class="row">
						<div class="form-group col-md">
							<label for="conv_base85">Base85 (<abbr title="American Standard Code for Information Interchange">ASCII</abbr>85):</label>
							<textarea id="conv_base85" class="form-control"></textarea>
							<?= $buttons ?>
						</div>
						<div class="form-group col-md">
							<label for="conv_z85">Z85:</label>
							<textarea id="conv_z85" class="form-control"></textarea>
							<?= $buttons ?>
						</div>
					</div>
					<!-- <div class="row">
						<div class="form-group col-md">
							<label for="conv_yenc">yEnc:</label>
							<textarea id="conv_yenc" class="form-control"></textarea>
							<?= $buttons ?>
						</div>
						<div class="form-group col-md">
							<label for="conv_xxencode">XXEncode: (not yet functional)</label>
							<textarea id="conv_xxencode" class="form-control"></textarea>
							<?= $buttons ?>
						</div>
					</div> -->
					<div class="row">
						<div class="form-group col-md">
							<label for="conv_morse">Morse Code:</label>
							<textarea id="conv_morse" class="form-control"></textarea>
							<?= $buttons ?>
						</div>
						<div class="form-group col-md">
							<label for="conv_uuencode">UUEncode:</label>
							<textarea id="conv_uuencode" class="form-control"></textarea>
							<?= $buttons ?>
						</div>
					</div>
					<div class="row">
						<div class="form-group col-md">
							<label for="conv_puny">Punycode:</label>
							<textarea id="conv_puny" class="form-control"></textarea>
							<?= $buttons ?>
						</div>
					</div>
				</div>
			</section>
		</div>

		<div class="col-md">
			<h2>Digits
				<small>
					<label title="Each space separated number is its own value"><input type="checkbox" id="int_split" checked="checked"/> Split</label>
					<label title="Each space separated number is its own value, padded with leading zeros"><input type="checkbox" id="int_padded"/> Split Padded</label>
					<label title="A single number with space grouped digits"><input type="checkbox" id="int_grouped"/> Grouped</label>
				</small>
			</h2>

			<section id="digits" class="card">
				<div class="card-body">
					<div class="row">
						<div class="form-group col-md">
							<label for="conv_bin">Binary:</label>
							<textarea id="conv_bin" class="form-control digits"></textarea>
							<?= $buttons ?>
						</div>
						<div class="form-group col-md">
							<label for="conv_oct">Octal:</label>
							<textarea id="conv_oct" class="form-control digits"></textarea>
							<?= $buttons ?>
						</div>
					</div>
					<div class="row">
						<div class="form-group col-md">
							<label for="conv_dec">Decimal:</label>
							<textarea id="conv_dec" class="form-control digits"></textarea>
							<?= $buttons ?>
						</div>
						<div class="form-group col-md">
							<label for="conv_hex">Hexadecimal:</label>
							<textarea id="conv_hex" class="form-control digits bytes"></textarea>
							<button type="button" class="btn btn-sm btn-warning hash_raw" title="Hash the bytes as a raw string">Hash Bytes</button>
							<?= $buttons ?>
						</div>
					</div>
				</div>
			</section>


<!-- =========== SPLIT HERE ==================== -->


			<h2><abbr title="Unicode Transformation Format">UTF</abbr>-8</h2>

			<section id="utf8" class="card">
				<div class="card-body">
					<div class="row">
						<div class="form-group col-md">
							<label for="conv_utf8char"><abbr title="Unicode Transformation Format">UTF</abbr>-8: ( 😃√π! )</label>
							<textarea id="conv_utf8char" class="form-control"></textarea>
							<?= $buttons ?>
						</div>
					</div>
					<div class="row">
						<div class="form-group col-md">
							<label for="conv_utf8bytes">Bytes: ( <span class="example">F0 9F 98 83 E2 88 9A CF 80 21</span> )</label>
							<textarea id="conv_utf8bytes" class="form-control bytes"></textarea>
							<button type="button" class="btn btn-sm btn-warning hash_raw" title="Hash the bytes as a raw string">Hash Bytes</button>
							<?= $buttons ?>
						</div>
						<div class="form-group col-md">
							<label for="conv_utf8cbytes"><abbr title="Escaped">Esc</abbr>.: ( <span class="example">\xf0\x9f\x98\x83\xe2\x88\x9a\xcf\x80\x21</span> )</label>
							<textarea id="conv_utf8cbytes" class="form-control"></textarea>
							<?= $buttons ?>
						</div>
					</div>
					<div class="row">
						<div class="form-group col-md">
							<label for="conv_utf8htmldec"><abbr title="HyperText Markup Language">HTML</abbr> Decimal <abbr title="Numerical Character Reference">NCR</abbr>: ( <span class="example">&amp;#128515;&amp;#8730;&amp;#960;&amp;#33;</span> )</label>
							<textarea id="conv_utf8htmldec" class="form-control"></textarea>
							<?= $buttons ?>
						</div>
						<div class="form-group col-md">
							<label for="conv_utf8htmlhex"><abbr title="HyperText Markup Language">HTML</abbr> Hex <abbr title="Numerical Character Reference">NCR</abbr>: ( <span class="example">&amp;#x1F603;&amp;#x221A;&amp;#x3C0;&amp;#x21;</span> )</label>
							<textarea id="conv_utf8htmlhex" class="form-control"></textarea>
							<?= $buttons ?>
						</div>
					</div>
					<div class="row">
						<div class="form-group col-md">
							<label for="conv_utf8esc">Escaped Unicode: ( <span class="example">\u1F603\u221A\u3C0\u21</span> )</label>
							<textarea id="conv_utf8esc" class="form-control"></textarea>
							<?= $buttons ?>
						</div>
						<div class="form-group col-md">
							<label for="conv_utf8code">Code Point: ( <span class="example">U+1F603 U+221A U+3C0 U+21</span> )</label>
							<textarea id="conv_utf8code" class="form-control"></textarea>
							<?= $buttons ?>
						</div>
					</div>
				</div>
			</section>
		</div>

<!--
		<div class="col-md">
			<h2>Inverted Color</h2>

			<section id="color" class="card">
				<div class="card-body">
					<div class="row">
						<div class="form-group col-md">
							<label for="color_dec">Decimal:</label>
							<textarea id="color_dec" class="form-control color"></textarea>
							<?= $buttons ?>
						</div>
						<div class="form-group col-md">
							<label for="color_hex">Hexadecimal:</label>
							<textarea id="color_hex" class="form-control color"></textarea>
							<?= $buttons ?>
						</div>
					</div>
				</div>
			</section>
		</div>
-->

	</div>

	<div class="row" id="hashes">
		<div class="col-md table-responsive">
			<h2>Hashes</h2>
			<div class="form-group">
				<form method="get" action="<?= $_SERVER['SCRIPT_NAME'] ?>#hashes">
				<label for="hash_value">Input String:</label> <label><input type="checkbox" name="hash_raw" id="hash_raw" <?= $_REQUEST['hash_raw'] ? 'checked="checked"' : '' ?>> Hash Raw Bytes</label>
				<textarea id="hash_value" name="hash_value" class="form-control"><?= $_REQUEST['hash_value'] ?></textarea>
				<button type="button" class="btn btn-sm btn-success hash_form">Submit</button>
				<button type="button" class="send btn btn-sm btn-secondary">Send to 'Raw'</button>
				</form>
			</div>
			<table class="table table-striped table-hover table-condensed">
				<thead>
					<tr style="border-bottom: 1px solid #999;">
						<th style="border-right: 1px solid #999">Algorithm</th>
						<th>Hash</th>
					</tr>
				</thead>
				<?php foreach ($algos as $algo) { ?>
					<?php
						$algoname = slug($algo);
						$bad = ['md5', 'sha1', 'crc32', 'haval128,3', 'md4', 'ripemd128'];
						$ok = ['sha256', 'sha512'];
						$good = ['sha3-512'];
						$class = '';
						if (in_array($algo, $bad)) {
							$class = ' class="text-danger danger"';
						}
						elseif (in_array($algo, $ok)) {
							$class = ' class="text-warning warning"';
						}
						elseif (in_array($algo, $good)) {
							$class = ' class="text-success success"';
						}
					?>
				<tr<?= $class ?>>
					<th style="border-right: 1px solid #999"><?= $algo ?></th>
					<td id="hash_<?= $algoname ?>" class="hash_out"><?= $hashes[$algo] ?></td>
				</tr>
				<?php } ?>
			</table>
		</div>
	</div>

	<div class="row" id="ruler_box">
		<div class="col-md">
			<h2>Length Conversion</h2>
			<canvas id="ruler" width="1280" height="130">Your browser does not support the canvas element.</canvas>
			<script>
				document.getElementById("ruler").width = document.getElementById("ruler").parentElement.clientWidth - 30;
				window.onresize = function () {
					document.getElementById("ruler").width = document.getElementById("ruler").innerWidth - 30;
					if ( ! r) {
						r = new Ruler();
					}
					r.draw();
				};
			</script>
			<form>
				<div id="divInput" class="form-row">
					<span class="col"><label for="mm">MM:</label> <input id="mm" type="text" onchange="r.calc(this);" onkeyup="r.calc(this);" placeholder="100" title="millimeter" class="form-control"></span>
					<span class="col"> &nbsp; = &nbsp; <label for="cm">CM:</label> <input id="cm" type="text" onchange="r.calc(this);" onkeyup="r.calc(this);" placeholder="10" title="centimeter" class="form-control"></span>
					<span class="col" style="white-space:pre;"> &nbsp;= &nbsp; <label for="inch">Decimal Inch:</label> <input id="inch" type="text" onchange="r.calc(this);" onkeyup="r.calc(this);" placeholder="3.94" title="decimal inch" class="form-control"></span>
					<span class="col" style="white-space:pre;"> &nbsp;= &nbsp; <label for="finch">Fractional Inch:</label> <input id="finch" type="text" onchange="r.calc(this);" onkeyup="r.calc(this);" placeholder="3 15/16" title="fractional inch" class="form-control"></span>
				</div>
				<br>
				<div class="form-row">
					<div class="col-auto">
						<label for="fractions">Graduations:</label>
						<select id="fractions" onchange="r.draw();" class="form-control">
							<option value="8">1/8"</option>
							<option value="16" selected="selected">1/16"</option>
							<option value="32">1/32"</option>
						</select>
						<input type="checkbox" id="mark18" value="1" onchange="r.draw();" style="margin-left:1em;">
						<label for="mark18">label 1/8" markings</label>
					</div>
				</div>
			</form>
			<div class="lead"><strong id="msg">100 mm &nbsp; = &nbsp; 10 cm &nbsp; = &nbsp; 3.94 inches &nbsp; = &nbsp; 3 15/16 inches</strong></div>
			<div id="formula">
				<ul>
					<li>100 mm ÷ 10 = 10 cm</li>
					<li>100 mm ÷ 25.4 = 3.937007874015748 in</li>
				</ul>
			</div>
		</div>
	</div>
	<div style="height:100px;">&nbsp;</div>
</div>

<script>
	if ( ! r) {
		r = new Ruler();
	}
	r.draw();
</script>

<script type="text/javascript">

	let bindDelay = 500; // ms
	let blocked = false;

	if ( ! String.prototype.modPad) {
		String.prototype.modPad = function (num, char) {
			let ret = this;
			let pad = ret.length % num;
			if (pad) {
				if (pad < char.length) {
					char = substr(char, -pad);
				}

				while (ret.length % num) {
					ret = char + ret;
				}
			}

			return ret;
		}
	}

	// https://gist.github.com/EvanHahn/2587465
	if ( ! String.prototype.caesar) {
		String.prototype.caesar = function(amount) {
			let str = this;
			amount = parseInt(amount, 10);

			if (0 === amount) {
				return str;
			}

			if (amount < 0) {
				return str.caesar(amount + 26);
			}

			let output = '';

			for (let i = 0; i < str.length; i ++) {
				let c = str[i];

				if (c.match(/[a-z]/i)) {
					let code = str.charCodeAt(i);

					// Uppercase letters
					if ((code >= 65) && (code <= 90)) {
						c = String.fromCharCode(((code - 65 + amount) % 26) + 65);
					}
					// Lowercase letters
					else if ((code >= 97) && (code <= 122)) {
						c = String.fromCharCode(((code - 97 + amount) % 26) + 97);
					}
				}

				output += c;
			}

			return output;
		};
	}

	function slug(name) {
		return name.replace(/[\\\\/*+-]/img, "_")
	}

	function group(str, num, reverse) {
		if ( ! str) {
			return "";
		}

		if ("undefined" === typeof reverse) {
			reverse = true;
		}
		reverse = !!reverse;

		let regex = new RegExp(".{1," + num + "}", "img");
		if (reverse) {
			return str.split("").reverse().join("").match(regex).map(function (s) {
				return s.split("").reverse().join("");
			}).reverse().join(" ");
		}
		else {
			let arr = str.match(regex);
			if (arr) {
				return arr.join(" ");
			}
			else {
				return "";
			}
		}
	}

	$("#int_grouped").on("change click share:update", function (evt) {
		$("#int_split").prop("checked", false);
		$("#int_padded").prop("checked", false);
		if ("share:update" !== evt.type) {
			$("textarea.digits:first").trigger("share:update");
		}
	});

	$("#int_padded").on("change click share:update", function (evt) {
		$("#int_split").prop("checked", false);
		$("#int_grouped").prop("checked", false);
		if ("share:update" !== evt.type) {
			$("textarea.digits:first").trigger("share:update");
		}
	});

	$("#int_split").on("change click share:update", function (evt) {
		$("#int_padded").prop("checked", false);
		$("#int_grouped").prop("checked", false);
		if ("share:update" !== evt.type) {
			$("textarea.digits:first").trigger("share:update");
		}
	});

	$("textarea.digits").bindWithDelay("change keyup share:update", function (evt) {
		// if there was a modifier pressed (Alt, Ctrl, etc), don't do anything
		// the change event will capture any changes like cuts or pastes
		if (evt.altKey || evt.ctrlKey || evt.metaKey) {
			evt.preventDefault();
			evt.stopPropagation();
			return false;
		}

		if (("change" === evt.type) && blocked) {
			return;
		}

		// do the simple base conversions
		let type = $(this).attr("id").split("_")[1];
		let val = $(this).val();
		let converted = "";
		let split = $("#int_split").prop("checked");
		let padded = $("#int_padded").prop("checked");
		let grouped = $("#int_grouped").prop("checked");

		if ("" === val.replace(/[\s,.]+/g, "")) {
			$("#conv_bin").not(":focus").val("");
			$("#conv_oct").not(":focus").val("");
			$("#conv_dec").not(":focus").val("");
			$("#conv_hex").not(":focus").val("");

			// pass the emptiness along to the other areas
			if ("share:update" !== evt.type) {
				$("#conv_utf8bytes").not(":focus").val("").trigger("share:update");
				$("#conv_bytes").not(":focus").val("").trigger("share:update");
			}

			return;
		}

		if (grouped) {
			val = val.replace(/[\s,.]+/g, "");

			switch (type) {
				case "bin" :
					val = val.replace(/[^01]+/img, "");
					converted = BigInteger.parse(val, 2);
					break;
				case "oct" :
					val = val.replace(/[^0-7]+/img, "");
					converted = BigInteger.parse(val, 8);
					break;
				case "dec" :
					val = val.replace(/[^0-9]+/img, "");
					converted = BigInteger.parse(val, 10);
					break;
				case "hex" :
					val = val.replace(/[^0-9a-f]+/img, "");
					converted = BigInteger.parse(val, 16);
					break;
				default :
					// do nothing
					break;
			}

			// convert the converted back to the rest
			$("#conv_bin").not(":focus").val(group(converted.toString(2).modPad(8, "0"), 8));
			$("#conv_oct").not(":focus").val(group(converted.toString(8).modPad(3, "0"), 3));
			$("#conv_dec").not(":focus").val(group(converted.toString(10), 3));
			$("#conv_hex").not(":focus").val(group(converted.toString(16).modPad(2, "0"), 2));

			// pass the bytes along to the other areas
			if ("share:update" !== evt.type) {
				$("#conv_utf8bytes").not(":focus").val(group(converted.toString(16).modPad(2, "0"), 2)).trigger("share:update");
				$("#conv_bytes").not(":focus").val(group(converted.toString(16).modPad(2, "0"), 2)).trigger("share:update");
			}
		}
		else {
			let val_array = val.replace(/^\s+|\s+$/img, "").split(" ");

			let outvar = {
				"bin": [],
				"oct": [],
				"dec": [],
				"hex": []
			};
			for (let i = 0, len = val_array.length; i < len; i += 1) {
				val = val_array[i];

				switch (type) {
					case "bin" :
						val = val.replace(/[^01]+/img, "");
						converted = BigInteger.parse(val, 2);
						break;
					case "oct" :
						val = val.replace(/[^0-7]+/img, "");
						converted = BigInteger.parse(val, 8);
						break;
					case "dec" :
						val = val.replace(/[^0-9]+/img, "");
						converted = BigInteger.parse(val, 10);
						break;
					case "hex" :
						val = val.replace(/[^0-9a-f]+/img, "");
						converted = BigInteger.parse(val, 16);
						break;
					default :
						// do nothing
						break;
				}

				outvar.bin.push(converted.toString(2));
				outvar.oct.push(converted.toString(8));
				outvar.dec.push(converted.toString(10));
				outvar.hex.push(converted.toString(16));
			}

			if (padded) {
				let padlen = 0;
				for (let type in outvar) {
					if (outvar.hasOwnProperty(type)) {
						switch (type) {
							case "bin" : padlen = 8; break;
							case "oct" : padlen = 3; break;
							case "hex" : padlen = 2; break;
						}

						for (let i = 0; i < outvar[type].length; i += 1) {
							outvar[type][i] = outvar[type][i].modPad(padlen, "0")
						}
					}
				}

				// pass the bytes along to the other areas
				// but only if padded
				if ("share:update" !== evt.type) {
					$("#conv_utf8bytes").not(":focus").val(outvar.hex.join(" ")).trigger("share:update");
					$("#conv_bytes").not(":focus").val(outvar.hex.join(" ")).trigger("share:update");
				}
			}

			$("#conv_bin").not(":focus").val(outvar.bin.join(" "));
			$("#conv_oct").not(":focus").val(outvar.oct.join(" "));
			$("#conv_dec").not(":focus").val(outvar.dec.join(" "));
			$("#conv_hex").not(":focus").val(outvar.hex.join(" "));
		}
	}, bindDelay);

	$("textarea.color").bindWithDelay("change keyup", function (evt) {
		// if there was a modifier pressed (Alt, Ctrl, etc), don't do anything
		// the change event will capture any changes like cuts or pastes
		if (evt.altKey || evt.ctrlKey || evt.metaKey) {
			evt.preventDefault();
			evt.stopPropagation();
			return false;
		}

		if (("change" === evt.type) && blocked) {
			return;
		}

		// do the simple base conversions
		let type = $(this).attr("id").split("_")[1];
		let val = $(this).val();
		let converted = "";

		if ("" === val.replace(/[\s,.]+/g, "")) {
			$("#color_dec").val("");
			$("#color_hex").val("");

			return;
		}

		val = val.replace(/[\s,.]+/g, "");

		let half = false;
		let full = false;
		switch (type) {
			case "dec" :
				val = val.replace(/[^0-9]+/img, "");
				converted = BigInteger.parse(val, 10);
				break;
			case "hex" :
				val = val.replace(/[^0-9a-f]+/img, "");

				if (1 === val.length) {
					half = true;
					converted = BigInteger.parse(val, 16);
				}
				else if (2 < val.length) {
					full = true;
					// TODO: split this and invert the color across the brightness value
				}
				else {
					converted = BigInteger.parse(val, 16);
				}

				break;
			default :
				// do nothing
				break;
		}

		if (half) {
			// do a simple hex inversion
			converted = 15 - converted;

			// convert the converted back to the rest
			$("#color_dec").val(converted.toString(10));
			$("#color_hex").val(converted.toString(16).modPad(2, "0"));
		}
		else if (full) {
			// convert the converted back to the rest
			$("#color_hex").val(converted.toString(16));
		}
		else {
			// do a simple color inversion
			converted = 255 - converted;

			// convert the converted back to the rest
			$("#color_dec").val(converted.toString(10));
			$("#color_hex").val(converted.toString(16).modPad(2, "0"));
		}
	}, bindDelay * 2);

	$("#converters").find("textarea").bindWithDelay("change keyup share:update", function (evt) {
		// if there was a modifier pressed (Alt, Ctrl, etc), don't do anything
		// the change event will capture any changes like cuts or pastes
		if (evt.altKey || evt.ctrlKey || evt.metaKey) {
			evt.preventDefault();
			evt.stopPropagation();
			return false;
		}

		if (("change" === evt.type) && blocked) {
			return;
		}

		let type = $(this).attr("id").split("_")[1];
		let val = $(this).val();

		// do the ajax conversions
		$.ajax(window.location.href, {
			method: "POST",
			dataType: "json",
			data: {
				encodings: true,
				val: val,
				from: type
			},
			success: function (data) {
				$("#caesar").val(13);
				for (let enc in data) {
					if (data.hasOwnProperty(enc)) {
						if (false === data[enc]) {
							data[enc] = "";
						}

						$("#conv_" + enc).not(":focus").val(data[enc]);

						// pass the bytes along to the other areas
						if (("share:update" !== evt.type) && ("bytes" === enc)) {
							$("#int_padded").prop("checked", true).trigger("share:update");
							$("#conv_utf8bytes").not(":focus").val(data[enc]).trigger("share:update");
							$("#conv_hex").not(":focus").val(data[enc]).trigger("share:update");
						}
					}
				}
			}
		});
	}, bindDelay);

	$("#utf8").find("textarea").bindWithDelay("change keyup share:update", function (evt) {
		// if there was a modifier pressed (Alt, Ctrl, etc), don't do anything
		// the change event will capture any changes like cuts or pastes
		if (evt.altKey || evt.ctrlKey || evt.metaKey) {
			evt.preventDefault();
			evt.stopPropagation();
			return false;
		}

		if (("change" === evt.type) && blocked) {
			return;
		}

		let type = $(this).attr("id").split("_")[1].slice(4);
		let val = $(this).val();

		if ("" === val) {
			// fill the textareas with the empty string
			$("#conv_utf8char").not(":focus").val("");
			$("#conv_utf8htmldec").not(":focus").val("");
			$("#conv_utf8htmlhex").not(":focus").val("");
			$("#conv_utf8bytes").not(":focus").val("");
			$("#conv_utf8cbytes").not(":focus").val("");
			$("#conv_utf8esc").not(":focus").val("");
			$("#conv_utf8code").not(":focus").val("");

			// pass the emptiness along to the other areas
			if ("share:update" !== evt.type) {
				$("#int_padded").prop("checked", true).trigger("share:update");
				$("#conv_hex").not(":focus").val("").trigger("share:update");
				$("#conv_bytes").not(":focus").val("").trigger("share:update");
			}

			return
		}

		let funcName = "from"+type.charAt(0).toUpperCase()+type.slice(1);
		let ret = window[funcName](val);

		// do conversions
		ret.push(ret[2].map(x => x.toString(16).toUpperCase()));
		ret.push(ret[3].map(x => x.padStart(4, "0")));

		// fill the textareas with the returned values
		$("#conv_utf8char").not(":focus").val(ret[0]);
		$("#conv_utf8bytes").not(":focus").val(ret[1].toUpperCase());
		$("#conv_utf8cbytes").not(":focus").val("\\x" + ret[1].toLowerCase().split(" ").join("\\x"));
		$("#conv_utf8htmldec").not(":focus").val("&#" + ret[2].join(";&#") + ";");
		$("#conv_utf8htmlhex").not(":focus").val("&#x" + ret[3].join(";&#x") + ";");
		$("#conv_utf8esc").not(":focus").val("\\u" + ret[4].join("\\u"));
		$("#conv_utf8code").not(":focus").val("U+" + ret[4].join(" U+"));

		// pass the bytes along to the other areas
		if ("share:update" !== evt.type) {
			$("#int_padded").prop("checked", true).trigger("share:update");
			$("#conv_hex").not(":focus").val(ret[1].toUpperCase()).trigger("share:update");
			$("#conv_bytes").not(":focus").val(ret[1].toUpperCase()).trigger("share:update");
		}
	}, bindDelay);

	$("#caesar").on("change click", function (evt) {
		evt.stopPropagation();

		blocked = true;
		setTimeout(function(){ blocked = false; }, 500);

		let str = $('#conv_raw').val();
		let amt = $(this).val();

		$("#conv_rot13").val(str.caesar(amt));
	});

	$("button.hash").on("click", function (evt) {
		let val = $(this).parents(".form-group").find("textarea").val();
		let algoname;

		$("#hash_value").val(val);

		// do the ajax hashes
		$.ajax(window.location.href, {
			method: "POST",
			dataType: "json",
			data: {
				hashes: true,
				hash: val
			},
			success: function (data) {
				for (let algo in data) {
					if (data.hasOwnProperty(algo)) {
						algoname = slug(algo);
						$("#hash_" + algoname).text(data[algo]);
					}
				}

				window.location.hash = "";
				window.location.hash = "#hashes";
			}
		});
	});

	$("button.hash_raw").on("click", function (evt) {
		let val = $(this).parents(".form-group").find("textarea").val();
		let algoname;

		$("#hash_value").val(val);
		$("#hash_raw").prop('checked', true);

		// do the ajax hashes
		$.ajax(window.location.href, {
			method: "POST",
			dataType: "json",
			data: {
				hashes: true,
				hash_value: val,
				hash_raw: true,
			},
			success: function (data) {
				for (let algo in data) {
					if (data.hasOwnProperty(algo)) {
						algoname = slug(algo);
						$("#hash_" + algoname).text(data[algo]);
					}
				}

				window.location.hash = "";
				window.location.hash = "#hashes";
			}
		});
	});

	$("button.hash_form").on("click", function (evt) {
		let val = $(this).parents(".form-group").find("textarea").val();
		let algoname;

		$("#hash_value").val(val);

		// do the ajax hashes
		$.ajax(window.location.href, {
			method: "POST",
			dataType: "json",
			data: {
				hashes: true,
				hash_value: val,
				hash_raw: $('#hash_raw').is(':checked'),
			},
			success: function (data) {
				for (let algo in data) {
					if (data.hasOwnProperty(algo)) {
						algoname = slug(algo);
						$("#hash_" + algoname).text(data[algo]);
					}
				}

				window.location.hash = "";
				window.location.hash = "#hashes";
			}
		});
	});

	$("button.copy").on("click", function (evt) {
		let $text = $(this).parents(".form-group").find("textarea");
		let $span = $text.parents(".form-group").find("span.msg");

		$text.select();
		document.execCommand("copy");

		$span.text("Copied!").fadeOut(5000, function () {
			$span.text("").show();
		});
	});

	$("button.clear").on("click", function (evt) {
		let $text = $(this).parents(".form-group").find("textarea");
		$text.val("").trigger("keyup");
	});

	$("button.send").on("click", function (evt) {
		let val = $(this).parents(".form-group").find("textarea").val();
		$("#conv_raw").val(val).trigger("keyup");
		$([document.documentElement, document.body]).animate({
			scrollTop: $("#conv_raw").offset().top - 40
		}, 500);
	});

	$("button.html").on("click", function (evt) {
		let val = $(this).parents(".form-group").find("textarea").val();
		let wndw = window.open();
		wndw.document.title = "Geek Tools HTML Output";
		wndw.document.write(val);
	});

	$("button.file").on("click", function (evt) {
		let val = $(this).parents(".form-group").find("textarea").val();

		$('#file').val(val)
			.parent().submit();
	});

</script>

</body>
</html>
<?php

	function do_hashes($skip_algos) {
		$algos = hash_algos();

		$raw = filter_var($_REQUEST['hash_raw'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;

		if ($raw) {
			$value = hex2bin(str_replace(' ', '', $_REQUEST['hash_value'] ?? ''));
		}
		else {
			$value = $_REQUEST['hash_value'] ?? '';
		}

		$_REQUEST['hash_value'] = htmlspecialchars($value);

		$hashes = [];
		foreach ($algos as $algo) {
			if ( in_array( $algo, $skip_algos ) ) {
				continue;
			}

			$hashes[ $algo ] = hash( $algo, $value, false );
		}

		return $hashes;
	}

	function do_encodings( ) {
		if ( ! array_key_exists('val', $_POST)) {
			return null;
		}

		$val = $_POST['val'];

		$base85 = new base85();
		$z85 = new z85();
		$morse = new MorseText();
		$puny = new Punycode();

		// convert the incoming string to a raw utf-8 string
		switch ($_POST['from']) {
			case 'bytes' : $raw = from_bytes($val); break;
			case 'code' : $raw = from_code($val); break;
			case 'rot13' : $raw = str_rot13($val); break;
			case 'rev' : $raw = mb_strrev($val); break;
			case 'morse' : $raw = $morse->fromMorse($val); break;
			case 'base64' : $raw = base64_decode(preg_replace('|\s+|', '', $val)); break;
			case 'base64url' : $raw = base64url_decode(preg_replace('|\s+|', '', $val)); break;
			case 'base85' : $raw = $base85->decode($val); break;
			case 'z85' : $raw = $z85->decode($val); break;
			case 'uuencode' : $raw = convert_uudecode($val); break;
			case 'xxencode' : $raw = convert_xxdecode($val); break;
			case 'quoted' : $raw = quoted_printable_decode($val); break;
			case 'url' : $raw = to_utf8(urldecode($val)); break;
			case 'puny' :
				try {
					$raw = $puny->decode($val);
				}
				catch (OutOfBoundsException $e) {
					$raw = 'ERROR: ' . $e->getMessage();
				}
				break;
			case 'raw' : // no break
			default : $raw = $val; break;
		}

		try {
			$punyenc = $puny->encode($raw);
		}
		catch (OutOfBoundsException $e) {
			$punyenc = 'ERROR: ' . $e->getMessage();
		}

		$encoded = [
			'bytes' => to_bytes($raw),
			'code' => to_code($raw),
			'rot13' => str_rot13($raw),
			'rev' => mb_strrev($raw),
			'morse' => $morse->toMorse($raw),
			'base64' => base64_encode($raw),
			'base64url' => base64url_encode($raw),
			'base85' => $base85->encode($raw),
			'z85' => $z85->encode($raw),
			'uuencode' => convert_uuencode($raw),
			'xxencode' => convert_xxencode($raw),
			'quoted' => quoted_printable_encode($raw),
			'url' => urlencode($raw),
			'puny' => $punyenc,
			'raw' => $raw,
		];

		return $encoded;
	}

	function to_bytes($val) {
		$ret = [];

		$bytes = unpack('C*', $val) ?: [];

		foreach ($bytes as $byte) {
			$ret[] = strtoupper(str_pad(dechex($byte), 2, '0', STR_PAD_LEFT));
		}

		return implode(' ', $ret);
	}

	function from_bytes($val) {
		$val = str_split(preg_replace('%\s+%im', '', strtoupper($val)), 2);
		$ret = '';

		foreach ($val as $byte) {
			if (preg_match('#^[0-9a-f]+$#i', $byte)) {
				$ret .= chr(hexdec($byte));
			}
		}

		return $ret;
	}

	// convert A-Z -> 1-26, all others to .
	function to_code($val) {
		// split mb_string into characters
		$arr = preg_split('//u', strtoupper($val), null, PREG_SPLIT_NO_EMPTY) ?: [''];

		$out = [];
		foreach ($arr as $char) {
			if (preg_match('/[A-Z]/', $char)) {
				$out[] = ord($char) - 64;
			}
			elseif (is_numeric($char) && $char > 0) {
				$out[] = chr($char + 64);
			}
			else {
				$out[] = '.';
			}
		}

		return implode(" ", $out);
	}

	// convert 1-26 -> A-Z, all others to -
	function from_code($val) {
		$arr = array_filter(explode(" ", $val));

		$out = "";
		foreach ($arr as $val) {
			$val = (int) $val;
			if ((1 <= $val) && (26 >= $val)) {
				$out .= chr($val + 64);
			}
			else {
				$out .= "-";
			}
		}

		return $out;
	}

	function base64url_encode($data) {
		return strtr(base64_encode($data), '+/', '-_');
	}

	function base64url_decode($data) {
		return base64_decode(strtr($data, '-_', '+/'));
	}

	function slug($name) {
		return preg_replace("/[\\/\+\*-]/im", "_", $name);
	}

	// TODO: build these...
	function convert_xxencode($str) {
		return $str;
	}

	function convert_xxdecode($str) {
		return $str;
	}

	function mb_strrev($string, $encoding = null) {
		if ( ! $encoding) {
			$encoding = mb_detect_encoding($string);
		}

		$reversed = '';
		if ($encoding) {
			$length = mb_strlen($string, $encoding);
			while ($length-- > 0) {
				$reversed .= mb_substr($string, $length, 1, $encoding);
			}
		}
		else {
			$length = strlen($string);
			while ($length-- > 0) {
				$reversed .= substr($string, $length, 1);
			}
		}

		return $reversed;
	}

	function to_utf8($string) {
		// From http://w3.org/International/questions/qa-forms-utf-8.html
		if (preg_match('%^(?:
			[\x09\x0A\x0D\x20-\x7E]              # ASCII
			| [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
			| \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
			| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
			| \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
			| \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
			| [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
			| \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
			)*$%xs', $string)
		) {
			return $string;
		} else {
			return iconv('CP1252', 'UTF-8', $string);
		}
	}


	class base85 {

		// the symbol table (ASCII 33 "!" - 117 "u")
		public $chars = '!"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstu';


		public function encode( $str ) {
			$ret = '';

			// convert to byte array
			$bytes = unpack( 'C*', $str );

			// pad the byte array to a multiple of 4 byte chunks
			$padding = 4 - ( count( $bytes ) % 4 );
			if ( count( $bytes ) % 4 === 0 ) {
				$padding = 0;
			}

			for ( $i = $padding; 0 < $i; --$i ) {
				array_push( $bytes, 0 );
			}

			// chunk the array into 4 byte pieces
			$chunks = array_chunk( $bytes, 4 );

			foreach ( $chunks as & $chunk ) {
				// convert the chunk to an integer
				$tmp = 0;
				foreach ( $chunk as $val ) {
					$tmp = bcmul( $tmp, 256 );
					$tmp = bcadd( $tmp, $val );
				}

				$chunk = $tmp;

				// simple translations
				if ( $this->enc_simple( $chunk, $ret ) ) {
					continue;
				}

				// convert the integer into 5 "quintet" chunks
				$div = 85 * 85 * 85 * 85;
				while ( 1 <= $div ) {
					$idx = bcdiv( $chunk, $div );
					$idx = bcmod( $idx, 85 );
					$ret .= $this->chars[$idx];
					$div /= 85;
				}
			}

			// if null bytes were added, remove them from the final string
			if ( $padding ) {
				$ret = $this->padding_swap($ret);
				$ret = substr( $ret, 0, -$padding );
			}

			return $ret;
		}


		public function decode( $str ) {
			$ret = '';

			// do some minor clean up to the input
			$str = preg_replace( '%\s+%im', '', $str );

			if ( preg_match( "%^<~.*~>$%im", $str ) ) {
				$str = substr( $str, 2, - 2 );
			}

			$str = $this->dec_simple( $str );

			// convert to an index array
			$bytes = [];
			foreach ( str_split( $str ) as $char ) {
				$bytes[] = strpos( $this->chars, $char ); // class name used here to prevent issues
			}

			// pad the index array to a multiple of 5 char chunks
			$padding = 5 - ( count( $bytes ) % 5 );
			if ( count( $bytes ) % 5 === 0 ) {
				$padding = 0;
			}

			for ( $i = $padding; 0 < $i; --$i ) {
				array_push( $bytes, 84 ); // the last index
			}

			// chunk the array into 5 char pieces
			$chunks = array_chunk( $bytes, 5 );

			foreach ( $chunks as $chunk ) {
				// convert the chunk to an integer
				$tmp = 0;
				foreach ( $chunk as $val ) {
					$tmp = bcmul( $tmp, 85 );
					$tmp = bcadd( $tmp, $val );
				}

				$chunk = $tmp;

				// convert the integer into 4 byte chunks
				$div = 256 * 256 * 256;
				while ( 1 <= $div ) {
					$idx = bcdiv( $chunk, $div );
					$idx = bcmod( $idx, 256 );
					$ret .= chr( $idx );
					$div /= 256;
				}
			}

			// remove any padding that was added
			$ret = substr( $ret, 0, -$padding );

			return $ret;
		}


		protected function enc_simple( $chunk, & $ret ) {
			if ( '0' === $chunk ) { // four empty (null) bytes
				$ret .= 'z';

				return true;
			}

			if ( '538976288' === $chunk ) { // four spaces
				$ret .= 'y';

				return true;
			}

			return false;
		}


		protected function dec_simple( $ret ) {
			$ret = str_replace( 'z', '!!!!!', $ret );
			$ret = str_replace( 'y', '+<VdL/', $ret );

			return $ret;
		}


		protected function padding_swap( $ret ) {
			$ret = preg_replace( "/z$/", '!!!!!', $ret );

			return $ret;
		}

	} // end of base85 class

	class z85 extends base85 {

		// the symbol table
		public $chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ.-:+=^!/*?&<>()[]{}@%$#";

		protected function enc_simple( $chunk, & $ret ) { return false; }
		protected function dec_simple( $ret ) { return $ret; }
		protected function padding_swap( $ret ) { return $ret; }

	} // end of z85 class

	/**
	 * Punycode implementation as described in RFC 3492
	 *
	 * @link http://tools.ietf.org/html/rfc3492
	 */
	class Punycode
	{

		/**
		 * Bootstring parameter values
		 *
		 */
		const BASE			= 36;
		const TMIN			= 1;
		const TMAX			= 26;
		const SKEW			= 38;
		const DAMP			= 700;
		const INITIAL_BIAS = 72;
		const INITIAL_N	 = 128;
		const PREFIX		 = 'xn--';
		const DELIMITER	 = '-';

		/**
		 * Encode table
		 *
		 * @param array
		 */
		protected static $encodeTable = [
			'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l',
			'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x',
			'y', 'z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
		];

		/**
		 * Decode table
		 *
		 * @param array
		 */
		protected static $decodeTable = [
			'a' =>  0, 'b' =>  1, 'c' =>  2, 'd' =>  3, 'e' =>  4, 'f' =>  5,
			'g' =>  6, 'h' =>  7, 'i' =>  8, 'j' =>  9, 'k' => 10, 'l' => 11,
			'm' => 12, 'n' => 13, 'o' => 14, 'p' => 15, 'q' => 16, 'r' => 17,
			's' => 18, 't' => 19, 'u' => 20, 'v' => 21, 'w' => 22, 'x' => 23,
			'y' => 24, 'z' => 25, '0' => 26, '1' => 27, '2' => 28, '3' => 29,
			'4' => 30, '5' => 31, '6' => 32, '7' => 33, '8' => 34, '9' => 35
		];

		/**
		 * Character encoding
		 *
		 * @param string
		 */
		protected $encoding;

		/**
		 * Constructor
		 *
		 * @param string $encoding Character encoding
		 */
		public function __construct($encoding = 'UTF-8')
		{
			$this->encoding = $encoding;
		}

		/**
		 * Encode a domain to its Punycode version
		 *
		 * @param string $input Domain name in Unicode to be encoded
		 * @return string Punycode representation in ASCII
		 */
		public function encode($input)
		{
			$input = mb_strtolower($input, $this->encoding);
			$parts = explode('.', $input);
			foreach ($parts as &$part) {
				$length = strlen($part);
				if ($length < 1) {
					throw new LabelOutOfBoundsException(sprintf('The length of any one label is limited to between 1 and 63 octets, but %s given.', $length));
				}
				$part = $this->encodePart($part);
			}
			$output = implode('.', $parts);
			$length = strlen($output);
			if ($length > 255) {
				throw new DomainOutOfBoundsException(sprintf('A full domain name is limited to 255 octets (including the separators), %s given.', $length));
			}

			return $output;
		}

		/**
		 * Encode a part of a domain name, such as tld, to its Punycode version
		 *
		 * @param string $input Part of a domain name
		 * @return string Punycode representation of a domain part
		 */
		protected function encodePart($input)
		{
			$codePoints = $this->listCodePoints($input);

			$n = static::INITIAL_N;
			$bias = static::INITIAL_BIAS;
			$delta = 0;
			$h = $b = count($codePoints['basic']);

			$output = '';
			foreach ($codePoints['basic'] as $code) {
				$output .= $this->codePointToChar($code);
			}
			if ($input === $output) {
				return $output;
			}
			if ($b > 0) {
				$output .= static::DELIMITER;
			}

			$codePoints['nonBasic'] = array_unique($codePoints['nonBasic']);
			sort($codePoints['nonBasic']);

			$i = 0;
			$length = mb_strlen($input, $this->encoding);
			while ($h < $length) {
				$m = $codePoints['nonBasic'][$i++];
				$delta = $delta + ($m - $n) * ($h + 1);
				$n = $m;

				foreach ($codePoints['all'] as $c) {
					if ($c < $n || $c < static::INITIAL_N) {
						$delta++;
					}
					if ($c === $n) {
						$q = $delta;
						for ($k = static::BASE;; $k += static::BASE) {
							$t = $this->calculateThreshold($k, $bias);
							if ($q < $t) {
								break;
							}

							$code = $t + (($q - $t) % (static::BASE - $t));
							$output .= static::$encodeTable[$code];

							$q = ($q - $t) / (static::BASE - $t);
						}

						$output .= static::$encodeTable[$q];
						$bias = $this->adapt($delta, $h + 1, ($h === $b));
						$delta = 0;
						$h++;
					}
				}

				$delta++;
				$n++;
			}
			$out = static::PREFIX . $output;
			$length = strlen($out);
			if ($length > 63 || $length < 1) {
				throw new LabelOutOfBoundsException(sprintf('The length of any one label is limited to between 1 and 63 octets, but %s given.', $length));
			}

			return $out;
		}

		/**
		 * Decode a Punycode domain name to its Unicode counterpart
		 *
		 * @param string $input Domain name in Punycode
		 * @return string Unicode domain name
		 */
		public function decode($input)
		{
			$input = strtolower($input);
			$parts = explode('.', $input);
			foreach ($parts as &$part) {
				$length = strlen($part);
				if ($length > 63 || $length < 1) {
					throw new LabelOutOfBoundsException(sprintf('The length of any one label is limited to between 1 and 63 octets, but %s given.', $length));
				}
				if (strpos($part, static::PREFIX) !== 0) {
					continue;
				}

				$part = substr($part, strlen(static::PREFIX));
				$part = $this->decodePart($part);
			}
			$output = implode('.', $parts);
			$length = strlen($output);
			if ($length > 255) {
				throw new DomainOutOfBoundsException(sprintf('A full domain name is limited to 255 octets (including the separators), %s given.', $length));
			}

			return $output;
		}

		/**
		 * Decode a part of domain name, such as tld
		 *
		 * @param string $input Part of a domain name
		 * @return string Unicode domain part
		 */
		protected function decodePart($input)
		{
			$n = static::INITIAL_N;
			$i = 0;
			$bias = static::INITIAL_BIAS;
			$output = '';

			$pos = strrpos($input, static::DELIMITER);
			if ($pos !== false) {
				$output = substr($input, 0, $pos++);
			} else {
				$pos = 0;
			}

			$outputLength = strlen($output);
			$inputLength = strlen($input);
			while ($pos < $inputLength) {
				$oldi = $i;
				$w = 1;

				for ($k = static::BASE;; $k += static::BASE) {
					$digit = static::$decodeTable[$input[$pos++]];
					$i = $i + ($digit * $w);
					$t = $this->calculateThreshold($k, $bias);

					if ($digit < $t) {
						break;
					}

					$w = $w * (static::BASE - $t);
				}

				$bias = $this->adapt($i - $oldi, ++$outputLength, ($oldi === 0));
				$n = $n + (int) ($i / $outputLength);
				$i = $i % ($outputLength);
				$output = mb_substr($output, 0, $i, $this->encoding) . $this->codePointToChar($n) . mb_substr($output, $i, $outputLength - 1, $this->encoding);

				$i++;
			}

			return $output;
		}

		/**
		 * Calculate the bias threshold to fall between TMIN and TMAX
		 *
		 * @param integer $k
		 * @param integer $bias
		 * @return integer
		 */
		protected function calculateThreshold($k, $bias)
		{
			if ($k <= $bias + static::TMIN) {
				return static::TMIN;
			} elseif ($k >= $bias + static::TMAX) {
				return static::TMAX;
			}
			return $k - $bias;
		}

		/**
		 * Bias adaptation
		 *
		 * @param integer $delta
		 * @param integer $numPoints
		 * @param boolean $firstTime
		 * @return integer
		 */
		protected function adapt($delta, $numPoints, $firstTime)
		{
			$delta = (int) (
				($firstTime)
					? $delta / static::DAMP
					: $delta / 2
				);
			$delta += (int) ($delta / $numPoints);

			$k = 0;
			while ($delta > ((static::BASE - static::TMIN) * static::TMAX) / 2) {
				$delta = (int) ($delta / (static::BASE - static::TMIN));
				$k = $k + static::BASE;
			}
			$k = $k + (int) (((static::BASE - static::TMIN + 1) * $delta) / ($delta + static::SKEW));

			return $k;
		}

		/**
		 * List code points for a given input
		 *
		 * @param string $input
		 * @return array Multi-dimension array with basic, non-basic and aggregated code points
		 */
		protected function listCodePoints($input)
		{
			$codePoints = [
				'all' => [],
				'basic' => [],
				'nonBasic' => [],
			];

			$length = mb_strlen($input, $this->encoding);
			for ($i = 0; $i < $length; $i++) {
				$char = mb_substr($input, $i, 1, $this->encoding);
				$code = $this->charToCodePoint($char);
				if ($code < 128) {
					$codePoints['all'][] = $codePoints['basic'][] = $code;
				} else {
					$codePoints['all'][] = $codePoints['nonBasic'][] = $code;
				}
			}

			return $codePoints;
		}

		/**
		 * Convert a single or multi-byte character to its code point
		 *
		 * @param string $char
		 * @return integer
		 */
		protected function charToCodePoint($char)
		{
			$code = ord($char[0]);
			if ($code < 128) {
				return $code;
			} elseif ($code < 224) {
				return (($code - 192) * 64) + (ord($char[1]) - 128);
			} elseif ($code < 240) {
				return (($code - 224) * 4096) + ((ord($char[1]) - 128) * 64) + (ord($char[2]) - 128);
			} else {
				return (($code - 240) * 262144) + ((ord($char[1]) - 128) * 4096) + ((ord($char[2]) - 128) * 64) + (ord($char[3]) - 128);
			}
		}

		/**
		 * Convert a code point to its single or multi-byte character
		 *
		 * @param integer $code
		 * @return string
		 */
		protected function codePointToChar($code)
		{
			if ($code <= 0x7F) {
				return chr($code);
			} elseif ($code <= 0x7FF) {
				return chr(($code >> 6) + 192) . chr(($code & 63) + 128);
			} elseif ($code <= 0xFFFF) {
				return chr(($code >> 12) + 224) . chr((($code >> 6) & 63) + 128) . chr(($code & 63) + 128);
			} else {
				return chr(($code >> 18) + 240) . chr((($code >> 12) & 63) + 128) . chr((($code >> 6) & 63) + 128) . chr(($code & 63) + 128);
			}
		}
	}

	class DomainOutOfBoundsException extends OutOfBoundsException { }
	class LabelOutOfBoundsException extends OutOfBoundsException { }
