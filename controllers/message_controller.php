<?php
//require_once(APPPATH ."helpers/common_helper.php");

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
//require_once(APPPATH. "libraries/FTP_Connection.php");

class message_controller extends CI_Controller {

    public $language=null;
    public $tempmsgstr='';
  
	function __construct()
	{
		parent::__construct();
		
				
		$this->load->helpers('controller');
		$this->load->library('email');
		
		controller_helper();

		if (!isset($this->dm_account_user->id))
		{
			if ($this->uri->segment(2)=='send_userlist' and $this->uri->segment(3)=='SEND')
			{
				$redirect_page = $this->uri->uri_string();
				$this->session->set_userdata('__redirect_page__',$redirect_page);
			}
		}
		switch_language();
		security_control();

	}

	function message_send_simple($param1="")
	{
		#---Update session data------------------------------------------------
        $session_data['menu_id']='M_MESSAGE';
        $this->session->set_userdata($session_data);

		#---Should be the same balance, but just get the latest possible-------
		$dm_account = new DM_Account();
		$dm_account->where('id',$this->dm_account->id)->get();

		#---SMS Balance Check, only apply to non-post-paid customer------------
		if (!$dm_account->post_paid_account)
		{
			if ($dm_account->sms_balance<=0)
			{
			     show_message('','MESSAGE_MESSAGE_SEND_NO_BALANCE');
			     return;
			}
		}

		$segment2 =  $this->uri->segment(2);
		if ($segment2=='message_send_simple')
		{
			if ($this->message_bundle->is_message($param1))
			{
				$errors = $param1;
			}
            elseif (substr($param1,0,6)=='ERROR_')
    		{
    			$url="{$this->base_url}index.php/message_controller/list_sent_messages";
    			$href_list_sent_messages="<a href='$url'><b>". $this->message_bundle->get_instance()->get_lang_value('threading_here') . "</b></a>";
    			$errors = $this->message_bundle->get_instance()->get_lang_value($param1);
			//error_log($param1);
    			$errors .= ". ". $this->message_bundle->get_instance()->get_lang_value('threading_please') . " ". $this->message_bundle->get_instance()->get_lang_value('threading_click'). " ".  $href_list_sent_messages . " " . $this->message_bundle->get_instance()->get_lang_value('threading_to_see') . " ". $this->message_bundle->get_instance()->get_lang_value('details') .".";
    		}
			else
			{
				#---Careful, this is for tag id
				$errors = '';
				$tag_id = $param1;
				if ($param1) $_POST['including_tags'][0] = $tag_id;
			}
		}
		else
		{
			$errors=$param1;
		}

        #---Get Available Groups-----------------------------------------------
        $tags=DM_Tag::get_instance()->get_tags_with_count();
        $selection = format_select_options($tags,'id','tag_name');

        #---Case that we create blank selection--------------------------------  this creating blank line which does not need anymore in multi selection 
      //  if (count($tags)>30) array_unshift($selection,array('value'=>'','text'=>''));

        #---Create Form Data---------------------------------------------------
        $form_data=$_POST;
        $form_data['including_tags[]']=$selection;
        $form_data['message_send_type']='message_send_simple';

        $data['form'] = FormFactory::get_instance()->create(FormFactory::MESSAGE_SEND_SIMPLE)->create($form_data,$errors);
        $this->load->view('home',$data);
    }

    function message_input_basic($errors="",$user_id='')
    {
	    #---Should be the same balance, but just get the latest possible-------
		$dm_account = new DM_Account();
		$dm_account->where('id',$this->dm_account->id)->get();

		#---SMS Balance Check, only apply to non-post-paid customer------------
		if (!$dm_account->post_paid_account)
		{
			if ($dm_account->sms_balance<=0)
			{
			     show_message('','MESSAGE_MESSAGE_SEND_NO_BALANCE');
			     return;
			}
		}

		$ftp_connection = new FTP_Connection($this->customer_id);
		if (!$ftp_connection->is_connectable())
		{
			$error = $ftp_connection->get_error();
			show_message('',$error);
		    return;
		}

		$including_phone_list='';
		if ($errors=='SEND_ONE')
		{
			$dm_user = new DM_User();
			$dm_user->where('customer_id',$this->customer_id);
			$dm_user->where('id',$user_id);
			$dm_user->get();
			$including_phone_list = $dm_user->phone;
			$errors='';
		}

    	if (substr($errors,0,6)=='ERROR_')
    	{
			$url="{$this->base_url}index.php/message_controller/list_sent_messages";
			$href_list_sent_messages="<a href='$url'><b>here</b></a>";
			$errors = $this->message_bundle->get_instance()->get_lang_value($errors);
			$errors .= ". Please click $href_list_sent_messages to see detail.";

    		//$errors = $this->message_bundle->get_instance()->get_lang_value($errors);
    	}

		#---Fake, just to by-pass----------------------------------------------
		if ($errors=='SEND_MESSAGE') $errors='';

   	    $tags = DM_Tag::get_instance()->get_tags_with_count();
		$assigned_tags = format_select_options($tags,'id','tag_name');

		if ($including_phone_list=='')
		  $including_phone_list = $this->input->post('including_phone_list');

        $sender_name=empty($_POST) ? $dm_account->sender : $this->input->post('sender_name');

   	    $form_data = $_POST;
   	    $form_data['sender_name']=$sender_name;
   	    $form_data['including_phone_list']=$including_phone_list;
		$form_data['including_tags[]']=$assigned_tags;
		$form_data['excluding_tags[]']=$assigned_tags;
        $form_data['progress_info'] = $this->get_message_input_progress_info(0); # . "</div>";


        //$data['form'] = FormFactory::get_instance()->create(FormFactory::MESSAGE_INPUT_BASIC)->create($form_data,$errors);
        $form_name=$this->dm_account_user->superuser_flag ? 'message_input_basic' : 'message_input_basic_for_user';
        $data['form'] = FormFactory::get_instance()->create('kompis',$form_name)->open($form_data,$errors);
        $this->load->view('home',$data);
    }

