<?php

   function  show_message($title,$message)
    {
		$CI = &get_instance();

		$message = $CI->message_bundle->get_lang_value($message);


		$data['form'] = "<br>" .
						"<div style='text-align:center; border:1px none black;'>
						 <p class='show_message_box'>
						 	<span class='show_message_title'>Message</span>
						    <br/><br/>
						    <span class='show_message_text'>$message</span>
						    <br/><br/>
						 </p>
                         </div>";
        $CI->load->view('home',$data);
    }

?>