<?php
require_auto(XENOPHAGE . '/classes/xwidget.php');
require_auto(XENOPHAGE . '/classes/modeliterator.php');

AutoLoad::path(dirname(__FILE__).'/modelexception.php');

class AbstractModel extends XWidget {
	public $_engine = null;
	public $_sql = null;
	public $_table = null;

	public static $relations = array();
	public static $native_fields = array();

	protected $id = null;

	protected $_model = array(); //array describing model and all it's relations
	protected static $_tables = array();

	protected $_method = null;
	protected $_action = null;

	/**
	* Initializer can be assoc array or integer. In a first case
	* it treated as new object properties, otherwise it treated
	* as an object id
	*/
	public function __construct() {
		parent::__construct();

		$this->_sql = $this->_engine->_sql;
		$this->_table = $this->getTableName();

		self::$relations[$this->_class] = array(
			'has_one' => array(),
			'has_many' => array(),
			'has_and_belongs_to_many' => array(),
			'belongs_to' => array()
		);

		$this->model();

		if ($initializer) {
			if (is_array($initializer))
				$this->_load_assoc($initializer);
			else
				$this->load($initializer);
		}
	}

	public static function createModel() {
		return array();
	}

	public function __destruct() {
		parent::__destruct();

		unset($this->_sql);
		unset($this->_has_one);
		unset($this->_belongs_to);
		unset($this->_has_many);
		unset($this->_has_and_belongs_to_many);
	}

	public function model() {}

	/**
	* Converts object to a human-readable string
	*/
	public function __toString() {
		foreach ($this as $p)
			if (is_string($p))
				return $p;
			elseif (is_object($p) && method_exists($p,'get') && is_string($p->get()))
				return $p->get();

		return get_class($this).' #'.$this->id();
	}

	public function save() {
		if ($this->id())
			return $this->update();
		else
			return $this->insert();
	}

	/**
	* Loads model from the data storage
	*/
	public function load($id = null) {
		if (null === $id)
			$id = $this->id();

		if (!$this->_sql->query(
			'SELECT * FROM '.$this->_table.' WHERE id="'.addslashes($id).'"'
		))
			throw new ModelException(
				'No such '.Inflector::humanize($this->_class), 404);

		$this->_load_assoc($this->_sql->a());
		$this->id($id);
	}

	public function update() {
		$fields = $this->get_native_fields();

		if (!$n = count($fields)) //nothing to update
			return $this->id();

		$set = array();
		foreach ($fields as $field) {
			if ('id' == $field)
				continue;

			if (_is_a($this->$field, 'iInput') && (null === $this->$field->get()))
				$set[] = '`'.$field.'`='.$this->_sql->db_type_cast(null);
			else
				$set[] = '`'.$field.'`='.$this->_sql->db_type_cast($this->$field);
		}

		$this->_sql->begin();
		
		$query =
			'SELECT 1 FROM '.$this->_table.
			' WHERE id="'.addslashes($this->id()).'"';
			
		if (0 == $this->_sql->query($query))
			$this->insert();
		else
			if ($set)
			{
				$query = 'UPDATE '.$this->_table.' SET '.join(',', $set).
					$this->where_condition();

				$this->_sql->query($query);
			}

		$this->_sql->commit();

		return $this->id();
	}

	protected function where_condition() { 
		return ' WHERE id="'.addslashes($this->id()).'"';
	}

//	public function prepare_insert

	public function prepare_insert_sql() {
		$fields = $this->get_native_fields();
		if (! $this->id())
			$fields = array_diff($fields, array('id'));

		if (! $n = count($fields)) //nothing to update
			return;

		$not_empty = array();
		$values = array();

		foreach ($fields as $field) {
			if (_is_a($this->$field, 'iInput') && (null === $this->$field->get()))
				continue;

			$not_empty[] = $field;
			$values[] = $this->_sql->db_type_cast($this->$field);
		}

		$query =
			'INSERT INTO '.$this->_table.'(`'.implode('`,`',$not_empty).'`) '.
			'VALUES('.join(',', $values).')';
		return $query; 
	}

	public function insert() {
		//return id of inserted object
		$id = $this->_sql->query($this->prepare_insert_sql());

		if (!$this->id())
			$this->id($id);

		return $this->id();
	}

