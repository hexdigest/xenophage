<?php
AutoLoad::path(dirname(__FILE__) . '/itext.php');
class iLongText extends iText {	

	public function __construct($check_condition = null) { 
		parent::__construct($check_condition);

		$this->_sql_statement = 'LONGTEXT';
	}	
}
?>
