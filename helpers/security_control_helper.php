<?php

/*
 +------------------------------------------------------------------------+
 | Control Access                                                         |
 +------------------------------------------------------------------------+
*/
function security_control($security_check=true)
{
	$CI = &get_instance();
	$security_data = new StdClass();
	$security_data->post_data   = $_POST;

	
	
	
	//-- cookie detecting --
	//session_start();
	//$_SESSION['cas'] = time();
	

	//$prem = SID;

	  if (!(count($_COOKIE) > 0) )
      {
      	redirect('/serviceloginauth');
      }
	
	

	$user_id = $CI->session->userdata('user_id');
	$somemenuid=  $CI->session->userdata('menu_id');
	
	
	
	#error_log($somemenuid);
	
	#error_log("Menu ID " .  $CI->session->userdata('menu_name'));
	
	
	
	

    $type = $CI->uri->segment(1) . '.' . $CI->uri->segment(2);
    $security_data->segment3 = $CI->uri->segment(3) ? $CI->uri->segment(3) : null;
    $security_data->segment4 = $CI->uri->segment(4) ? $CI->uri->segment(4) : null;
    $security_data->segment5 = $CI->uri->segment(5) ? $CI->uri->segment(5) : null;

	#---------------------------------------------------------------------+
	# Handle valid login case, welcome page and error page                |
	#---------------------------------------------------------------------+

	if ($type=='kompis.test') return;
	if ($type=='kompis.test1') return;
	if ($type=='home.topmenu_control') return;

	//$control_action = $CI->uri->segment(2);
	$control_action = $CI->router->method;

	if ($CI->user_id)
	{
		#---Verify Account User------------------------------------------------
		$dm_account_user = new DM_Account_User();
		$dm_account_user->where('id',$user_id)->get();
		$security_data->customer_id = $dm_account_user->customer_id;
		
		$is_internal_special_user=$dm_account_user->is_internal_special_account(); // special user flag 
		$is_superuser=$dm_account_user->superuser_flag; //super user flag 
		

		#---Verify Account-----------------------------------------------------
		$dm_account = new DM_Account();
		$dm_account->where('id',$dm_account_user->customer_id)->get();

		if ($type == "kompis." or $type == '.')
		    $case = 1;
		elseif (!method_exists($CI,$control_action))
			$case = 2;
		elseif (!$dm_account_user->active_flag)
		    $case = 4;
		elseif ($dm_account->deleted_flag)
		    $case = 5;
		else
		    $case = 999;
	}
	else
	{
		if ($type == "kompis." or $type == '.')
		    $case = 3;
		elseif (!method_exists($CI,$control_action))
			$case = 3;
		else
		    $case = 999;
	}

	#---Early Exit Case----------------------------------------------------
	
	switch ($case)
	{
		
		case 1:
		  redirect('/home/welcome');
		  break;
		case 2:
		  redirect("/kompis/error_page/$case:$type:method");
		  break;
		case 3:
		  redirect('/authentication_controller/login');
		  break;
		case 4:
          $session_data['user_id']     ='';
          $session_data['menubar']     ='';
          $session_data['tag_page_no'] = '';
          $session_data['menu_id']     = '';
          $session_data['special_login_id']='';
          $CI->session->set_userdata($session_data);
          $CI->session->sess_destroy();
		  redirect('/authentication_controller/logout/E_ACCOUNT_USER_INACTIVE');
		  break;
		case 5:
          $session_data['user_id']     ='';
          $session_data['menubar']     ='';
          $session_data['tag_page_no'] = '';
          $session_data['menu_id']     = '';
          $session_data['special_login_id']='';
          $CI->session->set_userdata($session_data);
          $CI->session->sess_destroy();
		  redirect('/authentication_controller/logout/E_INVALID_ACCOUNT');
		  break;
	}

    if (!$security_check) return true;;

    $security_factory = SecurityReceiverFactory::get_instance();
    $security_invoker = $security_factory->create($type,$security_data);
   

    if (!$security_invoker)
    {
		redirect("/kompis/error_page/1:$type:secure");
    }

	//echo $type; die;

	if (!$security_invoker->execute())
	{
        $error = $session_data['security_error']= $security_invoker->get_error();
	
	
		redirect("/authentication_controller/logout/$error");
		#redirect('/serviceinvalidauth');
	}
	
	#---secruriy hole prevention---
	
	#error_log("User id ".  $user_id);
	#error_log($CI->uri->segment(1) );
	#error_log( $CI->uri->segment(2));
	
	
	
	
	#$query = $CI->db->query("select * from tbl_menu_users where menu_ID=".$submenu['ID']." and account_user_ID=".$userid . " and flag =1" );
	
	
	$submenus = $CI->db->query("SELECT * FROM menus WHERE href ='".  $CI->uri->segment(1) ."/". $CI->uri->segment(2).  "'" );
	
	  foreach($submenus->result_array() as $submenu){
		
		
		
		$users= $submenu['enable_users']==1?true:false; //$submenu->users;
		
		
		if($users){
			
			$query = $CI->db->query("select * from tbl_menu_users where menu_ID=".$submenu['ID']." and account_user_ID=".$user_id . " and flag =1" );
			if ($query->num_rows() > 0){
				$usrrow = $query->row();
				$found_username = $usrrow->account_user_ID;
			}
			else{
				#error_log(1);
				redirect('/serviceinvalidauth');
			}	
		}
		$internal_user_only=$submenu['internal_user_only']==1?true:false;//(string)$submenu['internal_user_only'];
		$superuser_only= $submenu['superuser_only']==1?true:false;//(string)$submenu['superuser_only'];
		$for_special_login=$submenu['for_special_login']==1?true:false;
		//if ((string)$submenu['for_special_login'] and $CI->session->userdata('special_login_id'))
		if($for_special_login and $CI->session->userdata('special_login_id'))
		{}
		elseif ($internal_user_only and !$is_internal_special_user){
			
			
			redirect('/serviceinvalidauth');
		}
		if ($superuser_only and !$is_superuser) {
			
			
			redirect('/serviceinvalidauth');
		}
		break;
		
	  }
	
	
	
	
	
	
	
}





?>