	/**
		Save model to data storage as new record
	*/
	public function save_insert(){
		$this->id = null;
		return $this->save();
	}

	/**
	* Deletes model. If $deep is not null than has_one and has_many
	* related models will be recursively deleted
	*/
	public function delete($deep = false) {
		if (($id = $this->id()) === null)
			return false;

		$this->_sql->begin();

		$this->_sql->query(
			'DELETE FROM '.$this->_table.' WHERE id="'.$id.'"');

		if ($deep) {
			$foreign_key = Inflector::foreign_key($this);

			$f1 = array('Inflector', 'singularize');
			$f2 = array('Inflector', 'classify');

			$has_one = $this->has_one();
			$has_many = array_map($f1, $this->has_many());

			$one_to_one = array_map($f2, array_merge($has_one, $has_many));
			foreach ($one_to_one as &$model)
				$model = XModel::get_table($model);

			$habtm = array_map($f2, array_map($f1, $this->has_and_belongs_to_many()));

			foreach ($habtm as $k => &$v)
				$v = self::reference_table($v, get_class($this));

			$tables = array_merge($habtm, $one_to_one);

			foreach ($tables as $table)
				$this->_sql->query(
					'DELETE FROM '.$table.' WHERE '.$foreign_key.'="'.$id.'"');
		}

		$this->_sql->commit();

		return true;
	}

	public function create_table() {
		foreach ($this->has_and_belongs_to_many() as $model) {
			$related_class = Inflector::camelize(Inflector::singularize($model));

			$relation_model = new XModel;
			$relation_model->_table =
				self::reference_table($this->_class, $related_class);

			//reference table already exists
			if (! $this->_sql->query('SHOW TABLES LIKE "'.$relation_model->_table.'"')) {
				$index1 = Inflector::foreign_key($this->_class);
				$index2 = Inflector::foreign_key($related_class);

				$relation_model->$index1 = new iUnsignedBigInt;
				$relation_model->$index1->set(0);
				$relation_model->$index2 = new iUnsignedBigInt;
				$relation_model->$index2->set(0);

				$relation_model->create_index(
					array($index1,$index2), SQL_INDEX_PRIMARY);

				$relation_model->create_index($index2, SQL_INDEX);
				//create relation table
				$relation_model->create_table();
			}
		}

		//if the table already exists skipping creation
		if (!$this->_sql->query('SHOW TABLES LIKE "'.self::get_table($this).'"')) {
			$this->_sql->create_model_table($this);
			return true;
		} else
			return false;
	}

	/**
	* Adds an index to the table that represents $this model
	* $index_type can be PRIMARY, UNIQUE or just an INDEX
	*/
	public function create_index($fields_list, $index_type = SQL_INDEX_PRIMARY) {
		if (!isset($this->_indexes[$index_type]))
			$this->_indexes[$index_type] = array();

		if (!is_array($fields_list))
			$fields_list = array($fields_list);

		if (!in_array($fields_list,$this->_indexes[$index_type]))
			$this->_indexes[$index_type][] = $fields_list;
	}

	public function get_indexes() {
		return $this->_indexes;
	}

	/*
		Retrieve information about the native object properties
	*/
	public function get_native_fields() {
		$class = get_class($this);

		if (!isset(self::$native_fields[$class])) {
			$a = $this->_sql->fields($this->_table);
			self::$native_fields[$class] = $a;
		}

		return self::$native_fields[$class];
	}

	public function get_relations() {
		return self::$relations[get_class($this)];
	}

	public function has_one($underscored_class_name = null, $through = null) {
		if (null === $underscored_class_name)
			return array_keys(self::$relations[$this->_class]['has_one']);

		if ($through)
			$this->_through[$underscored_class_name] = $through;
		else
			self::$relations[$this->_class]['has_one'][$underscored_class_name] =
				Inflector::camelize($underscored_class_name);
	}

	public function belongs_to($underscored_class_name = null) {
		if (null === $underscored_class_name)
			return array_keys(self::$relations[$this->_class]['belongs_to']);

		$underscored_class_name = Inflector::underscore($underscored_class_name);

		$camel_class = Inflector::camelize($underscored_class_name);
		self::$relations[$this->_class]['belongs_to'][$underscored_class_name] =
			$camel_class;

		$index = Inflector::foreign_key($camel_class);

		$this->$index = 0;
		$this->create_index($index, SQL_INDEX_FOREIGN_KEY);
	}

