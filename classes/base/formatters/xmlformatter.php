<?php
AutoLoad::path(dirname(__FILE__).'/../../../classes/utils/inflector.php');

class XMLFormatter {
	public function format(array $array) {
		header('Content-Type: text/xml; charset="UTF-8"');

		echo '<?xml version="1.0" encoding="UTF-8"?>';
		self::drawXML($array, 'root');
	}

	public static function drawXML($array, $parentName) { 
		/* don't even try to make this code look better the goal is 
		to output everyting to buffer as fast as possible without 
		saving anyting to temporary variables */
		echo '<', $parentName;

		if ($array[':attributes']) {
			foreach ($array[':attributes'] as $name => $value) 
				echo ' ', $name, '="', htmlspecialchars($value), '"';
		}
		echo '>';

		if (isset($array[':text']))
			echo htmlspecialchars($array[':text']);
		
		$numericName = null;
		foreach ($array as $name => $value) {
			if (':attributes' === $name || ':text' === $name) 
				continue;

			if (is_null($numericName) && is_numeric($name)) 
				$numericName = Inflector::singularize($parentName);
			
			$realName = is_numeric($name) ? $numericName : $name;

			if (is_array($value)) 
				self::drawXML($value, $realName);
			else 
				echo '<',$realName,'>',htmlspecialchars($value),'</',$realName,'>';
		}

		echo '</', $parentName, '>';
	}
}
?>
