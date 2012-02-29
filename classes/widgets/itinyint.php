<?php
AutoLoad::path(dirname(__FILE__) . '/iint.php');
class iTinyInt extends iInt {
	protected $_sql_statement = 'TINYINT';
}
?>
