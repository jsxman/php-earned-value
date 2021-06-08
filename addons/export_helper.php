<?
function print_header(){
   //This function simply prints the header for the export file
//   echo "Name";
//   echo ",Start_Date";
//   echo ",Finish_Date";
//   echo ",Actual_Finish";
//   echo ",Percent_Complete";
//   echo ",Scheduled_Work";
//   echo ",Actual_Work";
//   echo ",WBS";
//   echo ",Resource_Names";
//   echo "\n";
   
   echo "Percent_Work_Complete";
   echo "\tActual_Finish";
   echo "\tActual_Work";
   echo "\tFinish_Date";
   echo "\tName";
   echo "\tScheduled_Work";
   echo "\tResource_Names";
   echo "\tWBS";
   echo "\n";
}


function print_data(){
   $tasksArray = getProjectData();
   foreach($tasksArray AS $task){
//      echo $task['Name'];
//      echo ",".$task['Start_Date'];
//      echo ",".$task['Finish_Date'];
//      echo ",".$task['Actual_Finish'];
//      echo ",".$task['Percent_Complete'];
//      echo ",".$task['Scheduled_Work'];
//      echo ",".$task['Actual_Work'];
//      echo ",".$task['WBS'];
//      echo ",".$task['Resources'];
//      echo "\n";
      
      echo $task['Percent_Complete'];
      echo "\t".$task['Actual_Finish'];
      echo "\t".$task['Actual_Work'];
      echo "\t".$task['Finish_Date'];
      echo "\t".$task['Name'];
      echo "\t".$task['Scheduled_Work'];
      echo "\t".$task['Resources'];
      echo "\t".$task['WBS'];
      echo "\n"; 
   }
}

