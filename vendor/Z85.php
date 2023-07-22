<?php

require_once dirname(__FILE__) . '/Base85.php';

class Z85 extends base85 {

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

}
