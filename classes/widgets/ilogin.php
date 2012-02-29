<?php
AutoLoad::path(dirname(__FILE__) . '/istring.php');
class iLogin extends iString {
	public function __construct($length = null) { 
		parent::__construct($length);

		$this->_sql_statement .= ' UNIQUE';
	}
}
?>
