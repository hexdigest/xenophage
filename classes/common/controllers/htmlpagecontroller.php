<?php
AutoLoad::path(dirname(__FILE__) . '/controller.php');
AutoLoad::path(dirname(__FILE__) . '/../model/htmlpage.php');
AutoLoad::path(X2_CLASS_PATH . '/utils/xdom.php');
/**
 * Information pages view contoller
 */
class HTMLPageController extends Controller {

	/**
	 * Shows pages subtree with root in specified page
	 * @param int $parentId
	 */
	public function getTree($path) {
		try {
			$page = $this->getPageByPath($path);
			$page->children = $this->getDescendants((int) $page->id());
		} catch(ModelException $e) {
			throw new HTTPException("Page not found", 404);
		}
		$this->page = $page;
	}

	/**
	* Return page tree of the given depth 
	* @param string $path - path to page
	* @param int $depth - maximum levels
	*/
	public function getTreeMenu($path, $depth = 1) { 
		$page = $this->getPageByPath($path);
		$this->items = $this->getTreeLinks($page, 
			$this->linkToAction('showPage', array('path' => '')), $depth);
	}

	protected function getTreeLinks($page, $basePath, $depth) {
		$path = $basePath;
		if (strcmp($page->alias, Engine::instance()->_language->id()))
			 $path .= $page->alias . '/';

		$result = array(
			'link' => $path,
			'title' => strval($page->title)
		);
		
		if ($page->menuOnly->get())
			$result['menuOnly'] = 1;

		if ($depth) {
			$children = array();

			foreach ($this->getChildren($page->id()) as $child) 
				$children[] = $this->getTreeLinks($child, $path, $depth - 1);
			
			if ($children)
				$result['items'] = $children;
		}

		return $result;
	}


	/**
	 * Shows single page by path
	 * @param string $path
	 */
	public function showPage($path) {
		$this->page = $this->getPageByPath($path);
	}


	/**
	 * Return page by path
	 * @param string $path
	 */
	public function getPageByPath($path) {
		$prefix = trim(parse_url(STATIC_PAGE_UPDATE_URL, PHP_URL_PATH), '/');		
		$prefix = array_slice(explode('/', $prefix), 3);
		
		$prefix[] = Engine::instance()->_language->id();
		
		$path = trim($path, '/');
		$aliases = array_merge($prefix, explode('/', $path));

		$parent = 0;
		$page = null;
		$iterator = new ModelIterator('HTMLPage');
		foreach($aliases as $alias) {
			if (strlen($alias)) {
				$page = null;
				$iterator->find(array('alias' => $alias, 'parentId' => $parent), false);
				foreach($iterator as $page) {break;}

				if (!$page) 
					throw new HTTPException("Page not found: {$path}", 404);

				$parent = (int) $page->id();
			}
		}
		
		return $page;
	}

	/**
	 * Updates static pages
	 */
	public function updatePages() {
		$content = Utils::getFromURL(STATIC_PAGE_UPDATE_URL);

		$dom = new XDOM();
		try {
			$dom->loadXML($content);
		} catch(Exception $e) {
			throw new ControllerException('Wrong pages xml format');
		}

		$iterator = new ModelIterator('HTMLPage');
		$iterator->remove();

		$rootPage = $dom->xpathFirstNode('/xenophage/controller/treeizer');
		$this->parsePage($rootPage, $dom);
	}

	/**
	 * Parses DOM subtree and save pages from it
	 * @param DOMElement $pageElement root page in subtree
	 * @param XDOM $dom
	 */
	private function parsePage(DOMElement $pageElement, XDOM $dom) {
		if((int) $dom->xpathFirstValue('enable', $pageElement) != 1) return;

		$page = new HTMLPage();
		$page->id = (int) $pageElement->getAttribute('id');
		$page->parentId = $dom->xpathFirstValue('parent_id', $pageElement);
		$page->title = $dom->xpathFirstValue('zag', $pageElement);
		$page->alias = $dom->xpathFirstValue('alias', $pageElement);
		$page->position = $dom->xpathFirstValue('position', $pageElement);
		$page->contents = $dom->xpathFirstValue('text', $pageElement);
		$page->menuOnly = $dom->xpathFirstValue('section', $pageElement);
		$page->save();
		
		$children = $dom->xpath('children/child', $pageElement);
		foreach($children as $child) {
			$this->parsePage($child, $dom);
		}
	}

	/**
	* Returns all descendants of parent page
	* @param int $parentId - parent page identifier
	*/
	private function getDescendants($parentId) {
		$result = array();
		foreach ($this->getChildren($parentId) as $child) {
			$child->children = $this->getChildren((int) $child->id());
			$result[] = $child;
		}

		return $result;
	}

	/**
	* Returns children of given page sorted in position order
	* @param int $parentId - parent page identifier
	*/
	private function getChildren($parentId) {
		$iterator = new ModelIterator('HTMLPage');
		$children = array();

		foreach ($iterator->find(array('parentId' => $parentId)) as $page) 
			$children[] = $page;

		usort($children, array(__CLASS__, 'pSort'));

		return $children;
	}

	/**
	* Helper function for sorting pages in position order used by usort
	*/
	public static function pSort($a, $b) {
		if ($a->position->get() == $b->position->get())
			return 0;

		return ($a->position->get() > $b->position->get()) ? +1 : -1;
	}
}
?>
