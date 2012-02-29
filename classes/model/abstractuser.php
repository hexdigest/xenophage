<?php
AutoLoad::path(dirname(__FILE__) . '/../base/persistence/abstractpersistentmodel.php');

abstract class AbstractUser extends AbstractPersistentModel {

	public $_idFieldName = 'login';
	
	public function model() {
		$fields = array();
		$fields['login'] = new iString();
		$fields['password'] = new iPassword();
		$fields['region']   = new iInt();
		$fields['language'] = new iString();

		return $fields;
	}
}
?>
