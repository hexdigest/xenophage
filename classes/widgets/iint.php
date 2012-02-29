<?php
AutoLoad::path(dirname(__FILE__).'/inumeric.php');

class iInt extends iNumeric {
	protected $_sql_statement = 'INT';
	
	public function check() {
		if (!parent::check()) 
			return false;

		if (strpos($this->_value, '.')) 
			return $this->error(_('Value must be integer'));
		else
			return true;
	}

	public function & set($value) { 
		if (! is_numeric($value))
			$this->_value = null;
		else
			$this->_value = intval($value);

		return $this;
	}
}
?>
