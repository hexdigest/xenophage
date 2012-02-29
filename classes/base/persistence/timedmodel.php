<?php
class TimedModel extends XModel {
	public function save() { 
		$this->update_time = new iDateTime;
		$this->update_time->set(time());

		if (! $this->id())
			$this->create_time = $this->update_time;
		
		return parent::save();
	}

	public function create_table() { 
		if (get_class($this) == __CLASS__)
			return;

		$this->create_time = new iDateTime;
		$this->create_time->set(0);

		$this->update_time = new iDateTime;
		$this->update_time->set(0);

		return parent::create_table();
	}
}
?>
