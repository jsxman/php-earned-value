<?
/**************************************************************************
 *  PAEV - PHP Adjusted Earned Value                                      *
 *          (paev.JS-X.com)                                               *
 *                                                                        *
 *  This program is free software: you can redistribute it and/or modify  *
 *  it under the terms of the GNU General Public License as published by  *
 *  the Free Software Foundation, either version 3 of the License, or     *
 *  any later version.                                                    *
 *                                                                        *
 *  This program is distributed in the hope that it will be useful,       *
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of        *
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         *
 *  GNU General Public License for more details.                          *
 *                                                                        *
 *  You should have received a copy of the GNU General Public License     *
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>. *
 **************************************************************************/

if(!isset($HACK_CHECK) || !$HACK_CHECK)exit; // DO NOT DIRECTLY LOAD THIS FILE
if($DEBUG>10)echo"DEBUG(10): Loading File: generalFunctions.inc.php<BR>\n";

function set_error($str)
{
  global $ERROR;
  if(strlen($str)) $ERROR[count($ERROR)]=$str;
}
function show_version()
{
  global $PAEV_VERSION;
  echo "<fieldset class=version><legend><b>Version</b></legend>";
  echo "<table width=100%><tr><td align=right>PAEV Version $PAEV_VERSION.</td></tr></table>\n";
  echo "</fieldset>";
}
function show_pageloadtime()
{
  global $PAGE_TIME_START;
  $currenttime=microtime();
  $temp=$currenttime-$PAGE_TIME_START;
  echo "<fieldset class=pagetime><legend><b>Page Load Time</b></legend>";
  echo "<table width=100%><tr><td align=right>This page loaded in $temp seconds.</td></tr></table>\n";
  echo "</fieldset>";
}
function show_error()
{
  global $ERROR;
  if(!count($ERROR))return;
  echo "<fieldset class=error><legend><b>ERROR MESSAGE</b></legend>";
  echo "<table width=100%><tr><td>";
  print "<ul>";
  for($i=0;$i<count($ERROR);$i++)
    print "<li>".$ERROR[$i]."</li>\n";
  print "</ul></td></tr></table>";
  echo "</fieldset>";
}
function set_status($str)
{
  global $STATUS;
  if(strlen($str)) $STATUS[count($STATUS)]=$str;
}
function show_status()
{
  global $STATUS;
  if(!count($STATUS))return;
  echo '<div style="position:relative; top:-170px; left:-10px;"> <fieldset class=status><legend><b>STATUS MESSAGE</b></legend>';
  echo "<table width=100%></tr><tr><td>";
  print "<ul>";
  for($i=0;$i<count($STATUS);$i++)
    print "<li>".$STATUS[$i]."</li>\n";
  print "</ul></td></tr></table>";
  echo "</fieldset></div>";
}
function set_todo($str)
{
  global $TODO;
  if(strlen($str)) $TODO[count($TODO)]=$str;
}
function show_todo()
{
  global $TODO;
  if(!count($TODO))return;
  echo "<fieldset class=warn><legend><b>To Do List</b></legend>";
  print "<table width=100%><tr><td><ul>";
  for($i=0;$i<count($TODO);$i++)
    print "<li>".$TODO[$i]."</li>";
  print "</ul></td></tr></table>";
  echo "</fieldset>";
}
function show_menu($_which)
{
  global $MENU_FILE;
  global $DEBUG;
  debug(10,"Function: show_menu()");
  if(isset($MENU_FILE) && file_exists($MENU_FILE))
    Include($MENU_FILE);
  else
    show_error("File not found: \$MENU_FILE ($MENU_FILE).<BR>\nYou must edit the config/global.inc.php file.<BR>\n");
}
function show_header()
{
  global $HEADER_FILE;
  global $DEBUG;
  debug(10,"Function: show_header()");
  if(isset($HEADER_FILE) && file_exists($HEADER_FILE))
    Include($HEADER_FILE);
  else
    show_error("File not found: \$HEADER_FILE ($HEADER_FILE).<BR>\nYou must edit the config/global.inc.php file.<BR>\n");
}
function show_footer()
{
  global $FOOTER_FILE;
  //show_todo();
  //show_pageloadtime();
  //show_dbstat();
  //show_version();
  if(isset($FOOTER_FILE) && file_exists($FOOTER_FILE))
    Include($FOOTER_FILE);
  else
    show_error("File not found: \$FOOTER_FILE ($FOOTER_FILE).<BR>\nYou must edit the config/global.inc.php file.<BR>\n");
}
function show_permission_error()
{
  echo "<h1>You do not have permission to view this page for the selected project.</h1><br />";
  echo "<h4>Your permissions have been set by the admistrator for this project.</h4>"; 
}
function debug($level,$str)
{
  global $DEBUG;
  if($DEBUG>$level)
    print "<table width=100% class=debug><tr><td>DEBUG($level): $str</td></tr></table>\n";
}

  Function getPostedData()
  {
    global $_POST,$_GET;
    global $_FORM;
    global $DEBUG;
    global $_SESSION;

    $test="";
    if(isset($_SESSION['TEST']))$test=$_SESSION['TEST'];
    $str="";
    $str.="TESTING=$test<BR>";
    $str.="SESSION-FORM<BR>\n";
    /* get the saved FORM data from a session tie out if it exists */
    if(isset($_SESSION['FORM']))
    {
      $str.= "XXXX1XXXX<BR>\n";
      $x=unserialize($_SESSION['FORM']);
      $str.="x:<DIR>$x</DIR>\n";
      if(is_array($x))
      {
        $str.= "XXXX2XXXX<BR>\n";
        reset($x);
        {
          while(list($name,$value)=each($x))
          {
            $str.= "XXXX3XXXX $name , $value<BR>\n";
            if(!is_array($value))
            {
              $_FORM[$name]=$value;
              $str.= "[SESSION-FORM] $name=\"$value\"<BR>\n";
            }
            else
            {
              while(list($name2,$value2)=each($x[$name]))
              {
                $str.= "XXXX4XXXX $name , $value<BR>\n";
                if(!is_array($_FORM[$name]))
                {
                  $_FORM[$name]=array($name2=>$value2);
                  $str.= "[SESSION-FORM]".$name."[".$name2."]=\"$value2\"<BR>\n";
                }
                else
                {
                  $_FORM[$name][$name2]=$value2;
                  $str.= "[SESSION-FORM]".$name."[".$name2."]=\"$value2\"<BR>\n";
                }
              }
            }
          }
        }
      }
    } /* end of _SESSION['FORM'] */
    //unset($_SESSION['FORM']);

    $str.="GET-FORM<BR>\n";
    reset($_GET);
    while(list($name,$value)=each($_GET))
    {
      if(!is_array($value))
      {
        $_FORM[$name]=$value;
        $str.= "$name=\"$value\"<BR>\n";
      }
      else
      {
        while(list($name2,$value2)=each($_GET[$name]))
        {
          if(!is_array($_FORM[$name]))
          {
            $_FORM[$name]=array($name2=>$value2);
            $str.= $name."[".$name2."]=\"$value2\"<BR>\n";
          }
          else
          {
            $_FORM[$name][$name2]=$value2;
            $str.= $name."[".$name2."]=\"$value2\"<BR>\n";
          }
        }
      }
    }
    $str.="POST-FORM<BR>\n";
    reset($_POST);
    while(list($name,$value)=each($_POST))
    {
      if(!is_array($value))
      {
        $_FORM[$name]=$value;
        $str.= "$name=\"$value\"<BR>\n";
      }
      else
      {
        while(list($name2,$value2)=each($_POST[$name]))
        {
          if(!is_array($_FORM[$name]))
          {
            $_FORM[$name]=array($name2=>$value2);
            $str.= $name."[".$name2."]=\"$value2\"<BR>\n";
          }
          else
          {
            $_FORM[$name][$name2]=$value2;
            $str.= $name."[".$name2."]=\"$value2\"<BR>\n";
          }
        }
      }
    }
    return $str;
  }

