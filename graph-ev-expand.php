<?
$HACK_CHECK=1; Include("config/global.inc.php");
debug(10,"Loading File: graph-ev-expand.php");
checkPermissions($SESSION_TIMEOUT); // if not logged in, or session has timed out...

$strProject=postedData($_FORM['txt_project']);
$strSmall=(isset($_FORM['txt_small']))?postedData($_FORM['txt_small']):0;

require_once($CHARTDIRECTOR);

//Get project info
$strInfo = "SELECT MIN(wt.due_date) AS start, MAX(wt.due_date) AS end, MIN(wh_date) AS hist_st, MAX(wh_date) AS hist_end, project_name, start_date";
$strInfo.= " FROM $TABLE_WBS_TO_TASK AS wt";
$strInfo.= " LEFT JOIN $TABLE_WBS ON $TABLE_WBS.wbs_id=wt.wbs_id";
$strInfo.= " LEFT JOIN $TABLE_PROJECT ON $TABLE_WBS.project_id=$TABLE_PROJECT.project_id";
$strInfo.= " LEFT JOIN $TABLE_WBS_HISTORY ON $TABLE_WBS_HISTORY.wbs_id=$TABLE_WBS.wbs_id";
$strInfo.= " WHERE $TABLE_PROJECT.project_id=$strProject AND wt.rollup != 1";
$strInfo.= " AND wt.due_date > 0 GROUP BY $TABLE_PROJECT.project_id";

$resultInfo = dbquery($strInfo);
$rowInfo = mysql_fetch_array($resultInfo);
$project_name = $rowInfo['project_name'];

if($rowInfo['start_date'] > 0){
   $start_date = $rowInfo['start_date'];
}
if($rowInfo['start'] > 0 && (!isset($start_date) || $rowInfo['start'] < $start_date)){
   $start_date = $rowInfo['start'];
}
if(!isset($start_date) || $rowInfo['hist_st'] < $start_date){
   $start_date = $rowInfo['hist_st'];
}
if(!isset($start_date) || $start_date == 0){
   //can't create graph!
   noGraph($strSmall);
   return;
}

$end_date = 0;
if($rowInfo['end'] > 0){
   $end_date = $rowInfo['end'];
}
if($rowInfo['hist_end'] > $end_date){
   $end_date = $rowInfo['hist_end'];
}
if($end_date == 0){
   //can't create graph!
   noGraph($strSmall);
   return;
}
$budget_dates = Array();
$budget_estimates = Array();
$last_estimate = 0;

//Get project's first planned value so we can see the growth of the project
$strFirst = "SELECT SUM(total_phours) as first, wh_date";
$strFirst.= " FROM $TABLE_WBS_HISTORY";
$strFirst.= " LEFT JOIN $TABLE_WBS ON $TABLE_WBS_HISTORY.wbs_id=$TABLE_WBS.wbs_id";
$strFirst.= " WHERE project_id=$strProject AND total_phours>0";
$strFirst.= " GROUP BY wh_date ORDER BY wh_date";

$resultFirst = dbquery($strFirst);
while($rowFirst = mysql_fetch_array($resultFirst)){
   //Only record budget changes if they are more than 5% of the last value
   if($rowFirst['first'] > ($last_estimate + $last_estimate*0.05)){
      $budget_estimates[] = $rowFirst['first'];
      $budget_dates[] = $rowFirst['wh_date'];
      $last_estimate = $rowFirst['first'];
   }
}

$planned_hrs = Array();
$planned_date = Array();
$reserve_hrs = Array();
$planned_total = 0;

//set start of data
$planned_hrs[] = 0;
$planned_date[] = chartTime2($start_date-1);


// ########
// ########
// ########
//Get BCWS (Planned Value) -- get all planned hours by due date and add them up
$strPlanned = "SELECT wt.due_date, SUM(wt.planned_hours) AS planned";
$strPlanned.= " FROM $TABLE_WBS_TO_TASK AS wt";
$strPlanned.= " LEFT JOIN $TABLE_WBS ON $TABLE_WBS.wbs_id=wt.wbs_id";
$strPlanned.= " WHERE project_id=$strProject AND wt.rollup != 1";
$strPlanned.= " GROUP BY wt.due_date ORDER BY wt.due_date";

$resultPlanned = dbquery($strPlanned);
while($rowPlanned = mysql_fetch_array($resultPlanned)){

   if($rowPlanned['due_date'] == 0){
      $reserve_hrs[] = $rowPlanned['planned'];
   }else{
      $planned_total += $rowPlanned['planned'];
      $planned_hrs[] = $planned_total;
      $planned_date[] = chartTime2($rowPlanned['due_date']);
   }
}
//handle planned hours that do not have a due date
foreach($reserve_hrs as $value){
   $planned_total += $value;
}
$planned_hrs[] = $planned_total;
$planned_date[] = chartTime2($end_date);