	public function has_many($underscored_plural_class_name = null,
		$through = null) {

		if (null === $underscored_plural_class_name)
			return array_keys(self::$relations[$this->_class]['has_many']);

		if ($through)
			$this->_through[$underscored_plural_class_name] = $through;
		else
			self::$relations[$this->_class]['has_many'][$underscored_plural_class_name] =
				Inflector::camelize(Inflector::singularize($underscored_plural_class_name));
	}

	/**
	* Tell the model, that it has and belongs to many $plural_class_name
	* if the $plural_class_name is not passed, method returns all
	* $plural_class_names which belong to $this model
	*/
	public function has_and_belongs_to_many($underscored_plural_class_name=null,
		$through = null) {
		if (null === $underscored_plural_class_name) {
			if (self::$relations[$this->_class]['has_and_belongs_to_many'])
				return array_keys(self::$relations[$this->_class]['has_and_belongs_to_many']);
			else
				return array();
		}

		if ($through)
			$this->_through[$underscored_plural_class_name] = $through;
		else
			self::$relations[$this->_class]['has_and_belongs_to_many'][$underscored_plural_class_name] =
				Inflector::camelize(Inflector::singularize($underscored_plural_class_name));
	}

	public function __call($method, $args) {
		$model = Inflector::classify(Inflector::singularize($method));
		if (is_subclass_of($model, 'XModel')) {
			if (!$this->id())
				throw new ModelException('Model must be stored to resolve relations');

			$iterator = new ModelIterator($this->_class);
			$iterator = $iterator->find_by_id($this->id());

			return call_user_func_array(array($iterator, $method), $args);
		}

		throw new ModelException(
			'Method not supported by this model: '.get_class($this).'::'.$method);
	}

	//For static search functions like User::find_by_login('username');
	public static function __callStatic($method, $args) { 
		$iterator = new ModelIterator(get_called_class());

		return call_user_func_array(array($iterator, $method), $args);
	}

	public function __set($name, $value) {
		parent::__set($name, $value);

		if (in_array($name, $this->has_one())){
			if (null === $this->id())
				$this->save();

			$classname = Inflector::camelize($name);

			if (_is_a($value, $classname)) {
				$foreign_key = $this->foreign_key();

				if ($this->$name->$foreign_key != $this->id())
					$this->$name->save();
			}
		} else
		if (in_array($name, $this->belongs_to())) {
			$class_name = Inflector::camelize($name);

			if (_is_a($value, $class_name)) {
				if (null === $value->id())
					$this->$name->save();

				$foreign_key = Inflector::foreign_key($class_name);

				$this->$foreign_key = $this->$name->id();
			}
		}
	}

	public function __get($name) {
		$value = parent::__get($name);
		if ($value !== null)
			return $value;

		$class_name = Inflector::camelize($name);

		if (isset($this->_through[$name])) {
			$through = $this->_through[$name];
			return $this->$through->$name;
		} else
		if (in_array($name, $this->has_one())) {
			if (!$this->_has_one[$name]) {
				$class_name = Inflector::camelize($name);

				$query =
					'SELECT * FROM '.self::get_table($class_name).' '.
					'WHERE '.$this->foreign_key().'="'.$this->id().'" '.
					'LIMIT 1';

				if ((null == $this->id()) || ! $this->_sql->query($query))
					return null;

				$instance = new $class_name;
				$instance->_load_assoc($this->_sql->a());

				$this->_has_one[$name] = $instance;
			}

			return $this->_has_one[$name];
		} else
		if (in_array($name, $this->belongs_to())) {
			if (!$this->_belongs_to[$name]) {
				$foreign_key = Inflector::foreign_key($class_name);

				if (! $this->$foreign_key)
					return null;

				$instance = new $class_name($this->$foreign_key);
				$this->_belongs_to[$name] = $instance;
			}

			return $this->_belongs_to[$name];
		} else
		if (in_array($name, $this->has_many())) {
			if (!$this->id())
				return array();

			if (!$this->_has_many[$name]) {
				$model = Inflector::singularize($class_name);
				$this->_has_many[$name] = new ModelIterator(
					array($this,$model),
					array('where' => array($this->_table.'.id = '.$this->id()))
				);
			}

			return $this->_has_many[$name];
		} else
		if (in_array($name, $this->has_and_belongs_to_many())) {
			if (!$this->_has_and_belongs_to_many[$name]) {
				$model = Inflector::singularize($class_name);
				$this->_has_and_belongs_to_many[$name] = new ModelIterator(
					array($this, $model),
					array('where' => array($this->_table.'.id = "'.$this->id().'"'))
				);
			}

			return $this->_has_and_belongs_to_many[$name];
		}

		return null;
	}

