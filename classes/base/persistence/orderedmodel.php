<?php
class OrderedModel extends XModel {
	public function save() {
		$this->_sql->begin();
		{
			if (! $this->id()) {
				if ($last = $this->all()->order_by('_order_num DESC')->first())
					$this->_order_num = $last->_order_num + 1;
				else
					$this->_order_num = 1;
			}

			$id = parent::save();
		}
		$this->_sql->commit();

		return $id;
	}

	public function up() { 
		if (! $this->id())
			return;

		$upper = $this->all()
			->find_by('_order_num < '.$this->_order_num)
			->order_by('_order_num DESC')
			->first();

		if ($upper) {
			$this->_sql->begin();
			{
				$current_pos = $this->_order_num;
				$this->_order_num = $upper->_order_num;
				$upper->_order_num = $current_pos;
				$this->save();
				$upper->save();
			}
			$this->_sql->commit();
		}
	}

	public function down() { 
		if (! $this->id())
			return;

		$lower = $this->all()
			->find_by('_order_num > '.$this->_order_num)
			->order_by('_order_num ASC')
			->first();

		if ($lower) {
			$this->_sql->begin();
			{
				$current_pos = $this->_order_num;
				$this->_order_num = $lower->_order_num;
				$lower->_order_num = $current_pos;
				$this->save();
				$lower->save();
			}
			$this->_sql->commit();
		}
	}

	public function create_table() { 
		$this->_order_num = 0;

		if (get_class($this) != __CLASS__)
			return parent::create_table();
	}
}
?>
