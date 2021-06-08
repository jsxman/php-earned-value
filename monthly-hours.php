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

/**************************************************************************
* Description: This page pulls data from the paev_tool database and       *
*       displays a table that provides a monthly total of hours worked    *
*       (actual and planned) on the project per person assigned to the    *
*       project.                                                          *
*                                                                         *
* Assumptions: Start dates are calculated based on an 8 hour day, 5 day   *
*       work week. Any work holidays are ignored. Future versions of this *
*       tool may include the ablility to account for holidays.            *
*                                                                         *
* Limitations: It should be understood that the start date for a completed*
*       task (ie. a task with actual hours worked and a completion date)  *
*       may not reflect reality; someone may work extra time to complete a*
*       task early or on time, it is used as an estimate. Future versions *
*       of this tool may provide a solution to this discrepancy.          *
**************************************************************************/

/******************************************/
/* HEADER: INCLUDE/SECURITY CHECK - START */
/******************************************/
$HACK_CHECK=1; Include("config/global.inc.php");
debug(10,"Loading File: monthly-hours.php");
debug(10,"User Level is DB-Admin (".$_SESSION['dbAdmin'].")");
checkPermissions($SESSION_TIMEOUT); // if not logged in, or session has timed out...
/******************************************/
/* HEADER: INCLUDE/SECURITY CHECK - END   */
/******************************************/

/********************************************
*	        PERMISSIONS CHECK	    *
********************************************/
$strProject =isset($_FORM['txt_project'])?postedData($_FORM['txt_project']):0;
$strProject = getProject($strProject);

$userId = $_SESSION['paev_userID'];
$projectAdmin = isProjectAdmin($strProject, $userId);
$dbAdmin = $_SESSION['dbAdmin'];
/********************************************
*	    END PERMISSIONS CHECK	    *
********************************************/


if($strProject)
{
	$strSQL12 = "SELECT project_name FROM $TABLE_PROJECT WHERE project_id = '$strProject'";
	$result12 = dbquery($strSQL12);
	$row12 = mysql_fetch_array($result12);
	$projectName = $row12['project_name'];
	$TITLE="$projectName: Project Hours By Month";
}else
{
	$TITLE="Project Hours By Month";
}
show_header();
show_menu("REPORTS");
echo '<div style="position:relative; top:-150px">';
show_status();
show_error();
?>
<link rel="stylesheet" href="style.css" type="text/css">
<?


// compute the start date given then end date in "U" format (seconds since the EPOC
// assume 8 hour days, and weekend dates dont' count
function getStart2($end_date, $task_hours)
{
	$endDay = date("w", $end_date);
	if($endDay==0) $end_date+=60*60*24;
	if($endDay==6) $end_date+=60*60*24*2;
	
	
	$startDate = $end_date;
	while($task_hours > 0)
	{
		$task_hours -= 8;
		$startDate -= 60*60*24;
		$startDay = date("w", $startDate);
		if($startDay==0) {$startDate-=60*60*24*2;}
  		if($startDay==6) {$startDate-=60*60*24;}
	}
	return $startDate;
}




