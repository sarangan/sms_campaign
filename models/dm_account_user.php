<?php

class DM_Account_User extends DataMapper
{
	public $table = 'account_users';
	//public $created_field = 'created_date';
	//public $updated_field = 'updated_date';
	

	public static $config;

	var $validation = array(
  	    array(
	        'field' => 'username',
	        'label' => 'Username',
	        'rules' => array('required','unique')
	    ),
	    array(
	        'field' => 'customer_id',
	        'label' => 'Customer ID',
	        'rules' => array('required')
	    ),
	    array(
	        'field' => 'password',
	        'label' => 'Password',
	        'rules' => array('required')
	    ),
	    array(
	        'field' => 'confirm_password',
	        'label' => 'Confirm Password',
	        'rules' => array('required', 'matches' => 'password')
	    ),
	    array(
	        'field' => 'email',
	        'label' => 'Email',
	        'rules' => array('required','valid_email')
	    ),
	    array(
	        'field' => 'phone',
	        'label' => 'Phone',
	        'rules' => array('required','numeric','min_length' => 10)
	    ),
	    array(
	        'field' => 'first_name',
	        'label' => 'First Name',
	        'rules' => array('required')
	    ),
	    array(
	        'field' => 'last_name',
	        'label' => 'Last Name',
	        'rules' => array('required')
	    )
	);

	public function __construct()
	{
	 	parent::__construct();
	}

	public function is_password_valid($password)
	{
		return (md5($password)==$this->password);
	}

	public function is_internal_special_account()
	{
	   
	   /*$special_accounts['alanwong']='';
	   $special_accounts['haakonkalbakk']='';
	   $special_accounts['incent']='';
	   $special_accounts['geirsand']='';
	   $special_accounts['John']='';
	   $special_accounts['havard']='';
	   $special_accounts['anna']='';
	   $special_accounts['lily']='';
	   $special_accounts['trondreite']='';
	   $special_accounts['sara']='';
	   $special_accounts['bjornbotten']='';
           $special_accounts['grim']='';
	   $special_accounts['bernita'] = '';		   
	   $special_accounts['casper']=''; */
	   //$CI = &get_instance();
	   
	   //$dm_special = new DM_Special_Accounts();
	   //$result =  $dm_special->where('active_flag',1)->get()->result();
	   //error_log($dm_special->query());
	   
	   /*$CI =  get_instance();
	   $CI->db->select('*');
	   $CI->db->from("special_accounts");
	   $CI->db->where("active_flag", 1);
	   $result = $CI->db->get()->result();
	   
	   foreach ($result as $row){
		//error_log($row->account_name);
		$special_accounts[$row->account_name]='';
		}
		return isset($special_accounts[$this->username]);
	   */
	   
	   return $this->specialuser_flag==1?true:false;
	}

	public function get_group_count()
	{
		$dm_rel=new DM_Account_User_Tag_Rel();
		return $dm_rel->where('account_user_id',$this->id)->count();
	}
    public function get_fullname()
    {
    	if ($this->first_name=='' and $this->last_name=='') return '';
    	if ($this->first_name=='' and $this->last_name!='') return $this->last_name;
    	if ($this->first_name!='' and $this->last_name=='') return $this->first_name;
    	return "$this->first_name $this->last_name";
    }

}


?>
