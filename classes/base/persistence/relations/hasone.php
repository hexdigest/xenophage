<?php
AutoLoad::path(dirname(__FILE__).'/relation.php');

class HasOne extends Relation {
	protected $finder = 'find_one_by_';

	public function create($mainClass, $relatedClasses = array()) { 
		parent::create($mainClass, $relatedClasses);

		/*
			if main class HasOne of relatedClasses then each of relatedClasses
			BelongsTo mainClass
		*/
		foreach ($relatedClasses as $relatedClass)  
			self::addRelations('BelongsTo', $relatedClass, array($mainClass));
	}

	public static function resolve($object, $attribute) { 
		$mainClass = get_class($object);
		$relationType = get_class($this);

		foreach (self::$relations[$mainClass][$relationType] as $relatedClass) { 
			if (0 === strcasecmp($relatedClass, $attribute)) {
				$finder = $this->finder . Inflector::foreign_key($mainClass);
				return $relatedClass::$finder($object->id());
			}
		}

		return false;
	}
}
?>
