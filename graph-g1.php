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

$HACK_CHECK=1; Include("config/global.inc.php");
debug(10,"Loading File: graph-g1.php");
checkPermissions($SESSION_TIMEOUT); // if not logged in, or session has timed out...

$strProject=postedData($_FORM['txt_project']);
$strWBS=(isset($_FORM['txt_wbs']))?postedData($_FORM['txt_wbs']):0;
$strSmall=(isset($_FORM['txt_small']))?postedData($_FORM['txt_small']):0;


require_once($CHARTDIRECTOR);

//This is the newer function that should be more accurate
//getStart2 is the older version
function getStart($end_date, $task_hours)
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

/* compute the start date given then end date in "U" format (seconds since the EPOC */
/* assume 8 hour days, and weekend dates dont' count */
function getStart2($end_date,$task_hours)
{
  $endDay = date("w",$end_date); /* returns 0 for Sunday, 1 for Monday, ... 6 for Saturday */

  /* end date must be a work day M-F */
  if($endDay=0) $end_date+=60*60*24; /* add one day */
  if($endDay=6) $end_date+=60*60*24*2; /* add two days */

  $taskDays = round($task_hours / 8,0)+1; /* just add one to account for partial hours left over */

  /* adjust the task length for saturdays and sundays */
  $taskDays += round($taskDays/7,0) + 1;
  $startDate=$end_date - ($taskDays * 60*60*24);
  $startDay = date("w",$startDate); /* returns 0 for Sunday, 1 for Monday, ... 6 for Saturday */
  if($startDay=0) $startDate-=60*60*24*2; /* subtract two day */
  if($startDay=6) $startDate-=60*60*24; /* subtract one days */
  debug(10,"END-DATE($end_date), TASK-HOURS($task_hours), TASK-DAYS($taskDays), START-DATE($startDate)");
  return $startDate;
}


$theFirstDate=0;
$theLastDate=0;

