<?php
AutoLoad::path(dirname(__FILE__).'/xwidget.php');

class MessageBox extends XWidget {
	public function __construct() { 
		parent::__construct();

		$args = func_get_args();
		$this->message = array_shift($args);
		$this->comments = $args;
	}
}
?>
