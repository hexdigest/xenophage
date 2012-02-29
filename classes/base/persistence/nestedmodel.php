<?php
require_auto(XENOPHAGE . '/classes/xmodel.php');

class NestedModel extends XModel {
	protected $_thread_id = null;
	protected $_children = null;

	public function append_child(&$child) {
		$this->_sql->begin();

		if ($this->id() === null) 
			$this->save();

		if ($thread_id = $this->_thread_id)
			$child->$thread_id = $this->$thread_id;
			
		$child->ns_level = $this->ns_level + 1;

		if ($this->nested_set_optimal_direction()) {
			$child->ns_left = $this->ns_right;
			$child->ns_right = $this->ns_right + 1;
			$query = 
				'UPDATE '.$this->_table.' SET '.
					'ns_right=ns_right+2,'.
					'ns_left=IF(ns_left>'.$this->ns_right.',ns_left+2,ns_left) '.
				'WHERE ns_right>='.$this->ns_right;

			$this->ns_right += 2;
		} else {
			$child->ns_right = $this->ns_left;
			$child->ns_left = $this->ns_left - 1;
			$query = 
				'UPDATE '.$this->_table.' SET '.
					'ns_left=ns_left-2,'.
					'ns_right=IF(ns_right<'.$this->ns_left.',ns_right-2,ns_right) '.
				'WHERE ns_left<='.$this->ns_left;

			$this->ns_left -= 2;
		}
				
		if ($thread_id)
			$query.= ' AND '.$thread_id.'="'.$this->$thread_id.'"';

		$this->_sql->query($query);

		$child->save();
		
		$this->_sql->commit();
	}

	public function delete($deep = false) { 
		if ($this->id() === null)
			return false;

		$this->_sql->begin();

		$query = 'UPDATE '.$this->_table.' SET ';
		$d = ($this->ns_right-$this->ns_left+1);
			
		if ($d > 1) { //remove all the nested models if exist
			$this->_sql->query(
				'DELETE FROM '.$this->_table.' '.
				'WHERE '.
					'ns_left>"'.$this->ns_left.'" AND ns_right<"'.$this->ns_right.'"'
			);
		}

		if ($this->nested_set_optimal_direction())
			$query .= 
				'ns_right=ns_right-'.$d.','.
				'ns_left=IF(ns_left>'.$this->ns_right.',ns_left-'.$d.',ns_left) '.
				'WHERE ns_right>'.$this->ns_right;
		else
			$query .= 
				'ns_left=ns_left+'.$d.','.
				'ns_right=IF(ns_right<'.$this->ns_left.',ns_right+'.$d.',ns_right) '.
				'WHERE ns_left<'.$this->ns_left;

		$this->_sql->query($query);

		parent::delete($deep);
		$this->_sql->commit();		
	}

	/**
	* Return TRUE if the right part of nested set tree is smaller
	* than the left relatively current model
	*/
	public function nested_set_optimal_direction() {
		$thread_id = $where_thread = '';

		if ($thread_id = $this->_thread_id)
			$where_thread = ' AND '.$thread_id.' = "'.$this->$thread_id.'" ';

		if ($this->_sql->query(
			'SELECT ns_left,ns_right FROM '.$this->_table.' '.
			'WHERE ns_level = 0 '.$where_thread.
			'ORDER BY ns_left ASC '.
			'LIMIT 1'
		)) {
			list($left, $right) = $this->_sql->r();
			return (($left - $this->ns_left) > ($right - $this->ns_right));
		}

		return null;
	}

	public function save() { 
		if (null === $this->ns_level) {
			$this->ns_level = 0;
			$this->ns_left = 0;
			$this->ns_right = 1;
		}

		return parent::save();
	}

	public function create_table() { 
		$this->ns_level = 0;
		$this->ns_left = 0;
		$this->ns_right = 1;

		$this->create_index('ns_left', SQL_INDEX);
		$this->create_index('ns_right', SQL_INDEX);

		$thread_id = $this->thread_id();

		$belongs_to = str_ireplace('_id', '', $thread_id);

		if ($thread_id && !in_array($belongs_to, $this->belongs_to()))
			$this->create_index($thread_id, SQL_INDEX);

		if (get_class($this) != __CLASS__)
			return parent::create_table();
	}

	public function thread_id($thread_id = null) { 
		if (null === $thread_id)
			return $this->_thread_id;

		$this->_thread_id = $thread_id;
	}

	public function set_to_root($thread_id) {
		$query = 'SELECT * FROM '.$this->_table.' WHERE ns_level="0"';

		if ($this->_thread_id) 
			$query .= ' AND '.$this->_thread_id.'="'.addslashes($thread_id).'"';
	
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
			
		$where = array(
			'ns_level' => $this->ns_level + 1,
			$this->_table.'.ns_left>'.$this->ns_left,
			$this->_table.'.ns_right<'.$this->ns_right
		);

		if ($thread_id = $this->_nested_set_thread_id)
			$where[$thread_id] = $this->$thread_id;

		$this->_children = new ModelIterator($this, array("where" => $where));

		return $this->_children;
	}

	protected function _descendants() {
		$where = array(
			$this->_table.'.ns_left>'.$this->ns_left,
			$this->_table.'.ns_right<'.$this->ns_right
		);
				
		if ($thread_id = $this->_nested_set_thread_id)
			$where[$thread_id] = $this->$thread_id;

		return new ModelIterator($this, array("where" => $where));
	}

	protected function _siblings() { 
		$where = array(
			'ns_level' => $this->ns_level,
			$this->_table.'.ns_left>'.$this->parent->ns_left,
			$this->_table.'.ns_right<'.$this->parent->ns_right,
			$this->_table.'.id <> '.$this->id()
		);

		if ($thread_id = $this->_thread_id)
			$where[$thread_id] = $this->$thread_id;

		return new ModelIterator($this, array('where' => $where));
	}

	protected function _parent() { 
		$thread_id = $this->_thread_id;

		$this->_sql->query(
			'SELECT * FROM '.$this->_table.' '.
			'WHERE ns_left<'.$this->ns_left.' AND '.
				'ns_right>'.$this->ns_right.' AND '.
				'ns_level = '.$this->ns_level.'-1'.
				($thread_id?' AND '.$thread_id.' = '.$this->$thread_id:'')
		);

		return new $this->_class($this->_sql->a());
	}

	protected function _ancestors() { 
		$where = array(
			$this->_table.'.ns_left<'.$this->ns_left,
			$this->_table.'.ns_right>'.$this->ns_right
		);

		if ($thread_id = $this->_thread_id)
			$where[$thread_id] = $this->$thread_id;

		$iterator = new ModelIterator($this, array('where' => $where));
		$iterator = $iterator->order_by('ns_level ASC');

		return $iterator;
	}
}
?>
