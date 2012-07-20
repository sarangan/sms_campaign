<?php

    $dm_message = new DM_Message();
    $dm_message->where('customer_id',$this->customer_id);
    $dm_message->order_by('id desc');
    $dm_message->get();

	if ($current_page<1) $current_page=1;

	$count = $dm_message->where('customer_id',$this->customer_id)->count();
	$p = get_default_pagination();
	//$p->page_size=3;
	$p->offset = $p->page_size*($current_page-1);
	$p->current_page = $current_page;
	$p->page_total = $count;
	$p->controller= 'message_controller';
	$p->action='process_form';
	$pagination_link=get_pagination($p);

    $dm_message->where('customer_id',$this->customer_id);
    $dm_message->order_by('id desc');
    $dm_message->get($p->page_size,$p->offset);

	$view['pagination']=$pagination_link;

    $tk[] = 'id';
    $tk[] = 'limit';
    $tk[] = 'tag';
    $tk[] = 'created_date';
    $tk[] = 'processed_date';
    $tk[] = 'success_count';
    $tk[] = 'rejected_count';
    $tk[] = 'action';
    $tk[] = 'no_remarks';
    $tk[] = $action;
    $tk[] = $title;

	$CI = &get_instance();
	$t = $CI->message_bundle->get_lang_values($tk);
	$list_title = $t->$title;


    $message_ids = array();
	foreach ($dm_message->all as $message)
	{
		$message_ids[] = $message->id;
	}

    if (empty($message_ids))
       return "<h2>--- No Message Available ---</h2>";

    //$counts = M_essageDetails::get_count($message_ids);
    //pre($counts);

    $dm_message_detail = new DM_Message_Detail();
    $success = $dm_message_detail->get_success_count($message_ids);
    $reject  = $dm_message_detail->get_rejected_count($message_ids);

    $content = '';
    //foreach ($result->list as $message)
    foreach ($dm_message->all as $message)
    {
      $message->limit = $message->limit_message == 0 ? '&nbsp;' : $message->limit_message;
      $message->tag_name = $message->tag_name ?  $message->tag_name : '&nbsp;';

      $message->start_datetime   = substr($message->start_datetime,0,16);
      $message->start_process_datetime = $message->start_process_datetime ?  $message->start_process_datetime : '&nbsp;';
      $message->start_process_datetime = substr($message->start_process_datetime,0,16);

      //$count      = $counts[$message->id]['success'];
      //$rejected   = $counts[$message->id]['rejected'];
      $count = $success[$message->id];
      $rejected = $reject[$message->id];

      $info_text  = $message->remark<>'' ? $message->remark : $t->no_remarks;
      $base_url   = base_url();
      $link_action="<a href='{$base_url}index.php/message_controller/$method/$message->id'>{$t->$action}</a>";
      $link_id    = "<span onmouseover=\"return overlib('$info_text',ABOVE);\" onmouseout='return nd();'><b>$message->id</b></span>";

      $content .= "<tr>
        <td style='text-align: center;'>$link_id&nbsp;</td>
           <td style='text-align: center;'>$message->limit</td>
          <td style='text-align: left;'>$message->tag_name</td>
           <td style='text-align: left;'>$message->start_datetime</td>
            <td style='text-align: left;'>$message->start_process_datetime</td>
            <td style='text-align: right;'>$count&nbsp;</td>
            <td style='text-align: right;'>$rejected&nbsp;</td>
           <td style='text-align: center;'>$link_action</td>
        </tr>\n";
    }
?>
<table id='list' border='0' cellspacing='0' cellpadding='0' width='100%'>
<tr id='list_title'>
  <th colspan='8' style='text-align:left;'><?=$list_title?></th>
</tr>
<tr>
 <th colspan='8' style='text-align:left;'><hr style='border-style: dotted; color: black;'></th>
</tr>
<tr id='list_heading'>
 <th style='text-align: center;'><?=$t->id?></th>
 <th style='text-align: center;'><?=$t->limit?></th>
 <th style='text-align: left;'><?=$t->tag?></th>
 <th style='text-align: left;'><?=$t->created_date?></th>
 <th style='text-align: left;'><?=$t->processed_date?></th>
 <th style='text-align: right;'><?=$t->success_count?>&nbsp;</th>
 <th style='text-align: right;'><?=$t->rejected_count?>&nbsp;</th>
 <th style='text-align: center;'>&nbsp;<?=$t->action?></th>
</tr>
  <?=$content?>
</table>
<?=$pagination_link?>
