<?php

class ProgressController extends Zend_Controller_Action
{
	public function preDispatch()
	{
	}

    public function init()
    {
    	$this->view->placeholder('page')->set("progress");
    }

	public function loginAction()
	{
		if(Zend_Auth::getInstance()->hasIdentity())  
		{  
			$sess = new Zend_Session_Namespace('renewal.auth');
			if ($sess->group == 'user'){
				$this->_redirect('/progress/index');  
			}
		}
		
	    $form = new Application_Form_UserLogin();
	 	$form->setAction('/progress/login');
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
					$sess->group = "user"; 
					$userInfo = $authAdapter->getResultRowObject();  
					$authStorage = $auth->getStorage();  
					$authStorage->write($userInfo); 
					$this->_redirect('/progress/index');  
				} else {
					$this->view->message = "Email or password is incorrect. Please try again.";
				}
			}
		}
	}

	public function indexAction()
	{   
		if (!Zend_Auth::getInstance()->hasIdentity()) {
			$this->_redirect('/progress/login');
		}
		
		$userInfo = Zend_Auth::getInstance()->getStorage()->read();
		$this->view->user = $userInfo;
	}
	
	public function createAction(){
		$form = new Application_Form_UserCreate(); 
		$this->view->formcreate = $form;
		
		if ($this->getRequest()->isPost()) {
			if($form->isValid($_POST) ) {
				$values = $form->getValues();
				$values['regdate'] = time();
				$values['lastlogindate'] = time();
				$data = array(
					'fname'         => $values['fname'],
					'lname'         => $values['lname'],
					'email'         => $values['email'],
					'password'      => $values['password'],
					'regdate'       => $values['regdate'],
					'lastlogindate' => $values['lastlogindate'] 
				);       
				$user = new UsersTable;
				$user->insert($data);
			  	$this->_helper->getHelper('FlashMessenger')->addMessage('Congratulation on your first step to being fit!');
		        $this->_redirect('/progress/success');
			}
		}
	}
	
	public function logoutAction()
	{
		Zend_Auth::getInstance()->clearIdentity();
		$this->_redirect('/progress/login');
	}
	
	public function successAction()
	{
		if ($this->_helper->getHelper('FlashMessenger')->getMessages()) {
			$this->view->messages = $this->_helper->getHelper('FlashMessenger')->getMessages();    
		} else {
			$this->_redirect('/progress');    
		} 
	}


}

