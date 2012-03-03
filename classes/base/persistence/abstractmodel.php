<?php
AutoLoad::path(dirname(__FILE__).'/../../widgets/xwidget.php');
AutoLoad::path(dirname(__FILE__).'/relations/');


abstract class AbstractModel extends XWidget { 
	protected static $_attributes = array(); //Native attributes for all models

	/**
	* Return array of persistent fields. Other fields
	* will not be stored via persistence layer
	*/
	abstract public function createModel();

	public function __construct($id = null) { 
		parent::__construct();

		$class = $this->_class;

		$this->id = 0;
		$this->createModel();
		$this->createRelationsAttributes();

		if (! isset(self::$_attributes[$class])) 
			self::$_attributes[$this->_class] = array_keys($this->_properties);

		if (null !== $id) {
			//Loading model
		}
	}
	
	protected function createRelationsAttributes() {
		foreach (Relation::getAttributesFor($this->_class) as $name => $value) 
			$this->$name = $value;
	}

	/**
	* Special methods for finding objects of current class and for defining
	* relations with other classes
	*/
	public static function __callStatic($method, $args) {
		if (0 === strstr($method, 'find_')) {
			echo 'Executing finder:', $method, PHP_EOL;
		} else {
			//Create relations, i.e. self::HasOne('Boomerang');
			$method::create(get_called_class(), $args);
		}
	}

	/**
	* Return model serialized into associative array
	*/
	public function getValuesAsArray() { 
		$values = array();

		//Field names to save
		foreach (self::$_attributes[$this->_class] as $field) { 
			if(!isset($this->_properties[$field])) {
				$values[$fieldName] = null;
			} else
			//normal field
			if ($this->_properties[$field] instanceof iInput) {
				$values[$fieldName] = $this->_properties[$field]->get();
			} else
			//scalars
			if (! is_object($this->_properties[$field])) {
				$values[$fieldName] = $this->_properties[$field];
			}
			//other types of fields is ignored
		}

		return $values;
	}

	/**
	* Return identifier of the model
	*/
	public function id() { 
		return $this->id;
	}
	
	/**
	* More sophisticated setter that call iInput->set
	* if accessed field is instance of iIput
	*/
	public function __set($name, $value) { 
		if ($this->$name instanceof iInput) 
			$this->$name->set($value);
		else
			parent::__set($name, $value);
	}
}
?>
