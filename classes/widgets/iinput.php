<?php
AutoLoad::path(dirname(__FILE__) . '/xwidget.php');

class iInput extends XWidget {
	const ALERT_NOT_MATCH = 0; //raised if input value doesn't match expression
	const ALERT_MATCH = 1; //alert raised if input value match expression
	const ALERT_EVAL = 2; //attach callback function

	protected $_sql_statement = null;
	protected $_value = null;

	protected $_optional = false;
	protected $_error_message = null;
	protected $_readonly;
	protected $_hint = null;
	
	protected $_alerts = array();

	public function get() { 
		return $this->_value;
	}
	
	public function set($value) { 
		$this->_value = $value;

		return $this;
	}
	
	/**
	* Returns true if value is valid, false otherwise
	* Also should generate $this->_error_message
	*/
	public function check() {
		if ((is_array($this->_value) && !$this->_value) || (!is_array($this->_value) && !strlen($this->_value))) {			
			if ($this->_optional)
				return true;
			else
				return $this->error(__('required_field'));
		}

		foreach ($this->_alerts as $alert) { 
			list($ereg, $message, $mode) = $alert;
			if (self::ALERT_EVAL == $mode) {
				try { 
					$value = $this->_value;
					$result = eval('return '.$ereg.';');
					if (!$result)
						return $this->error($message);
				} catch (Mistake $e) {
					return $this->error($message);
				}
			} elseif (preg_match('/'.$ereg.'/sui', $this->_value) == $mode)
				return $this->error($message);
		}
		return true;
	}
	
	/**
	* Raise alerts user when the value of input field does or doesn't 
	* match given ereg
	*/
	public function &alert($ereg, $message = null, $mode = iInput::ALERT_NOT_MATCH) {
		$this->_alerts[] = array($ereg, $message, $mode);

		return $this;
	}

	public function __toString() {
		if (is_array($this->_value))
			return join(',', $this->_value);
		else
			return (string)$this->_value;
	}
	
	public function _init_db() {
		if (!$this->_sql_statement)
			return '';

		$statement = $this->_sql_statement;

		if ($this->_value !== null)
			$statement .= ' NOT NULL DEFAULT "'.$this.'"';

		return $statement;
	}

	public function _draw() {
		$canvas = parent::_draw();
		
		$canvas[':attributes']['baseclass'] = 'iInput';
		$canvas[':text'] = (string) $this;

		if (intval($this->_optional))
			$canvas[':attributes']['optional'] = '1';
		
		if ($this->_hint)
			$canvas[':attributes']['hint'] = $this->_hint;

		if ($this->_readonly) 
			$canvas[':attributes']['readonly'] = $this->_readonly;
			
		foreach ($this->_alerts as $alert) { 
			list($ereg, $message, $mode) = $alert;

			$alert_node = array();
			$alert_node[':attributes']['regex'] = $ereg;
			$alert_node[':attributes']['message'] = $message;
			$alert_node[':attributes']['mode'] = (int)$mode;
			$canvas['alerts'][] = $alert_node;
		}
		
		if ($this->_error_message)
			$canvas[':attributes']['error'] = $this->_error_message;
		return $canvas;
	}

	public function &optional($bool = true) { 
		$this->_optional = $bool;

		return $this;
	}
	
	/**
	* Text shortly descripbes input field
	*/
	public function &hint($hint = null) { 
		if (null === $hint)
			return $this->_hint;

		$this->_hint = $hint;

		return $this;
	}
		
	public function &readonly($flag = null) {
		if (null === $flag)
			return $this->_readonly;

		$this->_readonly = $flag;		
		
		return $this;
	}

	public function &disable($relevant) {
		return $this->_disabled = true;
	}
	

	/**
	* Sets $this->_error_message if $message is given, otherwise return
	* $this->_error_message
	*/
	public function error($message = null) {
		if (null === $message)
			return $this->_error_message;

		$this->_error_message = $message;

		return false;
	}
}
?>
