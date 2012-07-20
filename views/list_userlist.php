<?

	#---Get language text------------------------------------------------------
	$tk[] = 'phone_abbr';
	$tk[] = 'first_name';
	$tk[] = 'last_name';
	$tk[] = 'description';
	$tk[] = 'email';
	$tk[] = 'status';
	$tk[] = 'action';
	$t = $this->message_bundle->get_lang_values($tk);

	#---Generate the Content---------------------------------------------------
	$content = '';
	$index=1;
	foreach ($dm_user->all as $user)
	{
		$odd_even=$index++%2==0 ? 'even' : 'odd';
		$checkbox = "<input type='checkbox' name='user_ids[]' value='$user->id'/>";
		$content .= "<tr class='$odd_even' style='text-align:center;'>\n" .
                    "<td>$checkbox</td>" .
                    "<td>$user->phone</td>" .
                    "<td style='text-align:left;'>$user->first_name</td>" .
                    "<td style='text-align:left;'>$user->last_name</td>" .
                    "<td style='text-align:left;'>$user->email</td>" .
                    "<td style='text-align:left;'>$user->description</td>" .
                    "</tr>\n";
	}
	$base_url=base_url();
?>
<div style='margin:0 1% 0 1%; border:1px none red;'>
   <form  class='checkbox_handler' name='form_list_of_userlist' method='post' action='<?echo base_url()?>index.php'>
   <table class='list' border='0' cellspacing='0' cellpadding='0' style='width:100%;'>
   <caption>Current selected Members</caption>
   <input type='hidden' name='__form_name__' value='userlsit_edit_list' />
   <input type='hidden' name='tag_id' value='$tag_id' /> <!--required for group listing-->
   <input type='hidden' name='userlist_key_id' value='<?=$userlist_key_id?>' />
   <input type='hidden' name='userlist_callback_uri' value='<?=$userlist_callback_uri?>' />
   <input type='hidden' name='page_number' value='<?=$page_number?>' />
   <thead>
   <tr id='list_heading'>
   <th style='text-align: left; width:5%;'>&nbsp;</th>
   <th style='text-align: center; width:12%;'><?=$t->phone_abbr?></th>
   <th style='text-align: left; width:20%;'>&nbsp;<?=$t->first_name?></th>
   <th style='text-align: left; width:20%;'>&nbsp;<?=$t->last_name?></th>
   <th style='text-align: left; width:20%;'>&nbsp;<?=$t->email?></th>
   <th style='text-align: left; width:*%;'>&nbsp;<?=$t->description?>&nbsp;</th>
   </tr>
   </thead>
   <?=$content?>
   <tr>
	 <td style='vertical-align:bottom; padding-left:0px; text-align: center; cellspacing:2px;'>
 	 	&nbsp;&nbsp;<img border='0' src='<?echo base_url()?>/img/checkbox_indicator.png' style='vertical-align:bottom;'/>
     </td>
     <td colspan='5'>
 	   <input type='button' class='checkbox_handler_checkall' class='small_submit_button' value='Check All'/>
 	   <input type='button' class='checkbox_handler_uncheckall' class='small_submit_button' value='Uncheck All'/>
 	   <input type='button' class='checkbox_handler_remove_from_list' class='small_submit_button' value='Remove' disabled/>
 	   <input type='button' class='checkbox_handler_back' class='small_submit_button' value='Back'/>
 	   <input type='button' class='checkbox_handler_send' class='small_submit_button' value='Send'/>
    </td>
   </tr>
   </table>
   </form>
   <?=$pagination_link?>

   </div><br>