<?php
include_once('header.php');
//require_once(APPPATH. "libraries/SecurityReceiverFactory.php");
//require_once(APPPATH. "libraries/HtmlElementFactory.php");
//require_once(APPPATH. "libraries/HtmlElement.php");
//require_once(APPPATH. "libraries/FormFactory.php");
//require_once(APPPATH. "libraries/FormManager.php");
//require_once(APPPATH. "libraries/Register.php");
//require_once(APPPATH. "libraries/MessageBundleManager.php");
//require_once(APPPATH. "libraries/pager.class.php");
//require_once(APPPATH. "libraries/MenuManager.php");

class Authentication_Controller extends CI_Controller {

    public $language=null;

	function __construct()
	{
		parent::__construct();
		$this->load->helpers('controller');

		controller_helper();
		switch_language();
		security_control();
	}

    function login($error='')
    {
    	if ($this->user_id) redirect('/authentication_controller/logout');

        if (empty($_POST))
        {
            $data['form'] = FormFactory::get_instance()->create(FormFactory::LOGIN)->create("",$error);
            $this->load->view('home',$data);
            return true;
        }

        $rules['username']  = 'required';
        $rules['password']  = 'required';
        $fields['username'] = $this->message_bundle->get_lang_value('username');
        $fields['password'] = $this->message_bundle->get_lang_value('password');

        $this->validation->set_rules($rules);
        $this->validation->set_fields($fields);
        if ($this->validation->run())
        {
            $username = $_POST['username'];
            $password = $_POST['password'];
			$error = validate_account($username, $password);
        }
        else
        {
            $error=$this->validation->error_string;
        }

        if ($error)
        {
            $data['data']=$_POST;
            $data['form'] = FormFactory::get_instance()->create(FormFactory::LOGIN)->create("",$error);
            $this->load->view('home',$data);
        }
        else
        {
            $this->save_login_activity();
			$redirect_page = $this->session->userdata('__redirect_page__');;
			if ($redirect_page)
			{
				$this->session->unset_userdata('__redirect_page__');
				redirect($redirect_page);
			}

            $start_page=INSTALLATION=='mobilskole' ? 'message_controller/message_send_simple'
                                                   : '/home/welcome';
	        redirect($start_page);
        }
    }

    function logout($error="")
    {
		$redirect_page = $this->session->userdata('__redirect_page__');;

		@session_start();
		if (isset($_SESSION['tag_page_no']))   unset($_SESSION['tag_page_no']);

        $this->save_logout_activity();

        $session_data['user_id']     ='';
        $session_data['menubar']     ='';
        $session_data['tag_page_no'] = '';
        $session_data['menu_id']     = '';
        $session_data['special_login_id']='';
		$session_data['__member_search_user_list__']='';
		$session_data['login_activity_id']='';
		$session_data['special_login_activity_id']='';

	//setcookie("tempgrp","",time() - 60*60); // erase grp cookie
	 setcookie('tempgrp', '', time()-1000);
        setcookie('tempgrp', '', time()-1000, '/');

	
        $this->session->set_userdata($session_data);
        $this->session->sess_destroy();

        $session_data['__redirect_page__']=$redirect_page;
        $this->session->set_userdata($session_data);
	
	
        $data['form'] = FormFactory::get_instance()->create(FormFactory::LOGIN)->create("",rawurldecode($error));
        $this->load->view('home',$data);
    }

	function special_login_form($error="")
	{
       if (!$this->dm_account_user->is_internal_special_account())
  	       $error='You are not allowed to access this option.';
       $data['form'] = FormFactory::get_instance()->create(FormFactory::SPECIAL_LOGIN)->create("",$error);
       $this->load->view('home',$data);
	}

