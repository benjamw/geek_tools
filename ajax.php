<?php

const APACHE_MIME_TYPES_URL = 'https://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types';

// Optional progressive-enhancement backend for the otherwise static
// index.html. It serves two things the browser cannot do on its own:
//   - the caller's IP for the "Your IP" banner (GET ?ip=1)
//   - server-side MIME sniffing + the Apache mime.types map for downloads
// The client falls back gracefully (hides the banner / uses a Blob download)
// when this file is absent and the request 404s.

if (isset($_GET['ip'])) {
	header('Content-Type: text/plain');
	echo $_SERVER['REMOTE_ADDR'] ?? '';
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
