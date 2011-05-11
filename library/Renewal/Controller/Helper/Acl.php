<?php
class Renewal_Controller_Helper_Acl
{
	public $acl;
	public function __construct()
	{
		$this->acl = new Zend_Acl();
	}
	public function setRoles()
	{
		$this->acl->addRole(new Zend_Acl_Role(‘user’));
		$this->acl->addRole(new Zend_Acl_Role(‘admin’));

	}

	public function setResources()
	{

		$this->acl->add(new Zend_Acl_Resource(‘view’));
		//$this->acl->add(new Zend_Acl_Resource(‘edit’));

	}

	public function setPrivilages()
	{
		$this->acl->allow(‘user’,null,’view’);
		$this->acl->allow(‘admin’);
	}
	public function setAcl()
	{
		Zend_Registry::set(‘acl’,$this->acl);
	}
}
}