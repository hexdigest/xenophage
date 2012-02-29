<?php
abstract class AbstractAccessProvider {
	abstract function allow($controller, $action); 
}
?>
