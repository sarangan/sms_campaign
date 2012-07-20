<?php

require_once(APPPATH ."helpers/common_helper.php");


class MessageManager
{
  private $post_data;
  private $including_tags;
  private $exclude_index;
  private $entity_tag;
  private $file_handle;
  private $is_new_tag;

  private $all_users=array();
  private $excluding_phone_list=array();
  private $excluding_phone_list_from_tag=array();
  private $output_directory;

   function __construct($post_data)
   {
      if ($post_data=='') return;
      
      $this->CI = &get_instance();
      $CI = $this->CI;

      #---Determine output directory---------------------------------------------
	/*switch (getenv('HTTP_HOST'))
	{
		#--Development-----------------------------------------------------
		case '127.0.0.1';
			$dir = '../../file_drop/kompis/message_files/';
			break;
        #---Test Environment-----------------------------------------------
		case 'kompis3.incent.com.sg';
            $dir = '/kompis/file_drop/message_files/';
			break;
		case 'kompis3.incent.no';
            $dir = '/kompis/file_drop/message_files/';
			break;
        case 'beta.incent.com.sg';
            $dir = '/kompis-beta/file_drop/message_files/';
            break;
		#---Production System----------------------------------------------
		case 'secure.incent.no';
		    $dir = '/home/1/i/incent/kompis/file_drop/message_files/';
			break;
		case '117.121.241.179':
		case 'customers.incent.com.sg':
		    $dir = '/kompis/file_drop/message_files/';
			break;
		default:
			$dir='';
			break;
	}*/
	$dir= $CI->config->item('SMSOUPUTPATH');
	//error_log($dir);

	$this->output_directory = $dir;


	
	$this->customer_id =  $CI->dm_account->id;
	$this->dm_account  =  $CI->dm_account;
	$this->dm_account_user = $CI->dm_account_user;

    $this->post_data = $post_data;
    $this->tag_name       = $CI->input->post('tag_name');
    $this->message        = $CI->input->post('message');
    $this->limit_message  = $CI->input->post('limit_message');
    $this->including_tags = $CI->input->post('including_tags');
    $this->excluding_tags = $CI->input->post('excluding_tags');
    $this->remark         = $CI->input->post('remark');
    $this->online         = isset($post_data['__online__']);

    #---urgent 1.0-------------------------------------------------------------
	$this->sender_name=isset($post_data['sender_name']) ? $post_data['sender_name'] : $this->dm_account->sender;

	if (!$this->sender_name) $this->sender_name = $this->dm_account->sender;

    #---Include Data-------------------------------------------------------
    $including_phone_list = $CI->input->post('including_phone_list');
    $including_phone_list = explode("\n",$including_phone_list);
    if ($including_phone_list[0]=='') unset($including_phone_list[0]);

	if ($this->including_tags==null)
	{
		$including_users = array();
	}
	else
	{
		//    	$including_users = U_serTagRels::get_instance()->g_et_by_tag_id($this->including_tags);
		//		$including_users = $including_users->get_result()->list;

		$dm_user = new DM_User();
		$including_users = $dm_user->get_by_groups($this->including_tags)->data;
	}

    $this->all_users = array_merge($including_users, $including_phone_list);

    #---Exclude Data-------------------------------------------------------
    $excluding_phone_list = $CI->input->post('excluding_phone_list');
    $excluding_phone_list = explode("\n",$excluding_phone_list);
    if ($excluding_phone_list[0]=='') unset($excluding_phone_list[0]);
    $this->excluding_phone_list = array_flip($excluding_phone_list);

	$excluding_tag_users = array();
	if ($this->excluding_tags!=null)
	{
		$dm_user = new DM_User();
		$excluding_tag_users = $dm_user->get_by_groups($this->excluding_tags)->data;
	}

    foreach ($excluding_tag_users as $user)
    {
      $this->excluding_phone_list_from_tag[$user->phone]="";
    }
  }



