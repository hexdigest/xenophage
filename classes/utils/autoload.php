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
		}
	}

	/**
	* Add file with exact filename
	*/
	public static function addFile($filename) {
		if (is_file($filename)) {
			$chunks = explode(".", strtolower(basename($filename)));
			$extension = array_pop($chunks);
			$name = implode('.', $chunks);

			if ($extension === 'php' && !isset(self::$files[$name])) {
				self::$files[$name] = $filename;
				if ('cli' == php_sapi_name()) {
					require_once($filename);
				}
			}
		}
	}

	/**
	* Load source files from given class
	* @param string $classname - class to load
	*/
	public function loadClass($classname) { 
		$result = false;
		if ($classname) { 
			$classname = strtolower($classname);
			if (array_key_exists($classname, self::$files)) {
				require_once(self::$files[$classname]);
				$result = (class_exists($classname) || interface_exists($classname));
			}
		}

		return $result;
	}
}

class AutoloadException extends Exception {
	protected $classname = null;
	
	public function __construct($classname) { 
		parent::__construct('Class '.$classname.' could not be found');

		$this->classname = $classname;
	}

	public function getClass() {
		return $this->classname;
	}
}

function __autoload($classname) {
	if (! AutoLoad::loadClass($classname)) 
		throw new AutoloadException($classname);
}
?>
