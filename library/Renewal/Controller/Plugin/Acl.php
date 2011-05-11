<?php
class My_Controller_Plugin_Acl extends Zend_Controller_Plugin_Abstract
{
	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
		$acl = Zend_Registry::get(‘acl’);
		$usersNs = new Zend_Session_NameSpace(“members”);
		If($usersNs->userType==’’){
			$roleName=’guest’;
		} else {
			$roleName=$userType;
		}
		$privilageName=$request->getActionName();
		if(!$acl->isAllowed($roleName,null,$privilageName)){
			$request->setControllerName(‘Error’);
			$request->setActionName(‘index’);
		}
	}
}