	function special_login()
	{
		if (!$this->dm_account_user->is_internal_special_account())
		{
			$this->special_login_form();
			return;
		}

		$username      = $this->input->post('username');
		$your_password = $this->input->post('your_password');

		#---Simple Check----------------------------------------------------------------------
		$error= '';
		if (!$username) $error = "E_SPECIAL_LOGIN_USERNAME_REQUIRED";
		else if (!$your_password) $error = "E_SPECIAL_LOGIN_YOUR_PASSWORD_REQUIRED";

		if ($error)
		{
			$this->special_login_form($error);
			return;
		}

		#---Account Check------------------------------------------------------
		//$account_user_id = Account_U_ser::get_id_by_username($username);
		$dm_account_user = new DM_Account_User();
		$dm_account_user->where('username',$username)->get();

        $account_inactive=false;
		if ($dm_account_user->exists())
		{
            $dm_account=new DM_Account();
            $dm_account->where('id',$dm_account_user->customer_id);
            $dm_account->get();
            if ($dm_account->deleted_flag) $account_inactive=true;
		}

		if ($this->account_user_id == $dm_account_user->id)
			$error = "E_SPECIAL_LOGIN_CANNOT_AS_YOURSELF";
		elseif (!$dm_account_user->id)
		    $error = "E_SPECIAL_LOGIN_USERNAME_INVALID";
		elseif (!$this->dm_account_user->is_password_valid($your_password))
			$error = "E_SPECIAL_LOGIN_PASSWORD_INVALID";
		elseif (!$dm_account_user->active_flag)
			$error = "E_SPECIAL_LOGIN_ACCOUT_USER_INACTIVE";
        elseif ($account_inactive)
			$error = "E_SPECIAL_LOGIN_ACCOUT_INACTIVE";

        #---Should also verify whether account and accout user stataus---------

		if ($error)
		{
			$this->special_login_form($error);
			return;
		}

        $session_data['user_id']=$dm_account_user->id;
        $session_data['special_login_id'] = $this->account_user_id;
	//error_log($this->account_user_id);
        $this->session->set_userdata($session_data);

        $this->save_login_activity();

        redirect('/home/welcome');
	}

	function special_logoff()
	{
		$this->save_logout_activity();
        $session_data['special_login_id'] = '';
        $session_data['user_id']=$this->session->userdata('special_login_id');
        $this->session->set_userdata($session_data);

        redirect('/home/welcome');
	}

    private function save_login_activity()
    {
       $special_login_id=$this->session->userdata('special_login_id');
       if ($special_login_id)
       {
          $user_id=$special_login_id;
          $as_login_user_id=$this->session->userdata('user_id');
          $user_id_for_customer=$as_login_user_id;
          $parent_login_activity_id=$this->session->userdata('login_activity_id');
       }
       else
       {
          $user_id=$this->session->userdata('user_id');
          $as_login_user_id=null;
          $user_id_for_customer=$user_id;
          $parent_login_activity_id=null;
       }

       if (!$user_id) die('---Not Possible (Authentication:No User ID)---');

       $dm_account_user=new DM_Account_User();
       $dm_account_user->where('id',$user_id_for_customer);
       $dm_account_user->get();

       if (!$dm_account_user->exists()) die('---Not Possible (Authentication:User ID Not In DB)---');

       $dm_login_activity=new DM_Login_Activity();
       $dm_login_activity->user_id=$user_id;
       $dm_login_activity->as_login_user_id=$as_login_user_id;
       $dm_login_activity->customer_id=$dm_account_user->customer_id;
       $dm_login_activity->parent_login_activity_id=$parent_login_activity_id;
       $dm_login_activity->login_datetime=date('Y-m-d H:i:s');
       $dm_login_activity->save();

       if ($special_login_id)
          $session_data['special_login_activity_id']=$dm_login_activity->id;
       else
          $session_data['login_activity_id']=$dm_login_activity->id;
       $this->session->set_userdata($session_data);
    }

    private function save_logout_activity()
    {
        $special_login_id=$this->session->userdata('special_login_id');
    	if ($special_login_id)
    	{
           $login_activity_id=$this->session->userdata('special_login_activity_id');
           $user_id=$this->session->userdata('special_login_id');
    	}
    	else
    	{
           $login_activity_id=$this->session->userdata('login_activity_id');
           $user_id=$this->user_id;
    	}

        if ($login_activity_id)
        {
            $dm_login_activity=new DM_Login_Activity();
            $dm_login_activity->where('id',$login_activity_id);
            $dm_login_activity->where('user_id',$user_id);
            $dm_login_activity->get();

            if ($dm_login_activity->exists())
            {
               $dm_login_activity->logout_datetime=date('Y-m-d H:i:s');
               $dm_login_activity->save();
            }
        }
    }


	function activate($username='',$passcode='')
	{
		if ($username=='' or $passcode=='')
		{
			redirect('authentication_controller/login/E_FORGOT_PASSWORD_ACTIVATION_ERROR');
			return;
		}

		$dm_account_user = new DM_Account_User();
		$dm_account_user->where('username',$username);
		$dm_account_user->where('passcode',$passcode);
		$dm_account_user->get();

		if (!$dm_account_user->id)
		{
			redirect('authentication_controller/login/E_FORGOT_PASSWORD_ACTIVATION_ERROR');
			return;
		}

		if ($dm_account_user->passcode_activated_datetime)
		{
			redirect('authentication_controller/login/E_FORGOT_PASSWORD_ALAREADY_ACTIVATED');
			return;

		}
		$dm_account_user->password = $dm_account_user->new_password;
		$dm_account_user->passcode_activated_datetime = date('Y-m-d H:i:s');
		$dm_account_user->save();
		redirect('authentication_controller/login/MESSAGE_FORGOT_PASSWORD_ACTIVATED_SUCCESS');
	}