	private function make_all_users_stdClass($recipients)
	{
		$only_stdClass_recipients = array();

		foreach($recipients as $recipient) {
			if(!is_object($recipient)) { 
				$new_recipient = new StdClass();
				$new_recipient->first_name = '';
				$new_recipient->last_name = '';
				$new_recipient->phone = $recipient;
				$recipient = $new_recipient;
			}
			$only_stdClass_recipients[] = $recipient;
		}
	 
		return $only_stdClass_recipients;
	}

	function remove_duplicate_users($users) {
		$all_users_without_duplicates = array();
		foreach($users as $user) {
			$all_users_without_duplicates[$user->phone] = $user;
		}
		return array_values($all_users_without_duplicates);
	}

	private function filter_excluded_users($all_users, $excluded_numbers) {
		$recipients = array();

		foreach($all_users as $user) {
			if(!array_key_exists($user->phone, $excluded_numbers)) {
			 	$recipients[$user->phone] = $user;
			}
		}
		
		return $recipients;
	}
	/**
	 * This function filters exluding numbers out of the all_users array
	 */
	function get_all_recipients() {
		$users = $this->make_all_users_stdClass($this->all_users);
		$users = $this->remove_duplicate_users($users);
		
		$excluded_numbers = array_merge(
			$this->excluding_phone_list, 
			$this->excluding_phone_list_from_tag);

		$recipients = $this->filter_excluded_users($users, $excluded_numbers);
	
		return $recipients;
	}
	

  /*
   +-------------------------------------------------------------------------
   |
   +-------------------------------------------------------------------------
  */
  private function create_tag()
  {
    if (!$this->tag_name)
    {
      $this->entity_tag = null;
      return;
    }


	$dm_tag = new DM_Tag();
	$dm_tag->where('tag_name',$this->tag_name);
	$dm_tag->where('customer_id',$this->customer_id);
	$dm_tag->get();

    if ($dm_tag->id)
    {
      $this->entity_tag = $dm_tag;
      $this->is_new_tag = false;
      return;
    }

    #---Handle New Tag Name (if any)---------------------------------------
    if ($this->tag_name)
    {
      $dm_tag = new DM_Tag();
      $dm_tag->customer_id = $this->customer_id;
      $dm_tag->tag_order = $dm_tag->default->tag_order;
      $dm_tag->description = $this->tag_name;
      $dm_tag->tag_name = $this->tag_name;
      $dm_tag->save();

	  $this->is_new_tag=true;
      if ($dm_tag->id) $this->entity_tag = $dm_tag;
      else  $this->entity_tag = null;
    }
  }

  /*
   +-------------------------------------------------------------------------
   |
   +-------------------------------------------------------------------------
  */
  private function open_message()
  {
    #---Create Message Header----------------------------------------------
//    $_POST['including_tags']=join("\n",$this->including_tags);
//    $_POST['excluding_tags']=join("\n",$this->excluding_tags);
//    $_POST['customer_id']=$this->customer_id;
//    $_POST['account_user_id']=$this->account_userx\->p_roperty->id;
//    $_POST['tag_name']=$this->tag_name;
//    $_POST['limit_message'] = $this->limit_message;
//    $_POST['filename']="";
//    $_POST['is_new_tag_flag']=$this->is_new_tag;
//    $_POST['remark']=$this->remark;
//    $_POST['message']=$this->message;

    $dm_message = new DM_Message();
    if ($this->entity_tag) $dm_message->tag_id = $this->entity_tag->id;
    else $dm_message->tag_id = -1;

	$CI = &get_instance();

    $dm_message->start_datetime=date('Y-m-d H:i:s');
    $dm_message->including_phone_list = $CI->input->post('including_phone_list');
    $dm_message->excluding_phone_list = $CI->input->post('excluding_phone_list');
    $dm_message->including_tags=join("\n",$this->including_tags);
    $dm_message->excluding_tags=join("\n",$this->excluding_tags);
    $dm_message->customer_id=$this->customer_id;
    $dm_message->account_user_id=$this->dm_account_user->id;
    $dm_message->tag_name=$this->tag_name;
    $dm_message->limit_message = $this->limit_message;
    $dm_message->filename="";
    $dm_message->is_new_tag_flag=$this->is_new_tag;
    $dm_message->remark=$this->remark;
    $dm_message->message=$this->message;
    $dm_message->post_paid = $CI->dm_account->post_paid_account;
    $dm_message->sender_name = $this->sender_name; #---Urget 1.0
    $dm_message->save();
    $this->dm_message = $dm_message;
  }

