<?
class Importer{
   private $_fileName = "";
   private $_projectID = 0;
   private $_emptyProject = false;
   private $_percentCompleteCol = -1;
   private $_plannedWorkCol = -1;
   private $_actualWorkCol = -1;
   private $_actualEndDateCol = -1;
   private $_startDateCol = -1;
   private $_endDateCol = -1;
   private $_resourceNameCol = -1;
   private $_taskNameCol = -1;
   private $_wbsCol = -1;
   private $_plannedDuration = false;
   private $_actualDuration = false;
   private $_importedWBSArray = array();
   private $_importedTaskArray = array();
   private $_uploaderID = 0;

   private $_usersArray = array();
   private $_currentTaskArray = array();
   private $_currentWBSArray = array();

   private $_errorMessage = "";

   function __construct($fileName, $projectID, $uploaderID){
      $fileName = preg_replace("/\s*/","",$fileName);
      if($fileName != ""){
         $this->_fileName = $fileName;
      }else{
         $this->_errorMessage.= "An invalid file name has been given.<br/>";
      }

      if($projectID > 0){
         $this->_projectID = $projectID;
      }else{
         $this->_errorMessage.= "An invalid project ID has been given.<br/>";
      }

      if($uploaderID > 0){
         $this->_uploaderID = $uploaderID;
      }else{
         $this->_errorMessage.= "An invalid uploader ID has been given.<br/>";
      }

      //Delete the previous entries for this uploader user
      $deleteEntries = "DELETE FROM pev__temp_tasks WHERE uploader_id='".$this->_uploaderID."'";
      mysql_query($deleteEntries) or die("MySQL Error: ".mysql_error());
   }

   private function setColumn($columnName, $number){
      //This function will set the column number for the given column name
      $columnName = trim($columnName);
      switch($columnName){
         case "Name":
            $this->_taskNameCol = $number;
            break;
         case "Start_Date":
            $this->_startDateCol = $number;
            break;
         case "End_Date":
         case "Finish_Date":
         case "Baseline Finish":
         case "Baseline1 Finish":
         case "Baseline2 Finish":
         case "Baseline3 Finish":
         case "Baseline4 Finish":
         case "Baseline5 Finish":
         case "Baseline6 Finish":
         case "Baseline7 Finish":
         case "Baseline8 Finish":
         case "Baseline9 Finish":
         case "Baseline10 Finish":
            $this->_endDateCol = $number;
            break;
         case "Actual_Finish":
            $this->_actualEndDateCol = $number;
            break;
         case "Percent_Complete":
            $this->_percentCompleteCol = $number;
            break;
         case "Resource_Names":
         case "Resources":
            $this->_resourceNameCol = $number;
            break;
         case "Duration":
            //User has uploaded a file with the number of days instead of hours
            $this->_plannedDuration = true;
            //No break here
         case "Work":
         case "Scheduled_Work":
            $this->_plannedWorkCol = $number;
            break;
         case "Actual_Duration":
            //User has uploaded a file with the number of days instead of hours
            $this->_actualDuration = true;
            //No break here
         case "Actual_Work":
            $this->_actualWorkCol = $number;
            break;
         case "WBS":
            $this->_wbsCol = $number;
            break;
      }
   }

   private function getProjectData(){

      //Determine if this is an empty project
      $getWBS = "SELECT wbs_id, wbs_name FROM pev__wbs WHERE project_id='".$this->_projectID."'";
      $result = mysql_query($getWBS);
      if($result != false){
         if(mysql_num_rows($result) > 0){
            while($row = mysql_fetch_array($result)){
               $this->_currentWBSArray[$row['wbs_id']] = $row['wbs_name'];
            }
         }else{
            //By definition, this project is empty because it does not have any WBSs
            $this->_emptyProject = true;
         }
      }else{
         $this->_errorMessage.= "MySQL Error: ".mysql_error()."<br/>";
         return false;
      }

      //Find any tasks that may be part of this project
      $getTasks = "SELECT task_id, task_name FROM pev__task WHERE project_id='".$this->_projectID."'";
      $result = mysql_query($getTasks);
      if($result != false){
         while($row = mysql_fetch_array($result)){
            //An operation needs to be done to separate the task name from the order number
            $task = explode(" ", $row['task_name'], 2);
            if(is_numeric($task[0])){
               $this->_currentTaskArray[$row['task_id']] = $task[1];
            }else{
               $this->_currentTaskArray[$row['task_id']] = $row['task_name'];
            }
         }
      }else{
         $this->_errorMessage.= "MySQL Error: ".mysql_error()."<br/>";
         return false;
      }

      //Find any users belonging to the project
      $getUsers = "SELECT pa.person_id, first, last, username FROM pev__project_access AS pa";
      $getUsers.= " LEFT JOIN pev__person AS p ON p.person_id=pa.person_id";
      $getUsers.= " WHERE pa.project_id='".$this->_projectID."'";
      $result = mysql_query($getUsers);
      if($result != false){
         while($row = mysql_fetch_array($result)){
            $fullName = $row['first']." ".$row['last'];
            $this->_usersArray[] = array('person_id' => $row['person_id'],
                                         'full_name' => $fullName,
                                         'given_name' => $row['username']);
         }
      }else{
         $this->_errorMessage.= "MySQL Error: ".mysql_error()."<br/>";
         return false;
      }

      return true;

   }

