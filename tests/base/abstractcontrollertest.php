<?php
AutoLoad::path(dirname(__FILE__).'/../../classes/base/abstractcontroller.php');

class NonAbstractController extends AbstractController {
	public function tRedirect($url) { 
		$this->redirect($url);
	}
}

class AbstractControllerTest extends PHPUnit_Framework_TestCase {
	public function testTest() {
		$this->assertTrue(true);
	}

	/**
	* @expectedException RedirectException
	*/
	public function testRedirect() {
		$c = new NonAbstractController;
		$c->tRedirect('/');
	}
}
?>