// keep this old method around just in case we need it later...
  Function getPostedData2()
  {
    global $_POST,$_GET;
    global $_FORM;
    global $DEBUG;
    for(reset($_POST);$key=key($_POST);next($_POST))
    {
      $_FORM[$key] =  $_POST[$key];
      if($DEBUG)print "$key=&quot;".$_POST[$key]."&quot;<BR>\n";
    }
                                                                                                                                                                                                        
    for(reset($_GET);$key=key($_GET);next($_GET))
    {
      $_FORM[$key] =  $_GET[$key];
      if($DEBUG)print "$key=&quot;".$_GET[$key]."&quot;<BR>\n";
    }
  }

  Function encryptData($m) {
     global $encryptKey;

     if(function_exists('mcrypt_create_iv') && function_exists('mcrypt_encrypt') && function_exists('mcrypt_decrypt'))
     {
       $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_BLOWFISH,MCRYPT_MODE_CBC),MCRYPT_RAND);
       $c = mcrypt_encrypt(MCRYPT_BLOWFISH, $encryptKey, $m, MCRYPT_MODE_CBC, $iv);
       // encode and tack on the iv
       $c1 = base64_encode($c . "\$IV\$" . $iv);
       return $c1;
     }
     else
     {
       return $m;
     }
  }

