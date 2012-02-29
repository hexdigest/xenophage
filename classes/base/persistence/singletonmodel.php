<?php
require_auto(XENOPHAGE . '/classes/xmodel.php');

class SingletonModel extends XModel {
	protected static $instances = array();
	protected $_is_instance = null;

	public function __construct($initializer = null) { 
		if ($initializer) {
			if (is_array($initializer)) 
				$id = $initializer['id'];
			else 
				$id = strval($initializer);
		} 

		if (isset(self::$instances[get_class($this)][$id])) {
			parent::__construct();
			$this->id = $id;
		} else {
			parent::__construct($initializer);

			if ($this->id()) 
				$this->singletize();
		}
	}

	public function id($id = null) { 
		$old_id = $this->id;

		parent::id($id);

		if ($id && !$old_id) 
			$this->singletize();

		return $this->id;
	}

	public function __call($method, $args) { 
		if ($this->_is_instance || ! $instance = $this->get_instance())
			return parent::__call($method, $args);
		else
			return $instance->__call($method, $args);
	}

	public function __get($name) {
		if ($this->_is_instance || !$this->id())
			return parent::__get($name);

		return $this->get_instance()->__get($name);
	}

	public function __set($name, $value) { 
		if ($this->_is_instance || !$this->id())
			return parent::__set($name, $value);

		return $this->get_instance()->__instance_set($name, $value);
	}

	public function __instance_set($name, $value) { 
		return parent::__set($name, $value);
	}

	public function _draw(&$canvas) { 
		if ($this->_is_instance || !$this->id())
			return parent::_draw($canvas);

		return $this->get_instance()->_draw($canvas);
	}

	public function &get_instance() {
		if ($this->id())
			return self::$instances[get_class($this)][$this->id()];
	}

	public function singletize() { 
		if ($this->_is_instance)
			return;

		$this->_is_instance = true;
		self::$instances[get_class($this)][$this->id()] = &$this;
	}

	public function create_table() { 
		if (get_class($this) != __CLASS__)
			return parent::create_table();
	}
	
	public function next() {
		$next = each($this->_properties); 
		
		if ($this->_last = ($next === false))
			return null;
		
		list($key, $current) = $next;
		
		$this->_key = $key;
		$this->_current = $this->$key;
		
		return $this->_current;
	}
}
?>
