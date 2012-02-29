<?php
class PHPErrorException extends Exception {
	protected $inFile = null;
	protected $inLine = null;

	public function __construct($message, $errorType, $inFile, $inLine) {
		$this->codeFile = $inFile;
		$this->codeLine = $inLine;

		parent::__construct($message, $errorType);
	}

	public function getFileName() {
		return $this->inFile;
	}

	public function getLineNumber() {
		return $this->inLine;
	}

	public function errorToException($errNo, $errStr, $errFile, $errLine) {
		$errStr = preg_replace('/\[<(.*?)>\]/s', '', $errStr);
		$errStr = preg_replace('/^(.*?) : /s', '', $errStr);

		if (! in_array($errNo, array(E_NOTICE, E_USER_NOTICE, E_STRICT)))
			throw new PHPErrorException($errStr, $errNo, $errFile, $errLine);
	}

	public static function setHandler() {
		set_error_handler(array(__CLASS__, 'errorToException'));
	}
}
?>
