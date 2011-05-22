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
			->setAttrib('ROWS', '4')
			->setLabel("Measures: Format: Name: Value: Unit;");
		
		
		$submit = new Zend_Form_Element_Submit($formState);
		if ($this->formState == "update")
		{
			$id->setValue($currentProgram[0]->id);
			$name->setValue($currentProgram[0]->name);
			$measure->setValue($currentProgram[0]->p_measure);	
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
		$programTable = new ProgramsTable();
		$this->removeElement($this->formState);	
		if ($this->formState == "update")
		{
			
			$where = $programTable->getAdapter()
					 ->quoteInto('id = ?',$this->getValue('id'));	
			$programTable->update($this->getValues(),$where);
			
		}
		else{
			print_r($this->getValues());
			$programTable->insert($this->getValues());
		}
			
		$this->addElement(new Zend_Form_Element_Submit($this->formState));
		
	}
}

?>