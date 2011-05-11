<?php
class Application_Form_UserLogin extends Zend_Form
{
	public function init()
	{
		$this->setName('login')
	         ->setMethod('post');
	
		$email = new Zend_Form_Element_Text('email');
		$email->setLabel('Email:')
				->setRequired(true)
				->addFilter('StripTags') 
				->addFilter('StringTrim') 
				->addValidator('EmailAddress');
   	
		$password = new Zend_Form_Element_Password('password');
	    $password->setLabel('Password:')
	             ->setRequired(true)
	             ->addFilter('HtmlEntities')            
	             ->addFilter('StringTrim');
	                                
		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setLabel('Login');
		$submit->setAttrib('class', 'submit');     
	
		$this->addElements(
			array($email, $password, $submit)
		);
	}
}