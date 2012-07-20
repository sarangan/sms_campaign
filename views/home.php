<?php
    /*
     +------------------------------------------------------------------------+
     |                                                                        |
     +------------------------------------------------------------------------+
    */
	//global $base_url;
	//global $image_info;
	global $message_bundle;

	$CI = &get_instance();

	$register   = Register::get_instance();
	
    //$language = 'english';
    //$base_url   = $register->get_property('base_url');

	//$image_info = "{$base_url}img/info.gif";
	//echo $image_info;
	$base_url=base_url();

	$tk[] = 'logout';
	$tk[] = 'login';
	$tk[] = 'register_new';
	$tk[] = 'forgot_password';
	$tk[] = 'helptext_close';
	$tk[] = 'company_name';
	$tk[] = 'image_logo';
	$tk[] = 'Menu_color_css';
	$tk[] = 'group_header';

	$tk[] = 'contact_us';
	$tk[] = 'contact_us_url';
    $tk[] = 'custom_css';
    $tk[] = 'quickstart_title';
    $tk[] = 'quickstart_subtitle';
    $tk[] = 'quickstart_message';
    $tk[] = 'your_members';
    $tk[] = 'webpage_title';

	$t = $CI->message_bundle->get_lang_values($tk);
	
	
    $user_id = $this->session->userdata('user_id');
    $menu_id = $this->session->userdata('menu_id');
	//echo "<div>$menu_id</div>";

	switch ($this->config->item('incent_country'))
	{
		case 'sg':
		$contact_url = 'http://www.incent.com.sg';
		  break;
		case 'no':
		  $contact_url = 'http://www.incent.no';
		  break;
		default:
		  $contact_url = 'http://www.incent.com.sg';
		  break;
	}

	$navigation_menu= '';
	$quick_menu='';
    if ($user_id)
    {

   	   	$language   = $register->get_property('language');
    	$menu_manager = new MenuManager($language);
    	$navigation_menu = $menu_manager->get_top_menu();

    	$quick_menu=$menu_manager->get_quick_menu();
    	$leflt_column_visibility = 'visible';

		$current_action =  $this->uri->segment(2);
		$controller = $this->uri->segment(1);
    	        $menu_info = $menu_manager->get_submenu_links($menu_id,$controller,$current_action);


		if ($this->dm_account_user->superuser_flag)
	    	$is_super_user = true;
		else
			$is_super_user = false;

        if ($CI->session->userdata('special_login_id'))
        	$action='special_logoff';
        else
           $action='logout';

		$customer_id =  $this->customer_id;
        $action_text=$t->logout;


        //$help_message = $menu_manager->get_help_text($menu_id,$controller,$current_action);

		#---Just set default---------------------------------------------------
        $dm_user = new DM_User();
        $lists->member_count = $dm_user->get_count();

		$view = array();
		$view['customer_id'] = $customer_id;
		$view['with_pagination'] = true;
		$view['page']=0;

        $lists->table=$this->load->view('your_member_list',$view,true);


        $username = $this->dm_account_user->username;
        $register_account = $this->dm_account->company;
        $title_action = 'search_members_form';
        $username_separator = "|";
		$forget_password='';
		$right_top_visibility = 'block';
    }
    else
    {
     	$leflt_column_visibility = 'hidden';
    	$menu_info = new StdClass();
    	$menu_info->title= "Log In";
    	$menu_info->links="<br /><br /><br />";

        $action       = "login";
        $action_text  = $t->login;
        $register_account = "<a href='{$base_url}index.php/account_controller/create_form' style='color: white; text-decoration: none'>".
                             "$t->register_new</a>";
        $lists    = new StdClass();
        $lists->table = "<br/><br /><br />";
        $lists->member_count = '';
        $submenu  = $lists;

        $t->quickstart_message='';
        $t->quickstart_title='';
        $t->quickstart_subtitle='';
		$right_top_visibility = 'none';

        $username = "";
        $title_action = '';
        $username_separator = "";
        $forget_password="| <a href='{$base_url}index.php/authentication_controller/forgot_password_form'>$t->forgot_password</a>";;
    }
	$login_color = "#ffffff";
	$display_warning = 'none';
   	if ($CI->session->userdata('special_login_id'))
   	{
	   	$login_color="#FF0000";
		$display_warning = 'inline';
   	}

    $image_path="{$base_url}img/";
    $webpage_title='InCent - Kompis';
   

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
                      "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
  <title><?=$t->webpage_title?></title>
  <!--meta http-equiv="X-UA-Compatible" content="IE=8" /-->
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
  <meta http-equiv="cache-control" content="no-cache">
  <meta http-equiv="expires" content="Mon, 26 Jul 1997 05:00:00 GMT"/>