  /*
   +-------------------------------------------------------------------------
   |
   +-------------------------------------------------------------------------
  */
   public function get_message_id()
   {
      if (!$this->dm_message) return null;
      return $this->dm_message->id;
   }



   private function get_file_info()
   {
//     $file_info=new StdClass();
//     $file_info->bulk_insert_type=2;
//     $file_info->file_ext='.txt';
//     $file_info->username='Kompis3-no';
//     $file_info->password='jgyDaf6';
//     return $file_info;


      $kompis_system=get_kompis_system();
      $system="{$kompis_system->location}-{$kompis_system->type}";

      switch ($system)
      {
        case 'NO-prod':
          $username='Kompis3-no';
          $password='jgyDaf6';
          break;
        case 'SG-test':
          $username='Kompis3-test';
          $password='gbmamGBU';
          break;
        case 'NO-test':
          $username='Kompis3-test';
          $password='gbmamGBU';
          break;
       default:
          $username='Kompis3-dev';
          $password='ns789asd';
          break;
     }

     $file_info=new StdClass();
     $file_info->bulk_insert_type=2;
     $file_info->file_ext='.txt';
     $file_info->username=$username;
     $file_info->password=$password;
     return $file_info;
  }

  /*
   +-------------------------------------------------------------------------
   |
   +-------------------------------------------------------------------------
  */
  private function open_file()
  {
    $file_info=$this->get_file_info();

    $file_open_status=false;
    while (true)
    {
  

      $timestamp=date('YmdHis');
      $filename="{$file_info->bulk_insert_type}_{$timestamp}";
      $filename.="_{$file_info->username}_{$file_info->password}{$file_info->file_ext}";

      //if (file_exists(self::OUTPUT_DIR . $filename))
      if (file_exists($this->output_directory . $filename))
      {
        #echo "$filename<br>";
        sleep(rand(1,3));
        continue;
      }
      else
      {
        $file_open_status=true;
        break;
      }
    }

    if (!$file_open_status) return false;

    $this->file_handle = fopen($this->output_directory . $filename,'w');
    $this->filename= $filename;
    //$_POST['filename']=$filename;

	$this->filename=$filename;
    $this->dm_message->filename = $filename;
    $this->dm_message->update();

    return true;
  }

  /*
   +-------------------------------------------------------------------------
   |
   +-------------------------------------------------------------------------
  */
  private function close_message()
  {
    //$this->entity_message->close();
	//$_POST['end_datetime'] = $this->get_now();
  	$this->dm_message->end_datetime = date('Y-m-d H:i:s');
  	$this->dm_message->save();
  }

