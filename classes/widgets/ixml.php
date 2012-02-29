<?php
AutoLoad::path(dirname(__FILE__) . '/istring.php');
/**
 * XML string
 */
class iXml extends iString {

	public function _draw() {
		$canvas = parent::_draw();
		$canvas[':xml'] = $canvas[':text'];
		unset($canvas[':text']);
		return $canvas;
	}

}
?>