if($strProject && ($projectAdmin || $dbAdmin))
{
//Get project start and end dates
$projStrtTime = mktime(0,0,0,12,31,2030);	//Set Project Start time to date in the future
$projEndTime = mktime(0,0,0,1,1,1990);		//Set Project End time to a date in the past

//Start off calculating the project start and end dates with the planned hours for all project task
$strSQL0 = "SELECT planned_hours, due_date";
$strSQL0.= " FROM $TABLE_TASK, $TABLE_WBS_TO_TASK";
$strSQL0.= " WHERE $TABLE_TASK.task_id = $TABLE_WBS_TO_TASK.task_id";
$strSQL0.= " AND project_id ='$strProject'";
$result0 = dbquery($strSQL0);

while($row0 = mysql_fetch_array($result0))
{
	$hours = $row0['planned_hours'];
	$duedate = $row0['due_date'];

	if($duedate > $projEndTime)
	{					//If a task due date is after the current project end date
		$projEndTime = $duedate;	//Set as the new project end date
	}	


	if($duedate > 0 && $hours > 0)
	{					//Calculate the start date for the task
		$startdate = getStart2($duedate, $hours);
	}

	if($startdate < $projStrtTime)
	{					//If a task start date is before the current project start date
		$projStrtTime = $startdate;	//Set as the new project start date
	}		
}

//Check the current project start and end dates against the actual hours for all tasks
$strSQL11 = "SELECT actual_hours, ec_date";
$strSQL11.= " FROM $TABLE_TASK, $TABLE_WBS_TO_TASK";
$strSQL11.= " WHERE $TABLE_TASK.task_id = $TABLE_WBS_TO_TASK.task_id";
$strSQL11.= " AND project_id ='$strProject'";
$result11 = dbquery($strSQL11);

while($row11 = mysql_fetch_array($result11))
{
	$hours = $row11['actual_hours'];
	$duedate = $row11['ec_date'];

	if($duedate > $projEndTime)
	{					//If a task complete date is after the current project end date
		$projEndTime = $duedate;	//Set as the new project end date
	}	


	if($duedate > 0 && $hours > 0)
	{					//Calculate the start date for the task
		$startdate = getStart2($duedate, $hours);
	}

	if($startdate < $projStrtTime){		//If a task start date is before the current project start date
		$projStrtTime = $startdate;	//Set as the new project start date
	}		
}
$projStrtMonth = date("n", $projStrtTime);
$projStrtYear = date("Y", $projStrtTime);
$projEndMonth = date("n", $projEndTime);
$projEndYear = date("Y", $projEndTime);
//Project Start and End Dates have been set



//Display graphs of the information contained in the table being constructed

//Display fieldset if a project has been selected and begin setting up the table
?>
<br /><br />
<table align=center>
	<tr>
		<td>
			<a href=<?=$PAGE_SHOW;?>?txt_graph=graph-month.php&txt_project=<?=$strProject;?>&txt_small=0&txt_planned=1>
			<img border=0 src=graph-month.php?txt_project=<?=$strProject;?>&txt_small=1&txt_planned=1>
			</a>
		</td>
		<td>
			<a href=<?=$PAGE_SHOW;?>?txt_graph=graph-month.php&txt_project=<?=$strProject;?>&txt_small=0&txt_planned=0>
			<img border=0 src=graph-month.php?txt_project=<?=$strProject;?>&txt_small=1&txt_planned=0>
			</a>
		<td>
	</tr>
</table>

<fieldset><legend>Total Project Hours by Month - Project: <?=$projectName;?></legend>

<p align="center"><font size="2">Total Hours By Month For Project: <?=$projectName;?></font></p>
<table class="list" id="personHours" align="center">
	<thead id="personHoursHead">	
		<tr>
			<th rowspan="2">Name</th>

<?
$month = $projStrtMonth;	//$month will be used to count through the months
$year = $projStrtYear;		//$year will be used to count through the years
$count = 0;
//This will set up the table headers by adding a month/year column for each month within a project
while($tableTime < $projEndTime)
{
	
	$monthName = date("M", mktime(0,0,0,$month,1,$year)); 
	echo "\t\t\t<th colspan=2>$monthName $year</th>\n";
	
	$month++;
	if($month == 13)
	{
		$month = 1;
		$year++;
	}

	$tableTime = mktime(0,0,0,$month,0,$year);
	$count++;
}
echo "\t\t\t<th colspan=2>Total Hours per Person</th>\n";
echo "\t\t</tr>\n";
echo "\t\t<tr>\n";
//Add a Planned and Actual subheader for hours
while($count >= 0)
{
	echo "\t\t\t<th>Planned</th>\n\t\t\t<th>Actual</th>\n";
	$count--;
}
echo "\t\t</tr>\n";
echo "\t</thead>\n";				//End Table Header
echo "\t<tbody id=\"personHoursBody\">\n";

// Pull everyone who is associated/assigned to a project
$strSQL2 = "SELECT P.person_id, P.first, P.last";
$strSQL2.= " FROM $TABLE_PERSON as P";
$strSQL2.= " LEFT JOIN $TABLE_PROJECT_ACCESS as A on A.person_id=P.person_id";
$strSQL2.= " WHERE A.project_id='$strProject'";
$strSQL2.= " ORDER by P.last ASC";
$result2 = dbquery($strSQL2);

$row = 0;
$sumHrsByMonth = array();
// For each person in the project, we will pull all of their data for that project
while($row2 = mysql_fetch_array($result2))
{
	$person_id = $row2['person_id'];
	$person_name = $row2['last'].", ".$row2['first'];
	//Get person's hours on the project by month
	$strSQL1 = "SELECT task_name, planned_hours, due_date";
	$strSQL1.= " FROM $TABLE_TASK, $TABLE_WBS_TO_TASK, $TABLE_PERSON_TO_WBSTASK";
	$strSQL1.= " WHERE $TABLE_TASK.task_id = $TABLE_WBS_TO_TASK.task_id";
	$strSQL1.= " AND $TABLE_PERSON_TO_WBSTASK.wbs_to_task_id = $TABLE_WBS_TO_TASK.wbs_to_task_id";
	$strSQL1.= " AND person_id = '$person_id'";
	$strSQL1.= " AND project_id = '$strProject'";
	$strSQL1.= " ORDER BY due_date ASC";
	$result1 = dbquery($strSQL1);

	$ddate_epoc = array();
	$sdate_epoc = array();
	$phours = array();
	$ddate = array();
	$sdate = array();
	$hrsbymonth = array();
	$count = 0;

	// This sets up the start and due date for each task associated with each person
	while($row1 = mysql_fetch_array($result1))
	{
		$tname = $row1['task_name'];
		$phours[$count] = $row1['planned_hours'];
		$ddate_epoc[$count] = $row1['due_date'];
	
		//If the due date is not present, the task will be skipped
		if($ddate_epoc[$count] > 0)
		{
			//Calculate start date
			$sdate_epoc[$count] = getStart2($ddate_epoc[$count], $phours[$count]);
			$ddate[$count] = date_read($ddate_epoc[$count]);
			$sdate[$count] = date_read($sdate_epoc[$count]);
	
			$count ++;
		}
	}

	//This is the meat and potatoes of the code. This adds up all of the hours a person is planned to work
	//during a month. That amount is then inserted in to the array according to the year and month 
	for($i = 0; $i < $count; $i++)
	{
	
		$startMonth = date("n", $sdate_epoc[$i]);
		$startYear = date("Y", $sdate_epoc[$i]);
		$dueMonth = date("n", $ddate_epoc[$i]);
		$dueYear = date("Y", $ddate_epoc[$i]);
		//if start date and due date are equal, add task hours to corresponding array
		if($startMonth == $dueMonth){
			$hrsbymonth[$startYear][$startMonth] += $phours[$i];
		}

		//if start month and due month are not equal, find the number of hours left in the first month
		//and add that to the corresponding $hrsbymonth array. The difference between the start month
		//and end month determines how many times this process is repeated.
		if($startMonth != $dueMonth){
			$totalHours = 0;
			$hours = 0;
			$checkDate = $sdate_epoc[$i];
			$checkMonth = $startMonth;
			$thisMonth = $startMonth;
			$thisYear = $startYear;
			while($totalHours < $phours[$i]){
				while($checkMonth == $thisMonth && $totalHours < $phours[$i]){
					$hours += 8;
					$totalHours += 8;
					if($totalHours > $phours[$i]){
						$hours -= 8;
						$totalHours -= 8;
						$hours += ($phours[$i] - $totalHours);
						$totalHours += 8;
					}
					$checkDate += 60*60*24;
					$dayofWeek = date("w", $checkDate);
					if($dayofWeek==0) {$checkDate+=60*60*24;}
  					if($dayofWeek==6) {$checkDate+=60*60*24*2;}
					$checkMonth = date("n", $checkDate);
				}
				$hrsbymonth[$thisYear][$thisMonth] += $hours;
				//$mytotal = $hrsbymonth[$thisYear][$thisMonth];
				//echo "Hours[$thisYear][$thisMonth]: $mytotal<br>";  //Test Output
				$thisYear = date("Y", $checkDate);
				$thisMonth = date("n", $checkDate);
				$hours = 0;	
			}
		}
	
	}

	//This next section is to be used for the actual hours data
	//Get person's actual hours for the project
	$strSQL10 = "SELECT task_name, actual_hours, ec_date";
	$strSQL10.= " FROM $TABLE_TASK, $TABLE_WBS_TO_TASK, $TABLE_PERSON_TO_WBSTASK";
	$strSQL10.= " WHERE $TABLE_TASK.task_id = $TABLE_WBS_TO_TASK.task_id";
	$strSQL10.= " AND $TABLE_PERSON_TO_WBSTASK.wbs_to_task_id = $TABLE_WBS_TO_TASK.wbs_to_task_id";
	$strSQL10.= " AND person_id = '$person_id'";
	$strSQL10.= " AND project_id = '$strProject'";
	$strSQL10.= " ORDER BY ec_date ASC";
	$result10 = dbquery($strSQL10);

	$ecdate_epoc = array();
	$actualsdate_epoc = array();
	$actualhours = array();
	$ecdate = array();
	$actualsdate = array();
	$actualhrsbymonth = array();
	$count = 0;
	while($row10 = mysql_fetch_array($result10)){
		$tname = $row10['task_name'];
		$actualhours[$count] = $row10['actual_hours'];
		$ecdate_epoc[$count] = $row10['ec_date'];
	
		if($ecdate_epoc[$count] > 0){
			//Calculate start date
			$actualsdate_epoc[$count] = getStart2($ecdate_epoc[$count], $actualhours[$count]);
	
			$ecdate[$count] = date_read($ecdate_epoc[$count]);
			$actualsdate[$count] = date_read($actualsdate_epoc[$count]);
	
			$count ++;
		}
	}

	for($i = 0; $i < $count; $i++){
	
		$startMonth = date("n", $actualsdate_epoc[$i]);
		$startYear = date("Y", $actualsdate_epoc[$i]);
		$dueMonth = date("n", $ecdate_epoc[$i]);
		$dueYear = date("Y", $ecdate_epoc[$i]);
		//if start date and due date are equal, add task hours to corresponding array
		if($startMonth == $dueMonth){
			$actualhrsbymonth[$startYear][$startMonth] += $actualhours[$i];
		}

		//if start month and due month are not equal, find the number of hours left in the first month
		//and add that to the corresponding $hrsbymonth array. The difference between the start month
		//and end month determines how many times this process is repeated.
		if($startMonth != $dueMonth){
			$totalHours = 0;
			$hours = 0;
			$checkDate = $actualsdate_epoc[$i];
			$checkMonth = $startMonth;
			$thisMonth = $startMonth;
			$thisYear = $startYear;
			while($totalHours < $actualhours[$i]){
				while($checkMonth == $thisMonth && $totalHours < $actualhours[$i]){
					$hours += 8;
					$totalHours += 8;
					if($totalHours > $actualhours[$i]){
						$hours -= 8;
						$totalHours -= 8;
						$hours += ($actualhours[$i] - $totalHours);
						$totalHours += 8;
					}
					$checkDate += 60*60*24;
					$dayofWeek = date("w", $checkDate);
					if($dayofWeek==0) {$checkDate+=60*60*24;}
  					if($dayofWeek==6) {$checkDate+=60*60*24*2;}
					$checkMonth = date("n", $checkDate);
				}
				$actualhrsbymonth[$thisYear][$thisMonth] += $hours;
				//$mytotal = $hrsbymonth[$thisYear][$thisMonth];
				//echo "Hours[$thisYear][$thisMonth]: $mytotal<br>";  //Test Output
				$thisYear = date("Y", $checkDate);
				$thisMonth = date("n", $checkDate);
				$hours = 0;	
			}
		}
	}


	echo "\t\t<tr>\n";
	echo "\t\t\t<td class=poc>$person_name</td>\n";
	$month = $projStrtMonth;
	$year = $projStrtYear;
	$tableTime = mktime(0,0,0,$month,0,$year);
	$total = 0;
	$actualtotal = 0;
	while($tableTime < $projEndTime)
	{
		if($hrsbymonth[$year][$month] > 170)
		{
			$class = "red_hours";
		}
		else
		{
			$class = "hours";
		}
		echo "\t\t\t<td class=$class>".$hrsbymonth[$year][$month]."</td>\n";
		echo "\t\t\t<td class=actual>".$actualhrsbymonth[$year][$month]."</td>\n";
		$sumHrsByMonth[$year][$month] += $hrsbymonth[$year][$month];
		$sumActHrsByMonth[$year][$month] += $actualhrsbymonth[$year][$month];
		$total += $hrsbymonth[$year][$month];
		$actualtotal += $actualhrsbymonth[$year][$month];
		$month++;
		if($month == 13)
		{
			$month = 1;
			$year++;
		}

		$tableTime = mktime(0,0,0,$month,0,$year);
	
	}
	echo "\t\t\t<td class=hours>$total</td>\n\t\t\t<td class=actual>$actualtotal</td>\n";
	echo "\t\t</tr>\n";
	$row++;

}//This is where the data for this person ends. The loop will go back up to line 224
 //to continue to construct the table if more people have to be calculated

//Set up the footer for the table and calculate the total hours for that month
echo "\t</tbody>\n";
echo "\t<tfoot id=\"personHoursFoot\">\n";
echo "\t\t<tr>\n";
echo "\t\t\t<td>Total Hours per Month</td>\n";
$month = $projStrtMonth;
$year = $projStrtYear;
$tableTime = mktime(0,0,0,$month,0,$year);
$total = 0;
$actualtotal = 0;
while($tableTime < $projEndTime){

	
	echo "\t\t\t<td class=hours>".$sumHrsByMonth[$year][$month]."</td>\n\t\t\t<td class=actual>".$sumActHrsByMonth[$year][$month]."</td>\n";
	$total += $sumHrsByMonth[$year][$month];
	$actualtotal += $sumActHrsByMonth[$year][$month];
	$month++;
	if($month == 13){
		$month = 1;
		$year++;
	}

	$tableTime = mktime(0,0,0,$month,0,$year);
	
}
echo "\t\t\t<td class=hours>$total</td>\n\t\t\t<td class=actual>$actualtotal</td>\n";
echo "\t\t</tr>\n";
echo "\t</tfoot>\n";
echo "</table>\n";						//End table id="personHours"

// Pull information for total planned and actual hours for all tasks and display missing hours
$strSQL3 = "SELECT SUM(planned_hours) as PTotal, SUM(actual_hours) as ATotal";
$strSQL3.= " FROM $TABLE_TASK, $TABLE_WBS_TO_TASK";
$strSQL3.= " WHERE $TABLE_TASK.task_id = $TABLE_WBS_TO_TASK.task_id";
$strSQL3.= " AND project_id='$strProject'";
$result3 = dbquery($strSQL3);

while($row3 = mysql_fetch_array($result3)){
	$plannedHours = $row3['PTotal'];
	$actualHours = $row3['ATotal'];
}
$missingHours = $plannedHours - $total;
$missingHours = $actualHours - $actualtotal;
// Now set up a small table that will display some basic project information 
// concerning the number of hours assigned and worked and if there are any missing hours
?>
<br><br>
<table align=center>
	<tr>
		<th colspan=3>Project Information</th>
	</tr>
	<tr>
		<th>Subject</th>
		<th>Detail</th>
		<th>Notice</th>
	</tr>

	<tr>
		<td class="info">Planned Hours</td>
		<td class="info"><?=$plannedHours;?> hours have been assigned to this project.</td>

<?
if($missingHours)
{
?>
		<td class="info"><font color="FF0000">There are <?=$missingHours;?> planned hours that are currently unassigned or do not have a due date.</font></td>
<?
}
else
{
?>
		<td></td>
<?
}
?>

	</tr>
	<tr>
		<td class="info">Actual Hours</td>
		<td class="info"><?=$actualHours;?> hours have been worked on this project.</td>

<?
if($missingHours)
{
?>
		<td class=info><font color="FF0000">There are <?=$missingHours;?> worked hours that are currently unassigned or do not have a completion date.</font></td>
<?
}
else
{
?>
		<td></td>
<?
}
?>
	</tr>
</table>

</fieldset>
<?
} //End diplay of fieldset if a project has been selected

if($strProject && !($projectAdmin || $dbAdmin))
{
show_permission_error(); //If user does not have permission
}

?>
<script>
var tblel;

if(document.getElementById("personHoursBody"))
{
  tblel = document.getElementById("personHoursBody");

  for(var i=0; i<tblel.rows.length; i++){
	if(i%2 == 0)
		tblel.rows[i].className = 'row0';
	if(i%2 == 1)
		tblel.rows[i].className = 'row1';
  }
}

</script>
<?

projectSelector($PAGE_HOURS, $strProject);
show_footer();
?>
</div>