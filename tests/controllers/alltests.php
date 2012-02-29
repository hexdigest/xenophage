<?php
require_once(dirname(__FILE__).'/../testutils.php');

class Controllers_AllTests {
	public static function suite() {
		return TestUtils::createSuiteFromPath('Controllers tests', (array) dirname(__FILE__));
	}
}
?>
