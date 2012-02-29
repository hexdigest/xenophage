<?php
AutoLoad::path(dirname(__FILE__) . '/istring.php');
Utils::define('PASSWORD_HASH_FUNC', 'sha1');

class iPassword extends iString {
	protected $_length = null;

	public function __construct() {
		parent::__construct();

		$this->_length = strlen(call_user_func(PASSWORD_HASH_FUNC, 'hash me')); 
		//suppose password hash func always generate fixed-length hashes
		$this->_sql_statement = 'CHAR('.$this->_length.')';
	}

	public function __toString() { 
		//if value is already hashed
		if (strlen($this->_value) == $this->_length)
			return $this->_value;

		return call_user_func(PASSWORD_HASH_FUNC, $this->_value);
	}
}
?>
