<?php
require_once(dirname(__FILE__).'/../testutils.php');

class Widgets_AllTests {
	public static function suite() {
		return TestUtils::createSuiteFromPath('Widgets tests', (array) dirname(__FILE__));
	}
}
?>
