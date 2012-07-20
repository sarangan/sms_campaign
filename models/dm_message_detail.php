<?php

class DM_Message_Detail extends DataMapper
{
	public $table = 'message_details';

	public static $config;



	public function __construct()
	{
	 	parent::__construct();
		
  	}

	private function get_count($message_ids,$type)
	{
		#---Verify Input Parameter---------------------------------------------
		$return_one = false;
		if (!is_array($message_ids))
		{
			$message_ids    = array($message_ids);
		    $return_one = true;
		}
		
		
		
		#---Success Count----------------------------------------------------------
		if ($type=='success') $this->db->where('status','');
		else if ($type=='reject') $this->db->where('status <>','');
		else die('---should not happen:Message Details:get_count()');

		$this->db->from($this->table);
		$this->db->select('message_id, count(*) as count');
		$this->db->where_in('message_id',$message_ids);
		$this->db->group_by('message_id');
		$results = $this->db->get()->result();

		if ($return_one) return +$results[0]->count;

		$counts = array();
		foreach ($results as $result)
		{
			$counts[$result->message_id]=$result->count;
		}

		foreach ($message_ids as $message_id)
		{
			$counts[$message_id] = isset($counts[$message_id]) ? $counts[$message_id] : 0;;
		}

		return $counts;
	}

	public function get_success_count($message_ids)
	{
		return $this->get_count($message_ids,'success');
	}

	public function get_rejected_count($message_ids)
	{
		return $this->get_count($message_ids,'reject');
	}

}


?>