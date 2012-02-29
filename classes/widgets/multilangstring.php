<?php
AutoLoad::path(dirname(__FILE__).'/xwidget.php');

/**
 * Description of multilangstring
 */
class MultiLangString extends XWidget implements ArrayAccess {
	protected static $_lang = null;

	public function _draw() { 
		return $this->__toString();
	}

	public static function setCurrentLanguage($lang) { 
		self::$_lang = $lang;
	}

	/**
	 * Calls on implicit or explicit cast to string
	 */
	public function  __toString() {
		return $this->getStringOnLang(self::$_lang);
	}

	/**
	* Returns string on given language
	* @param string $language - language
	*/
	public function getStringOnLang($language) {
		if (isset($this->_properties[$language])) {
			$result =  $this->_properties[$language];
		} else {
			$values = array_values($this->_properties);
			$result = strval(array_shift($values));
		}

		return $result;
	}

	public function get() {
		return $this->_properties;
	}

	public function set($strings) {
		if($strings instanceof MultiLangString)
			$this->_properties = $strings->get();
		else
			$this->_properties = $strings;
	}

	/**
	* Return MultiLangString object from given dom object and XPath expression
	*/
	public static function createFromDOM($expression, $dom, $contextNode = null) { 
		$strings = array();
		foreach ($dom->xpath($expression, $contextNode) as $node) { 
			if ($node instanceof DOMText) { //Text node
				$lang = $node->parentNode->getAttributeNS('http://www.w3.org/XML/1998/namespace', 'lang');
				$strings[$lang] = $node->nodeValue;
			} else
			if ($node instanceof DOMNode) { //DOM node
				$lang = $node->getAttributeNS('http://www.w3.org/XML/1998/namespace', 'lang');
				$newDOM = new XDOM;
				$newNode = $newDOM->importNode($node, true);
				$newDOM->appendChild($newNode);
				$strings[$lang] = strval($newDOM);
			}
		}

		$mString = new MultiLangString;
		$mString->set($strings);	
		
		return $mString;
	}

	//***************************************************************
	// ArrayAccess implementation

	/**
	*
	* @param int $offset
	* @param mixed $value
	*/
	public function offsetSet($offset, $value) {
		$this->_properties[$offset] = $value;
	}

	/**
	*
	* @param int $offset
	* @return boolean
	*/
	public function offsetExists($offset) {
		return isset($this->_properties[$offset]);
	}

	/**
	*
	* @param int $offset
	*/
	public function offsetUnset($offset) {
		unset($this->_properties[$offset]);
	}

	/**
	*
	* @param int $offset
	* @return Model
	*/
	public function offsetGet($offset) {
		return isset($this->_properties[$offset]) ? $this->_properties[$offset] : null;
	}

	//***************************************************************
	// Iterator implementation

	/**
	*
	*/
	public function rewind() {
		reset($this->_properties);
	}

	/**
	*
	* @return Model
	*/
	public function current() {
		return current($this->_properties);
	}

	/**
	*
	* @return int
	*/
	public function key() {
		return key($this->_properties);
	}

	/**
	*
	*/
	public function next() {
		next($this->_properties);
	}

	/**
	*
	* @return boolean
	*/
	public function valid() {
		return  (bool)current($this->_properties);
	}

}
?>
