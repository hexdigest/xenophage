<?php
AutoLoad::path(dirname(__FILE__).'/sqliterator.php');

class ModelIterator extends SQLIterator {
	protected $models = null;
	protected $conditions = null; 
	protected $passed_models = null;
	protected $count_total = null;

	public $instance = null;

	public $query = null;

	public function __construct($models, $conditions = array()) {	
			
		if (!is_array($models))
			$models = array($models);

		$this->passed_models = $models;

		foreach (array('columns','where','order_by','group_by','limit','become') as $key) 
			$this->conditions[$key] = 
				isset($conditions[$key]) ? $conditions[$key] : array();

		foreach ($models as &$model) 
			if (is_object($model)) 
				$model = get_class($model);
		
		$this->models = $models;

		$this->query = 
			'SELECT '.
				$this->columns().
				$this->from($models).' '.$this->where().' '.$this->group_by().' '.
				$this->order_by().' '.$this->limit()
		;			
		
		parent::__construct(clone XEngine::instance()->_sql, $this->query);
	}
	
	/**
	* Adds new object to the has_many/has_and_belongs_to_many collection 
	* of another object, use something like this:
	* $picture->comments->add(new Comment);
	*/
	public function add(&$object) {
		$object_class = get_class($object);

		if (strcasecmp($object_class, $model = end($this->models)))
			throw new Exception($object_class.' is not a '.$model);

		list($related_obj) = array_slice($this->passed_models, -2, 1);

		if (!(_is_a($related_obj, 'XModel') && $related_obj->id()))
			throw new Exception(
				'Can\'t add: '.get_class($related_obj).' must be stored model');
		
		$property_name = 
			Inflector::underscore(Inflector::pluralize(get_class($object)));

		if (in_array($property_name ,$related_obj->has_and_belongs_to_many())) { 
			try {
				$this->_sql->begin();

				if (!$object->id())
					$object->save();

				if (!$this->_sql->query(
					'SELECT 1 FROM '.XModel::reference_table($object, $related_obj).' '.
					'WHERE '.
						'`'.$object->foreign_key().'`='.$object->id().' AND '.
						'`'.$related_obj->foreign_key().'`='.$related_obj->id()
				)) $this->_sql->query(
					'INSERT '.XModel::reference_table($object, $related_obj).
					'('.$object->foreign_key().','.$related_obj->foreign_key().') '.
					'VALUES('.$object->id().','.$related_obj->id().')'
				,false);

				$this->_sql->commit();

				return $object->id();
			} catch (Exception $e) {
				$this->_sql->rollback();
				throw $e;
			}
		} elseif (in_array($property_name ,$related_obj->has_many())) { 
			$related_key = $related_obj->foreign_key();
			$object->$related_key = $related_obj->id();

			return $object->save();
		} else
			throw new Exception(
				get_class($related_obj).' is not related to '.$property_name);
	}
	
	/* 
		If HABTM relation processed, this function will delete corresponding
		records in relation table. 
	*/
	public function unlink($obj = null) {
		$table = '';
		if (count($this->models) > 1) {
			list($previous,$current) = array_slice($this->models,-2,2);

			$previous = new $previous;
		
			$property = Inflector::pluralize(Inflector::underscore($current));
		
			//if HABTM then remove only references in reference table
			if (in_array($property, $previous->has_and_belongs_to_many())) {
				$table = XModel::reference_table($previous, $current);
				if ($obj)
					$obj_key = $table.'.'.Inflector::foreign_key($obj);
			} else {
				$table = XModel::get_table($current);
				$obj_key = $table.'.id';
			}
		}

		$query = 'DELETE '.$table.' '.$this->from($this->models).' '.$this->where();

		if ($obj) {
			if (!strlen($obj->id())) //Object specified but not exist
				return;

			$query .= ' AND '.$obj_key.'="'.addslashes($obj->id()).'"';
		}

		$this->_sql->query($query);
	}