$actual_hrs = Array();
$actual_date = Array();

//set start of data
$actual_hrs[] = 0;
$actual_date[] = chartTime2($start_date-1);

//Get ACWP (Actual Cost) -- get all actual hours recorded in history and add them up by date
$strActual = "SELECT wh_date, SUM(total_ahours) AS actual";
$strActual.= " FROM $TABLE_WBS_HISTORY";
$strActual.= " LEFT JOIN $TABLE_WBS ON $TABLE_WBS.wbs_id=$TABLE_WBS_HISTORY.wbs_id";
$strActual.= " WHERE project_id=$strProject";
$strActual.= " GROUP BY wh_date ORDER BY wh_date";

$resultActual = dbquery($strActual);
while($rowActual = mysql_fetch_array($resultActual)){
   $actual_hrs[] = $rowActual['actual'];
   $actual_date[] = chartTime2($rowActual['wh_date']);
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
      $earned_hrs[] = $hoursEarned;
      $earned_date[] = chartTime2($last_date);
      //If the earned hours for this date is less than the earned hours for the previous date,
      //a replan must have occured. (A project cannot lose earned hours.) Mark the dates so
      //the graph can be annotated.
      if(sizeof($earned_hrs) > 1 && $hoursEarned < $earned_hrs[sizeof($earned_hrs)-2]){
         $replanStart[] = $earned_date[sizeof($earned_date)-2];
         $replanEnd[] = chartTime2($last_date);
      }
      $hoursEarned = 0;
   }
   $hoursEarned += ($rowEarned['percent_complete']/100) * $wbsTotal[$rowEarned['wbs_id']];
   $last_date = $rowEarned['wh_date'];
   //echo "Date: $last_date, Hours: $hoursEarned<br/>";
}
$earned_hrs[] = $hoursEarned;
$earned_date[] = chartTime2($last_date);

//print_r($earned_date);
//print_r($earned_hrs);
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
   $legendObj = $c->addLegend(500, 30, false, "arialbd.ttf", 9);
   $legendObj->setBackground(Transparent);
   //$legendObj->addKey("Previous Budget Estimates", 0xcc9900);
}

# Add a title box to the chart using 15 pts Times Bold Italic font, on a light blue
# (CCCCFF) background with glass effect. white (0xffffff) on a dark red (0x800000)
# background, with a 1 pixel 3D border.
$textBoxObj = $c->addTitle("Earned Value $project_name", "timesbi.ttf", 15);
$textBoxObj->setBackground(0xccccff, 0x000000, glassEffect());

# Add a title to the y axis
$c->yAxis->setTitle("Hours");

# Set the y-axis scale to be date scale with ticks every 7 days (1 week)
$c->xAxis->setDateScale(chartTime2($start_date-86400), chartTime2($end_date), 86400 * 7);

# Add a red (ff0000) dash line to represent the current day
$c->xAxis->addMark(chartTime2(time()), $c->dashLineColor(0xff0000, DashLine), "Today", "arialbd.ttf", 7);

if(!$strSmall){

   # Add a dash line to represent the total budget
   $c->yAxis->addMark($planned_total, $c->dashLineColor(0x336600, DashLine), "Total Budget ->", "arialbd.ttf", 7);
   /*
   $count = 0;
   $last_date = 0;
   foreach($budget_estimates as $value){
      //Display value if budget date is more than 4 weeks from previous date
      if($value != $planned_total && $count > 0 && ($budget_dates[$count]-$last_date) > 4*604800){
         # Add a dash line to represent the first project planned estimate
         $c->yAxis->addMark($value, $c->dashLineColor(0xcc9900, DashLine), date("M j Y",$budget_dates[$count]), "arial.ttf", 6);
         $last_date = $budget_dates[$count];
      }
      $count++;
   }
   */
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

//Add replan markers if any exits
if(isset($replanStart[0])){
   foreach($replanStart as $key => $value){
      $c->xAxis->addZone($value,$replanEnd[$key],0xccff0000);
   }
}

# Add a title to the x axis
$c->xAxis->setTitle("Date");

# Add a line layer to the chart
$layer1 = $c->addLineLayer($planned_hrs, 0x0000cc, "Planned Value");
$layer1->setXData($planned_date);

$layer2 = $c->addLineLayer($actual_hrs, 0x00ff00, "Actual Cost");
$layer2->setXData($actual_date);

$layer3 = $c->addLineLayer($earned_hrs, 0x000000, "Earned Value");
$layer3->setXData($earned_date);

# Set the default line width to 2 pixels
$layer1->setLineWidth(2);
$layer2->setLineWidth(2);
$layer3->setLineWidth(3);

# output the chart
header("Content-type: image/png");
print($c->makeChart2(PNG));

?>