<?php

const APACHE_MIME_TYPES_URL = 'https://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types';

require_once 'vendor/morse/library/Table.php';
require_once 'vendor/morse/library/Text.php';
require_once 'vendor/Base85.php';
require_once 'vendor/Z85.php';

if ( ! function_exists('str_contains')) {
	function str_contains(string $haystack, string $needle): bool {
		return $needle !== '' && mb_strpos($haystack, $needle) !== false;
	}
}

$algos = hash_algos();
$skip_algos = [];
$hashes = do_hashes($skip_algos); // see bottom of file

$uuid = guidv4();

if ( ! empty($_POST['encodings'])) {
	$encodings = do_encodings(); // see bottom of file

	if ( ! $encodings) {
		exit;
	}

	$return = json_encode($encodings);

	while (json_last_error()) {
		// remove malformed UTF-8 from the result set
		// usually caused by reverse or rot13
		foreach ($encodings as $key => $value) {
			json_encode($value);
			if (json_last_error()) {
				$encodings[$key] = '-- ' . json_last_error_msg() . " -- Adjusted data follows:\n" . mb_convert_encoding($value, 'UTF-8', 'UTF-8');
			}
		}

		$return = json_encode($encodings);
	}

	echo $return;
	exit;
}

if ( ! empty($_POST['hashes'])) {
	echo json_encode($hashes);
	exit;
}

if ( ! empty($_POST['file'])) {
	$content = base64_decode($_POST['file']);

	$finfo = new finfo(FILEINFO_MIME);
	$type = $finfo->buffer($content, FILEINFO_MIME_TYPE);

	$mimes = generateUpToDateMimeArray(APACHE_MIME_TYPES_URL);
	$exts = array_flip($mimes);

	header('Content-Disposition: attachment; filename="geek_file.' . $exts[$type] . '"');
	header('Content-Type: ' . $type);
	echo $content;
	exit;
}



// ========= FUNCTIONS ===================================

function do_hashes(array $skip_algos): array {
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

		$hashes[$algo] = hash($algo, $value);
	}

	return $hashes;
}

function do_encodings(): array {
	if ( ! array_key_exists('val', $_POST)) {
		return [];
	}

	$val = $_POST['val'];

	$base85 = new Base85();
	$z85 = new Z85();
	$morse = new Morse\Text();

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
			$raw = base64_decode_both(preg_replace('%\s+%', '', $val));
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
			$raw = idn_to_utf8($val);
			break;
		case 'raw' : // no break
		default :
			$raw = $val;
			break;
	}

	return [
		'bytes' => to_bytes($raw),
		'code' => to_code($raw),
		'rot13' => str_rot13($raw),
		'rev' => mb_strrev($raw),
		'morse' => $morse->toMorse($raw),
		'base64' => base64_encode($raw),
		'base85' => $base85->encode($raw),
		'z85' => $z85->encode($raw),
		'uuencode' => convert_uuencode($raw),
		'xxencode' => convert_xxencode($raw),
		'quoted' => quoted_printable_encode($raw),
		'url' => urlencode($raw),
		'puny' => idn_to_ascii($raw),
		'raw' => $raw,
	];
}

function to_bytes($val): string {
	$ret = [];

	$bytes = unpack('C*', $val) ?: [];

	foreach ($bytes as $byte) {
		$ret[] = strtoupper(str_pad(dechex($byte), 2, '0', STR_PAD_LEFT));
	}

	return implode(' ', $ret);
}

function from_bytes($val) {
	$val = preg_replace('%\s+%im', '', strtoupper($val));

	if ('' === $val) {
		return '';
	}

	return ctype_xdigit(strlen($val) % 2 ? '' : $val) ? hex2bin($val) : "ERROR: invalid binary string";
}

// convert A-Z -> 1-26, all others to .
function to_code($val): string {
	// split mb_string into characters
	$arr = preg_split('//u', strtoupper($val), -1, PREG_SPLIT_NO_EMPTY) ?: [''];

	$out = [];
	foreach ($arr as $char) {
		if (preg_match('/[A-Z]/', $char)) {
			$out[] = ord($char) - 64;
		}
		elseif (is_numeric($char) && $char > 0) {
			$out[] = chr($char + 64);
		}
		elseif ('' !== $char) {
			$out[] = '.';
		}
	}

	return implode(" ", $out);
}

// convert 1-26 -> A-Z, all others to -
function from_code($val): string {
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

function base64_decode_both($data) {
	if (str_contains($data, '-') || str_contains($data, '_')) {
		$data = strtr($data, '-_', '+/');
	}

	return base64_decode($data);
}

function slug($name) {
	return preg_replace("/[\\/+*-]/im", "_", $name);
}

function generateUpToDateMimeArray($url): array {
	$s = [];
	foreach (@explode("\n", @file_get_contents($url)) as $x) {
		if (isset($x[0]) && $x[0] !== '#'
			&& preg_match_all('#(\S+)#', $x, $out)
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

// TODO: build these...
function convert_xxencode($str) {
	return $str;
}

function convert_xxdecode($str) {
	return $str;
}

function mb_strrev($string, $encoding = null): string {
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
			)*$%x', $string
		)
	) {
		return $string;
	}
	else {
		return iconv('CP1252', 'UTF-8', $string);
	}
}

/**
 * @throws Exception
 */
function guidv4($data = null): string {
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
