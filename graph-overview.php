<?
$broke = false;
$HACK_CHECK=1; Include("config/global.inc.php");
debug(10,"Loading File: graph-g1.php");
checkPermissions($SESSION_TIMEOUT); // if not logged in, or session has timed out...

$strProject=postedData($_FORM['txt_project']);
$strSmall=(isset($_FORM['txt_small']))?postedData($_FORM['txt_small']):0;

require_once($CHARTDIRECTOR);

$strSQL2 = "SELECT MAX(wt.due_date) AS end, MIN(wt.due_date) as start";
$strSQL2.= " FROM pev__wbs_to_task AS wt";
$strSQL2.= " LEFT JOIN pev__wbs ON pev__wbs.wbs_id=wt.wbs_id";
$strSQL2.= " WHERE pev__wbs.project_id=$strProject AND wt.due_date>0 AND wt.rollup!=1";

$result2 = dbquery($strSQL2);
$row2 = mysql_fetch_array($result2);

$chartEnd = $row2['end'] + (86400 * 7);
$chartStart = $row2['start'] - (86400 * 7);

$strSQL2 = "SELECT MAX(wh_date) AS end, MIN(wh_date) as start";
$strSQL2.= " FROM pev__wbs_history";
$strSQL2.= " LEFT JOIN pev__wbs ON pev__wbs.wbs_id=pev__wbs_history.wbs_id";
$strSQL2.= " WHERE pev__wbs.project_id=$strProject";

$result2 = dbquery($strSQL2);
$row2 = mysql_fetch_array($result2);

if($chartEnd < $row2['end']){
   $chartEnd = $row2['end'] + (86400 * 7);
}
if($chartStart < $row2['start']){
   $chartStart = $row2['start'] - (86400 * 7);
}




/********************************************************WBS Progress Graph************************************/

$startDate = array();     //Holds start date for wbs
$endDate = array();       //Holds end date for wbs
$labels = array();        //Holds name of wbs
$percentStart = array();  //This will be the same as $startDate
$percentEnd = array();    //This will be a date that represents the percent complete of a WBS
$smallLabels = array();

//Get wbs start and end dates and labels
$query = "SELECT wbs_id, wbs_name FROM pev__wbs WHERE project_id=$strProject ORDER BY wbs_order";
$result = dbquery($query);

