<?php
require_once(APPPATH. "libraries/MessageBundleManager.php");

class TagListingHelper
{
		
	private $updown ='';
	function __construct($users,$links,StdClass $pagination, $updown)
	{
		
		$this->updown =  $updown;
            
            
            
		$this->params = $users;
		$this->links = $links;
		$this->pagination = $pagination;

		$this->create_checkbox = ($pagination->name=='tag_list') ? true : true;
		$this->factory = HtmlElementFactory::get_instance();
		$this->CI = &get_instance();

		$this->CI->load->library('HeaderLinkManager');

		global $system_folder;
	   	
		#---Determine Colspan--------------------------------------------------------------
		$colspan = $pagination->name=='search_members' ? 6 : 5;
		$this->show_status= $pagination->name=='search_members' ? true : false;
		$this->link_size = $links ? count($links) : 5;
		$this->colspan += $colspan + $this->link_size;
		$this->colspan += $this->create_checkbox ? 1 : 0;
	}

	


	function get()
	{
		
		#---return nothing if no data available--------------------------------
		if (count($this->params->users)==0) return null;

		#---Core Process-------------------------------------------------------
		$this->get_lang_text();
		$this->form_title = $this->get_form_title();

		$result=$this->get_content();
		$this->content = $result->content;
		#$this->javascripts=$result->javascripts;
		$checkbox_control = $this->get_checkbox_control();

		#---Retrieve checkboxes (if any)---------------------------------------
		$this->checkboxes = $checkbox_control->checkboxes;
		$this->first_column_header = $checkbox_control->first_column;

		#---Finally, return HTML content----------------------------------------
		return $this->get_final_content();
	}


	function get_final_content()
	{
		#----required for group listing----------------------------------------
		$tag_id_textfield = '';
		if (isset($this->params->tag_id))
		{
			$tag_id_textfield = "<input type='hidden' name='tag_id' value='{$this->params->tag_id}' />\n";
		}

		$t = $this->lang_text;
		$base_url = base_url();
		@$pagination_link = $this->params->pagination_link;
		#----------------------------------------------------------------------
		$status_header =  $this->show_status=='' ? '' : "<th style='text-align: center;'>&nbsp;$t->status&nbsp;</th>\n";
		//<form  class='checkbox_handler' name='form_list_of_member' method='post' action='{$base_url}index.php/{$this->users->controller}/remove_members'>

		$worklist_id = $this->CI->session->userdata($this->params->userlist_key_id);

		if ($this->pagination->form_title)
		{
			$form_title = $this->lang_text->{$this->pagination->form_title};
			$break = '';
		}
		else
		{

		    $form_title = 'Group Member Listing';
			$break = "<br>";
		}

		$header_data = array();
		$header_data[] = array('field'=>'group','text'=>$t->group);
		$header_data[] = array('field'=>'description','text'=>$t->description);
                $header_data[] = array('field'=>'modified_tag_caption','text'=>$t->modified_tag_caption);
                $header_data[] = array('field'=>'modified_tag_des_caption','text'=>$t->modified_tag_des_caption);

		$header_link_manager = new HeaderLinkManager($this->pagination);
		$header_link_manager->set_default_key('group');
		$header_link_manager->set_total_record($this->params->total);
		$header_link = $header_link_manager->get_header_link($header_data);

		$browser = $_SERVER['HTTP_USER_AGENT'];
		$legend_class = strstr($browser,'Opera') ? 'listing_box_no_border' : 'listing_box';

		$listing_footer = get_listing_footer($this->params->total,$pagination_link);
        $button_scripts = join("\n",$this->scripts);
	

		$html="<div class='list'><!--legend  class='listing_legend'>$form_title</legend-->
			   <!--div style='margin:0 1% 0 1%; border:1px none red;'-->
			   <form  class='checkbox_handler' name='form_list_of_member' method='post' action='{$base_url}index.php/'>
	           $tag_id_textfield
	           <input type='hidden' name='userlist_key_id' value='{$this->params->userlist_key_id}' />
		       <input type='hidden' name='userlist_callback_uri' value='{$this->params->userlist_callback_uri}' />
			   <table class='list' border='0' cellspacing='1' cellpadding='1' style='width:100%;'>
			   <caption>$form_title</caption>
			   <thead>
	           <tr>
	           	   $this->first_column_header
			       <th style='text-align: left; width:10%;'>&nbsp;$header_link->group</th>
			       <th style='text-align: left; width:20%;'>&nbsp;$header_link->description&nbsp;</th>
                               <th style='text-align: left; width:10%;'>&nbsp;$header_link->modified_tag_caption</th>
                               <th style='text-align: left; width:10%;'>&nbsp;$header_link->modified_tag_des_caption&nbsp;</th>
			   </tr>
			   </thead>
			   <tbody>
		       $this->content
		       </tbody>
			   $this->checkboxes
			   </table>
			   <div style='text-align:center; margin:0; padding:0'>
			   $listing_footer
		       </div>
			   </form>
			   </div>
               $button_scripts";


		#---Result Single User Result------------------------------------------
	    $result = new StdClass();
	    $result->html_table = $html;
	    $result->search_result = null;
		$result->is_empty = empty($list);

	    if (sizeof($this->params->users)==1 and isset($users->all[0]))
	    {
			$return->search_result = $users->all[0];
	    }
	    return $result;

	}

