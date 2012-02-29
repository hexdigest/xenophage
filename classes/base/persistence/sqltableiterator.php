<?php
require_auto(XENOPHAGE . '/classes/sqliterator.php');

class SQLTableIterator extends SQLIterator {
	protected $order_by = null;
	protected $limit = null;
	protected $original_query = null;

	public function __construct($query, $sqlconnect = null) {
		$query = trim($query);
		if (stripos($query, 'SELECT') === 0)
			$query = preg_replace('/^SELECT/ui', 'SELECT SQL_CALC_FOUND_ROWS', $query);

		$this->original_query = $query;

		if ($sqlconnect)
			parent::__construct($sqlconnect, $query);
		else
			parent::__construct(clone XEngine::instance()->_sql, $query);
	}

	public function &order_by($order_by) {
		$this->order_by = 'ORDER BY ' . $order_by;

		$this->refresh_query();

		return $this;
	}

	public function &paginate($page_num = 1, $rows_per_page = XEN_ROWS_PER_PAGE) { 
		$this->limit = 'LIMIT '.$rows_per_page;

		if ($offset = ($page_num - 1) * $rows_per_page)
			$this->limit .= ' OFFSET '.$offset;

		$this->refresh_query();

		return $this;
	}

	public function refresh_query() { 
		$this->query = $this->original_query.' '.$this->order_by.' '.$this->limit;
	}

	public function count() {
		$this->_sql->query('SELECT FOUND_ROWS()');
		list($count_rows) = $this->_sql->r();

		return $count_rows;
	}
}
?>
