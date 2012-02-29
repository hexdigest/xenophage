<?php
require_auto(XENOPHAGE . '/classes/xmodel.php');
require_auto(XENOPHAGE . '/classes/widgets/');

class SQLTableFilter extends XModel {
	protected $_query = null;
	protected $_old_widgets = array();
	protected $_widgets_lines = array();
	protected $_rows_per_page = null;
	protected $_default_order = null;

	public function __construct($query, $rows_per_page = XEN_ROWS_PER_PAGE) { 
		$this->_query = $query;
		$this->_rows_per_page = $rows_per_page;

		parent::__construct();
		$this->set_method('get');
	}

	public function model() { 
		$lines = preg_split('/[\r\n]+/sui', $this->_query);
		$regs = array();

		foreach ($lines as $line) {
			if (preg_match('/<\?(php)?(.*)\?>/sui', $line, $regs)) 
				eval($regs[2]);

			foreach ($this->added_widgets() as $name) {
				$this->_widgets_lines[$name] = $line;
				$this->$name->optional(true);
			}
		}
	}

	protected function added_widgets() { 
		$new_wigets = array_diff(array_keys($this->_properties), 
			$this->_old_widgets);

		$this->_old_widgets = array_keys($this->_properties);

		return $new_wigets;
	}

	protected function remove_widget_line($name, $query) {
		$line = $this->_widgets_lines[$name];

		return str_replace($line, '', $query);
	}

	protected function replace_widget_value($name, $query) {
		$line = $this->_widgets_lines[$name];
		$new_line = preg_replace('/<\?(php)?(.*)\?>/sui', strval($this->$name), $line);

		return str_replace($line, $new_line, $query);
	}
	
	public function submit() { 
		$result_query = $this->_query;

		foreach ($this as $name => $value) {
			if (is_object($value) && method_exists($value, 'set')) {
				$value->set($_REQUEST[$name]);

				if ($value->get()) 
					$result_query = $this->replace_widget_value($name, $result_query);
				else
					$result_query = $this->remove_widget_line($name, $result_query);
			}
		}

		$result_query = preg_replace('/\s+AND\s+$/sui', '', $result_query);
		$result_query = preg_replace('/\s+WHERE\s+$/sui', '', $result_query);

		return $result_query;
	}

	public function _draw(&$canvas) { 
		$query = $this->submit();

		//show submit only if the filter has a fields to fill
		if ($this->get_nested_widgets('iInput')) {
			$this->apply_filter = new iSubmit;
			$this->apply_filter->title($this->title());
		}

		parent::_draw($canvas);

		$table = new PaginalTable(new SQLTableIterator($query), 
			$this->_rows_per_page);

		if ($this->_default_order)
			$table->default_order($this->_default_order);

		$table_node = $canvas->ownerDocument->createElement('filter_results');
		$table->_draw($table_node);

		$canvas->appendChild($table_node);
	}

	public function default_order($order) { 
		$this->_default_order = $order;
	}

	public function create_table() {}
}
?>
