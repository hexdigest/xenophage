<?php
AutoLoad::path(dirname(__FILE__).'../../classes/utils/utils.php');

class UtilsTest extends PHPUnit_Framework_TestCase {
	public function testParseURL() { 
		$url = 'domxslt:///path/to/file.xsl';
		$expected = array(
			'scheme' => 'domxslt',
			'host' => '',
			'path' => '/path/to/file.xsl'
		);	
		$result = Utils::parseURL($url);
		$this->assertEquals($expected, $result);

		$url = 'http://user:password@host.name/path/to/file.xml?param=value';
		$expected = array(
			'scheme' => 'http',
			'host' => 'host.name',
			'user' => 'user',
			'pass' => 'password',
			'path' => '/path/to/file.xml',
			'query' => 'param=value'
		);
		$result = Utils::parseURL($url);
		$this->assertEquals($expected, $result);

	}

	public function testGetFromURL() {
		$file = __FILE__;
		$this->assertEquals(file_get_contents($file), Utils::getFromURL($file), "Local file simple path");
		$this->assertEquals(file_get_contents($file), Utils::getFromURL('file://'.$file), "Local file scheme path");
	}

	public function testSprintfa() { 
		$result = Utils::sprintfa('%(hello)s, %(world)s!', array('world' => 'Universe', 'hello' => 'Hi'));
		$expected = 'Hi, Universe!';

		$this->assertEquals($expected, $result);

		$expected = '002,3.25';
		$result = Utils::sprintfa('%(integer)03d,%(float).2f', array('integer' => 2, 'float' => 3.245));
		$this->assertEquals($expected, $result);
	}
}
?>
