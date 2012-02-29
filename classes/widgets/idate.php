<?php
AutoLoad::path(dirname(__FILE__) . '/iinput.php');
Utils::define('DATE_FORMAT', '%Y-%m-%d');
Utils::define('DB_DATE_FORMAT', '%Y-%m-%d');
class iDate extends iInput {
	protected $_sql_statement = 'DATE';

	protected $_format = null;
	protected $_db_format = null;
	private $value_to_check = null;

	public function __construct() { 
		parent::__construct();
		$this->_format = DATE_FORMAT;
		$this->_db_format = DB_DATE_FORMAT;
	}

	public function set($value) { 
		$this->value_to_check = $value;
		if (!$value) 
			parent::set(null);
		else
			parent::set($this->timestamp($value));
	}
	
	public function get($date_format = null) { 
		if (null === $date_format)
			return parent::get();
		else
			return strftime($date_format, parent::get());
	}

	public function timestamp($value) {
		if (is_numeric($value))
			return intval($value);

		//if the $value given in full ISO-8601 format
		if (strpos($value, 'T')) 
			$value = reset(explode('+', str_replace('T',' ', $value)));

		$a = strptime($value, $this->_format);

		return mktime(
			$a['tm_hour'],
			$a['tm_min'],
			$a['tm_sec'],
			$a['tm_mon'] + 1,
			$a['tm_mday'],
			$a['tm_year'] + 1900
		);
	}
	
	public function check() {
		/*
		 * небольшой хак, чтобы проверялось введенное значение, 
		 * а не timestamp 
		 * */
		parent::set($this->value_to_check);
		
		if (!parent::check())
			return false;
		
		$this->set($this->value_to_check);
		
		if ($this->_optional && !$this->value_to_check)
			return true;

		$a = strptime($this->value_to_check, $this->_format);
		
		$date_is_valid = checkdate(
			$a['tm_mon'] + 1,
			$a['tm_mday'],
			$a['tm_year'] + 1900
		);
		
		if (!$date_is_valid)
			return $this->error(_('Invalid date'));

		return true;
	}
	
	public function __toString() {
		if (null === $this->_value)
			return '';
		else {
			$this->_value = intval($this->_value);
			return strval(strftime($this->_db_format, $this->_value));
		}	
	}

	public function _draw() {
		$result = parent::_draw($canvas);
		
		if (null !== $this->get())
			$result[':text'] = strftime($this->_format, $this->_value);

		$result[':attributes']['format'] = $this->_format;

		return $result;
	}
}
?>
