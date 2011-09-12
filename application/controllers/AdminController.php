<?php

class AdminController extends Zend_Controller_Action
{
	public function init()
	{
		$contextSwitch = $this->_helper->getHelper('contextSwitch');
		$contextSwitch->addActionContext('enrollment', 'json')
					  ->initContext();
	}

	public function loginAction()
	{
		if(Zend_Auth::getInstance()->hasIdentity())  
		{  
			$sess = new Zend_Session_Namespace('renewal.auth');
			if ($sess->admingroup == 'admin'){
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
					->setCredentialColumn('password')
					->setCredentialTreatment('? AND is_admin = 1');
				$authAdapter
					->setIdentity($values['email'])
					->setCredential($values['password']);
				$auth = Zend_Auth::getInstance();
				$result = $auth->authenticate($authAdapter);
				if ($result->isValid()){
					$sess = new Zend_Session_Namespace('renewal.auth');
					$sess->admingroup = "admin"; 
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
		if ($sess->admingroup != "admin") $this->_redirect('/admin/login');   

		$user_model = new Application_Model_Users;
		$user_details = $user_model->fetchAll();
		$user_output = $user_details->toArray();
		
		$db = Zend_Db_Table::getDefaultAdapter();
		 
		$i = 0;		
		foreach ($user_details as $user_detail){
			 
			$temp = $db->select()
				->from(array('u'=>'users'), array())
				->join(array('p'=>'users_programs') ,'u.id = p.user_id', array())
				->join(array('pn'=>'programs'),'p.program_id = pn.id', array('pname'=>'name'))
				->where('u.id = ?', $user_detail->id)
				->query()
				->fetchAll();
			$user_output[$i]['p_enrolled'] = $temp; 
            $i++;
		} 
		$this->view->users = $user_output;  
	} 
	
	public function programAction()
	{ 
		if (!Zend_Auth::getInstance()->hasIdentity()) {
			$this->_redirect('/admin/login');
		}
		$sess = new Zend_Session_Namespace('renewal.auth');
		if ($sess->admingroup != "admin") $this->_redirect('/admin/login');
		
		$program = new Application_Model_Programs;
		$this->view->programs = $program->fetchAll();
		 
		$state = $this->_getParam('state');
		$id = $this->_getParam('id'); 
		
		//$this->view->numberone == array("apple","pear");
	    //print_r($this->view->numberone);

		if ($state == "update" or $state == "view"){
			$row = $program->find($id); 				
			$this->view->program_form = new Application_Form_Program($state, $row);
			$programDetail = $program->find($id); 
			$measures = $programDetail[0]['p_measure'];
			$measures = explode(';',$measures);
			$measures = preg_grep('#\S#', array_map('trim', $measures));			
			for ($i=0; $i<count($measures);$i++) {
				$item = explode(':', $measures[$i]);
				$measure[$i][] = trim($item[0]);
				$measure[$i][] = trim($item[1]);
				$measure[$i][] = trim($item[2]);
			}  
			
		    $this->view->programDetail = $programDetail;
			$this->view->measures = $measure;
			 
			
		} else if ($state=="delete"){
			$where = $program->getAdapter()->quoteInto('id = ?', $id);
			$program->delete($where);
			$this->_redirect('/admin/program');
		} else if ($state=="paypal"){  
			$this->view->paypal = "paypal";
			$this->view->paypal_pid = $id; 
			
			$program_detail = $program->select()
					   ->where('id= ?', $id)
					   ->query()->fetchAll();

			if ($program_detail[0]["paypal"]!=""){
				$this->view->paypal_code = $program_detail[0]["paypal"];
			}
			
		} else { 
			$this->view->program_form = new Application_Form_Program();
		}
		
		
		
		if ($this->getRequest()->isPost() &&
			$this->view->program_form->isValid($this->getRequest()->getParams()))
		{
			echo $this->view->program_form->persistData();
			//$this->_redirect('/admin/program/update/'.$id);
		}

	} 
	
	public function programactiveAction(){
		if (!Zend_Auth::getInstance()->hasIdentity()) {
			$this->_redirect('/admin/login');
		}
		$sess = new Zend_Session_Namespace('renewal.auth');
		if ($sess->admingroup != "admin") $this->_redirect('/admin/login');

		$this->_helper->layout->disableLayout(); 
		$this->getHelper('viewRenderer')->setNoRender(true);
		
		$state = $this->_getParam('state');
		$pid = $this->_getParam('pid');
		$p_status = $this->_getParam('p_status');   
		
		$program_model = new Application_Model_Programs;
		
		if ($state == "update" && $pid!=null && $p_status!=null){
			$update_status = $program_model->update(
			array(
				'active'      => $p_status,
				), 
			array(
				'id = ?' => $pid
				)
			);
			
			echo $update_status;
		}
	}
	
	public function paypalbtnAction(){
		if (!Zend_Auth::getInstance()->hasIdentity()) {
			$this->_redirect('/admin/login');
		}
		$sess = new Zend_Session_Namespace('renewal.auth');
		if ($sess->admingroup != "admin") $this->_redirect('/admin/login');

		$this->_helper->layout->disableLayout(); 
		$this->getHelper('viewRenderer')->setNoRender(true);
		
		$pid = $this->_getParam('pid');
		$paypalbtn= stripslashes($this->_getParam('paypal_btn'));
		
		$program_model = new Application_Model_Programs;
		
		$update_status = $program_model->update(
		array(
			'paypal'      => $paypalbtn,
			), 
		array(
			'id = ?' => $pid
			)
		); 
	    
		header('Content-Type: application/json'); 
		echo $update_status;
		
	}
	
	public function enrollmentAction()
	{   
		if (!Zend_Auth::getInstance()->hasIdentity()) {
			$this->_redirect('/admin/login');
		}
		$sess = new Zend_Session_Namespace('renewal.auth');
		if ($sess->admingroup != "admin") $this->_redirect('/admin/login');
		
	    $pid = $this->_getParam('pid');
		$uid = $this->_getParam('uid');
		$program_model = new Application_Model_Programs;
		$this->view->programs = $program_model->fetchAll();
	}
	
	public function programusersAction(){ 
		if (!Zend_Auth::getInstance()->hasIdentity()) {
			$this->_redirect('/admin/login');
		}
		$sess = new Zend_Session_Namespace('renewal.auth');
		if ($sess->admingroup != "admin") $this->_redirect('/admin/login');
		
		$this->_helper->layout->disableLayout(); 
		$this->getHelper('viewRenderer')->setNoRender(true);  
		
		$db = Zend_Db_Table::getDefaultAdapter(); 
		$pid = $this->_getParam('pid');    
		$uid = $this->_getParam('uid');
		$rid = $this->_getParam('rid');
		$mid = $this->_getParam('mid');
		$pidm= $this->_getParam('pidm');
		$measure = stripslashes($this->_getParam('m_output'));
		
		// list program's user 
		if($pid!=null && $uid==null && $rid==null && $mid==null && $measure==null){
			$program_model = new Application_Model_Programs;
			$program       = $program_model->find($pid)->current();
			$users         = $program->findManyToManyRowset('Application_Model_Users', 'Application_Model_UsersPrograms');

			header('Content-Type: application/json'); 
			echo json_encode($users->toArray());
		}   
		
		// check and add user to program
		if($pid!=null && $uid!=null && $rid==null && $mid==null && $measure==null){    
			$user = $db->select()
				       ->from('users_programs')
					   ->where('user_id = ?', $uid)
					   ->where('program_id = ?', $pid)
					   ->query()->fetchAll();

		    header('Content-Type: application/json'); 
		    if ($user != null){
				echo 0;
			}else{
				$table = new Application_Model_UsersPrograms;
				$table->insert(array("user_id"=>$uid, "program_id"=>$pid));
				echo 1;
		    }	
			
		}
		
		//remove user from program
		if ($pid!=null && $uid==null && $rid!=null && $mid==null && $measure==null){
			$db->delete('users_programs', array(
				'program_id = ?' => $pid,
				'user_id = ?' => $rid
			));
		}
		
		//read user program measurement data
		if ($pid!=null && $uid==null && $rid==null && $mid!=null && $measure==null){
			$user = $db->select()
				       ->from('users_programs')
					   ->where('user_id = ?', $mid)
					   ->where('program_id = ?', $pid)
					   ->query()->fetchAll();
		    echo json_encode($user); 
		} 
		
		//read default program measurement 
		if ($pidm!=null && $pid==null && $uid==null && $rid==null && $mid==null && $measure==null){
		   $p_measurements = $db->select()->from('programs','p_measure')
		 						  ->where('id = ?', $pidm)
								  ->query()
								  ->fetchAll();
								
		   echo json_encode($p_measurements);
		}
		
		//update measurement
		if ($measure!=null && $uid!=null && $pid!=null && $rid==null && $mid==null ){
			$programuser_model = new Application_Model_UsersPrograms;
			//$data = 

			$update_status = $programuser_model->update(
			array(
				'u_measure'      => $measure,
				), 
			array(
				'user_id = ?' => $uid,
				'program_id = ?' => $pid
				)
			);
			
			echo $update_status;
		}
	}
	
	public function usersdirectoryAction(){	 
		if (!Zend_Auth::getInstance()->hasIdentity()) {
			$this->_redirect('/admin/login');
		}
		$sess = new Zend_Session_Namespace('renewal.auth');
		if ($sess->admingroup != "admin") $this->_redirect('/admin/login');  
		
	    $this->_helper->layout->disableLayout(); 
		$this->getHelper('viewRenderer')->setNoRender(true);
		   
		$user_model = new Application_Model_Users;
		$users		= $user_model->fetchAll(); 
		foreach ($users as $user) {
			$items[$user->id] = $user->fname." ".$user->lname;
		}
		
		header('Content-Type: application/json'); 
		echo json_encode($items); 
		
	}
	
	public function userprofileAction(){
		if (!Zend_Auth::getInstance()->hasIdentity()) {
			$this->_redirect('/admin/login');
		}
		$sess = new Zend_Session_Namespace('renewal.auth');
		if ($sess->admingroup != "admin") $this->_redirect('/admin/login');  
		
	    $this->_helper->layout->disableLayout(); 
		$this->getHelper('viewRenderer')->setNoRender(true); 
		
		$item = $this->_getParam('item');
		$uid = $this->_getParam('uid');
		$value = stripslashes($this->_getParam('value'));

		$user_model = new Application_Model_Users;

		$update_status = $user_model->update(
		array(
			$item => $value,
			), 
		array(
			'id = ?' => $uid,
			)
		);
		  
		echo $value;
	}
	
	public function rstpwdAction(){
		if (!Zend_Auth::getInstance()->hasIdentity()) {
			$this->_redirect('/admin/login');
		}
		$sess = new Zend_Session_Namespace('renewal.auth');
		if ($sess->admingroup != "admin") $this->_redirect('/admin/login');  

		$this->_helper->layout->disableLayout(); 
		$this->getHelper('viewRenderer')->setNoRender(true);  
		
		$user_model = new Application_Model_Users; 
		
		$uid = $this->_getParam('uid');
		
		$update_status = $user_model->update(
			array(
				'password' => '123456',
				), 
			array(
				'id = ?' => $uid,
				)
	   	);
	   	echo $update_status;
	}
    
	public function contentsAction(){
		if (!Zend_Auth::getInstance()->hasIdentity()) {
			$this->_redirect('/admin/login');
		}               
		
		$sess = new Zend_Session_Namespace('renewal.auth');
		if ($sess->admingroup != "admin") $this->_redirect('/admin/login');
		
		$content_model = new Application_Model_Contents();                            
		$this->view->intro = $content_model
			->fetchRow($content_model->select()->where("name = 'introduction'"))->value;
		$this->view->about = $content_model
			->fetchRow($content_model->select()->where("name = 'about'"))->value;
		$this->view->description= $content_model
			->fetchRow($content_model->select()->where("name = 'location_description'"))->value;
		$this->view->hour= $content_model
			->fetchRow($content_model->select()->where("name = 'location_time'"))->value;
		$this->view->add1= $content_model
			->fetchRow($content_model->select()->where("name = 'location_address_1'"))->value;
		$this->view->add2= $content_model
			->fetchRow($content_model->select()->where("name = 'location_address_2'"))->value;
		$this->view->city= $content_model
			->fetchRow($content_model->select()->where("name = 'location_city_state_zip'"))->value; 
		$this->view->fb= $content_model
			->fetchRow($content_model->select()->where("name = 'fb_link'"))->value;
		$this->view->twt= $content_model
			->fetchRow($content_model->select()->where("name = 'twitter_link'"))->value;
	}
	
	public function updatecontentAction(){ 
		if (!Zend_Auth::getInstance()->hasIdentity()) {
			$this->_redirect('/admin/login');
		}               
		
		$sess = new Zend_Session_Namespace('renewal.auth');
		if ($sess->admingroup != "admin") $this->_redirect('/admin/login');  

		$this->_helper->layout->disableLayout(); 
		$this->getHelper('viewRenderer')->setNoRender(true);  
		
		$contents_model = new Application_Model_Contents; 
		$item = $this->_getParam('item');  
		$value = stripslashes($this->_getParam('value'));
		
		$update_status = $contents_model->update(
		array(
			'value' => $value,
			), 
		array(
			'name = ?' => $item,
			)
		); 
		
		echo $value;
	}
	
	public function testimonialsAction(){   
		if (!Zend_Auth::getInstance()->hasIdentity()) {
			$this->_redirect('/admin/login');
		}               
		
		$sess = new Zend_Session_Namespace('renewal.auth');
		if ($sess->admingroup != "admin") $this->_redirect('/admin/login');
		
		$testimonials_model = new Application_Model_Testimonials;
		$this->view->testimonials = $testimonials_model->fetchAll();  
	}        
	
	public function updatetestimonialsAction(){
		if (!Zend_Auth::getInstance()->hasIdentity()) {
			$this->_redirect('/admin/login');
		}               
		
		$sess = new Zend_Session_Namespace('renewal.auth');
		if ($sess->admingroup != "admin") $this->_redirect('/admin/login');

		$this->_helper->layout->disableLayout(); 
		$this->getHelper('viewRenderer')->setNoRender(true);  

		$testimonial_model = new Application_Model_Testimonials;
		$id = $this->_getParam('id');  
		$item = $this->_getParam('item');  
		$value = stripslashes($this->_getParam('value'));

		switch ($item) {
			case 'tm-name':	
				$item='name';
				break;
			case 'tm-from': 
				$item='from'; 
				break;
			case 'tm-value': 
				$item='content';
				break;
		}   
		
		$update_status = $testimonial_model->update(
		array(
			$item => $value,
			), 
		array(
			'id = ?' => $id,
			)
			);     
	   
		echo $value;
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