<!--meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" /-->

<link rel="stylesheet" type="text/css" href="<?=$base_url?>css/<?=$t->group_header?>" />

<link rel="stylesheet" type="text/css" href="<?=$base_url?>css/navistyle.css" />



  <link type="text/css" rel="stylesheet" href="<?=$base_url?>css/test.css" /> 
  <link type="text/css" rel="stylesheet" href="<?=$base_url?>css/test0.css" />
  <link type="text/css" rel="stylesheet" href="<?=$base_url?>css/<?=$t->custom_css?>" />
  <link type="text/css" rel="stylesheet" href="<?=$base_url?>css/listing_table.css" />
  <link rel="stylesheet" type="text/css" href="<?=$base_url?>css/quick_menu.css" />
  <link type="text/css" rel="stylesheet" href="<?=$base_url?>css/home.css" />

  <link href="<?=$base_url?>css/<?=$t->Menu_color_css?>" rel="stylesheet" type="text/css"></link>

  <link rel="stylesheet" href="<?=$base_url?>assets/tipsy.css" type="text/css" />
  <link rel="stylesheet" href="<?=$base_url?>assets/tipsy-docs.css" type="text/css" />
  
	<link rel="stylesheet" href="<?=$base_url?>css/jquery.twitter.css" type="text/css" media="all">
  

    <!--script type="text/javascript" src="<?=$base_url?>assets/jquery-1.2.6.min.js"></script-->
   	
      <script type="text/javascript" src="<?=$base_url?>assets/jquery-1.4.2.min.js"></script>
	  <!-- script type='text/javascript' src='<?=$base_url?>js/jquery-1.2.6.min.js'></script-->
	  
	  <script type="text/javascript" src="<?=$base_url?>js/jkmegamenu.js"/>
	  
	<script type="text/javascript" src="<?=$base_url?>js/webwidget_menu_glide.js"></script> <!-- menu script -->
	 	
	<script type="text/javascript" src="<?=$base_url?>js/jquery.zrssfeed.min.js"></script>
	<script type="text/javascript" src="<?=$base_url?>js/jquery.vticker.js"></script>

  
	<script type="text/javascript">
		jkmegamenu.definemenu("megaanchor", "megamenu1", "mouseover");
	</script>
  
	<script type="text/javascript" src="<?=$base_url?>js/kompis.js"></script>
	  <!--script type="text/javascript" src="<?=$base_url?>js/overlib.js"></script-->


	  <!--[if IE]><script src="<?=$base_url?>js/tooltips/other_libs/excanvas_r3/excanvas.js" type="text/javascript" charset="utf-8"></script><![endif]-->
	
	<!--script type='text/javascript' src='<?=$base_url?>assets/project.js'></script <body onload='boot();'> -->
	
	
	
        <script type="text/javascript" src="<?=$base_url?>assets/jquery.tipsy.js"></script>
  	



</head>


	
<!--Mega Drop Down Menu HTML. Retain given CSS classes-->
<?=$quick_menu?>

<div class="box-wrap">

