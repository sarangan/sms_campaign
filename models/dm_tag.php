<?php

class DM_Tag extends DataMapper
{
	public $table = 'tags';
	public $created_field = 'created_date';
	public $updated_field = 'updated_date';

	public $defalut;

	public static $config;

	var $validation = array(
	    array(
	        'field' => 'tag_name',
	        'label' => 'Tag Name',
	        'rules' => array('required','unique_pair' => 'customer_id')
	    ),
	    array(
	        'field' => 'customer_id',
	        'label' => 'Customer ID',
	        'rules' => array('required')
	    ),
	    array(
	        'field' => 'tag_order',
	        'label' => 'Tag Order',
	        'rules' => array('required','numeric')
	    ),
	    array(
	        'field' => 'description',
	        'label' => 'Tag Description',
	        'rules' => array('required')
	    )
	);

	public function __construct()
	{
	 	parent::__construct();
	 	$this->default = new StdClass();
	 	$this->default->tag_order= 10;
		$this->load->library("benchmark"); // from live 
	}

	public function get_instance()
	{
		return new self();
	}

	public function get_tags_external($user_id)
	{
		    $dm_account_user = new DM_account_user;
		$dm_account_user->get_by_id($user_id);
		if(!$dm_account_user->id) return false;
    
    $query = "
      SELECT u.id, first_name, last_name, phone, email, tag_name, t.id as tag_id
      FROM tags t 
      JOIN user_tag_relationships ut ON t.id = ut.tag_id
      JOIN users u ON u.id = ut.user_id
      WHERE t.customer_id = $dm_account_user->customer_id
      AND u.active_flag = 1
      AND u.deleted_flag <> true
      AND t.deleted_flag <> true
      ORDER BY tag_order,tag_name, last_name, first_name, user_id
      ;
    ";

    $result = $this->db->query($query);

    $return = array();
    foreach($result->result() as $row) {
      $tag_id = $row->tag_id;
      
      if(!isset($return[$tag_id])) {
        $return[$tag_id] = array();
        $return[$tag_id]['id'] = $tag_id;
        $return[$tag_id]['name'] = $row->tag_name;
        $return[$tag_id]['members'] = array();
      }
      
      if($row->first_name && $row->last_name)
			  $name = $row->last_name.", ".$row->first_name;
			else {
				$name = $row->last_name.$row->first_name;
		  }
      $member['name'] = $name;
      $member['id'] = $row->id;
      $member['phone'] = $row->phone;

      $return[$tag_id]['members'][] = $member;
    }



    return array_values($return);
    return;
    

		$dm_account = new DM_account;
		$dm_account->get_by_id($dm_account_user->customer_id);
		

		$dm_tag = new DM_Tag();
#		$dm_tag->where('account_user.id',$user_id);
		$dm_tag->where('customer_id', $dm_account->id);
		$dm_tag->where('deleted_flag<>',true);
		$dm_tag->order_by('tag_order, description, id');
		# if (!empty($tags)) $dm_tag->where_in('id',$tags);
		$dm_tag->get();
		return $dm_tag;
	}

	public function get_tags($customer_id=null)
	{
		$CI = &get_instance();
		$dm_account_user = $CI->dm_account_user;

		$tags = array();
	    if (!$dm_account_user->superuser_flag)
		{
			$tags = DM_Account_User_Tag_Rel::get_instance()->get_tags();
            if (empty($tags)) return array();
		}

		$customer_id = $customer_id ? $customer_id : $CI->customer_id;

		$dm_tag = new DM_Tag();
		$dm_tag->where('customer_id',$customer_id);
		$dm_tag->where('deleted_flag <>',true);
		$dm_tag->order_by('tag_order,tag_name, description, id');
		if (!empty($tags)) $dm_tag->where_in('id',$tags);
		$dm_tag->get();
		return $dm_tag;
	}

	public function get_array()
	{
		$result = array();
		foreach ($this->all as $tag)
		{
			$result[] = $tag->id;
		}
		return $result;
	}

