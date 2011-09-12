<?php

class AboutController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
		$content_model = new Application_Model_Contents();                           
		$this->view->about= $content_model
			->fetchRow($content_model->select()->where("name = 'about'"))->value;
    }


}

