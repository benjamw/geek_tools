<?php

	// FIXME: find out what caused the window[funcName] to fail saying that window[funcName] was not a function

	// TODO: create IPv6 tools
		// Add buttons to IP tool inputs
		// Allow and automatically remove square brackets around an IPv6 address
		// A double colon should not be used to denote an omitted single section of zeros - SEE IF THIS IS AN ISSUE AND FIX
		// convert to/from int (no :, but padding) - DONE (no padding)
		// convert to/from binary (no :, but padding)
		// add ability to handle IPv6/IPv4 addresses and convert between them
			// like 64:ff9b::192.0.2.128 <-> 64:ff9b::C000:280
	// TODO: create CIDR tools for both

	// TODO: add functionality to be able to change the rotation in the ROT-13 field
	//		but prevent the new string from being sent to the translators (unless the send button is pressed)
	//		and just do the translation in the rot-13 box from the contents of the raw box


	// TODO: Test all encoding and decoding functions to make sure they are working both ways
	// TODO: get xxencoding working
	// TODO: add a checkbox to add the <~ ... ~> from the ends of the ASCII85 encoded string, <~n=Q)*n=Q)-n=Q)Y~>
	// TODO: do the above but removing automatically if found
	// TODO: add a checkbox to add any demarcation characters for the encoded strings (UUEncode, Z85, etc)
	// TODO: remove any of the above automatically if found

/**
 * @var string $uuid
 * @var string[] $algos
 * @var string[] $hashes
 */
require_once 'ajax.php';

$buttons = <<< EOHTML
	<button type="button" class="copy btn btn-sm btn-primary">Copy</button>
	<button type="button" class="hash btn btn-sm btn-success">Hash</button>
	<span class="msg"></span>
	<span class="float-end">
		<button type="button" class="send btn btn-sm btn-secondary">Send to 'Raw'</button>
		<button type="button" class="clear btn btn-sm btn-secondary">Clear</button>
	</span>
EOHTML;

?>
<!doctype html><!-- PHP Version: <?= phpversion() ?> -->
<html lang="en" data-bs-theme="dark">
<head>

	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta http-equiv="x-ua-compatible" content="ie=edge">

	<meta name="author" content="Benjam Welker">
	<meta name="description" content="A page to convert strings to various forms, numbers between bases, and UTF-8 encodings.">

	<title>Geek Tools</title>

	<!-- Bootstrap: Latest compiled and minified CSS -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>

	<link rel="stylesheet" href="assets/main.css">

	<!-- jQuery -->
	<script src="https://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>

	<!-- BigInteger -->
	<script src="assets/biginteger.js"></script>

	<!-- bindWithDelay -->
	<script src="assets/bind_with_delay.js"></script>

	<!-- UTF8 Converter -->
	<!-- original from http://www.endmemo.com/unicode/unicodeconverter.php : http://www.endmemo.com/unicode/script/convertuni.js -->
	<script src="assets/utf8_conv.js"></script>

	<script src="assets/rand_ip.js"></script>
	<script src="assets/ruler.js"></script>
</head>
<body>

