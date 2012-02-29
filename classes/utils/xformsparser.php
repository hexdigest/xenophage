<?php
AutoLoad::path(dirname(__FILE__).'/xdom.php');
AutoLoad::path(dirname(__FILE__).'/utils.php');
AutoLoad::path(dirname(__FILE__).'/../widgets/');

class XFormsParser {
	protected $dom = null;
	protected $lang = null;
	protected static $groups_count = 0;

	protected static $regexps = array(
		'anyURI' => '^([A-Za-z]+://)?([A-Za-z0-9]+(:[A-Za-z0-9]+)?@)?([a-zA-Z0-9][-A-Za-z0-9.]*\\.[A-Za-z]{2,7})(:[0-9]+)?(/[-_.A-Za-z0-9]+)?(\\?[A-Za-z0-9%&=]+)?(#\\w+)?$',
		'email' => '^[\\w-_\.]*[\\w-_\.]\@[\\w]\.+[\\w]+[\\w]$',
		'url' => '^((http|ftp|https)://)?([A-Za-z0-9]+(:[A-Za-z0-9]+)?@)?([a-zA-Z0-9][-A-Za-z0-9.]*\\.[A-Za-z]{2,7})(:[0-9]+)?(/[-_.A-Za-z0-9]+)?(\\?[A-Za-z0-9%&=]+)?$',
		'string' => '.*',
		'boolean' => '^true|false|0|1$',
		'integer' => '^[0-9]+$',
		'nonPositiveInteger' => '^[-][0-9]+$',
		'nonNegativeInteger' => '^[+]?[0-9]+$',
		'negativeInteger' => '^[-][0-9]+$',
		'positiveInteger' => '^[+]?[0-9]+$',
		'decimal' => '^[-+]?[0-9]+(\.|,)?[0-9]*$',
		'phone' => '^[0-9 \+-]+$',
		'date' => '^[0-9]{4}-[0-9]{2}-[0-9]{2}$'
	);

	public function __construct($xFormsXML) { 
		$this->dom = new XDOM;
		$this->dom->loadXML($xFormsXML);
		$this->dom->registerNamespace('xf', 'http://www.w3.org/2002/xforms');
	}

	public function buildForm(& $form) { 
		self::$groups_count = 0;
		self::drawWidgets($this->dom->xpath('//xf:*[not(ancestor::xf:*)]'), $form, $this->dom);
	}

	/**
	* Returns constraints for the current loaded xforms
	*/
	public function getConstraints() { 
		$constraints = array();
		$nodes = $this->dom->xpath('//xf:bind[@constraint]');
		foreach ($nodes as $node) { 
			$bind = $node->getAttribute('id');
			$constraint = $node->getAttribute('constraint');

			$mString = MultiLangString::createFromDOM('//xf:*[@bind="'.$bind.'"]/xf:alert/text()', $this->dom);

			$constraints[$bind] = array(
				'constraint' => $constraint, 
				'alert' => strval($mString)
			);
		}

		return $constraints;
	}

	/**
	* Returns xpath expressions for calculated params in a form of
	* associative array: fieldName => expression
	*/
	public function getExpressions() { 
		$calculatedFields = array();
		$nodes = $this->dom->xpath('//xf:bind[@calculate and @nodeset]');
		foreach ($nodes as $node) { 
			$nodeset = $node->getAttribute('nodeset');
			$expression = $node->getAttribute('calculate');

			$calculatedFields[$nodeset] = $expression;
		}

		return $calculatedFields;
	}

	/**
	* Calculate fields by xpath expressions
	*/
	public static function calculateExpressions($params, $expressions) { 
		$result = array();
		$dom = self::getXMLParams($params);
			
		foreach ($expressions as $name => $expression) 
			$result[$name] = $dom->evaluate($expression, $dom->firstChild);

		return $result;
	}

	/**
	* Return nodesets values incl. calculated ones
	* @param array $params - form params based on bind/@id
	*/
	public function getNodesets($params) { 
		$expressions = $this->getExpressions();
		if ($expressions) {
			$calculated = $this->calculateExpressions($params, $expressions);
			$params = array_merge($params, $calculated);
		}

		$nodesets = array();
		foreach ($params as $name => $value) { 
			$bindNode = $this->dom->xpathFirstNode('//xf:bind[@id="'.$name.'" and @nodeset]');
			if ($bindNode) {
				$nodeset = $bindNode->getAttribute('nodeset');
				$nodesets[$nodeset] = $value;
			}
		}

		return $nodesets;
	}

	/**
	* Helper-function that produce DOM document to run xpath expressions
	* over it
	* @param array $params - associative array of params
	*/
	public static function getXMLParams($params) {
		$dom = new XDOM;
		$dom->encoding = 'UTF-8';
		$root = $dom->createElement('root');

		foreach ($params as $name => $value) {
			$node = $dom->createElement($name, strval($value));
			$root->appendChild($node);	
		}

		$dom->appendChild($root);

		return $dom;
	}

