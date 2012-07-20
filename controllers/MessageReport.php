<?php

class MessageReport
{
  const OUTPUT_DIR = '../../file_drop/kompis/message_files/';

  static function get_message_header($method,$action)
  {
  	global $system_folder;
    require_once(APPPATH. "libraries/Messages.php");
    $messages = Messages::get_instance()->get_unprocessed_messages();

	die('MessageReport:get_messge_header');

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
    $t = Register::get_instance()->get_property('message_bundle')->get_lang_values($tk);

    $message_ids = array();
    $result = $messages->get_result();
    foreach ($result->list as $message) $message_ids[] = $message->id;

    if (empty($message_ids))
       return "<h2>--- No Message Available ---</h2>";

    //$counts = M_essageDetails::get_count($message_ids);

   	$dm_message_detail = new DM_Message_Detail();
   	$success = $dm_message_detail->get_success_count($message_ids);
   	$reject  = $dm_message_detail->get_rejected_count($message_ids);


    $content = '';
    foreach ($result->list as $message)
    {
      $message->limit = $message->limit_message == 0 ? '&nbsp;' : $message->limit_message;
      $message->tag_name = $message->tag_name ?  $message->tag_name : '&nbsp;';

      $message->start_datetime   = substr($message->start_datetime,0,16);
      $message->start_process_datetime = $message->start_process_datetime ?  $message->start_process_datetime : '&nbsp;';
      $message->start_process_datetime = substr($message->start_process_datetime,0,16);

      //$count      = $counts[$message->id]['success'];
      //$rejected   = $counts[$message->id]['rejected'];
      $count = $success[$message->id];
      $rejected = $success[$message->id];

      $info_text  = $message->remark<>'' ? $message->remark : $t->no_remarks;
      $base_url   = base_url();
      $link_action="<a href='{$base_url}index.php/message_controller/$method/$message->id'>{$t->$action}</a>";
      $link_id    = "<span onmouseover=\"return overlib('$info_text',ABOVE);\" onmouseout='return nd();'><b>$message->id</b></span>";

      $content .=
               "<tr>
        <td class='list1' style='text-align: right;'>$link_id&nbsp;</td>
           <td class='list1' style='text-align: left;'>$message->limit</td>
          <td class='list1' style='text-align: left;'>$message->tag_name</td>
           <td class='list1' style='text-align: left;'>$message->start_datetime</td>
            <td class='list1' style='text-align: left;'>$message->start_process_datetime</td>
            <td class='list1' style='text-align: right;'>$count&nbsp;</td>
            <td class='list1' style='text-align: right;'>$rejected&nbsp;</td>
           <td class='list1' style='text-align: center;'>$link_action</td>
        </tr>\n";
    }

    return "<table border='1' cellspacing='0' cellpadding='0'>
            <tr>
         <th class='list1' style='text-align: center;'>$t->id</th>
         <th class='list1' style='text-align: left;'>$t->limit</th>
         <th class='list1' style='text-align: left;'>$t->tag</th>
         <th class='list1' style='text-align: left;'>$t->created_date</th>
         <th class='list1' style='text-align: left;'>$t->processed_date</th>
            <th class='list1' style='text-align: right;'>$t->success_count</th>
            <th class='list1' style='text-align: right;'>$t->rejected_count</th>
            <th class='list1' style='text-align: right;'>$t->action</th>
          </tr>
          $content
            </table>";
  }

  public static function get_message_header_for_simple_view()
  {
    $t_title = Register::get_instance()->get_property('message_bundle')->get_lang_value('view_message_header');

    return  "<h2>$t_title</h2><br/>\n" .
          self::get_message_header('message_view_show_header','view');
  }

  public static function get_message_header_for_detailed_view()
  {
    $t_title = Register::get_instance()->get_property('message_bundle')->get_lang_value('view_message_detail');
    return "<h2>$t_title</h2><br/>\n" .
         self::get_message_header('message_view_show_details','detail');
  }

  public static function get_message_header_for_file_view()
  {
    $t_title = Register::get_instance()->get_property('message_bundle')->get_lang_value('view_message_file');
    return "<h2>$t_title</h2><br/>\n" .
           self::get_message_header('message_view_show_file_details','view_file');
  }

