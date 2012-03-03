<?php
/**
* Autoload subsystem provides path() function that can be used instead
* of native PHP require functions family. The goal is to provide better 
* perfomance while keepeng ability of developer to see requirements of each
* source file. 
*/
class AutoLoad {
	protected static $files = array();

	/**
	* Register required file name or dir in the autoload list
	* @param string $filename - file to load
	*/

	public static function path($filename) {
		if (file_exists($filename)) {
			if (is_dir($filename)) {
				$dirname = $filename;
				$dir = new DirectoryIterator($dirname);
				foreach ($dir as $file) 
					self::addFile($dirname.$file);
			} else {
				self::addFile($filename);
			}
		} else {
			if ('cli' == php_sapi_name())
				$eol = PHP_EOL;
			else
				$eol = '<br/>';

			$message = 'Path: "'.$filename.'" not found';
			echo $message, $eol;
			throw new Exception($message);
		}
	}

	/**
	* Add file with exact filename
	*/
	public static function addFile($fileName) {
		if (is_file($fileName)) {
			$chunks = explode(".", strtolower(basename($fileName)));
			$extension = array_pop($chunks);
			$className = implode('.', $chunks);

			if ($extension === 'php' && !isset(self::$files[$className])) {
				self::$files[$className] = $fileName;
				if ('cli' == php_sapi_name()) {
					require_once($fileName);
				}
			}
		}
	}

	/**
	* Load source files from given class
	* @param string $classname - class to load
	*/
	public static function loadClass($className) { 
		$lName = strtolower($className);

		if (array_key_exists($lName, self::$files)) 
			require_once(self::$files[$lName]);
	}
}

class AutoLoadException extends Exception {
	protected $classname = null;
	
	public function __construct($classname) { 
		parent::__construct('Class '.$classname.' could not be found');

		$this->classname = $classname;
	}

	public function getClass() {
		return $this->classname;
	}
}

spl_autoload_register(array('AutoLoad', 'loadClass'));
?>