function getProjectData(){
   $projectID = $_SESSION['projectID'];
   $tasksArray = array();
   $wbsCount = 1;
   //Get all WBSs
   $getWBS = "SELECT wbs_id, wbs_name, wbs_order, due_date, ec_date, planned_hours, actual_hours, percent_complete FROM pev__wbs WHERE project_id='$projectID' ORDER BY wbs_order";
   $wbsResult = mysql_query($getWBS) or die("An error occurred while processing your request");
   while($wbsRow = mysql_fetch_array($wbsResult)){
      $wbsID = $wbsRow['wbs_id'];
      $wbsName = $wbsRow['wbs_name'];
      $taskCount = 1;
      $tempArray = array();   //Temporarily holds the data so it can be cleanly inserted later
      $wbsStart = 0;
      $wbsFinish = $wbsRow['due_date'];
      $wbsActualFinish = $wbsRow['ec_date'];
      $wbsScheduledWork = $wbsRow['planned_hours'];
      $wbsActualWork = $wbsRow['actual_hours'];
      $plannedPercent = $wbsRow['percent_complete'];
      $wbsComplete = true;
      $wbsnumber = 1;
	  
      //Now that we have the WBS, we can join all the related task information
      $getTasks = "SELECT t.task_name, wtt.due_date, wtt.ec_date, wtt.planned_hours, wtt.actual_hours, wtt.percent_complete, wtt.wbs_number";
      $getTasks.= ", p.first, p.last FROM pev__wbs_to_task AS wtt";
      $getTasks.= " LEFT JOIN pev__task AS t ON t.task_id=wtt.task_id";
      $getTasks.= " LEFT JOIN pev__person_to_wbstask AS ptw ON ptw.wbs_to_task_id=wtt.wbs_to_task_id";
      $getTasks.= " LEFT JOIN pev__person AS p ON ptw.person_id=p.person_id";
      $getTasks.= " WHERE wtt.wbs_id='$wbsID' ORDER BY due_date";
      $taskResult = mysql_query($getTasks) or die("MySQL Error: ".mysql_error()."\n$getTasks");
	  
	  
      while($taskRow = mysql_fetch_array($taskResult)){
         
         $wbsnumber = $taskRow[wbs_number];
         

         //Make sure the times are set to how MS Project would report it
         $raw_start_date = calculate_start_date($taskRow['due_date'], $taskRow['planned_hours']);
		 
		 

         if($wbsStart == 0){
            $wbsStart = $raw_start_date;
         }else if($wbsStart > $raw_start_date){
            $wbsStart = $raw_start_date;
         }
		 
		 
         $start_date = format_project_date($raw_start_date);
         $finish_date = format_project_date($taskRow['due_date']);
         if($taskRow['percent_complete'] == 100){
            $actual_finish = format_project_date($taskRow['ec_date']);
         }else{
            $actual_finish = "NA";
            $wbsComplete = false;     //WBS is not complete yet
         }

         
       //  if($taskRow['percent_complete'] == 100){
            
       //  }else{
       //     $actual_finish = "NA";
       //     $wbsComplete = false;     //WBS is not complete yet
       //  }
         //Get the WBS hierarchy number
         $WBS = "$wbsCount.$taskCount";
         $taskCount++;
         $tempArray[] = array('Name'             => $taskRow['task_name'],
                              'Start_Date'       => $start_date,
                              'Finish_Date'      => $finish_date,
                              'Actual_Finish'    => $actual_finish,
                              'Percent_Complete' => $taskRow['percent_complete']."%",
                              'Scheduled_Work'   => $taskRow['planned_hours']." hrs",
                              'Actual_Work'      => $taskRow['actual_hours']." hrs",
                              'WBS'              => $wbsnumber,
                              'Resources'        => $taskRow['first']." ".$taskRow['last']);
      }
      
         $wbsActualFinish = format_project_date($wbsActualFinish);
      
     
         $per_complete = $plannedPercent;
    
       
	  $getTasks2 = "SELECT wbs_number";
      $getTasks2.= " FROM pev__wbs_to_task WHERE wbs_id='$wbsID'";
	  $wResult = mysql_query($getTasks2) or die("MySQL Error: ".mysql_error()."\n$getTasks2");
	  $wRow = mysql_fetch_array($wResult);
	  $wbsnumber = $wRow['wbs_number'];
	  
      //Fill in the tasksArray with all data gathered
     $tasksArray[] = array('Name'             => $wbsName,
                            'Start_Date'       => format_project_date($wbsStart),
                            'Finish_Date'      => format_project_date($wbsFinish),
                            'Actual_Finish'    => $wbsActualFinish,
                            'Percent_Complete' => $per_complete."%",
                            'Scheduled_Work'   => $wbsScheduledWork." hrs",
                            'Actual_Work'      => $wbsActualWork." hrs",
                            'WBS'              => $wbsCount,
                            'Resources'        => "");

      foreach($tempArray AS $task){
         $tasksArray[] = array('Name'             => $task['Name'],
                               'Start_Date'       => $task['Start_Date'],
                               'Finish_Date'      => $task['Finish_Date'],
                               'Actual_Finish'    => $task['Actual_Finish'],
                               'Percent_Complete' => $task['Percent_Complete'],
                               'Scheduled_Work'   => $task['Scheduled_Work'],
                               'Actual_Work'      => $task['Actual_Work'],
                               'WBS'              => $task['WBS'],
                               'Resources'        => $task['Resources']);
      }
      //delete the temp array
      $tempArray = array();
      $wbsCount++;
   }

   return $tasksArray;
}

function calculate_start_date($due_date, $planned_hours){
   //Need to calculate the start date for the task according to the due date and the planned hours
   //Assumption is made that there are 8 hours in the day and work days are Mon-Fri
   if($planned_hours == 0){
      return $due_date;
   }
   //Break the task length into 8 hr work days units
   //MS Project says that an 8 hour task should be started and finished on the same day so we need to account for that
   //by subtracting a day from $workDays to get the right calculation
   $workDays = intval($planned_hours/8) - 1;
   if($workDays < 0){
      $workDays == 0;
   }
   //Get the start date based on the number of work days (86400 secs in a day)
   $start_date = $due_date - ($workDays*86400);
   $dayofWeek = date("N", $start_date);
   if($dayofWeek == 7){
      //This is a Sunday, so roll back two more days to Friday
      $start_date -= 2*86400;
   }else if($dayofWeek == 6){
      //This is a Saturday, so roll back one day to Friday
      $start_date -= 86400;
   }

   return $start_date;
}

function format_project_date($date){
   //$date is in UNIX epoch time format
   //Need to convert it to a (www mm/dd/yy) format where www stands for the day of the week
   return date("D n/j/y", $date);
}
?>