/*Create function that validates Integer input*/
function validInt($v)
{
	$v=preg_replace("/[^0-9]+/","",$v);
	$v=mysql_real_escape_string($v);
    	return $v;
}

/*Create function that validates floating point input*/
function validFloat($v)
{

        $v = floatval($v);
	if(is_nan($v)){
           $v = 0;
        }else{
           $v = round($v,1);
        }
	$v=mysql_real_escape_string($v);
    	return $v;
}

/*all data goes through here - can validate or do security here later...*/
function postedData($v)
{
	/* only allow letters or numbers or spaces or period */
	$v=preg_replace("/[^a-zA-Z0-9\-:\/\. ]/","",$v);
	/*This function escapes special characters and prevents mySQL injection*/
	$v = mysql_real_escape_string($v);
	return $v;
}

/* input is MM/DD/YYYY or MM/DD/YY, output is seconds since the EPOC */
function date_save($d)
{
	$val=0;
	if(!strlen($d))
		return 0;

	$_d=explode("/",$d);
	if(!isset($_d)||!isset($_d[2]))
		return 0;

	if($_d[2]<1999)
		$_d[2]+=2000;

	$val=date("U",mktime(0,0,0,$_d[0],$_d[1],$_d[2]));

	return $val;
}

/* output is MM/DD/YYYY, input is seconds since the EPOC */
function date_read($d)
{
	if(strlen($d) && $d)
		return date("n/j/Y",$d);
	else
		return "";
}


  Function unEncryptData($c) {
     global $encryptKey;

     if(function_exists('mcrypt_create_iv') && function_exists('mcrypt_encrypt') && function_exists('mcrypt_decrypt'))
     {
       // decode and get the iv off
       list($c1,$iv)=explode("\$IV\$",base64_decode($c));
       $m = mcrypt_decrypt(MCRYPT_BLOWFISH,$encryptKey,$c1,MCRYPT_MODE_CBC,$iv);
       return rtrim($m);
     }
     else
     {
       return $c;
     }
  }
  $rowStyle=0; // initial value
  Function resetRowColor()
  {
     global $rowStyle;
     $rowStyle=0;
  }
/*use this to set the class tag of alternating rows in tables (in conjunction with stylesheet)*/
Function alternateRowColor() 
{
	global $rowStyle;
	$rowStyle ++;
	If ($rowStyle%2 == 1) 
	{
        	Return "row1";
	} 
	Else 
	{
        	Return "row2";
	}
}

function getProject($strProject)
{
	//$strProject = isset($_FORM['txt_project'])?postedData($_FORM['txt_project']):0;
	if($strProject)
	{
		debug(10,"was set($strProject)");
		$_SESSION['projectID'] = $strProject;
	}
	$strProject = $_SESSION['projectID'];
	return $strProject;
}

function isProjectAdmin($strProject, $userId)
{
	#Conduct permissions check for user level for a project
	$strSQL = "SELECT is_admin FROM pev__project_access WHERE project_id='$strProject'";
	$strSQL.= " AND person_id='$userId'";
	$result = dbquery($strSQL);
	$row = mysql_fetch_array($result);
	$projectAdmin = $row['is_admin'];
	
	return $projectAdmin;
}

function projectSelector($page, $strProject)
{
	/* DETERMINE WHICH PROJECTS THIS PERSON CAN EDIT/VIEW */
	if($_SESSION['dbAdmin'])
	{
  		/* do the dbquery outside the select list so the debug will appear */
  		$strSQL0 = "SELECT project_id, project_name FROM pev__project WHERE associated_id is NULL ORDER BY project_name ASC,project_id ASC";
	}
	else
	{
  		/* do the dbquery outside the select list so the debug will appear */
 		$strSQL0 = "SELECT P.project_id, P.project_name FROM pev__project_access AS PA";
  		$strSQL0.= " LEFT JOIN pev__project AS P ON P.project_id=PA.project_id";
  		$strSQL0.= " WHERE PA.person_id='".$_SESSION['paev_userID']."' AND associated_id is NULL";
  		$strSQL0.= " ORDER BY P.project_name ASC, P.project_id ASC";
	}
	$result0 = dbquery($strSQL0);
?>

<BR>
<form action="<?=$page;?>" method="POST" name="projectSelect">
<fieldset>
	<legend><b>Select Project</b></legend>
	<table align="center">
		<tr>
			<td>Pick Project: </td>
			<td><select name="txt_project" id="sel">
<?
	while($row0 = mysql_fetch_array($result0))
	{
  		$t1=$row0['project_id'];
  		$t2=$row0['project_name'];
  		$SEL="";if($t1==$strProject) $SEL="SELECTED";
  		echo "\t\t\t\t\t<option $SEL value='$t1'>$t2</option>\n";

	}
?>
			</select></td>
			<td><input class="but" type="submit" value="Do Now" name="txt_action" id="projectSelectButton"></td>
		</tr>
	</table>
</fieldset>
</form>

<?
}/*END pageSelector FUNCTION*/

