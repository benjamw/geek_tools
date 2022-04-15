<?php

require 'vendor/morse/library/Table.php';
require 'vendor/morse/library/Text.php';

$algos = hash_algos();
$skip_algos = [];
$hashes = do_hashes($skip_algos); // see bottom of file

$version = phpversion();

$uuid = guidv4();

if ( ! empty($_POST['encodings'])) {
	$encodings = do_encodings(); // see bottom of file
	$return = json_encode($encodings);
	while (json_last_error()) {
		// remove malformed UTF-8 from the result set
		// usually caused by reverse or rot13
		foreach ($encodings as $key => $value) {
			json_encode($value);
			if (json_last_error()) {
				$encodings[ $key ] = '-- ' . json_last_error_msg() . " -- Adjusted data follows:\n" . mb_convert_encoding($value, 'UTF-8', 'UTF-8');;
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
					$s[ $out[1][ $i ] ] = $out[1][0];
				}
			}
		}

		return $s;
	}

	$mimes = generateUpToDateMimeArray();
	$exts = array_flip($mimes);

	header('Content-Disposition: attachment; filename="geek_file.' . $exts[ $type ] . '"');
	header('Content-Type: ' . $type);
	echo $content;
	exit;
}

function do_hashes($skip_algos) {
	$algos = hash_algos();

	$raw = filter_var($_REQUEST['hash_raw'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;

	if ($raw) {
		$value = hex2bin(str_replace(' ', '', $_REQUEST['hash_value'] ?? ''));
	}
	else {
		$value = $_REQUEST['hash_value'] ?? '';
	}

	$_REQUEST['hash_value'] = htmlspecialchars($value);

	$hashes = [];
	foreach ($algos as $algo) {
		if (in_array($algo, $skip_algos)) {
			continue;
		}

		$hashes[ $algo ] = hash($algo, $value, false);
	}

	return $hashes;
}

function do_encodings() {
	if ( ! array_key_exists('val', $_POST)) {
		return null;
	}

	$val = $_POST['val'];

	$base85 = new base85();
	$z85 = new z85();
	$morse = new Morse\Text();
	$puny = new Punycode();

	// convert the incoming string to a raw utf-8 string
	switch ($_POST['from']) {
		case 'bytes' :
			$raw = from_bytes($val);
			break;
		case 'code' :
			$raw = from_code($val);
			break;
		case 'rot13' :
			$raw = str_rot13($val);
			break;
		case 'rev' :
			$raw = mb_strrev($val);
			break;
		case 'morse' :
			$raw = $morse->fromMorse($val);
			break;
		case 'base64' :
			$raw = base64_decode(preg_replace('|\s+|', '', $val));
			break;
		case 'base64url' :
			$raw = base64url_decode(preg_replace('|\s+|', '', $val));
			break;
		case 'base85' :
			$raw = $base85->decode($val);
			break;
		case 'z85' :
			$raw = $z85->decode($val);
			break;
		case 'uuencode' :
			$raw = convert_uudecode($val);
			break;
		case 'xxencode' :
			$raw = convert_xxdecode($val);
			break;
		case 'quoted' :
			$raw = quoted_printable_decode($val);
			break;
		case 'url' :
			$raw = to_utf8(urldecode($val));
			break;
		case 'puny' :
			try {
				$raw = $puny->decode($val);
			}
			catch (OutOfBoundsException $e) {
				$raw = 'ERROR: ' . $e->getMessage();
			}
			break;
		case 'raw' : // no break
		default :
			$raw = $val;
			break;
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
	$val = preg_replace('%\s+%im', '', strtoupper($val));

	return ctype_xdigit(strlen($val) % 2 ? "" : $val) ? hex2bin($val) : "ERROR: invalid binary string";
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
	if (preg_match(
		'%^(?:
			[\x09\x0A\x0D\x20-\x7E]              # ASCII
			| [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
			| \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
			| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
			| \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
			| \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
			| [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
			| \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
			)*$%xs', $string
	)
	) {
		return $string;
	}
	else {
		return iconv('CP1252', 'UTF-8', $string);
	}
}


class base85
{

	// the symbol table (ASCII 33 "!" - 117 "u")
	public $chars = '!"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstu';


	public function encode($str) {
		$ret = '';

		// convert to byte array
		$bytes = unpack('C*', $str);

		// pad the byte array to a multiple of 4 byte chunks
		$padding = 4 - (count($bytes) % 4);
		if (count($bytes) % 4 === 0) {
			$padding = 0;
		}

		for ($i = $padding; 0 < $i; --$i) {
			array_push($bytes, 0);
		}

		// chunk the array into 4 byte pieces
		$chunks = array_chunk($bytes, 4);

		foreach ($chunks as & $chunk) {
			// convert the chunk to an integer
			$tmp = 0;
			foreach ($chunk as $val) {
				$tmp = bcmul($tmp, 256);
				$tmp = bcadd($tmp, $val);
			}

			$chunk = $tmp;

			// simple translations
			if ($this->enc_simple($chunk, $ret)) {
				continue;
			}

			// convert the integer into 5 "quintet" chunks
			$div = 85 * 85 * 85 * 85;
			while (1 <= $div) {
				$idx = bcdiv($chunk, $div);
				$idx = bcmod($idx, 85);
				$ret .= $this->chars[ $idx ];
				$div /= 85;
			}
		}

		// if null bytes were added, remove them from the final string
		if ($padding) {
			$ret = $this->padding_swap($ret);
			$ret = substr($ret, 0, -$padding);
		}

		return $ret;
	}


	public function decode($str) {
		$ret = '';

		// do some minor clean up to the input
		$str = preg_replace('%\s+%im', '', $str);

		if (preg_match("%^<~.*~>$%im", $str)) {
			$str = substr($str, 2, -2);
		}

		$str = $this->dec_simple($str);

		// convert to an index array
		$bytes = [];
		foreach (str_split($str) as $char) {
			$bytes[] = strpos($this->chars, $char); // class name used here to prevent issues
		}

		// pad the index array to a multiple of 5 char chunks
		$padding = 5 - (count($bytes) % 5);
		if (count($bytes) % 5 === 0) {
			$padding = 0;
		}

		for ($i = $padding; 0 < $i; --$i) {
			array_push($bytes, 84); // the last index
		}

		// chunk the array into 5 char pieces
		$chunks = array_chunk($bytes, 5);

		foreach ($chunks as $chunk) {
			// convert the chunk to an integer
			$tmp = 0;
			foreach ($chunk as $val) {
				$tmp = bcmul($tmp, 85);
				$tmp = bcadd($tmp, $val);
			}

			$chunk = $tmp;

			// convert the integer into 4 byte chunks
			$div = 256 * 256 * 256;
			while (1 <= $div) {
				$idx = bcdiv($chunk, $div);
				$idx = bcmod($idx, 256);
				$ret .= chr($idx);
				$div /= 256;
			}
		}

		// remove any padding that was added
		$ret = substr($ret, 0, -$padding);

		return $ret;
	}


	protected function enc_simple($chunk, &$ret) {
		if ('0' === $chunk) { // four empty (null) bytes
			$ret .= 'z';

			return true;
		}

		if ('538976288' === $chunk) { // four spaces
			$ret .= 'y';

			return true;
		}

		return false;
	}


	protected function dec_simple($ret) {
		$ret = str_replace('z', '!!!!!', $ret);
		$ret = str_replace('y', '+<VdL/', $ret);

		return $ret;
	}


	protected function padding_swap($ret) {
		$ret = preg_replace("/z$/", '!!!!!', $ret);

		return $ret;
	}

} // end of base85 class

class z85 extends base85
{

	// the symbol table
	public $chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ.-:+=^!/*?&<>()[]{}@%$#";

	protected function enc_simple($chunk, &$ret) {
		return false;
	}

	protected function dec_simple($ret) {
		return $ret;
	}

	protected function padding_swap($ret) {
		return $ret;
	}

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
	 */
	const BASE = 36;

	const TMIN = 1;

	const TMAX = 26;

	const SKEW = 38;

	const DAMP = 700;

	const INITIAL_BIAS = 72;

	const INITIAL_N = 128;

	const PREFIX = 'xn--';

	const DELIMITER = '-';

	/**
	 * Encode table
	 *
	 * @param array
	 */
	protected static $encodeTable = [
		'a',
		'b',
		'c',
		'd',
		'e',
		'f',
		'g',
		'h',
		'i',
		'j',
		'k',
		'l',
		'm',
		'n',
		'o',
		'p',
		'q',
		'r',
		's',
		't',
		'u',
		'v',
		'w',
		'x',
		'y',
		'z',
		'0',
		'1',
		'2',
		'3',
		'4',
		'5',
		'6',
		'7',
		'8',
		'9',
	];

	/**
	 * Decode table
	 *
	 * @param array
	 */
	protected static $decodeTable = [
		'a' => 0,
		'b' => 1,
		'c' => 2,
		'd' => 3,
		'e' => 4,
		'f' => 5,
		'g' => 6,
		'h' => 7,
		'i' => 8,
		'j' => 9,
		'k' => 10,
		'l' => 11,
		'm' => 12,
		'n' => 13,
		'o' => 14,
		'p' => 15,
		'q' => 16,
		'r' => 17,
		's' => 18,
		't' => 19,
		'u' => 20,
		'v' => 21,
		'w' => 22,
		'x' => 23,
		'y' => 24,
		'z' => 25,
		'0' => 26,
		'1' => 27,
		'2' => 28,
		'3' => 29,
		'4' => 30,
		'5' => 31,
		'6' => 32,
		'7' => 33,
		'8' => 34,
		'9' => 35
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
	public function __construct($encoding = 'UTF-8') {
		$this->encoding = $encoding;
	}

	/**
	 * Encode a domain to its Punycode version
	 *
	 * @param string $input Domain name in Unicode to be encoded
	 *
	 * @return string Punycode representation in ASCII
	 */
	public function encode($input) {
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
	 *
	 * @return string Punycode representation of a domain part
	 */
	protected function encodePart($input) {
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
			$m = $codePoints['nonBasic'][ $i++ ];
			$delta = $delta + ($m - $n) * ($h + 1);
			$n = $m;

			foreach ($codePoints['all'] as $c) {
				if ($c < $n || $c < static::INITIAL_N) {
					$delta++;
				}
				if ($c === $n) {
					$q = $delta;
					for ($k = static::BASE; ; $k += static::BASE) {
						$t = $this->calculateThreshold($k, $bias);
						if ($q < $t) {
							break;
						}

						$code = $t + (($q - $t) % (static::BASE - $t));
						$output .= static::$encodeTable[ $code ];

						$q = ($q - $t) / (static::BASE - $t);
					}

					$output .= static::$encodeTable[ $q ];
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
	 *
	 * @return string Unicode domain name
	 */
	public function decode($input) {
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
	 *
	 * @return string Unicode domain part
	 */
	protected function decodePart($input) {
		$n = static::INITIAL_N;
		$i = 0;
		$bias = static::INITIAL_BIAS;
		$output = '';

		$pos = strrpos($input, static::DELIMITER);
		if ($pos !== false) {
			$output = substr($input, 0, $pos++);
		}
		else {
			$pos = 0;
		}

		$outputLength = strlen($output);
		$inputLength = strlen($input);
		while ($pos < $inputLength) {
			$oldi = $i;
			$w = 1;

			for ($k = static::BASE; ; $k += static::BASE) {
				$digit = static::$decodeTable[ $input[ $pos++ ] ];
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
	 *
	 * @return integer
	 */
	protected function calculateThreshold($k, $bias) {
		if ($k <= $bias + static::TMIN) {
			return static::TMIN;
		}
		elseif ($k >= $bias + static::TMAX) {
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
	 *
	 * @return integer
	 */
	protected function adapt($delta, $numPoints, $firstTime) {
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
	 *
	 * @return array Multi-dimension array with basic, non-basic and aggregated code points
	 */
	protected function listCodePoints($input) {
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
			}
			else {
				$codePoints['all'][] = $codePoints['nonBasic'][] = $code;
			}
		}

		return $codePoints;
	}

	/**
	 * Convert a single or multi-byte character to its code point
	 *
	 * @param string $char
	 *
	 * @return integer
	 */
	protected function charToCodePoint($char) {
		$code = ord($char[0]);
		if ($code < 128) {
			return $code;
		}
		elseif ($code < 224) {
			return (($code - 192) * 64) + (ord($char[1]) - 128);
		}
		elseif ($code < 240) {
			return (($code - 224) * 4096) + ((ord($char[1]) - 128) * 64) + (ord($char[2]) - 128);
		}
		else {
			return (($code - 240) * 262144) + ((ord($char[1]) - 128) * 4096) + ((ord($char[2]) - 128) * 64) + (ord($char[3]) - 128);
		}
	}

	/**
	 * Convert a code point to its single or multi-byte character
	 *
	 * @param integer $code
	 *
	 * @return string
	 */
	protected function codePointToChar($code) {
		if ($code <= 0x7F) {
			return chr($code);
		}
		elseif ($code <= 0x7FF) {
			return chr(($code >> 6) + 192) . chr(($code & 63) + 128);
		}
		elseif ($code <= 0xFFFF) {
			return chr(($code >> 12) + 224) . chr((($code >> 6) & 63) + 128) . chr(($code & 63) + 128);
		}
		else {
			return chr(($code >> 18) + 240) . chr((($code >> 12) & 63) + 128) . chr((($code >> 6) & 63) + 128) . chr(($code & 63) + 128);
		}
	}
}

class DomainOutOfBoundsException extends OutOfBoundsException
{

}

class LabelOutOfBoundsException extends OutOfBoundsException
{

}

/**
 * @throws Exception
 */
function guidv4($data = null) {
	// Generate 16 bytes (128 bits) of random data or use the data passed into the function.
	$data = $data ?? random_bytes(16);
	assert(strlen($data) == 16);

	// Set version to 0100
	$data[6] = chr(ord($data[6]) & 0x0f | 0x40);
	// Set bits 6-7 to 10
	$data[8] = chr(ord($data[8]) & 0x3f | 0x80);

	// Output the 36 character UUID.
	return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}
