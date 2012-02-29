<?php
require_once(dirname(__FILE__).'/../classes/utils/autoload.php');
require_once(dirname(__FILE__).'/../classes/utils/utils.php');
require_once(dirname(__FILE__).'/testutils.php');

require_once(dirname(__FILE__).'/utils/alltests.php');
require_once(dirname(__FILE__).'/widgets/alltests.php');
require_once(dirname(__FILE__).'/controllers/alltests.php');
require_once(dirname(__FILE__).'/model/alltests.php');
require_once(dirname(__FILE__).'/base/alltests.php');
require_once(dirname(__FILE__).'/pc/alltests.php');

AutoLoad::path(dirname(__FILE__).'/../classes/exceptions/');
AutoLoad::path(dirname(__FILE__).'/../classes/base/access_providers/allowall.php');

Utils::define('ACCESS_PROVIDER', 'AllowAll');
Utils::define('MONGO_CONNECTION_PARAMS', 'mongodb://localhost:27017/dbtest');
Utils::define('LANGUAGE_CLASS', 'LanguageStub');

class AllTests {
	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite;
		$suite->addTestSuite("Utils_AllTests");
		$suite->addTestSuite("Widgets_AllTests");
		$suite->addTestSuite("Controllers_AllTests");
		$suite->addTestSuite("Base_AllTests");
		return $suite;
	}
}
?>
