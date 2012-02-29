<?php
AutoLoad::path(dirname(__FILE__).'/abstractwebpagecontroller.php');
AutoLoad::path(dirname(__FILE__).'/../model/abstractuser.php');
AutoLoad::path(dirname(__FILE__).'/../base/engine.php');

Utils::define('AUTHORIZATION_CONTROLLER_CLASS', 'AuthorizationController');
Utils::define('REGISTRATION_FORM_CLASS', '');

abstract class AbstractRegistrationController extends AbstractWebPageController {
	public function __construct() { 
		parent::__construct();

		$this->linkToLogin = $this->linkTo(AUTHORIZATION_CONTROLLER_CLASS, 'login');
	}

	public function register() {
		
	}
}
?>
