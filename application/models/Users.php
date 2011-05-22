<?php

class Application_Model_Users extends Zend_Db_Table_Abstract
{

    protected $_name = 'users';
	protected $_primary = 'id';
	
	public function get()
	{
		$id = (int)$id;
		$row = $this->fetchRow('uid ='.$id);
		if (!row) {
			throw new Exception("could not find row $id");
		}                                                 
		return $row->toArray();
	}
	
	public function create($data)
	{
	   
	}
	
	public function edit()
	{
		
	}
	
	public function delete()
	{
		
	}


}

