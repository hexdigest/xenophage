<?php
AutoLoad::path(dirname(__FILE__).'/relation.php');
AutoLoad::path(dirname(__FILE__).'/../../../utils/inflector.php');

class BelongsTo extends Relation {
	public function create($mainClass, $relatedClasses = array()) { 
		parent::create($mainClass, $relatedClasses);

		/*
			if main class BelongsTo relatedClasses then relatedClass
			has many mainClasses
		*/
		foreach ($relatedClasses as $relatedClass)  
			self::addRelations('HasMany', $relatedClass, array($mainClass));
	}

	public static function resolve($object, $attribute) {}

	public static function getAttribute($mainClass, $relatedClass) {
		return Inflector::foreign_key($relatedClass);
	}
}
?>
