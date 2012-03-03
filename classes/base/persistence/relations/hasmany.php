<?php
AutoLoad::path(dirname(__FILE__).'/hasone.php');

class HasMany extends HasOne {
	protected $finder = 'find_by_';
}
?>
