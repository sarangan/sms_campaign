<?php


function switch_language()
{
	$CI = &get_instance();
	if ($CI->uri->segment(2)=='lang')
	{
		$country = $CI->uri->segment(3);
		//error_log($country);
		switch ($country)
		{
			case 'no':
				$lang = 'norwegian';
				break;
			case 'cn':
				$lang = 'simplified_chinese';
				break;
			case 'se':
				$lang = 'Swedish';
				break;
			case 'de':
				$lang = 'German';
				break;
			case 'nn':
				$lang = 'nynorsk';
				break;
			default:
				$lang = 'english';
				break;
		}

		$controller = $CI->session->userdata('last_controller');
		$action     = $CI->session->userdata('last_action');
		$param      = $CI->session->userdata('last_param');
		$session_data['language']=$lang;
		$CI->session->set_userdata($session_data);

		redirect("$controller/$action/$param");
	}
	else
	{
        $session_data['last_controller']= $CI->uri->segment(1);
        $session_data['last_action']    = $CI->uri->segment(2);
        $session_data['last_param']     = $CI->uri->segment(3);
        $CI->session->set_userdata($session_data);
	}

    $CI->language = $CI->session->userdata('language');


    if (!$CI->language)
    {
    	$language =  (INSTALLATION == 'mobilskole') ? 'norwegian' : 'english';
    	$CI->config->config['language']=$language;
    }
    else
		$CI->config->config['language']=$CI->language;

}


?>