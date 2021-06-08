<?php
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

$HACK_CHECK=1; include("config/global.inc.php");
debug(10,"Loading File: graph-month.php");
checkPermissions($SESSION_TIMEOUT); // if not logged in, or session has timed out...

$strPlanned=postedData($_FORM['txt_planned']);
$strProject=postedData($_FORM['txt_project']);   //Provides the Project ID
$strSmall=postedData($_FORM['txt_small']);       //Provides the option to display a small chart (1=yes)
$printed = false;
require_once($CHARTDIRECTOR);


/*This function will be used to calculate start dates. It accounts for weekends and assumes
an 8 hour work day for 5 days a week.*/
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


//This will determine if planned hours or actual hours data will be displayed
if($strPlanned)
{
	$taskHours = "planned_hours";
	$taskDate = "due_date";
	$title = "Planned";
        $planned = 1;
}
else
{
	$taskHours = "actual_hours";
	$taskDate = "ec_date";
	$title = "Actual";
        $planned = 0;
}



//Get Project Name from project ID
$strSQL10 = "SELECT project_name FROM $TABLE_PROJECT WHERE project_id='$strProject'";
$result10 = dbquery($strSQL10);
$row10 = mysql_fetch_array($result10);
$projName = $row10['project_name'];




//Get project start and end dates
$projStrtTime = mktime(0,0,0,12,31,2030);	//Set Project Start time to date in the future
$projEndTime = mktime(0,0,0,1,1,1990);		//Set Project End time to a date in the past

//Start off calculating the project start and end dates with the planned hours for all project tasks
$strSQL0 = "SELECT planned_hours, due_date";   //
$strSQL0.= " FROM $TABLE_TASK LEFT JOIN $TABLE_WBS_TO_TASK ON $TABLE_TASK.task_id = $TABLE_WBS_TO_TASK.task_id";
$strSQL0.= " WHERE project_id ='$strProject' AND due_date > 0 AND $TABLE_WBS_TO_TASK.rollup!=1";
$result0 = dbquery($strSQL0);

$startdate = 0;
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

	if($startdate < $projStrtTime && $startdate != 0)
	{					//If a task start date is before the current project start date
		$projStrtTime = $startdate;	//Set as the new project start date
	}	
}

/*Next, check project start and end dates against acutal hours for all tasks to ensure unplanned hours are
accounted for*/
$strSQL3 = "SELECT actual_hours, ec_date";   //
$strSQL3.= " FROM $TABLE_TASK LEFT JOIN $TABLE_WBS_TO_TASK ON $TABLE_TASK.task_id = $TABLE_WBS_TO_TASK.task_id";
$strSQL3.= " WHERE project_id ='$strProject' AND ec_date > 0 AND $TABLE_WBS_TO_TASK.rollup!=1";
$result3 = dbquery($strSQL3);

while($row3 = mysql_fetch_array($result3))
{
	$hours = $row3['actual_hours'];   //
	$duedate = $row3['ec_date'];  //

	if($duedate > $projEndTime)
	{					//If a task due date is after the current project end date
		$projEndTime = $duedate;	//Set as the new project end date
	}	


	if($duedate > 0 && $hours > 0)
	{					//Calculate the start date for the task
		$startdate = getStart2($duedate, $hours);
	}

	if($startdate < $projStrtTime && $startdate != 0)
	{					//If a task start date is before the current project start date
		$projStrtTime = $startdate;	//Set as the new project start date
	}
		
}

$projStrtMonth = date("n", $projStrtTime);
$projStrtYear = date("Y", $projStrtTime);
$projEndMonth = date("n", $projEndTime);
$projEndYear = date("Y", $projEndTime);
//Project Start and End Dates have been set

//This section will set the x-axis labels (Month/Year) for the chart
//It will start with the starting project time and add month/year to the label array
$checktime = mktime(0,0,0,$projStrtMonth,1,$projStrtYear);
$labelcount = 0;
$labels = array();

while($checktime < $projEndTime)
{

  	$monthName = date("M", $checktime);
 	$month = date("n", $checktime);
 	$year = date("Y", $checktime);
  	$labels[$labelcount] = "$monthName\n$year";
  	$labelcount++;
  	$month++;
  	if($month > 12)
	{
		$year++;
		$month = 1;
  	}
  	$checktime = mktime(0,0,0,$month,1,$year);
}

$manyLabels = 0;	//Used to set label display
if($labelcount > 20)
{
  	$manyLabels = 1;
}
$fewLabels = 0;		//Used to set label display
if($labelcount < 8)
{
  	$fewLabels = 1;
}
//Labels array has been set

