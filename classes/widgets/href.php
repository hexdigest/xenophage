<?php
AutoLoad::path(dirname(__FILE__) . '/xwidget.php');
class HRef extends XWidget {
	protected $_href = null;

	public function __construct($href, $title = null) { 
		parent::__construct();

		if (! $title)
			$title = $href;

		$this->title($title);

		$this->_href = $href;
	}

	public function _draw() { 
		$result = parent::_draw();
		$result[':text'] = $this->_href;

		return $result;
	}
}
?>
