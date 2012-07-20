<?php

class  MessageProcessor
{
	private $message_id;
	private $message_details;
	private static $db;

	function  __construct($message_id)
	{
		//self::$db = DatabaseSelect::get_instance();
		$this->message_id = $message_id;
		$this->dm_message = new DM_Message();
		$this->dm_message->where('id',$message_id)->get();

	}

	function get_message_details()
	{
		$dm_message_detail = new DM_Message_Detail();
		$dm_message_detail->where('message_id',$this->message_id);
		$dm_message_detail->where('status','');
		$dm_message_detail->get();
		$this->dm_message_detail=$dm_message_detail;
		return  count($dm_message_detail->all);
	}

	function get_user_id_by_phone(array $phones)
	{
		$first=true;

		foreach ($phones as $phone=>$user_id) $in[]=$phone;
		$in[]="6598470408";
		$in[]="6598470401";

		$in_values = join(",",$in);
		if (empty($in)) return array();

		$customer_id = $this->dm_message->customer_id;

//		$sql = "select id, phone " .
//			   "from users " .
//			   "where customer_id = '$customer_id'" .
//			   "  and phone in ($in_values)";
//
//		$results = self::$db->execute_sql($sql);
//		if (empty($results)) return array();


		$dm_user = new DM_User();
		$dm_user->selct('id,phone');
		$dm_user->where('customer_id',$customer_id);
		$dm_user->where_in('phone',$in);
		$dm_user->get();

		if (count($dm_user->all)==0) return array();

		$existing_phones = array();
		foreach ($dm_user->all as $user)
		{
		 	$existing_phones[$user->phone] = $user->id;
		}
		return $existing_phones;
	}

	function get_existing_rel(array $users)
	{
		$tag_id = $this->dm_message->tag_id;

		$dm_rel = new DM_User_Tag_Rel();
		$dm_rel->where('tag_id',$tag_id);
		$dm_rel->where_in('user_id',$users);
		$dm_rel->get();
		if (count($dm_rel->all)==0) return array();

		$existing_reel = array();
		foreach ($dm_rel->all as $rel)
		{
			$existing_rel[$rel->user_id]=$tag_id;
		}
		return $existing_rel;
	}

	function update_db(array $phones, array $existing_phones)
	{
		$tag_id = $this->dm_message->tag_id;
		$customer_id = $this->dm_message->customer_id;

		#---Handling User------------------------------------------------------
		$users = array();

		#==Note: message_details is datamapper object--------------------------
		foreach ($phones as $phone=>$message_detail)
		{
			$user_id =  $message_detail->user_id;

			if ($user_id>0)
			{
				$update_user_id = false;
			}
			elseif (!array_key_exists($phone,$existing_phones))
			{
				$dm_user = new DM_User();
				$dm_user->where('customer_id',$customer_id);
				$dm_user->where('phone',$phone);
				$dm_user->get();

				$dm_user->active_flag=true;
				$dm_user->phone = $phone;
				$dm_user->customer_id = $customer_id;
				$dm_user->email = '';
				if ($dm_user->save())
				{
					$update_user_id = true;
					$user_id = $dm_user->id;
				}
			}
			else
			{
				$user_id = $existing_phones[$phone];
				$update_user_id = true;
			}
			$users[]=$user_id;

			if ($update_user_id) $message_detail->user_id = $user_id;
			$message_detail->processed_datetime = date('Y-m-d H:i:s');
			$message_detail->save();

		}

		#---Hanlding  Relationships--------------------------------------------
		$existing_rel    = $this->get_existing_rel($users);
		foreach ($users as $user_id)
		{
			if (!array_key_exists($user_id,$existing_rel))
			{
			    $dm_rel = new DM_User_Tag_Rel();
			    $dm_rel->user_id = $user_id;
			    $dm_rel->tag_id = $tag_id;
			    $dm_rel->save();
			}
		}
	}

	/*
	 +------------------------------------------------------------------------+
	 |                                                                        |
	 +------------------------------------------------------------------------+
	*/
	function execute()
	{
		if (!$this->get_message_details())
		{
	//			if ($this->dm_message->start_process_datetime=='' and $this->dm_message->end_process_datetime=='')
	//			{
	//				$this->dm_message->start_process_datetime = date('Y-m-d H:i:s');
	//				$this->dm_message->end_process_datetime    = date('Y-m-d H:i:s');
	//				$this->dm_message->save();
	//				return false;;
	//			}
	//			return false;
		}
		if ($this->dm_message->tag_id<=0)
		{
			$this->dm_message->start_process_datetime = date('Y-m-d H:i:s');
			$this->dm_message->end_process_datetime    = date('Y-m-d H:i:s');
			$this->dm_message->save();
			return;
		}

		$this->dm_message->start_process_datetime = date('Y-m-d H:i:s');
		$this->dm_message->save();

		$phones = array();

		set_time_limit(60*60);
		$count=0;
		foreach ($this->dm_message_detail->all as $index=>$message_detail)
		{
			$phone = $message_detail->phone;
			$phones[$phone]=$message_detail;;
			$count++;

			if ($count>1000)
			{
				$existing_phones = $this->get_user_id_by_phone($phones);
				//$this->update_db($phones,$existing_phones);
				$phones = array();
				$count=0;
			}
		}

		if (!empty($phones))
		{
			$existing_phones = $this->get_user_id_by_phone($phones);
			$this->update_db($phones,$existing_phones);
		}

		set_time_limit(360);

		$this->dm_message->end_process_datetime    = date('Y-m-d H:i:s');
		$this->dm_message->save();
	}
}
?>