//BEGIN CHART SETUP
//Set chart size variables depending if a small chart has been selected
if($strSmall)
{
  	$chartH=300;
  	$chartW=350;
  	$plotH=175;
  	$plotW=250;
}
else
{
	$chartH=600;
	$chartW=650;
	$plotH=475;
	$plotW=550;
}


// Create a XYChart object of size 500 x 320 pixels
$c = new XYChart($chartW, $chartH, 0xf5deb3, 0x000000, 1);

// Set the plotarea at (100, 40) and of size 280 x 240 pixels
$c->setPlotArea(50, 70, $plotW, $plotH, 0xffffff, -1, -1, 0xcccccc, 0xcccccc);

if($strSmall){
	/* Add a legend box at (50, 30) (top of the chart) with horizontal layout. Use 9 pts
	Arial Bold font. Set the background and border color to Transparent.*/
	$legendObj = $c->addLegend(20, 25, false, "arialbd.ttf", 7);
	$legendObj->setBackground(Transparent);
}else{
	/* Add a legend box at (50, 30) (top of the chart) with horizontal layout. Use 9 pts
	 Arial Bold font. Set the background and border color to Transparent.*/
	$legendObj = $c->addLegend(20, 25, false, "arialbd.ttf", 9);
	$legendObj->setBackground(Transparent);
}

/* Add a title box to the chart using 15 pts Times Bold Italic font, on a light blue
 (CCCCFF) background with glass effect. white (0xffffff) on a dark red (0x800000)
 background, with a 1 pixel 3D border.*/

$textBoxObj = $c->addTitle("", "timesbi.ttf", 12);
unset($x);
$textBoxObj->setBackground(0xd2b48c, 0x000000, glassEffect());

// Add a title to the y axis. Draw the title upright (font angle = 0)
$textBoxObj = $c->yAxis->setTitle("Hours Per Person");


// Set the labels on the x axis
$c->xAxis->setLabels($labels);

if($strSmall && !$fewLabels){
	//if the small graph is being displayed, condense the x-axis labels to prevent
	//crowding of the values
	$c->xAxis->setLabelStep(3);
}

if(!$strSmall && $manyLabels){
	//if the small graph is being displayed, condense the x-axis labels to prevent
	//crowding of the values
	$c->xAxis->setLabelStep(2);
}

// Add a stacked bar layer and set the layer 3D depth to 8 pixels
$layer = $c->addBarLayer2(Stack);

//CHART SETUP IS COMPLETE.
//Now the actual data will be plotted on the graph. 

//Pull a list of all the people with access on a project
$strSQL2 = "SELECT P.person_id, P.first, P.last";
$strSQL2.= " FROM $TABLE_PERSON as P";
$strSQL2.= " LEFT JOIN $TABLE_PROJECT_ACCESS as A on A.person_id=P.person_id";
$strSQL2.= " WHERE A.project_id='$strProject'";
$strSQL2.= " GROUP BY P.last ORDER BY P.last ASC";
$result2 = dbquery($strSQL2);