  public function get_count()
  {
  	//global $system_folder;
  	require_once(APPPATH. "libraries/BlockedPhones.php");
	require_once(APPPATH. "libraries/PhoneNumberValidator.php");

	$validator = PhoneNumberValidator::get_instance();

    $phone_index   = array();
    $include_index = array();
    $message = $this->message;

    $send_count=0;
	$count=array();
    $blocked_phones = new BlockedPhones();

    foreach ($this->all_users as $index=>$user)
    {
      #---Handle Existing User-------------------------------------------
      if ($user instanceof StdClass)
      {
        $user_id = $user->user_id;
        $phone   = $user->phone;
      }
      else
      {
        $user_id = '';
        $phone   = $user;
      }

	  $validator->set_phone_list($phone);
	  $result = $validator->execute(true);

      $status = '';
      if ($phone=='')
           $status = 'E_PHONE_NULL';
      elseif ($result->error_code)
      {
			$status =$result->status;
      }
//      elseif (!is_numeric($phone))
//           	$status = 'E_PHONE_NOT_NUMERIC';
//      elseif (strlen($phone)>10)
//           $status = 'E_PHONE_TOO_LONG';
//      elseif (strlen($phone)<10)
//           $status = 'E_PHONE_TOO_SHORT';
      elseif (array_key_exists($phone,$phone_index))
        $status = 'dup-tag';
      elseif (array_key_exists($phone,$include_index))
        $status = 'dup-include';
      elseif (array_key_exists($phone,$this->excluding_phone_list))
        $status = "exclude";
      elseif (array_key_exists($phone,$this->excluding_phone_list_from_tag))
        $status = "exclude-tag";
      elseif ($blocked_phones->is_blocked_by_phone($phone))
        $status = "blocked";

      if ($status!='')
      {
      	 @$count[$status]++;
      }

      if ($user instanceof StdClass)
        $phone_index[$phone]=$index;
      else
        $include_index[$user]=$index;
    }

	$total=0;
    foreach ($count as $x)
    {
    	$total+=$x;
    }

	$count['total']=$total;

	return $count;

  }

  private function prepare($data)
  {
		$dm_user = new DM_User();
		$dm_user->where('customer_id',$this->customer_id);
		$dm_user->where('phone',$data->phone);
		$dm_user->get();

  		$sender=$data->phone;
		$sender_short = substr($sender,2);
		$sender_sg = substr($sender_short,0,4) . " " . substr($sender_short,4,4);
		$sender_no = substr($sender_short,0,3) . " " . substr($sender_short,3,2) . " " .substr($sender_short,5,3);

  	    $operation = new StdClass();
		$operation->standard->DATE=date('d.m.Y');
		$operation->standard->TIME=date('H:i:s');
		$operation->standard->DATETIME=date('d.m.Y H:i:s');
		$operation->standard->RCP=$data->phone;
		$operation->standard->RCP_NO=$sender_no;
		$operation->standard->RCP_SG=$sender_sg;
		$operation->standard->RCP_S=$sender_short;
		$operation->standard->FIRST_NAME = $dm_user->first_name;
		$operation->standard->LAST_NAME = $dm_user->last_name;
		$operation->standard->PHONE = $dm_user->phone;
		$operation->standard->DESC = $dm_user->description;
		$operation->standard->EMAIL = $dm_user->email;
		$operation->standard->FULL_NAME = trim("$dm_user->first_name $dm_user->last_name");
		$operation->standard->CUSTOM1 = $dm_user->custom_field1;
		$operation->standard->CUSTOM2 = $dm_user->custom_field2;
		$operation->standard->CUSTOM3 = $dm_user->custom_field3;

		return $operation;
  }

  private function replacement($message,$data)
  {
	$replacement = $this->prepare($data);
	preg_match_all("/(\[[A-Z_0-9]+?\])/",$message, $fields );
	$fields=$fields[0];
	$new_message = $message;
	foreach ($fields as $field)
	{
		$name = str_replace("]","",$field);
		$name = str_replace("[","",$name);
		if (!isset($replacement->standard->$name)) continue;
		$new_message =str_replace($field,$replacement->standard->$name,$new_message);
	}
  	return $new_message;
  }