Function show_rules(){
 $fileName="";
 $fileName="admin/testfile.txt";
 $fileHandle=fopen($fileName,'r');
 $loginType= fread($fileHandle, 16);
 fclose($fileHandle);
?>
 <fieldset><legend><b>Available Rules</b></legend>
 <table class=list>
 <tr>
 <th class=rulehead>Rule</th><th class=rulehead>Current Configuration</th>
 </tr>
 <tr class=row0>
 <td class=rule>Login Process</td><?echo "<td class=rule>$loginType</td>";?>
 </table>
 </fieldset>
<?
}

function log_action($str){
   $logFile = "admin/log.txt";
   $fh = fopen($logFile, 'a');
   $time = date("M d y H:i:s");
   fwrite($fh, "$time - $str");
   fclose($fh);
}

function replanProject($project_id) {

   //Record project history
   recordHistory($project_id);

   //Gather old project information
   $oldProjectInfo = "SELECT project_name, revision FROM pev__project WHERE project_id='$project_id' LIMIT 1";
   $infoResult = dbquery($oldProjectInfo);

   $infoRow = mysql_fetch_array($infoResult);

   $projectName = $infoRow['project_name'];
   $revision = $infoRow['revision'];
   $today = date("U");

   //Create new project
   $revision += 1;
   $projectNameArray = explode("- rev", $projectName);
   $projectNameArray[0] = rtrim($projectNameArray[0]);
   $newProjectName = "$projectNameArray[0] - rev $revision";
   $associated_id = $project_id;
   $start_date = date_save(date_read($today));

   $createProject = "INSERT INTO pev__project (project_name, start_date, active, locked, revision)";
   $createProject.= " VALUES('$newProjectName', '$start_date', '1', '0', '$revision')";

   dbquery($createProject);

   $newProjectId = mysql_insert_id();

	$_SESSION['projectID'] = $newProjectId;

	error_log("replanProjet($project_id) - New Project ID: $newProjectId");

   //Update the old project to associate to the new one
   $strUpdate = "UPDATE pev__project SET associated_id='$newProjectId', active='0' WHERE project_id='$project_id'";
   dbquery($strUpdate);

   //Copy users
   $getUsers = "SELECT person_id, is_admin FROM pev__project_access WHERE project_id='$project_id'";
   $usersResult = dbquery($getUsers);

   while ($usersRow = mysql_fetch_array($usersResult)) {

      $person_id = $usersRow['person_id'];
      $is_admin = $usersRow['is_admin'];

      $setUsers = "INSERT INTO pev__project_access (project_id, person_id, is_admin)";
      $setUsers.= " VALUES('$newProjectId', '$person_id', '$is_admin')";

      dbquery($setUsers);

      $setUsers = "";
   }

   //Copy events that occur after the current date
   $getEvents = "SELECT is_doc, event_type, other, event_pdate, event_edate, event_adate FROM pev__event";
   $getEvents.= " WHERE project_id='$project_id'";
   $eventsResult = dbquery($getEvents);

	error_log("replanProject($projectId) - $getEvents");

   while ($eventsRow = mysql_fetch_array($eventsResult)) {

      $is_doc = $eventsRow['is_doc'];
      $event_type = $eventsRow['event_type'];
      $other = $eventsRow['other'];
      $event_pdate = $eventsRow['event_pdate'];
      $event_edate = $eventsRow['event_edate'];
      $event_adate = $eventsRow['event_adate'];

      $setEvents  = "INSERT INTO pev__event (project_id, is_doc, event_type, other, event_pdate, event_edate, event_adate)";
      $setEvents .= " VALUES('$newProjectId', '$is_doc', '$event_type', '$other', '$event_pdate', '$event_edate', '$event_adate')";

      dbquery($setEvents);
   }

   //Copy all WBSs and create a mapping between the two
   $wbsMap = array();
   $wbs_to_task_idMap = array();
 
   $getWBS = "SELECT wbs_id, wbs_name, wbs_order FROM pev__wbs WHERE project_id='$project_id'";

   $wbsResult = dbquery($getWBS);

   while ($wbsRow = mysql_fetch_array($wbsResult)) {

      $wbs_id = $wbsRow['wbs_id'];
      $wbs_name = $wbsRow['wbs_name'];
      $wbs_order = $wbsRow['wbs_order'];

      $setWbs = "INSERT INTO pev__wbs (wbs_name, project_id, wbs_order)";
      $setWbs.= " VALUES('$wbs_name', '$newProjectId', '$wbs_order')";

      dbquery($setWbs);

      $newWbs_id = mysql_insert_id();

      //Map the two WBS ids for later reference
      $wbsMap[$wbs_id] = $newWbs_id;
      $wbs_to_task_idMap[$wbs_id] = $newWbs_id;
   }
   
   $getActivity  = "SELECT wt.wbs_to_task_id, w.wbs_id, wt.parent_id, wt.wbs_number, wt.rollup, wt.due_date, wt.ec_date, wt.planned_hours, wt.actual_hours, wt.percent_complete, wt.task_id, person_id FROM pev__wbs_to_task AS wt";
   $getActivity .= " LEFT JOIN pev__wbs AS w ON w.wbs_id=wt.wbs_id";
   $getActivity .= " LEFT JOIN pev__person_to_wbstask AS pw ON pw.wbs_to_task_id=wt.wbs_to_task_id";
   $getActivity .= " WHERE wt.percent_complete < 100 AND project_id='$project_id' ORDER BY wt.wbs_number";

   $activityResult = dbquery($getActivity);

   while ($activityRow = mysql_fetch_array($activityResult)) {

      $oldWBS_to_task_id = $activityRow['wbs_to_task_id'];
      $wbs_id = $activityRow['wbs_id'];
      $parent_id = $activityRow['parent_id'];
      $parent_id = str_replace("w","",$parent_id);
      $wbs_number = $activityRow['wbs_number'];
      $rollup = $activityRow['rollup'];
      $due_date = $activityRow['due_date'];
      $ec_date = $activityRow['ec_date'];
      $planned_hours = $activityRow['planned_hours'];
      $actual_hours = $activityRow['actual_hours'];
      $percent_complete = $activityRow['percent_complete'];
      $task_id = $activityRow['task_id'];
      $person_id = $activityRow['person_id'];

      if ($actual_hours > 0 || $percent_complete < 100) {

         //Some work has been done on this task so we must subtract the earned value from the planned value and zero out everything else
         $earned_value = $planned_hours * ($percent_complete / 100);
         $planned_hours -= $earned_value;
         $actual_hours = 0;
         $percent_complete = 0;
      }

      //Correct for any missing data in the database
      if ($ec_date == NULL || $ec_date == 0) {

         $ec_date = $due_date;
      }

      if ($due_date == NULL || $due_date == 0) {

         $due_date = $ec_date;
      }

      //All dates that are due prior to today need to moved to today. This will prevent problems with graphing.
      if ($due_date < $today) {

         $due_date = date_save(date_read($today));
      }

      if ($ec_date < $today) {

         $ec_date = date_save(date_read($today));
      }

      $newWbs_id = $wbsMap[$wbs_id];
         
      //Maps parent_id to associated WBS/Rollup Task
      if ($wbs_to_task_idMap[$parent_id]) {

      	$parent_id = $wbs_to_task_idMap[$parent_id];

      	if ($newWbs_id == $parent_id) {

      		$parent_id = "w".$parent_id;
      	}
      }

      $getTasks = "SELECT task_id, task_name, scope_growth FROM pev__task WHERE task_id='$task_id'";

   	$tasksResult = dbquery($getTasks);

   	while ($tasksRow = mysql_fetch_array($tasksResult)) {

      	$task_name = $tasksRow['task_name'];
      	$scope_growth = $tasksRow['scope_growth'];

      	//Modify scope growth so users know where tasks came from
      	$setTasks = "INSERT INTO pev__task (project_id, task_name, scope_growth)";
      	$setTasks.= " VALUES('$newProjectId', '$task_name', '$scope_growth')";

      	dbquery($setTasks);

      	$newTask_id = mysql_insert_id();
   	}
         
      $setActivity = "INSERT INTO pev__wbs_to_task (wbs_id, parent_id, wbs_number, rollup, due_date, ec_date, planned_hours, actual_hours, percent_complete, task_id)";
      $setActivity.= " VALUES('$newWbs_id', '$parent_id', '$wbs_number', '$rollup', '$due_date', '$ec_date', '$planned_hours', '$actual_hours', '$percent_complete', '$newTask_id')";

      dbquery($setActivity);

      $newWBS_to_task_id = mysql_insert_id();
          
      if ($rollup == "1") {

      	$wbs_to_task_idMap[$oldWBS_to_task_id] = $newWBS_to_task_id;

         $getChildren = "SELECT wbs_number FROM pev__wbs_to_task WHERE percent_complete < 100 AND parent_id = '$oldWBS_to_task_id'";

         $result = dbquery($getChildren);

         //Updates Rollup Tasks if needed
         if (mysql_num_rows($result) == 0) {

        		$updateRollup = "UPDATE pev__wbs_to_task SET rollup = '0' WHERE wbs_to_task_id = '$newWBS_to_task_id'";

         	dbquery($updateRollup);
         }
      }

      $wbs_to_task_id = mysql_insert_id();

      $setPersonTasks = "INSERT INTO pev__person_to_wbstask (person_id, wbs_to_task_id)";
      $setPersonTasks.= " VALUES('$person_id', '$wbs_to_task_id')";

      dbquery($setPersonTasks);
   }

   echo $newProjectId;
	
	return $newProjectId;
}

