<?php
/**
 * Base class for all controllers
 */
abstract class AbstractController extends XWidget {
	/**
	* Run method with a given params
	* @param string $action - method to run
	* @param array $params - associative array of params
	*/
	public function _run_action($action, $params = array()) { 
		$rm = new ReflectionMethod($this, $action); 
		$orderedParams = array(); 
		foreach($rm->getParameters() as $param) { 
			$name = $param->getName();

			if (isset($params[$name]))
				$orderedParams[] = $params[$name]; 
			else
			if ($param->isOptional())
				$orderedParams[] = $param->getDefaultValue(); 
			else
				throw new Exception('Missing required parameter: '.$name);
		}

		$rm->invokeArgs($this, $orderedParams);

		$result = $this->_draw();
		$result[':attributes']['action'] = $action;

		return $result;
	}

	/**
	 * Logs event
	 * @param string $message event message
	 */
	public function log($message) {
		error_log($message);
	}

	/**
	* Send Location http-header to client
	*/
	protected function redirect($URL = null) {
		//don't do anyting in console mode
		if ('cli' == php_sapi_name()) 
			throw new RedirectException($URL);

		if (null === $URL)
			$URL = $_SERVER['REQUEST_URI'];

		header('Location: '.$URL);
		exit;
	}

	/**
	* Return link to first URL that matching params
	*/
	protected function linkTo($controller, $action, $params = array()) { 
		return URLDispatcher::getURLByRule(array(
			'controller' => $controller,
			'action' => $action,
			'params' => $params
		));
	}

	/**
	* Return link to action of currently running controller
	*/
	protected function linkToAction($action, $params = array()) { 
		return $this->linkTo(get_class($this), $action, $params);
	}

	/**
	* Redirect client to given controller and action
	*/
	protected function redirectTo($controller, $action, $params = array()) {
		$this->redirect($this->linkTo($controller, $action, $params));
	}

	/**
	* Redirect client to method of currently running controller
	*/
	protected function redirectToAction($action, $params = array()) {
		$this->redirect($this->linkToAction($action, $params));
	}
	
	/**
	* Helper method that returns current session
	*/
	public function session() { 
		return Engine::instance()->session;
	}	
}
?>
