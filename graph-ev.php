<?
$HACK_CHECK=1; Include("config/global.inc.php");
debug(10,"Loading File: graph-ev-expand.php");
checkPermissions($SESSION_TIMEOUT); // if not logged in, or session has timed out...

$strProject=postedData($_FORM['txt_project']);
$strSmall=(isset($_FORM['txt_small']))?postedData($_FORM['txt_small']):0;
$planned=(isset($_FORM['txt_planned']))?postedData($_FORM['txt_planned']):0;
require_once($CHARTDIRECTOR);

// ########
// ########
// ########
//Get project info
$strInfo = "SELECT MIN(wt.due_date) AS start, MAX(wt.due_date) AS end, MIN(wh_date) AS hist_st, MAX(wh_date) AS hist_end, project_name, $TABLE_PROJECT.start_date";
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


// ##############
// ##############
// ##############
//Get BCWS (Planned Value) -- get all planned hours by due date and add them up
$strPlanned = "SELECT $TABLE_WBS_TO_TASK.due_date, SUM($TABLE_WBS_TO_TASK.planned_hours) AS planned";
$strPlanned.= " FROM $TABLE_WBS_TO_TASK";
$strPlanned.= " LEFT JOIN $TABLE_WBS ON $TABLE_WBS.wbs_id=$TABLE_WBS_TO_TASK.wbs_id";
$strPlanned.= " WHERE project_id=$strProject AND rollup != 1";
$strPlanned.= " GROUP BY $TABLE_WBS_TO_TASK.due_date ORDER BY $TABLE_WBS_TO_TASK.due_date";

