<?

  global $system_folder;

  function get_statistics_content($messages)
  {
  	  global $system_folder;
  	  require_once(APPPATH. "libraries/MonthlyStatistics.php");

	  //$message_result = $messages->get_result();

	  #---Monthly Statistics---------------------------------------------------
	  if (!empty($messages))
	  {
		  $year = substr($messages[0]->year_month,0,4);
	  	  $result = MonthlyStatistics::get_monthly_statistics($year);
		  $monthly_statistics = array();
		  foreach ($result as $record)
		  {
		  	 $data = new StdClass();
		  	 $data->customer_id = $record->customer_id;
		  	 $data->member_size = $record->member_size;
		  	 $data->group_size  = $record->group_size;
		  	 $monthly_statistics[$record->period] = $data;
		  }
	  }

      $content = '';
      $count_by_date = array();
      $message_ids = array();
 	  foreach ($messages as $record)
	  {
	  	 if (!isset($count_by_month[$record->year_month])) $count_by_month[$record->year_month]=0;
		 if (!isset($count_by_date[$record->year_month][$record->date])) $count_by_date["$record->year_month"][$record->date]=0;

         if (!isset($display_months[$record->year_month])) $display_months[$record->year_month] = $record->display_month;
		 $count_by_date[$record->year_month][$record->date]++;
		 $count_by_month[$record->year_month]++;
		 $message_ids[] = $record->id;
	  }

	  if (empty($message_ids)) return '';

	  #---Message Details--------------------------------------------------------------
	  $message_details = Messages::get_instance()->get_message_statistic_details($message_ids);
	  $detail_result = $message_details->get_result();

	  foreach ($detail_result->list as $record)
	  {
	  	  $year_month = substr($record->date,0,6);
		  if (!isset($message_count_by_months[$year_month])) $message_count_by_months[$year_month]=0;
		  if (!isset($message_count_by_dates[$record->date])) $message_count_by_dates[$record->date]=0;
	      $display_dates[$record->date] = $record->display_date;
	      $message_count_by_months[$year_month] += $record->count;
	      $message_count_by_dates[$record->date] += $record->count;
	  }

	  $content = '';

	  $no=0;
	  $total_month_message=0; $total_month_campaign=0;
 	  $total_day_message=0; $total_day_campaign=0;


	  foreach ($count_by_month as $month=>$month_count)
	  {
	  	 $message_count_by_month = $message_count_by_months[$month];
	  	 $total_month_message  += $message_count_by_months[$month];
	  	 $total_month_campaign += $month_count;
	  	 $display_month = $display_months[$month];

	  	 $monthly_member_size = isset($monthly_statistics[$month]) ? $monthly_statistics[$month]->member_size : 0;
	  	 $monthly_group_size  = isset($monthly_statistics[$month]) ? $monthly_statistics[$month]->group_size  : 0;

         foreach ($count_by_date[$month] as $date=>$date_count)
         {
        	@$message_count_by_date = +$message_count_by_dates[$date];
        	$total_day_campaign += $date_count;
        	@$total_day_message  += $message_count_by_dates[$date];

        	if (isset($display_dates[$date]))
        	{
        		$display_date = $display_dates[$date];
        	}
        	else
        	{
        		 //	$display_date = substr($date,6,2) . "-" . substr($date,4,2) . "-" . substr($date,0,4);
        	}


			$content .= "<tr>
			          		<td align='center'>$display_month</td>
			          		<td align='right'>&nbsp;$month_count&nbsp;</td>
			          		<td align='right'>$message_count_by_month&nbsp;</td>
			          		<td align='right'>$monthly_member_size&nbsp;</td>
			          		<td align='right'>$monthly_group_size&nbsp;</td>
			          		<td align='center'>&nbsp;$display_date&nbsp;</td>
			          		<td align='right'>&nbsp;$date_count&nbsp;</td>
			          		<td align='right'>&nbsp;$message_count_by_date&nbsp;</td>
			          	    <td></td>
					      </tr>\n";
		    $month='&nbsp;';
		    $month_count='&nbsp;';
		    $message_count_by_month = '';
		    $display_month='';
   	  	    $monthly_member_size = '';
	  	    $monthly_group_size  = '';
         }
         $content .= "<tr><td colspan='9'>&nbsp;</td></tr>\n";
	  }
	  $content .= "<tr style='font-weight:bold;'>
	  		        <td style='text-align: right;'>Total&nbsp;</td>
	  		        <td style='text-align: right;'>$total_month_campaign&nbsp;</td>
	  		        <td style='text-align: right;'>$total_month_message&nbsp;</td>
	  		        <td>&nbsp;</td>
	  		        <td>&nbsp;</td>
	  		        <td style='text-align: right;'>Total&nbsp;</td>
   	  		        <td style='text-align: right;'>$total_day_campaign&nbsp;</td>
   	  		        <td style='text-align: right;'>$total_day_message&nbsp;</td>
   	  		        <td>&nbsp;</td>
	  		       </tr>";

	  return $content;
  }

      require_once(APPPATH. "libraries/Messages.php");

	  #---Message--------------------------------------------------------------
      $messages = new Messages();
      //$messages->auto_count(false);
      $result = $messages->get_message_statistic($params);
	  $content = get_statistics_content($result);
?>
<!--div style='margin:0 1% 0 1%; border:1px none red; width: 98%''-->
<fieldset class='listing_box'>
<legend class='listing_legend'>Statistics</legend>
<table id='list' border='0' cellspacing='1' cellpadding='1' width='100%''>
<tr id='list_heading'>
 <th align='center'style='width:16%;'>&nbsp;Month/Year&nbsp;</th>
 <th align='right' style='width:8%;'>&nbsp;Blast&nbsp;</th>
 <th align='right' style='width:12%;'>&nbsp;Messages&nbsp;</th>
 <th align='right' style='width:12%;'>&nbsp;Members&nbsp;</th>
 <th align='right' style='width:10%;'>&nbsp;Groups&nbsp;</th>
 <th align='center' style='width:15%;'>---Date---</th>
 <th align='right' style='width:8%;'>&nbsp;Blast&nbsp;</th>
 <th align='right' style='width:12%;'>&nbsp;Messages&nbsp;</th>
 <th>&nbsp;</th>
</tr>
<?=$content?>
</table>
</fieldset>
</!--div-->