  public static function get_message_details($message_id)
  {
    $MD = new MessageDetails();
    $message_details = $MD->get_messages($message_id);

    $tk[] = 'message_detail';
    $tk[] = 'message_id';
    $tk[] = 'unique_number';
    $tk[] = 'phone';
    $tk[] = 'user_id';
    $tk[] = 'insert_date';
    $tk[] = 'process_date';
    $tk[] = 'status';
    $t  = Register::get_instance()->get_property('message_bundle')->get_lang_values($tk);

    $content = '';
    foreach  ($message_details as $seq=>$message_detail)
    {
      $unique_id = $message_detail->unique_id <1 ? '' : $message_detail->unique_id;
      $index = $seq + 1;
      $content .= "<tr>\n"  .
                  "<td class='list1'>&nbsp;$index</td>\n" .
                    "<td class='list1'>&nbsp;$unique_id</td>\n" .
                    "<td class='list1'>&nbsp;$message_detail->phone</td>\n" .
                   "<td class='list1' align='center'>&nbsp;$message_detail->user_id&nbsp;</td>\n" .
                    "<td class='list1' >&nbsp;$message_detail->inserted_datetime</td>\n" .
                    "<td class='list1' >&nbsp;$message_detail->processed_datetime</td>\n" .
                   "<td class='list1' >&nbsp;$message_detail->status</td>\n" .
                  "<tr>\n";
    }

    return "<h2>$t->message_detail&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$t->message_id : $message_id</h2><br/>
         <table border='1' cellspacing=0 cellpadding=0>
           <tr>
           <th class='list1'>$t->message_id</th>
           <th class='list1'>$t->unique_number</th>
           <th class='list1'>$t->phone</th>
           <th class='list1' align='center'>$t->user_id</th>
           <th class='list1'>$t->insert_date</th>
           <th class='list1'>$t->process_date</th>
           <th class='list1'>$t->status</th>
         </tr>
         $content
         </table>";
  }