while($row = mysql_fetch_array($result)){
   $wbs_id = $row['wbs_id'];
   $wbs_name = $row['wbs_name'];

   $labels[] = $wbs_name;  //Push wbs name onto stack (array)
   $smallLabels[] = "";

   $query1 = "SELECT MAX(due_date) as end, SUM(planned_hours) as total_hours";
   $query1.= " FROM pev__wbs_to_task WHERE wbs_id=$wbs_id AND due_date>0 AND rollup!=1";
   $query1.= " GROUP By wbs_id";

   $result1 = dbquery($query1);
   $row1 = mysql_fetch_array($result1);

   if(isset($row1['end'])){
      $total_hours = $row1['total_hours'];
      $endDate[] = chartTime2($row1['end']);        //Push date onto stack (array)

      $query11 = "SELECT due_date, planned_hours";
      $query11.= " FROM pev__wbs_to_task WHERE wbs_id=$wbs_id AND due_date>0 AND rollup!=1";
      $query11.= " ORDER BY due_date ASC";

      $result11 = dbquery($query11);

      //Find the beginning time for the WBS
      $flag = true;
      while($row11 = mysql_fetch_array($result11)){
         if($flag){           //Only allow defined tasks set the start_date
            $workdays = $row11['planned_hours']/8;
            $startTime = $row11['due_date'];
            while($workdays > 0){
               $startTime = $startTime - 3600*24;
               $workdays--;
               if(date("w", $startTime) == 0){
                  $startTime = $startTime - 3600*24*2;
               }else if(date("w", $startTime) == 6){
                  $startTime = $startTime - 3600*24;
               }
            }
            $test_start_date = $startTime;
            $flag = false;
         }else{
            $workdays = $row11['planned_hours']/8;
            $test_start_date = $row11['due_date'];
            while($workdays > 0){
               $test_start_date = $test_start_date - 3600*24;
               $workdays--;
               if(date("w", $test_start_date) == 0){
                  $test_start_date = $test_start_date - 3600*24*2;
               }else if(date("w", $test_start_date) == 6){
                  $test_start_date = $test_start_date - 3600*24;
               }
            } 
         }

         //Compare them to the set start_date, if task starts sooner, replace start date
         if($startTime > $test_start_date){
            $startTime = $test_start_date;
         }

         if($startTime < $chartStart){
            $chartStart = $startTime - (86400 * 7);
         }
      }

            $startDate[] = chartTime2($startTime);    //Push date onto stack (array)
      $percentStart[] = chartTime2($startTime);
      $todayTime = time(); //1272672000;

      $query22 = "SELECT due_date, SUM(planned_hours) AS planned_hours";
      $query22.= " FROM pev__wbs_to_task WHERE wbs_id=$wbs_id AND due_date>0 AND rollup!=1 GROUP BY due_date ORDER BY due_date";
      $result22 = dbquery($query22);

      $query23 = "SELECT SUM(planned_hours*(percent_complete/100)/$total_hours) as percent_complete";
      $query23.= " FROM pev__wbs_to_task WHERE wbs_id=$wbs_id AND due_date>0 AND rollup!=1 GROUP BY wbs_id";
      $result23 = dbquery($query23);
      $row23 = mysql_fetch_array($result23);

      $prev_due_date = 0;
      $prev_percent_complete = 0;
      $cumulative_percent = 0;
      $cumulative_hours = 0;
      $due_date = 0;
      $taskHours = 0;
      while(($row22 = mysql_fetch_array($result22)) && ($cumulative_percent <= $row23['percent_complete']))
      {
        $taskHours = $row22['planned_hours'];
        $cumulative_hours += $taskHours;
        $prev_percent_complete = $cumulative_percent;
        $cumulative_percent = $cumulative_hours / $total_hours;
        $prev_due_date = $due_date;
        $due_date = $row22['due_date'];
//echo "Due Date: " . date("d M Y",$due_date) . ", Cumul. %: $cumulative_percent<br>";
      }

      $sched_var_percent = ($row23['percent_complete'] - $prev_percent_complete)/($cumulative_percent - $prev_percent_complete);
      if ($prev_due_date == 0)
        $prev_due_date = $startTime;
      $duration = $due_date - $prev_due_date;
      $percentDateEnd = ($duration * $sched_var_percent) + $prev_due_date;

      if ($percentDateEnd == 0)
      {
        $percentEnd[] = chartTime2($todayTime);
//echo "2) PercentDateEnd: " . date("d M Y",$todayTime) . ", Duration: " . $duration/(3600*24) . "<br>";
      }
      elseif ($sched_var_percent == 0)
      {
        if ( $todayTime > $due_date )
        {
          $taskWorkdays = $taskHours / 8;
          $taskTime = $due_date - ($taskWorkdays*3600*24);
          $percentEnd[] = chartTime2($taskTime);
//echo "3) PercentDateEnd: " . date("d M Y",$taskTime) . ", Due Date: " . date("d M Y",$due_date) . "Duration: " . $duration/(3600*24) . ", Planned Hours: $taskHours Workdays: $taskWorkdays<br>";
        }
        else
        {
          if ($row23['percent_complete'] == 0)
          {
            $percentEnd[] = chartTime2($startTime);
          }
          else
          {
            $percentEnd[] = chartTime2($todayTime);
          }
//echo "4) PercentDateEnd: " . date("d M Y",$todayTime) . ", Duration: " . $duration/(3600*24) . "<br>";
        }
      }
      else
      {
        $percentEnd[] = chartTime2($percentDateEnd);
//echo "5) PercentDateEnd: " . date("d M Y",$percentDateEnd) . ", Duration: " . $duration/(3600*24) . "<br>";
      }
   }else{
      $endDate[] = null;
      $startDate[] = null;
      $percentStart[] = null;
      $percentEnd[] = null;
   }

}