	function get_lang_text()
	{
		#---Get Text For Language----------------------------------------------
		$tk[] = 'id';
		$tk[] = 'group';
		$tk[] = 'description';
                $tk[] = 'modified_tag_caption';
                $tk[] = 'modified_tag_des_caption';
		if ($this->pagination->form_title) $tk[] = $this->pagination->form_title;
		$this->lang_text = $this->CI->message_bundle->get_lang_values($tk);
	}

	function get_form_title()
	{
		$form_title = '';
		if ($this->pagination->form_title)
		{
			$t->form_title = $this->lang_text->{$this->pagination->form_title};
			$form_title="<tr>
	                       <th colspan='$this->colspan' style='text-align:left;'>$t->form_title</th>
			             </tr>
			             <!--tr>
	                       <th colspan='$this->colspan' style='text-align:left;'><hr style='border-style: dotted; color: black; width:100%;'></th>
			             </tr-->";
		}
		return $form_title;
	}

	function get_content()
	{
	    if ($this->links=="")
		{
	        $have_inactive_member = false;
			foreach($this->params->users as $index=>$user)
			{
				$status = $user->active_flag . $user->deleted_flag;
			    if ($status=='00') $have_inactive_member = true;
			}
		}

		#---Tag Name-----------------------------------------------------------
        $tag_name='';
        if (isset($this->params->users[0]))
        {
		  $first_user = $this->params->users[0];
		  $dm_tag = new DM_Tag();
		  $dm_tag->where('id',$first_user->id)->get();
		  $tag_name = $dm_tag->tag_name;
        }

		$content = '';
		$all_javascripts = array();
		$index=0;
		foreach($this->params->users as $user)
		{
			#---Basic Information----------------------------------------------
			$user_id = isset($user->user_id) ? $user->user_id : $user->id;
			#$tag_id  = isset($user->tag_id)  ? $user->tag_id  : "";
			$tag_id = isset($user->id) ? $user->id : "";
			$tag_name = $user->tag_name;
			$description  = $user->description;
			

			#---Only call this when links is not provided----------------------
			#---Only search called this----------------------------------------
			if ($this->links=="")
			{
				$link_status = $this->create_links($user,$have_inactive_member);
				$links  = $link_status->links;
				$status = "<td style='text-align:center; width:6%'>" .
				          $link_status->status .
				          "</td>";
			}
			else
			{
				$links = $this->links;
				$status = '';
			}

			#$result = $this->get_link_anchors($links,$phone_number,$user,$tag_id,$tag_name);
			#$anchors = $result->anchors;
			#$all_javascripts = array_merge($all_javascripts,$result->javascripts);

			$all_links='';
			/*foreach ($anchors as $anchor)
			{
				$all_links .= "<td style='text-align:center; width:3%'>" .
				      $anchor .
				      "</td>";
			}*/

			$first_column = "";
			$block =  true;
			if ($this->create_checkbox)
			{
			  # $is_blocked = $this->blocked_phones->is_blocked_by_phone($phone_number) ? true : false;
			   $chkstatus ='';

			  
			   
			  /* $temp_worklist_id = $this->CI->session->userdata('__session_group_user_list__');
			   #---Determine if user ids already in the list--------------------------
			   $temp_dm_worklist_detail = new DM_WorkList_Detail();
			   $temp_dm_worklist_detail->where('worklist_id',$temp_worklist_id);
			   $temp_dm_worklist_detail->where_in('entity_id',$user_id);
			 //  $temp_dm_worklist_detail->where_in('worklist_id',$temp_worklist_id);
			   $temp_dm_worklist_detail->get();*/
			   
			   
			   
			   if(isset($_COOKIE['tempgrp'])) {
			   $tempgrparr = explode("*",$_COOKIE['tempgrp']);
			  // error_log($tempgrparr);
			   foreach($tempgrparr as $row){
				if(isset($row))
				if($row == $user_id)
				   $chkstatus='checked';
			   }
			   
			   }
			   
			   
			  /*  foreach ($temp_dm_worklist_detail->all as $temp_worklist_detail)
			    {
				//error_log($temp_worklist_detail->worklist_id ." - ".$temp_worklist_detail->entity_id);
				if($temp_worklist_detail->entity_id == $user_id)
					$chkstatus='checked';
				   
			    }*/
			   //error_log('worklist' . $temp_worklist_id);
			  
			   $unblock_checkbox = "<input name='user_ids[]' id='user_id_$index' value='$user_id' type='checkbox' {$chkstatus}/>&nbsp;&nbsp;&nbsp;";
			   
		           //$unblock_checkbox = "<input name='user_ids[]' id='user_id_$index' value='$user_id' type='checkbox'/>&nbsp;&nbsp;&nbsp;";


				   $first_column =  "<td style='text-align:center; padding-left: 0px;'>" .
	                                $unblock_checkbox .
	                                "</td>\n";
					//error_log($unblock_checkbox); // this is where we should care about the checking the box
			}

            $odd_even=$index++%2==0?'even':'odd';
            
            $shiftedtext = $this->get_ShiftText($tag_name);
            $shifteddes = $this->get_ShiftText($description);
            
            
         

	        $content .= "<tr class='$odd_even'>\n".
						$first_column .
                            "<td style='text-align: left;'>&nbsp;$tag_name&nbsp;</td>\n" .
	                    "<td style='text-align: left;'>&nbsp;$description&nbsp;</td>\n" .
                            # "<td style='text-align: left;'><label for='user_id_$index'>&nbsp;$tag_id</label></td>\n" .
                            # "<td style='text-align: left;'><label for='user_id_$index'>&nbsp;".  $this->get_ShiftText($tag_name) ."</label></td>\n" .
                            "<td style='text-align: left;'><textarea style='width:100%;height:100%;margin:0;padding:0; font-size: 0.8em; color: #333333; font-family: Verdana,Arial,Helvetica,sans-serif; ' class='$odd_even' rows='1' cols='1' name='shift_tags[$user_id]' id='$shiftedtext'>".  $shiftedtext ."</textarea></td>\n" .
                             "<td style='text-align: left;'><textarea style='width:100%;height:100%;margin:0;padding:0; font-size: 0.8em; color: #333333; font-family: Verdana,Arial,Helvetica,sans-serif; ' class='$odd_even' rows='1' cols='1' name='shift_des[$user_id]' id='$shifteddes'>".  $shifteddes ."</textarea></td>\n" .
	                    "</tr>\n";
	    }
	    $result=new StdClass();
	   # $result->javascripts=join("\n",$all_javascripts);
	    $result->content=$content;
	    return $result;
	    //return $content . $javascripts;
	}
        
        
        function get_ShiftText($tag_name)
        {
                     
            $shiftText =$tag_name;
            
            $chk = is_numeric(substr($tag_name, 0, 1));
            if($chk)
            {
                $temp = preg_replace('/^(\d+).*$/', "$1", $tag_name); #intval(substr($tag_name, 0, 1));
                if($this->updown == 'upgrade_button')
                    $rep = intval($temp) + 1 ;
                else
                    $rep = intval($temp) - 1 ;
                    
                #$shiftText =  preg_replace('/abc/', '123', $var, 1);
                $temp = '\''. $temp . '\'';
                $rep  = '\'' . $rep .  '\'';
                $shiftText = preg_replace($temp ,  $rep, $tag_name, 1);
                
                $shiftText = str_replace("'", "", $shiftText);
                                
               # $str = preg_replace('/^(\d+).*$/', "$1", $str);
                    
            }
            elseif(preg_match('/^[a-zA-Z]/', $tag_name)) {
                
                $chk = is_numeric(substr($tag_name, 1, 1));
                if($chk)
                {
                    $substr =  substr($tag_name, 1);
                    $temp = preg_replace('/^(\d+).*$/', "$1", $substr);
                     if($this->updown == 'upgrade_button')
                        $rep = intval($temp) + 1 ;
                    else
                        $rep = intval($temp) - 1 ;
                        
                    $temp = '\''. $temp . '\'';
                    $rep  = '\'' . $rep .  '\'';
                    $shiftText = preg_replace($temp ,  $rep, $substr, 1);
                    $shiftText =  substr($tag_name, 0, 1) . $shiftText ;
                    $shiftText = str_replace("'", "", $shiftText);
                }
                
            }
            return $shiftText;
            
        }

