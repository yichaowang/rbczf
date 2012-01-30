<?php

class AdminController extends Zend_Controller_Action
{
	
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
		$this->view->programs = $program->fetchAll($program->select()->order('id ASC'));
	}         
	
	public function programaddAction(){
		if (!Zend_Auth::getInstance()->hasIdentity()) {
			$this->_redirect('/admin/login');
		}
		$sess = new Zend_Session_Namespace('renewal.auth');
		if ($sess->admingroup != "admin") $this->_redirect('/admin/login'); 
		$this->_helper->layout->disableLayout();
		
		$program_name = $this->_getParam('name');		
        if ($program_name==""){
	    	$this->getHelper('viewRenderer')->renderScript('admin/partial/program/add_program.phtml');
		} else {
			$this->getHelper('viewRenderer')->setNoRender(true);
			
			$program_model = new Application_Model_Programs;
			$new_program = array(
				'name' 		=> $program_name,
				'p_measure' => 'Heart Rate:bpm;Jacks:per min;Plank:in 2 mins;Sprints:per min;Sit Ups:per min;Jump Rope:per min;Burpees:per min;Push Ups:per min',
				'active' 	=> 0
			);
			$program_model->insert($new_program);
			echo 1;
		}
		
	}
	
	public function programdeleteAction(){
		if (!Zend_Auth::getInstance()->hasIdentity()) {
			$this->_redirect('/admin/login');
		}
		$sess = new Zend_Session_Namespace('renewal.auth');
		if ($sess->admingroup != "admin") $this->_redirect('/admin/login');
		 
		$this->_helper->layout->disableLayout();
		$this->getHelper('viewRenderer')->setNoRender(true);
		
		$program_id = $this->_getParam('id');
				
        if ($program_id!=""){
			$program_model = new Application_Model_Programs;
			$program_model->delete('id ='.$program_id);
			$this->_redirect('/admin/program');
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
				array('active' => $p_status), 
				array('id = ?' => $pid)
			);
			echo $update_status;
		}
	}
	
	
	public function programdetailAction(){
		if (!Zend_Auth::getInstance()->hasIdentity()) {
			$this->_redirect('/admin/login');
		}                                      

		$sess = new Zend_Session_Namespace('renewal.auth');
		if ($sess->admingroup != "admin") $this->_redirect('/admin/login');
		
		$this->_helper->layout->disableLayout(); 
		 
		$program_model = new Application_Model_Programs;
		$id = $this->_getParam('id');
		$state = $this->_getParam('state');

		$program_detail = $program_model->find($id)->current();
        
		$measures = $program_detail['p_measure'];
		$measures = explode(';',$measures);
		$measures = preg_grep('#\S#', array_map('trim', $measures));			
		for ($i=0; $i<count($measures);$i++) {
			$item = explode(':', $measures[$i]);
			$measure[$i]['name'] = trim($item[0]);
			$measure[$i]['unit'] = trim($item[1]);
		} 

		$this->view->program_detail = $program_detail;
		$this->view->program_measures = $measure;    
        if ($state == "preview") {
			$this->getHelper('viewRenderer')->renderScript('admin/partial/program/detail_preview.phtml');
		} else if ($state == "edit") {
			$this->getHelper('viewRenderer')->renderScript('admin/partial/program/detail_editing.phtml');
		}
	} 
	
	public function programpaypalAction(){
		if (!Zend_Auth::getInstance()->hasIdentity()) {
			$this->_redirect('/admin/login');
		}                                      
		
		$sess = new Zend_Session_Namespace('renewal.auth');
		if ($sess->admingroup != "admin") $this->_redirect('/admin/login');

		$id = $this->_getParam('id');
		$req = $this->_getParam('req');
		$paypal_code = stripslashes($this->_getParam('paypal')); 
		$program_model = new Application_Model_Programs;
		$program_row = $program_model->find($id)->current();
		
		if ($req=='preview'){
			$this->view->program = $program_row;  
			$this->_helper->layout->disableLayout();
			$this->getHelper('viewRenderer')->renderScript('admin/partial/program/paypal.phtml');
		} else if ($req=='update'){
			$this->_helper->layout->disableLayout(); 
			$this->getHelper('viewRenderer')->setNoRender(true);
			$program_row->paypal = $paypal_code;
			$program_row->save();
			// echo 1;
			
			echo $paypal_code;
		} 
	}
	
	public function programcontentAction(){
		if (!Zend_Auth::getInstance()->hasIdentity()) {
			$this->_redirect('/admin/login');
		}                                      
		
		$sess = new Zend_Session_Namespace('renewal.auth');
		if ($sess->admingroup != "admin") $this->_redirect('/admin/login');

		$this->_helper->layout->disableLayout(); 
		$this->getHelper('viewRenderer')->setNoRender(true);
		
		$id = $this->_getParam('id'); 
		$item = $this->_getParam('item');
		$value = stripslashes($this->_getParam('value'));

		$program_model = new Application_Model_Programs;

		$update_status = $program_model->update(
			array($item => $value),
			array('id = ?' => $id)
		);
		  
		echo $value;   
	}  
	
	public function programitemAction(){
		if (!Zend_Auth::getInstance()->hasIdentity()) {
			$this->_redirect('/admin/login');
		}                                      
		
		$sess = new Zend_Session_Namespace('renewal.auth');
		if ($sess->admingroup != "admin") $this->_redirect('/admin/login');

		$this->_helper->layout->disableLayout(); 
		$this->getHelper('viewRenderer')->setNoRender(true);
		 
		$pid = $this->_getParam('pid');
		$id  = $this->_getParam('id'); // the position of the item in p_measure
		$type = ($this->_getParam('item_type')=='item-unit') ? 1 : 0;
		$type_user = ($this->_getParam('item_type')=='item-unit') ? 2 : 0;
		$value = stripslashes($this->_getParam('value'));       
		
		// update programs p_measures
		$program_model = new Application_Model_Programs;
		$program_detail = $program_model->find($pid)->current();
		$detail = $program_detail['p_measure'];  
		$items = explode(";", $detail);
		$items = preg_grep('#\S#', array_map('trim', $items));
		$items_size = count($items);
		for ($i=0;$i<$items_size;$i++){
			$items[$i] = explode(":",$items[$i]);
		}   
		$items[$id][$type] = $value;
		
		$new_items = array();
	   	foreach($items as $item){
			$new_items[] = implode(":",$item);
		}                              
		$new_items = implode(";",$new_items); 
		
		$update_status = $program_model->update(
			array('p_measure' => $new_items),
			array('id = ?' => $pid)
		);
	    
		// update users_program u_measure
		$user_program_model = new Application_Model_UsersPrograms;
		$users_program_rowset = $user_program_model->fetchAll('program_id = '. $pid);
		foreach($users_program_rowset as $row){ 
			if ($row->u_measure == ""){
				continue;
			}
			$umeasure_output = array();  
			$umeasure_item_row = explode(";", $row->u_measure);
		   	$umeasure_item_row = preg_grep('#\S#', array_map('trim', $umeasure_item_row));
			$count = count($umeasure_item_row);
            for($j=0;$j<$count;$j++){
	        	$umeasure_output[$j] = explode(":",$umeasure_item_row[$j]);
			}
			$umeasure_output[$id][$type_user] = $value;  // mapping update
			$output = array();
			foreach($umeasure_output as $umeasure){
				$output[]=implode(":",$umeasure);
			}
			$output = implode(";", $output);
			$row->u_measure = $output;
			$row->save();
		}
		
		echo $value;
			
	}     
	
	public function programsortAction(){
		if (!Zend_Auth::getInstance()->hasIdentity()) {
			$this->_redirect('/admin/login');
		}
		$sess = new Zend_Session_Namespace('renewal.auth');
		if ($sess->admingroup != "admin") $this->_redirect('/admin/login');

		$this->_helper->layout->disableLayout(); 
		$this->getHelper('viewRenderer')->setNoRender(true);

		$pid = $this->_getParam('pid');
		$start_pos  = $this->_getParam('start_pos'); 
	  	$end_pos  = $this->_getParam('end_pos');
	    
		if ($start_pos == $end_pos) {
			exit;
		}                         
		
	  	// update programs p_measures
	 	$program_model = new Application_Model_Programs;
		$program_row = $program_model->find($pid)->current();
		$items = explode(";", $program_row->p_measure);
		$items = preg_grep('#\S#', array_map('trim', $items));
		$item_temp = $items[$start_pos];
		unset($items[$start_pos]);
   		array_splice($items,$end_pos,0,$item_temp);   
		$program_row->p_measure = implode(";", $items);
		$program_row->save();
		
		// update users_program u_measure
		$user_program_model = new Application_Model_UsersPrograms;
		$users_program_rowset = $user_program_model->fetchAll('program_id = '. $pid);
		foreach($users_program_rowset as $row){ 
			if ($row->u_measure == ""){
				continue;
			}
			$umeasure_item_rowset = explode(";", $row->u_measure);
		   	$umeasure_item_rowset = preg_grep('#\S#', array_map('trim', $umeasure_item_rowset));
			$umeasure_temp = $umeasure_item_rowset[$start_pos];
			unset($umeasure_item_rowset[$start_pos]);
			array_splice($umeasure_item_rowset,$end_pos,0,$umeasure_temp);   
			$row->u_measure = implode(";",$umeasure_item_rowset);
			$row->save();   
		}
	}                                                       
	
	public function programdeleteitemAction(){
	   	if (!Zend_Auth::getInstance()->hasIdentity()) {
			$this->_redirect('/admin/login');
		}
		$sess = new Zend_Session_Namespace('renewal.auth');
		if ($sess->admingroup != "admin") $this->_redirect('/admin/login');

		$this->_helper->layout->disableLayout(); 
		$this->getHelper('viewRenderer')->setNoRender(true);
		
		$pid = $this->_getParam('pid');
		$id = $this->_getParam('id'); 
		 
		// delete programs p_measures
	 	$program_model = new Application_Model_Programs;
		$program_row = $program_model->find($pid)->current();
		$items = explode(";", $program_row->p_measure);
		$items = preg_grep('#\S#', array_map('trim', $items));
		unset($items[$id]);
		$program_row->p_measure = implode(";", $items);
		$program_row->save();      
		
		// delete users_program u_measure
		$user_program_model = new Application_Model_UsersPrograms;
		$users_program_rowset = $user_program_model->fetchAll('program_id = '. $pid);
		foreach($users_program_rowset as $row){ 
			if ($row->u_measure == ""){
				continue;
			}
			$umeasure_item_rowset = explode(";", $row->u_measure);
		   	$umeasure_item_rowset = preg_grep('#\S#', array_map('trim', $umeasure_item_rowset));
			unset($umeasure_item_rowset[$id]);
			$row->u_measure = implode(";",$umeasure_item_rowset);
			$row->save();   
		}
		
	}   
	
	
	public function programaddmeasurementAction(){
		if (!Zend_Auth::getInstance()->hasIdentity()) {
			$this->_redirect('/admin/login');
		}
		$sess = new Zend_Session_Namespace('renewal.auth');
		if ($sess->admingroup != "admin") $this->_redirect('/admin/login');

		$this->_helper->layout->disableLayout(); 
		$this->getHelper('viewRenderer')->setNoRender(true);

		$pid = $this->_getParam("pid");
		$mname = $this->_getParam("mname");
		$munit = $this->_getParam("munit"); 
        
		// add programs p_measures
		$program_model = new Application_Model_Programs;
		$program_row = $program_model->find($pid)->current();
		$items = $program_row->p_measure;
		$items = $items.";".$mname.":".$munit;
		$program_row->p_measure = $items;
		$program_row->save();
		
		// add users_program u_measure
		$user_program_model = new Application_Model_UsersPrograms;
		$users_program_rowset = $user_program_model->fetchAll('program_id = '. $pid);
		foreach($users_program_rowset as $row){ 
			if ($row->u_measure == ""){
				continue;
			}
			$umeasure_item = $row->u_measure;
		   	$umeasure_item = $umeasure_item.";".$mname.":N/A,N/A:".$munit;
			$row->u_measure = $umeasure_item;
			$row->save();   
		}
	}
	
	public function programtestarrayAction(){
		if (!Zend_Auth::getInstance()->hasIdentity()) {
			$this->_redirect('/admin/login');
		}
		$sess = new Zend_Session_Namespace('renewal.auth');
		if ($sess->admingroup != "admin") $this->_redirect('/admin/login');

		$this->_helper->layout->disableLayout(); 
		$this->getHelper('viewRenderer')->setNoRender(true);


		$pid = $this->_getParam('pid');
		$start_pos  = $this->_getParam('start_pos'); 
		$end_pos  = $this->_getParam('end_pos'); 
		
		$start_pos =1;
		$end_pos = 4;
		$test_array = array('a', 'b', 'c', 'd', 'e');
		$insert_array = $test_array[$start_pos];
		unset($test_array[$start_pos]);
		array_splice($test_array, $end_pos,0, $insert_array);   
		
		print_r($test_array);
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
	
	public function galleryAction(){
		if (!Zend_Auth::getInstance()->hasIdentity()) {
			$this->_redirect('/admin/login');
		}               
		
		$sess = new Zend_Session_Namespace('renewal.auth');
		if ($sess->admingroup != "admin") $this->_redirect('/admin/login');    
		
		$gallery_model = new Application_Model_Gallery();                            
		$this->view->gallery = $gallery_model->fetchAll($gallery_model->select()->order('seq'));
		
		
	}                                                                       
	
	public function galleryuploadAction(){
		 if (!Zend_Auth::getInstance()->hasIdentity()) {
				$this->_redirect('/admin/login');
			}               
                        
		$sess = new Zend_Session_Namespace('renewal.auth');
		if ($sess->admingroup != "admin") $this->_redirect('/admin/login');   
		
		$this->_helper->layout->disableLayout(); 
		$this->getHelper('viewRenderer')->setNoRender(true);    
		
		$gallery_model = new Application_Model_Gallery();                            
		

		if (isset($_FILES['ufile'])){
			$dir = 'images/gallery-index/';
			$filedir = $dir . $_FILES['ufile']['name'];                             
			if (move_uploaded_file($_FILES['ufile']['tmp_name'], $filedir)){
				$gallery_model->insert(array(
					'filename' => $_FILES['ufile']['name'],
					'caption' => $_FILES['ufile']['name'],
					'src' => $filedir,
					'seq' => 0
				));   
			}                            
		}          
		$this->_redirect('/admin/gallery');
	}
	
	public function galleryupdateAction(){
		if (!Zend_Auth::getInstance()->hasIdentity()) {
			$this->_redirect('/admin/login');
		}               
		
		$sess = new Zend_Session_Namespace('renewal.auth');
		if ($sess->admingroup != "admin") $this->_redirect('/admin/login');
		
		$this->_helper->layout->disableLayout(); 
		$this->getHelper('viewRenderer')->setNoRender(true);
		
		$id = $this->_getParam('id');
		$item = $this->_getParam('item');
		$value = $this->_getParam('value');
		$delete = $this->_getParam('del');
	    
		$gallery_model = new Application_Model_Gallery;
		$gallery_row = $gallery_model->find($id)->current();
		
		if ( $delete == "" ){
			$gallery_row->$item = $value;
			$gallery_row->save(); 
			echo $value;
		} else {
			$dir = $gallery_row->src;
			unlink($dir);
			$gallery_row->delete();
		}
		
	
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
		$this->view->testimonials = $testimonials_model->fetchAll($testimonials_model->select()->order('id ASC')); 
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
}

