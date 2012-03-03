<?php
AutoLoad::path(dirname(__FILE__) . '/drawable.php');
AutoLoad::path(dirname(__FILE__). '/../../classes/utils/inflector.php');

class XWidget implements Drawable, Iterator {
	protected $_properties = array();
	protected $_last = false;
	protected $_title = null;
	protected $_class = null;
	
	protected $_owner = null;
	protected $_name = null;

	public $_current = null;
	public $_key = null;

	public function __construct() { 
		$this->_class = get_class($this);
	}

	public function __destruct() { 
		if (is_array($this->_properties))	
			foreach ($this->_properties as &$property)  
				unset($property);
		
		unset($this->_properties);
	}

	public function __set($name, $value) { 
		//tell the widget who it's owner
		if (is_object($value) && is_subclass_of($value, 'XWidget')) 
			$value->initialize($this, $name);

		$this->_properties[$name] = $value;
	}

	public function __unset($name) { 
		unset($this->_properties[$name]);
	}

	public function __isset($name) { 
		return isset($this->_properties[$name]);
	}
	
	public function __get($name) { 
		if (isset($this->_properties[$name]))
			return $this->_properties[$name];

		return null;
	}
	
	public function initialize($owner, $name) { 
		$this->_owner = $owner;
		$this->_name = $name;
	}

	protected function _draw_var($var) {
		if ($var === null)
			return;

		if (is_object($var)) {
			if ($var instanceof Drawable) {
				return $var->_draw();
			} elseif (is_a($var, 'DOMElement')) {
				return array(':xml' => $var->ownerDocument->saveXML($var));
			} elseif (is_a($var, 'DOMDocument')) {
				return array(':xml' => $var->saveXML($var->documentElement));
			} elseif (is_a($var, 'ModelIterator')) {
				$canvas = $this->_draw_numeric_array($var);
				return $canvas;
			} else {
				$canvas = $this->_draw_assoc_array($var);
				$canvas[':attributes']['class'] = get_class($var);
				return $canvas;
			}
		} elseif (is_array($var)) {
			if (self::is_numeric_array($var))
				return $this->_draw_numeric_array($var);
			else
				return $this->_draw_assoc_array($var);
		} else {
			return $var;
		}
	}
	
	protected function _draw_assoc_array($array) {
		$result = array();
		foreach ($array as $key => $val) {
			if (is_numeric($key)) {
				$id = (is_object($val) && ($tmp = $val->id)) ? (string) $tmp : $key;
				$result[] = array_merge(array(':attributes' => array('id' => $id)),
					$this->_draw_var($val));
			} else {
				$result[$key] = $this->_draw_var($val);
			}
		}
		return $result;
	}
	
	protected function _draw_numeric_array($array) {
		$result = array();
		foreach ($array as $key => $val) {
			$node = $this->_draw_var($val);
			$id = (is_object($val) && method_exists($val,'id')) ? $val->id() : $key;
			$item = array_merge(
				array(':attributes' => array('id' => $id)),
				is_array($node) ? $node : array(':text' => $node)
			);
			$result[] = $item;
		}
		return $result;
	}

	public function _draw() {
		$result = array();

		if ($this->_title !== null)
			$result[':attributes']['title'] = strval($this->_title);

		$result[':attributes']['class'] = $this->_class;

		foreach ($this as $name => $var) {
			if (null === $var || !$name) continue;

			$result[$name] = $this->_draw_var($var);
		}
		return $result;
	}
	
	/**
	* Sets title of an element
	*/
	public function &title($title = null) { 
		if (null === $title) 
			return $this->_title;

		$this->_title = $title;

		return $this;
	}
	
	public function _load_assoc($assoc_array) { 
		foreach ($assoc_array as $key => $value) {
			if (is_object($this->$key) && method_exists($this->$key, 'set'))
				$this->$key->set($value);
			else
				$this->$key = $value;
		}
	}

	public function next() {
		$next = each($this->_properties); 

		if ($this->_last = ($next === false))
			return null;
		
		list($this->_key, $this->_current) = $next;

		return $this->_current;
	}

	public function current() {
		if (null !== $this->_current)
			return $this->_current;
		else 
			return $this->next();
	}
	
	public function key() { 
		return $this->_key;
	}

	public function rewind() { 
		$this->_last = false;
		$this->_current = null;
		return reset($this->_properties);
	}

	public function valid() {
		return !($this->_last || !$this->_properties);
	}

	public function get_nested_widgets($class = null) { 
		$widgets = array();
		foreach ($this as $name => $widget) { 
			if (is_a($widget, __CLASS__)) {
				if (!$class || is_a($widget, $class))
					$widgets[$name] = $widget;

				$nested_widgets = $widget->get_nested_widgets($class);

				$widgets = array_merge($widgets, $nested_widgets);
			}
		}

		return $widgets;
	}

	public static function is_numeric_array($array) {
		foreach ($array as $i => $value) 
			return (0 === $i);
	}
}
?>
