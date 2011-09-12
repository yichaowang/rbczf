<?php

class TestimonialsController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
	 	$testimonial_model = new Application_Model_Testimonials();                           
		$this->view->testimonials= $testimonial_model->fetchAll();
    }


}

