<?php

	// find out what caused the window[funcName] to fail saying that window[funcName] was not a function

	// `7B EA` does weird things when switching between UTF-8 bytes, and hexadecimal

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

require_once 'ajax.php';

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
	<!-- PHP Version: <?= phpversion() ?> -->

	<div id="ip">
		Your IP: <span><?= $_SERVER['REMOTE_ADDR']; ?></span><br>
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
							<label for="conv_base64">Base64: ( <span class="example">+</span> and <span class="example">/</span> )
								<label for="b64url">
									<input type="checkbox" id="b64url">
									<abbr title="Uniform Resource Locator">URL</abbr>:
									( <span class="example">-</span> and <span class="example">_</span> )
								</label>
							</label>
							<textarea id="conv_base64" class="form-control"></textarea>
							<form method="post" style="display:inline;">
								<input type="hidden" name="file" id="file">
								<button type="button" class="btn btn-sm btn-warning file" title="Download File">File</button>
							</form>
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
							<label for="conv_uuencode">UUEncode:</label>
							<textarea id="conv_uuencode" class="form-control"></textarea>
							<?= $buttons ?>
						</div>
						<div class="form-group col-md">
							<label for="conv_puny">Punycode:</label>
							<textarea id="conv_puny" class="form-control"></textarea>
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
							<label for="conv_rot13" class="form-inline">Rot&mdash;<input type="number" id="caesar" class="form-control input-xs" max="26" min="-26" step="1" value="13">
								(Caesar cipher):</label>
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
							<label for="conv_morse">Morse Code:</label>
							<textarea id="conv_morse" class="form-control"></textarea>
							<?= $buttons ?>
						</div>
						<div class="form-group col-md">
							<label for="conv_code">A &rarr; 1:</label>
							<textarea id="conv_code" class="form-control"></textarea>
							<?= $buttons ?>
						</div>
					</div>

				</div>
			</section>

			<h2>Random Things</h2>

			<section id="random" class="card">
				<div class="card-body">
					<form>
						<div class="form-group row">
							<label for="rand_ipv4">IPv4 (for documentation examples)</label>
							<input id="rand_ipv4" class="form-control" type="text" disabled>
						</div>
						<div class="form-group row">
							<label for="rand_ipv6">IPv6 (for documentation examples)</label>
							<input id="rand_ipv6" class="form-control" type="text" disabled>
						</div>
						<div class="form-group row">
							<label for="rand_uuid"><abbr title="Universally Unique IDentifier">UUID</abbr> (<abbr title="Generated with PHP random_bytes function">cryptographically secure</abbr>)</label>

							<input id="rand_uuid" class="form-control" type="text" disabled value="<?= $uuid ?>">
						</div>
					</form>
				</div>
			</section>

		</div>

		<div class="col-md">
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
							<button type="button" class="btn btn-sm btn-warning hash_raw" title="Hash the bytes as raw bytes">Hash Bytes</button>
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
							<button type="button" class="btn btn-sm btn-warning hash_raw" title="Hash the bytes as raw bytes">Hash Bytes</button>
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

			<h2>Links</h2>

			<section id="links" class="card">
				<div class="card-body">
					<ul class="list-unstyled">
						<li><a href="https://jwt.io/" target="_blank" class="btn btn-info" title="Encode and Decode JSON Web Tokens"><abbr title="JSON Web Token">JWT</abbr> Encode/Decode</a></li>
						<li><a href="https://www.freeformatter.com/json-escape.html" target="_blank" class="btn btn-info"><abbr title="JavaScript Object Notation">JSON</abbr> String Escape/Unescape</a></li>
						<li><a href="https://regex101.com/" target="_blank" class="btn btn-info" title="Create and test regular expressions"><abbr title="Regular Expression">Regex</abbr> 101</a></li>
						<li><a href="https://icyberchef.com/" target="_blank" class="btn btn-info" title="Highly customizable conversion tool">Cyber Chef</a></li>
						<li><a href="https://cryptii.com/">cryptii</a></li>
						<li><a href="https://ijmacd.github.io/rfc3339-iso8601/">RFC 3339 and ISO 8601 Date formats</a></li>
						<li><a href="https://iohelix.net/misc/lat_long.php" target="_blank" class="btn btn-info" title="Convert Latitude and Longitude values between different formats">Latitude Longitude Format Converter</a></li>
					</ul>
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
				<form method="post" action="<?= $_SERVER['SCRIPT_NAME'] ?>#hashes">
				<label for="hash_value">Input String:</label> <label for="hash_raw"><input type="checkbox" name="hash_raw" id="hash_raw" <?= ($_REQUEST['hash_raw'] ?? false) ? 'checked="checked"' : '' ?>> Hash Raw Bytes</label>
				<textarea id="hash_value" name="hash_value" class="form-control"><?= $_REQUEST['hash_value'] ?? '' ?></textarea>
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
	// instantiate the ruler
	if ( ! r) {
		r = new Ruler();
	}
	r.draw();
</script>

<script src="assets/process.js"></script>

</body>
</html>