if($strSmall)
{
  $chartH=200;
  $chartW=350;
  $plotH=120;
  $plotW=300;
  $offsetW=35;
  $offsetH=0;
}
else
{
  $chartH=600;
  $chartW=850;
  $plotH=450;
  $plotW=680;
  $offsetW=150;
  $offsetH=55;
}


# Create a XYChart object of size 620 x 280 pixels. Set background color to light
# blue (ccccff), with 1 pixel 3D border effect.
$c = new XYChart($chartW, $chartH, 0xccccff, 0xFF000000, 0);



# Set the plotarea at (140, 55) and of size 460 x 200 pixels. Use alternative
# white/grey background. Enable both horizontal and vertical grids by setting their
# colors to grey (c0c0c0). Set vertical major grid (represents month boundaries) 2
# pixels in width
$plotAreaObj = $c->setPlotArea($offsetW, 20, $plotW, $plotH, 0xffffff, 0xeeeeee, LineColor,
    0xc0c0c0, 0xc0c0c0);
$plotAreaObj->setGridWidth(2, 1, 1, 1);

# swap the x and y axes to create a horziontal box-whisker chart
$c->swapXY();

# Add a red (ff0000) dash line to represent the current day
$c->yAxis->addMark(chartTime2(time()), $c->dashLineColor(0xff0000, DashLine));

# Set multi-style axis label formatting. Month labels are in Arial Bold font in "mmm
# d" format. Weekly labels just show the day of month and use minor tick (by using
# '-' as first character of format string).
$labelsObj = $c->yAxis->setMultiFormat(StartOfMonthFilter(), "<*font=arialbd.ttf*>{value|d\nmmm\nyyyy}",
    StartOfDayFilter(), "-{value|d\nmmm}");

# Reverse the x-axis scale so that it points downwards.
$c->xAxis->setReverse();

# Set the horizontal ticks and grid lines to be between the bars
$c->xAxis->setTickOffset(0.5);

//Add milestones to the Chart
$query4 = "SELECT event_type, other, event_pdate, event_edate, event_adate, is_doc FROM pev__event WHERE project_id=$strProject";
$result4 = dbquery($query4);

while($row4 = mysql_fetch_array($result4)){

   if(isset($row4['event_adate']) && $row4['event_adate'] != 0){
      $date = $row4['event_adate'];
   }elseif(isset($row4['event_edate']) && $row4['event_edate'] != 0){
      $date = $row4['event_edate'];
   }else{
      $date = $row4['event_pdate'];
   }

   if($chartStart > $date){
      $chartStart = $date - (86400 * 7);
   }
   if($chartEnd < $date){
      $chartEnd = $date + (86400 * 7);
   }

   if($row4['is_doc'] == 1){
      $docDates[] = chartTime2($date);
      $docLabels[] = $EVENT_TYPES[$row4['event_type']][0];
   }else if($row4['event_type'] == -1){
      $specialDates[] = chartTime2($date);
      $specialLabels[] = $row4['other'];
   }else{
      $meetingDates[] = chartTime2($date);
      $meetingLabels[] = $EVENT_TYPES[$row4['event_type']][0];
   }
}


