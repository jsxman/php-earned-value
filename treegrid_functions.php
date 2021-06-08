<?php 


	// Add 'w' to wbs_id for each WBS to resolve the double association issue with first 
	// lvl children pointing to their parent WBS and also itself too.
	function addWBSMarker($wbs_id)
		{
		$markedWBS_id = 'w'.$wbs_id;
		return $markedWBS_id;
		}
	function removeWBSMarker($markedWBS_id)
		{
		$wbs_id = str_replace("w","",$markedWBS_id);
		return $wbs_id;	
		}
		
	function contains($substring, $string) 
		{
		$pos = strpos($string, $substring);
		if($pos === false) 
			{
				// string needle NOT found in haystack
				return false;
			}
		else
			{
				// string needle found in haystack
				return true;
			}
 
		}
?>