  <?php

    #---Global Information-----------------------------------------------------
    global $CI;
    global $is_internal_user;
    global $tooltip_manager;

    $CI = get_instance();
	$tooltip_manager = new TooltipManager();
    $is_internal_user = $this->dm_account_user->is_internal_special_account();

    #---Basic Information------------------------------------------------------
    $t = __list_sent_messages_get_lang();
    $t_title = $this->message_bundle->get_lang_value('list_sent_messages');
    list($dm_message,$count,$pagination_link) = __list_sent_messages_get_main_data($current_page);
    list($success,$reject) = __list_sent_messages_get_count($dm_message);
	$listing_footer = get_listing_footer($count,$pagination_link);
	

    #---Main Loop--------------------------------------------------------------
    $content = '';
    $index=0;
    foreach ($dm_message as $message) 
    {
      $message->limit = $message->limit_message == 0 ? '&nbsp;' : $message->limit_message;
      $message->tag_name = $message->tag_name ?  $message->tag_name : '&nbsp;';
      $message->start_datetime   = substr($message->start_datetime,0,16);
      $message->start_process_datetime = $message->start_process_datetime ?  $message->start_process_datetime : '&nbsp;';
      $message->start_process_datetime = substr($message->start_process_datetime,0,16);
      
      
      //error_log($dm_message->username);
      
      //$message->username=  $dm_message->username;

      $success_count  = $success[$message->id];
      $rejected_count = $reject[$message->id];
      
      list($ftp_status,$href_detail) = __list_sent_messages_get_status($message);
	  
	

      $odd_even=$index%2==0 ? 'even' : 'odd';
      $content .= "<tr class='$odd_even'>\n" .
                  "<td style='text-align: center;'>$message->id&nbsp;</td>\n" .
                  "<td style='text-align: left;'>&nbsp;$message->start_datetime&nbsp;</td>\n" .
                  "<td style='text-align: left;'>&nbsp;$message->sender_name&nbsp;</td>\n" .
		   "<td style='text-align: left;'>&nbsp;$message->username&nbsp;</td>\n" .
                  "<td style='text-align: left;'>&nbsp;$message->remark</td>\n" .
                  "<td style='text-align: right;'>&nbsp;$success_count&nbsp;</td>\n" .
                  "<td style='text-align: right;'>&nbsp;$rejected_count&nbsp;</td>\n" .
                  "<td style='text-align: center;'>\n  $ftp_status\n</td>\n" .
		          "<td style='text-align: center;'>\n  $href_detail\n</td>\n" .
                  "</tr>\n";
    $index +=1;
    }

	//$empty_message='';
 	//if (empty($message_ids))
 	//{
 	//   $empty_message = $this->message_bundle->get_lang_value('MESSAGE_NO_SMS_MESSAGE');
 	//   $empty_message = "<div class='successful' style='width:90%; text-align: center;'>$empty_message</span></div>";
 	//}

    //$all_scripts = join("\n",$scripts);

    /*
     +------------------------------------------------------------------------+
     | Support Functions                                                      |
     +------------------------------------------------------------------------+
    */
    function __list_sent_messages_get_status($message)
	{
	  global $is_internal_user;
	  global $tooltip_manager;


	  $ftp_status_message = '';
	 
	  $is_ftp_ok = is_numeric(strpos($message->ftp_status,"0")) ? false : true;
	  $text_align = 'center';

	  if ($is_internal_user)
	  {
	  	 $tmp = explode("|",$message->ftp_info);
		
		 
  	 	 $ftp_server= $tmp[0] ? " via <b>$tmp[0]</b>" : '';

	  	 if ($is_ftp_ok)
	  	 {
	  	    $ftp_status_message = "Message (%s) sent successfully{$ftp_server}.";
	  	 }
	  	 else
	  	 {
	  	    $ftp_status_message = "Message sent failded{$ftp_server}.<br>";

	  	 	$tmp = explode("|",$message->ftp_status);
	  	 	$tmp[0] = isset($tmp[0]) ? $tmp[0] : '';
	  	 	$tmp[1] = isset($tmp[1]) ? $tmp[1] : '';
	  	 	$tmp[2] = isset($tmp[2]) ? $tmp[2] : '';

	  	 	if (is_numeric($tmp[0]) and !$tmp[0]) $ftp_status_message .= "Failed at connect to FTP server.<br>";
	  	 	if (is_numeric($tmp[1]) and !$tmp[1]) $ftp_status_message .= "Failed at login to ftp server.<br>";
	  	 	if (is_numeric($tmp[2]) and !$tmp[2]) $ftp_status_message .= "Failed at ftp file transfer.<br>";
	  	    $text_align = 'left';
	  	 }
	  }
	  else
	  {
		   $ftp_status_message = ($is_ftp_ok) ? 'Message (%s) sent successfully' :
		                                        'Message (%s) sent failed.';
	  }

	  $status_image = ($is_ftp_ok) ? "ftp_status_ok.png": "ftp_status_failed.png";

	  $params = new StdClass();
	  $params->id = "sent_status_$message->id";
	  $params->type='image';
	  $params->width=220;
	  $params->text_align=$text_align;
	  $params->content=$ftp_status_message;
	  $params->image = $status_image;
      $params->replacement1 = $message->id;
	  $params->return_component_only = true;
      $ftp_status = $tooltip_manager->get($params);

	  $params = new StdClass();
	  $params->id ="view_detail_$message->id";
	  $params->type='anchor';
	  $params->width=250;
	  $params->content = 'Click here to view more detail of Message ID : %s.';
	  $params->href = "message_controller/list_sent_message_details/$message->id";
	  $params->image = 'note.gif';
	  $params->replacement1 = $message->id;
	  $params->return_component_only = true;

      $href_detail = $tooltip_manager->get($params);

      return array($ftp_status,$href_detail);

    }
    
   

    function __list_sent_messages_get_count($dm_message)
    {
      $success='';
      $reject = '';
 	 if (count($dm_message) > 0 )
 	  {
    	$message_ids = array();
    	foreach ($dm_message as $message) $message_ids[] = $message->id;
    	$dm_message_detail = new DM_Message_Detail();
    	$success = $dm_message_detail->get_success_count($message_ids);
    	$reject  = $dm_message_detail->get_rejected_count($message_ids);
 	 }
 	  return array($success,$reject);
    }

    function __list_sent_messages_get_main_data($current_page)
    {
	  global $CI;
	  $cpCI = clone $CI;
      #---Conidtion--------------------------------------------------------------
  	  $condition = array();
	  $conditionstr = '';
	  
	  $condition['customer_id']=$CI->customer_id;
	  $conditionstr = " where messages.customer_id =  " . $CI->customer_id;
	  
	  

      if (!$cpCI->dm_account_user->superuser_flag){
	  $condition['account_user_id']=$CI->account_user_id;
	   $conditionstr .= " and messages.account_user_id =  " . $CI->account_user_id ;
      }
		
	$cpCI->load->database();
	$dm_message = $cpCI->db->query('SELECT messages.id, messages.customer_id, messages.account_user_id, messages.limit_message, messages.remark, messages.tag_name, messages.tag_id, messages.is_new_tag_flag, messages.filename, messages.including_tags, messages.excluding_tags, messages.start_datetime, messages.end_datetime,
						messages.start_process_datetime, messages.end_process_datetime, messages.message, messages.including_phone_list,
						messages.excluding_phone_list, messages.ftp_info, messages.ftp_status, messages.post_paid, messages.sender_name,account_users.username 
						FROM messages LEFT JOIN account_users ON messages.account_user_id = account_users.id ' .  $conditionstr );

//echo 'Total Results: ' . $query->num_rows();

	//  $dm_message = new DM_Message();
	  
	  $count = $dm_message->num_rows() ;//$dm_message->where($condition)->count();
	  $p = get_default_pagination();
	  $p->offset = $p->page_size*($current_page-1);
	  $p->current_page = $current_page;
	  $p->page_total = $count;
	  $p->controller= 'message_controller';
	  $p->action='list_sent_messages';
	  $pagination_link=get_pagination($p);

	 // $dm_message->where($condition);
	 
	  $cpCI1 = clone $CI;
	 
	 	$dm_message = $cpCI1->db->query('SELECT messages.id, messages.customer_id, messages.account_user_id, messages.limit_message, messages.remark, messages.tag_name, messages.tag_id, messages.is_new_tag_flag, messages.filename, messages.including_tags, messages.excluding_tags, messages.start_datetime, messages.end_datetime,
						messages.start_process_datetime, messages.end_process_datetime, messages.message, messages.including_phone_list,
						messages.excluding_phone_list, messages.ftp_info, messages.ftp_status, messages.post_paid, messages.sender_name,account_users.username 
						FROM messages LEFT JOIN account_users ON messages.account_user_id = account_users.id ' .  $conditionstr . " order by id desc limit ". $p->offset  . "," . $p->page_size );
	 
	
	// $CI->db->order_by('id','desc'); 
	  //$dm_message->order_by('id','desc');
	 // $dm_message->get($p->page_size,$p->offset);
	// $CI->db->limit($p->page_size, $p->offset);



      return array($dm_message->result(),$count,$pagination_link);
    }

    function __list_sent_messages_get_lang()
    {
       global $CI;
       $tk[] = 'sent_date';
       $tk[] = 'internal_remark';
       $tk[] = 'success_count';
       $tk[] = 'rejected_count';
       $tk[] = 'action';
       $tk[] = 'details';
       $tk[] = 'message';
       $tk[] = 'status';
       $tk[] = 'username';

       return $CI->message_bundle->get_lang_values($tk);
    }
?>
<!--fieldset class='listing_box'-->
<!--legend class='listing_legend'><?=$t_title?> </legend-->
<div class='list' style='border: 1px solid none;'>
<table class='list' border='0' cellspacing='1' cellpadding='1' style='width: 100%;' >
<caption><?=$t_title?></caption>
<thead>
<tr id='list_heading'>
  <th style='text-align: center; width: 8%;'><?=$t->message?></th>
  <th style='text-align: left;   width: 23%;'>&nbsp;<?=$t->sent_date?></th>
  <th style='text-align: left;   width: 9%;'>&nbsp;Sender</th>
   <th style='text-align: left;   width: 9%;'>&nbsp;<?=$t->username?></th>
  <th style='text-align: left;   width: 49%;'>&nbsp;<?=$t->internal_remark?></th>
  <th style='text-align: right;  width: 5%;'>&nbsp;<?=$t->success_count?>&nbsp;</th>
  <th style='text-align: right;  width: 5%;'>&nbsp;<?=$t->rejected_count?>&nbsp;</th>
  <th style='text-align: left;   width: 5%;'>&nbsp;<?=$t->status?>&nbsp;</th>
  <th style='text-align: center; width: 6%;'>&nbsp;<?=$t->details?>&nbsp;</th>
</tr>
</thead>
<?=$content?>
</table>
<div style='text-align:center; margin:0; padding:0'>
  <?=$listing_footer?>
</div>
</div>
<!--/fieldset-->
<!--?=$empty_message?-->
<? echo join("\n",$tooltip_manager->get_stored_scripts(false));?>


