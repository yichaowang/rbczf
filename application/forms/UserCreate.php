<?php

class Application_Form_UserCreate extends Zend_Form
{
	public function init()
	{
		$this->setName('create-user');
		
		// $id = nwe Zend_Form_Element_Hidden('id');
		// $id->addFilter('Int'); 
		
		$fname = new Zend_Form_Element_Text('fname');
		$fname->setLabel('First Name:')
				->setRequired(true)
				->addFilter('StripTags') 
				->addFilter('StringTrim')
				->addValidator('NotEmpty');
				
	   	$lname = new Zend_Form_Element_Text('lname');
		$lname->setLabel('Last Name:')
				->setRequired(true)
				->addFilter('StripTags') 
				->addFilter('StringTrim')
				->addValidator('NotEmpty'); 
				

		$validator = new Zend_Validate_Db_NoRecordExists(array(
			'table'     => 'users',
			'field'     => 'email'
			));
		$validator->setMessage(
			"'%value%' is already registered with us. Please contact administrator if you believe there is a error",
			Zend_Validate_Db_NoRecordExists::ERROR_RECORD_FOUND 
			);
	       
		
		$email = new Zend_Form_Element_Text('email');
		$email->setLabel('Email:')
				->setRequired(true)
				->addFilter('StripTags') 
				->addFilter('StringTrim') 
				->addValidator('EmailAddress')
				->addValidator('Db_NoRecordExists', false, array(
					'table'     => 'users',
			        'field'     => 'email'
				));
			
  	 	$password = new Zend_Form_Element_Password('password');
	    $password->setLabel('Password:')
	             ->setRequired(true)
	             ->addFilter('HtmlEntities')            
	             ->addFilter('StringTrim');
	          
		$captcha = new Zend_Form_Element_Captcha('captcha', array(
		      'label' => 'Please enter two words displayed below:',
		       'required' => true,
		       'captcha' => array(
		           'pubkey' => '6Ldf3sYSAAAAAIifYDjWytu_8GOxXlPkFMwCPGeR',
		           'privkey' => '6Ldf3sYSAAAAAKfn-xewC2lBT5kfqyqD56fAcRML',
		           'captcha' => 'reCaptcha'
		       )   
		));      
	
		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setLabel('Create');
		$submit->setAttrib('id', 'submit');
		
		$this->addElements(
			array($fname, $lname, $email, $password, $captcha, $submit)
		);
		
	}
}