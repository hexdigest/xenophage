<?php
AutoLoad::path(dirname(__FILE__).'/../../classes/widgets/xform.php');
AutoLoad::path(dirname(__FILE__).'/../../classes/utils/xformsparser.php');

class XFormsTest extends PHPUnit_Framework_TestCase {
	public function testConstructor() { 
		$filename = dirname(__FILE__).'/../utils/xformsparsertest_article.xml';
		$xml = file_get_contents($filename);

		$xf = new XForm($xml);

		$this->assertInstanceOf('XForm', $xf);

		return $xf;
	}

	/**
	* @depends testConstructor
	*/
	public function testInitialParams(XForm $xf) { 
		$expected = array(
			'def' => null,
			'phone' => null,
			'sum' => 100,
		);

		$params = $xf->getParams();
		$this->assertEquals($expected, $params);
	}

	/**
	* @depends testConstructor
	*/
	public function testStructure($xf) { 
		$expected = array(
			'attribute:class' => 'XForm',
			'attribute:baseclass' => 'Form',
    	'group_0' => array( 
				'attribute:title' => 'Телефон получателя',
				'attribute:class' => 'iGroup',
				'attribute:role' => '',
				'def' => array( 
					'attribute:title' => 'Код телефона',
					'attribute:class' => 'iString',
					'attribute:baseclass' => 'iInput',
					'text:' => '',
					'alerts' => array(array(
						'attribute:regex' => '^[0-9]+$',
						'attribute:message' => 'Неверный код телефона получателя',
						'attribute:mode' => 0
					))
				),

				'phone' => array(
					'attribute:title' => 'Номер телефона',
					'attribute:class' => 'iString',
					'attribute:baseclass' => 'iInput',
					'text:' => '',
					'alerts' => array(array(
						'attribute:regex' => '^[0-9]+$',
						'attribute:message' => 'Неверный телефон получателя',
						'attribute:mode' => 0
					))
				)
			),

			'sum' => array(
				'attribute:title' => 'Сумма',
				'attribute:class' => 'iNumeric',
				'attribute:baseclass' => 'iInput',
				'text:' => 100,
				'alerts' => array(array(
					'attribute:regex' => '^[-+]?[0-9]+(\.|,)?[0-9]*$',
					'attribute:message' => 'Сумма введена неверно',
					'attribute:mode' => 0
				))
			),

			'submit' => Array(
				'attribute:title' => 'Заплатить',
				'attribute:class' => 'iSubmit',
				'attribute:baseclass' => 'iInput',
				'text:' => '0'
			),

			'constraints' => array(
				'phone' => array(
					'constraint' => "string-length(.) = 7 and . > '0000000' and . < '9999999'",
					'alert' => 'Неверный телефон получателя'
				),
				'def' => array(
					'constraint' => '. > 900 and . < 999',
					'alert' => 'Неверный код телефона получателя'
				)
			)
		);

		$result = $xf->_draw();
		$this->assertEquals($expected, $result);
	}

	/**
	* @depends testConstructor
	*/
	public function testValidateConstraints($xf) { 
		$xf->setParams(array(
			'def' => '700',
			'phone' => '3456769',
			'sum' => 100.0
		));

		$result = $xf->validate();
		$this->assertFalse($result);

		$xf->setParams(array(
			'def' => '905',
			'phone' => '456769',
			'sum' => 100.0
		));

		$result = $xf->validate();
		$this->assertFalse($result);

		$xf->setParams(array(
			'def' => '905',
			'phone' => '4567699',
			'sum' => 100.0
		));

		$result = $xf->validate();
		$this->assertTrue($result);
	}
}
?>
