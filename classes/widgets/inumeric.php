<?php
AutoLoad::path(dirname(__FILE__).'/iinput.php');

class iNumeric extends iInput {
	protected $_sql_statement = 'DOUBLE';
	protected $_min = null;
	protected $_max = null;

	public function __construct($min = null, $max = null) { 
		parent::__construct();
		
		$this->_min = $min;
		$this->_max = $max;
	}
	
	public function & set($value) {
		if (is_string($value))
			$value = str_replace(',', '.', $value);
		if (! is_numeric($value))
			$this->_value = null;
		else
			$this->_value = doubleval($value);

		return $this;
	}
	
	public function check() {
		$parent_check = parent::check();

		if ($parent_check && !strlen($this))
			return true;

		if (!is_numeric($this->_value)) 
			return $this->error(__('NOT_A_NUMBER'));	//Введенное значение не является числом
		elseif (
			(null !== $this->_min && $this->_value < $this->_min) || 
			(null !== $this->_max && $this->_value > $this->_max)
		)
			return $this->error(__('OUT_OF_RANGE'));	//За границами допустимого диапазона

		return $parent_check;
	}
}
?>
