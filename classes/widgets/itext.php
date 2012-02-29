<?php
AutoLoad::path(dirname(__FILE__) . '/istring.php');
class iText extends iString {
	public function __construct($check_condition = null) { 
		parent::__construct($check_condition);
	}

	public function _draw() { 
		$result = parent::_draw($canvas);

		if ($this->_max_length)
			$result[':attributes']['max_length'] = $this->_max_length;

		return $result;
	}
}
?>
