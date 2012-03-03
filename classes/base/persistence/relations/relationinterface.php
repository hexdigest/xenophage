<?php
abstract class Relation {
	protected static $relations = array();

	public function __construct($mainClass, $relatedClasses = array()) {
		//Adding new relations to the global register of relations
		if (! isset(self::$relations[$mainClass]))
			self::$relations[$mainClass] = array();

		$relationClass = get_class($this);
		if (! isset(self::$relations[$mainClass][$relationClass]))
			self::$relations[$mainClass][$relationClass] = array();

		self::$relations[$mainClass][$relationClass] = array_unique(array_merge(
			self::$relations[$mainClass][$relationClass], $relatedClasses));
	}
}
?>