  public static function get_message_header_details($message_id)
  {
    $tk[] = 'message_id';
    $tk[] = 'customer_id';
    $tk[] = 'limit';
    $tk[] = 'remark';
    $tk[] = 'tag_name';
    $tk[] = 'tag';
    $tk[] = 'is_new_tag';
    $tk[] = 'transmission_file';
    $tk[] = 'start_insert_datetime';
    $tk[] = 'end_insert_datetime';
    $tk[] = 'start_process_datetime';
    $tk[] = 'end_process_datetime';
    $tk[] = 'ftp_status';
    $tk[] = 'message';
    $tk[] = 'including_tags';
    $tk[] = 'excluding_tags';
    $tk[] = 'including_phone_list';
    $tk[] = 'excluding_phone_list';

    $t = Register::get_instance()->get_property('message_bundle')->get_lang_values($tk);

    $entity_message = new Message($message_id);

    $view_new_tag = $entity_message->property->is_new_tag_flag ? 'Yes' : 'No';
    $view_limit   = $entity_message->property->limit == 0 ? "" : $entity_message->property->limit;
    $view_tag_id  = $entity_message->property->tag_id>0 ? $entity_message->property->tag_id : '&nbsp;';

    $excluding_phone_list = $entity_message->property->excluding_phone_list ? $entity_message->property->excluding_phone_list : '&nbsp;';
    $including_phone_list = $entity_message->property->including_phone_list ? $entity_message->property->including_phone_list : '&nbsp;';

    $message_bundle = Register::get_instance()->get_property('message_bundle');
    $title_text     = $message_bundle->get_lang_value('message_header');
    $table = "<h2>$title_text</h2><br>
         <table border='1' cellspacing='0' cellpadding='0'>
         <tr>
         <th class='list1' align='right'>$t->message_id&nbsp;</th>
         <td class='list1'>&nbsp;{$entity_message->property->id}</td>
         </tr>
         <tr>
         <th class='list1' align='right'>&nbsp;$t->customer_id&nbsp;</th>
         <td class='list1'>&nbsp;{$entity_message->property->customer_id}</td>
         </tr>
         <tr>
         <th class='list1' align='right'>&nbsp;$t->limit&nbsp;</th>
         <td class='list1'>&nbsp;{$view_limit}</td>
         </tr>
          <th class='list1' align='right'>&nbsp;$t->remark&nbsp;</th>
         <td class='list1'>&nbsp;{$entity_message->property->remark}</td>
         </tr>
         <tr>
         <th class='list1' align='right'>$t->tag_name&nbsp;</th>
         <td class='list1'>&nbsp;{$entity_message->property->tag_name}</td>
         </tr>
         <tr>
         <th class='list1' align='right'>$t->tag&nbsp;</th>
         <td class='list1'>&nbsp;{$view_tag_id}</td>
         </tr>
         <tr>
         <th class='list1' align='right'>$t->is_new_tag&nbsp;</th>
         <td class='list1'>&nbsp;{$view_new_tag}</td>
         </tr>
         <tr>
         <th class='list1' align='right'>$t->transmission_file&nbsp;</th>
         <td class='list1'>&nbsp;{$entity_message->property->filename}</td>
         </tr>
          <tr>
         <th class='list1' align='right'>$t->start_insert_datetime&nbsp;</th>
         <td class='list1'>&nbsp;{$entity_message->property->start_datetime}</td>
         </tr>
         <tr>
         <th class='list1' align='right'>$t->end_insert_datetime&nbsp;</th>
         <td class='list1'>&nbsp;{$entity_message->property->end_datetime}</td>
         </tr>
         <tr>
         <th class='list1' align='right'>$t->start_process_datetime&nbsp;</th>
         <td class='list1'>&nbsp;{$entity_message->property->start_process_datetime}</td>
         </tr>
         <tr>
         <th class='list1' align='right'>$t->end_process_datetime&nbsp;</th>
         <td class='list1'>&nbsp;{$entity_message->property->end_process_datetime}</td>
         </tr>
         <tr>
         <tr>
         <th class='list1' align='right'>$t->ftp_status&nbsp;</th>
         <td class='list1'>&nbsp;{$entity_message->property->ftp_status}</td>
         </tr>
         <tr>

         <th class='list1' align='right'>$t->message&nbsp;</th>
         <td class='list1'>&nbsp;{$entity_message->property->message}</td>
         </tr>
         <tr>
         <th class='list1' align='right'>$t->including_tags&nbsp;</th>
         <td class='list1'>&nbsp;{$entity_message->property->including_tags}</td>
         </tr>
          <tr>
         <th class='list1' align='right'>$t->excluding_tags&nbsp;</th>
         <td class='list1'>&nbsp;{$entity_message->property->excluding_tags}</td>
         </tr>
         <tr>
         <th class='list1' align='right'>$t->including_phone_list&nbsp;</th>
         <td class='list1'>{$including_phone_list}</td>
         </tr>
          <tr>
         <th class='list1' align='right'>$t->excluding_phone_list</th>
         <td class='list1'>{$excluding_phone_list}</td>
         </tr>
         </table>\n";

    return $table;
  }

  public static function get_message_file_content($message_id)
  {
    $tk[] = 'view_message_file_content';
    $tk[] = 'filename';
    $tk[] = 'no_file_content';
    $tk[] = 'error';
    $t = Register::get_instance()->get_property('message_bundle')->get_lang_values($tk);

    $entity_message = new Message($message_id);
    $filename = $entity_message->property->filename;

    $handle = @fopen(self::OUTPUT_DIR . $filename,'r');

    if (!$handle)
    {
      return "<h2>$t->view_message_file_content</h2>
              <h2>$t->filename : $filename</h2>
              <h2>$t->error : $t->no_file_content !!!</h2>";
    }

    $linecount=1;
    $last_split="";
    $content='';
    while (!feof($handle))
    {
      $line       = $last_split . fgets($handle,100000);
      if ($line=='') continue;
      $splits     = explode('#EOR#',$line);
      $split_size = count($splits);
      for ($index=0; $index<$split_size-2; $index++)
      {
        $line = $splits[$index];
        $content .= "{$line}#EOR#<br>";
      }

      @$last_split = $splits[$index] . '#EOR#' . $splits[$index+1];
      $linecount++;
    }

    $splits     = explode('#EOR#',$last_split);
    foreach ($splits as $split)
    {
      if (strlen($split)<=1) continue;
      $content .= "$split#EOR#<br>";
    }

    if ($content=='') $content="<h2>***** {$t->no_file_content}. *****</h2>";
    else $content = "<br/>$content";

    return "<h2>$t->view_message_file_content</h2>
            <h2>$t->filename : $filename</h2>
            $content";
  }

}

?>