  /*
   +-------------------------------------------------------------------------
   |
   +-------------------------------------------------------------------------
  */
  private function process_message()
  {
  	//global $system_folder;
  	require_once(APPPATH. "libraries/BlockedPhones.php");
  	require_once(APPPATH. "libraries/PhoneNumberValidator.php");

	$validator = PhoneNumberValidator::get_instance();


	#---Urgent 1.0-------------------------------------------------------------
	$sender_name = $this->sender_name;

    $phone_index   = array();
    $include_index = array();
    $original_message = $this->message;

    $dm_message_detail = new DM_Message_Detail();

    $_POST['message_id'] = $this->dm_message->id;
    $handle = $this->file_handle;

    $send_max_number=100000;
    $send_count=0;
    $unique_index=1;

    $blocked_phones = new BlockedPhones();

    //loop
    foreach ($this->all_users as $index=>$user)
    {
      #---Handle Existing User-------------------------------------------
      if ($user instanceof StdClass)
      {
        $user_id = $user->user_id;
        $phone   = $user->phone;
      }
      else
      {
        $dm_user = new DM_User();
        $dm_user->where('customer_id',$this->customer_id);
        $dm_user->where('phone',$user);
        $dm_user->get();

        $user_id = $dm_user->id;
        $phone   = $user;
      }

	  $validator->set_phone_list($phone);
	  $result = $validator->execute(true);

      $status = '';
      if ($phone=='')
           $status = 'E_PHONE_NULL';
      elseif ($result->error_code)
      {
			$status =$result->status;
      }
//      elseif (!is_numeric($phone))
//           $status = 'E_PHONE_NOT_NUMERIC';
//      elseif (strlen($phone)>10)
//           $status = 'E_PHONE_TOO_LONG';
//      elseif (strlen($phone)<10)
//           $status = 'E_PHONE_TOO_SHORT';
      elseif (array_key_exists($phone,$phone_index))
        $status = 'dup-tag';
      elseif (array_key_exists($phone,$include_index))
        $status = 'dup-include';
      elseif (array_key_exists($phone,$this->excluding_phone_list))
        $status = "exclude";
      elseif (array_key_exists($phone,$this->excluding_phone_list_from_tag))
        $status = "exclude-tag";
      elseif ($blocked_phones->is_blocked_by_phone($phone))
      {
      	  $status = "blocked";
      }

	  $dm_account= new DM_Account();
	  $dm_account->where('id',$this->customer_id)->get();

	  if (!$dm_account->post_paid_account)
	  {
		  if ($dm_account->sms_balance<=$dm_account->sms_max_low_level)
		  {
		  	 $status = 'reject-low-balance';
		  }
	  }

      $unique_id = '';
      $message='';
      if ($status=='')
      {
        $unique_id = substr('00000',1,5-strlen($unique_index)) . $unique_index;
        $unique_id = $this->dm_message->id . $unique_id;
        $unique_index++;

		#---????
		if (substr($sender_name,0,1)=='+')
		{
			$sender_number = substr($sender_name,1,20);
			$sender = '';
		}
		else
		{
			$sender_number = '1963';
		}

		$data = new StdClass();
		$data->phone = $phone;
		$message = $this->replacement($original_message,$data);
		if (!$this->online) $message = utf8_decode($message);

        $line = "#|#$unique_id#|#$sender_number#|#$sender_name#|#0#|#$phone#|#$message#|#1#|#1#EOR#";
        if ($this->online) $line = utf8_decode($line);

        //$line = utf8_decode($line);
        //$line = $this->utf8_to_unicode($line);
        //$line = unicode_encode($line);
        if ($send_count<$send_max_number)
        {
           fwrite($handle,$line);
           $send_count++;
           //$this->a_ccount->debit_sms_balance();
           $dm_account = new DM_Account();
           $dm_account->where('id',$this->customer_id)->get();

           if ($dm_account->post_paid_account)
           {
           		$dm_account->post_paid_quantity++;
           }
           else
           {
           		$dm_account->sms_balance = $dm_account->sms_balance - 1;

           }
           $dm_account->save();
        }
      }

	  $dm_message_detail = new DM_Message_Detail();
	  $dm_message_detail->phone = $phone;
	  $dm_message_detail->user_id = $user_id;
	  $dm_message_detail->status = $status;
      $dm_message_detail->comment= "";
      $dm_message_detail->unique_id=$unique_id;
      $dm_message_detail->refno=$unique_id;
      $dm_message_detail->message_id = $this->dm_message->id;
      $dm_message_detail->inserted_datetime = date('Y-m-d H:i:s');
      $dm_message_detail->message=$message;
	  $dm_message_detail->save();

      if ($user instanceof StdClass)
        $phone_index[$phone]=$index;
      else
        $include_index[$user]=$index;

      //if ($this->limit_message>0 and $unique_index>$this->limit_message) break;

    }

	#---Added this-------------------------------------------------------------
	$account_user_id  = @$this->dm_account_user->id;
	if (!$account_user_id) $account_user_id = 0;

//	$_POST['customer_id'] = $this->a_ccount->p_roperty->id;
//	$_POST['account_user_id'] = $account_user_id;
//	$_POST['quantity']= $send_count;
//	$_POST['description']='Outgoing Messages';
//	$sms_credit_transaction = new S_MS_Credit_Transaction();

	$dm_transact = new DM_SMS_Credit_Transaction();
	$dm_transact->customer_id = $this->customer_id;
	$dm_transact->account_user_id = $account_user_id;
	$dm_transact->quantity = -$send_count;
	$dm_transact->description = 'Outgoing Messages';
	$dm_transact->message_id = $this->dm_message->id;
	$dm_transact->save();

	//echo $sms_credit_transaction->create();
	//echo $sms_credit_transaction->get_error_messages();
	//die;

    fclose($handle);
  }