   public function readData(){
      $loadData = $this->getProjectData();
      if(!$loadData){
         //An error occured somewhere while getting the project data
         return false;
      }

      //Open file and get header information
      $file_handle = fopen($this->_fileName,'r');
      if(!$file_handle){
         //Cannot open the file
         $this->_errorMessage.= "File Error: Cannot open file<br/>";
         return false;
      }
      
      $header = fgets($file_handle, 4096);
      $columns = explode(",",$header);
      foreach($columns as $index=>$col){
            $this->setColumn($col, $index);
      }
      //Check columns to see if they are set properly
      $fileSafe = $this->checkColumns();
      if(!$fileSafe){
         $this->_errorMessage.= "PAEV could not properly import the file. Please check the file format and try again.<br/>";
         return false;
      }

      //Begin parsing the file information
      $dataExists = false;
      try{
         while($buffer = fgets($file_handle, 4096)){
            $dataExists = true;
            /*There are two ways a line must be parsed:
              The first way is simply separating by commas (the most common way)
              The second way is separating by quotes, then commas because the resource names column may have commas in it
            */
            //Check for quotes...
            $check = explode('"', $buffer);
            if(sizeof($check) == 1){
               //This line is a normal line without quotes
               $data = explode(",", $buffer);
            }else{
               //$data contains may contain three parts, but index 1 should always contain the resource names
               $data = explode(",", $check[0]);
               array_pop($data);    //There is a null element that needs to be removed
               $data[] = $check[1];
               $remainingData = explode(",", $check[2]);
               array_shift($remainingData); //There is a null element that needs to be removed
               foreach($remainingData AS $el){
                  $data[] = $el;
               }
            }
            $personID = $this->checkUser($data[$this->_resourceNameCol]);
            $personName = trim($data[$this->_resourceNameCol]);
            $taskName = trim($data[$this->_taskNameCol]);
            $is_wbs = $this->is_wbs($data[$this->_wbsCol]);
            $taskID = $this->checkTask($taskName, $is_wbs);
            $wbs = trim($data[$this->_wbsCol]);
            $dueDate = $this->formatDate($data[$this->_endDateCol]);
            $ecDate = $this->formatDate($data[$this->_actualEndDateCol]);
            $planHours = $this->getHours($data[$this->_plannedWorkCol], true);
            $actualHours = $this->getHours($data[$this->_actualWorkCol], false);
            $percentComplete = trim($data[$this->_percentCompleteCol]);
            $this->saveTask($personID, $personName, $taskID, $taskName, $is_wbs, $wbs, $dueDate, $ecDate, $planHours, $actualHours, $percentComplete);
         }
      }catch(Exception $e){
         $this->_errorMessage.= "Caught exception: ".$e->getMessage()."<br/>";
         fclose($file_handle);
         return false;
      }
      if(!feof($file_handle)){
         //An error occurred somewhere during fgets() processing
         return false;
      }
      if(!$dataExists){
         $this->_errorMessage.= "It appears there is no data in the imported file. Please check the file and try again<br/>";
         return false;
      }
      fclose($file_handle);
      return true;
   }

   public function getErrors(){
      return $this->_errorMessage;
   }

   private function checkColumns(){
      //This makes sure that all of the columns are recognized and returns false if a critical column could not be found in the import file
      $error = false;
      if($this->_percentCompleteCol == -1){
         $this->_errorMessage.= "Missing or incorrectly named Percent_Complete column<br/>";
         $error = true;
      }
      if($this->_plannedWorkCol == -1){
         $this->_errorMessage.= "Missing or incorrectly named Scheduled_Work/Duration column<br/>";
         $error = true;
      }
      if($this->_actualWorkCol == -1){
         $this->_errorMessage.= "Missing or incorrectly named Actual_Work/Actual_Duration column<br/>";
         $error = true;
      }
      if($this->_actualEndDateCol == -1){
         $this->_errorMessage.= "Missing or incorrectly named Actual_Finish column<br/>";
         $error = true;
      }
      if($this->_startDateCol == -1){
         //Not having a start date is okay.
         //$this->_errorMessage.= "Could not find Start Date column<br/>";
         //return false;
      }
      if($this->_endDateCol == -1){
         $this->_errorMessage.= "Missing or incorrectly named Finish_Date/Baseline_Finish column<br/>";
         $error = true;
      }
      if($this->_resourceNameCol == -1){
         $this->_errorMessage.= "Missing or incorrectly named Resource_Names column<br/>";
         $error = true;
      }
      if($this->_taskNameCol == -1){
         $this->_errorMessage.= "Missing or incorrectly named Name column<br/>";
         $error = true;
      }
      if($this->_wbsCol == -1){
         $this->_errorMessage.= "Missing or incorrectly named WBS column<br/>";
         $error = true;
      }

      if($error){
         //Could not find one or more columns, so report an error
         return false;
      }else{
         //No errors so far! Report success.
         return true;
      }
   }

