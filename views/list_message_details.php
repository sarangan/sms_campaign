<?php

	global $CI;
	$CI = &get_instance();

	/*
	 +------------------------------------------------------------------------+
	 |                                                                        |
	 +------------------------------------------------------------------------+
	 */
	$t = __list_message_details_get_lang();
	$sort_option = __list_message_details_get_sort_option($sort_key);

    $dm_message = new DM_Message();
    $dm_message->where('id',$message_id);
	$dm_message->where('customer_id',$CI->customer_id);
    $dm_message->get();

	$including_tag_names = __list_message_details_get_including_tag_names($dm_message->including_tags);
	$excluding_tag_names = __list_message_details_get_including_tag_names($dm_message->excluding_tags);

	#---Main Content-----------------------------------------------------------
	if ($current_page<1) $current_page=1;

	$dm_message_detail = new DM_Message_Detail();
	$count = $dm_message_detail->where('message_id',$message_id)->count();
	$p = get_default_pagination();
	$p->offset = $p->page_size*($current_page-1);
	$p->current_page = $current_page;
	$p->page_total = $count;
	$p->controller= 'message_controller';
	$p->action='list_sent_message_details';
	$p->action_indicator = $sort_key;
	$pagination_link=get_listing_footer($count,get_pagination($p));

	$header_data = array();
	$header_data[] = array('field'=>'phone','text'=>$t->phone_abbr);
	$header_data[] = array('field'=>'status','text'=>$t->status);

	$header_link_manager = new HeaderLinkManager($p);
	$header_link_manager->set_total_record($count);
	$header_link = $header_link_manager->get_header_link($header_data);

	//$header_data[] = array('field'=>'first_name','text'=>$t->first_name);
	//$header_data[] = array('field'=>'last_name','text'=>$t->last_name);
	//$header_data[] = array('field'=>'description','text'=>$t->description);
	//$header_data[] = array('field'=>'email','text'=>$t->email);

	if ($sort_option->field=='default') $sort_field="phone $sort_option->order";
	else   $sort_field = "$sort_option->field $sort_option->order";

	$dm_message_detail->where('message_id',$message_id);
	$dm_message_detail->order_by($sort_field);
	$dm_message_detail->get($p->page_size,$p->offset);

	$member_info = __list_message_details_get_member_info($dm_message_detail);

    $msg_content="";
    $seq=1;
    $base=($current_page-1)*($p->page_size);
    //foreach($message_details->get_result()->list as $index=>$message_detail)

    $count_error=0;

	$scripts = array();
	$tooltip_manager = new TooltipManager();
    foreach ($dm_message_detail->all as $index=>$message_detail)
    {
    	$member = isset($member_info[$message_detail->user_id]) ? $member_info[$message_detail->user_id] : null;
		if (!$member)
		{
			$member = new StdClass();
			$member->first_name=null;
			$member->last_name=null;
			$member->email=null;
			$member->description=null;
		}

		$link = '&nbsp;';
		
		
		if ($message_detail->message)
		{
			$message_detail->message = str_replace('"','\"',$message_detail->message);
			$sms_message = "<span style=\"font-size:90%; font-weight:bold;\">Start of SMS Message</span><br>"  .
			               str_replace("\n","<br>",htmlentities($message_detail->message,ENT_QUOTES,'UTF-8')) .
			               "<br><span style=\"font-size:90%; font-weight:bold;\">End of SMS Message</span>" ;

		    $params = new StdClass();
		    $params->id = "view_sent_message_$message_detail->id";
		    $params->type = 'image';
		    $params->image = 'note.gif';
		    $params->content=$message_detail->message;
		    $params->prefix_text = 'Start of SMS Message';
		    $params->postfix_text = 'End of SMS Message';
			$tooltip = $tooltip_manager->get($params);
 	        $scripts[] = $tooltip->script;
            $link = $tooltip->component;
		}

		#---Debugging Status---------------------------------------------------
		//echo "[$message_detail->delivery_status] [$message_detail->status]<br>";


		$message_status=$message_detail->delivery_status ? $message_detail->delivery_status
		                : $message_detail->status;
				

        #---Temp for pickup error number---------------------------------------
        #if ($message_detail->delivery_status=='DELIVRD') continue;
        #if ($message_detail->status=='dup-tag') continue;
        #if (!$message_detail->delivery_status and !$message_detail->status)  $count_error++;
        #echo "$count_error|$message_detail->phone|$member->first_name|$member->last_name<br>";

		$index=$base+$seq-1;
		$even_odd=$index++%2==0 ? 'even' : 'odd';
		$msg_content .= "<tr class='$even_odd'>\n" .
                        "<td align='center'>{$index}</td>\n" .
                        "<td>&nbsp;{$message_detail->phone}</td>\n" .
                        "<td>&nbsp;{$member->first_name}</td>\n" .
                        "<td>&nbsp;{$member->last_name}</td>\n" .
                        "<td>&nbsp;{$member->email}</td>\n" .
                        "<td>&nbsp;{$member->description}</td>\n" .
                        "<td align='center'>&nbsp;{$message_status}</td>\n" .
                        "<td align='center'>$link</td>\n" .
                        "</tr>\n";
			
      $seq++;
    }
    #echo $count_error;

    $all_scripts = join("\n",$scripts);

	$message = str_replace("\n","<br>&nbsp;",$dm_message->message);

	function __list_message_details_get_lang()
	{
	    $tk[] = 'message';
	    $tk[] = 'tag_name';
	    $tk[] = 'internal_remark';
	    $tk[] = 'including_tags';
	    $tk[] = 'excluding_tags';
	    $tk[] = 'message_header';
	    $tk[] = 'phone_abbr';
	    $tk[] = 'index';
	    $tk[] = 'status';
	    $tk[] = 'list_of_phone_numbers';
		$tk[] = 'first_name';
		$tk[] = 'last_name';
		$tk[] = 'email';
		$tk[] = 'description';

	    //$CI = &get_instance();
	    global $CI;
	    return $CI->message_bundle->get_lang_values($tk);

	}

	function __list_message_details_get_including_tag_names($tag_ids)
	{
		if ($tag_ids=='') return null;

		$tag_ids = explode("\n",$tag_ids);

	    $dm_tag = new DM_Tag();
	    $dm_tag->where_in('id',$tag_ids);
	    $dm_tag->get();

		$index=1;
		$tag_names = '';
		foreach ($dm_tag->all as $tag)
		{
	      	$tag_names .= "&nbsp;$index. {$tag->tag_name}<br>";
			$index++;
		}

		return $tag_names;
	}

	function __list_message_details_get_member_info($dm_message_detail)
	{
		global $CI;

		#---Note: whate happen when have phone but no user id

		#---Get all Ids first--------------------------------------------------
		foreach ($dm_message_detail->all as $message_detail)
		{
			$member_ids[]=$message_detail->user_id;
		}
		$member_ids = array_unique($member_ids);

		#---Retrieve data from DB----------------------------------------------
		$dm_user = new DM_User();
		$dm_user->where('customer_id',$CI->customer_id);
		$dm_user->where_in('id',$member_ids);
		$dm_user->get();

		#---Create return array------------------------------------------------
		#---Note: Index by user id---------------------------------------------
		$member_info = array();
		foreach ($dm_user->all as $dm_user)
		{
			$member_info[$dm_user->id]=$dm_user->stored;
		}
		return $member_info;
	}

	function __list_message_details_get_sort_option($sort_key=null)
	{
		$count = substr_count($sort_key,":");
		switch ($count)
		{
			case 2:
			  list($indicator,$type,$field) = explode(':',$sort_key);
			  $order = 'asc';
			  break;
			case 3:
			  list($indicator,$type,$field,$order) = explode(':',$sort_key);
			  break;
			default:
			  $field='default';
			  break;
		}

		$option = new StdClass();
		$option->indicator = $indicator;
		$option->type= $type;
		$option->field=$field;
		$option->order=$order;

		return $option;
	}