function recordHistory($project_id){

   debug(10,"Recording the history for this project($project_id)");

	$_hTotalPlanHours = 0;
	$_hTotalActualHours = 0;
	$_hTotalPC = 0;
	
	// these are used as temp vars while looping over WBS for this project
	$_hWBSTotalHours = Array();
	$_hWBSPlanHours = Array();
	$_hWBSActualHours = Array();
	$_hWBSPC = Array(); /* this is not a %, but an earned hours of the planed based on the percent */
	
	/* find all WBS for this project */
	$strSQL0 = "SELECT wbs_name,wbs_id FROM pev__wbs";
	$strSQL0 .= " WHERE project_id='$project_id'";
	
	$result0 = dbquery($strSQL0);
	
	$_hWBS=Array();

	while($row0 = mysql_fetch_array($result0)) {

		$_hWBS[$row0['wbs_id']] = $row0['wbs_name'];

		$_hWBSPlanHours[$row0['wbs_id']] = 0;

		$_hWBSActualHours[$row0['wbs_id']] = 0;

		$_hWBSPC[$row0['wbs_id']] = 0;

		$_hWBSTotalHours[$row0['wbs_id']] = 0;

	}

	// go through all tasks of this project and total up the data
	$strSQL0 = "SELECT W.wbs_id,W.planned_hours,W.actual_hours,W.percent_complete, W.due_date FROM pev__task AS T";
	$strSQL0.= " LEFT JOIN pev__wbs_to_task AS W ON T.task_id=W.task_id";
	$strSQL0.= " WHERE T.project_id='$project_id' AND W.rollup = '0'";

	$result0 = dbquery($strSQL0);

  while($row0 = mysql_fetch_array($result0)) {

		$_hid  =$row0['wbs_id'];
		
		if ($_hid) {
		
			// this is the total planned hours - for the end of the effort 
			if ($row0['due_date'] < date("U",mktime(23,59,59,date("n"),date("j"),date("Y")))) {

				// if the date has passed, then add in the hours
				$_hWBSPlanHours[$row0['wbs_id']] += $row0['planned_hours'];
			}

			$_hWBSTotalHours[$row0['wbs_id']] += $row0['planned_hours']; // total planned hours regardless of date due 
			$_hWBSActualHours[$row0['wbs_id']] += $row0['actual_hours'];
			$_hWBSPC[$row0['wbs_id']] += round(($row0['planned_hours'] * $row0['percent_complete'] / 100),1);
		}
  }

  /* loop through each WBS (to get total, and to save each WBS */
  $_hdate = date("U"); /* this is now in seconds since the EPOC */
  $day = date("j", $_hdate);
  $month = date("n", $_hdate);
  $year = date("Y", $_hdate);
  $_hdate = mktime(0,0,0,$month,$day,$year); /*The date is now stripped of hours,minutes,seconds*/

  foreach ($_hWBS as $key => $val) {

		$strCleanDate = "DELETE FROM pev__wbs_history";
		$strCleanDate.= " WHERE wh_date='$_hdate' AND wbs_id=$key";
		dbquery($strCleanDate);

  		$_hTotalTotalHours += $_hWBSTotalHours[$key];
    	$_hTotalPlanHours += $_hWBSPlanHours[$key];
    	$_hTotalActualHours += $_hWBSActualHours[$key];
    	$_hTotalPC += $_hWBSPC[$key];

    	// compute this WBS's real % complete
    	$_htemp1 = round(($_hWBSPC[$key] / $_hWBSTotalHours[$key]) * 100, 1);

    	debug(10,"Going to save WBS($val/$key) info WPH(".$_hWBSPlanHours[$key]."), WAH(".$_hWBSActualHours[$key]."), WPC($_htemp1)");

    	$strSQL3  = "INSERT INTO pev__wbs_history SET";
    	$strSQL3 .= " wbs_id='$key'";
    	$strSQL3 .= ",wh_date='$_hdate'";
    	$strSQL3 .= ",total_phours='$_hWBSPlanHours[$key]'";
    	$strSQL3 .= ",total_ahours='$_hWBSActualHours[$key]'";
    	$strSQL3 .= ",percent_complete='$_htemp1'";
		error_log("recordHistory($project_id) - $strSQL3");
    	$result3 = dbquery($strSQL3);

    	$strSQL4 = "SELECT last_insert_id()";

    	$result4 = dbquery($strSQL4);

    	$row4 = mysql_fetch_array($result4);

    	$_htemp3=$row4[0];

    	set_status("Success: WBS-HISTORY ($_htemp3) data inserted.");

		error_log("recordHistory() - $strSQL3");
  }

  $_htemp2=round(($_hTotalPC/$_hTotalTotalHours)*100,1);
}

