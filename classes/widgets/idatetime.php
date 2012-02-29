<?php
AutoLoad::path(dirname(__FILE__) . '/idate.php');
Utils::define('DATETIME_FORMAT', '%Y-%m-%d %H:%M:%S');
Utils::define('DB_DATETIME_FORMAT', '%Y-%m-%d %H:%M:%S');
class iDateTime extends iDate {
	protected $_sql_statement = 'DATETIME';

	protected $_format = null;
	protected $_db_format = null;

	public function __construct() { 
		parent::__construct();
		$this->_format = DATETIME_FORMAT;
		$this->_db_format = DB_DATETIME_FORMAT;
	}
}
?>
