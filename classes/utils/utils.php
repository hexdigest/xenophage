<?php
/**
 * Different utilites
 */
Utils::define("TEST_DOMAINS", "");
Utils::define("MAIL_FEEDBACK", "feedback@mobi-money.ru");

class Utils {
	public static function define($constant, $value) {
		if (defined($constant))
			return;
	
		define($constant, $value);
	}

	/**
	* Generates string of @length random @characters (digits if not specified)
	* @param $langth - length of generated string
	* @param $characters - characters to use during generation
	*/
	public static function randomString($length, $characters = '1234567890') {
		$random_string = '';
	
		$n = strlen($characters);

		while ($length--)
			$random_string .= substr($characters,mt_rand()%$n,1);
		
		return $random_string;
	}

	/**	
	* More sophisticated version of sprintf
	* @param string $pattern - sprintf like-pattern referencing to $replacements array in
	*		a form: '%(hello)s, %(world)s!'
	* @param array $replacements - associative array of replacements, i.e.
	*		array('world' => 'universe', 'hello' => 'hi')
	*
	* Utils::sprintfa('%(hello)s, %(world)s!', array('world' => 'universe', 'hello' => 'hi));
	* returns "Hi, universe!"
	*/
	public static function sprintfa($pattern, $replacements) {
		$sprintf_args = array();
		if (preg_match_all('/%\((.*?)\)/sui', $pattern, $regs)) {
			foreach ($regs[1] as $match) {
				if (isset($replacements[$match])) 
					$sprintf_args[] = $replacements[$match];
				else
					$sprintf_args[] = '';

				$pattern = preg_replace('/%\('.$match.'\)/sui', '%', $pattern);
			}
		}

		array_unshift($sprintf_args, $pattern);

		return call_user_func_array('sprintf', $sprintf_args);
	}

	/**
	* Returns instance of given className
	* @param string $className - name of the class
	* @param mixed $params - parameters passed to the constructor
	*/
	public static function getClassInstance() { 
		$args = func_get_args();
		$className = array_shift($args);

		$refClass = new ReflectionClass($className); 

		return $args ? $refClass->newInstanceArgs($args): $refClass->newInstance();
	}
}
?>