	/**
	* Validates constraint for given bind node
	* @param string $constraint - constraint to check
	* @param string $bind - param name to check
	* @param XDOM $dom - dom object to run XPath queries against it
	*/
	public static function checkConstraint($constraint, $bind, $dom) { 
		return (bool)$dom->xpathFirstNode('/root/'.$bind.'['.$constraint.']');
	}

	public static function drawWidgets($widgets, &$container, $dom) {
		//Ищем поля ввода на форме и мапим их в соответствующие виджеты
		foreach ($widgets as $xf_widget) {
			if (!is_a($xf_widget, 'DOMElement'))
				continue;

			if ($bind_id = $xf_widget->getAttribute('bind')) {
				$bind = $dom->xpathFirstNode('//xf:bind[@id="'.$bind_id.'"]');
				if ($bind) {
					$nodeset =
						$dom->xpathFirstValue('//xf:bind[@id="'.$bind_id.'"]/@nodeset');
					$xsd_type = str_replace('xsd:', '', $bind->getAttribute('type'));
				} else {
					throw new Exception('"//xf:bind[@id="'.$bind_id.'"]" not found');
				}
			}

			switch ($xf_widget->nodeName) {
				case 'xf:group':
					$bind_id = 'group_'.self::$groups_count;
					self::$groups_count++;

					$label = strval(MultiLangString::createFromDOM('./xf:label/text()', $dom, $xf_widget));
					$role = $xf_widget->getAttribute('role');

					$group = new iGroup($role);
					$group->title($label);

					self::drawWidgets($xf_widget->childNodes, $group, $dom);
					$container->$bind_id = $group;

					continue 2;

				case 'xf:output':
					$container->$bind_id = new iString;
					$container->$bind_id->readonly(true);
					break;

				case 'xf:textarea':
					$container->$bind_id = new iText;
					break;

				case 'xf:select1':
					$labels = array();
					$itemsNodes = $dom->xpath('//xf:*[@bind="'.$bind_id.'"]//xf:item');
					foreach ($itemsNodes as $node) { 
						$labels[] = strval(MultiLangString::createFromDOM('./xf:label[not(@role)]/text()', $dom, $node));
					}

					$values =
						$dom->xpathValues('//xf:*[@bind="'.$bind_id.'"]//xf:item/xf:value');

					$items = array_combine($values, $labels);

					//Находим возможные варианты для выпадающего списка
					$container->$bind_id = new iSelect($items);
					break;

				case 'xf:input':
					if ($xsd_type == 'phone')
						$container->$bind_id = new iPhone;
					elseif ($xsd_type == 'decimal')
						$container->$bind_id = new iNumeric;
					elseif ($xsd_type == 'date')
						$container->$bind_id = new iDate;
					else
						$container->$bind_id = new iString;
					break;

				case 'xf:submit':
					$bind_id = 'submit';
					$container->$bind_id = new iSubmit;

					$label = strval(MultiLangString::createFromDOM('//xf:submit/xf:label[not(@role)]/text()', $dom));
					if ($label)
						$container->$bind_id->title($label);

					continue 2;

					break;

				default: //ignore all unknown widgets
					continue 2;
			}

			$label = strval(MultiLangString::createFromDOM('//xf:*[@bind="'.$bind_id.'"]/xf:label[not(@role)]/text()', $dom));
			if ($label)
				$container->$bind_id->title($label);	

			$hint = strval(MultiLangString::createFromDOM('//xf:*[@bind="'.$bind_id.'"]/xf:hint[not(@role)]/text()', $dom));

			if ($hint)
				$container->$bind_id->hint($hint);

			$required =
				$dom->xpathFirstValue('//xf:*[@id="'.$bind_id.'"]/@required') == "true()";

			if (!$required)
				$container->$bind_id->optional();

			$alert = strval(MultiLangString::createFromDOM('//xf:*[@bind="'.$bind_id.'"]/xf:alert/text()', $dom));

			if ($xsd_type && isset(self::$regexps[$xsd_type]))
				$container->$bind_id->alert(self::$regexps[$xsd_type], $alert);

			if ($nodeset) {
				$value = $dom->xpathFirstValue('//xf:instance//'.$nodeset);
				$container->$bind_id->set($value);
			}
		}

		//добавляем на форму скрытые поля (есть бинды но нет инпутов)
		foreach ($dom->xpath('//xf:bind[@nodeset]') as $bind) {
			$bind_id = $bind->getAttribute('id');
			if (!$bind_id || $bind->getAttribute('calculate'))
				continue;

			$nodeset = $bind->getAttribute('nodeset');
			if (! strlen($nodeset))
				continue;

			if (! $dom->xpath('//xf:*[@bind="'.$bind_id.'"]')) {
				$container->$bind_id = new iHidden;
				$container->$bind_id->set($dom->xpathFirstValue('//xf:instance//'.$nodeset));

				$required = $bind->getAttribute('required') == "true()";

				if (!$required)
					$container->$bind_id->optional();
			}
		}
	}
}
?>
