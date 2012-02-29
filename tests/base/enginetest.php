<?php
AutoLoad::path(dirname(__FILE__).'/../../classes/base/engine.php');
AutoLoad::path(dirname(__FILE__).'/../../classes/base/abstractcontroller.php');

class EngineTestController extends AbstractController {
	public function testAction($params) { 
		$this->params = $params;
	}
}

class EngineTest extends PHPUnit_Framework_TestCase {
	public function setUp() { 
		URLDispatcher::registerRule('/test/(\w+?)/', array(
			'controller' => 'EngineTestController',
			'action' => 'testAction',
			'view' => 'xslte://'.dirname(__FILE__).'/template_engines/templateenginetest.xsl',
			'params' => array(
				'params' => new URLParam(1)
			)
		));

		$this->engine = new Engine;
		$this->engine->session->create();
		$this->engine->session->clientId = '123';
	}

	public function tearDown() { 
		URLDispatcher::$rules = array();
	}

	public function testRunController() { 
		$this->engine->runController('EngineTestController', 'testAction', array('params' => array(1, 2, 3)));

		$actual = $this->engine->_draw();
		$actual = $actual['controllers'];
		
		$expected = array(
			array(
				'attribute:class' => 'EngineTestController',
				'params' => array(
					array(
						'attribute:id' => 0,
						'text:' => 1
					),
					array(
						'attribute:id' => 1,
						'text:' => 2
					),
					array(
						'attribute:id' => 2,
						'text:' => 3
					)
				),

				'attribute:action' => 'testAction'
			)
		);

		$this->assertEquals($expected, $actual);
	}

	public function testRunEngine() { 
		$_SERVER['REQUEST_URI'] = '/test/somevalue/';

		ob_start();
		$this->engine->run();
		ob_end_clean();


		$expected0 = array(
			'attribute:class' => 'EngineTestController',
			'params' => 'somevalue',
			'attribute:action' => 'testAction'
		);
		$expected1 = array(
			'attribute:class' => 'TestRunFromTemplateController',
			'params' => 'parav value passed from template',
			'attribute:action' => 'testAction'
		);
		$result = $this->engine->_draw();

		$this->assertEquals($expected0, $result['controllers'][0]);
		$this->assertEquals($expected1, $result['controllers'][1]);
	}
}
?>
