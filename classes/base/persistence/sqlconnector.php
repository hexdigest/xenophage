<?php
//Require all connectors we have
require_auto(XENOPHAGE . '/classes/connectors/');

/* изжить эти глобальные константы после удаления из всех проектов */

define('SQL_ERROR_DUPLICATE_ENTRY', 1); 
define('SQL_ERROR_TABLE_DOES_NOT_EXIST', 2); 
 
define('SQL_INDEX', 0); 
define('SQL_INDEX_PRIMARY', 1); 
define('SQL_INDEX_UNIQUE', 2); 
define('SQL_INDEX_FOREIGN_KEY', 4);

class SQLException extends Exception {
	const DUPLICATE_ENTRY = 1;
	const TABLE_DOES_NOT_EXIST = 2;
}

class SQLConnector {
	const SQL_ERROR_DUPLICATE_ENTRY = 1; //вынести в SQLException
	const SQL_ERROR_TABLE_DOES_NOT_EXIST = 2; //вынести в SQLException

	const SQL_INDEX = 0;
	const SQL_INDEX_PRIMARY = 1;
	const	SQL_INDEX_UNIQUE = 2;
	const SQL_INDEX_FOREIGN_KEY = 4;

	public $_connector = null;

	public function __construct($URI) { 
		$params = parse_url($URI);

		if (!$params['scheme'])
			throw new SQLException('SQL connector is not specified');
		
		$connector_class = $params['scheme'].'connector';
		
		$this->_connector = new $connector_class($params);
	}

	public function __destruct() { 
		unset($this->_connector);
	}

	public function __call($method, $params) {
		return call_user_func_array(array($this->_connector, $method), $params);
	}

	public static function assoc_to_where($array) {
		if ($array)
			return '"'.implode('" AND "',array_map('addslashes', $array)).'"';

		return '';
	}

	public function __clone() {
		if (get_class($this) == __CLASS__) {
			$this->_connector = clone $this->_connector;
			$this->_connector->reset();
		}
	}
}
?>