/* FIND THE MAX HOURS to use as a SCALE */
if($strWBS)
{
$strSQL0 = "SELECT";
$strSQL0.= " WT.due_date";
$strSQL0.= ", WT.ec_date";
$strSQL0.= ", WT.planned_hours";
$strSQL0.= ", WT.actual_hours";
$strSQL0.= ", WT.percent_complete";
$strSQL0.= ", P.project_name";
$strSQL0.= ", T.task_name";
$strSQL0.= ", W.wbs_name";
$strSQL0.= " FROM $TABLE_WBS_TO_TASK AS WT";
$strSQL0.= " LEFT JOIN $TABLE_TASK AS T ON T.task_id=WT.task_id";
$strSQL0.= " LEFT JOIN $TABLE_WBS AS W ON W.wbs_id=WT.wbs_id";
$strSQL0.= " LEFT JOIN $TABLE_PROJECT AS P ON P.project_id=T.project_id";
$strSQL0.= " WHERE WT.wbs_id='$strWBS'";
$strSQL0.= " ORDER BY WT.due_date ASC";
}
else /* if not a specific WBS, then the entire PROJECT */
{
$strSQL0 = "SELECT";
$strSQL0.= " WT.due_date";
$strSQL0.= ", WT.ec_date";
$strSQL0.= ", WT.planned_hours";
$strSQL0.= ", WT.actual_hours";
$strSQL0.= ", WT.percent_complete";
$strSQL0.= ", P.project_name";
$strSQL0.= ", T.task_name";
$strSQL0.= ", W.wbs_name";
$strSQL0.= " FROM $TABLE_WBS_TO_TASK AS WT";
$strSQL0.= " LEFT JOIN $TABLE_TASK AS T ON T.task_id=WT.task_id";
$strSQL0.= " LEFT JOIN $TABLE_WBS AS W ON W.wbs_id=WT.wbs_id";
$strSQL0.= " LEFT JOIN $TABLE_PROJECT AS P ON P.project_id=T.project_id";
$strSQL0.= " WHERE P.project_id='$strProject'";
$strSQL0.= " ORDER BY WT.due_date ASC";
}
$result0 = dbquery($strSQL0);
debug(10,$strSQL0);
$labels=Array();
$startDate=Array();
$endDate=Array();
$actualStartDate=Array();
$actualEndDate=Array();
$count=-1;
$textMaxWidth = 40;
while($row0 = mysql_fetch_array($result0))
{
  $OKAY=1;
  $wbs=$row0['wbs_id'];
  $wbsName=$row0['wbs_name'];
  $task=$row0['task_name'];
  //Determine task_name size, set as max if greater than default
  $textWidth = strlen($task);
  if($textWidth > $textMaxWidth)
  {
      $textMaxWidth = $textWidth;
  }
  $projName=$row0['project_name'];
  $due_date=$row0['due_date'];$tmp=split("/",date_read($due_date));$chartED =chartTime("20".$tmp[2],$tmp[0],$tmp[1]);
  debug(10,"DUE_DATE ($due_date), TMP($tmp[2],$tmp[0],$tmp[1]), CHARTED($chartED)");

  $ec_date=$row0['ec_date'];$tmp=split("/",date_read($ec_date)); $chartAED=chartTime("20".$tmp[2],$tmp[0],$tmp[1]);
  $planned_hours=($row0['planned_hours'])?$row0['planned_hours']:1;
  $actual_hours=($row0['actual_hours'])?$row0['actual_hours']:1;
  $percent_complete=$row0['percent_complete'];
  if(!$due_date)$OKAY=0;
  if(!$ec_date)$OKAY=0;
  if(!$planned_hours)$OKAY=0;
  if(!$actual_hours)$OKAY=0;


  if($OKAY)
  {
    $count++;

    $tmpSD=getStart($due_date,$planned_hours);
    $tmp=split("/",date_read($tmpSD));
    $chartSD=chartTime("20".$tmp[2],$tmp[0],$tmp[1]);
    debug(10,"DUE($due_date), HOURS($planned_hours), START($tmpSD), TMP(20".$tmp[2]."/".$tmp[0]."/".$tmp[1].")");

    $tmpASD=getStart($ec_date,$actual_hours);
    $tmp=split("/",date_read($tmpASD));
    $chartASD=chartTime("20".$tmp[2],$tmp[0],$tmp[1]);
  
    $labels[$count]=$task;
    $startDate[$count]=$chartSD;
    $endDate[$count]=$chartED;
    $actualStartDate[$count]=$chartASD;
    $actualEndDate[$count]=$chartAED;

    if(!$theFirstDate) $theFirstDate=$chartSD;
    if($theFirstDate>$chartSD)$theFirstDate=$chartSD;
    if($theFirstDate>$chartASD)$theFirstDate=$chartASD;

    if(!$theLastDate) $theLastDate=$chartED;
    if($theLastDate<$chartED)$theLastDate=$chartED;
    if($theLastDate<$chartAED)$theLastDate=$chartAED;

    debug(10,"Added Task($count): $task SD($chartSD), ED($chartED), ASD($chartASD), AED($chartAED)");
  }
  if(!$ec_date && $planned_hours && $actual_hours && $due_date)
  {
    $count++;

    $tmpSD=getStart($due_date,$planned_hours);
    $tmp=split("/",date_read($tmpSD));
    $chartSD=chartTime("20".$tmp[2],$tmp[0],$tmp[1]);
    debug(10,"DUE($due_date), HOURS($planned_hours), START($tmpSD), TMP(20".$tmp[2]."/".$tmp[0]."/".$tmp[1].")");

    $tmpASD=getStart($ec_date,$actual_hours);
    $tmp=split("/",date_read($tmpASD));
    $chartASD=chartTime("20".$tmp[2],$tmp[0],$tmp[1]);
  
    $labels[$count]=$task;
    $startDate[$count]=$chartSD;
    $endDate[$count]=$chartED;
    $actualStartDate[$count]=$chartED;
    $actualEndDate[$count]=$chartED;

    if(!$theFirstDate) $theFirstDate=$chartSD;
    if($theFirstDate>$chartSD)$theFirstDate=$chartSD;
    //if($theFirstDate>$chartASD)$theFirstDate=$chartASD;

    if(!$theLastDate) $theLastDate=$chartED;
    if($theLastDate<$chartED)$theLastDate=$chartED;
    //if($theLastDate<$chartAED)$theLastDate=$chartAED;
  }
}
debug(10,"total number added $count");


/*
    $nRED  =rand(100,200); $nGREEN=rand(0,100); $nBLUE =rand(0,100);
    $c0=$nRED*256*256+$nGREEN*256+$nBLUE;
*/

/*

$labels = array("Market Research", "Define Specifications", "Overall Archiecture", "Project Planning", "Detail Design", "Software Development", "Test Plan", "Testing and QA", "User Documentation"); 
# the planned start dates and end dates for the tasks
$startDate = array(chartTime(2004, 8, 16), chartTime(2004, 8, 30), chartTime(2004, 9, 13), chartTime(2004, 9, 20), chartTime(2004, 9, 27), chartTime(2004, 10, 4), chartTime(2004, 10, 25), chartTime(2004, 11, 1), chartTime(2004, 11, 8));
$endDate = array(chartTime(2004, 8, 30), chartTime(2004, 9, 13), chartTime(2004, 9, 27), chartTime(2004, 10, 4), chartTime(2004, 10, 11), chartTime(2004, 11, 8), chartTime(2004, 11, 8), chartTime(2004, 11, 22), chartTime(2004, 11, 22)); 
# the actual start dates and end dates for the tasks up to now
$actualStartDate = array(chartTime(2004, 8, 16), chartTime(2004, 8, 27), chartTime( 2004, 9, 9), chartTime(2004, 9, 18), chartTime(2004, 9, 22));
$actualEndDate = array(chartTime(2004, 8, 27), chartTime(2004, 9, 9), chartTime(2004, 9, 27), chartTime(2004, 10, 2), chartTime(2004, 10, 8));

*/

