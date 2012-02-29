<?php
AutoLoad::path(dirname(__FILE__).'/controller.php');
AutoLoad::path(dirname(__FILE__).'/../model/abstractuser.php');
AutoLoad::path(dirname(__FILE__).'/../base/engine.php');
AutoLoad::path(dirname(__FILE__).'/../widgets/');

class LoginForm extends Form{
	public function __construct() { 
		parent::__construct();
		$this->title(__('LOGIN_FORM_TITLE'));	//Вход в систему
		$this->phone = new iPhone;
		$this->phone->title(__('LOGIN_FORM_PHONE_TITLE')); //Телефон
		$this->password = new iPassword;
		$this->password->title(__('LOGIN_FORM_PASSWORD_TITLE')); //Пароль
		$this->submit = new iSubmit;
		$this->submit->title(__('LOGIN_FORM_SUBMIT_BUTTON_TITLE')); //Войти
	}	
}

class SendPasswordForm extends Form{
	public function __construct() { 
		parent::__construct();
		$this->title(__('SEND_PASSWORD_FORM_TITLE'));//Запрос пароля
		$this->phone = new iPhone;
		$this->phone->title(__('LOGIN_FORM_PHONE_TITLE')); //Телефон
		$this->submit = new iSubmit;
		$this->submit->title(__('SEND_PASSWORD_FORM_SUBMIT_BUTTON_TITLE')); //Получить пароль
	}	
}

/**
 * login/logout user and sending of user password
 */
class AuthorizationController extends Controller {
	public function __construct() { 
		parent::__construct();
		
		$this->sessionLifetime = SESSION_LIFETIME;

		$this->linkToLogin = $this->linkToAction('login');
		$this->linkToLogout = $this->linkToAction('logout');
		$this->linkToSendPassword = $this->linkToAction('sendPassword');
	}

	public function login($redirectTo = null){
		if ($this->authorize()){
			if (null === $redirectTo) {
				if ($this->linkToAction(__FUNCTION__) == $_SERVER['REQUEST_URI'])
					$redirectTo = '/';
			}

			$this->redirect($redirectTo);
		}
	}

	/**
	 * Login and send password forms
	 * @return 
	 */
	public function authorize(){
		$session = $this->session();
		if ($session->authorized()){
			$this->client = $this->client();
			return true;
		}

		if ($this->sendPassword()) {
			$this->success = new SuccessBox(
				__('PASSWORD_SENT_SUCCESS_BOX_TITLE'), //Пароль отправлен
				__('PASSWORD_SEND_SUCCESS_BOX_MESSAGE') //SMS с паролем придет на указанный вами номер в течение нескольких минут
			);
		}

		return $this->authorizeClient();
	}

	/**
	* Outputs request password form and send password to client on submit
	*/
	public function sendPassword() { 
		$form = new SendPasswordForm;

		$result = false;
		if ($form->submit($_POST)) {
			try { 
				$pc = new PCCommand;
				$pc->sendPassword($form->phone->getFullNumber());
				$result = true;
			} catch (PCResultException $e) {
				$this->error = new ErrorBox($e->getMessage());
			} catch (Exception $e) {
				$this->error = new ErrorBox(__('ERROR_TECHNICAL')); //Техническая ошибка
			}
		} 

		if (! $result) {
			$this->send_form = $form;
		}

		$this->result = $result;
		return $this->result;
	}

	/**
	* Outputs authorization form and authorizes client on successfull submission
	*/
	public function authorizeClient() { 
		$form = new LoginForm;

		$result = false;
		if ($form->submit($_POST)) {
			try { 
				$pc = new PCCommand();
				$clientId = $pc->authorizeClient($form->phone->getFullNumber(), $form->password->get());
				$client = new Client;
				$client->clientId = $clientId;
				$client->update();

				$this->session()->clientId = $clientId;

				$this->client = $client;
				$result = true;
			} catch (PCResultException $e) {
				if (in_array($e->getCode(), array(PCResult::CLIENT_AUTHORIZATION_PASSWORD_EXPIRED, PCResult::WRONG_PASSWORD )))
					$form->password->error($e->getMessage());
				else
					$form->phone->error($e->getMessage());
			} catch (Exception $e) {
				$this->error = new ErrorBox(__('ERROR_TECHNICAL')); //Техническая ошибка
			}
		}

		if (! $result) {
			$this->login_form = $form;
		}

		$this->result = $result;
		return $this->result;
	}
	
	/**
	 * Renders current client if autorized
	 */
	public function currentClient() {
		if($this->session()->authorized()) {
			$this->client = $this->client();
		}
	}
	
	/**
	 * Closes session and redirects user to main page
	 */
	public function logout(){
		Engine::instance()->session->remove();
		$this->redirect('/');
	}
}

?>
