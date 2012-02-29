<?php
AutoLoad::path(dirname(__FILE__) . '/iinput.php');
class iTime extends iInput {
	protected $_sql_statement = 'TIME';

	protected $_format = null;
	protected $_db_format = null;

	public function __construct() { 
		parent::__construct();
		$this->_format = XEN_TIME_FORMAT;	
	}
	
	public function check() {
		if (!parent::check())
			return false;
		
		if ($this->_optional && !$this->_value)
			return true;

		$date_is_valid = strtotime($this->_value);
		
		if (!$date_is_valid)
			return $this->error(_('Invalid time'));

		return true;
	}
}
?>
