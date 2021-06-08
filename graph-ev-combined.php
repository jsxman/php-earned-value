<?
$HACK_CHECK=1; Include("config/global.inc.php");
debug(10,"Loading File: graph-ev-expand.php");
checkPermissions($SESSION_TIMEOUT); // if not logged in, or session has timed out...

$strProject=postedData($_FORM['txt_project']);
$strSmall=(isset($_FORM['txt_small']))?postedData($_FORM['txt_small']):0;
$planned=(isset($_FORM['txt_planned']))?postedData($_FORM['txt_planned']):0;
require_once($CHARTDIRECTOR);

//Get start date for this project
$getStart = "SELECT start_date FROM pev__project WHERE project_id='$strProject'";
$result = dbquery($getStart);
$startRow = mysql_fetch_array($result);
$replan_dates[] = $startRow['start_date'];

//Find all associated projects, if any
$associated_project = $strProject;
$allProjects = Array();

while($associated_project != NULL){
   $allProjects[] = $associated_project;
   $getAssocProj = "SELECT project_id, start_date FROM pev__project WHERE associated_id='$associated_project'";
   $result = dbquery($getAssocProj);
   $row = mysql_fetch_array($result);
   $associated_project = $row['project_id'];
   $replan_dates[] = $row['start_date'];
}
//Reverse array, we want to start from the first available version
$allProjects = array_reverse($allProjects);

//Pop off the first replan date (and null value), this is the start of the first project - not needed
array_pop($replan_dates);
array_pop($replan_dates);


$currentProject = $allProjects[sizeof($allProjects)-1];

//Get project info
$strInfo = "SELECT MIN($TABLE_WBS_TO_TASK.due_date) AS start, MAX($TABLE_WBS_TO_TASK.due_date) AS end, MIN(wh_date) AS hist_st, MAX(wh_date) AS hist_end, project_name, start_date";
$strInfo.= " FROM $TABLE_WBS_TO_TASK";
$strInfo.= " LEFT JOIN $TABLE_WBS ON $TABLE_WBS.wbs_id=$TABLE_WBS_TO_TASK.wbs_id";
$strInfo.= " LEFT JOIN $TABLE_PROJECT ON $TABLE_WBS.project_id=$TABLE_PROJECT.project_id";
$strInfo.= " LEFT JOIN $TABLE_WBS_HISTORY ON $TABLE_WBS_HISTORY.wbs_id=$TABLE_WBS.wbs_id";
$strInfo.= " WHERE";
foreach($allProjects AS $value){
   $strInfo.= " $TABLE_PROJECT.project_id=$value OR";
}
$strInfo.= " $TABLE_PROJECT.project_id='-1'"; //this is used as filler that will not pull anything
$strInfo.= " AND $TABLE_WBS_TO_TASK.due_date > 0 GROUP BY $TABLE_PROJECT.project_id";

$resultInfo = dbquery($strInfo);

$end_date = 0;
$start_date = date("U");
while($rowInfo = mysql_fetch_array($resultInfo)){
   $project_name = $rowInfo['project_name'];

   

if($rowInfo['start'] > 0 && (!isset($start_date) || $rowInfo['start'] < $start_date)){
   $start_date = $rowInfo['start'];
}
if(!isset($start_date) || $rowInfo['hist_st'] < $start_date){
   $start_date = $rowInfo['hist_st'];
}
   
   if($rowInfo['end'] > $end_date){
      $end_date = $rowInfo['end'];
   }
   if($rowInfo['hist_end'] > $end_date){
      $end_date = $rowInfo['hist_end'];
   }
}

if($start_date == 0){
   //can't create graph!
   noGraph($strSmall);
   return;
}
if($end_date == 0){
   //can't create graph!
   noGraph($strSmall);
   return;
}

$actual_hrs = Array();
$actual_date = Array();

//set start of data
$actual_hrs[] = 0;
$actual_date[] = chartTime2($start_date-1);
$prevd = 0;
foreach($allProjects AS $key=>$proj_id){
   //Get ACWP (Actual Cost) -- get all actual hours recorded in history and add them up by date

   //This will ensure the previous versions hours are added to make the line consistant
   $prev_ver_hrs = $actual_hrs[sizeof($actual_hrs)-1];
   $max_ahours[$proj_id] = $prev_ver_hrs;

   //echo "{$max_ahours[$proj_id]} <br>";
   $strActual = "SELECT wh_date, SUM(total_ahours) AS actual";
   $strActual.= " FROM $TABLE_WBS_HISTORY";
   $strActual.= " LEFT JOIN $TABLE_WBS ON $TABLE_WBS.wbs_id=$TABLE_WBS_HISTORY.wbs_id";
   $strActual.= " WHERE project_id=$proj_id";
   $strActual.= " GROUP BY wh_date ORDER BY wh_date";

   $resultActual = dbquery($strActual);
   while($rowActual = mysql_fetch_array($resultActual)){
      
	  if($prevd <= $rowActual['wh_date']){
	  $actual_hrs[] = $rowActual['actual'] + $prev_ver_hrs;
      $actual_date[] = chartTime2($rowActual['wh_date']);
	  }
	  $prevd = $rowActual['wh_date'];
   }

}


