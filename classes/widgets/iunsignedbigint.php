<?php
AutoLoad::path(dirname(__FILE__) . '/iunsignedint.php');
class iUnsignedBigInt extends iUnsignedInt {
	protected $_sql_statement = 'BIGINT UNSIGNED';

	public function & set($value) { 
		if (is_numeric($value))
			$this->_value = $value;
		else
			$this->_value = null;

		return $this;
	}
}
?>
