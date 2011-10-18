<?php

class IndexController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        $program_model = new Application_Model_Programs();
 		$content_model = new Application_Model_Contents();
        $gallery_model = new Application_Model_Gallery();

		$this->view->intro = $content_model->select()->where("name = 'introduction'")->query()->fetchAll();                                            
		$this->view->programs = $program_model->fetchAll();
		$this->view->gallery = $gallery_model->fetchAll($gallery_model->select()->order('seq'));
    }


}

