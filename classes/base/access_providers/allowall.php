<?php
AutoLoad::path(dirname(__FILE__).'/abstractaccessprovider.php');

class AllowAll extends AbstractAccessProvider {
	public function allow($controller, $action) { 
		return true;
	}
}
?>
