<?php
AutoLoad::path(dirname(__FILE__) . '/istring.php');
class iURI extends iString {
	protected $_protocols = array();
	
	/**
	* new iURI('http','https'); 
	* where 'http' and 'https' - supported protocols
	*/
	public function __construct() { 
		parent::__construct();
		$this->_protocols = func_get_args();
	}

	public function check() { 
		if ($this->_protocols) {
			if (preg_match('/^('.join('|', $this->_protocols).')://.*$/ui', $this->_value))
				return true;	

			return $this->error(_('Invalid URI'));
		}

		return parent::check();
	}
}
?>
