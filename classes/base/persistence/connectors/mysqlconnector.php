<?php
define('CLIENT_FOUND_ROWS', 2);

class MySQLConnector extends SQLConnector {
	protected $_link = null;
	
	public static $transaction_status = array();
	public static $connection_status = array();

	protected $_query = null;
	protected $_result = null;
	protected $_count = null;
	protected $_last = null;
	protected $_affected = null;

	protected $_storage_charset = null;

	public function __construct($params) { 
		if (!$params['path'])
			throw new SQLException('Database name is not specified');
		
		if (isset($params['port']))
			$params['host'] .= ':'.$params['port'];

		$this->_link = mysql_connect(
			$params['host'], 
			$params['user'], 
			$params['pass'], 
			true,
			CLIENT_FOUND_ROWS
		);
		
		try {
			$dbname = substr($params['path'], 1);
			mysql_select_db($dbname, $this->_link);

			//connection settings like encoding
			if ($params['query']) {
				parse_str($params['query'], $in_connection_params);
			
				if ($encoding = $in_connection_params['connection_charset'])
					$this->q('SET NAMES '.addslashes($encoding));

				if (isset($in_connection_params['storage_charset']))
					$this->_storage_charset = $in_connection_params['storage_charset'];
			}
		} catch (Mistake $e) {
			throw new SQLException(mysql_error($this->_link), 
				mysql_errno($this->_link));
		}
		
		//Transaction status of this connection. 1 means transaction closed
		if (!isset(self::$transaction_status[$this->_link]))
			self::$transaction_status[$this->_link] = 1;

		self::$connection_status[$this->_link] = true;
	}
	
	/**
	* Runs SQL query, on non-empty SELECT return $this object
	* on INSERT return LAST_INSERT_ID()
	* otherwise return affected rows count
	*/
	public function query($query, $get_insert_id = true) {
		$this->_query = trim($query);

		$result = $this->q($this->_query);
		if (!$result)
			return false;
			
		if (true === $result) {
			if (strtolower(substr($this->_query,0,6))==='insert' && $get_insert_id) {
				$r = $this->q('SELECT LAST_INSERT_ID() AS "id"');
				return $this->_last = mysql_result($r, 0, 'id');
			} else {
				return $this->_affected = mysql_affected_rows($this->_link);
			}
		}

		$this->_result = $result;
		$this->_count = mysql_num_rows($result);

		if ($this->_count)
			return $this;
			
		return 0;
	}
	
	public function q($query) {
		$r = mysql_query($query, $this->_link);

		if ($c = mysql_errno($this->_link)) {
			//rolling back current transaction
			if ($this->is_transaction_started())
				$this->rollback();

			throw new SQLException(
				mysql_error($this->_link).PHP_EOL.
					'Query: '.substr($query,0,255).((strlen($query)>255)?'...':''), 
				$this->wrap_code($c));
		}

		return $r;
	}

	public function begin() { 
		$status = self::$transaction_status[$this->_link];

		if (1 == $status)
			$this->q('BEGIN');

		$status = $status << 1;

		return self::$transaction_status[$this->_link] = $status;
	}
	
	public function commit() { 
		$status = self::$transaction_status[$this->_link];

		$status = $status >> 1;
		if ($status <= 1) {
			$this->q('COMMIT');
			return (self::$transaction_status[$this->_link] = 1);
		} else
			return self::$transaction_status[$this->_link] = $status;
	}

	public function rollback() { 
		$this->q('ROLLBACK');
		
		$status = self::$transaction_status[$this->_link];

		$isNestedRollback = (($status >> 1) !== 1);
		
		//reset transaction status
		self::$transaction_status[$this->_link] = 1;

		if ($isNestedRollback)
			throw new SQLException('Nested transaction rolled back');
	}

	public function is_transaction_started() { 
		return (self::$transaction_status[$this->_link] !== 1);
	}

	public function a() { 
		return mysql_fetch_assoc($this->_result);
	}
	
	public function r() { 
		return mysql_fetch_row($this->_result);
	}
	
	/**
	* Returns array containing column number $number
	* Numbers start from 0
	*/
	public function column($number = 0) {
		if (!$this->_count)
			return array();
		
		$result = array();
		$this->rewind();

		while ($row = $this->r()) 
			$result[] = $row[$number];

		return $result;
	}

