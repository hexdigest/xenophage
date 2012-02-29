<?php
class TestUtils {
	
	public static function createSuiteFromPath($name, array $paths, array $excludes = array('alltests.php')){
		$suite = new PHPUnit_Framework_TestSuite($name);

		foreach ($paths as $path) { 
			$dir = $path.DIRECTORY_SEPARATOR;
			foreach (scandir($dir) as $file){
				if (preg_match('/.php$/', $file) && !in_array($file, $excludes)){
					$suite->addTestFile($dir.$file);
				}
			}
		}

		return $suite;
	}

}
?>
