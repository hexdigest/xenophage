<?php
class MySQLiConnector extends SQLConnector {
	protected $mysqli = null;

	protected $_query = null;
	protected $_result = null;
	protected $_count = null;
	protected $_last = null;
	protected $_affected = null;

	public function __construct($params) { 
		if (!$params['path'])
			throw new SQLException(SQLException::INVALID_CONNECTOR_PARAMS);
		
		if (isset($params['port']))
			$params['host'] .= ':'.$params['port'];

		parse_str($params['query'], $vars);

		if ($vars['persistent']) 
			$params['host'] = 'p:' . $params['host'];

		$this->mysqli = new MySQLi(
			$params['host'], 
			$params['user'], 
			$params['pass'], 
			substr($params['path'], 1) //database name
		);

		if ($this->mysqli->connect_error)
			throw new SQLException(
				SQLException::INVALID_CONNECTOR_PARAMS, $this->mysqli->connect_error);

		if ($vars['charset'] && !$this->mysqli->set_charset($vars['charset'])) 
			throw SQLException(SQLException::ERROR_LOADING_CHARSET);
	}
	
	/**
	* Runs SQL query, on non-empty SELECT return $this object
	* on INSERT return LAST_INSERT_ID()
	* otherwise return affected rows count
	*/
	public function query($query) {
		$this->_query = trim($query);

		$r = $this->q($this->_query);

		$result = false;

		if ($r) {
			if (true === $r) { //Non SELECT query
				if (! strcasecmp('INSERT', substr($this->_query, 0, 6))) 
					$result = $this->_last = $this->mysqli->insert_id;
				else 
					$result = $this->_affected = $this->mysqli->affected_rows;
			} else {
				$this->_result = $result;
				$this->_count = $result->num_rows;

				$result = $this;
			}
		}

		return $result;	
	}
	
	public function q($query) {
		$r = $this->mysqli->query($query);
		
		if (! $r) 
			throw new SQLException($this->wrap_code($this->mysqli->errno), 
				$this->mysqli->error);

		return $r;
	}

	public function begin() { 
		$this->mysqli->autocommit(false);
	}
	
	public function commit() { 
		$result = $this->mysqli->commit();
		$this->mysqli->autocommit(true);

		if (! $result)
			throw new SQLException($this->wrap_code($this->mysqli->errno), 
				$this->mysqli->error);
	}

	public function rollback() { 
		$this->mysqli->rollback();
		$this->mysqli->autocommit(false);
	}

	public function a() { 
		return $this->_result->fetch_assoc();
	}
	
	public function r() { 
		return $this->_result->fetch_row();
	}
	
	/**
	* Must return wrapped error code for all database engines
	*/
	public static function wrap_code($error_code) {
		switch ($error_code) {
			case 1062:
				return SQLException::ERROR_DUPLICATE_ENTRY;
			case 1146:
				return SQLException::ERROR_TABLE_DOES_NOT_EXIST;
			default:
				return $error_code;
		}
	}

	public function disconnect() { 
		$this->mysqli->close();
	}

	public function rewind() { 
		if ($this->_count)
			$this->mysqli->data_seek(0);
	}
	
	/**
	* Returns list of $table field names
	*/
	public function fields($table) { 
		$r = $this->q('DESC '.$table);
		$fields = array();

		while ($row = $r->fetch_assoc()) 
			$fields[] = $row['Field'];
		
		return $fields;
	}
	
	public function db_type_cast($value) {
		if (null === $value)
			$str = 'NULL';	
		else
		if (is_object($value)) {
			if (method_exists($value, 'toDatabase')) {
				$str = $value->toDatabase();
				if (null === $str)
					$str = 'NULL';
				else
					$str = '"'.$this->mysqli->real_escape_string($str).'"';
			} else
				throw new SQLException(SQLException::OBJECT_CANT_BE_STORED,
					'Object can\'t be stored');
		} else
		if (is_array($value))  //array maps as enum
			$str = '"'.join('","', array_map(array(
				$this->mysqli, 'real_escape_string'), $value)).'"';
		else
		if (is_bool($value))
			$str = ($value ? 'TRUE' : 'FALSE');
		else
			$str = '"'.$this->mysqli->real_escape_string($value).'"';

		return $str;
	}
	
	/**
	* Creates table for the given model
	*/
	public function create_model_table($model) { 
		$query = 'CREATE TABLE `'.$model->_table.'` (';
		
		if (strcasecmp('XModel', get_class($model)))
			$query .= '`id` SERIAL,';

		$model_indexes = $model->get_indexes();

		$foreign_keys = array();
		if ($model_indexes[self::INDEX_FOREIGN_KEY])
			foreach ($model_indexes[self::INDEX_FOREIGN_KEY] as $foreign_key)  
				$foreign_keys[] = reset($foreign_key);

		foreach ($model as $field => $value) {
			if ($value === null)
				continue;

			if (in_array($field, $foreign_keys))
				$str = 'BIGINT UNSIGNED NOT NULL DEFAULT "' . $value . '"';
			elseif (is_object($value) && method_exists($value, '_init_db'))
				$str = $value->_init_db();
			elseif (is_array($value))  //array maps as enum
				$str = 'ENUM ("'.join('","', array_map(array(
					$this->mysqli, 'real_escape_string'), $value)) . '")';
			elseif (is_string($value))
				$str = 'TEXT NOT NULL DEFAULT "' . $this->mysqli
					->real_escape_string($value) . '"';
			elseif (is_double($value))
				$str = 'DOUBLE NOT NULL DEFAULT "' . $value . '"';
			elseif (is_int($value))
				$str = 'INT NOT NULL DEFAULT "' . $value . '"';
			elseif (is_bool($value))
				$str = 'BOOL NOT NULL DEFAULT ' . ($value ? 'TRUE' : 'FALSE');
			else
				continue;

			if ($str)
				$query .= '`'.$field.'` '.$str.',';
		}

		$index_array = array();

		if (!$model_indexes)
			$query = substr($query, 0, -1);
		else {
			foreach ($model_indexes as $index_type => $indexes) { 
				foreach ($indexes as $index) { 
					if (in_array($index_type,array(self::INDEX, self::INDEX_FOREIGN_KEY)))
						$index_array[] = 
							'KEY `'.join('_',$index).'` (`'.join('`,`', $index).'`)';
					elseif ($index_type == self::INDEX_UNIQUE) 
						$index_array[] =
							'UNIQUE KEY `'.join('_',$index).'` (`'.join('`,`', $index).'`)';
					elseif (($index_type == self::INDEX_PRIMARY) && (count($index) > 1)) 
						$index_array[] =
							'PRIMARY KEY `'.join('_',$index).'` (`'.join('`,`', $index).'`)';
				}
			}
		}

		$query .= join(',', $index_array).')';
		
		return $this->query($query);
	}

	public function reset() { 
		$this->_query = null;

		if ($this->_result) {
			$this->_result->free();
			$this->_result = null;
		}

		$this->_count = null;
		$this->_last = null;
		$this->_affected = null;
	}
}
?>