   function message_input_sms($xerrors="")
   {
      $sender_name=$this->input->post('sender_name');

      $errors='';
      if ($sender_name=='') $errors= $this->message_bundle->get_instance()->get_lang_value('err_sender_name_req') . "."; //'Sender name required.';
      elseif (strlen($sender_name)<3) $errors=$this->message_bundle->get_instance()->get_lang_value('err_threechar_sender') . "."; //'At least 3 characters required for Send Name.';
      elseif (strlen($sender_name)>11) $errors=$this->message_bundle->get_instance()->get_lang_value('err_maxelevenchar_sender') . "."; //'Maximum 11 characters are allowed for Send Name.';

      if ($errors)
      {
         $_POST['FORM_NAME']='advance_search_members_form';
         $this->message_input_basic($errors);
         return;
      }

    	global $system_folder;
     	require_once(APPPATH. "libraries/PhoneNumberValidator.php");
    	if (empty($_POST)) redirect("/message_controller/message_input_basic");

		$ftp_connection = new FTP_Connection($this->customer_id);
		if (!$ftp_connection->is_connectable())
		{
			$error = $ftp_connection->get_error();
			show_message('',$error);
		    return;
		}

        $including_tags = $this->input->post('including_tags');
        $tag_name = $this->input->post('tag_name');
        $limit    = $this->input->post('limit');
		$excluding_phone_list = $this->input->post('excluding_phone_list');
		$including_phone_list = $this->input->post('including_phone_list');
		$sender_name=$this->input->post('sender_name');

		$empty_tag = $including_tags ? false : true;

		$validate_phone_list = "\n" . $including_phone_list;
		$validate_phone_list = explode("\n",$validate_phone_list);
		$validate_phone_list = array_unique($validate_phone_list);
		unset($validate_phone_list[0]);
		$validate_phone_list = array_values($validate_phone_list);

		#$empty_including_phone_list = $including_phone_list ? false : true;
		$empty_including_phone_list = empty($validate_phone_list) ? true : false;

		$_POST['including_phone_list'] = implode("\n",$validate_phone_list);
		$_POST['remark'] = $this->input->post('internal_remark');
        $_POST['progress_info'] = $this->get_message_input_progress_info(1);

		$errors = '';
		if ($validate_phone_list and !empty($validate_phone_list))
		{
			$validator = PhoneNumberValidator::get_instance();
			$validator->set_phone_list($validate_phone_list);
			$response = $validator->execute();
			$errors = $response->errors;
			$_POST['including_phone_list'] = implode("\n",$response->valid_phones);
		}


		if ($xerrors=="")
		{
			if (sizeof($including_tags)==1 and $including_tags[0]=='')
				$empty_tag = true;

			if ($empty_tag and $empty_including_phone_list)
				$errors .= $this->message_bundle->get_lang_value('E_BOTH_TAG_AND_PHONES_EMPTY') . "\n";

			if ($limit<>'' and !is_numeric($limit))
				$errors .= $this->message_bundle->get_lang_value('E_LIMIT_NOT_NUMERIC') . "\n";

			if (strlen($tag_name)>30)
				$errors .= $this->message_bundle->get_lang_value('E_TAG_NAME_TOO_LONG') . "\n";
		}

		if ($errors)
		{
			$_POST['FORM_NAME']='advance_search_members_form';
			$this->message_input_basic($errors);
		}
		else
		{

	        $data['form'] = FormFactory::get_instance()->create(FormFactory::MESSAGE_INPUT_SMS)->create($_POST,$xerrors);
	        $this->load->view('home',$data);
		}
    }

