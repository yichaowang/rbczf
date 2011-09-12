<?php

class Application_Form_Program extends Zend_Form
{
	
	protected $formState;
	
	public function __construct($formState = "create", $currentProgram = null)
	{
		$this->formState = $formState;
	

		$id = new Zend_Form_Element_Hidden("id");
		$id->setDecorators(array('ViewHelper'));
				
		$name = new Zend_Form_Element_Text("name");
		$name->setLabel("Program Name:");
		
		$measure = new Zend_Form_Element_Textarea("p_measure");
	  
		$measure
			->setAttrib('COLS', '40')
			->setAttrib('ROWS', '12')
			->setLabel("Measures: (Format: Name: Value: Unit;)");
		
		
		$submit = new Zend_Form_Element_Submit($formState); 
		$submit->setAttrib('class','ui-button-apperence');
		
		if ($this->formState == "update")
		{
			$id->setValue($currentProgram[0]->id);
			$name->setValue($currentProgram[0]->name);
			$measure->setValue($currentProgram[0]->p_measure);
			//$measure->setAttrib('readonly', 'readonly');
			//$measure->setAttrib('style','border: 1px solid #eee');	
		}
		
		$this->addElements(array($id,$name,$measure,$submit));
		
			
		$this->setDecorators(array(
		    'FormElements',
		    array('HtmlTag', array('tag' => 'dl')),
		    'Form'
		));		
	}
	
	public function persistData()
	{
		$programTable = new Application_Model_Programs();
		$this->removeElement($this->formState);	
		if ($this->formState == "update")
		{
			$p_id = $this->getValue('id');
			$p_measure = explode(';',$this->getValue('p_measure'));
			$p_measure = preg_grep('#\S#', array_map('trim', $p_measure));
			$where = $programTable->getAdapter()
					 ->quoteInto('id = ?', $p_id);	
			$programTable->update($this->getValues(),$where);
			   
			// update individual client entry 
			$user_program_model = new Application_Model_UsersPrograms;
			$program_users_rowsets = $user_program_model->fetchAll("program_id =".$p_id);
			foreach ($program_users_rowsets as $user_row){
				if ($user_row['u_measure']!=''){
					$u_measure = explode(";",$user_row['u_measure']);
					$u_measure = preg_grep('#\S#', array_map('trim', $u_measure));   
					if(count($p_measure) > count($u_measure)){ 
						foreach ($p_measure as $key=>$p){
							$p_i = explode(":",$p);
							$u_i = explode(":",$u_measure[$key]);
							if (!isset($p_i[2])){
								unset($p_measure[$key]);
								$p_measure = array_values($p_measure);
								break 2; 
							}
							if (trim($p_i[0])!=trim($u_i[0])){
								array_splice($u_measure, $key, 0, $p_i[0].":N/A,N/A:".$p_i[2]);
							}else if(trim($p_i[2])!=trim($u_i[2])){
								$u_measure[$key] = $p_i[0].":".$u_i[1].":".$p_i[2];
							}
							echo "adding</br>"; 
							//echo $key.":".$p." and ".$p_i[0]."</br>";
							//echo $key.":".$u_measure[$key]." and ".$u_i[0]."</br></br>";
						}
					}else if(count($p_measure) == count($u_measure)){
						foreach ($p_measure as $key=>$p){
							$p_i = explode(":",$p);
							$u_i = explode(":",$u_measure[$key]); 
							if (!isset($p_i[2])){
								unset($p_measure[$key]);
								$p_measure = array_values($p_measure);
								break 2; 
							}
							if (trim($p_i[0])!=trim($u_i[0]) || trim($p_i[2])!=trim($u_i[2]) ){
								$u_measure[$key] = $p_i[0].":".$u_i[1].":".$p_i[2];
							}; 
							echo "Changing</br>"; 
							//echo $key.":".$p." and ".$p_i[0]."</br>";
							//echo $key.":".$u_measure[$key]." and ".$u_i[0]."</br></br>";
						}
						
					}else if(count($p_measure) < count($u_measure)){
						foreach ($u_measure as $key=>$u){
							if (!isset($p_measure[$key])){
								unset($u_measure[$key]);
								$u_measure = array_values($u_measure);
							}else{
								$p_i = explode(":",$p_measure[$key]);
							}
							$u_i = explode(":",$u); 
							if (!isset($p_i[2])){
								unset($p_measure[$key]);
								$p_measure = array_values($p_measure);
							} 
							if (trim($p_i[0])!=trim($u_i[0])){
                            	unset($u_measure[$key]);
								$u_measure = array_values($u_measure);
							}; 
							if (trim($p_i[2])!=trim($u_i[2])){
                            	$u_measure[$key] = $p_i[0].":".$u_i[1].":".$p_i[2];
							};
							echo "Deleting</br>"; 
							echo $key.":".$p_measure[$key]." and ".$p_i[0]."</br>";
							echo $key.":".$u." and ".$u_i[0]."</br></br>";
						}
					} 
					
					
					$user_row['u_measure'] = implode(";",$u_measure).";";
					$user_row->save();
				};
			}
			
		}
		else{
			$programTable->insert($this->getValues());
		}
			
		$this->addElement(new Zend_Form_Element_Submit($this->formState));
		
	}
}

?>