?>

<fieldset class='listing_box'>
<legend class='listing_legend'><?=$t->message_header?></legend>
<table border='0' cellspacing='1' cellpadding='1' width='100%'>
<tr>
  <th class='list1' width='14%'><?=$t->message?> ID</th>
  <td class='list2' width='36%'>&nbsp;<b><?=$dm_message->id?></b></td>
  <th class='list1' width='14%>'>&nbsp;<?=$t->internal_remark?>&nbsp;</th>
  <td class='list2' width='36%'>&nbsp;<?=$dm_message->remark?></td>
</tr>
<tr>
  <th class='list1'><?=$t->tag_name?>&nbsp;</th>
  <td class='list2'>&nbsp;<?=$dm_message->tag_name?></td>
  <th class='list1'>Limit Message&nbsp;</th>
  <td class='list2'>&nbsp;<?=$dm_message->limit_message?></td>
</tr>
<tr>
  <th class='list1'>Date/Time&nbsp;</th>
  <td class='list2'>&nbsp;<?=$dm_message->end_datetime?></td>
  <th class='list1'>Send Name&nbsp;</th>
  <td class='list2'>&nbsp;<?=$dm_message->sender_name?></td>
</tr>
<tr>
  <th class='list1'><?=$t->message?>&nbsp;</th>
  <td class='list2'><br>&nbsp;<?=$message?><br><br></td>
  <th class='list1'>&nbsp;</th>
  <td class='list2'>&nbsp;</td>
</tr>
<tr>
  <th class='list1'>&nbsp;<?=$t->excluding_tags?>&nbsp;</th>
  <td class='list2'><?=$excluding_tag_names?></td>
  <th class='list1'>&nbsp;<?=$t->including_tags?>&nbsp;</th>
  <td class='list2'><?=$including_tag_names?></td>

</tr>
<tr>
</tr>
</table>
</fieldset>

<br>
<div style='margin:0 1% 0 1%; border:1px none red;'>
<table class='list' border='0' cellspacing='1' cellpadding='1' width='100%'>
<caption><?=$t->list_of_phone_numbers?></caption>
<thead>
<tr id='list_heading'>
   <th style='width:6%; text-align:center;' ><?=$t->index?></th>
   <th style='width:10%; text-align:left;'>&nbsp;<?=$header_link->phone?></th>
   <th style='width:19%; text-align:left;'>&nbsp;<?=$t->first_name?></th>
   <th style='width:19%; text-align:left;'>&nbsp;<?=$t->last_name?></th>
   <th style='width:15%; text-align:left;'>&nbsp;<?=$t->email?></th>
   <th style='width:18%; text-align:left;'>&nbsp;<?=$t->description?></th>
   <th style='width:8%; text-align:center;'>&nbsp;<?=$header_link->status?></th>
   <th style='width:5%; text-align:center;'>Msg</th>
</tr>
</thead>
<?=$msg_content?>
</table>
<?=$pagination_link?>
</div>
<?=$all_scripts?>