   function message_confirmation()
   {
    	global $system_folder;
		require_once(APPPATH. "libraries/MessageManager.php");
    	require_once(APPPATH. "libraries/PhoneNumberValidator.php");

     	$message_send_type = $this->input->post('message_send_type');
		$message = $this->input->post('message');
		
	$tempmsgstr ='';

        #---Length Check-------------------------------------------------------
        $msg_too_long = strlen($message)>580 ? true : false;

	    $illegal_chars = preg_replace("/[\\\[\] A-Za-z0-9@#£\$¥äöèéùìòÇØøÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ!#¤%&()*+,-.\/\"\'\r\n\f:;<=>?\{\}ÄÖÑÜ§¿€~^]/","",$message);

		if ($illegal_chars or $msg_too_long)
		{
			$errors='';
			if ($illegal_chars) $errors .= "Following illegal characters \"$illegal_chars\" detected in your message.\n";
            if ($msg_too_long)  $errors .= "Message provided too long.";
            $_POST['message']=substr($message,0,580);

			if ($message_send_type=='message_send_simple')
			{
				$_POST['FORM_NAME']='message_send_simple';
				$this->message_send_simple($errors);
			}
			else
			{
				$_POST['FORM_NAME']='advance_search_members_form';
				$this->message_input_sms($errors);
			}
			return;
		}

		$ftp_connection = new FTP_Connection($this->customer_id);
		if (!$ftp_connection->is_connectable())
		{
			$error = $ftp_connection->get_error();
			show_message('',$error);
		    return;
		}


		if ($this->input->post('message_send_type')=='message_send_simple2')
		{
	        $validate_phone_list = $this->input->post('including_phone_list');
		

			if (!$validate_phone_list)
			{
				$this->send_userlist("No phone was seleced for sending message.");
				return;
			}

			$validator = PhoneNumberValidator::get_instance();
			$validator->set_phone_list($validate_phone_list);
			$response = $validator->execute();
			$errors = $response->errors;
			$_POST['including_phone_list'] = implode("\n",$response->valid_phones);

			if ($errors)
			{
				$this->send_userlist($errors);
				return;
			}

		}

		if ($this->input->post('message_send_type')=='message_send_simple')
		{
			$_POST['tag_name']='';
			$_POST['limit_message']='';
			$_POST['remark']='internal_remark';
			$_POST['including_phone_list']='';
			$_POST['excluding_phone_list']='';
		}

		if ($message_send_type=='message_send_simple')
		{
			$errors="";
			$including_tags = $this->input->post("including_tags");
			$message = $this->input->post('message');

			if ($including_tags[0]=='')	$errors = $this->message_bundle->get_lang_value('err_sms_no_tag_select').".\n" ; // "No tag was selected for sending message.\n"; //
			else if ($including_tags=="") $errors .= $this->message_bundle->get_lang_value('err_sms_no_tag_select') .".\n";
			if ($message=="") 			$errors .= $this->message_bundle->get_lang_value('E_SMS_MESSAGE_EMPTY') ."\n"; //SMS message is empty

			if ($errors)
			{
				$_POST['FORM_NAME']='message_send_simple';
				$this->message_send_simple($errors);
				return;
			}
		}

    	$message_manager = new MessageManager($_POST);
    	$reject_counts = $message_manager->get_count();
		$actual_reject_text="";

		$rejected_texts['E_PHONE_NOT_NUMERIC']=" ". $this->message_bundle->get_lang_value('thread_err_sendsms_reject'). " ". $this->message_bundle->get_lang_value('ERROR_PHONE_INVALID_NON_NUMERIC') ."."; //' are rejected for not numerical phone number.';
		$rejected_texts['E_PHONE_INVALID_COUNTRY']= " ". $this->message_bundle->get_lang_value('thread_err_sendsms_reject'). " ". $this->message_bundle->get_lang_value('ERROR_PHONE_INVALID_COUNTRY')."."; //' are rejected for valid country code:q.';
		$rejected_texts['E_PHONE_NULL'] =" ". $this->message_bundle->get_lang_value('thread_err_sendsms_reject'). " ". $this->message_bundle->get_lang_value('ERROR_PHONE_EMPTY')."."; // ' are rejected for "empty/blank" phone number.';
		$rejected_texts['E_PHONE_TOO_LONG'] = " ". $this->message_bundle->get_lang_value('thread_err_sendsms_reject'). " ". $this->message_bundle->get_lang_value('ERROR_PHONE_INVALID_TOO_LONG').".";//' are rejected for phone number too long.';
		$rejected_texts['E_PHONE_TOO_SHORT'] =" ". $this->message_bundle->get_lang_value('thread_err_sendsms_reject'). " ". $this->message_bundle->get_lang_value('ERROR_PHONE_INVALID_TOO_SHORT').".";// ' are rejected for phone number too short.';
		$rejected_texts['E_PHONE_INVALID_DIGIT'] =" ". $this->message_bundle->get_lang_value('thread_err_sendsms_reject'). " ". $this->message_bundle->get_lang_value('ERROR_PHONE_INVALID_DIGIT').".";// ' are rejected for invalid digit.';
		$rejected_texts['exclude-tag']=" ". $this->message_bundle->get_lang_value('thread_err_sendsms_reject_2'). " ". $this->message_bundle->get_lang_value('thread_excluding_groups').".";//" are rejected from excluding groups.";
		$rejected_texts['dup-tag']=" ". $this->message_bundle->get_lang_value('thread_err_sendsms_reject'). " ". $this->message_bundle->get_lang_value('err_sendsms_duplicating_gr.').".";//" are rejected for duplicating in groups.";
		$rejected_texts['exclude']=" ". $this->message_bundle->get_lang_value('thread_err_sendsms_reject'). " ". $this->message_bundle->get_lang_value('err_sendsms_excluding_phone_list').".";//" are rejected for excluding phone list.";
		$rejected_texts['blocked']=" ". $this->message_bundle->get_lang_value('thread_err_sendsms_reject'). " ". $this->message_bundle->get_lang_value('thread_blocked_list').".";//" are rejected for blocked list.";

		foreach ($reject_counts as $key=>$reject_count)
		{
			if ($key=='total') continue;
			$text = $rejected_texts[$key];
			$actual_reject_text .= "<b>$reject_count</b> $text<br>";
		}

		if ($reject_counts['total']==0)
		   $total_reject=0;
		else
		   $total_reject='';

    	$including_tags = $this->input->post('including_tags');
    	$tag_name       = $this->input->post('tag_name');

		$errors = '';
	    if (!$this->input->post('message'))
			$errors .= $this->message_bundle->get_lang_value('E_SMS_MESSAGE_EMPTY') . "\n";

		if ($errors)
		{
			if ($this->input->post('callback'))
			{
				$callback=$this->input->post('callback');
				$this->$callback($errors);
				return;
			}
			$_POST['FORM_NAME']='advance_search_members_form';
			$this->message_input_sms($errors);
			return;
		}

		$o = new StdClass();
		$o->tag_ids = $including_tags;
		$dm_tag = DM_Tag::get_instance()->get_tags_with_count($o);

		$including_tags_counts = array();

		$including_tags_count = 0;
		$including_tag_names = '';
		$connector = '';

		foreach ($dm_tag as $key=>$tag)
		{
			if ($key > 0 ) 	$connector = ($key==(count($dm_tag) - 1)) ? ' and ' : ',';
			$including_tag_names .= "{$connector}$tag->tag_name";
			$including_tags_count += $tag->count;
		}

		#---Excluding Tags-----------------------------------------------------
		$excluding_tags_counts = array();
    	$excluding_tags = $this->input->post('excluding_tags');

		$o = new StdClass();
		$o->tag_ids = $excluding_tags;
		$dm_tag = DM_Tag::get_instance()->get_tags_with_count($o);

		//$result = $tags->get_result();
		$excluding_tag_names = '';
		$excluding_tags_count = 0;
		$connector = '';
		foreach ($dm_tag as $key=>$tag)
		{
			if ($key > 0 ) $connector = ($key==(count($dm_tag)-1)) ? ' and ' : ',';
			$excluding_tag_names .= "{$connector}$tag->tag_name";
			$excluding_tags_count += $tag->count;
		}

		if (!isset($count)) $counts=0;

		$excluding_phone_list = $this->input->post('excluding_phone_list');
		$including_phone_list = $this->input->post('including_phone_list');
		$message_send_type    = $this->input->post('message_send_type');

			if (isset($_POST['internal_remark'])) $_POST['remark'] = $_POST['internal_remark'];
    	$_POST['including_tags_count'] = $including_tags_counts;
	   	$_POST['excluding_tags_count'] = $excluding_tags_counts;
        $progress_info = $this->get_message_input_progress_info(2);

		if ($including_phone_list<>'')
			$including_list_count = count(explode("\n",$including_phone_list));
		else
			$including_list_count = 0;

		if ($excluding_phone_list<>'')
			$excluding_list_count = count(explode("\n",$excluding_phone_list));
		else
			$excluding_list_count = 0;

	   	$total = $including_tags_count + $including_list_count - $reject_counts['total'];

		$note = '';
		$balance = $this->dm_account->sms_balance;
		$error='';

        /*
         +--------------------------------------------------------------------+
         | Confirmation page                                                  |
         +--------------------------------------------------------------------+
        */
        $tk[]='message_confirm_show_recipients';
        $tk[]='message_confirm_show_actual_reject_count';
        $tk[]='message_confirm_show_your_message';
        $tk[]='message_confirm_show_include_from_group_count';
        $t=Register::get_instance()->get_property('message_bundle')->get_lang_values($tk);
		if ($this->dm_account->post_paid_account)
		{
			$note ="<p class='message_confirm' style='text-align:left;padding-left:0;'>" .
					"Note : ". $this->message_bundle->get_lang_value('thread_post_paid_account') ."<br><br>" . //This is a post paid account
					sprintf($this->message_bundle->get_lang_value('msg_confirm'),"<b>{$total}</b>")  ."</p>\n"; //Are you sure you want to send this message to
		}
		elseif ($total > $balance) //or $balance<$this->dm_account->sms_low_warning_level
		{
			$note = "<p class='message_confirm' style='text-align:left;padding-left:0; font-style:italic;'>
					<b>Note : </b><br/>" .
					$this->message_bundle->get_lang_value('sms_credit_low') . " (". $this->message_bundle->get_lang_value('thread_current_balance') .": <b>{$balance}</b>).<br/>". 
					$this->message_bundle->get_lang_value('err_unable_process_req') . "</b> 
					</p>
					<p class='message_confirm' style='text-align:left;padding-left:0;'>".
					sprintf($this->message_bundle->get_lang_value('thread_send_no_recep'),"<b>{$total}</b>") ; 
			$error=$this->message_bundle->get_lang_value('err_unable_process_req');

			$form_instruction=new StdClass();
			$form_instruction->SUBMIT_BUTTON=false;
			$form_instruction->CLEAR_BUTTON=false;
			$form_instruction->BACK_BUTTON =true;
			$_POST['__form_special_instruction__']=$form_instruction;
		}
		else
		{
		   $text=sprintf($t->message_confirm_show_recipients,"<b>$total</b>");
           $note="<p class='message_confirm' style='text-align:left;padding-left:0;'>" .
                 "$text" .
                 "</p>\n";
		}



	    $form_title = $this->message_bundle->get_lang_value('form_title_send_messages_confirmation');
		$tagged_text = "";
		$message = $this->input->post('message');
		$sms_message = str_replace("\n","<br>",$message);
		$sms_message = "<fieldset class='listing_box'>" .
		               "<legend class='listing_legend' style='font-size:100%; font-weight:bold;'>" .
                       "<b>$t->message_confirm_show_your_message</b>" .
                       "</legend>" .
		                $sms_message .
		               "</fieldset>";

		if ($tag_name<>'') $tagged_text = "<br>The <b>$total</b> recipients will be tagged with <b>$tag_name</b>.";

		if ($message_send_type=='message_send_advance')
		{
		    
		    $send_confirm_no_include = $this->message_bundle->get_lang_value('send_confirm_no_include');
		    $send_confirm_no_exclude_group = $this->message_bundle->get_lang_value('send_confirm_no_exclude_group');
		    $send_confirm_no_exclude = $this->message_bundle->get_lang_value('send_confirm_no_exclude');
		    
		    
		    
		    
			$advance_message = "<b>$including_list_count</b> {$send_confirm_no_include}<br>
			<b>{$excluding_tags_count}</b> {$send_confirm_no_exclude_group} {$excluding_tag_names}<br>
			<b>{$excluding_list_count}</b> {$send_confirm_no_exclude}<br>";
		}
		else
		{
			$advance_message='';
		}
		
		

        $replacement_count_text = $this->get_replacement_count_text($total);
        $acutual_reject_count_text=sprintf($t->message_confirm_show_actual_reject_count,"<b>$total_reject</b>");
        $text_included_from_group_count=sprintf($t->message_confirm_show_include_from_group_count,
                                                "<b>$including_tags_count</b>",
                                                "<b>$including_tag_names</b>");
	
	
	
	
	$recipient_names = '';
	require_once(APPPATH. "libraries/MessageManager.php");
	$m = new MessageManager($_POST);
	$all_recipients = $m->get_all_recipients();
	$recipient_names = array();
	
	foreach($all_recipients as $a_recipient) {
		$recipient_names [] = get_display_name(
			$a_recipient->first_name,
			$a_recipient->last_name,
			$a_recipient->phone, ' (%s)');
	}
	
	
	if($this->input->post('message_send_type') == 'message_send_simple2'){
	    $dm_user = new DM_User();
	    $recipient_names =  $dm_user->get_by_phone($response->valid_phones);
	}
	
	
	sort($recipient_names);
	
	
		
		
		$output = "<div class='group'>
				   <h2>$form_title</h2>
				   $progress_info
				   
				   <div class='error'>
				   $note
				  </div>
				   <fieldset>
				   <p class='messsge_confirm' style='text-align:left;padding-left:0;'>$acutual_reject_count_text<br />$actual_reject_text</p>
				    </fieldset>
				   $sms_message
				   <div class='success'>
				   <p class='messsge_confirm' style='text-align:left;padding-left:0;'>$replacement_count_text</p>
				   <p class='message_confirm' style='text-align:left; padding-left:0;'>" .
                  "$text_included_from_group_count<br>
				   $advance_message
				   $tagged_text
				  </p>
				  </div>
				  <fieldset class='listing_box'>
				   		<legend class='listing_legend'>".$this->message_bundle->get_lang_value('thread_all_recipients')."</legend>
						<textarea readonly style='width: 100%; height:80px; border: 0;'>".implode($recipient_names, "\n")."</textarea>
					</fieldset>
				    				
				  </div>";
		
		$tempmsgstr ='';
		$tempmsgstr .= trim($acutual_reject_count_text) . " " .trim($actual_reject_text) .  "\n";
		$tempmsgstr .= trim($replacement_count_text)  .  "\n";
		$tempmsgstr .= trim($text_included_from_group_count)  .  "\n";
		$tempmsgstr .= trim($advance_message)  .  "\n";
		$tempmsgstr .= trim($tagged_text)  .  "\n";
		
		//error_log($tempmsgstr);

		$session_data['tempmsgstr']=$tempmsgstr;
		$this->session->set_userdata($session_data);

		
		$back_url = $this->get_back_url();

		$form_name='message_confirmation';
		$_POST['__back_button_url__'] = $back_url;
		
		
    	$data['form'] = $output . FormFactory::get_instance()->create('kompis',$form_name)->open($_POST,$error);
	
	$this->load->view('home',$data);
    }

