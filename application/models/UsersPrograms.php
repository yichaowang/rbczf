<?php

class Application_Model_UsersPrograms extends Zend_Db_Table_Abstract
{

    protected $_name = 'users_programs';
	protected $_primary = 'id';  
	
	
    protected $_referenceMap    = array(
        'Users' => array(
            'columns'           => array('user_id'),
            'refTableClass'     => 'Application_Model_Users',
            'refColumns'        => array('id')
        ),
        'Programs' => array(
            'columns'           => array('program_id'),
            'refTableClass'     => 'Application_Model_Programs',
            'refColumns'        => array('id')
        )
    );      
}