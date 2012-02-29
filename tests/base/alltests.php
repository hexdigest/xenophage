<?php
class Base_AllTests {
	public static function suite() {
		return TestUtils::createSuiteFromPath(
			'Base tests', 
			array(
				dirname(__FILE__),
				dirname(__FILE__).DIRECTORY_SEPARATOR.'/template_engines/',
				dirname(__FILE__).DIRECTORY_SEPARATOR.'/persistence/',
				));
	}
}
?>