	/**
	* Return collection of objects satisfying $conditions
	* $this->accounts->find_by(array('name'=>'Michael'));
	*/
	public function find_by($what) {
		$conditions = $this->conditions;

		if (is_array($what)) { //complex condition
			$table = XModel::get_table(end($this->models));

			foreach ($what as $key => $value) {
				if ($value === null) 
					$conditions['where'][] = $table.'.'.$key.' IS NULL';
				elseif (is_bool($value))
					$conditions['where'][] = $table.'.'.$key.'="'.intval($value).'"';
				elseif (is_array($value) && $value)
					$conditions['where'][] = $table.'.'.$key.
						' IN("'.join('","',array_map('addslashes',$value)).'")';
				elseif ($value instanceof ModelIterator) {
					$ids = array();
					foreach ($value as $id => $obj) 
						$ids[] = $id;

					if ($ids)
						$conditions['where'][] = $table.'.'.$key.
						' IN("'.join('","',array_map('addslashes', $ids)).'")';
				}
				else
					$conditions['where'][] = $table.'.'.$key.'="'.addslashes($value).'"';
			}
			
		} else //String condition that should be appended to WHERE statement
			$conditions['where'][] = $what;

		return new ModelIterator($this->models, $conditions);
	}
	
	public function __call($method, $args) { 
		if (strpos($method, 'find_by_')===0) {
			$conditions = explode('_and_',substr($method,8));

			if ((count($conditions) == 1) && (count($args)>1)) 
				$conditions = array($conditions[0] => $args);
			else
				$conditions = array_combine($conditions, $args);
			
			if (false === $conditions)
				throw new Exception(
					'Wrong parameters count: '.$method.'('.join(',',$args).')');

			return $this->find_by($conditions);
		} else {
			$model = Inflector::classify(Inflector::singularize($method));

			if (!is_subclass_of($model, 'XModel'))
				throw new Exception('Not a model: '.$model);

			$table = XModel::get_table($model);
			
			$models = $this->models;
			$models[] = $model;

			$conditions = $this->conditions;

			$columns = array();
			if (is_array($args[0]))
				$args = $args[0];

			foreach ($args as $column) {
				if (strpos($column, '(') !== false)
					$columns[] = $column;
				else
					$columns[] = $table.'.'.$column;
			}
			
			$conditions['columns'] = array_merge($columns, $conditions['columns']);
			
			return new ModelIterator($models, $conditions);
		}

		throw new Exception('No such method: '.$method);
	}

	public function from($models, $skip_from = false) {
		$tables = array();
		foreach ($models as $model) 
			$tables[$model] = XModel::get_table($model);

		$previous_model = array_shift($models);
		$from = $tables[$previous_model];
		
		foreach ($models as $model) { 
			if (!isset(XModel::$relations[$previous_model]))
				new $previous_model; //filling $previous_model relations array
			
			$rel = XModel::$relations[$previous_model];
			if (in_array($model, $rel['has_many']) || 
				in_array($model, $rel['has_one'])) {

				$from .= 
					' INNER JOIN '.$tables[$model].' ON '.
					$tables[$previous_model].'.id='.$tables[$model].'.'.
					Inflector::foreign_key($previous_model);
			} else
			if (in_array($model, $rel['belongs_to'])) {
				$from .= 
					' INNER JOIN '.$tables[$model].' ON '.
					$tables[$model].'.id='.$tables[$previous_model].'.'.
					Inflector::foreign_key($model);
			} else
			if (in_array($model, $rel['has_and_belongs_to_many'])) {
				$reference_table = XModel::reference_table($model,$previous_model);

				$from .= 
					' INNER JOIN '.$reference_table.' ON '.
						$tables[$previous_model].'.id='.
							$reference_table.'.'.Inflector::foreign_key($previous_model).
					' INNER JOIN '.$tables[$model].' ON '.
							$reference_table.'.'.Inflector::foreign_key($model).'='.
								$tables[$model].'.id';
			} else
				throw new Exception(
					'Model '.$model.' is not related to '.$previous_model);

			$previous_model = $model;
		}
		
		if ($skip_from)
			return $from;

		return 'FROM '.$from;
	}
	
	public function columns() { 
		$table = XModel::get_table(end($this->models));

		if ($columns = func_get_args()) {
			if (is_array($columns[0])) 
				$columns = $columns[0];

			$conditions = $this->conditions;
			
			$conditions['columns'] = array_merge($conditions['columns'], $columns);

			return new ModelIterator($this->models, $conditions);
		} elseif (!$this->conditions['columns']) {
			return $this->as_table().'.* ';
		} else {			
			$fields = array();
			foreach ($this->conditions['columns'] as $field => $sql) {
				if (is_int($field))
					$fields[] = $sql;
				else
					$fields[] = $sql.' AS '.$field;
			}
			
			return join(',', $fields).', '.$table.'.id ';
		}
	}

