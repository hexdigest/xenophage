<?php
AutoLoad::path(dirname(__FILE__) . '/xwidget.php');

class iGroup extends XWidget {
	protected $_role = null;

	public function __construct($role = null) { 
		parent::__construct();

		$this->_role = $role;
	}

	public function _draw() { 
		$result = parent::_draw();

		if (null !== $this->_role)
			$result[':attributes']['role'] = $this->_role;

		return $result;
	}
}
?>