$planned_hrs = Array();
$planned_date = Array();
$reserve_hrs = Array();   //Tasks that do not have a due date
$reserve_hrs2 = 0;
foreach($allProjects AS $key=>$proj_id){

   //This will move the planned hours to start at the same point the last actual hours
   if($currentProject == $proj_id){
      $getStart = "SELECT start_date FROM pev__project WHERE project_id='$proj_id'";
      $startResult = dbquery($getStart);
      $startRow = mysql_fetch_array($startResult);
      $planned_total = $prev_ver_hrs;
      $planned_hrs[$proj_id][] = $prev_ver_hrs;
      $planned_date[$proj_id][] = chartTime2($startRow['start_date']);
   }else{
      $planned_total = 0;
      //set start of data
      $planned_hrs[$proj_id][] = 0;
      $planned_date[$proj_id][] = chartTime2($start_date-1);
   }

   //Get BCWS (Planned Value) -- get all planned hours by due date and add them up
   $strPlanned = "SELECT $TABLE_WBS_TO_TASK.due_date, SUM($TABLE_WBS_TO_TASK.planned_hours) AS planned";
   $strPlanned.= " FROM $TABLE_WBS_TO_TASK";
   $strPlanned.= " LEFT JOIN $TABLE_WBS ON $TABLE_WBS.wbs_id=$TABLE_WBS_TO_TASK.wbs_id";
   $strPlanned.= " WHERE project_id=$proj_id";
   $strPlanned.= " GROUP BY $TABLE_WBS_TO_TASK.due_date ORDER BY $TABLE_WBS_TO_TASK.due_date";

   $resultPlanned = dbquery($strPlanned);
   while($rowPlanned = mysql_fetch_array($resultPlanned)){

      if($rowPlanned['due_date'] == 0){
         $reserve_hrs2 += $rowPlanned['planned'];
      }else{
         $planned_total += $rowPlanned['planned'];
         $planned_hrs[$proj_id][] = $planned_total;
         $planned_date[$proj_id][] = chartTime2($rowPlanned['due_date']);
      }
   }
   //handle planned hours that do not have a due date
   
      $planned_total += $reserve_hrs2;                                     
   
   $planned_hrs[$proj_id][] = $planned_total;
   $planned_date[$proj_id][] = chartTime2($end_date);
}

$earned_hrs = Array();
$earned_date = Array();

//set start of data
$earned_hrs[] = 0;
$earned_date[] = chartTime2($start_date-1);



/*
//Get BCWP (Earned Value) -- get planned hours for each wbs by date and multiply by percent complete, and add it to that date
$strEarned = "SELECT wh_date, SUM(total_phours * (percent_complete/100)) AS earned";
$strEarned.= " FROM $TABLE_WBS_HISTORY";
$strEarned.= " LEFT JOIN $TABLE_WBS ON $TABLE_WBS.wbs_id=$TABLE_WBS_HISTORY.wbs_id";
$strEarned.= " WHERE project_id=$strProject";
$strEarned.= " GROUP BY wh_date ORDER BY wh_date";

$resultEarned = dbquery($strEarned);
while($rowEarned = mysql_fetch_array($resultEarned)){
   $earned_hrs[] = $rowEarned['earned'];
   $earned_date[] = chartTime2($rowEarned['wh_date']);
}
*/

// ##########
// ##########
// ##########
//Get BCWP (Earned Value) -- get planned hours for each wbs by date and multiply by percent complete, and add it to that date
$prev_ver_hrs = $max_ahours[$proj_id];
$strTotal = "SELECT wt.wbs_id, SUM(wt.planned_hours) AS planned";
$strTotal.= " FROM pev__wbs_to_task AS wt LEFT JOIN pev__wbs AS w ON w.wbs_id=wt.wbs_id";
$strTotal.= " WHERE project_id=$strProject AND wt.rollup!=1 GROUP BY wt.wbs_id";

$total_result = dbquery($strTotal);
$wbsTotal = Array();
//Set up an array to hold the total planned hours for a wbs and index the array by wbs_id
while($row_total = mysql_fetch_array($total_result)){
   $wbsTotal[$row_total['wbs_id']] = $row_total['planned'];
}

$strEarned = "SELECT $TABLE_WBS_HISTORY.wbs_id, wh_date, $TABLE_WBS_HISTORY.percent_complete";
$strEarned.= " FROM $TABLE_WBS_HISTORY";
$strEarned.= " LEFT JOIN $TABLE_WBS ON $TABLE_WBS.wbs_id=$TABLE_WBS_HISTORY.wbs_id";
$strEarned.= " WHERE project_id=$strProject";
$strEarned.= " ORDER BY wh_date";

$resultEarned = dbquery($strEarned);
$last_date = 0;    //Holds last wh_date
$hoursEarned = 0;  //Earned value total for the recorded history date
$replanStart = Array();  //Holds start date for a replan period if exists
$replanEnd = Array();    //Holds end date for a replan period if exists 

