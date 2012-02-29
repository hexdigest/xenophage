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
	 * Loads content from specified URL
	 * @param string $url URL to load from
	 * @param array|string $postParams HTTP POST parameters
	 * @param array $headers additional HTTP headers
	 * @return string loaded content
	 */
	public static function getFromURL($url, $postParams = null, $headers = null) {
		$result = "";
		$parsed = self::parseURL($url);
		if(!isset($parsed['scheme']) || $parsed['scheme'] == 'file') {
			$fileName = $parsed['path'];
			if(!file_exists($fileName))
				throw new Exception("File not found: {$url}");
			$result = file_get_contents($fileName);
		} else {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

			if($postParams) {
				curl_setopt($ch, CURLOPT_PORT, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postParams);
			}
			if($headers) {
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			}

			$result = curl_exec($ch);
			$code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$error  = curl_error($ch);

			curl_close($ch);

			if ($error || $code != 200)
				throw new Exception($error, $code);
			
		}

		return $result;
	}

	/**
	* Replacement of buggy parse_url that don't understand empty host 
	* for example: "scheme:///path/?param1=param1"
	* @param string $URL - url to parse
	*/
	public static function parseURL($URL) { 
		if (strpos($URL, ':///')) {
			$URL = str_replace(':///', '://somehost/', $URL);
			$result = parse_url($URL);
			if ($result) {
				$result['host'] = '';
			}
		} else {
			$result = parse_url($URL);
		}

		return $result;
	}

	/**
	 * Send mail
	 * @param string $to recipient
	 * @param string $subject
	 * @param string $message
	 * @param string $headers additional headers to send
	 * @param array|iFile $file_attachments
	 * @param string $from
	 */
	public function mail($to, $subject, $message, $headers = '', $fileAttachments = null, $from = null) {
		$from = is_null($from) ? 'robot@mobi-money.ru' : $from;
		$headers = $headers . ($headers ? "\r\n" : '').
					"From: {$from}\r\n".
					"To: {$to}\r\n".
					"MIME-Version: 1.0\r\n";

		$test_domains = explode(',', TEST_DOMAINS);
		if (in_array($_SERVER['HTTP_HOST'], array_map("trim",$test_domains)) && $to == MAIL_FEEDBACK)
			$message = '*ТЕСТ ФОРМЫ*<br/>' . $message;

		if (!is_array($fileAttachments)) {
			$fileAttachments = array($fileAttachments);
		}

		if (is_subclass_of($fileAttachments[0], 'iFile')) {
			$boundary = '--==================_846811060==_';
			$headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

			$attachment_messages = array();
			foreach ($fileAttachments as $file_attachment) {
				if (is_subclass_of($file_attachment,'iFile')) {
					$attachment_message  = "Content-Disposition: attachment; filename=\"{$file_attachment->get_filename()}\"\r\n";
					$attachment_message .= "Content-Transfer-Encoding: base64\r\n";
					$attachment_message .= "Content-Type: {$file_attachment->mime_type}\r\n\r\n";
					$attachment_message .= chunk_split(base64_encode($file_attachment->get_contents()));
					$attachment_messages[] = $attachment_message;
				}
			}

			//дописываем boundary в текст сообщения
			$text_message .= "--".$boundary."\r\n";
			$text_message .= "Content-Type: text/html; charset=windows-1251\r\n\r\n";
			$text_message .= $message."\r\n";

			$message = $text_message;
			if (count($attachment_messages)) {
				$attachments_dump = "--{$boundary}\r\n".
									join("--{$boundary}\r\n", $attachment_messages).
									"\r\n--{$boundary}--";
				$message .= $attachments_dump;
			}
		} else {
			$headers .= "Content-Type: text/html; charset=windows-1251\r\n\r\n";
		}

		mail($to, $subject, iconv("UTF-8", "windows-1251",$message),
			iconv("UTF-8", "windows-1251", $headers));
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