//As each person is listed in the database pull, their planned/actual hours will be calculated
$colorCount = 0;
while($row2 = mysql_fetch_array($result2))
{
	$person_id = $row2['person_id'];
	$person_name = $row2['last'].", ".$row2['first'];

	//Gather all of the tasks belonging to this person for this project
	//Each task will be analyzed to determine how many hours they work in a given month
	//That monthly total will be added to their total hours worked for that month
	$strSQL1 = "SELECT task_name, $taskHours, $taskDate";
	$strSQL1.= " FROM $TABLE_TASK, $TABLE_WBS_TO_TASK, $TABLE_PERSON_TO_WBSTASK";
	$strSQL1.= " WHERE $TABLE_TASK.task_id = $TABLE_WBS_TO_TASK.task_id";
	$strSQL1.= " AND $TABLE_PERSON_TO_WBSTASK.wbs_to_task_id = $TABLE_WBS_TO_TASK.wbs_to_task_id AND $TABLE_WBS_TO_TASK.rollup!=1";
	$strSQL1.= " AND person_id = '$person_id'";
	$strSQL1.= " AND project_id = '$strProject'";
	$strSQL1.= " ORDER BY due_date ASC";
	$result1 = dbquery($strSQL1);

	$ddate_epoc = array();  //Task Due date in unix time format
	$sdate_epoc = array();  //Task Start date in unix time format
	$phours = array();      //Planned Hours
	$ddate = array();       //Due date
	$sdate = array();       //Start date
	$hrsbymonth = array();  //This array will hold the hours the person works per month
	$count = 0;		//Counter
	while($row1 = mysql_fetch_array($result1))
	{
		$tname = $row1['task_name'];
		$phours[$count] = $row1[$taskHours];
		$ddate_epoc[$count] = $row1[$taskDate];
	
		if($ddate_epoc[$count] > 0)
		{
			//Calculate start date
			$sdate_epoc[$count] = getStart2($ddate_epoc[$count], $phours[$count]);
			$ddate[$count] = date_read($ddate_epoc[$count]);
			$sdate[$count] = date_read($sdate_epoc[$count]);

			$count ++;
		}
	}

	for($i = 0; $i < $count; $i++)
	{
	
		$startMonth = date("n", $sdate_epoc[$i]);
		$startYear = date("Y", $sdate_epoc[$i]);
		$dueMonth = date("n", $ddate_epoc[$i]);
		$dueYear = date("Y", $ddate_epoc[$i]);
		//echo "Hours: $phours[$i] Start date: $sdate[$i] Due date: $ddate[$i] Start1: $startMonth/$startYear<br>";
		//if start date and due date are equal, add task hours to corresponding array
		if($startMonth == $dueMonth)
		{
		   if(!isset($hrsbymonth[$startYear][$startMonth])){
                      $hrsbymonth[$startYear][$startMonth] = 0;
                   }
                   $hrsbymonth[$startYear][$startMonth] += $phours[$i];
		}

		//if start month and due month are not equal, find the number of hours left in the first month
		//and add that to the corresponding $hrsbymonth array. The difference between the start month
		//and end month determines how many times this process is repeated.
		if($startMonth != $dueMonth)
		{
			$totalHours = 0;
			$hours = 0;
			$checkDate = $sdate_epoc[$i];
			$checkMonth = $startMonth;
			$thisMonth = $startMonth;
			$thisYear = $startYear;
			while($totalHours < $phours[$i])
			{
				while($checkMonth == $thisMonth && $totalHours < $phours[$i])
				{
					$hours += 8;
					$totalHours += 8;
					if($totalHours > $phours[$i])
					{
						$hours -= 8;
						$totalHours -= 8;
						$hours += ($phours[$i] - $totalHours);
						$totalHours += 8;
					}
					$checkDate += 60*60*24;
					$dayofWeek = date("w", $checkDate);
					if($dayofWeek==0) 
					{
						$checkDate+=60*60*24;
					}
  					if($dayofWeek==6) 
					{
						$checkDate+=60*60*24*2;
					}
					$checkMonth = date("n", $checkDate);
				}
                                if(!isset($hrsbymonth[$thisYear][$thisMonth])){
                                   $hrsbymonth[$thisYear][$thisMonth] = 0;
                                }
                                if(!$planned && mktime(1,0,0,date("n"),1,date("Y")) < mktime(1,0,1,$thisMonth,1,$thisYear)){
                                   //If this task's actual time is in the future, don't add it
                                   $hrsbymonth[$thisYear][$thisMonth] += 0;
                                }else{
				   $hrsbymonth[$thisYear][$thisMonth] += $hours;
                                }
				$thisYear = date("Y", $checkDate);
				$thisMonth = date("n", $checkDate);
				$hours = 0;	
			}
		}
	}
//print_r($hrsbymonth);
//echo " startMonth: $projStrtMonth, startYear: $projStrtYear <br/>";
	$data = array();
	$count = 0;
	$month = $projStrtMonth;
	$year = $projStrtYear;
	while($count < $labelcount)
	{
                if(!isset($hrsbymonth[$year][$month])){
                   $data[$count] = 0;
                }else{
  		   $data[$count] = $hrsbymonth[$year][$month];
                }
 		$month++;
 		if($month > 12)
		{
    			$year++;
    			$month = 1;
  		}
  		$count++;
	}

    	$color= $COLORS[$colorCount];
    	$colorCount++;
    	if($colorCount > 14)
    	{
    		$colorCount = 0;
    	}
		
		
		
		
		
	    $printed = true;
    	$layer->addDataSet($data, $color, $person_name);
		
}//end while loop that calculates each persons hours

if ($printed == false) {
if ($strSmall){
$textBoxObj = $c->addText(30,30,"<*color=FF0000*>PLEASE ASSIGN TASKS TO INDIVIDUALS",Arial,12);
$textBoxObj = $c->addText(55, 70, "<*img=/srv/www/htdocs/paev4/paevnd.png*>");
}
else {
$textBoxObj = $c->addText(45,30,"<*color=FF0000*>PLEASE ASSIGN TASKS TO INDIVIDUALS",Arial,20);
$textBoxObj = $c->addText(200, 200, "<*img=/srv/www/htdocs/paev4/paevnd.png*>");
}

}

$textBoxObj = $c->addTitle("$title Hours by Month: $projName", "timesbi.ttf", 12);



// Enable bar label for the whole bar
$layer->setAggregateLabelStyle();

// Enable bar label for each segment of the stacked bar
$layer->setDataLabelStyle();

// output the chart
header("Content-type: image/png");
print($c->makeChart2(PNG));
?>