//Add up all earned values for each WBS by recorded history date
while($rowEarned = mysql_fetch_array($resultEarned)){
   if($last_date != $rowEarned['wh_date'] && $last_date > 0){
      $earned_hrs[] = $hoursEarned + $prev_ver_hrs;
      $earned_date[] = chartTime2($last_date);
      //If the earned hours for this date is less than the earned hours for the previous date,
      //a replan must have occured. (A project cannot lose earned hours.) Mark the dates so
      //the graph can be annotated.
      
      $hoursEarned = 0;
   }
   if(!isset($wbsTotal[$rowEarned['wbs_id']])){
         //account for wbses that do not have any hours assigned to them
         $wbsTotal[$rowEarned['wbs_id']] = 0;
      }
   $hoursEarned += ($rowEarned['percent_complete']/100) * $wbsTotal[$rowEarned['wbs_id']];
   $last_date = $rowEarned['wh_date'];
   //echo "Date: $last_date, Hours: $hoursEarned<br/>";
}
$earned_hrs[] = $hoursEarned + $prev_ver_hrs;
$earned_date[] = chartTime2($last_date);




if($strSmall)
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
$c->setPlotArea($offsetW, $offsetH, $plotW, $plotH, 0xffffff, -1, -1, 0xcccccc, 0xcccccc);

if(!$strSmall){
   # Add a legend box at (50, 30) (top of the chart) with horizontal layout. Use 9 pts
   # Arial Bold font. Set the background and border color to Transparent.
   $legendObj = $c->addLegend(270, 30, false, "arialbd.ttf", 9);
   $legendObj->setBackground(Transparent);
   //$legendObj->addKey("Previous Budget Estimates", 0xcc9900);
}

# Add a title box to the chart using 15 pts Times Bold Italic font, on a light blue
# (CCCCFF) background with glass effect. white (0xffffff) on a dark red (0x800000)
# background, with a 1 pixel 3D border.
$textBoxObj = $c->addTitle("Combined Earned Value $project_name", "timesbi.ttf", 15);
$textBoxObj->setBackground(0xccccff, 0x000000, glassEffect());

# Add a title to the y axis
$c->yAxis->setTitle("Hours");

# Set the y-axis scale to be date scale with ticks every 7 days (1 week)
$c->xAxis->setDateScale(chartTime2($start_date-86400), chartTime2($end_date), 86400 * 7);

# Add a red (ff0000) dash line to represent the current day
$c->xAxis->addMark(chartTime2(time()), $c->dashLineColor(0xff0000, DashLine), "Today", "arialbd.ttf", 7)->setFontAngle(90);

if(!$strSmall){

   # Add a dash line to represent the total budget
   $c->yAxis->addMark($planned_total, $c->dashLineColor(0x336600, DashLine), "Current Budget ->", "arialbd.ttf", 7);

   #Add a dash line to represent all re-plan dates;
   foreach($replan_dates AS $date){
      $mark = $c->xAxis->addMark(chartTime2($date), $c->dashLineColor(0x663300, DashLine), "Re-Plan", "arialbd.ttf", 7);
      $mark->setLineWidth(2);
      $mark->setFontAngle(90);
   }
   //$legendObj->addKey("Re-Plan", $c->dashLineColor(0x663300, DashLine), 2);
}

if(!$strSmall){
   # Set multi-style axis label formatting. Month labels are in Arial Bold font in "mmm
   # d" format. Weekly labels just show the day of month and use minor tick (by using
   # '-' as first character of format string).
   $c->xAxis->setMultiFormat(StartOfWeekFilter(), "<*font=arialbd.ttf*>{value|mmm d yyyy}",
    StartOfDayFilter(), "-");

   $c->xAxis->setLabelStyle("", 8, 0x000000, 90);
}else{
   $c->xAxis->setMultiFormat(StartOfWeekFilter(), "<*font=arialbd.ttf*>{value|mmm\nd\nyyyy}",
    StartOfDayFilter(), "-");
}


# Add a title to the x axis
$c->xAxis->setTitle("Date");


# Add a line layer to the chart
$layer1 = $c->addLineLayer($planned_hrs[$allProjects[0]], $c->dashLineColor(0x0000cc, DashLine), "Initial Plan");
$layer1->setXData($planned_date[$allProjects[0]]);


$layer2 = $c->addLineLayer($planned_hrs[$allProjects[sizeof($allProjects)-1]], 0x0000cc, "Current Planned Value");
$layer2->setXData($planned_date[$allProjects[sizeof($allProjects)-1]]);


$layer3 = $c->addLineLayer($actual_hrs, 0x00ff00, "Actual Cost");
$layer3->setXData($actual_date);


$layer4 = $c->addLineLayer($earned_hrs, 0x000000, "Earned Value");
$layer4->setXData($earned_date);

# Set the default line width to 2 pixels
$layer1->setLineWidth(2);
$layer2->setLineWidth(2);
$layer3->setLineWidth(3);
$layer4->setLineWidth(3);

# output the chart
header("Content-type: image/png");
print($c->makeChart2(PNG));