    private function get_back_url($status='')
    {
		if ($status)
			$message='MESSAGE_MESSAGE_SENT_SUCCESSFULLY';
		else
			$message='ERROR_MESSAGE_SENT_FAILED';


		switch ($this->input->post('message_send_type'))
		{
			case 'message_send_simple':
			    $back_url = "message_controller/message_send_simple/$message";
			    break;
			case 'message_send_simple2':
			    $session_data['userlistkeyid']='__session_group_user_list__';
			    $this->session->set_userdata($session_data);
			    $back_url = "message_controller/send_userlist/$message";
			    break;
			case 'message_send_advance':
			    $back_url = "message_controller/message_input_basic/$message";
			    break;
			case 'lucky_draw':
			    $message_id=$this->input->post('message_id');
			    $back_url = "lucky_draw/update_sms_status/$message_id";
			    break;

		}

		return $back_url;
    }

	private function get_replacement_count_text($total)
	{
    	$message_manager = new MessageManager($_POST);
		$replacement_keys = $message_manager->get_replacement_keys();

		if (!$replacement_keys)
		{
		    
		     		    
			 $send_confirm_text = $this->message_bundle->get_lang_value('send_confirm_text');
			///$clear_button =  self::$message_bundle->get_lang_value('clear_button');
								
			$len = mb_strlen($this->input->post('message'));
			
			//$replacement_count_text = "Your message is <b>$len</b> characters. There is no replacement text.";
			$replacement_count_text=sprintf($send_confirm_text,"<b>{$len}</b>" );
						
			return $replacement_count_text;
		}

		$control_qty = 1000;
		$ready_to_count = false;
		if ($total<=$control_qty) $ready_to_count = true;
		else $ready_to_count = $this->input->post('request_replacement_count') ? true : false;

		if (!$ready_to_count)
		{
			$tag_name= $this->input->post('tag_name');
			$message= $this->input->post('message');
			$limit_message= $this->input->post('limit_message');
			$including_tags= $this->input->post('including_tags');
			$excluding_tags= $this->input->post('excluding_tags');
			$remark= $this->input->post('remark');
			$including_phone_list= $this->input->post('including_phone_list');
			$excluding_phone_list= $this->input->post('excluding_phone_list');
			$message_send_type= $this->input->post('message_send_type');

			$including_tags = $this->input->post('including_tags');
			$html_including_tags = '';
			foreach ($including_tags as $tag_id)
			{
				$html_including_tags .= "<input type='hidden' name='including_tags[]' value='$tag_id' />";
			}

			$excluding_tags = $this->input->post('excluding_tags');
			$html_excluding_tags = '';
			foreach ($excluding_tags as $tag_id)
			{
				$html_excluding_tags .= "<input type='hidden' name='excluding_tags[]' value='$tag_id' />";
			}

			$confirmation ="return j_confirm(\"This functions might take some time and slow down the system. (Numer of records : $total). Are you sure want to go through with this process ? \")";

			$x = "<form style='padding-top:0;' method='post'>" .
				 "<input type='hidden' name='tag_name' value='$tag_name' />" .
				 "<input type='hidden' name='message' value='$message' />" .
				 "<input type='hidden' name='limit_message' value='$limit_message' />" .
				 "<input type='hidden' name='including_tags' value='$including_tags' />" .
				 "<input type='hidden' name='excluding_tags' value='$excluding_tags' />" .
				 "<input type='hidden' name='including_phone_list' value='$including_phone_list' />" .
				 "<input type='hidden' name='excluding_phone_list' value='$excluding_phone_list' />" .
				 "<input type='hidden' name='remark' value='$remark' />" .
				 "<input type='hidden' name='message_send_type' value='$message_send_type' />" .
				 $html_including_tags .
				 $html_excluding_tags .
				 "<p style='text-align:left; padding:0;'>Request for <b>Longest</b> and <b>Shortest</b> message Count ? ".
			     "<input type='submit' name='request_replacement_count' value='Request' onclick='$confirmation'" .
			     "</p>" .
				 "</form>";
			return $x;
		}


    	$replacement = $message_manager->get_replacement_count();
		unset($_POST['request_replacement_count']);

        $tooltip_manager = new TooltipManager();

		$min_text = str_replace("\n","<br>",$replacement->min_message);
		$max_text = str_replace("\n","<br>",$replacement->max_message);

		$params = new StdClass();
		$params->type='image';
		$params->image = 'note.gif';
		$params->positions = "'right','bottom','left','top'";
		$params->text_align = 'left';
		$params->fontsize = 13;

		$params->content = $min_text;
		$tooltip = $tooltip_manager->get($params);
 	    $scripts[] = $tooltip->script;
        $href_min_message = $tooltip->component;

		$params->id = null;
		$params->content = $max_text;
		$tooltip = $tooltip_manager->get($params);
 	    $scripts[] = $tooltip->script;
        $href_max_message = $tooltip->component;

		$all_scripts = join("\n",$scripts);

        $tk[]='message_confirm_show_longest_message';
        $tk[]='message_confirm_show_shortest_message';
        $t=Register::get_instance()->get_property('message_bundle')->get_lang_values($tk);

        $longest_text=sprintf($t->message_confirm_show_longest_message,
                              "<b>$replacement->max_length</b>",
                              $href_max_message);
        $shortest_text=sprintf($t->message_confirm_show_shortest_message,
                               "<b>$replacement->min_length</b>",
                               $href_min_message);

		$replacement_count_text =
             "$longest_text<br>" .
			 "$shortest_text" .
			 $all_scripts;

		return $replacement_count_text;
	}


