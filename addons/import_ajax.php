<?
if(isset($_REQUEST['action'])){
   switch($_REQUEST['action']){
      case "finish_import":
         finish_import();
         echo "1";
         break;
   }
}

function finish_import()
{
   $userID = $_SESSION['paev_userID'];
   $projectID = $_SESSION['projectID'];
   echo "userId = $userID\nprojectID = $projectID";

   $getLockedStatus = "SELECT locked FROM pev__project WHERE project_id='$projectID'";
   $lockedResult = mysql_query($getLockedStatus);
   if($lockedRow = mysql_fetch_array($lockedResult))
   {
      $lock = $lockedRow['locked'];
   }
   else
   {
      echo "An error occured while checking the project's lock status.";
      return;
   }
   echo "\nlock = $lock\n\n";

   $ignoreTasks = $_REQUEST['ignore'];
   $ignoreTasksArray = array();
   $filter = array ("{", "}");
   $ignoreTasks = str_replace($filter,"",$ignoreTasks);
   $ignoreTasksArray = explode(",",$ignoreTasks);
   array_pop($ignoreTasksArray);//remove empty element off end of array
   print_r($ignoreTasksArray);

   //Make sure users inserted into project are actually part of the project.
   $tempUserArray = array();
   $getTempUsers = "SELECT person_id FROM pev__temp_tasks WHERE uploader_id='$userID' AND person_id !='0' GROUP BY person_id";
   $tempUserResult = mysql_query($getTempUsers) or die("MySQL Error: ".mysql_error()."\n$getTempUsers");
   while($tempUserRow = mysql_fetch_array($tempUserResult)){
      $tempUserArray[] = $tempUserRow['person_id'];
   }

   $projectUserArray = array();
   $getProjectUsers = "SELECT person_id FROM pev__project_access WHERE project_id='$projectID'";
   $projectUsersResult = mysql_query($getProjectUsers) or die("MySQL Error: ".mysql_error()."\n$getProjectUsers");
   while($projectUsersRow = mysql_fetch_array($projectUsersResult)){
      $projectUserArray[] = $projectUsersRow['person_id'];
   }
   $newUsers = array_diff($tempUserArray, $projectUserArray);
   foreach($newUsers AS $newUser_id){
      //This person should be added to the project
      $insertPerson = "INSERT INTO pev__project_access (project_id, person_id) VALUES ('$projectID', '$newUser_id')";
      mysql_query($insertPerson) or die("MySQL Error: ".mysql_error()."\n$insertPerson");
   }

   if($lock)
   {
   	//Update existing Tasks
		$getCurrentTasks = "SELECT id, task_name, wbs, ec_date, actual_hours, percent_complete, person_id, three_switch";
	   $getCurrentTasks.= " FROM pev__temp_tasks WHERE uploader_id='$userID' AND is_wbs='0' AND id !='0'";
	   $currentTasksResult = mysql_query($getCurrentTasks) or die("MySQL Error: ".mysql_error()."\n$getCurrentTasks");
	   while($row = mysql_fetch_array($currentTasksResult))
	   {
	      $taskID = $row['id'];
	      $taskName = $row['task_name'];
	      $wbsHierarchy = $row['wbs'];
	      $wbsArray = explode(".",$row['wbs']);
	      $wbs = $wbsArray[0];
	      $ec_date = $row['ec_date'];
	      $actual_hours = $row['actual_hours'];
	      $percent_complete = $row['percent_complete'];
	      $poc = $row['person_id'];
	      $threeSwitch = $row['three_switch'];
	      
		   //Update the record
		   
	      $getWBSes = "SELECT wbs_id FROM pev__wbs WHERE project_id = '$projectID'";
			$resultWBSes = mysql_query($getWBSes) or die("MySQL Error: ".mysql_error()."\n$getWBSes");
			while($rowWBS = mysql_fetch_array($resultWBSes))
			{
			
				$currentWBS = $rowWBS['wbs_id'];
				$getWBStoTaskId = "SELECT wbs_to_task_id, wbs_id, task_id FROM pev__wbs_to_task WHERE wbs_to_task_id = '$taskID' AND wbs_id = '$currentWBS'";
				$result = mysql_query($getWBStoTaskId) or die("MySQL Error: ".mysql_error()."\n$getWBStoTaskId");
				if($row2 = mysql_fetch_array($result)) 
				{
					if(!in_array($wbs, $ignoreTasksArray) && !in_array($wbsHierarchy, $ignoreTasksArray))
					{
						$updateRecord = "UPDATE pev__wbs_to_task SET ec_date='$ec_date', actual_hours='$actual_hours', percent_complete='$percent_complete' WHERE wbs_to_task_id = '".$row2['wbs_to_task_id']."'";
	         		mysql_query($updateRecord) or die("MySQL Error: ".mysql_error()."\n$updateRecord");
					}
				}
			}
	         //Get the person to wbstask id and update/insert
//	         $getPerson = "SELECT person_to_wbstask_id FROM pev__person_to_wbstask WHERE wbs_to_task_id='$wbs_to_task_id'";
//	         $personResult = mysql_query($getPerson)  or die("MySQL Error: ".mysql_error()."\n$getPerson");
//	         if($personRow = mysql_fetch_array($personResult))
//	         {
//	            //Update needed
//	            $person_to_wbstask_id = $personRow['person_to_wbstask_id'];
//	            $updatePerson = "UPDATE pev__person_to_wbstask SET person_id='$poc' WHERE person_to_wbstask_id='$person_to_wbstask_id'";
//	            mysql_query($updatePerson) or die("MySQL Error: ".mysql_error()."\n$updatePerson");
//	         }
//	         else
//	         {
//	            //Insert Needed
//	            $insertPerson = "INSERT INTO pev__person_to_wbstask (person_id,wbs_to_task_id) VALUES ('$poc', '$wbs_to_task_id')";
//	            mysql_query($insertPerson) or die("MySQL Error: ".mysql_error()."\n$insertPerson");
//	         }
		}
   }
	else
	{
		
		 //Capture other variables
	   $deleteTasks = $_REQUEST['deletes'];
	   echo "\n\ndeleteTasks = $deleteTasks\n======================\n";
	   if($deleteTasks){
	      delete_project_items($projectID, $userID);
	   }
		
		$reassignments = $_REQUEST['reassign'];
	   $assignmentArray = array();
	   $assignmentArray = explode("|",$reassignments);
	   array_pop($assignmentArray); //pop off the null element off the end of the array
	   foreach($assignmentArray AS $assignment){
	      $value = explode("_",$assignment);
	      $person_name = $value[0];
	      $selection = $value[1];
	      if($selection == -1){
	         //Remove this person from the table
	         $setAssignment = "UPDATE pev__temp_tasks SET person_id='0' WHERE person_name='$person_name' AND uploader_id='$userID'";
	      }else if($selection > 0){
	         //Change assignment in the table
	         $setAssignment = "UPDATE pev__temp_tasks SET person_id='$selection' WHERE person_name='$person_name' AND uploader_id='$userID'";
	      }
	
	      if($selection != 0){
	         mysql_query($setAssignment) or die("MySQL Error: ".mysql_error()."\n$setAssignment");
	      }
	   }
	   
		//Insert WBS's
	   $wbsAssociation = array(); //This array will keep track of the WBS name/id association
	   $getWBS = "SELECT id, task_name, wbs FROM pev__temp_tasks WHERE uploader_id='$userID' AND is_wbs='1' ORDER BY wbs";
	   $wbsResult = mysql_query($getWBS) or die("MySQL Error: ".mysql_error()."\n$getWBS");
	   while($row = mysql_fetch_array($wbsResult))
	   	{
	      $id = $row['id'];
	      $name = $row['task_name'];
	      $name = addslashes($name);
	      $wbsArray = explode(".",$row['wbs']);
	      $order = $wbsArray[0];
	      echo "order= $order  |  id = $id'\n";
	      if(!in_array($order, $ignoreTasksArray))
	      	{
	      	if($id == 0) 
	      		{
	      		echo "New WBS\n";
		         //This WBS needs to be inserted into the database
		         $insertWBS = "INSERT INTO pev__wbs (wbs_name, project_id, wbs_order) VALUES ('$name','$projectID','$order')";
		         mysql_query($insertWBS) or die("MySQL Error: ".mysql_error()."\n$insertWBS");
		         $id = mysql_insert_id();
	      		}
	      	else
	      		{
	      		// part one of being able to handle importing duplicate WBSs 
	      		$strSQL = "SELECT MAX(wbs_order) FROM pev__wbs WHERE project_id = $projectID";
	      		$result = dbquery($strSQL);
	      		$max = mysql_result($result, 0);
	      		$max = trim($max, "0");
	      		$max = $max+1;
	      		$max = sprintf("%03d", $max);
		         //This WBS needs to be inserted into the database
		         $insertWBS = "INSERT INTO pev__wbs (wbs_name, project_id, wbs_order) VALUES ('$name','$projectID','$max')";
		         mysql_query($insertWBS) or die("MySQL Error: ".mysql_error()."\n$insertWBS");
		         $id = mysql_insert_id();
	      		}
	      	}
	      //Add association to the array. The $order variable is used as a key because this is how the tasks in the temp_tasks table
	      //refer to thier WBS
	      $wbsAssociation[$order] = array('name' => $name, 'id' => $id);
	   }
	   
		//Insert New Tasks
	   $getNewTasks = "SELECT task_name, wbs, due_date, ec_date, plan_hours, actual_hours, percent_complete, person_id";
	   $getNewTasks.= " FROM pev__temp_tasks WHERE uploader_id='$userID' AND is_wbs='0' AND id='0' ORDER BY wbs";
	   $newTasksResult = mysql_query($getNewTasks) or die("MySQL Error: ".mysql_error()."\n$getNewTasks");
	   while($row = mysql_fetch_array($newTasksResult))
	   {
	   	echo ";) \n\n\n";
	      $taskName = $row['task_name'];
	      $taskName = addslashes($taskName);
	      $wbsHierarchy = $row['wbs'];
	      $wbsArray = explode(".",$row['wbs']);
	      $wbs = $wbsArray[0];
	      $due_date = $row['due_date'];
	      $ec_date = $row['ec_date'];
	      $plan_hours = $row['plan_hours'];
	      $actual_hours = $row['actual_hours'];
	      $percent_complete = $row['percent_complete'];
	      $poc = $row['person_id'];
	      echo "<script>alert('\n\nwbs_number = $wbs\nactual_hours = $actual_hours\n\n');<script>";

	      if(!in_array($wbs, $ignoreTasksArray) && !in_array($wbsHierarchy, $ignoreTasksArray))
	      {
	      	echo "madeit!!";   
	      //Create the task
	         $insertTask = "INSERT INTO pev__task (project_id, task_name) VALUES ('$projectID', '$taskName')";
	         mysql_query($insertTask) or die("MySQL Error: ".mysql_error()."\n$insertTask");
	         $taskID = mysql_insert_id();
	
	         //Associate the task to a WBS
	         $wbs_id = $wbsAssociation[$wbs]['id'];
	         print_r($wbsAssociation);
	         echo "\n\nwbs_id = $wbs_id";
	         $parentId = applyParentIdToTask($wbsHierarchy, $wbs_id, $projectID);
	         $insertWBStoTask = "INSERT INTO pev__wbs_to_task (wbs_id, parent_id, wbs_number, due_date, ec_date, planned_hours, actual_hours, percent_complete, task_id)";
	         $insertWBStoTask.= " VALUES('$wbs_id', '$parentId', '$wbsHierarchy', '$due_date', '$ec_date', '$plan_hours', '$actual_hours', '$percent_complete', '$taskID')";
	         mysql_query($insertWBStoTask) or die("MySQL Error: ".mysql_error()."\n$insertWBStoTask");
	         $wbsToTaskID = mysql_insert_id();
	         
         	//Associate person to the task
				$insertPersonToTask = "INSERT INTO pev__person_to_wbstask (person_id, wbs_to_task_id)";
				$insertPersonToTask.= " VALUES('$poc', '$wbsToTaskID')";
				mysql_query($insertPersonToTask) or die("MySQL Error: ".mysql_error()."\n$insertPersonToTask");
	      }
	   }
		//Update existing Tasks
	   $getCurrentTasks = "SELECT id, task_name, wbs, due_date, ec_date, plan_hours, actual_hours, percent_complete, person_id";
	   $getCurrentTasks.= " FROM pev__temp_tasks WHERE uploader_id='$userID' AND is_wbs='0' AND id !='0'";
	   $currentTasksResult = mysql_query($getCurrentTasks) or die("MySQL Error: ".mysql_error()."\n$getCurrentTasks");
	   while($row = mysql_fetch_array($currentTasksResult)){
	      $taskID = $row['id'];
	      $taskName = $row['task_name'];
	      $wbsHierarchy = $row['wbs'];
	      $wbsArray = explode(".",$row['wbs']);
	      $wbs = $wbsArray[0];
	      $due_date = $row['due_date'];
	      $ec_date = $row['ec_date'];
	      $plan_hours = $row['plan_hours'];
	      $actual_hours = $row['actual_hours'];
	      $percent_complete = $row['percent_complete'];
	      $poc = $row['person_id'];
	      if(!in_array($wbs, $ignoreTasksArray) && !in_array($wbsHierarchy, $ignoreTasksArray)){
	         //Get the WBS to task id
	         $wbs_id = $wbsAssociation[$wbs]['id'];
	         $getWBStoTaskId = "SELECT wbs_to_task_id FROM pev__wbs_to_task WHERE task_id='$taskID' AND wbs_id='$wbs_id'";
	         $wbsToTaskResult = mysql_query($getWBStoTaskId) or die("MySQL Error: ".mysql_error()."\n$getWBStoTaskId");
	         if($wbsToTaskRow = mysql_fetch_array($wbsToTaskResult)){
	            $wbs_to_task_id = $wbsToTaskRow['wbs_to_task_id'];
	         }else{
	            //This association does not exist, so one needs to be created.
	            $insertWBStoTask = "INSERT INTO pev__wbs_to_task (task_id, wbs_id) VALUES ('$taskID', '$wbs_id')";
	            mysql_query($insertWBStoTask) or die("MySQL Error: ".mysql_error()."\n$insertWBStoTask");
	            $wbs_to_task_id = mysql_insert_id();
	         }
	
	         //Update the record
	         $updateRecord = "UPDATE pev__wbs_to_task SET due_date='$due_date', ec_date='$ec_date', planned_hours='$plan_hours',";
	         $updateRecord.= " actual_hours='$actual_hours', percent_complete='$percent_complete' WHERE wbs_to_task_id='$wbs_to_task_id'";
	         mysql_query($updateRecord) or die("MySQL Error: ".mysql_error()."\n$updateRecord");
	         
	         //Get the person to wbstask id and update/insert
	         $getPerson = "SELECT person_to_wbstask_id FROM pev__person_to_wbstask WHERE wbs_to_task_id='$wbs_to_task_id'";
	         $personResult = mysql_query($getPerson)  or die("MySQL Error: ".mysql_error()."\n$getPerson");
	         if($personRow = mysql_fetch_array($personResult)){
	            //Update needed
	            $person_to_wbstask_id = $personRow['person_to_wbstask_id'];
	            $updatePerson = "UPDATE pev__person_to_wbstask SET person_id='$poc' WHERE person_to_wbstask_id='$person_to_wbstask_id'";
	            mysql_query($updatePerson) or die("MySQL Error: ".mysql_error()."\n$updatePerson");
	         }else{
	            //Insert Needed
	            $insertPerson = "INSERT INTO pev__person_to_wbstask (person_id,wbs_to_task_id) VALUES ('$poc', '$wbs_to_task_id')";
	            mysql_query($insertPerson) or die("MySQL Error: ".mysql_error()."\n$insertPerson");
	         }
	      }
	   }
	}

   //Finally remove the data in the data table
   $removeTempData = "DELETE FROM pev__temp_tasks WHERE uploader_id='$userID'";
   mysql_query($removeTempData) or die("MySQL Error: ".mysql_error()."\n$removeTempData");
}