<div class="box-header">
<div id="topsection">
  <a href='<?=$base_url?>index.php/home/welcome'><img class="logo" src="<?=$base_url?>img/<?=$t->image_logo?>" alt='Kompis' border='0' /></a>
  <div>
    <div class="flag">
      <span><a href="<?=$base_url?>index.php/kompis/lang/no"><img id="no" border="0" src="<?=$base_url?>img/flag_no.gif"><div id='no-content' class='target' style='display: none'><?php echo utf8_encode('Bokmål'); ?></div></a>&nbsp;</span>
      <span><a href="<?=$base_url?>index.php/kompis/lang/nn"><img id="nn" border="0" src="<?=$base_url?>img/flag_no.gif"><div id='nn-content' class='target' style='display: none'>Nynorsk</div></a>&nbsp;</span>
      <span><a href="<?=$base_url?>index.php/kompis/lang/gb"><img id="gb" border="0" src="<?=$base_url?>img/flag_gb.gif"><div id='gb-content' class='target' style='display: none'>English</div></a>&nbsp;</span>
      <span><a href="<?=$base_url?>index.php/kompis/lang/cn"><img id="cn" border="0" src="<?=$base_url?>img/flag_cn.gif"><div id='cn-content' class='target' style='display: none'>Simplified Chinese</div></a>&nbsp;</span>
      <span><a href="<?=$base_url?>index.php/kompis/lang/se"><img id="se" border="0" src="<?=$base_url?>img/flag_se.gif"><div id='se-content' class='target' style='display: none'>Swedish</div></a>&nbsp;</span>
      <span><a href="<?=$base_url?>index.php/kompis/lang/de"><img id="de" border="0" src="<?=$base_url?>img/flag_de.gif"><div id='de-content' class='target' style='display: none'>German</div></a>&nbsp;</span>
    </div>
    <div id='login' style='font-weight:bold; color: <?=$login_color?>;'>
      <img src="<?=$base_url?>/img/special_login_warning.gif"  style='vertical-align:bottom; display:<?=$display_warning?>'/>
      <?=$register_account?> | <?=$username?> <?=$username_separator?>
      <a style='color: <?=$login_color?>' href="<?=$base_url?>index.php/authentication_controller/<?=$action?>"><?=$action_text?></a>
	  <?=$forget_password ?>
    </div>
    <!--div id='login' style='font-weight:bold; color: #ffffff;'>
      <img src="<?=$base_url?>img/special_login_warning.gif"  style='vertical-align:bottom; display:none'/>
      <a href='<?=$base_url?>index.php/account_controller/create_form' style='color: white; text-decoration: none'>Register New</a> |        <a style='color: #ffffff' href="http://127.0.0.1/kompis3/index.php/authentication_controller/login">Log In</a>
    </div-->
  </div>
</div>
  <div style='clear: both'/>
  


<?php
   if ($user_id)
   {
?>
  <div>
        <!--div id='navigation'-->
		
		
		
		
		
		<div class="nav">
			<ul>
			<?=$navigation_menu?>
			</ul>
			<div style="clear: both"></div>
			
		</div>
		<br/><br/><br/><br/>
         
        <!--/div-->
  </div>
  <br/>
<?
   }
   else
   {
      echo "<br/>";
      echo "<br/>";
   }
?>

</div>
</div>


