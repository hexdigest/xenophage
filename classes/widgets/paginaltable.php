<?php
AutoLoad::path(dirname(__FILE__) . '/xwidget.php');
class PaginalTable extends XWidget {
	protected $_pagination = null;
	protected $_order_column = null;
	protected $_order_direction = null;
	protected $_default_order = null;
	protected $_rows_per_page = null;
	protected $_iterator = null;
	protected $_request_prefix = '';

	public function __construct($iterator, $rows_pp = XEN_ROWS_PER_PAGE, $request_prefix = '') { 
		parent::__construct();
		
		$this->_iterator = $iterator;
		$this->_rows_per_page = $rows_pp;
		$this->_request_prefix = $request_prefix;
	}

	public function _draw() {
		$this->order_link = PrepareToAddURIParam(DelURIParam(DelURIParam($_SERVER["REQUEST_URI"], $this->_request_prefix.'order_column'), $this->_request_prefix.'order_direction'));
		$order_column = addslashes($_REQUEST[$this->_request_prefix.'order_column']);
		$order_direction = strtoupper($_REQUEST[$this->_request_prefix.'order_direction']);

		if (!$order_column && $this->_default_order) {			
			$order_direction = trim(strrchr($this->_default_order,' '));
			$order_column = trim(substr($this->_default_order,0,stripos($this->_default_order,$order_direction)));	
			//list($order_column, $order_direction) = explode(' ', $this->_default_order);
		}
		if ($order_column && in_array($order_direction, array('ASC', 'DESC'))) {			
			$this->_order_column = $order_column;
			$this->_order_direction = $order_direction;

			$this->_iterator = 
				$this->_iterator->order_by('`'.$order_column.'` '.$order_direction);
		}

		$this->_pagination = new Pagination($this->_iterator, $this->_rows_per_page, $this->_request_prefix);

		parent::_draw($canvas);

		$rows = $canvas->ownerDocument->createElement('rows');
		$rows = $canvas->appendChild($rows);

		if ($this->_order_column) {
			$canvas->setAttribute('order_column', $this->_order_column);
			$canvas->setAttribute('order_direction', $this->_order_direction);
		}

		if (!empty($_REQUEST[$this->_request_prefix.'function']) && is_array($_REQUEST[$this->_request_prefix.'function'])){			
		
			$funcs = $canvas->ownerDocument->createElement('functions');
			
			foreach ($_REQUEST[$this->_request_prefix.'function'] as $field => $fn){
				$func = $canvas->ownerDocument->createElement('function', $fn);						
				$func->setAttribute('field', $field);
				$funcs->appendChild($func);
			}
			
			$funcs = $canvas->appendChild($funcs);
		}
		
		$this->_pagination->_draw($rows);
	}

	public function default_order($order) { 
		$this->_default_order = $order;
	}
}
?>
