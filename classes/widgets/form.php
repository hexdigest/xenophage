<?php
AutoLoad::path(dirname(__FILE__) . '/xwidget.php');
abstract class Form extends XWidget {
	protected $_validators = array();

	/**
	* Injects external form validator function
	*/
	public function injectValidator($function, &$params) { 
		$this->_validators[] = array($function, $params);
	}

	public function setParams($params) { 
		$result = false;

		if ($params) {
			foreach ($this->get_nested_widgets('iInput') as $name => $value) 
				if (isset($params[$name]))
					$value->set($params[$name]);

			$result = true;
		}

		return $result;
	}

	/**
	* Loads form data from request and returns form validation result
	*/
	public function submit($request) {
		$result = false;

		if (isset($request[$this->_class]))
			$result = $this->setParams($request[$this->_class]);

		return $result && $this->validate();
	}

	/**
	* Validates form data
	*/
	public function validate() {
		$errors = 0;

		foreach ($this->get_nested_widgets('iInput') as $input) 
			$errors += ! $input->check(); //update error counter

		if (! $errors) {
			foreach ($this->_validators as $validator) { 
				list($function, $params) = $validator;
				$errors += ! call_user_func_array($function, $params);
			}
		}
		
		return (0 === $errors);
	}

	/**
	* Return form data as associative array
	*/
	public function getParams() {
		$params = array();

		foreach ($this->get_nested_widgets('iInput') as $name => $value) {
			if (! $value instanceof iSubmit)
				$params[$name] = $value->get();
		}
		
		return $params;
	}

	public function _draw() { 
		$result = parent::_draw();
		$result[':attributes']['baseclass'] = __CLASS__;

		return $result;
	}
}
?>