$resultPlanned = dbquery($strPlanned);
while($rowPlanned = mysql_fetch_array($resultPlanned)){

   if($rowPlanned['due_date'] == 0){
      $reserve_hrs[] = $rowPlanned['planned'];
   }else{
      $planned_total += $rowPlanned['planned'];
      $planned_hrs[] = $planned_total;
      $planned_date[] = chartTime2($rowPlanned['due_date']);
      $last_planned = chartTime2($rowPlanned['due_date']); //this is for quick fix below
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
   $last_actual = $rowActual['actual']; //this is used for quick fix below
   $last_date = chartTime2($rowActual['wh_date']); //ditto
}

$earned_hrs = Array();
$earned_date = Array();

//set start of data
$earned_hrs[] = 0;
$earned_date[] = chartTime2($start_date-1);

//Get BCWP (Earned Value) -- get planned hours for each wbs by date and multiply by percent complete, and add it to that date
$strEarned = "SELECT wh_date, SUM(total_phours * ($TABLE_WBS_HISTORY.percent_complete/100)) AS earned";
$strEarned.= " FROM $TABLE_WBS_HISTORY";
$strEarned.= " LEFT JOIN $TABLE_WBS ON $TABLE_WBS.wbs_id=$TABLE_WBS_HISTORY.wbs_id";
$strEarned.= " WHERE project_id=$strProject";
$strEarned.= " GROUP BY wh_date ORDER BY wh_date";

$resultEarned = dbquery($strEarned);
while($rowEarned = mysql_fetch_array($resultEarned)){
   $earned_hrs[] = $rowEarned['earned'];
   $earned_date[] = chartTime2($rowEarned['wh_date']);
}


// ###########
// ###########
// ###########
/************************************************************************
This has been thrown in as a quick fix for a perishable requirement
to plot estimated value--FIX LATER!
************************************************************************/
/* for each WBS, then select total planned hours, and total actual hours */
$strSQL0 = "SELECT ";
$strSQL0.= " sum(H.planned_hours) AS tph";
$strSQL0.= ",sum(H.actual_hours) AS tah";
$strSQL0.= ",W.wbs_id";
$strSQL0.= ",W.wbs_name";
$strSQL0.= ",P.project_name";
$strSQL0.= " FROM $TABLE_WBS AS W";
$strSQL0.= " LEFT JOIN $TABLE_WBS_TO_TASK AS H ON H.wbs_id=W.wbs_id";
$strSQL0.= " LEFT JOIN $TABLE_PROJECT AS P ON P.project_id=W.project_id";
$strSQL0.= " WHERE W.project_id='$strProject' AND rollup != 1";
$strSQL0.= " GROUP BY W.wbs_id";
$strSQL0.= " ORDER BY W.wbs_order ASC";
$result0 = dbquery($strSQL0);
$count=-1;
$wbsA=Array();

while($row0 = mysql_fetch_array($result0))
{
  $count++;
  $tph=$row0['tph'];
  $tah=$row0['tah'];
  $wbs=$row0['wbs_id'];
  $wbsName=$row0['wbs_name'];
  $labels[$count]=$wbsName;
  $projName=$row0['project_name'];

  if(!isset($data0)) $data0=Array();
  if(!isset($data1)) $data1=Array();
  if(!isset($data2)) $data2=Array();
  if(!isset($data0[$count]))$data0[$count]=0;
  if(!isset($data1[$count]))$data1[$count]=0;
  if(!isset($data2[$count]))$data2[$count]=0;
  $data0[$count]=$tph;
  $data1[$count]=$tah;

  $_ac=$tah;
  $_pv=$tph;
  $_pc=0;
  $strSQL1 = "SELECT percent_complete FROM $TABLE_WBS_HISTORY WHERE wbs_id='$wbs' ORDER BY wh_date DESC LIMIT 1";
  $result1 = dbquery($strSQL1);
  if($row1 = mysql_fetch_array($result1))
  {
    $_pc=$row1['percent_complete']/100;
  }
  $_ev=$_pv*$_pc;
  if($_ac == 0){
     $_cpi = 0;
  }else{
     $_cpi=$_ev/$_ac;
  }

  if($_pc == 0){
     $_spi = 0;
  }else{
     $_spi=$_ev/$_pc;
  }
  
  if($_cpi == 0){
     $_eac = 0;
  }else{
     $_eac=$_ac+($_pv-$_ev)/$_cpi;
  }
  $data2[$count]=$_eac;
  //debug(10,"MATH: (AC:$_ac, PV:$_pv, PC:$_pc, EV:$_ev, CPI:$_cpi, SPI:$_spi, EAC:$_eac)");
  //debug(10,"Data Values date($tdate) count[$count] ($tph, $tah, $tpc) d0:(".$data0[$count].") d1:(".$data1[$count].") d2:(".$data2[$count].")");
}
$total_eac = 0;
foreach($data2 AS $key => $value){
   $total_eac += $value;
   if($value == 0){
      $total_eac += $data0[$key];
   }
}

//Set up EAC array
if($last_date > $last_planned){
   $last_planned = $actual_date[sizeof($actual_date)-1];
}
$estimate = Array($last_actual, $total_eac);
$estimate_date = Array($last_date, $last_planned);


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
   $legendObj = $c->addLegend(400, 30, false, "arialbd.ttf", 9);
   $legendObj->setBackground(Transparent);
}

# Add a title box to the chart using 15 pts Times Bold Italic font, on a light blue
# (CCCCFF) background with glass effect. white (0xffffff) on a dark red (0x800000)
# background, with a 1 pixel 3D border.
$textBoxObj = $c->addTitle("$project_name: ESA Software Hours", "timesbi.ttf", 15);
$textBoxObj->setBackground(0xccccff, 0x000000, glassEffect());

# Add a title to the y axis
$c->yAxis->setTitle("Hours");

# Set the y-axis scale to be date scale with ticks every 7 days (1 week)
$c->xAxis->setDateScale(chartTime2($start_date-86400), chartTime2($end_date), 86400 * 7);

# Add a red (ff0000) dash line to represent the current day
$c->xAxis->addMark(chartTime2(time()), $c->dashLineColor(0xff0000, DashLine), "", "arialbd.ttf", 7);

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
$layer1 = $c->addLineLayer($planned_hrs, 0x0000cc, "Planned Hours");
$layer1->setXData($planned_date);

$layer2 = $c->addLineLayer($actual_hrs, 0x00ff00, "Actual Hours");
$layer2->setXData($actual_date);

if($planned){
$layer3 = $c->addLineLayer($estimate, $c->dashLineColor(0x000000, DashLine), "Estimate at Completion");
$layer3->setXData($estimate_date);
$layer3->setLineWidth(2);
}
# Set the default line width to 2 pixels
$layer1->setLineWidth(2);
$layer2->setLineWidth(2);


# output the chart
header("Content-type: image/png");
print($c->makeChart2(PNG));


?>