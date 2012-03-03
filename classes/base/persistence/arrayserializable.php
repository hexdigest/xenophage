<?php
interface ArraySerializable {
    
	/**
	* Return model serialized into associative array
	*/
	public function getValuesAsArray();

	/**
	* As oppose to getValuesAsArray this method set
	* model properties from associative array
	*/
	public function setValuesFromArray($values);
}
?>