//////////////////////////////////////////////////////
//////////////////////////////////////////////////////
//////////////////////////////////////////////////////
//////////////////////////////////////////////////////
//////////////////////////////////////////////////////
//////////////////////////////////////////////////////

if($strSmall)
{
  $chartH=300;
  $chartW=350;
  $plotH=200;
  $plotW=320;
  $boxW=20;
  $height1=3;
  for($i=0;$i<=$count;$i++)$labels[$i]="";
}
else
{
  $height1=6;
  $boxW=6*$textMaxWidth;
  $plotH=30+12*$count;
  $plotW=820;
  $chartH=120+12*$count;
  $chartW=$boxW+$plotW+20;
}

# Create a XYChart object of size 620 x 280 pixels. Set background color to light
# green (ccffcc) with 1 pixel 3D border effect.
$c = new XYChart($chartW, $chartH, 0xccffcc, 0x000000, 1);

# Add a title box to the chart using 15 pts Times Bold Italic font, on a light blue
# (CCCCFF) background with glass effect. white (0xffffff) on a dark red (0x800000)
# background, with a 1 pixel 3D border.
$textBoxObj = $c->addTitle("Project Schedule: $projName ($wbsName)", "timesbi.ttf", 12);
$textBoxObj->setBackground(0x8888AA);

# Set the plotarea at (140, 55) and of size 460 x 200 pixels. Use alternative 
# white/grey background. Enable both horizontal and vertical grids by setting their 
# colors to grey (c0c0c0). Set vertical major grid (represents month boundaries) 2 
# pixels in width
$plotAreaObj = $c->setPlotArea($boxW, 75, $plotW, $plotH, 0xffffff, 0xeeeeee, LineColor, 0xc0c0c0, 0xc0c0c0);
$plotAreaObj->setGridWidth(2, 1, 1, 1); 

# swap the x and y axes to create a horziontal box-whisker chart
$c->swapXY(); 

# Set the y-axis scale to be date scale from Aug 16, 2004 to Nov 22, 2004, with ticks 
# every 7 days (1 week)
$c->yAxis->setDateScale($theFirstDate, $theLastDate, 86400 * 7); 
//$c->yAxis->setDateScale(chartTime(2004, 8, 1), chartTime(2004, 12, 1), 86400 * 7); 

# Add a red (ff0000) dash line to represent the current day
$c->yAxis->addMark(chartTime(date("Y"), date("n"), date("j")), $c->dashLineColor(0xff0000, DashLine)); 
//$c->yAxis->addMark(chartTime(2004, 10, 1), $c->dashLineColor(0xff0000, DashLine)); 

# Set multi-style axis label formatting. Month labels are in Arial Bold font in "mmm
# d" format. Weekly labels just show the day of month and use minor tick (by using
# '-' as first character of format string).
$c->yAxis->setMultiFormat(StartOfMonthFilter(), "<*font=arialbd.ttf*>{value|mmm d}", StartOfDayFilter(), "-{value|d}"); 

# Set the y-axis to shown on the top (right + swapXY = top)
$c->setYAxisOnRight(); 

# Set the labels on the x axis
$c->xAxis->setLabels($labels); 

# Reverse the x-axis scale so that it points downwards.
$c->xAxis->setReverse(); 

# Set the horizontal ticks and grid lines to be between the bars
$c->xAxis->setTickOffset(0.5); 

# Use blue (0000aa) as the color for the planned schedule
$plannedColor = 0x0000aa; 


# Use a red hash pattern as the color for the actual dates. The pattern is created as 
# a 4 x 4 bitmap defined in memory as an array of colors.
$actualColor = $c->patternColor(array(0xffffff, 0xffffff, 0xffffff, 0xff0000, 0xffffff, 0xffffff, 0xff0000, 0xffffff, 0xffffff, 0xff0000, 0xffffff, 0xffffff, 0xff0000, 0xffffff, 0xffffff, 0xffffff), 4); 

# Add a box whisker layer to represent the actual dates. We add the actual dates 
# layer first, so it will be the top layer.
$actualLayer = $c->addBoxLayer($actualStartDate, $actualEndDate, $actualColor, "Actual");

# Set the bar height to 8 pixels so they will not block the bottom bar
$actualLayer->setDataWidth($height1); 

# Add a box-whisker layer to represent the planned schedule date
$boxLayerObj = $c->addBoxLayer($startDate, $endDate, $plannedColor, "Plan");
$boxLayerObj->setBorderColor(SameAsMainColor); 

# Add a legend box on the top right corner (590, 60) of the plot area with 8 pt Arial 
# Bold font. Use a semi-transparent grey (80808080) background.
$b = $c->addLegend($chartW-50, 23, false, "arialbd.ttf", 8);
$b->setAlignment(TopRight);
$b->setBackground(0x80808080, -1, 2);

//exit;

# output the chart
header("Content-type: image/png");
print($c->makeChart2(PNG));
?>
