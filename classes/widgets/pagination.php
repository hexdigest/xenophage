<?php
AutoLoad::path(dirname(__FILE__) . '/xwidget.php');
class Pagination extends XWidget {
	protected $_iterator = null;
	protected $_request_prefix = '';
	
	/**
	* $link in format '/some/pages/{page}'
	*/
	public function __construct($iterator, $rows = XEN_ROWS_PER_PAGE, $request_prefix = '') { 
		parent::__construct();

		$this->_iterator = $iterator;
		$this->_request_prefix = $request_prefix;

		$current_page = intval($_REQUEST[$this->_request_prefix.'p_page']);
		
		if (intval($current_page) <= 0)
			$current_page = 1;
		
		$this->current_page = $current_page;
		$this->rows_per_page = $rows;
						
		if ($rows)
			$this->_iterator = $this->_iterator->paginate($current_page, $rows);		
	}

	public function _draw() { 
		$this->_draw_var($this->_iterator, $canvas);		
		$this->page_id = PrepareToAddURIParam(DelURIParam($_SERVER["REQUEST_URI"], $this->_request_prefix.'p_page'));		
		$this->count = $this->_iterator->count();		
		//A little bit tricky but works
		$pagination_node = $canvas->ownerDocument->createElement('pagination');
		
		XWidget::_draw($pagination_node);

		$canvas->parentNode->appendChild($pagination_node);
	}
}
?>
