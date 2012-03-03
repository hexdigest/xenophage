<?php
AutoLoad::path(dirname(__FILE__).'/sqlexception.php');
AutoLoad::path(dirname(__FILE__).'/connectors/');

class SQLConnector {
	const INDEX = 0;
	const INDEX_PRIMARY = 1;
	const	INDEX_UNIQUE = 2;
	const INDEX_FOREIGN_KEY = 4;

	protected	$_connector = null;
	protected static $connectors = array(); //cached connections

	public function __construct($URI) { 
		$connector_hash = md5($URI);

		if (! isset(self::$connectors[$connector_hash])) {
			$params = parse_url($URI);

			if ($params['scheme']) {
				$connector_class = $params['scheme'].'connector';
				self::$connectors[$connector_hash] = new $connector_class($params);	
			} else
				throw new SQLException(SQLException::INVALID_CONNECTOR_PARAMS);
		}

		$this->_connector = self::$connectors[$connector_hash];
	}

	/**
	* Delegate all calls to the actual connector
	*/
	public function __call($method, $params) {
		return call_user_func_array(array($this->_connector, $method), $params);
	}
}
?>
