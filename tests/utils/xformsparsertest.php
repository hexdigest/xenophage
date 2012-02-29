<?php
AutoLoad::path(dirname(__FILE__).'/../../classes/utils/xformsparser.php');
AutoLoad::path(dirname(__FILE__).'/../../classes/widgets/');

class XFormsParserTest extends PHPUnit_Framework_TestCase {
	public function testGetXMLParams() { 	
		$params = array(
			'param1' => 1,
			'param2' => 2
		);

		$test_string = '<'.'?xml version="1.0" encoding="UTF-8"?'.">\n".
			'<root><param1>1</param1><param2>2</param2></root>'."\n";

		$xml = strval(XFormsParser::getXMLParams($params));
		
		$this->assertEquals($test_string, $xml);
	}

	public function testCheckConstraint() { 
		$params = array(
			'a' => '5',
			'b' => 'foobar',
			'c' => '58'
		);

		$dom = XFormsParser::getXMLParams($params);

		$a = XFormsParser::checkConstraint('. > 4', 'a', $dom);
		$this->assertTrue($a);
		
		$b = XFormsParser::checkConstraint('contains(. , "oo")', 'b', $dom);
		$this->assertTrue($b);

		$c = XFormsParser::checkConstraint('. mod 2 = 0 and . = 58', 'c', $dom);
		$this->assertTrue($c);

		$ac = XFormsParser::checkConstraint('. mod 2 = 0 and ../a = 5', 'c', $dom);
		$this->assertTrue($ac);

		$ac = XFormsParser::checkConstraint('concat(., ../b) = "58foobar"', 'c', $dom);
	}

	public function testConstructor() { 
		$filename = dirname(__FILE__).'/xformsparsertest_article.xml';
		$xp = new XFormsParser(file_get_contents($filename));

		$this->assertTrue($xp instanceof XFormsParser);

		return $xp;
	}

	/**
	* @depends testConstructor
	*/
	public function testGetConstraints(XFormsParser $xp) { 
		$expected = array(
			'phone' => array(
				'constraint' => "string-length(.) = 7 and . > '0000000' and . < '9999999'",
				'alert' => 'Неверный телефон получателя'
			),
			'def' => array(
				'constraint' => '. > 900 and . < 999',
				'alert' => 'Неверный код телефона получателя'
			)
		);

		$this->assertEquals($expected, $xp->getConstraints());
	}

	/**
	* @depends testConstructor
	*/
	public function testGetExpressions(XFormsParser $xp) { 
		$fields = $xp->getExpressions();
		$this->assertEquals(array('account' => 'concat(\'7\', def, phone)'), $fields);

		return $fields;
	}

	/**
	* @depends testGetExpressions
	*/
	public function testCalculateExpressions($expressions) { 
		$params = array(
			'def' => 905, 
			'phone' => 9222822
		);

		$result = XFormsParser::calculateExpressions($params, $expressions);
		$this->assertEquals(array('account' => '79059222822'), $result);

		$result = XFormsParser::calculateExpressions($params, array('test' => 'def + phone'));
		$this->assertEquals(array('test' => 9223727), $result);
	}

	/**
	* @depends testConstructor
	*/
	public function testGetNodesets(XFormsParser $xp) { 
		$params = array(
			'def' => 905, 
			'phone' => 9222822,
			'sum' => 100
		);

		$result = $xp->getNodesets($params);
		$expected = array(
			'account' => '79059222822',
			'sum' => 100
		);
		$this->assertEquals($expected, $result);
	}

}
?>
