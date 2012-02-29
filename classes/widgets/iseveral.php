<?php
AutoLoad::path(dirname(__FILE__).'/iselect.php');

class iSeveral extends iSelect {
	protected $_value = array();

	public function get() {
		//if the widget was represented as multiple checkboxes
		//there can be no $_REQUEST variable for it
		if (isset($_REQUEST[get_class($this->_owner)]) && (null === $this->_value))
			return array();
		else
			return parent::get();
	}

	public function check() {
		$valid_values = array();
		foreach ($this->values() as $id => $obj) 
			$valid_values[] = $id;

		if (
			(is_array($this->_value) && !array_diff($this->_value, $valid_values)) || 
			!$this->_value
		)
			return true;

		$this->error(_('Invalid selection'));

		return false;
	}
	
	public function _draw() {
		if (!$this->_value)
			$this->_value = array();

		$selected = array_map('strval', $this->_value);
		$result = XWidget::_draw();
		$result['items'] = array();

		foreach ($this->values() as $id => $value) { 

			$node = $canvas->ownerDocument->createElement('item', $value);
			$node->setAttribute('id', $id);

			$item = array(
				':attributes' => array('id' => $id),
				':text' => strval($value)
			);

			if ($selected && in_array($id, $selected)) 
				$item[':attributes']['selected'] = 1;

			$result['items'][] = $item;
		}

		return $result;
	}

	public function &set($value) { 
		$selected = array();
		if (is_a($value, 'ModelIterator')) {
			foreach ($value as $id => $instance)  
				$selected[] = $id;
			
			$value = $selected;
		}	elseif (is_string($value) && strlen($value))
			$value = explode(',', $value);
		elseif (null === $value)
			$value = array();

		return parent::set($value);
	}

	public function __toString() {
		$strings = array();

		if (!$this->_value)
			return '';

		foreach ($this->values() as $value => $name) 
			if (in_array($value, $this->_value)) 
				$strings[] = $value;

		return join(',', $strings);
	}
}
?>