	public function where() { 
		$where = array();
		
		$table = XModel::get_table(end($this->models));

		if ($this->conditions['where']) {
			foreach ($this->conditions['where'] as $key => $condition) {
				if (is_numeric($key))
					$where[] = $condition;
				elseif (is_array($condition) && $condition) {
					$where[] = $table.'.'.$key.' IN("'.
						join('","', array_map('addslashes', $condition)).'")';
				} else
					$where[] = $table.'.'.$key.'="'.addslashes($condition).'"';
			}
		} 
		
		if (!$where)
			return '';

		return 'WHERE '.join(' AND ', $where);
	}

	public function order_by($items = null) { 
		if ($items) {
			$cond= $this->conditions;

			if (count($args = func_get_args()) > 1)
				$items = $args;
				
			if (is_array($items))
				$cond['order_by'] = array_merge($cond['order_by'], $items);
			else
				$cond['order_by'][] = $items;
			
			return new ModelIterator($this->models, $cond);
		}
		else {
			if (!$this->conditions['order_by'])
				return '';

			return 'ORDER BY '.join(',', $this->conditions['order_by']);	
		}
	}

	public function group_by($items = null) { 
		if (count(func_get_args()) > 1)
			$items = func_get_args();

		if ($items) {
			$conditions = $this->conditions;

			if (is_array($items))
				$conditions['group_by'] = array_merge($conditions['group_by'], $items);
			else
				$conditions['group_by'][] = $items;

			return new ModelIterator($this->models, $conditions);
		}
		else {
			if ($this->conditions['group_by']) 
				return 'GROUP BY '.join(', ', $this->conditions['group_by']);	
			else {
				if (count($this->models) > 1) {
					$last_table = $this->as_table();

					return 'GROUP BY '.$last_table.'.id';
				}
			}
		}
	}

	public function as_table() { 
		return XModel::get_table($this->as_class());
	}

	public function as_class() { 
		if ($this->conditions['become'])
			return $this->conditions['become'];
		else
			return end($this->models);
	}

	public function become($class) { 
		$conditions = $this->conditions;
		$conditions['become'] = $class;

		return new ModelIterator($this->models, $conditions);
	}
	
	public function paginate($page_num = 1, $rows_per_page = XEN_ROWS_PER_PAGE) { 
		return $this->limit($rows_per_page, ($page_num - 1) * $rows_per_page);
	}

	public function limit($limit = null, $offset = null) {
		if ($limit) {
			$conditions = $this->conditions;
			$conditions['limit'][0] = $limit;
			$conditions['limit'][1] = $offset ? $offset : 0;

			return new ModelIterator($this->models, $conditions);		
		}

		if (!$this->conditions['limit']) 
			return "";
		
		$limit ='LIMIT '.$this->conditions['limit'][0];
		if ($this->conditions['limit'][1] > 0)
			$limit .=' OFFSET '.$this->conditions['limit'][1]; 

		return $limit;
	}
	
	/**
	* Returns first found model
	*/
	public function first() { 
		foreach ($this->paginate(1,1) as $model)  
			return $model;
		
		return null;
	}
	
	public function __get($name) {
		$models = $this->models;
		$models[] = Inflector::camelize(Inflector::singularize($name));

		return new ModelIterator($models, $this->conditions);
	}
	
	//Mass set property to the list of models
	public function __set($name, $value) { 
		$table = $this->as_table();

		return $this->_sql->query(
			'UPDATE '.$this->from($this->models, true).' '.
				'SET `'.$table.'`.`'.$name.'`='.$this->_sql->db_type_cast($value).' '.
			$this->where()
		);
	}

	public function current() {
		$class = $this->as_class();

		$this->instance = new $class;

		//if requested fields passed by directly
		if ($this->conditions['columns']) 
			$this->instance->clear();
		
		$this->instance->_load_assoc(parent::current());

		return $this->instance;
	}

	public function key() {
    return $this->instance->id();
  }

	public function count() {
		if (null !== $this->count_total)
			return $this->count_total;

		$sql = clone $this->_sql;

		if ($this->conditions['group_by']) {
			$sql->query(
				'SELECT SQL_CALC_FOUND_ROWS COUNT(*) '.
				$this->from($this->models).' '.$this->where().' '.$this->group_by());

			$sql->query('SELECT FOUND_ROWS()');
		} else {
			$sql->query(
				'SELECT COUNT(*) '.$this->from($this->models).' '.$this->where());
		}

		list($this->count_total) = $sql->r();

		unset($sql);

		return $this->count_total;
	}
}
?>
