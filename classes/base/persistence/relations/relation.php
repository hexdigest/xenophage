<?php
abstract class Relation {
	protected static $relations = array();

	/**
	* Resolves relation for the given $object and it's unknown $attribute
	* return:
	* 	FALSE - if relation couln't be resolved (no relation between $object
	*						and $attribute was registered
	*		NULL - if there no $attribute corresponding $object found
	*		MIXED - any other value if relation was successfully resolved
	*/
	abstract public static function resolve($object, $attribute); 

	/**
	* Returns array('relation_name', relation_default_value)
	*/
	public static function getAttribute($mainClass, $relatedClass) {}

	public function create($mainClass, $relatedClasses = array()) {
		$relationType = get_called_class();
		self::addRelations($relationType, $mainClass, $relatedClasses);
	}

	public static function addRelations($relationType, $mainClass, $relatedClasses) {
		if (! isset(self::$relations[$mainClass]))
			self::$relations[$mainClass] = array();

		if (! isset(self::$relations[$mainClass][$relationTyp]))
			self::$relations[$mainClass][$relationType] = array();

		self::$relations[$mainClass][$relationType] = array_unique(array_merge(
			self::$relations[$mainClass][$relationType], $relatedClasses));
	}

	/**
	* Return all additional attributes for $mainClass that created by relations
	*/
	public static function getAttributesFor($mainClass) { 
		$returnAttributes = array();

		foreach (self::$relations[$mainClass] as $relationType => $relatedClasses) { 
			foreach ($relatedClasses as $relatedClass) {
				$attribute = $relationType::getAttribute($mainClass, $relatedClass);
				if ($attribute) {
					list($name, $value) = $attribute;
					$returnAttributes[$name] = $value;
				}
			}
		}

		return $returnAttributes;
	}

	public static function getRelationsTree() { 
		return self::$relations;
	}
}
?>