	public function get_tags_with_count($options=null)
	{
		$CI = &get_instance();
		$customer_id = $CI->customer_id;
		$dm_account_user = $CI->dm_account_user;

		$tags=array();
		if (isset($options->tag_ids))
		{
			$tags = $options->tag_ids;
		}

		//if (empty($tag_ids))
		if (empty($tags))
		{
			if (!$dm_account_user->superuser_flag)
			{
				$tags = DM_Account_User_Tag_Rel::get_instance()->get_tags();
            	if (empty($tags)) return array();
			}
		}

		$CI = &get_instance();
		$CI->db->select('b.*, count(a.user_id) as count');
		$CI->db->from('user_tag_relationships a');
		$CI->db->join('tags b','a.tag_id = b.id');
		$CI->db->where('customer_id',$CI->customer_id);
		$CI->db->where('deleted_flag <>', true);
		//$CI->db->order_by('tag_order, description, id');
		$CI->db->order_by('tag_order, tag_name');
		if (!empty($tags)) $CI->db->where_in('a.tag_id',$tags);
		$CI->db->group_by('tag_id');
		$result = $CI->db->get()->result();
		return $result;
	}

   public function get_count_by_customer_id($options=null)
   {
      $in_tags_condition='';
      if (isset($options->tag_ids))
      {
         $tag_list=join(',',$options->tag_ids);
         $in_tags_condition=" AND A.id in($tag_list) ";
      }

      $CI = &get_instance();
      $customer_id = $CI->customer_id;
      $sql = 'SELECT TAG_ID, COUNT(B.USER_ID) AS COUNT ' .
         'FROM tags A, ' .
         '     user_tag_relationships B ' .
         "WHERE A.CUSTOMER_ID = '$customer_id' " .
         "$in_tags_condition" .
         '  AND A.ID = B.TAG_ID ' .
         'GROUP BY TAG_ID';

    	$result = $this->db->query($sql)->result();

		$return_array = array();
		foreach ($result as $tag_count)
		{
			$return_array[$tag_count->TAG_ID] = $tag_count->COUNT;
		}
		return ($return_array);
	}

	public function get_count()
	{
       $dm_rel=new DM_User_Tag_Rel();
       $dm_rel->where('tag_id',$this->id);
       return $dm_rel->count();
	}

    public static function get_by_id($group_id='',$customer_id='')
    {
    	if (!$customer_id)
    	{
    		$CI=&get_instance();
    		$customer_id=$CI->customer_id;
    	}

    	$dm_group=new DM_Tag();
    	$dm_group->where('id',$group_id);
        $dm_group->where('customer_id',$customer_id);
    	$dm_group->get();
        return $dm_group;
    }

	public static function is_name_exists($group_name='',$customer_id='')
	{
      if (!$group_name)  return false;
      if (!$customer_id) return false;

      $dm_group=new DM_Tag();
      $dm_group->where('tag_name',$group_name);
      $dm_group->where('customer_id',$customer_id);

      if ($dm_group->count()) return true;
      return false;
	}

    public static function get_by_name($group_name='',$customer_id='')
    {
      if (!$group_name)  return false;
      if (!$customer_id) return false;

      $dm_group=new DM_Tag();
      $dm_group->where('tag_name',$group_name);
      $dm_group->where('customer_id',$customer_id);
      $dm_group->get();
      return $dm_group;
    }
    
    
    public function get_by_groups($tags,$option='',$customerid='')
	{
		#---Somekind of safety measurement-------------------------------------
		if (empty($tags)) $tags[]='-999';
		elseif (count($tags)==1 and $tags[0]=='')
		{
  			$tags = array();
  			$tags[]='-999';
		}

		if (!is_array($tags)) $tags = array($tags);
		if ($option=='') $option=new StdClass();
		if (!isset($option->page_size)) $option->page_size='';

		if (!isset($option->sort_key)) $option->sort_key = null;
		$count = substr_count($option->sort_key,":");
		switch ($count)
		{
			case 2:
			  list($indicator,$type,$field) = explode(':',$option->sort_key);
			  break;
			case 3:
			  list($indicator,$type,$field,$order) = explode(':',$option->sort_key);
			  break;
			default:
			  $field='default';
			  break;
		}

		if ($field=='default') $sort_field='a.first_name asc';
		else   $sort_field = "$field $order";
		
		$cusid= $customerid ;

     	$this->db->select('id,tag_name,description');
		$this->db->from($this->table);
		$this->db->where('customer_id',$cusid);
		#$this->db->order_by("tag_id, $sort_field");

		if ($option->page_size!='') $this->db->limit($option->page_size,$option->page_size*($option->current_page-1));
		$query_result = $this->db->get()->result();

		$this->db->select('count(*) as total');
		$this->db->from($this->table);
		$this->db->where('customer_id',$cusid);
		$query_count = $this->db->get()->result();

		$result=new StdClass();
		$result->data  = $query_result;
		$result->total = $query_count[0]->total;
		return $result;
	}
    
    

}


?>
