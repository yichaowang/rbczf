<?php

class AdminController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }
    
	public function loginAction()
	{
		if(Zend_Auth::getInstance()->hasIdentity())  
		{  
			$sess = new Zend_Session_Namespace('renewal.auth');
			if ($sess->group == 'admin'){
				$this->_redirect('/admin');  
			}
		}

		$form = new Application_Form_UserLogin();
		$form->setAction('/admin/login'); 
		$this->view->formlogin = $form;

		if ($this->getRequest()->isPost()) {
			if ($form->isValid($this->getRequest()->getPost())) {
				$values = $form->getValues();
				$dbAdapter = Zend_Db_Table::getDefaultAdapter(); 
				$authAdapter = new Zend_Auth_Adapter_DbTable($dbAdapter);
				$authAdapter
					->setTableName('users')
					->setIdentityColumn('email')
					->setCredentialColumn('password');
				$authAdapter
					->setIdentity($values['email'])
					->setCredential($values['password']);
				$auth = Zend_Auth::getInstance();
				$result = $auth->authenticate($authAdapter);
				if ($result->isValid()){
					$sess = new Zend_Session_Namespace('renewal.auth');
					$sess->group = "admin"; 
					$userInfo = $authAdapter->getResultRowObject();  
					$authStorage = $auth->getStorage();  
					$authStorage->write($userInfo);
					$this->_redirect('/admin');
				} else {
					$this->view->message = "Email or password is incorrect. Please try again.";
				}
			}
		}  
	}

    public function indexAction()
    {                            
		if (!Zend_Auth::getInstance()->hasIdentity()) {
	      	$this->_redirect('/admin/login');
	    }
		$sess = new Zend_Session_Namespace('renewal.auth');
		if ($sess->group != "admin")$this->_redirect('/admin/login');   
		 
		$users = new Application_Model_DbTable_User();
        $this->view->users = $users->fetchAll();
    } 
    
	public function logoutAction()
	{
		Zend_Auth::getInstance()->clearIdentity();
		$this->_redirect('/admin/login');
	}

	public function userAction()
	{
	}
	
	public function progressAction()
	{
		
	}



}

