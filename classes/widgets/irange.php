<?php
AutoLoad::path(dirname(__FILE__) . '/iinput.php');
class iRange extends iInput {
	
	public function __toString() {
		if(!$this->_value["from"] && !$this->_value["to"])
			return '';
		else 
			return intval($this->_value["from"])." 
				AND ".intval($this->_value["to"]);
	}

	public function _draw() {
		$result = parent::_draw($canvas);
		
		if ($this->_value) {						
			$result[':attributes']['from'] = $this->_value['from'];
			$result[':attributes']['to'] = $this->_value['to'];
		} else {
			$result[':attributes']['from'] = '';
			$result[':attributes']['to'] = '';
		}
		
		return $result;
	}
}
?>
