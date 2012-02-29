<?php
function __($code) {
	return Engine::instance()->_language[ strtolower($code) ];
}
?>