$j = 0;
if(isset($meetingDates)){
   for($i=0; $i<count($meetingDates); $i++)
      $meetingArray[] = $j;
   $layerMeeting = $c->addScatterLayer($meetingArray, $meetingDates, "Meetings",
                    TriangleSymbol, 13, 0xaeb404);
   if(!$strSmall){
      $layerMeeting->addExtraField($meetingLabels);
      $layerMeeting->setDataLabelFormat("{field0}");
      $textbox1 = $layerMeeting->setDataLabelStyle("arialbd.tff", 8, 0x000000, 45);
      $textbox1->setAlignment(Top);
   }
   array_unshift($startDate, 0);    //Add first array element. Milestones will be used in this section of the chart
   array_unshift($endDate, 0);
   array_unshift($percentStart, 0);
   array_unshift($percentEnd, 0);

   $j++;
}
if(isset($docDates)){
   for($i=0; $i<count($docDates); $i++)
      $docArray[] = $j;
   $layerDocs = $c->addScatterLayer($docArray, $docDates, "Documents",
                 SquareSymbol, 13, 0x0404b4);
   if(!$strSmall){
      $layerDocs->addExtraField($docLabels);
      $layerDocs->setDataLabelFormat("{field0}");
      $textbox = $layerDocs->setDataLabelStyle("arialbd.tff", 8, 0x000000, 45);
      $textbox->setAlignment(Top);
   }
   array_unshift($startDate, 0);    //Add first array element. Milestones will be used in this section of the chart
   array_unshift($endDate, 0);
   array_unshift($percentStart, 0);
   array_unshift($percentEnd, 0);

   $j++;
}
if(isset($specialDates)){
   for($i=0; $i<count($specialDates); $i++)
      $specialArray[] = $j;
   $layerSpecial = $c->addScatterLayer($specialArray, $specialDates, "Special Events",
                    DiamondSymbol, 13, 0xb45f04);
   if(!$strSmall){
      $layerSpecial->addExtraField($specialLabels);
      $layerSpecial->setDataLabelFormat("{field0}");
      $textbox2 = $layerSpecial->setDataLabelStyle("arialbd.tff", 8, 0x000000, 45);
      $textbox2->setAlignment(Top);
   }
   array_unshift($startDate, 0);    //Add first array element. Milestones will be used in this section of the chart
   array_unshift($endDate, 0);
   array_unshift($percentStart, 0);
   array_unshift($percentEnd, 0);

   $j++;
}

//Add additional labels to chart (put them in reverse order of the way they should appear)
if(isset($specialDates)){
   array_unshift($labels, "Special Events");
   array_unshift($smallLabels, "");
}
if(isset($docDates)){
   array_unshift($labels, "Documents");
   array_unshift($smallLabels, "");
}
if(isset($meetingDates)){
   array_unshift($labels, "Meetings");
   array_unshift($smallLabels, "");
}

# Set the y-axis scale to be date scale from Aug 16, 2004 to Nov 22, 2004, with ticks
# every 7 days (1 week)
$c->yAxis->setDateScale(chartTime2($chartStart), chartTime2($chartEnd), 86400 * 7);

if($strSmall){
   # Set the labels on the x axis
   $c->xAxis->setLabels($smallLabels);
}else{
   $c->xAxis->setLabels($labels);
}

$c->xAxis->setTitle("WBS & Milestones");

if(!$strSmall){
   $c->yAxis->setTitle("Date");
}
# Add a box whisker layer to represent the actual dates. We add the actual dates
# layer first, so it will be the top layer.
$actualLayer = $c->addBoxLayer($percentStart, $percentEnd, 0x00cc00,"Completed");

# Add a box-whisker layer to represent the planned schedule date
$boxLayerObj = $c->addBoxLayer($startDate, $endDate, 0xeeaaaa, "Planned");
$boxLayerObj->setBorderColor(SameAsMainColor);


if(!$strSmall){
   # Add a legend box on the top right corner (595, 60) of the plot area with 8 pt Arial
   # Bold font. Use a semi-transparent grey (80808080) background.
   $b = $c->addLegend(825, 535, false, "arialbd.ttf", 8);
   $b->setAlignment(TopRight);
   $b->setBackground(0x80808080, -1, 2);
}



/****************************************CPI/SPI Graph**********************************************/

/* find total planned hours for all WBS for this project */