	private function filter()
	{
		pre($_POST);
		$_POST['including_phone_list'] .= "\n6598470021";
		pre($_POST);

		$dm_tag_user_rel = new DM_User_Tag_Rel();
		$dm_tag_user_rel->where('tag_id',48)->get();
		foreach ($dm_tag_user_rel->all as $rel)
		{
			$user_id[] = $rel->user_id;
		}

		$dm_user = new DM_User();
		$dm_user->where_in('id',$user_id)->get();
		echo $this->db->last_query();
		pre(sizeof($dm_user->all));

	}

    function message_send()
    {
    	global $system_folder;
		require_once(APPPATH. "libraries/MessageManager.php");

		if (empty($_POST))
		{
			redirect("/message_controller/message_input_basic/ERROR_NO_DATA_PROVIDED_FOR_MESSAGE_SEND");
		}

		
    	$message_manager = new MessageManager($_POST);
		$status = $message_manager->execute();
	
	setcookie('tempgrp', '', time()-1000);
        setcookie('tempgrp', '', time()-1000, '/');
	//error_log($_POST);
		
	//email sending
	$to = $this->dm_account_user->email;
	$from_name=$this->message_bundle->get_lang_value('thread_mobilskole_mail');//'Mail Delivery Subsystem Mobilskole';
	$from_address="hjelp@mobilskole.no";
        
	/*$recipient_names = '';
	
	$all_recipients = $message_manager->get_all_recipients();
	$recipient_names = array();
	foreach($all_recipients as $a_recipient) {
		$recipient_names [] = get_display_name(
			$a_recipient->first_name,
			$a_recipient->last_name,
			$a_recipient->phone, ' (%s)');
	}
	sort($recipient_names); */
	
						
	
    $msgstr = strip_tags( $this->session->userdata('tempmsgstr') );



$contry = $this->config->item('remote_country_abb');
$mydatetime = null;

switch (strtoupper($contry)) {
        case "SG":
            $d = new DateTime("now", new DateTimeZone("Asia/Singapore"));
    		$mydatetime =  $d->format(DateTime::W3C);
	    break;
	case "NO":
	   $d = new DateTime("now", new DateTimeZone("Europe/Oslo"));
    $mydatetime =  $d->format(DateTime::W3C);
	    break;
	case "SE":
	     $d = new DateTime("now", new DateTimeZone("Europe/Stockholm"));
    $mydatetime =  $d->format(DateTime::W3C);
	    break;
	default:
    	 $mydatetime =  date('Y-m-d H:i:s');
	}

	//error_log($contry);
	//error_log($mydatetime);

      
      $subject=utf8_decode($this->message_bundle->get_lang_value('email_sms_delivery_notification')) ;//SMS Delivery Notification
            $sender_name=isset($post_data['sender_name']) ? $post_data['sender_name'] : $this->dm_account->sender;
      $message = $this->message_bundle->get_lang_value('thread_tech_sms_details'). "\n"; //----- Technical details of message -----
      $message .= $mydatetime ." \n";
      $message .= $this->message_bundle->get_lang_value('thread_original_sms') ." : " .  $_POST['message']. " \n"; //Original message 
      $message .= $this->message_bundle->get_lang_value('thread_remark')." : " .  $_POST['remark']. " \n"; 
      $message .= $this->message_bundle->get_lang_value('sender_name')." : " . $sender_name . " \n"; 
      $message .= $this->message_bundle->get_lang_value('thread_process_info')."\n";//Process info 
      $message .= $msgstr . "\n";
      $message .= $this->message_bundle->get_lang_value('thread_for_more_info'). " http://hjelp.mobilskole.no \n";//more info 
     // $message .=  implode($recipient_names, "\n") . "\n" ; 
      $message .= "----------------------------------- \n";
      
      $lnChecked =0;
      if(isset($_POST['emailchk']))
	$lnChecked = ($_POST['emailchk'] == "no") ? 1 : 0;   
    // error_log ("checked.." . $lnChecked );
    
    if($lnChecked == 1 ){

	//error_log($to);

	$this->email->from($from_address, $from_name);
	$this->email->to($to); 
	//$this->email->bcc('sarangan12@gmail.com'); 
    
	$this->email->subject($subject);
	$this->email->message($message);	
    
	$this->email->send();
	
	/*
	$headers = "From: ".$from_address."\r\n";
	$headers .= "Reply-To: ".$from_address."\r\n";
	$headers .= "Return-Path: ".$from_address."\r\n";
	$headers .= 'X-Mailer: PHP/' . phpversion()."\r\n";
	//set content type to HTML
	$headers .= "Content-type: text/html\r\n";

	mail($to,$subject,$message,$headers) ; */
		
	
	//error_log($message);
	//error_log('email send');
	
    }

	
	//$status='ERROR_MESSAGE_SENT_FAILED'; // need to take this guy and need to comment out the status send message * don't forget 
	
	

        $url=$this->get_back_url($status);

        redirect($url);
    	
    }

