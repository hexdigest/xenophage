<?php
AutoLoad::path(dirname(__FILE__) . '/iinput.php');
class iSelect extends iInput {
	protected $_values = array();
	
	/**
	* If more than one argument passed to constructor
	* all these arguments treated as possible values,
	*/
	public function __construct() { 
		parent::__construct();

		$args = func_get_args();

		if (is_object($args[0])) //iterator
			$this->_values = $args[0];
		elseif (count($args) > 1) 
			$this->_values = array_combine($args, $args);
		elseif (self::is_numeric_array($args[0]))
			$this->_values = array_combine($args[0], $args[0]);
		elseif (is_array($args[0]))
			$this->_values = $args[0];
		else
			throw new Exception('Values not given');
	}

	public function values() {
		return $this->_values;
	}

	/**
	* Checks if the value present in array of possible values
	*/
	public function check() {
		if (!parent::check())
			return false;

		if ((!$this->_value) && ($this->_optional))
			return true;

		if (is_object($this->values())) {
			foreach ($this->values() as $obj) 
				if ($obj->id() == $this->_value) 
					return true;
		} 
		elseif (in_array($this->_value, array_keys($this->values())))
			return true;

		return $this->error(_('Unexpected choice'));
	}

	public function _draw() {
		$result = iInput::_draw();
		$result['items'] = array();
		unset($result[':text']);

		foreach ($this->values() as $id => $value) { 
			$item = array(
				':attributes' => array('id' => $id)
			);
			
			if (is_array($value)) {
				$item = array_merge_recursive($item, $value);
			} else {
				$item[':text'] = strval($value);
			}

			if ($id == $this->get())
				$item[':attributes']['selected'] = 1;

			$result['items'][] = $item;
		}

		return $result;
	}
}
?>