$PlannedHours=Array();
$strSQL0 = "SELECT P.project_name, W.wbs_id, W.wbs_name, sum(T.planned_hours) as tph FROM $TABLE_WBS AS W";
$strSQL0.= " LEFT JOIN $TABLE_WBS_TO_TASK AS T ON T.wbs_id=W.wbs_id";
$strSQL0.= " LEFT JOIN $TABLE_PROJECT AS P ON P.project_id=W.project_id";
$strSQL0.= " WHERE W.project_id='$strProject' AND T.rollup!=1";
$strSQL0.= " GROUP BY W.wbs_id";
$strSQL0.= " ORDER BY W.wbs_order";
$result0 = dbquery($strSQL0);
$wbsA=Array();
while($row0 = mysql_fetch_array($result0))
{
  $projectName=$row0['project_name'];
  $wbsA[$row0['wbs_id']]=$row0['wbs_name'];
  $wbsID=$row0['wbs_id'];

  /* get the total plan hours in the WBS - this is PV */
  //$strSQL4 = "SELECT sum(planned_hours) as total_plan FROM $TABLE_WBS_TO_TASK WHERE wbs_id='$wbsID' GROUP BY wbs_id";
  //$result4 = dbquery($strSQL4);
  //$row4 = mysql_fetch_array($result4);
  //$total_plan=$row4['total_plan'];
  $total_plan=$row0['tph'];
  $PlannedHours[$wbsID]=$total_plan;

  debug(10,"Planned hours for WBS ($wbsID) is ".$total_plan);
}


/* get the WBS-HISTORY for all WBS in this PROJECT */
$strSQL1 = "SELECT H.wh_date,H.percent_complete,H.total_phours,H.total_ahours,H.wbs_id,W.wbs_name FROM $TABLE_WBS AS W";
$strSQL1.= " LEFT JOIN $TABLE_WBS_HISTORY AS H ON H.wbs_id=W.wbs_id";
$strSQL1.= " WHERE W.project_id='$strProject'";
$strSQL1.= " ORDER BY H.wh_date ASC";
$result1 = dbquery($strSQL1);

$dataBCWSHours=Array();
$dataEarnedHours=Array();
$dataActualHours=Array();
while($row1 = mysql_fetch_array($result1))
{
  $historyDate = $row1['wh_date'];
  $wbsName=$row1['wbs_name'];
  $wbs=$row1['wbs_id'];
  if ($row1['percent_complete'] > 0) {
  $pc=$row1['percent_complete']/100;
  }
  else {
  $pc=0;
  }
  
  

  $tmp1=$pc*$PlannedHours[$wbs];
  $dataEarnedHours[$historyDate][$wbs]=$tmp1;
  $dataActualHours[$historyDate][$wbs]=$row1['total_ahours'];
  $dataBCWSHours[$historyDate][$wbs]=$row1['total_phours'];
  
}

$CPIvalue = Array();
$SPIvalue = Array();
$dates = Array();
foreach($dataEarnedHours as $date_key => $wbs_item){
   $eh=0;
   $pv=0;
   $ac=0;
   $bcws=0;
   foreach($wbs_item as $wbs_key => $wbs_hours){
       $bcws+=$dataBCWSHours[$date_key][$wbs_key];
       $eh+=$wbs_hours;                              //get earned hours
       $ac+=$dataActualHours[$date_key][$wbs_key];   //get actual hours
       $pv+=$PlannedHours[$wbs_key];                 //get planned hours
   }
   if ($eh > 1){
   $pc = $eh/$pv;
   }
   else{
   $pc = 0;
   }
   
   $ev = $pv*$pc;
  if ($date_key >= $chartStart) { //otherwise you are writing junk data outside of the graph
   if ($pc != 0)   { //otherwise it is junk data
   $CPIvalue[] = $ev/$ac;         //store CPI value
   }
   else {
   $CPIvalue[] = 1;
   }
   
   if (($ev != 0) && ($bcws != 0)) {
   $SPIvalue[] = $ev/$bcws;         //store SPI value
   $broke = false;
   }
   else {
   $SPIvalue[] = 0 ;
   $broke = true;
   }
   //$unixDates[] = $date_key;
   $dates[] = chartTime2($date_key);
   
   }
}