   function get_message_input_progress_info($step)
   {
      #---Retrieve translation  for progress-----------------------------------
      $tk[] = 'progress_select_members';
      $tk[] = 'progress_write_message';
      $tk[] = 'progress_confirm';
      $tk[] = 'progress_send';
      $t = Register::get_instance()->get_property('message_bundle')->get_lang_values($tk);

      #---Put text into sequence-----------------------------------------------
      $texts= array($t->progress_select_members,
                    $t->progress_write_message,
                    $t->progress_confirm,
                    $t->progress_send);

      #---Decide which stage is to be highlighted------------------------------
      $progress_info='';
      $progress_arrow = "<img border='0' style='vertical-align:middle;' src='{$this->base_url}img/progress_arrow.gif'/>";
      foreach ($texts as $index=>$text)
      {
        $info = ($step == $index) ? "<b>$text</b>" : $text;
        if (($index+1)==count($texts)) $progress_arrow = '';
        $progress_info .= "$info $progress_arrow ";
      }

      #---Return result--------------------------------------------------------
      return "<div style='padding:10px 5px 10px 10px; border:1px none red;'>" .
             "$progress_info" .
             "</div>\n";
    }

	function list_sent_messages($current_page=1)
	{
    	$view['current_page']=$current_page;
        $data['form'] = $this->load->view('list_sent_messages',$view,true);
        $this->load->view('home',$data);
	}

