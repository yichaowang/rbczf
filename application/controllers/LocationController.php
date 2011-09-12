<?php

class LocationController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        $content_model = new Application_Model_Contents;
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
    }


}

