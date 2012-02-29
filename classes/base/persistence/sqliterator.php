<?php
class SQLIterator implements Iterator {
	protected $query = null;
	protected $row = null;
	protected $_sql = null;
	protected $fetched = false;
	static $_count = 0;
	
	public function __construct($sql, $query) { 
		$this->_sql = $sql;
		$this->query = $query;
	}

	public function __destruct() { 
		unset($this->_sql);
		unset($this->query);
		unset($this->row);
	}
	
	public function key() {
		return self::$_count;
	}

	public function current() {
		return $this->row;
	}

	public function next() {
		$this->row = $this->_sql->a();
		self::$_count++;
	}

	public function rewind() {
		if ($this->fetched)
			$this->_sql->rewind();
		else
			$this->fetch();

		$this->row = $this->_sql->a();
		self::$_count = 0;
	}

	public function valid() {
		return ($this->row ? true : false);
	}

	protected function fetch() { 
		if ($this->fetched)
			return;
		
		$this->_sql->query($this->query);
		$this->fetched = true;
	}
}
?>