function delete_project_items($projId, $userId){
	echo "<script>alert('MADE IT!!! to the deletion');</script>";
   //This function will remove all WBSs and tasks from a project
   $strSQL2 = "SELECT wbs_id FROM pev__wbs WHERE project_id='$projId'";
   $result2 = mysql_query($strSQL2) or die("MySQL Error: ".mysql_error()."\n$strSQL2");
   while($row2 = mysql_fetch_array($result2)){
      $wbsId = $row2['wbs_id'];

      //Delete associated WBS history records
      $strSQL3 = "DELETE FROM pev__wbs_history WHERE wbs_id='$wbsId'";
      $result3 = mysql_query($strSQL3) or die("MySQL Error: ".mysql_error()."\n$strSQL3");

      //Pull all wbs_to_task_id records so their references can be deleted from the person_to_wbstask table
      $strSQL4 = "SELECT wbs_to_task_id FROM pev__wbs_to_task WHERE wbs_id='$wbsId'";
      $result4 = mysql_query($strSQL4) or die("MySQL Error: ".mysql_error()."\n$strSQL4");
      while($row4 = mysql_fetch_array($result4)){
         $wbsToTaskId = $row4['wbs_to_task_id'];
         $strSQL5 = "DELETE FROM pev__person_to_wbstask WHERE wbs_to_task_id='$wbsToTaskId'";
         $result5 = mysql_query($strSQL5) or die("MySQL Error: ".mysql_error()."\n$strSQL5");
      }

      //Delete wbs_to_task records
      $strSQL6 = "DELETE FROM pev__wbs_to_task WHERE wbs_id='$wbsId'";
      $result6 = mysql_query($strSQL6) or die("MySQL Error: ".mysql_error()."\n$strSQL6");
   }

   //Delete all WBSes associated with a project
   $strSQL7 = "DELETE FROM pev__wbs WHERE project_id='$projId'";
   $result7 = mysql_query($strSQL7) or die("MySQL Error: ".mysql_error()."\n$strSQL7");

   //Delete all Tasks associated with a project
   $strSQL8 = "DELETE FROM pev__task WHERE project_id='$projId'";
   $result8 = mysql_query($strSQL8) or die("MySQL Error: ".mysql_error()."\n$strSQL8");

   //Set all wbs and task ids to 0 in the pev__temp_task table so the process will complete normally
   $updateIDs = "UPDATE pev__temp_tasks SET id='0' WHERE uploader_id='$userId'";
   mysql_query($updateIDs) or die("MySQL Error: ".mysql_error()."\n$updateIDs");

   return;

}

