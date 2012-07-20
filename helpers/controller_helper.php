<?php

function controller_helper()
{
	$CI = &get_instance();
	$CI->load->library('session');
	$CI->load->helpers('url');
	$CI->load->helpers('member_listing');
	$CI->load->helpers('tag_list');
	$CI->load->helper('language');
	$CI->load->helpers('switch_language');
	$CI->load->helpers('security_control');
	$CI->load->helpers('validate_account');
	$CI->load->helpers('search_filter');
	$CI->load->helpers('pagination');
	$CI->load->helpers('installation');
	$CI->load->helpers('show_message');

	$CI->load->library('validation');
	$CI->load->library('SecurityAlgorithmModel');
	$CI->load->library('SecurityReceiverModel');

	$CI->load->helpers('development');
	$CI->load->helpers('common');


	$CI->load->database();

   	#---Register---------------------------------------------------------------
	$CI->register = Register::get_instance();
	$CI->message_bundle = MessageBundleManager::get_instance();
	$CI->formfactory   = FormFactory::get_instance();
	$CI->register->set_property('message_bundle',$CI->message_bundle);
	$CI->register->set_property('validation',$CI->validation);
	$CI->register->set_property('db',$CI->db);

	
    $CI->load->library('DatabaseSelectModel');
 	$CI->load->library('DatabaseRecords',"*");
    $CI->load->library('Users',"*"); #---Required By Search
	#---> take noete $CI->load->library('Tags',"*");
    $CI->load->library('MessageDetails','*');
	//$CI->load->library('Keywords');

	unset($CI->message);
    unset($CI->User);
    unset($CI->Account);
    unset($CI->User_tag_relationships);
    unset($CI->Tag);

	#---Register some info---------------------------------------------
	$CI->base_url    = base_url();
	//    $this->entity_tags = new T_ag();

	$CI->register->set_property('startup_dir', getcwd());
	$CI->register->set_property('base_url',$CI->base_url);
	//	$this->register->set_property('config',$this->config);


	#---Security-----------------------------------------------------------
	#---Login and Account ID-----------------------------------------------
    $CI->user_id = $CI->session->userdata('user_id');
    $CI->menu_id = $CI->session->userdata('menu_id');

    switch_language();
	$CI->lang->load('installation');
	$CI->lang->load('kompis');

	#---Important----------------------------------------------------------
	if ($CI->user_id)
	{
	   
	   $dm_account_user = new DM_Account_User();
	   $dm_account_user->where('id',$CI->user_id)->get();

	   $dm_account = new DM_Account();
	   $dm_account->where('id',$dm_account_user->customer_id)->get();

	   $CI->customer_id = $dm_account_user->customer_id;
	   $CI->account_user_id = $CI->user_id;
	   $CI->dm_account_user = $dm_account_user;
	   $CI->dm_account = $dm_account;

       $CI->register->set_property('user_id',$CI->user_id);
       //$CI->register->set_property('account',$account);
       //$CI->register->set_property('account_user',$account_user);
       $CI->register->set_property('language',$CI->language);
	}

    $CI->output->set_header('Content-Type: text/html; charset=utf-8');
}

?>