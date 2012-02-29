<?php
AutoLoad::path(dirname(__FILE__).'/abstractcontroller.php');

abstract class AbstractWebPageController extends AbstractController {
	public function __construct() {
		parent::__construct();
		
		$this->showSessionBox(); //output message box if exist
	}

	/**
	* Stores box title and caption in session and then makes
	* redirect to given controller and action
	*/
	protected function redirectToBox($class, $message, $controller = null, $action = null, $params = null) {
		if (null === $controller) {
			$controller = get_class($this);
			$link = null;
		} else
			$link = $this->linkTo($controller, $action, $params);

		$this->saveSessionBox($class, $message, $controller);
		$this->redirect($link);
	}

	/**
	* Saves message box in session
	*/
	protected function saveSessionBox($class, $message, $controller = null) {
		if (null === $controller)
			$controller = get_class($this);

		if (! is_array($message))
			$message = (array) $message;

		$session = $this->session();
		$session->{$controller . '_box'} = array($class, $message);
	}

	/**
	* Restores message box from session
	*/
	protected function showSessionBox() {
		$box = $this->session()->{get_class($this) . '_box'};
		if ($box) {
			list($class, $message) = $box;
			$this->$class = Utils::getClassInstance($class, $message);
		}
	}

	/**
	 * Store success box before redirect
	 */
	protected function redirectToSuccess($message, $controller = null, $action = null, $params = array()) {
		$this->redirectToBox('SuccessBox', $controller, $action, $params);
	}
	
	/**
	 * Store error box before redirect
	 */
	protected function redirectToError($message, $controller = null, $action = null, $params = array()) {
		$this->redirectToBox('ErrorBox', $controller, $action, $params);
	}
}
?>
