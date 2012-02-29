<?php
AutoLoad::path(dirname(__FILE__).'/abstractaccessprovider.php');

class DenyAll extends AbstractAccessProvider {
	public function allow($controller, $action) { 
		return false;
	}
}
?>