	function get_checkbox_control()
	{
		if (!$this->create_checkbox)
		{
			$empty = new StdClass();
			$empty->checkboxes='';
			$empty->first_column='';
			return $empty;
		}

		$tooltip_manager = new ToolTipManager();

		$disable_checkbox = $this->params->total ? '' : 'disabled';
		//if (empty($listing_data)) $listing_data = '';
   		$clear_list_status='disabled';
   		$show_list_status = 'disabled';
   		$send_list_status = 'disabled';
   		$edit_list_status = 'disabled';
   		$add_to_group_status = 'disabled';

   		$mouseover_show_list = '';
   		$mouseover_clear_list = '';
   		$mouseover_edit_list = '';

		$worklist_id = $this->CI->session->userdata($this->params->userlist_key_id);
		$listing_data = array();
		if ($worklist_id)
		{
			$dm_worklist_detail = new DM_Worklist_Detail();
			$dm_worklist_detail->where('worklist_id',$worklist_id);
			$list_size = $dm_worklist_detail->count();

			$dm_worklist_detail->where('worklist_id',$worklist_id);
	   	    $dm_worklist_detail->order_by('id desc');
			$dm_worklist_detail->limit(20);
			$dm_worklist_detail->get();

			foreach ($dm_worklist_detail->all as $detail)
			{
				$listing_data[] = $detail->entity_id;
			}
		}

                $show_list_button = '';
                $output = 'N.A';
		if (!empty($listing_data))
		{
	   	    $clear_list_status = '';
   	   	    $show_list_status = '';
   	   	    $send_list_status = '';
   	   	    $edit_list_status = '';
   	   	    $add_to_group_status = '';
	   	    $dm_user = new DM_User();
	   	    $dm_user->order_by('first_name');
	   	    $dm_user->where_in('id',$listing_data)->get();
	   	    $show_list_size = 25;

			$list_content = '';
			$count = 0;
			
			

	   	    foreach ($dm_user->all as $index=>$user)
	   	    {
				if ($count++>=$show_list_size) break;

	   	    	/*$list_content .= "<tr>".
                                 "<td align=\"left\">$count $user->first_name $user->last_name</td>" .
                                 "<td align=\"center\">$user->phone</td>".
                                 "<tr>" .
	   	    	                 "<tr><td colspan=2><hr /><tr>"; */
			$list_content .='   ['. $count ."-   ". $user->first_name ." ". $user->last_name  . "   " . $user->phone . "]      ";

	   	    }

			//$list_size = count($dm_user->all);
			$more = $list_size > $show_list_size ? 'More...' : '';

	   		/*$output =   //join(",",$listing_data);
	   		"<table border=0 width=100% cellpadding=0 cellspacing=0>" . 
            "<tr><th align=left width=65%>". $this->CI->message_bundle->get_lang_value('thead_name') . "id:". $worklist_id." </th><th width=35% align='center'>".  $this->CI->message_bundle->get_lang_value('thead_number') ."</th><tr>" .
	   		"<tr><td colspan=2><hr /><tr>" .
	   		$list_content .
			"<tr><th align=left>". $this->CI->message_bundle->get_lang_value('total')  .":". $list_size . "</th><th align=center>$more</th><tr>" .
            "</table>"; */
			$output = $this->CI->message_bundle->get_lang_value('thead_name') . "id: -".  $worklist_id . " - ". 
			$this->CI->message_bundle->get_lang_value('thead_number') ."\n" . '<hr/>' .
			$list_content .  "\n" .'<hr/>';
			

			$this->output = $output;

		  $params = new StdClass();
		  $params->id = 'show_list';
		  $params->content = $output;
		  $params->type = 'button';
		  $params->fontsize = 13;
		  $params->positions= "'right','left'";
		  $params->padding =20;
		  $params->width = 300;
		  $params->spike_length = 40;
		  $params->disabled = '';
		  $params->button_class = 'checkbox_handler_show_list';
		  $params->button_text = $this->CI->message_bundle->get_lang_value('show_list_button'); //'Show List';

          $tooltip = $tooltip_manager->get($params);
		  $this->scripts[] = $tooltip->script;
	 	  $show_list_button = $tooltip->component;

		}
		else
		{
		   $show_list_button = "<input type='button' class='checkbox_handler_show_list'" .
   	   	                       " $mouseover_show_list value='Show List' $show_list_status/>\n" ;
		}

		
      #---Check All listed memebers--------------------------------------------
	  $params = new StdClass();
	  $params->id ='checkall'; // $this->CI->message_bundle->get_lang_value('checkall_button');
	  $params->content =$this->CI->message_bundle->get_lang_value('checkall_button_tip'); // "<span class='vtip_highlight'>Select all</span> listed members."; 
	  $params->type = 'button';
	  $params->disabled = '';
	  $params->positions = "'bottom','left'";
	  $params->button_class = 'checkbox_handler_checkall';
	  $params->button_text = $this->CI->message_bundle->get_lang_value('checkall_button'); //'Check All';
	  $params->width=150;
 	  $tooltip = $tooltip_manager->get($params);
 	  $this->scripts[] = $tooltip->script;
      $check_all_button = $tooltip->component;

      #---Uncheck All Listed members-------------------------------------------
	  $params = new StdClass();
	  $params->id ='uncheckall';
	  $params->content = $this->CI->message_bundle->get_lang_value('uncheckall_button_tip');  //"<span class='vtip_highlight'>Deselect all</span> listed members.";
	  $params->type = 'button';
	  $params->disabled = '';
	  $params->positions = "'bottom','top'";
	  $params->width=150;
	  $params->button_class = 'checkbox_handler_uncheckall';
	  $params->button_text = $this->CI->message_bundle->get_lang_value('uncheckall_button');//'Uncheck All';
 	  $tooltip = $tooltip_manager->get($params);
 	  $this->scripts[] = $tooltip->script;
      $uncheck_all_button = $tooltip->component;

      #---Add Selected Member to current list----------------------------------
	  $params = new stdClass();
	  $params->id = 'Upgrade';#'add_to_list';
	  $params->content =  $this->CI->message_bundle->get_lang_value('update'); //"<span class='vtip_highlight'>Add</span> selected member(s) to current list.";
	  $params->type = 'button';
	  $params->disabled = 'disabled';
      $params->positions = "'bottom','top'";
	  $params->button_class = 'checkbox_handler_upgrade';
	  $params->button_text = $this->CI->message_bundle->get_lang_value('update');//'Add To List';
 	  $tooltip = $tooltip_manager->get($params);
 	  $this->scripts[] = $tooltip->script;
      $add_to_list_button = $tooltip->component;

      
      #---Edit or View List----------------------------------------------------
	  $params = new StdClass();
	  $params->id = 'remove_list';
	  $params->content =  $this->CI->message_bundle->get_lang_value('remove');//"<span class='vtip_highlight'>Edit</span> or <span class='vtip_highlight'>view</span> current selected list.";
	  $params->type = 'button';
	  $params->width = 170;
	  $params->disabled = $edit_list_status;
	  $params->button_class = 'checkbox_handler_tag_remove';
	  $params->button_text = $this->CI->message_bundle->get_lang_value('remove');//'Edit List';
	  $tooltip = $tooltip_manager->get($params);
 	  $this->scripts[] = $tooltip->script;
      $edit_list_button = $tooltip->component;

     

  	  
	  
	$delete_button='';
	
	#if($this->pagination->name=='deleted_members' or $this->pagination->name=='unassigned_members'){
		
		
	
   	  $base_url = base_url();
	  $first_column_header = "<th width='3%'>&nbsp;</th>";
	  $checkbox_control = "\n".
	                      "<!-- Start of Checkox Control-->\n" .
                          "<tr>\n" .
	  		              "  <td style='vertical-align:bottom; padding-left:0px; text-align: center; cellspacing:2px;'>\n" .
 					      "    <img border='0' src='{$base_url}/img/checkbox_indicator.png' style='vertical-align:bottom;'/>\n".
                          "  </td>\n" .
	   		              "  <td colspan='$this->colspan'>\n" .
                          "$check_all_button/\n" .
                          "$uncheck_all_button" .
	   		                  "$add_to_list_button\n" .
				          "$edit_list_button\n" .
						  "  </td>\n" .
    		              "</tr>\n" .
    		              "<!-- End of Checkox Control-->\n";

	  $result = new StdClass();
	  $result->first_column = "<th width='3%'>&nbsp;</th>";
	  $result->checkboxes = $checkbox_control;
	  return $result;
	}