<!-- support -->
<?php
 if ($t->webpage_title ==  'Mobilskole') 
 {
 ?>

<script type="text/javascript" src="//assets.zendesk.com/external/zenbox/v2.4/zenbox.js"></script>
<style type="text/css" media="screen, projection">
  @import url(//assets.zendesk.com/external/zenbox/v2.4/zenbox.css);
</style>

<script type="text/javascript">
if (typeof(Zenbox) !== "undefined") {
Zenbox.init({
dropboxID: "20069051",
url: "https://mobilskole.zendesk.com",
tabID: "support",
tabColor: "orange",
tabPosition: "Left"
});
}
</script>
<div id="zenbox_tab" class="ZenboxTabLeft" href="#" style="display: block; background-image: url("//assets.zendesk.com/external/zenbox/images/tab_support.png"); background-color: orange; border-color: orange;" title="Support" classname="ZenboxTabLeft">Support</div>
<div id="zenbox_overlay" style="display: none;">
<div id="zenbox_container">
<div class="zenbox_header">
<img id="zenbox_close" src="//assets.zendesk.com/external/zenbox/images/close_big.png">
</div>
<iframe id="zenbox_body" scrolling="auto" frameborder="0" allowtransparency="true" src="//assets.zendesk.com/external/zenbox/v2.1/loading.html">
<html lang="en-US">
<head>
<title>Zendesk Dropbox Loading...</title>
<meta charset="UTF-8">
<style>
body {
margin: 0;
padding: 0;
}
.wrapper {
background: #fff;
-moz-border-radius: 12px;
-webkit-border-radius: 12px;
border-radius: 12px;
margin: auto;
padding: 25px;
text-align: center;
}
img {
height: 50px;
width: 50px;
}
</style>
</head>
<body>
<div class="wrapper">
<img title="Loading Spinner" src="/images/load_large.gif">
</div>
</body>
</html>
</iframe>
</div>
<div id="zenbox_scrim">&nbsp;</div>
</div>

<?php
 }
?>

<!-- support end -->



<div class="columns-float" style='border:1px none red;'>
    <div class="column-two" style='border:1px none green; visibility:<?=$leflt_column_visibility;?>;'>
        <div class="column-two-content">
		<div class="grouphead">
			<h2 style="text-align:center;" >
			<?=$menu_info->title?>
			</h2>
			<?=$menu_info->links?>
			<div class="roundbottom_white">
			</div>
		</div>
		<br />
		<?php
		$member_count_text='';
		if ($lists->member_count) $member_count_text = " ($lists->member_count)";
		?>
		
		<div class="grouphead">
			
				<h2 style="text-align:center;" >
				<?=$t->your_members ?><?=$member_count_text?>
				</h2>
							
			<div id='list_table'>
			<?=$lists->table?>
			</div>
			<div class="roundbottom_white">
				
			</div>
		</div>
	</div>
	
</div>

<!-- ............................ -->

    <div class="column-one" style='border:1px none red;'>
        <div class="column-one-content" style='border: 1px none red;'>


<!--start-->
<div id="contentcolumn" style='border:1px none yellow;'>
   <!--div class="roundcont_white">
   <div class="roundtop_white">
	 <img src="<?=$base_url?>img/tl_white.gif" alt="" width="15" height="15" class="corner_white" style="display: none" />
   </div>
   <div style='border:1px none red; width=90%; min-height:300px;'-->
   
	<?=$form?>
   <!--/div>
   <br /><br />
   <hr class='form_hr'/>
   <div id="footer"  style='border:1px none red; font-size:85%'-->
    

   
   <div class="notice"> 
   <a href="#"><?=$t->company_name?></a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="<?=$t->contact_us_url?>"><?=$t->contact_us?></a>
   </div>
   <!--div class="roundbottom_white" style='clear:both;'>
	 <img src="<?=$base_url?>img/bl_white.gif" alt="" width="15" height="15" class="corner_white" style="display: none" />
   </div-->
<!--/div>
</div-->

 </div>
<!--end-->



        </div>
    </div>

    <div class="box-clear">&nbsp;</div><!-- # needed to make sure column 3 is cleared || but IE5(PC) and OmniWeb don't like it  -->
</div><!-- close boxbody -->


<div class="column-three" style='border:1px none green;'>
<div class="column-three-content">
<div id="right_top" style='border:1px none red; display: <?=$right_top_visibility?>;'>
  <div class="roundcont_white">
	<div class="roundtop_white"><img src="<?=$base_url?>img/tl_white.gif" alt="" width="15" height="15" class="corner" style="display: none" /></div>
	  <p style='text-align: left; color: #E87400; border: 1px  solid none;'>
		 <img style="vertical-align:middle;" border="0"src="<?=$base_url?>img/question_mark.jpg"  />
	     <b>&nbsp;<?=$t->quickstart_title?></b>
	  </p>
	  <div id='right_top_text' style='border:1px solid none; margin-top: 0; padding-top: 0;'><p style='color: #E87400; text-align:left; margin-top: 0; padding-top: 0; margin-bottom: 0; padding-bottom:0; '><u><?=$t->quickstart_subtitle?></u></p>
<p style='color: #E87400; text-align: justify; padding-top:0; margin-top:0;'><?=$t->quickstart_message?></p></div>
	  <!--p style='text-align: left; color: #E87400; border: 1px  solid none;'>
		 <img style="vertical-align:middle;" border="0"src="<?=$base_url?>img/question_mark.jpg"  />
	     <b>&nbsp;Quick Start</b>
	  </p>
	  <div id='right_top_text' style='border:1px solid none; margin-top: 0; padding-top: 0;'><p style='color: #E87400; text-align:left; margin-top: 0; padding-top: 0; margin-bottom: 0; padding-bottom:0; '><u>Welcome to Kompis 3</u></p>
<p style='color: #E87400; text-align: justify; padding-top:0; margin-top:0;'>Your fast & convenient, one stop SMS broadcasting and management system.</p></div-->
	  <div class="roundbottom_white">
		<img src="<?=$base_url?>/img/bl_white.gif" alt="" width="15" height="15" class="corner" style="display: none" />
	</div>
  </div>
</div>
<!-- End of Right Column Top -->


<!-- Start of Right Column Bottom -->
<div id="right_bottom" style='border:1px none green; display: none;'>
   <div class="roundcont_white">
	   <div class="roundtop_white">
	   	  <img src="<?=$base_url?>/img/tl_white.gif" alt="" width="15" height="15" class="corner" style="display: none"/>
	   </div>
	   <p style='text-align: left; color: #E87400; border: 1px  solid none;'>
	      <img style="vertical-align:middle;" border="0"src="<?=$base_url?>img/question_mark.jpg"/>
	      <b>&nbsp;Tips</b>
	   </p>
	   <div id='right_bottom_text' style='color: #E87400; border:1px none black; margin: 0 15px 0 15px; padding-left: 0px;'></div>
	   <br />
   	   <div id='right_bottom_cancel' onclick='help_cancel()' style='display:none; border:1px none black; margin: 0 15px 0 15px; padding-left: 0px;'>
		  <!--img valign='bottom' src='<?=$base_url?>img/help_cancel.gif'/-->
		  <a href='' onclick='help_cancel(); return false;'><?=$t->helptext_close?></a>
	   </div>
	   <div class="roundbottom_white">
		 <img src="<?=$base_url?>img/bl_white.gif" alt="" width="15" height="15" class="corner" style="display: none" />
	   </div>
   </div>
</div>
<!-- End of Right Column Bottom -->


</div>



</div>


<!-- things -->


<?php
 if ($t->webpage_title ==  'Mobilskole') 
 {
 ?>
 &nbsp;&nbsp;&nbsp;	
<div class='column-three' style='border:1px none green;'>
<div class='column-three-content'>
	
	<div class='grouphead' style='padding-bottom: 0; border-style:none;'>	
			<script charset='utf-8' src='http://widgets.twimg.com/j/2/widget.js'></script>
<script>
new TWTR.Widget({
  version: 2,
  type: 'profile',
  rpp: 4,
  interval: 30000,
  width: 'auto',
  height: 330,
  theme: {
    shell: {
      background: '#C55341',
      color: '#ffffff'
    },
    tweets: {
      background: '#ffffff',
      color: '#000000',
      links: '#1e25b8'
    }
  },
  features: {
    scrollbar: false,
    loop: false,
    live: false,
    behavior: 'all'
  }
}).render().setUser('mobilskole').start();
</script>	
			
	</div>


</div>
</div>

<?php
 }
?>



<!-- man -->


<div class="box-clear">&nbsp;</div><!-- # needed to make sure column 3 is cleared || but IE5(PC) and OmniWeb don't like it  -->



<div class="nn4clear">&nbsp;</div><!-- # needed for NN4 to clear all columns || not needed by any other browser -->
<div class="box-footer"></div>
</div>
  
  
	

<!-- my tooltip function (sara)-->
<script type='text/javascript'>
  $(function() {

    for (var i=0;i<document.getElementsByTagName('*').length;i++) 
    {
      if (document.getElementsByTagName('*')[i].id) 
      {
        
        if (document.getElementsByTagName('*')[i].id ){

            var mux =document.getElementsByTagName('*')[i].id;
            var scx= mux.concat("-content")

          if($("#".concat(scx)).html())
          {
        
             var attribute = document.createAttribute("title");
             var tem = $("#".concat(scx)).html();
             var tst = tem.replace(/(<([^>]+)>)/ig,"");
	     
	     //alert(tst);

            attribute.nodeValue =tst;//document.getElementsByTagName(scx)[i];
          
            document.getElementsByTagName('*')[i].setAttributeNode(attribute);

           $(document.getElementsByTagName('*')[i]).tipsy({gravity: $.fn.tipsy.autoNS});
          
        }

      }
      
      } 
    }   
    
        
  });
  
  
</script>