	function forgot_password_form($error='')
	{
        $data['data']=$_POST;
        $data['form'] = $form = FormFactory::get_instance()->create('kompis','forgot_password_form')->open($_POST,$error);
        $this->load->view('home',$data);
	}

	function forgot_password()
	{
		#---Retrive user input-------------------------------------------------
		$username = $this->input->post('username');

		#---Simple Validatin---------------------------------------------------
		if ($username=='')
		{
			$this->forgot_password_form('E_FORGOT_PASSWORD_REQUIRED_FIELDS');
			return;
		}

		#---Attempt to retrive Account User------------------------------------
		$dm_account_user = new DM_Account_User();
		$dm_account_user->where('username',$username);
		$dm_account_user->get();

		#---Validate if account user exist-------------------------------------
		if (!$dm_account_user->id)
		{
			$this->forgot_password_form('E_FORGOT_PASSWORD_INVALID_INPUT');
			return;
		}

		#---Is Account Still active--------------------------------------------
		if (!$dm_account_user->active_flag)
		{
			$this->forgot_password_form('E_ACCOUNT_USER_INACTIVE');
			return;
		}

		#---Master Account ID--------------------------------------------------
		$dm_account=new DM_Account();
		$dm_account->where('id',$dm_account_user->customer_id);
		$dm_account->get();

		#---Generate Password--------------------------------------------------
		$base_url = base_url();
		$passcode = md5($dm_account_user->username . date('Y-m-d H:i:s'));

		$new_pass='';
		for ($i=0; $i<6;$i++) $new_pass.=chr(rand(65,90));
		for ($i=0; $i<6;$i++) $new_pass.=chr(rand(48,57));
		$change_date = date('Y-m-d H:i:s');
		$dm_account_user->new_password = md5($new_pass);
		$dm_account_user->passcode = $passcode;
		$dm_account_user->passcode_generated_datetime = $change_date;
		$dm_account_user->passcode_activated_datetime = null;
		$dm_account_user->save();

		#---Draft simple content/send email------------------------------
		$name = "$dm_account_user->first_name $dm_account_user->last_name";
		if ($name==' ') $name = 'User';


		$subject = "Request change of PW.";
		$message = "Dear $name,\n\n".
		           "We have received a request to change your account PW on $change_date.\n\n".
		           "Please click on the below link to activate your request :\n" .
		           "{$base_url}index.php/authentication_controller/activate/$username/$passcode\n\n" .
		           "Your new pw is $new_pass.\n\n" .
		           "Once you login, please change your password by editing your profile.\n\n" .
		           "Best Regards,\n" .
		           "InCent Ptd Ltd.";
		$headers 	= "From: incent<admin@incent.com.sg>\n";

        if ($dm_account->master_account_id==73)
        {
		   $message = "Hei $name,\n\n" .
		           "Vi har mottatt henvendelse om glemt passord for din konto på Mobilskole den $change_date\n\n" .
		           "Vennligst klikk på lenken nedenfor for å endre passord:\n" .
		           "{$base_url}index.php/authentication_controller/activate/$username/$passcode\n\n" .
		           "Ditt nye passord er $new_pass.\n\n" .
		           "Når du har logget inn, kan du endre passord under Konto/Endre Passord.\n\n" .
		           "Mvh\n" .
                   "Mobilskole AS.\n" .
                   "E-post: info@mobilskole.no\n" .
                   "Telefon: 22 41 82 20\n";
           $subject="Mobilskole: glemt passord";
           //$message=utf8_decode(utf8_encode($message));
     	   $headers="From: Mobilskole<info@mobilskole.no>\r\n";
        }

   	    $headers .= 'MIME-Version: 1.0' . "\r\n" . 'Content-type: text/plain; charset=UTF-8' . "\r\n";
		# $to = "$dm_account_user->email, alan@incent.com.sg, alanwongmk@gmail.com, alanwongmk@live.com.sg ";
		$to = $dm_account_user->email;
		mail($to, $subject, $message,$headers);
		redirect('authentication_controller/login/MESSAGE_FORGOT_PASSWORD_SENT_EMAIL');
	}
}
