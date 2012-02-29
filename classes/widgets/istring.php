<?php
AutoLoad::path(dirname(__FILE__).'/iinput.php');

class iString extends iInput {
	protected $_sql_statement = 'CHAR(255)';
	protected $_max_length = 0;

	public function & set($value) { 
		if (null === $value)
			$this->_value = null;
		else
			$this->_value = strval($value);

		return $this;
	}

	public function __construct($length = null) { 
		parent::__construct();
		
		if ($length) {
			$this->_max_length = $length;
			$this->_sql_statement = 'CHAR('.$this->_max_length.')';
		}
	}

	public function check() {
		if (!parent::check())
			return false;
	
		if (!$this->_optional && !strlen(trim($this->_value)))
			return false;
			
		if ($this->_max_length && (strlen($this->_value) > $this->_max_length)) 
			return $this->error('Max length is %s characters', $this->_max_length);
		
		return true;
	}
}
?>
