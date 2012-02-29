<?php
AutoLoad::path(dirname(__FILE__).'/iint.php');

class iBigInt extends iInt {
	protected $_sql_statement = 'BIGINT';
}
?>
