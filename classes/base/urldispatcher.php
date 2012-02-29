<?php
/**
* Helper class to define params encoded in URL
*/
class URLParam {
 	protected $number = null;

	public function __construct($number) { 
		$this->number = $number;
	}

	public function getNumber() { 
		return $this->number;
	}
}

class URLDispatcher {
	public static $rules = array();
	
	/**
	* Register dispatch rule
	* @param string $regexp - regular expression for URL in a form "/page/new/(\w+)", 
	* 	where slashes are not escaped with backslash 
	* @param array $rule - associative array containing following keys:
	* 	'controller' => controller to run if the request URL match $regexp
	*		'action' => method of controller to run
	*		'params' => associative array containing params
	*		'format' => output format
	*
	* each of keys in $rule can be set with a URLParam object. This fact tells dispatcher
	* to take param from URL dynamically, i.e.
	* URLDispatcher::registerRule('/run/(\w+)/(\w+)/(\d+)/', array(
	*		'controller' => new URLParam(1),
	*		'action' => new URLParam(2),
	*		'params' => array(
	*			'param' => new URLParam(3)
	*		)
	*	)
	*	when request come to /run/someclass/somemethod/5/
	* URLDispatcher::getRuleByURL will return array(
	*		'controller' => 'someclass',
	*		'action' =>	'somemethod',
	*		'params' => array(
	*			'param' => 5
	*		)
	*	)
	*	see tests for more examples
	*/
	public static function registerRule($regexp, array $rule) { 
		$regexp = str_replace('/', '\/', $regexp);
		self::$rules[] = array($regexp, $rule);
	}

	/**
	* Return corresponding rule by URL
	* @param string $URL - URL to test rules with
	*/
	public static function getRuleByURL($URL) { 
		$regs = array();

		foreach (self::$rules as $rule)
			if (preg_match('/^'.$rule[0].'$/sui', $URL, $regs)) 
				return self::fillArrayByRule($rule[1], $regs);
			
		throw new HTTPException('Not found', 404);
	}

	/**
	* Reverse search rule by URL very handy to make
	* consistent URLs on server-side and then pass them
	* to client.
	* @param array $rule - dispatch rule (see URLDispatcher::registerRule 
	*		for details. If rule contains actions associated with URLParam, then
	* 	$rule['params'] will be encoded in a query-string (GET-params)
	*/
	public static function getURLByRule($rule) { 
		foreach (self::$rules as $currentRule) { 
			$strictParams = true;
			list($regexp, $r) = $currentRule;

			$numbers = array();

			if ($r['controller'] instanceof URLParam) {
				$controller = $rule['controller'];
				$numbers[$r['controller']->getNumber()] = $controller;
			} else {
				$controller = $r['controller'];
			}

			if ($controller != $rule['controller'])
				continue;

			if ($r['action'] instanceof URLParam) {
				//arbitrary action given, checking params is unnecessary
				$strictParams = false; 
				$action = $rule['action'];
				$numbers[$r['action']->getNumber()] = $action;
			} else {
				$action = $r['action'];
			}

			if ($action != $rule['action'])
				continue;

			if ($strictParams) {
				if (count($r['params']) != count($rule['params']))
					continue;

				$params = array();
				if (isset($r['params'])) {
					foreach ($r['params'] as $param => $value) { 
						if (! isset($rule['params'][$param])) {
							continue 2;
						}

						if ($value instanceof URLParam) {
							$params[$param] = $rule['params'][$param];
							$numbers[$value->getNumber()] = $params[$param];
						} else
							$params[$param] = $value;


						if ($rule['params'][$param] != $params[$param]) {
							continue 2;
						}
					}
				}
			}
			
			$URL = self::replaceBracketsWithValues($regexp, $numbers);

			if (isset($rule['params']) && $rule['params'] && !$strictParams)
				$URL .= '?' . http_build_query($rule['params']);

			return $URL;
		}
		
		throw new Exception('No URL found for this rule');
	}

	/**
	* Replace brackets in a regular expression with a corresponding values
	* @param string $regexp - regular expression
	* @param array $replacements - replacements for the brackets
	*/
	public static function replaceBracketsWithValues($regexp, $replacements) {
		$regs = array();
		if (preg_match_all('/[^\\\](\(.+?[^\\\]\))/sui', $regexp, $regs)) {
			for ($i = 0; $i < count($regs[1]); $i++) {
				$n = strpos($regexp, $regs[1][$i]);
				$before = substr($regexp, 0, $n);
				$after = substr($regexp, $n + strlen($regs[1][$i]));

				$regexp = $before . $replacements[$i + 1] . $after;
			}
		}

		$regexp = str_replace('\/', '/', $regexp);

		return $regexp;
	}

	/**
	* Helper function that fills array recursively
	* @param array $source - source array with URLParam objects
	* @param array $regs - values to replace URLParam objects with
	*/
	private static function fillArrayByRule($source, $regs) {
		$result = $source;	
		foreach ($source as $key => $value) { 
			if ($value instanceof URLParam) {
				if (isset($regs[$value->getNumber()])) {
					$result[$key] = $regs[$value->getNumber()];
				} else {
					throw new Exception('Regexp does not match params count: '.$key);
				}
			} else
			if (is_array($value)) {
				$result[$key] = self::fillArrayByRule($value, $regs);
			}
		}
		
		return $result;
	}
}
?>