	/**
	* Must return wrapped error code for all database engines
	*/
	public static function wrap_code($error_code) {
		switch ($error_code) {
			case 1062:
				return self::SQL_ERROR_DUPLICATE_ENTRY;
			case 1146:
				return self::SQL_ERROR_TABLE_DOES_NOT_EXIST;
			default:
				return $error_code;
		}
	}

	public function disconnect() { 
		if (self::$connection_status[$this->_link]) {
			mysql_close($this->_link);
			self::$connection_status[$this->_link] = false;
		}
	}

	public function rewind() { 
		if ($this->_count)
			mysql_data_seek($this->_result, 0);
	}
	
	/**
	* Returns list of $table field names
	*/
	public function fields($table) { 
		$r = $this->q('DESC '.$table);
		$fields = array();

		while ($row = mysql_fetch_assoc($r)) 
			$fields[] = $row['Field'];
		
		return $fields;
	}
	
	/**
	* Creates index for the field(s)
	*/
	public function index($table, $fields) { 
		if (!is_array($fields))
			$fields = array($fields);
	}

	public function db_type_cast($value) {
		if (null === $value)
			$str = 'NULL';	
		elseif (is_object($value)) {
			if (method_exists($value,'toDatabase')) {
				$str = $value->toDatabase();
				if (null === $str)
					$str = 'NULL';
				else
					$str = '"'.addslashes($str).'"';
			} else
				throw new SQLException('Object of class "'.get_class($value).
					'" can\'t be stored in database');
		} elseif (is_array($value))  //array maps as enum
			$str = '"'.join('","', array_map('addslashes', $value)).'"';
		elseif (is_bool($value))
			$str = ($value ? 'TRUE' : 'FALSE');
		else
			$str = '"'.addslashes(strval($value)).'"';

		return $str;
	}
	
	/**
	* Creates table for the given model
	*/
	public function create_model_table($model) { 
		$query = 'CREATE TABLE `'.$model->_table.'` (';
		
		if (strcasecmp(get_class($model), 'XModel'))
			$query .= '`id` SERIAL,';

		$model_indexes = $model->get_indexes();

		$foreign_keys = array();
		if ($model_indexes[self::SQL_INDEX_FOREIGN_KEY])
			foreach ($model_indexes[self::SQL_INDEX_FOREIGN_KEY] as $foreign_key)  
				$foreign_keys[] = reset($foreign_key);

		foreach ($model as $field => $value) {
			if ($value === null)
				continue;

			if (in_array($field, $foreign_keys))
				$str = 'BIGINT UNSIGNED NOT NULL DEFAULT "'.$value.'"';
			elseif (is_object($value) && method_exists($value,'_init_db'))
				$str = $value->_init_db();
			elseif (is_array($value))  //array maps as enum
				$str = 'ENUM ("'.join('","', array_map('addslashes',$value)).'")';
			elseif (is_string($value))
				$str = 'TEXT NOT NULL DEFAULT "'.addslashes($value).'"';
			elseif (is_double($value))
				$str = 'DOUBLE NOT NULL DEFAULT "'.$value.'"';
			elseif (is_int($value))
				$str = 'INT NOT NULL DEFAULT "'.$value.'"';
			elseif (is_bool($value))
				$str = 'BOOL NOT NULL DEFAULT '.($value?'TRUE':'FALSE');
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
					if (in_array($index_type,array(self::SQL_INDEX, self::SQL_INDEX_FOREIGN_KEY)))
						$index_array[] = 
							'KEY `'.join('_',$index).'` (`'.join('`,`', $index).'`)';
					elseif ($index_type == self::SQL_INDEX_UNIQUE) 
						$index_array[] =
							'UNIQUE KEY `'.join('_',$index).'` (`'.join('`,`', $index).'`)';
					elseif (($index_type == self::SQL_INDEX_PRIMARY) && (count($index) > 1)) 
						$index_array[] =
							'PRIMARY KEY `'.join('_',$index).'` (`'.join('`,`', $index).'`)';
				}
			}
		}

		$query .= join(',',$index_array).')';
		
		if ($this->_storage_charset)
			$query .= ' CHARSET = '.$this->_storage_charset;

		return $this->query($query);
	}

	public function reset() { 
		$this->_query = null;
		$this->_result = null;
		$this->_count = null;
		$this->_last = null;
		$this->_affected = null;
	}
}
?>