//Check to see if array has only one entry. If so, add another point so the
//line is visible on the chart
if(sizeof($CPIvalue) == 1){
   //Make the new points the samevalue and separate points by 23 hours
   $CPIvalue[] = $CPIvalue[0];
   $SPIvalue[] = $SPIvalue[0];
   $dates[] = $dates[0] - 23*3600;
}



if($strSmall)
{
  $chart2H=100;
  $chart2W=350;
  $plot2H=45;
  $plot2W=300;
  $offsetW=35;
  $offsetH=50;
}
else
{
  $chart2H=230;
  $chart2W=850;
  $plot2H=150;
  $plot2W=680;
  $offsetW=150;
  $offsetH=75;
}


# Create an XYChart object of size 600 x 300 pixels, with a light blue (EEEEFF)
# background, black border, 1 pxiel 3D border effect and rounded corners
$c2 = new XYChart($chart2W, $chart2H, 0xccccff, 0xFF000000, 0);
//$c->setRoundedFrame();

# Set the plotarea at (55, 58) and of size 520 x 195 pixels, with white background.
# Turn on both horizontal and vertical grid lines with light grey color (0xcccccc)
$c2->setPlotArea($offsetW, $offsetH, $plot2W, $plot2H, 0xffffff, -1, -1, 0xcccccc, 0xcccccc);

if(!$strSmall){
   # Add a legend box at (50, 30) (top of the chart) with horizontal layout. Use 9 pts
   # Arial Bold font. Set the background and border color to Transparent.
   $legendObj = $c2->addLegend($chart2W-150, 0, false, "arialbd.ttf", 8);
   $legendObj->setBackground(Transparent);
}

if($strSmall){
   # Add a title to the y axis
   $c2->yAxis->setTitle("PI");
}else{
   $c2->yAxis->setTitle("Performance Index");
}
$c2->yAxis->addZone(0, 0.5, 0x80ff3333);
$c2->yAxis->addZone(0.5, 0.8, 0x80ffff00);
$c2->yAxis->addZone(0.8, 1.5, 0x8099ff99); 
$c2->yAxis->addZone(1.5, 2, 0x80ffff00);
$c2->yAxis->addZone(2, 99, 0x80ff3333);
$c2->yAxis->setAutoScale(0.1,0.1);

$labelsObj = $c2->xAxis->setMultiFormat(StartOfMonthFilter(), "<*font=arialbd.ttf*>{value|d\nmmm\nyyyy}",
    StartOfDayFilter(), "-{value|d\nmmm}");

$c2->xAxis->setDateScale(chartTime2($chartStart), chartTime2($chartEnd), 86400 * 7);

if(!$strSmall){
   # Add a title to the x axis
   $c2->xAxis->setTitle("Date");
}

$c2->setXAxisOnTop();



# Add a line layer to the chart
$layer2 = $c2->addLineLayer($SPIvalue, 0x00ffff, "Schedule");
$layer2->setXData($dates);


# Set the default line width to 2 pixels
$layer2->setLineWidth(2);



# Add a line layer to the chart
$layer = $c2->addLineLayer($CPIvalue, 0x0000ff, "Cost");
$layer->setXData($dates);

# Set the default line width to 2 pixels
$layer->setLineWidth(2);


if($broke == true){
$scale = max($CPIvalue) + 0.5;
$c2->yAxis()->setLinearScale(0, $scale, 0);
}





/************************************************MultiChart****************************************/

$m = new MultiChart($chartW, $chartH+$chart2H);
$m->setRoundedFrame();

$textBoxObj = $m->addTitle("Project Overview: $projectName", "arialbi.ttf");
$textBoxObj->setBackground(0xccbbcc, 0x000000, glassEffect());

$m->addChart(0, $chart2H+10, $c);

$m->addChart(0, 25, $c2);


# output the chart
header("Content-type: image/png");
print($m->makeChart2(PNG));

?>