<div class="container-fluid">

	<div id="ip">
		Your IP: <span><?= $_SERVER['REMOTE_ADDR']; ?></span><br>
	</div>

	<div class="row row-cols-sm-1 row-cols-md-2">

		<div class="col">
			<h2>Converters</h2>

			<section id="converters" class="card">
				<div class="card-body">

					<div class="row">
						<div class="form-group col">
							<label for="conv_raw">Raw:</label>
							<textarea id="conv_raw" class="form-control" data-bs-theme="light"></textarea>
							<textarea id="conv_bytes" class="form-control bytes hidden" data-bs-theme="light"></textarea>
							<button type="button" class="btn btn-sm btn-warning html" title="Open as HTML in new window">HTML</button>
							<?= $buttons ?>
						</div>
						<div class="form-group col">
							<label for="conv_base64">Base64: ( <span class="example">+</span> and <span class="example">/</span> )
								<label for="b64url">
									<input type="checkbox" id="b64url">
									<abbr title="Uniform Resource Locator">URL</abbr>:
									( <span class="example">-</span> and <span class="example">_</span> )
								</label>
							</label>
							<textarea id="conv_base64" class="form-control" data-bs-theme="light"></textarea>
							<form method="post" style="display:inline;">
								<input type="hidden" name="file" id="file">
								<button type="button" class="btn btn-sm btn-warning file" title="Download File">File</button>
							</form>
							<?= $buttons ?>
						</div>
					</div>

					<div class="row">
						<div class="form-group col">
							<label for="conv_base85">Base85 (<abbr title="American Standard Code for Information Interchange">ASCII</abbr>85):</label>
							<textarea id="conv_base85" class="form-control" data-bs-theme="light"></textarea>
							<?= $buttons ?>
						</div>
						<div class="form-group col">
							<label for="conv_z85">Z85:</label>
							<textarea id="conv_z85" class="form-control" data-bs-theme="light"></textarea>
							<?= $buttons ?>
						</div>
					</div>

					<div class="row">
						<div class="form-group col">
							<label for="conv_quoted">Quoted Printable:</label>
							<textarea id="conv_quoted" class="form-control" data-bs-theme="light"></textarea>
							<?= $buttons ?>
						</div>
						<div class="form-group col">
							<label for="conv_url"><abbr title="Uniform Resource Locator">URL</abbr> Encoded:</label>
							<textarea id="conv_url" class="form-control" data-bs-theme="light"></textarea>
							<?= $buttons ?>
						</div>
					</div>

					<div class="row">
						<div class="form-group col">
							<label for="conv_uuencode">UUEncode:</label>
							<textarea id="conv_uuencode" class="form-control" data-bs-theme="light"></textarea>
							<?= $buttons ?>
						</div>
						<div class="form-group col">
							<label for="conv_puny">Punycode:</label>
							<textarea id="conv_puny" class="form-control" data-bs-theme="light"></textarea>
							<?= $buttons ?>
						</div>
					</div>

					<!-- <div class="row">
						<div class="form-group col">
							<label for="conv_yenc">yEnc:</label>
							<textarea id="conv_yenc" class="form-control" data-bs-theme="light"></textarea>
							<?= $buttons ?>
						</div>
						<div class="form-group col">
							<label for="conv_xxencode">XXEncode: (not yet functional)</label>
							<textarea id="conv_xxencode" class="form-control" data-bs-theme="light"></textarea>
							<?= $buttons ?>
						</div>
					</div> -->

					<div class="row">
						<div class="form-group col">
							<label for="conv_rot13">Rot-<input type="number" id="caesar" class="form-control input-xs" max="26" min="-26" step="1" value="13">
								(Caesar cipher):</label>
							<textarea id="conv_rot13" class="form-control" data-bs-theme="light"></textarea>
							<?= $buttons ?>
						</div>
						<div class="form-group col">
							<label for="conv_rev">Reverse:</label>
							<textarea id="conv_rev" class="form-control" data-bs-theme="light"></textarea>
							<?= $buttons ?>
						</div>
					</div>

					<div class="row">
						<div class="form-group col">
							<label for="conv_morse">Morse Code:</label>
							<textarea id="conv_morse" class="form-control" data-bs-theme="light"></textarea>
							<?= $buttons ?>
						</div>
						<div class="form-group col">
							<label for="conv_code">A &rarr; 1:</label>
							<textarea id="conv_code" class="form-control" data-bs-theme="light"></textarea>
							<?= $buttons ?>
						</div>
					</div>

				</div>
			</section>
