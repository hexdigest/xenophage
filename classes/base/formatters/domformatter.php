<?php
AutoLoad::path(dirname(__FILE__).'/../../../classes/utils/xdom.php');

class DOMFormatter  {
	public function format(array $array) {
		header('Content-Type: text/xml; charset="UTF-8"');
		$this->getDOM($array);
	}

	protected function getDOM($array) { 
		$dom = new XDOM;
		$dom->encoding = 'UTF-8';

		$root = $dom->createElement('root');
//print_r($array);exit;
		self::arrayToNode($array, $root);

		$dom->appendChild($root);

		echo $dom;
	}


	public static function arrayToNode($array, & $node) {
		if (isset($array[':attributes'])) //non-empty array of attributes
			foreach ($array[':attributes'] as $aName => $aValue) 
				$node->setAttribute($aName, $aValue);

		if (isset($array[':text'])) {
			$childNode = $node->ownerDocument->createTextNode($array[':text']);
			$node->appendChild($childNode);
		}
		
		$numericName = null;
		foreach ($array as $name => $value) {
			if (':attributes' === $name || ':text' === $name)
				continue;

			if (is_null($numericName) && is_numeric($name)) 
				$numericName = Inflector::singularize($node->nodeName);

			$realName = is_numeric($name) ? $numericName : $name;

			if (is_array($value)) {
				$childNode = $node->ownerDocument->createElement($realName);
				self::arrayToNode($value, $childNode);
			} else 
				$childNode = $node->ownerDocument->createElement($realName, htmlspecialchars($value));

			$node->appendChild($childNode);
		}
	}
}
?>
