<?php
AutoLoad::path(dirname(__FILE__) . '/aclsubject.php');
/**
 * Base class of simple access control lists implementation.
 * Currently distinguishes only 2 types of users: authorized and not.
 * Inherit from this class if you want more complex mechanism
 */
class ACLBase {
	/**
	 * @var array 	Access Control List in form of 
	 *				$controller => array($action1, $action2, ..., $actionN)
	 *				To grant access to full controller (all public methods) 
	 *				action list should be any type except of array
	 *              To grant access to full controller except of some actions
	 *              set first element in action list to 'false' after that 
	 *              enumerate these actions
	 */
	protected $accessList = array();
	
	/**
	 * Check controller::action allowed for specified user
	 * @param ACLSubject $subject
	 * @param string $controller
	 * @param string $action
	 * @return boolean true if allowed, otherwise false
	 */
	public function allowed(ACLSubject $subject, $controller, $action) {
		if ($subject && $subject->authorized()) 
			return true;

		if (! array_key_exists($controller, $this->accessList)) 
			return false;

		$controllerAccessList = $this->accessList[$controller];
		if (!is_array($controllerAccessList)) 
			return true;

		$found = array_search($action, $controllerAccessList) !== false;
		return ($controllerAccessList[0] === false) ? !$found : $found ;
	}
};
?>