function applyParentIdToTask($wbs, $wbs_id, $projectId) 
// Returns the associated parentId to each task that is being imported
{
	$pos = strrpos($wbs,'.');
	if(!$pos === false) 
	{
		$subWBS = substr($wbs,0,$pos);				
		$query = "SELECT wbs_to_task_id FROM pev__wbs_to_task WHERE wbs_number = '".$subWBS."' AND wbs_id = '$wbs_id'";
		$result = mysql_query($query) or die("MySQL error: ".mysql_error());
		if(mysql_num_rows($result) != 0)
		{
			while($row = mysql_fetch_assoc($result))
			{
				$updateIDs = "UPDATE pev__wbs_to_task SET rollup = '1' WHERE wbs_to_task_id = '".$row['wbs_to_task_id']."'";
   			mysql_query($updateIDs) or die("MySQL Error: ".mysql_error()."\n$updateIDs");
				return $row['wbs_to_task_id'];
			}
		}
		else
		{
			$query2 = "SELECT wbs_id FROM pev__wbs WHERE project_id = '$projectId' AND wbs_order = '$subWBS'";
			$result2 = mysql_query($query2) or die("MySQL error: ".mysql_error());
			while($row2 = mysql_fetch_assoc($result2))
			{
				$markedParent_Id = "w".$row2['wbs_id']; 
				return $markedParent_Id;
			}
		}	
	}
	return null;
}
?>