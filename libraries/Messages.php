<?php

class Messages extends DatabaseRecords
{

	const DATABASE_TABLE = 'messages';

	private $collection = Array();

	function __construct($customer_id='')
	{
		parent::__construct($customer_id);
		$CI = &get_instance();
		$this->dm_account_user = $CI->dm_account_user;
	}

	public function get_instance()
	{
		$self = new self();
		return $self;
	}

	public function zzz_get_collection()
	{
		return $this->collection;
	}

	#---Only qu fix
	public function get_unprocessed_messages()
	{
		#---Filtering, if non-superuser----------------------------------------
		$filter_condition = "";
		if (!$this->dm_account_user->superuser_flag)
			$filter_condition = " and account_user_id = '{$this->dm_account_user->id}'";
		$sql = "SELECT *
		        FROM   messages
		        WHERE  customer_id = '$this->customer_id'
                       $filter_condition
		        ORDER BY id desc";
		       "WHERE  status = ''";
		$this->execute_sql($sql);
		return $this;
	}

	#---Only quick fix
	public function zzz_g_et_last_messages($limit=10)
	{
		$CI = &get_instance();
		$account_user_id = $CI->account_user_id;
		$customer_id = $CI->customer_id;
		$sql = "SELECT DISTINCT message
				FROM `messages`
                WHERE customer_id     = '$customer_id'
                AND   account_user_id = $account_user_id
                ORDER BY id desc
                LIMIT 0,$limit";
        $this->execute_sql($sql);
		return $this;
	}

	public function get_message_statistic($params)
	{
		$CI = &get_instance();
		$dm_account_user = $CI->dm_account_user;

		if (!isset($params['report_by'])) $report_by='own';
		else $report_by =$params['report_by'];

		$report_year = $params['year'];
		$filter_year = " and date_format(start_datetime,'%Y')='$report_year' ";

		$report_month = $params['month'];
		if ($report_month=='all')
		    $filter_month = '';
		else
			$filter_month = " and date_format(start_datetime,'%Y%m')='$report_year$report_month' ";

		#---Filtering, if non-superuser----------------------------------------
		$filter_conditions = "";
		if (!$dm_account_user->superuser_flag or $report_by=='own')
		{
			$filter_conditions = " and account_user_id = '{$dm_account_user->id}'";
		}

		$sql = "SELECT date_format(start_datetime,'%Y%m') as 'year_month',
				       id,
				       concat(substr(date_format(start_datetime,'%M'),1,3),'-',date_format(start_datetime,'%Y')) as 'display_month',
				       date_format(start_datetime,'%Y%m%d') as 'date',
				       customer_id
		        FROM messages m
				where customer_id = $this->customer_id
				 and date_format(start_datetime,'%Y')='$report_year'
				 $filter_month
				 $filter_conditions";
		//$this->execute_sql($sql);
		//pre($this->result->list);

		$CI = &get_instance();
		$result = $CI->db->query($sql)->result();
		return $result;
	}

	public static function get_message_statistic_details(array $message_ids)
	{
		$CI = &get_instance();
		$customer_id = $CI->customer_id;
		$dm_account_user = $CI->dm_account_user;

		#---Pick up only related Message IDs-----------------------------------
		$in_message_ids="";
		$len = count($message_ids);
		foreach ($message_ids as $index=>$message_id)
		{
           if (($index+1)==$len) $in_message_ids .= "$message_id";
		   else  $in_message_ids .= "$message_id,";
		}

		#---Filtering, if non-superuser----------------------------------------
		$filter_conditions = "";

		if (!$dm_account_user->superuser_flag)
		{
			$filter_conditions = " and account_user_id = '{$dm_account_user->id}'";
			$sql = "SELECT date_format(inserted_datetime,'%Y%m%d') as 'date',
					       date_format(inserted_datetime,'%d-%m-%Y') as 'display_date',
				       count(1) as 'count'
		        FROM message_details m
				where 1=1
			    and message_id in ($in_message_ids)
				group by 1";

		}
		else
		{
			$sql = "SELECT date_format(inserted_datetime,'%Y%m%d') as 'date',
					date_format(inserted_datetime,'%d-%m-%Y') as 'display_date',
				       count(1) as 'count'
		        FROM message_details m
				where 1=1
			    and message_id in ($in_message_ids)
				group by 1";
		}

		$self = new self();
		$result = self::$db->execute_sql($sql);

		$self->result->list = $result;
		return $self;
	}

	public static function zzz_g_et_unprocessed_messages_for_batch()
	{
		$sql = "select *
	            from messages
	            where start_process_datetime is null
	            and end_process_datetime is null";

		$self = new self();
		$result = self::$db->execute_sql($sql);

		$self->collection = $result;
		return $self;
	}

}

?>