<!--
			<h2>Time Conversion</h2>

			<section id="time" class="card">
				<div class="card-body">
					<div class="row">
						<div class="col">Decimal Values</div>
					</div>
					<div class="form-group row align-items-center row-cols-auto g-2">
						<div class="col-2">
							<input id="time_dec_days" class="form-control" type="text" inputmode="decimal" data-bs-theme="light">
						</div>
						<div class="col-0">
							<label for="time_dec_days" class="col-form-label">D</label> or
						</div>
						<div class="col-2">
							<input id="time_dec_hours" class="form-control" type="text" inputmode="decimal" data-bs-theme="light">
						</div>
						<div class="col-0">
							<label for="time_dec_hours" class="col-form-label">H</label> or
						</div>
						<div class="col-2">
							<input id="time_dec_mins" class="form-control" type="text" inputmode="decimal" data-bs-theme="light">
						</div>
						<div class="col-0">
							<label for="time_dec_mins" class="col-form-label">M</label> or
						</div>
						<div class="col-2">
							<input id="time_dec_secs" class="form-control" type="text" inputmode="decimal" data-bs-theme="light">
						</div>
						<div class="col-0">
							<label for="time_dec_secs" class="col-form-label">S</label>
						</div>
					</div>
					<div class="row">
						<div class="col"><abbr title="Day Hour Minute Second">DHMS</abbr> Values</div>
					</div>
					<div class="form-group row align-items-center row-cols-auto g-2">
						<div class="col-2">
							<input id="time_dhms_days" class="form-control" type="number" min="0" value="0" inputmode="numeric" data-bs-theme="light">
						</div>
						<div class="col-0">
							<label for="time_dhms_days" class="col-form-label">D</label>
						</div>
						<div class="col-2">
							<input id="time_dhms_hours" class="form-control" type="number" min="0" max="23" value="0" inputmode="numeric" data-bs-theme="light">
						</div>
						<div class="col-0">
							<label for="time_dhms_hours" class="col-form-label">H</label>
						</div>
						<div class="col-2">
							<input id="time_dhms_mins" class="form-control" type="number" min="0" max="59" value="0" inputmode="numeric" data-bs-theme="light">
						</div>
						<div class="col-0">
							<label for="time_dhms_mins" class="col-form-label">M</label>
						</div>
						<div class="col-2">
							<input id="time_dhms_secs" class="form-control" type="number" min="0" max="59" value="0" inputmode="numeric" data-bs-theme="light">
						</div>
						<div class="col-0">
							<label for="time_dhms_secs" class="col-form-label">S</label>
						</div>
					</div>
					<div class="row">
						<div class="col"><label for="time_generic">Generic Input</label></div>
					</div>
					<div class="form-group row align-items-center row-cols-auto g-2">
						<input id="time_generic" class="form-control" type="text" data-bs-theme="light">
					</div>
				</div>
			</section>
