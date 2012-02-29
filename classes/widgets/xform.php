<?php
AutoLoad::path(dirname(__FILE__).'/form.php');
AutoLoad::path(dirname(__FILE__).'/../utils/xformsparser.php');

class XForm extends Form {
	protected $_xp = null;

	public function __construct($xFormsXML) { 
		parent::__construct();

		$this->_xp = new XFormsParser($xFormsXML);
		$this->_xp->buildForm($this);

		$this->constraints = $this->_xp->getConstraints();
	}

	/**
	* Validates form data against constraints
	*/
	public function validate() {
		$result = parent::validate();

		if ($result && $this->constraints) {
			$params = $this->getParams();
			$dom = XFormsParser::getXMLParams($params);

			foreach ($this->get_nested_widgets('iInput') as $name => $input) { 
				if (isset($this->constraints[$name])) { 
					try { 
						if (! XFormsParser::checkConstraint($this->constraints[$name]['constraint'], $name, $dom)) {
							$input->error($this->constraints[$name]['alert']);
							$result = false;
						}
					} catch (PHPErrorException $e) {
						$input->error('Invalild constraint: '.$this->constraints[$name]['constraint']);
						$result = false;
					}
				}
			}

		}

		return $result;
	}

	/**
	* Return nodesets including calculated fields
	*/
	public function getNodesets() { 
		return $this->_xp->getNodesets($this->getParams());
	}
}
?>
