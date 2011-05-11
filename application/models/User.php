<?php

class Application_Model_User
{
	protected $_id
	protected $_fname;
	protected $_lname;
	protected $_email;
	
	public function __set ($name, $value);
	public function __get ($name);

}
	
class Application_Model_UserMapper
{
    public function save(Application_Model_Guestbook $user);
    public function find($id);
    public function fetchAll();
}