-->
			<h2>Random Things</h2>

			<section id="random" class="card">
				<div class="card-body">
					<div class="row">
						<div class="form-group col">
							<label for="rand_ipv4">IPv4 (for documentation examples)</label>
							<input id="rand_ipv4" class="form-control" type="text" disabled>
						</div>
					</div>
					<div class="row">
						<div class="form-group col">
							<label for="rand_ipv6">IPv6 (for documentation examples)</label>
							<input id="rand_ipv6" class="form-control" type="text" disabled>
						</div>
					</div>
					<div class="row">
						<div class="form-group col">
							<label for="rand_uuid"><abbr title="Universally Unique IDentifier">UUID</abbr>
								(<abbr title="Generated with PHP random_bytes function">cryptographically secure</abbr>)</label>
							<input id="rand_uuid" class="form-control" type="text" disabled value="<?= $uuid ?>">
						</div>
					</div>
				</div>
			</section>

		</div>

		<div class="col">
			<h2>Digits
				<small>
					<label title="Each space separated number is its own value"><input type="checkbox" id="int_split"/> Split</label>
					<label title="Each space separated number is its own value, padded with leading zeros"><input type="checkbox" id="int_padded" checked="checked"/> Split Padded</label>
					<label title="A single number with space grouped digits"><input type="checkbox" id="int_grouped"/> Grouped</label>
				</small>
			</h2>

			<section id="digits" class="card">
				<div class="card-body">
					<div class="row">
						<div class="form-group col">
							<label for="conv_bin">Binary:</label>
							<textarea id="conv_bin" class="form-control digits" data-bs-theme="light"></textarea>
							<?= $buttons ?>
						</div>
						<div class="form-group col">
							<label for="conv_oct">Octal:</label>
							<textarea id="conv_oct" class="form-control digits" data-bs-theme="light"></textarea>
							<?= $buttons ?>
						</div>
					</div>
					<div class="row">
						<div class="form-group col">
							<label for="conv_dec">Decimal:</label>
							<textarea id="conv_dec" class="form-control digits" data-bs-theme="light"></textarea>
							<?= $buttons ?>
						</div>
						<div class="form-group col">
							<label for="conv_hex">Hexadecimal:</label>
							<textarea id="conv_hex" class="form-control digits bytes" data-bs-theme="light"></textarea>
							<button type="button" class="btn btn-sm btn-warning hash_raw" title="Hash the bytes as raw bytes">Hash Bytes</button>
							<?= $buttons ?>
						</div>
					</div>
				</div>
			</section>

			<h2><abbr title="Unicode Transformation Format">UTF</abbr>-8</h2>

			<section id="utf8" class="card">
				<div class="card-body">
					<div class="row">
						<div class="form-group col">
							<label for="conv_utf8char"><abbr title="Unicode Transformation Format">UTF</abbr>-8: ( 😃√π! )</label>
							<textarea id="conv_utf8char" class="form-control" data-bs-theme="light"></textarea>
							<?= $buttons ?>
						</div>
					</div>
					<div class="row">
						<div class="form-group col">
							<label for="conv_utf8bytes">Bytes: ( <span class="example">F0 9F 98 83 E2 88 9A CF 80 21</span> )</label>
							<textarea id="conv_utf8bytes" class="form-control bytes" data-bs-theme="light"></textarea>
							<button type="button" class="btn btn-sm btn-warning hash_raw" title="Hash the bytes as raw bytes">Hash Bytes</button>
							<?= $buttons ?>
						</div>
						<div class="form-group col">
							<label for="conv_utf8cbytes"><abbr title="Escaped">Esc</abbr>.: ( <span class="example">\xf0\x9f\x98\x83\xe2\x88\x9a\xcf\x80\x21</span> )</label>
							<textarea id="conv_utf8cbytes" class="form-control" data-bs-theme="light"></textarea>
							<?= $buttons ?>
						</div>
					</div>
					<div class="row">
						<div class="form-group col">
							<label for="conv_utf8htmldec"><abbr title="HyperText Markup Language">HTML</abbr> Decimal <abbr title="Numerical Character Reference">NCR</abbr>: ( <span class="example">&amp;#128515;&amp;#8730;&amp;#960;&amp;#33;</span> )</label>
							<textarea id="conv_utf8htmldec" class="form-control" data-bs-theme="light"></textarea>
							<?= $buttons ?>
						</div>
						<div class="form-group col">
							<label for="conv_utf8htmlhex"><abbr title="HyperText Markup Language">HTML</abbr> Hex <abbr title="Numerical Character Reference">NCR</abbr>: ( <span class="example">&amp;#x1F603;&amp;#x221A;&amp;#x3C0;&amp;#x21;</span> )</label>
							<textarea id="conv_utf8htmlhex" class="form-control" data-bs-theme="light"></textarea>
							<?= $buttons ?>
						</div>
					</div>
					<div class="row">
						<div class="form-group col">
							<label for="conv_utf8esc">Escaped Unicode: ( <span class="example">\u1F603\u221A\u3C0\u21</span> )</label>
							<textarea id="conv_utf8esc" class="form-control" data-bs-theme="light"></textarea>
							<?= $buttons ?>
						</div>
						<div class="form-group col">
							<label for="conv_utf8code">Code Point: ( <span class="example">U+1F603 U+221A U+3C0 U+21</span> )</label>
							<textarea id="conv_utf8code" class="form-control" data-bs-theme="light"></textarea>
							<?= $buttons ?>
						</div>
					</div>
				</div>
			</section>

			<h2><abbr title="Internet Protocol">IP</abbr> Conversions</h2>

			<section id="ip_conv" class="card">
				<div class="card-body">
					<div class="row">
						<div id="ipv4_wrap" class="form-group col col-4">
							<h5>IPv4</h5>

							<label for="ipv4_text" class="form-label">IP Address: ( <span class="example">192.168.1.1</span> )</label>
							<input id="ipv4_text" type="text" class="form-control mono" data-bs-theme="light">

							<label for="ipv4_dec" class="form-label">Decimal (long) ( <span class="example">3232235777</span> ):</label>
							<input id="ipv4_dec" type="text" class="form-control mono" data-bs-theme="light">

							<label for="ipv4_hex" class="form-label">Hexadecimal ( <span class="example">C0 A8 01 01</span> ):</label>
							<input id="ipv4_hex" type="text" class="form-control mono" data-bs-theme="light">
						</div>
						<div id="ipv6_wrap" class="form-group col col-8">
							<h5>IPv6</h5>

							<label for="ipv6_text" class="form-label">IP Address: <button id="ipv6_text_toggle" class="clear btn btn-sm btn-tiny btn-secondary">Toggle Compression</button></label>
							<input id="ipv6_text" type="text" class="form-control mono" data-bs-theme="light">

							<label for="ipv6_dec" class="form-label">Decimal: <button id="ipv6_dec_toggle" class="clear btn btn-sm btn-tiny btn-secondary">Toggle Compression</button></label>
							<input id="ipv6_dec" type="text" class="form-control mono" data-bs-theme="light">

							<label for="ipv6_rfc1924" class="form-label"><abbr title="Request For Comments">RFC</abbr> 1924:</label>
							<input id="ipv6_rfc1924" type="text" class="form-control mono" data-bs-theme="light">
						</div>
					</div>
				</div>
			</section>

			<h2>Links</h2>

			<section id="links" class="card">
				<div class="card-body">
					<ul class="list-unstyled">
						<li><a href="https://iohelix.net/misc/lat_long.php" target="_blank" class="btn btn-info" title="Convert Latitude and Longitude values between different formats">Latitude Longitude Format Converter</a></li>
						<li><a href="https://jwt.io/" target="_blank" class="btn btn-info" title="Encode and Decode JSON Web Tokens"><abbr title="JSON Web Token">JWT</abbr> Encode/Decode</a></li>
						<li><a href="https://www.freeformatter.com/json-escape.html" target="_blank" class="btn btn-info"><abbr title="JavaScript Object Notation">JSON</abbr> String Escape/Unescape</a></li>
						<li><a href="https://regex101.com/" target="_blank" class="btn btn-info" title="Create and test regular expressions"><abbr title="Regular Expression">Regex</abbr> 101</a></li>
						<li><a href="https://icyberchef.com/" target="_blank" class="btn btn-info" title="Highly customizable conversion tool">Cyber Chef</a></li>
						<li><a href="https://cryptii.com/" target="_blank" class="btn btn-info" title="Modular conversion, encoding, and encryption online">cryptii</a></li>
						<li><a href="https://ijmacd.github.io/rfc3339-iso8601/" target="_blank" class="btn btn-info" title="RFC 3339 vs ISO 8601 vs HTML">Date formats</a></li>
					</ul>
				</div>
			</section>

		</div>

<!--
		<div class="col">
			<h2>Inverted Color</h2>

			<section id="color" class="card">
				<div class="card-body">
					<div class="row">
						<div class="form-group col">
							<label for="color_dec">Decimal:</label>
							<textarea id="color_dec" class="form-control color" data-bs-theme="light"></textarea>
							<?= $buttons ?>
						</div>
						<div class="form-group col">
							<label for="color_hex">Hexadecimal:</label>
							<textarea id="color_hex" class="form-control color" data-bs-theme="light"></textarea>
							<?= $buttons ?>
						</div>
					</div>
				</div>
			</section>
		</div>
-->

	</div>

	<div class="row" id="hashes">
		<div class="col table-responsive">
			<h2>Hashes</h2>
			<section class="card">
				<div class="card-body">
					<div class="form-group">
						<form method="post" action="<?= $_SERVER['SCRIPT_NAME'] ?>#hashes">
						<label for="hash_value">Input String:</label> <label for="hash_raw"><input type="checkbox" name="hash_raw" id="hash_raw" <?= ($_REQUEST['hash_raw'] ?? false) ? 'checked="checked"' : '' ?>> Hash Raw Bytes</label>
						<textarea id="hash_value" name="hash_value" class="form-control" data-bs-theme="light"><?= $_REQUEST['hash_value'] ?? '' ?></textarea>
						<button type="button" class="btn btn-sm btn-success hash_form">Submit</button>
						<button type="button" class="send btn btn-sm btn-secondary">Send to 'Raw'</button>
						</form>
					</div>
					<table class="table table-sm">
						<thead>
							<tr style="border-bottom: 1px solid #999;">
								<th>Algorithm</th>
								<th>Hash</th>
							</tr>
						</thead>
						<?php foreach ($algos as $algo) { ?>
							<?php
								$algoname = slug($algo);
								$bad = ['md4', 'md5', 'sha1', 'ripemd128', 'crc32', 'haval128,3'];
								$ok = ['sha256', 'sha512'];
								$good = ['sha3-512'];
								$class = '';
								if (in_array($algo, $bad)) {
									$class = ' class="table-danger"';
								}
								elseif (in_array($algo, $ok)) {
									$class = ' class="table-warning"';
								}
								elseif (in_array($algo, $good)) {
									$class = ' class="table-success"';
								}
							?>
						<tr<?= $class ?>>
							<th><?= $algo ?></th>
							<td id="hash_<?= $algoname ?>" class="hash_out"><?= $hashes[$algo] ?></td>
						</tr>
						<?php } ?>
					</table>
				</div>
			</section>
		</div>
	</div>

	<div class="row" id="ruler_box">
		<div class="col">
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
				<div id="divInput" class="row">
					<span class="col"><label for="mm">MM:</label><input id="mm" type="text" onchange="r.calc(this);" onkeyup="r.calc(this);" placeholder="100" title="millimeter" class="form-control"></span>
					<span class="col"><label for="cm">CM:</label><input id="cm" type="text" onchange="r.calc(this);" onkeyup="r.calc(this);" placeholder="10" title="centimeter" class="form-control"></span>
					<span class="col"><label for="inch">Decimal Inch:</label><input id="inch" type="text" onchange="r.calc(this);" onkeyup="r.calc(this);" placeholder="3.94" title="decimal inch" class="form-control"></span>
					<span class="col"><label for="finch">Fractional Inch:</label><input id="finch" type="text" onchange="r.calc(this);" onkeyup="r.calc(this);" placeholder="3 15/16" title="fractional inch" class="form-control"></span>
				</div>
				<div class="row">
					<div class="col-3">
						<label for="fractions">Smallest Graduations:</label>
						<select id="fractions" onchange="r.draw();" class="form-control">
							<option value="8">1/8"</option>
							<option value="16" selected="selected">1/16"</option>
							<option value="32">1/32"</option>
							<option value="64">1/64"</option>
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
					<li>3.94 in &times; 25.4 = 100.076 mm</li>
					<li>3 15/16 in = 3.9375 in</li>
				</ul>
			</div>
		</div>
	</div>
	<div style="height:100px;">&nbsp;</div>
</div>

<script>
	// instantiate the ruler
	if ( ! r) {
		r = new Ruler();
	}
	r.draw();
</script>

<script src="assets/process.js"></script>

</body>
</html>