	function list_sent_message_details($param1,$param2='',$param3='')
	{
		$this->load->library('HeaderLinkManager');

		$sort_key = 'SORT:MESSAGE_DETAIL:phone:asc';
		if ($param3=="CLICK")
		{
			$current_page = $param1;
			$sort_key = $param2;
			$message_id = $this->session->userdata('message_id');
		}
		else
		{
			$current_page = 0;
			$message_id = $param1;
		}

        $session_data['message_id']=$message_id;
        $this->session->set_userdata($session_data);

		$view = array();
		$view['message_id'] = $message_id;
		$view['current_page']=$current_page;
		$view['sort_key'] = $sort_key;
		
		
        $data['form'] = $this->load->view('list_message_details',$view,true);;

        $this->load->view('home',$data);

	}

	function monthly_message_count_form($errors='')
	{
		$form_data = $_POST;
		$dm_account = new DM_Account();
		$dm_account->order_by('company');
		$dm_account->get();

		$form_data = $_POST;
		$form_data['customer_ids[]'] = format_select_options($dm_account,'id','company');

        $data['form'] =  FormFactory::get_instance()->create('kompis','message_count_select_customer')->open($form_data,$errors);
        $this->load->view('home',$data);
	}

	function monthly_message_count()
	{
		$customer_ids = $this->input->post('customer_ids');

		if (!$customer_ids)
		{
			$this->monthly_message_count_form('Please select Customer.');
			return;
		}
		$view = array();
		$view['customer_id'] = $customer_ids[0];

		$data['form'] = $this->load->view('message_count',$view,true);
		$this->load->view('home',$data);
	}