//function recalculate($parentRowId)
//		{
//		echo "<script>alert('CALCULATING');</script>";
//		echo "Recalculating! \n=================\n";
//		$wbsTrigger;
//		$strProject = $_SESSION['projectID'];
//		$due_date = "";
//		$ec_date = "";
//		$SUM_planned_hrs = 0;
//		$SUM_actual_hrs = 0;
//		$SUM_percent = 0;
//		echo "ParentRowId / ParentWBS = $parentRowId";
//		$parentRow = checkIfRowExists($parentRowId);
//		echo " / ".$parentRow['wbs_number']." \n";
//		
//		if($parentRow)
//			{
//			echo "Parent Task Exists!\n";
//			
//			$strSQL = "";
//			$strSQL.= "SELECT WT.wbs_to_task_id, WT.wbs_number, WT.due_date, WT.ec_date, WT.planned_hours, WT.actual_hours, WT.percent_complete, W.wbs_id, WT.parent_id ";
//			$strSQL.= "FROM pev__wbs_to_task AS WT ";
//			$strSQL.= "LEFT JOIN pev__wbs AS W ON W.wbs_id = WT.wbs_id ";
//			$strSQL.= "WHERE WT.wbs_number LIKE '".$parentRow['wbs_number']."%' AND LENGTH(WT.wbs_number) <= '".(strlen($parentRow['wbs_number'])+4)."' AND W.project_id = '$strProject' ";
//			$strSQL.= "ORDER BY WT.wbs_number DESC";
//			$result = dbquery($strSQL);
//			
//			if(mysql_num_rows($result) == 1)
//				{
//				echo "\nNOTHING TO ADD\n\n";
//				$row = mysql_fetch_array($result);
//				recalculate($row['parent_id']);
//				}
//			else 
//				{
//				while($row = mysql_fetch_array($result))
//					{
//					$wbsTrigger = true;
//					if($row['wbs_number'] == $parentRow['wbs_number'])
//						{
//						$wbsTrigger = false;
//						echo ">>> END <<<\n\n";
//						echo "round((SUM_Percent/SUM_Planned_Hrs)*100,1) = ";
//						$SUM_percent = round(($SUM_percent/$SUM_planned_hrs)*100,1);
//						echo "$SUM_percent\n";
//						$strSQL2 = "";
//						$strSQL2.= "UPDATE pev__wbs_to_task ";
//						$strSQL2.= "SET due_date = '$due_date', ec_date = '$ec_date', planned_hours = $SUM_planned_hrs, actual_hours = $SUM_actual_hrs, percent_complete = $SUM_percent ";
//						$strSQL2.= "WHERE wbs_to_task_id = ".$row['wbs_to_task_id']." and wbs_number = '".$parentRow['wbs_number']."'";
//						$result = dbquery($strSQL2);
//						
//						recalculate($row['parent_id']);
//						break;
//						}
//					$SUM_planned_hrs = ($SUM_planned_hrs+$row['planned_hours']);
//					$SUM_actual_hrs = ($SUM_actual_hrs+$row['actual_hours']);
//					$SUM_percent+= round(($row['planned_hours']*$row['percent_complete']/100),1);
//					echo "SUM_Planned_Hours = $SUM_planned_hrs \nSUM_Actual_Hours = $SUM_actual_hrs \nSUM_Percent = $SUM_percent \n";
//					echo "Due_Date = ".date_read($due_date)." \n";
//					echo "EC_Date = ".date_read($ec_date)." \n";
//					
//					if($due_date < $row['due_date'])
//						{
//						$due_date = $row['due_date'];
//						echo "**DUE_DATE UPDATED**\n";
//						}
//					if($ec_date < $row['ec_date'])
//						{
//						$ec_date = $row['ec_date'];
//						echo "**EC_DATE UPDATED**\n";
//						}
//					echo "Due_Date = ".date_read($due_date)." \n";
//					echo "EC_Date = ".date_read($ec_date)." \n";
//					}
//				}
//			return true;
//			}
//		else
//			{
//			echo "PARENT = WBS \n\n";
//			$parentRow = checkIfWBSExists($parentRowId);
//			echo "ParentRow = $parentRow";
//			if($parentRow)
//				{
//				$strSQL = "";
//				$strSQL.= "SELECT WT.wbs_to_task_id, WT.wbs_number, WT.due_date, WT.ec_date, WT.planned_hours, WT.actual_hours, WT.percent_complete, W.wbs_id, WT.parent_id ";
//				$strSQL.= "FROM pev__wbs_to_task AS WT LEFT JOIN pev__wbs AS W ON W.wbs_id = WT.wbs_id ";
//				$strSQL.= "WHERE WT.wbs_number like '".$parentRow['wbs_order']."%' AND LENGTH(WT.wbs_number) <= 7 AND W.project_id = '$strProject' ";
//				$strSQL.= "ORDER BY WT.wbs_number DESC";
//				$result = dbquery($strSQL);
//				
//				while($row = mysql_fetch_array($result))
//					{
//					echo "\n\n...\n\n";
//					$SUM_planned_hrs = ($SUM_planned_hrs+$row['planned_hours']);
//					$SUM_actual_hrs = ($SUM_actual_hrs+$row['actual_hours']);
//					$SUM_percent+= round(($row['planned_hours']*$row['percent_complete']/100),1);
//					echo "SUM_Planned_Hours = $SUM_planned_hrs \nSUM_Actual_Hours = $SUM_actual_hrs \nSUM_Percent = $SUM_percent \n";
//					echo "Due_Date = ".date_read($due_date)." \n";
//					echo "EC_Date = ".date_read($ec_date)." \n";
//					
//					if($due_date < $row['due_date'])
//						{
//						$due_date = $row['due_date'];
//						echo "**DUE_DATE UPDATED**\n";
//						}
//					if($ec_date < $row['ec_date'])
//						{
//						$ec_date = $row['ec_date'];
//						echo "**EC_DATE UPDATED**\n";
//						}
//					echo "Due_Date = ".date_read($due_date)." \n";
//					echo "EC_Date = ".date_read($ec_date)." \n";
//					}
//				$SUM_percent = round(($SUM_percent/$SUM_planned_hrs)*100,1);
//				$strSQL2 = "";
//				$strSQL2.= "UPDATE pev__wbs ";
//				$strSQL2.= "SET due_date = '$due_date', ec_date = '$ec_date', planned_hours = $SUM_planned_hrs, actual_hours = $SUM_actual_hrs, percent_complete = '$SUM_percent' ";
//				$strSQL2.= "WHERE wbs_id = ".$parentRow['wbs_id']." and project_id = $strProject";
//				$result2 = dbquery($strSQL2);
//				echo "\n==========================\n";
//				echo "TOTAL_SUM_Planned_Hours = $SUM_planned_hrs\nTOTAL_SUM_Actual_Hours = $SUM_actual_hrs\nTOTAL_SUM_Percent = $SUM_percent\n";
//				echo "FINAL_Due_Date = ".date_read($due_date)." \n";
//				echo "FINAL_EC_Date = ".date_read($ec_date)." \n";
//				echo "==========================\n";
//				}
//			return false;
//			}
//		}

