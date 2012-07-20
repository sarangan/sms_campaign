<?php

    function pre($any)
    {
        echo "<div align='left'>\n";
        echo "<pre>";
        print_r($any);
        echo "</pre>";
        echo "</div>";
    }

    function pp($string)
    {
    	echo "[$string]";
    }

	function format_error($error)
	{
		$message = str_replace("</p>","</p><br/>",$error);
		$message = substr($message,0,strlen($message)-5);
		return $message;
	}

	function format_select_options($object,$value_field,$text_field)
	{
		$result=array();

		if (isset($object->all)) $object_items = $object->all;
		else $object_items = $object;

		foreach ($object_items as $item)
		{
			$result[]=array('value'=>$item->$value_field,'text'=>$item->$text_field);
		}

		return $result;
	}

	function generate_picklist(Array $lists,$params)
	{
		if (isset($params->size))
		{
			$size = $params->size;
		}
		else
		{
			$size = count($lists);
			if ($size<5) $size=5;
			elseif ($size>10) $size=10;
		}

		$multiple='multiple';
		if (isset($params->multiple))
		{
			$multiple=$params->multiple ? 'multiple' : '';
		}

		$options ='';
		foreach ($lists as $index=>$list)
		{
			$value = $list->{$params->value};
			$text = $list->{$params->text};
			$options .= "<option value='$value'>$text</option>\n";
		}



		return "<select class='form_roundbox' style='width:50%;' id='$params->id' name='$params->name' size='$size' $multiple>\n" .
			   "$options" .
			   "</select>\n";
	}

?>
