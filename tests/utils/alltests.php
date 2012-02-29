<?php
class Utils_AllTests {
	public static function suite() {
		return TestUtils::createSuiteFromPath('Utils tests', (array) dirname(__FILE__));
	}
}
?>
