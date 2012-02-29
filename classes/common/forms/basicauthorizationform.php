<?php
AutoLoad::path(dirname(__FILE__).'/../../widgets/form.php');
AutoLoad::path(dirname(__FILE__).'/../../base/persistence/modeliterator.php');

class BasicAuthorizationForm extends Form {
	public function model() { 
		$this->title(__('BASIC_AUTHORIZATION_FORM_TITLE'));

		$this->login = new iString;
		$this->login->title(__('BASIC_AUTHORIZATION_LOGIN_TITLE'));

		$this->password = new iPassword;
		$this->password->title(__('BASIC_AUTHORIZATION_PASSWORD_TITLE'));

		$this->submit = new iSubmit;
		$this->submit->title(__('BASIC_AUTHORIZATION_SUBMIT_TITLE'));
	}

	public function submit() { 
		$result = parent::submit();

		if ($result) {
			$iterator = new ModelIterator(USER_CLASS);
			$user = $iterator->findOne(array(
				'login' => $this->login->get(),
				'password' => $this->password->get()
			));

			if (! $user) {
				$result = false;
				$this->login->error(__('BASIC_AUTHORIZATION_LOGIN_ERROR'));
			}
		}

		return $result;
	}
}
?>