  public function perform_file_transfer($source_file)
  {
    


    $remote_file = "bulk/".basename($source_file);
    $local_file = $this->output_directory . $source_file;


	$ftp_info->status = "1|1|1"; //For testing, not sending anything;

	$ftp_connection = new FTP_Connection($this->customer_id);
    $ftp_info = $ftp_connection->transfer($remote_file,$local_file);
	$status = $ftp_info->status;

    $this->dm_message->ftp_status=$status;
	$this->dm_message->ftp_info="$ftp_info->ftp_server|{$ftp_info->ftp_user_name}|$ftp_info->ftp_user_pass";
    $this->dm_message->update();

    if (is_numeric(strpos($status,'0')))
      return false;
    else
      return true;
  }

  function utf8_to_unicode( $str ) {

        $unicode = array();
        $values = array();
        $lookingFor = 1;

        for ($i = 0; $i < strlen( $str ); $i++ ) {

            $thisValue = ord( $str[ $i ] );

            if ( $thisValue < 128 ) $unicode[] = $thisValue;
            else {

                if ( count( $values ) == 0 ) $lookingFor = ( $thisValue < 224 ) ? 2 : 3;

                $values[] = $thisValue;

                if ( count( $values ) == $lookingFor ) {

                    $number = ( $lookingFor == 3 ) ?
                        ( ( $values[0] % 16 ) * 4096 ) + ( ( $values[1] % 64 ) * 64 ) + ( $values[2] % 64 ):
                    	( ( $values[0] % 32 ) * 64 ) + ( $values[1] % 64 );

                    $unicode[] = $number;
                    $values = array();
                    $lookingFor = 1;

                } // if

            } // if

        } // for

        return $unicode;

    } // utf8_to_unicode


  /*
   +-------------------------------------------------------------------------
   | Main Control
   +-------------------------------------------------------------------------
  */
  public function execute()
  {
  	$dm_account = new DM_Account();
  	$dm_account->where('id',$this->customer_id)->get();

	#---SMS Balance check, apply apply to non-post-paid account----------------
	if (!$dm_account->post_paid_account)
	{
	  	if ($dm_account->sms_balance<1)
	  	{
	  		$this->is_low_balance = true;
	  		return false;
	  	}
	}

	$this->is_low_balance = false;

    $this->create_tag();
    $this->open_message();

    if (!$this->open_file()) return false;

    $this->process_message();
    $status= $this->perform_file_transfer($this->filename);
    $this->close_message();
    //echo $this->filename;
	return $status;

  }

