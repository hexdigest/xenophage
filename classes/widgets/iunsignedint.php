<?php
AutoLoad::path(dirname(__FILE__) . '/iint.php');
class iUnsignedInt extends iInt {
	protected $_sql_statement = 'INT UNSIGNED';
	
	public function check() {
		if (!parent::check()) 
			return false;

		if (intval($this->_value) < 0) 
			return $this->error(_('Value must be greater than or equal to 0'));
		else
			return true;
	}
}
?>
