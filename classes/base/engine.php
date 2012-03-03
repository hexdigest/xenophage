<?php
require_once(dirname(__FILE__).'/../utils/autoload.php');
require_once(dirname(__FILE__).'/../utils/utils.php');
require_once(dirname(__FILE__).'/__.php');

AutoLoad::path(dirname(__FILE__).'/../exceptions/');
AutoLoad::path(dirname(__FILE__).'/../widgets/xwidget.php');
AutoLoad::path(dirname(__FILE__).'/urldispatcher.php');

class Engine extends XWidget {
	private static $__instance = null; // static instance of XEngine
	protected $_controllers = array();

	public $_route = null;
	public $_http_status = 200;
	public $_path = '';
	public $_dispatch_rule = array();
	public $_controller = null;
	public $_action = null;

	public function run() {
		try {
			$this->_path = $this->getRequestPath();
			$this->_dispatch_rule = URLDispatcher::getRuleByURL($this->_path);

//TODO: run all controllers binded to this url
			$this->runController(
				$this->_dispatch_rule['controller'],
				$this->_dispatch_rule['action'],
				$this->_dispatch_rule['params']
			);

			if (isset($this->_dispatch_rule['format']))
				$this->format($this->_dispatch_rule['format']);
			

		} catch (Exception $e) {
			$this->exception_handler($e);
		}

		$this->shutdown();
	}

	public static function getRequestPath() { 
		$parts = explode('?', $_SERVER['REQUEST_URI']);
		return reset($parts);
	}

	/**
	* Flexible exceptions handler
	*/
	protected function exception_handler($e) {
echo $e;exit;
		$method = 'process_'.strtolower(get_class($e));
		if (method_exists($this, $method))
			return $this->$method($e);
		else
			return $this->process_exception($e);
	}

	/**
	* HTTP exceptions handler
	*/
	protected function process_httpexception($e) {
		$this->_http_status = $e->getCode();

		header('HTTP/1.0 '.$this->_http_status.' '.$e->getMessage());

		//if format was specified output error page in this format
		if (empty($this->_dispatch_rule['format']))
			echo $this->_http_status. ' '. $e->getMessage();
		else
			$this->format($this->_dispatch_rule['format']);
	}

	/**
	* Default exceptions handler
	*/
	protected function process_exception($e) {
		$this->log(get_class($e) . ':'. $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL);
	}

	/**
	* Runs action of the given controller
	*/
	public function runController($class, $action, $params = array()) {
		if (! (
				self::check_function_name($class) &&
				self::check_function_name($action)
		))
			throw new HTTPException('Not found', 404);

		if (! is_subclass_of($class, 'AbstractController'))
			throw new Exception($class.' must be an instance of AbstractController');

		$this->checkPermissions($class, $action);

		if (null === $this->_controller) {
			$this->_controller = $class;
			$this->_action = $action;
		}

		$controller = new $class;
		$this->_controllers[] = $controller->_run_action($action, $params);

		return $controller;
	}

	protected function checkPermissions($controller, $action) {
		$accessProvider = Utils::getClassInstance(ACCESS_PROVIDER);

		if (! $accessProvider->allow($controller, $action))
			throw new HTTPException('Forbidden ', 403);
	}

	public static function check_function_name($identifier) {
		return preg_match('/^[a-z_]+[a-z0-9_]*$/i', $identifier);
	}

	public function shutdown() {}

	public function log() {
		$message = print_r(func_get_args(), true);
		error_log($message);
	}

	public static function & init($class) {
		if (null === self::$__instance)
			self::$__instance = new $class;

		return self::instance();
	}

	public static function &instance() {
		return self::$__instance;
	}

	public function _draw() {
		$result = array(
			':attributes' => array(
				'http_status' => $this->_http_status,
				'path' => $this->_path,
				'controller' => $this->_controller,
				'action' => $this->_action
			)
		);

		if (isset($_SERVER['HTTP_HOST']))
			$result[':attributes']['host'] = $_SERVER['HTTP_HOST'];

		$result['controllers'] = $this->_controllers;
		
		return $result;
	}

	public function format($format) {
		Utils::getClassInstance($format.'Formatter')->format($this->_draw());
	}
}
?>
