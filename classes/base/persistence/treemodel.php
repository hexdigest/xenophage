<?php
require_auto(XENOPHAGE . '/classes/xmodel.php');

class TreeModel extends XModel {
	protected $_children = null;

	public function append_child($child) { 
		$this->_sql->begin();

		if ($this->id() === null) 
			$this->save();

		$child->parent_id = $this->id();
		$child->save();

		$this->_sql->commit();
	}

	public function create_table() { 
		$this->parent_id = 0;
		$this->create_index('parent_id', SQL_INDEX_FOREIGN_KEY);

		if (get_class($this) != __CLASS__)
			return parent::create_table();
	}

	public function set_to_root() { 
		$query = 'SELECT * FROM '.$this->_table.' WHERE parent_id="0"';

		if (!$this->_sql->query($query))
			throw new ModelException('No root');

		return $this->_load_assoc($this->_sql->a());
	}

	public function __get($name) { 
		switch ($name) {
			case 'children':
			case 'siblings':
			case 'ancestors':
			case 'descendants':
			case 'descendants_or_self':
				if (null === $this->id())
					return array();

				$function = '_'.$name;
				return $this->$function();

			case 'parent': 
				return $this->_parent();

			default: 
				return parent::__get($name);
		}
	}

	protected function _children() { 
		if ($this->_children)
			return $this->_children;

		$this->_children = new ModelIterator($this, array(
			"where" => array('parent_id' => $this->id())));

		return $this->_children;
	}

	protected function _descendants() { 
		$parents = array($this->id());
		$descendants = array();

		while ($parents) {
			$this->_sql->query(
				'SELECT id FROM '.$this->_table.' '.
				'WHERE parent_id IN("'.join('","', $parents).'")'
			);

			$parents = $this->_sql->column();
			$descendants = array_merge($descendants, $parents);
		}

		$result = array();

		if ($descendants)
			$result = new ModelIterator($this, array("where"=>array(
				$this->_table.'.id IN('.join(',', $descendants).')')));

		return $result;
	}

	protected function _descendants_or_self() {
		$parents = array($this->id());
		$descendants = array();

		while ($parents) {
			$this->_sql->query(
				'SELECT id FROM '.$this->_table.' '.
				'WHERE parent_id IN("'.join('","', $parents).'")'
			);

			$parents = $this->_sql->column();
			$descendants = array_merge($descendants, $parents);
		}

		array_push($descendants, $this->id());

		return new ModelIterator($this, array("where"=>array(
			$this->_table.'.id IN('.join(',', $descendants).')')));
	}

	protected function _siblings() { 
		return new ModelIterator($this, array(
			'where' => array(
				'parent_id'=>$this->parent_id,
				$this->_table.'.id <> '.$this->id()
			)
		));
	}

	protected function _parent() { 
		if ($this->parent_id === null)
			return null;
		else 
			return new $this->_class($this->parent_id);
	}

	protected function _ancestors() { 
		$ancestors = array();
			
		$parent_id = $this->parent_id;

		while ($parent_id) {
			$ancestor = new $this->_class($parent_id);
			$ancestors[$parent_id] = $ancestor;
			$parent_id = $ancestor->parent_id;
		}
	
		return $ancestors;
	}

	public function delete($deep = false) { 
		$this->_sql->begin();
		foreach ($this->children as $child)
			$child->delete($deep);

		parent::delete($deep);
		$this->_sql->commit();
	}
}
?>