	function generate_statistics()
	{
		if (!empty($_POST))
		{
			global $system_folder;
	    	require_once(APPPATH. "libraries/MonthlyStatistics.php");
			MonthlyStatistics::generate_statistics($_POST);
		}
		$data['form'] = $this->load->view('generate_statistics_form.php',null,true);

		$this->load->view('home',$data);
	}

	function statistics()
	{
		$result = '';
		if (!empty($_POST))
		{
			$view= array();
			$view['params']=$_POST;
			$result = $this->load->view('list_message_statistics',$view,true);
		}
		$view['result']=$result;
		$data['form'] = $this->load->view('statistic_form',$view,true);
		$this->load->view('home',$data);
	}

    function process_form($current_page=0)
    {
		$view['action']='process';
		$view['method']='process';
		$view['current_page']=$current_page;
		$view['title']='process_message';
		$data['form']=$this->load->view('list_message_headers',$view,true);
        $this->load->view('home',$data);
    }

    function process($message_id)
    {
    	global $system_folder;
    	require_once(APPPATH. "libraries/MessageProcessor.php");
		$message_processor = new MessageProcessor($message_id);
		$message_processor->execute();
		redirect('/message_controller/process_form');
    }

	function send_userlist($param='',$phone_number='', $userlistkey='')
	{
		//error_log(implode(',',$this->input->post('user_ids')));
		//error_log('param');
		//error_log($param);
		//error_log('phone number');
		//error_log($phone_number);
		
		if ($param=='SEND')
		{
		    $_POST['including_phone_list'] = $phone_number;
			$errors = '';
		}
		else
		{
		    $tempgrparr = null;
		    $useridarr = array();
		       if(isset($_COOKIE['tempgrp'])) {
			   $tempgrparr = explode("*",$_COOKIE['tempgrp']);
			  // error_log($tempgrparr);
			   foreach($tempgrparr as $row){
				if(strlen(trim($row)) > 0)
				    $useridarr[] = $row;
				
			   }
			   
		    }

		  //  $_POST['userlist_key_id']  __session_group_user_list__
			$this->load->helper('userlist');
			$errors = $param;
			$user_ids = $useridarr;//$this->input->post('user_ids'); // getting check box ids from cookie for god sake
			$userlist_key_id = $this->input->post('userlist_key_id');
			if(strlen (trim($userlist_key_id)) > 0 )
			    $userlist_key_id = $this->input->post('userlist_key_id');
			else
			    $userlist_key_id =  $this->session->userdata('userlistkeyid');

			//$userlist_key_id ='__session_group_user_list__' ;//$this->input->post('userlist_key_id');
			$helper = new UserListHelper($userlist_key_id);
			$helper->add($user_ids);

			$worklist_id = $helper->get_current_worklist_id();
			$dm_worklist_detail = new DM_Worklist_Detail();
			$dm_worklist_detail->where('worklist_id',$worklist_id)->get();
			$stored_list = array();
			foreach ($dm_worklist_detail->all as $detail)
			{
				$user_ids[] = $detail->entity_id;
			}

			if (!empty($user_ids))
			{
				$dm_user = new DM_User();
				$dm_user->where_in('id',$user_ids)->get();

				$phone_list = array();
				foreach ($dm_user->all as $user) $phone_list[] = $user->phone;
				$_POST['including_phone_list']=join("\n",$phone_list);
			}
			$helper->remove_all();
		}

		$form_data = $_POST;
        $data['form'] =  FormFactory::get_instance()->create('kompis','message_input_basic_2')->open($form_data,$errors);
        $this->load->view('home',$data);

	}
}