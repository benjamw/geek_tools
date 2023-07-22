<?php
class Base85 {

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
				$ret .= $this->chars[$idx];
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

}