	public function id($id = null) {
		if (null !== $id)
			$this->id = strval($id);

		return $this->id;
	}

	public function _draw(&$canvas) {
		parent::_draw($canvas);

		$canvas->setAttribute('id', $this->id());
		$canvas->setAttribute('baseclass', 'XModel');

		if ($this->_method)
			$canvas->setAttribute('method', $this->_method);

		if ($this->_action)
			$canvas->setAttribute('action', $this->_action);
	}

	public function _load_assoc($assoc_array) {
		foreach ($assoc_array as $key => $value) {
			if ('id' == $key)
				$this->id($value);
			elseif (is_object($this->$key) && method_exists($this->$key, 'set'))
				$this->$key->set($value);
			else
				$this->$key = $value;
		}
	}

	public function submit($strict = true) {
		$class = get_class($this);

		if (!$_REQUEST[$class])
			return false;

		foreach ($this->get_nested_widgets('iInput') as $name => $value) 
			$value->set($_REQUEST[$class][$name]);

		return $strict ? $this->check() : true;
	}

	public function check() {
		$errors = 0;

		foreach ($this->get_nested_widgets('iInput') as $key => $v)
			if (!$v->check())
				$errors++;

		return (0 === $errors);
	}

	public function foreign_key() {
		return Inflector::foreign_key($this);
	}

	public function __clone() {
		$this->id = null;

		foreach ($this->_has_many as $key => &$value)
			$value = null;

		foreach ($this->_has_and_belongs_to_many as $key => &$value)
			$value = null;

		foreach ($this->_has_one as $key => &$value)
			$value = null;

		foreach ($this->_belongs_to as $key => &$value)
			$value = null;

		parent::__clone();
	}

	public function add_property($name, $object) {
		$this->$name = null;

		$this->_sql->query(
			'ALTER TABLE '.$this->_table.' ADD `'.$name.'` '.$object->_init_db());

		self::$native_fields[get_class($this)] = $this->_sql->fields($this->_table);
	}

	public function delete_property($name) {
		$this->$name = null;

		$this->_sql->query(
			'ALTER TABLE '.$this->_table.' DROP `'.$name.'`');

		self::$native_fields[get_class($this)] = $this->_sql->fields($this->_table);
	}

	public function set_method($method) {
		$this->_method = $method;
	}

	public function set_action($action) {
		$this->_action = $action;
	}

	public function get_table_name() {
		return XEN_SQL_TABLES_PREFIX . Inflector::tableize(get_class($this));
	}

	public static function get_table($model) {
		if (is_object($model))
			$class = get_class($model);
		else
			$class = $model;

		$class = strtolower($class);

		if (!isset(self::$_tables[$class])) {
			$model = new $class;
			$table = $model->get_table_name();

			self::$_tables[$class] = $table;
		}

		return self::$_tables[$class];
	}

	public static function reference_table($model1, $model2) {
		$table1 = self::get_table($model1);
		$table2 = self::get_table($model2);

		return min($table1, $table2).'_'.max($table1, $table2);
	}

	public function all() {
		if (function_exists('get_called_class'))
			return new ModelIterator(get_called_class());
		else {
			$class = get_class($this);
			return new ModelIterator($class);
		}
	}

	function get_widgets_values() {
		$array = array();		

		foreach ($this->get_nested_widgets('iInput') as $name => $object)
			$array[$name] = $object->get();	

		return $array;
	}
	/**
	 * Throws ModelException binding to this class.
	 */
	protected function raise($msg = null, $code = null) {
		$e = new ModelException($msg, $code);
		$e->gen_class = get_class($this);
		throw $e;
	}
}
?>
