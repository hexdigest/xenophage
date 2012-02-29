<?php
AutoLoad::path(dirname(__FILE__).'/../../classes/base/urldispatcher.php');

class URLDispatcherTest extends PHPUnit_Framework_TestCase {
	public function setUp() { 
		URLDispatcher::registerRule('/help/', array(
			'controller' => 'PageViewer',
			'action' => 'showPage',
			'params' => array(
				'path' => '/pages/help/index'
			)
		));

		URLDispatcher::registerRule('/page/([\w_]+)/([\w_]+)/', array(
			'controller' => 'PageViewer',
			'action' => 'showPage',
			'params' => array(
				'param1' => new URLParam(1),
				'param2' => new URLParam(2)
			)
		));

		URLDispatcher::registerRule('/json/([\w_]+)/([\w_]+)/', array(
			'controller' => new URLParam(1),
			'action' => new URLParam(2),
			'params' => $_GET
		));
	}

	public function tearDown() { 
		URLDispatcher::$rules = array();
	}

	public function testGetRuleByURL() { 
		$result = URLDispatcher::getRuleByURL('/help/');
		$expected = array(
			'controller' => 'PageViewer',
			'action' => 'showPage',
			'params' => array(
				'path' => '/pages/help/index'
			)
		);
		$this->assertEquals($expected, $result);

		$result = URLDispatcher::getRuleByURL('/json/URLDispatcherTest/testGetRuleByURL/');
		$expected = array(
			'controller' => 'URLDispatcherTest',
			'action' => 'testGetRuleByURL',
			'params' => array()
		);
		$this->assertEquals($expected, $result);

		$expected = array(
			'controller' => 'PageViewer',
			'action' => 'showPage',
			'params' => array(
				'param1' => '_value1_',
				'param2' => '_value2_'
			)
		);
		$result = URLDispatcher::getRuleByURL('/page/_value1_/_value2_/');
		$this->assertEquals($expected, $result);
	}

	public function testReplaceBracketsWithValues() {
		$result = URLDispatcher::replaceBracketsWithValues('/home\/(\w+)\/(\d+)\/', array(
			1 => 'hello',
			2 => 'world'
		));

		$this->assertEquals('/home/hello/world/', $result);
	}

	public function testGetURLByRule() { 	
		$result = URLDispatcher::getURLByRule(array(
			'controller' => 'PageViewer',
			'action' => 'showPage',
			'params' => array(
				'path' => '/pages/help/index'
			)
		));
		$this->assertEquals('/help/', $result);

		$result = URLDispatcher::getURLByRule(array(
			'controller' => 'PageViewer',
			'action' => 'showPage',
			'params' => array(
				'param1' => 'hello',
				'param2' => 'world'
			)
		));
		$this->assertEquals('/page/hello/world/', $result);

		$result = URLDispatcher::getURLByRule(array(
			'controller' => 'SomeController',
			'action' => 'SomeAction',
			'params' => array(
				'p1' => 'hello',
				'p2' => 'world'
			)
		));

		$expected = '/json/SomeController/SomeAction/?p1=hello&p2=world';
		$this->assertEquals($expected,$result);
	}
}
?>
