<?php

class AdminController extends Zend_Controller_Action
{
	public function init()
	{
		$contextSwitch = $this->_helper->getHelper('contextSwitch');
		$contextSwitch->addActionContext('enrollment', 'json')
					  ->addActionContext('programusers', 'json')
					  //->addActionContext('usersdirectory', 'json')
					  ->initContext();
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
		if ($sess->group != "admin") $this->_redirect('/admin/login');   

		$users = new Application_Model_Users();
		$this->view->users = $users->fetchAll();
	} 
	
	public function programAction()
	{ 
		if (!Zend_Auth::getInstance()->hasIdentity()) {
			$this->_redirect('/admin/login');
		}
		$sess = new Zend_Session_Namespace('renewal.auth');
		if ($sess->group != "admin") $this->_redirect('/admin/login');
		
		$program = new Application_Model_Programs;
		$this->view->programs = $program->fetchAll();
		 
		$state = $this->_getParam('state');
		$id = $this->_getParam('id');

		if ($state == "update" or $state == "view"){
			$row = $program->find($id); 				
			$this->view->form = new Application_Form_Program($state, $row);
			$programDetail = $program->find($id); 
			$measures = $programDetail[0]['p_measure'];
			$measures = explode(';',$measures);
			for ($i=0; $i<count($measures)-1;$i++) {
				$item = explode(':', $measures[$i]);
				$measure[$i][] = trim($item[0]);
				$measure[$i][] = trim($item[1]);
				$measure[$i][] = trim($item[2]);
			} 
			
		    $this->view->programDetail = $programDetail;
			$this->view->measures = $measure; 
			
		} else if($state=="delete"){
			$where = $program->getAdapter()->quoteInto('id = ?', $id);
			$program->delete($where);
			$this->_redirect('/admin/program');
		}				
		else {
			$this->view->form = new Application_Form_Program();
		}            
		
		if ($this->getRequest()->isPost() &&
			$this->view->form->isValid($this->getRequest()->getParams()))
		{
			$this->view->form->persistData();
			$this->_redirect('/admin/program/view/'.$id);
		}

	}
	
	public function enrollmentAction()
	{
	    $pid = $this->_getParam('pid');
		$uid = $this->_getParam('uid');
		$program_model = new Application_Model_Programs;
		$this->view->programs = $program_model->fetchAll();

		$adduserform = new Zend_Dojo_Form;
		$adduserform->setAction('/admin/enrollment')
			->setMethod('post');
		$userbox = new Zend_Dojo_Form_Element_ComboBox('user');
		$userbox->setLabel('add user to program:')
			->setOptions(array(
				'autocomplete' => true, 
				'storeId'	=> 'countryStore', 
				'storeType' => 'dojo.data.ItemFileReadStore', 
				'storeParams' => array('url' => "/admin/usersdirectory"), 
				'dijitParams' => array('searchAttr' => 'name')))     
			->setRequired(true) 
			->addValidator('NotEmpty', true) 
			->addFilter('HTMLEntities') 
			->addFilter('StringToLower') 
			->addFilter('StringTrim');

		$submit = new Zend_Dojo_Form_Element_SubmitButton('submit');
		$submit->setLabel('Add client to program');
		$submit->setAttrib('id', 'submit');	


		$adduserform->addElements(array($userbox, $submit));
		$this->view->adduserform = $adduserform;          
	}
	
	public function programusersAction(){
		$pid = $this->_getParam('pid');
		$program_model = new Application_Model_Programs;
		$program       = $program_model->find($pid)->current();
	    $users         = $program->findManyToManyRowset('Application_Model_Users', 'Application_Model_UsersPrograms');
		$this->view->users = $users->toArray(); 
	}
	
	public function usersdirectoryAction(){		
		/*
		// get country list from Zend_Locale 
		$user_model = new Application_Model_Users;
		$users		= $user_model->fetchAll(); 
		$items = array(); 
		foreach ($users as $user) {
			$items[] = array('name' => $user->fname);
		}
		// generate and return JSON string compliant with dojo.data structure 
		$data = new Zend_Dojo_Data('name', $items); 
		$this->view->data = $data->toJson();  
		*/ 
		
		//disable layout and view rendering
		$this->_helper->layout->disableLayout(); 
		$this->getHelper('viewRenderer')->setNoRender(true); 
		
		// get country list from Zend_Locale 
		$territories = Zend_Locale::getTranslationList('territory', null, 2); 
		$items = array(); foreach ($territories as $t) {
		$items[] = array('name' => $t);
		}
		// generate and return JSON string compliant with dojo.data structure 
		$data = new Zend_Dojo_Data('name', $items); 
		header('Content-Type: application/json'); 
		echo $data->toJson();   
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