	/*
	 +-----------------------------------------------------------------------------+
	 | Parameters                                                                  |
	 +-----------------------------------------------------------------------------+
	 */
	function get_link_anchors($links,$phone_number,$user,$tag_id='',$tag_name='')
	{
		//$factory = $this->factory; //HtmlElementFactory::get_instance();
		$tooltip_manager = new ToolTipManager();
		$user_id = $user->user_id ? $user->user_id : $user->id;
		$identity = ($user->first_name or $user->last_name) ? "$user->first_name $user->last_name" : $user->phone;

		$anchors = '';
		foreach ($links as $link)
		{
			if ($link=='block')
			{
				 $link = $this->blocked_phones->is_blocked_by_phone($phone_number) ? 'unblock' : 'block';
			}

$name='';
		    $param = new StdClass();
			switch ($link)
			{
				case 'update':
					$param->image    = 'update.png';
					$param->href     = "member_controller/update_form/$user_id";
					$name = 'update';
					//$param->function        = 'update_form';
					//$param->param1          = $user_id;
					$param->info            = 'info_update_member_details';
					break;
				case 'remove':
					$param->confirm_message = 'confirm_remove_member';
					$param->image    = 'remove.png';
					$param->href      = "member_controller/remove_member/$user_id/$tag_id";
					$name = 'remove';

					//$param->function        = 'remove_member';
					//$param->param1          = $user_id;
					//$param->param2          = $tag_id;
					$param->info            = 'info_remove_member_from_tag';
					break;
				case 'delete':
					$param->confirm_message = 'confirm_delete_member';
					$param->image    = 'delete.png';
					$param->href      = "member_controller/delete/$user_id";
					$name = 'delete';
					//$param->function        = 'delete';
					//$param->param1          = $user_id;
					$param->info            = 'info_delete_member';
					break;
				case 'send':
					$param->image    = 'send.gif';
					$param->href     = "message_controller/message_input_basic/SEND_ONE/$user_id";
					//$param->function        = "message_input_basic/SEND_ONE/$user_id";
					//$param->param1          = "SEND_ONE";
					//$param->param2          = $user_id;
					$name='send';
					$param->info            = 'info_send_sms';
					break;
				case 'reactivate':
					$param->confirm_message = 'confirm_reactivate_member';
					$param->image    = 'update.png';
					$param->href      = "member_controller/reactivate_member/$user_id";
					//$param->function        = 'reactivate_member';
					//$param->param1          = $user_id;
					$name = 'react';
					$param->info            = 'info_reactivate_member';
					break;
				case 'unblock':
					$param->confirm_message = 'confirm_unblock_member';
					$param->image    = 'unblock.gif';
					$param->href      = "blocklist_controller/unblock/$user_id";
					$name = 'unblock';
					//$param->function        = 'unblock';
					//$param->param1          = $user_id;
					$param->info            = 'info_unblock_member';
					break;
				case 'block':
					$param->confirm_message = 'confirm_block_member';
					$param->image    = 'block.gif';
					$param->href      = "blocklist_controller/block/$user_id";
					$name = 'block';
					//$param->function        = 'block';
					//$param->param1          = $user_id;
					$param->info            = 'info_block_member';
					break;
			    case 'sms_messages':
                    $param->image='sms_message.png';
                    $param->href="member_controller/show_incoming_messages/1/$user_id";
                    $param->info='info_member_show_sms_messages';
                    $name='sms_message';
                    break;
				default:
				    $param = null;
				    break;
			}



         if ($param)
         {
         	$param->id = "{$name}_$user_id";
		    $param->type = 'anchor';
			$param->replacement1 = $identity;
			$param->replacement2 = $tag_name;

 	        $tooltip = $tooltip_manager->get($param);
         	$anchors[] = $tooltip->component;
         	$javascrips[] =$tooltip->script;

         }
         else
         {
   	     	$anchors[] .= '&nbsp;';
         }
		}

		$result = new StdClass();
		$result->anchors = $anchors;
		$result->param   = $param;
		$result->javascripts = $javascrips;
		return $result;


	}

	function create_links($user,$have_inactive_member)
	{
		$status = $user->active_flag . $user->deleted_flag;
		switch($status)
		{
			case '11':
			case '10':
			  $status ='active';
			  $links[]='update';
			  if ($have_inactive_member) $links[]='';
			  $links[]='block';
			  $links[]='sms_messages';
			  break;
			case '00':
			  $status='inactive';
			  $links[]='update';
			  $links[]='delete';
			  $links[]='block';
			  $links[]='sms_messages';
			  break;
			case '01':
			  $status ='deleted';
			  $links[]='reactivate';
			  if ($have_inactive_member) $links[]='';
			  $links[]='block';
			  break;
		}
		$result = new StdClass();
		$result->status = $status;
		$result->links  = $links;
		return $result;
	}
        
                
        

}



?>
