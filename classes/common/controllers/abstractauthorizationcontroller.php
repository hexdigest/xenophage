<?php
AutoLoad::path(dirname(__FILE__).'/abstractwebpagecontroller.php');
AutoLoad::path(dirname(__FILE__).'/../model/abstractuser.php');
AutoLoad::path(dirname(__FILE__).'/../base/engine.php');

Utils::define('REGISTRATION_CONTROLLER_CLASS', 'RegistrationController');
Utils::define('AUTHORIZATION_FORM_CLASS', 'AuthorizationForm');
Utils::define('USER_CLASS', 'User');
Utils::define('LOGOUT_REDIRECT_URL', '/');

/**
 * login/logout user and sending of user password
 */
abstract class AbstractAuthorizationController extends AbstractWebPageController {
	public function __construct() { 
		parent::__construct();
		
		$this->linkToLogin = $this->linkToAction('login');
		$this->linkToLogout = $this->linkToAction('logout');
		$this->linkToAuthorize = $this->linkToAction('authorize');
		$this->linkToRegistration = $this->linkTo(
			REGISTRATION_CONTROLLER_CLASS, 'register');
	}
	

	/**
	 * Login and send password forms
	 * @return 
	 */
	public function authorize(){
		$session = $this->session();
		$userId = $session->userId->get();

		if ($userId)
			$this->user = Utils::getInstance(USER_CLASS, $userId);
		else {
			$form = Utils::getInstance(AUTHORIZATION_FORM_CLASS);
			if ($form->submit()) {
				$userId = $form->getUserId();
				$session->userId = $userId;
			} else
				$this->form = $form;
		}

		return $userId;
	}

	/**
	 * Closes session and redirects user to main page
	 */
	public function logout() {
		Engine::instance()->session->remove();
		$this->redirect(LOGOUT_REDIRECT_URL);
	}
}
?>
