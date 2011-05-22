<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	protected function _initDojo() {
		// get view resource 
		$this->bootstrap('view'); 
		$view = $this->getResource('view');
		// add helper path to view 
		Zend_Dojo::enableView($view);

		// configure Dojo view helper, disable 
		$view->dojo()->setCdnBase(Zend_Dojo::CDN_BASE_GOOGLE)
			->addStyleSheetModule('dijit.themes.tundra') 
			->disable();
	}
}