   private function getHours($hours, $planned){
      $hours = trim($hours);
      $hoursArray = explode(" ", $hours);
      if($planned){
         if($this->_plannedDuration){
            //This will convert days into hours (8 hours per day)
            return ($hoursArray[0] * 8);
         }else{
            return $hoursArray[0];
         }
      }else{
         if($this->_actualDuration){
            //This will convert days into hours (8 hours per day)
            return ($hoursArray[0] * 8);
         }else{
            return $hoursArray[0];
         }
      }
   }

   private function saveTask($personID, $personName, $taskID, $taskName, $is_wbs, $wbs, $dueDate, $ecDate, $planHours, $actualHours, $percentComplete){
      //This function saves the uploaded information into a temporary table
      if($ecDate == 0 && $dueDate != 0){
         $ecDate = $dueDate;
      }
      $insertTask = "INSERT INTO pev__temp_tasks (id, task_name, is_wbs, wbs, due_date, ec_date, plan_hours, actual_hours, percent_complete, person_id, person_name, uploader_id)";
      $insertTask.= " VALUES('$taskID', '$taskName', '$is_wbs', '$wbs', '$dueDate', '$ecDate', '$planHours', '$actualHours', '$percentComplete', '$personID',";
      $insertTask.= " '$personName', '".$this->_uploaderID."')";
      if(!mysql_query($insertTask)){
         throw new Exception("MySQL Error: ".mysql_error());
      }
   }

   private function checkTask($taskName, $is_wbs){
      //This function will determine if the task already exists or if it is a WBS
      if($this->_emptyProject){
         return 0;
      }
      $taskName = trim($taskName);
      //$taskNameArray = explode(" ", $taskName);
      //if($taskNameArray[0] == "WBS"){
      if($is_wbs){
         //This is a WBS
         if(in_array($taskName, $this->_currentWBSArray)){
            return array_search($taskName, $this->_currentWBSArray);
         }else{
            return 0;
         }
      }

      //See if this task is already a part of the project.
      if(in_array($taskName, $this->_currentTaskArray)){
         return array_search($taskName, $this->_currentTaskArray);
      }else{
         return 0;
      }
   }

   private function is_wbs($wbs){
      $wbs = trim($wbs);
      $wbsArray = explode(".",$wbs);
      if(sizeof($wbsArray) == 1){
         return 1;
      }else{
         return 0;
      }
   }

   private function formatDate($date){
      //$date will be in the following format: xxx mm/dd/yy
      $date = trim($date);
      if($date == "" || $date == "NA"){
         return 0;
      }
      $dateArray = explode(" ",$date);
      if(sizeof($dateArray) != 2){
         //This is not a formatted date
         return 0;
      }

      $dateSplit = explode("/",$dateArray[1]);
      if(sizeof($dateSplit) != 3){
         //This is not a formatted date
         return 0;
      }

      //This will be converted to a unix timestamp
      return mktime(0,0,0,$dateSplit[0],$dateSplit[1],$dateSplit[2]);
   }

   private function checkUser($userName){
      //This function checks to see if the user exists in the user array
      //If not then it checks the database to find the user
      //If the user does not exist in the db, it will set a flag (person_id) to notify the user
      $userName = trim($userName);
      if(strlen($userName) == 0){
         return 0;
      }

      foreach($this->_usersArray as $user){
         if(in_array($userName, $user)){
            return $user['person_id'];
         }
      }

      //This user was not found in the array
      //Check database
      $userNameArray = explode(" ", $userName);
      $first = $userNameArray[0];
      if(isset($userNameArray[1])){
         $last = $userNameArray[1];
      }
      $person_id = 0;
      $findUser = "SELECT person_id, CONCAT(first,' ',last) AS name FROM pev__person WHERE first LIKE '%$first%'";
      if(isset($userNameArray[1])){
         $findUser.= " AND last LIKE '%$last%'";
      }
      $findUser.= " LIMIT 1";
      $result = mysql_query($findUser) or die("MySQL Error: ".mysql_error());
      if($row = mysql_fetch_array($result)){
         $person_id = $row['person_id'];
         $fullName = $row['name'];
         $this->_usersArray[] = array('person_id' => $person_id,
                                      'full_name' => $fullName,
                                      'given_name' => $userName);
      }else{
         //User was not found in the database
         $this->_usersArray[] = array('person_id' => 0,
                                      'full_name' => 'Unknown',
                                      'given_name' => $userName);
      }
      return $person_id;
   }

}
?>