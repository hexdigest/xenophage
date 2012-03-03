<?php
class SQLException extends Exception {
	const INVALID_CONNECTOR_PARAMS = -1;
	const ERROR_LOADING_CHARSET = -2;
	const DUPLICATE_ENTRY = 1;
	const TABLE_DOES_NOT_EXIST = 2;
	const OBJECT_CANT_BE_STORED = 3;

	public function __construct($code, $string = null) { 
		parent::__construct($string, $code);
	}
}
?>