 /*
  +===========================================================================+
  | Replacement Count                                                         |
  +===========================================================================+
 */
  public function get_replacement_count()
  {
	#---Get Replacement Keys (if any)------------------------------------------
	$replacement_keys = $this->get_replacement_keys();
	if (empty($replacement_keys)) return null;

 	$batch_size = 10;
 	$result = new StdClass();
 	$result->max_length = 0;
 	$result->max_data = null;
 	$result->max_user_id = null;
 	$result->max_message = null;
 	$result->min_length = 10000000000;
 	$result->min_data = null;
 	$result->min_user_id = null;
 	$result->min_message = null;


	for ($batch_index=0; ; $batch_index++)
    {
		$offset = $batch_index * $batch_size;
		$batch = array_slice($this->all_users,$offset, $batch_size);
		if (empty($batch)) break;

		#---Note: This is pass by reference------------------------------------
 		$this->get_batch_user($batch);
		$this->xxxx($result,$batch);
    }

    #---Perform message replacement now----------------------------------------
	$result->min_message = $this->get_replacement_text($result->min_data);
	$result->max_message = $this->get_replacement_text($result->max_data);

	$result->min_length = mb_strlen($result->min_message);
	$result->max_length = mb_strlen($result->max_message);

	return $result;
  }

  private function get_replacement_text($prepared_data)
  {
	$replacement_keys = $this->get_replacement_keys();
	$replacement_message = $this->message;
  	foreach ($replacement_keys as $replacement_key)
  	{
		$theKey="[$replacement_key]";
		if (!isset($prepared_data[$replacement_key])) continue;
		$theValue=$prepared_data[$replacement_key];
		$replacement_message = str_replace($theKey,$theValue,$replacement_message);
  	}
   	return $replacement_message;
  }

  private function xxxx(&$result,$batch)
  {
	#---Get Replacement Keys (if any)------------------------------------------
	$replacement_keys = $this->get_replacement_keys();
	foreach ($batch as $user)
	{
		$prepared_data = $this->prepare($user);
		foreach ($prepared_data->standard as $field_key=>$field_value)
		{
			if(!in_array($field_key,$replacement_keys))
			{
				unset($prepared_data->standard->{$field_key});
			}
		}
		$prepared_data = (array)$prepared_data->standard;
		$length = strlen(join('',array_values($prepared_data)));
		//echo join('',array_values($prepared_data)) ."  $length . " . (ceil($length / 160)) . "xxxx<br>";

		if ($length>$result->max_length)
		{
			$result->max_length = $length;
			$result->max_data = $prepared_data;
			$result->max_user_id = $user->user_id;
		}
		if ($length<$result->min_length)
		{
			$result->min_length = $length;
			$result->min_data = $prepared_data;
			$result->min_user_id = $user->user_id;
		}
	}
  }

  private function get_batch_user(&$batch)
  {
  	$phones = array();
  	$phones_index = array();
  	foreach ($batch as $index=>$user)
  	{
  		if ($user instanceof StdClass) continue;
  		$phones[$index] = $user;
  		$phones_index["$user:$index"]='';
  	}

  	#---No user input phones---------------------------------------------------
  	if (empty($phones)) return;

 	$dm_user = new DM_User();
	$dm_user->where('customer_id',$this->customer_id);
	$dm_user->where_in('phone',$phones);
	$dm_user->get();

	#---Retrieve user data and also create by phone index----------------------
	$indexed_users = new StdClass();
	foreach ($dm_user->all as $user)
	{
		$stored_obj = $user->stored;
		$stored_obj->user_id = $user->id;
		$indexed_users->{$user->phone} = $user->stored;
	}

  	foreach ($phones_index as $key=>$dummy)
  	{
		list($phone,$index) = explode(':',$key);
		if (isset($indexed_users->{$phone})) $batch[$index] = $indexed_users->{$phone};
		else  unset($batch[$index]);
  	}
  }

  function get_replacement_keys()
  {
	preg_match_all("/(\[[A-Z_123456789]+?\])/",$this->message, $fields );
	$fields=$fields[0];

	$replacement_keys = array();
	foreach ($fields as $field)
	{
		$name = str_replace("]","",$field);
		$name = str_replace("[","",$name);
		$replacement_keys[] = $name;
	}
  	return $replacement_keys;
  }

}

?>