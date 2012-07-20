<?php

class DM_Message extends DataMapper
{
	public $table = 'messages';
	public $created_field = 'created_date';
	public $updated_field = 'updated_date';

	public static $config;


	public function __construct()
	{
	 	parent::__construct();
  	}


}


?>