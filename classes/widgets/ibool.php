<?php
AutoLoad::path(dirname(__FILE__) . '/iinput.php');
class iBool extends iInput {
	protected $_sql_statement = 'BOOL';

	public function &set($value) { 
		$this->_value = (bool)$value;
		return $this;
	}
	
	public function get() { 
		return (bool)$this->_value;
	}	

	public function check() {
		return true;
	}

	public function __toString() {
		return $this->_value ? '1' : '0';
	}
	
	public function _init_db() {
		if (!$this->_sql_statement)
			return '';

		$statement = $this->_sql_statement;

		
		$statement .= ' NOT NULL DEFAULT '.($this->_value ? '1' : '0');

		return $statement;
	}	
}
?>