function noGraph($small){
   require_once($CHARTDIRECTOR);
   if($small)
   {
     $chartH=300;
     $chartW=350;
     $plotH=195;
     $plotW=275;
     $offsetW=50;
     $offsetH=40;
   }
   else
   {
     $chartH=600;
     $chartW=850;
     $plotH=450;
     $plotW=700;
     $offsetW=100;
     $offsetH=55;
   }


   # Create an XYChart object of size 600 x 300 pixels, with a light blue (EEEEFF)
   # background, black border, 1 pxiel 3D border effect and rounded corners
   $c = new XYChart($chartW, $chartH, 0xeeeeff, 0x000000, 1);
   $c->setRoundedFrame();

   # Set the plotarea at (55, 58) and of size 520 x 195 pixels, with white background.
   # Turn on both horizontal and vertical grid lines with light grey color (0xcccccc)
   //$c->setPlotArea($offsetW, $offsetH, $plotW, $plotH, 0xffffff, -1, -1, 0xcccccc, 0xcccccc);

   # Add a title box to the chart using 15 pts Times Bold Italic font, on a light blue
   # (CCCCFF) background with glass effect. white (0xffffff) on a dark red (0x800000)
   # background, with a 1 pixel 3D border.
   $textBoxObj = $c->addTitle("This graph requires a locked project", "timesbi.ttf", 12);
   $textBoxObj->setBackground(0xccccff, 0x000000, glassEffect());

   # output the chart
   header("Content-type: image/png");
   print($c->makeChart2(PNG));

   return;
}

?>
