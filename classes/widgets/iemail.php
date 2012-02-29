<?php
AutoLoad::path(dirname(__FILE__) . '/istring.php');
class iEmail extends iString {
	public function __construct() { 
		parent::__construct();

		$this->alert(
			'^[A-Za-z0-9-][A-Z_a-z0-9-]*([.][-_A-Za-z0-9]+)*'.
			'@([a-z][.])?[A-Za-z0-9][-_A-Za-z0-9]*[.][a-z]{2,}([.][a-z]{2,})*$',

			__('incorrectly_fild')
		);
	}

	// Если присваивают пустой строке, пусть останется NULL
	public function &set($value) { 
		$value ? parent::set($value) : parent::set(null);
		return $this;
	}
}
?>
