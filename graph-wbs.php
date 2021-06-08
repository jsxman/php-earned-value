<?

$HACK_CHECK=1; Include("config/global.inc.php");
debug(10,"Loading File: graph-g1.php");
checkPermissions($SESSION_TIMEOUT); // if not logged in, or session has timed out...

$strProject=postedData($_FORM['txt_project']);
$strSmall=(isset($_FORM['txt_small']))?postedData($_FORM['txt_small']):0;

require_once($CHARTDIRECTOR);


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
   $query1.= " FROM pev__wbs_to_task WHERE wbs_id = $wbs_id AND due_date>0";
   $query1.= " GROUP By wbs_id";

   $result1 = dbquery($query1);
   $row1 = mysql_fetch_array($result1);

   if(isset($row1['end'])){
      $total_hours = $row1['total_hours'];
      $endDate[] = chartTime2($row1['end']);        //Push date onto stack (array)

      $query11 = "SELECT due_date, planned_hours";
      $query11.= " FROM pev__wbs_to_task WHERE wbs_id = $wbs_id AND due_date>0";

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
      }

      $startDate[] = chartTime2($startTime);    //Push date onto stack (array)
      $percentStart[] = chartTime2($startTime);
      $todayTime = time(); //1272672000;

      $query22 = "SELECT due_date, SUM(planned_hours) AS planned_hours";
      $query22.= " FROM pev__wbs_to_task WHERE wbs_id=$wbs_id AND due_date>0 GROUP BY due_date ORDER BY due_date";
      $result22 = dbquery($query22);

      $query23 = "SELECT SUM(planned_hours*(percent_complete/100)/$total_hours) as percent_complete";
      $query23.= " FROM pev__wbs_to_task WHERE wbs_id=$wbs_id GROUP BY wbs_id";
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

//Get Chart Start and End Dates for the Chart Range
$query3 = "SELECT MAX(due_date) as end, project_name FROM pev__wbs_to_task";
$query3.= " LEFT JOIN pev__wbs ON pev__wbs.wbs_id=pev__wbs_to_task.wbs_id";
$query3.= " LEFT JOIN pev__project ON pev__project.project_id=pev__wbs.project_id";
$query3.= " WHERE pev__wbs.project_id=$strProject and due_date>0 GROUP BY pev__wbs.project_id";
$result3 = dbquery($query3);

$row3 = mysql_fetch_array($result3);
$chartEnd = $row3['end'] + (86400 * 7);
$project_name = $row3['project_name'];

$query33 = "SELECT MIN(due_date) as start, planned_hours FROM pev__wbs_to_task";
$query33.= " LEFT JOIN pev__wbs ON pev__wbs.wbs_id=pev__wbs_to_task.wbs_id";
$query33.= " WHERE pev__wbs.project_id=$strProject and due_date>0 GROUP BY pev__wbs.project_id";
$result33 = dbquery($query33);

$row33 = mysql_fetch_array($result33);
$workdays = $row33['planned_hours']/8;
$workdays = $workdays + (2 * floor($workdays/7));
$startTime = $row33['start'] - (3600*24*$workdays);
$chartStart = $startTime - (86400 * 7);


if($strSmall)
{
  $chartH=300;
  $chartW=350;
  $plotH=225;
  $plotW=300;
  $offsetW=25;
  $offsetH=40;
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
$c = new XYChart($chartW, $chartH, 0xccccff, 0x000000, 1);

# Add a title to the chart using 15 points Times Bold Itatic font, with white
# (ffffff) text on a deep blue (000080) background
$textBoxObj = $c->addTitle("Project Status Overview for $project_name", "timesbi.ttf", 15, 0xffffff);
$textBoxObj->setBackground(0x000080);

# Set the plotarea at (140, 55) and of size 460 x 200 pixels. Use alternative
# white/grey background. Enable both horizontal and vertical grids by setting their
# colors to grey (c0c0c0). Set vertical major grid (represents month boundaries) 2
# pixels in width
$plotAreaObj = $c->setPlotArea($offsetW, $offsetH, $plotW, $plotH, 0xffffff, 0xeeeeee, LineColor,
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
# Add a box whisker layer to represent the actual dates. We add the actual dates
# layer first, so it will be the top layer.
$actualLayer = $c->addBoxLayer($percentStart, $percentEnd, 0x00cc00,"Completed");


# Add a box-whisker layer to represent the planned schedule date
$boxLayerObj = $c->addBoxLayer($startDate, $endDate, 0xeeaaaa, "Planned");
$boxLayerObj->setBorderColor(SameAsMainColor);


if(!$strSmall){
   # Add a legend box on the top right corner (595, 60) of the plot area with 8 pt Arial
   # Bold font. Use a semi-transparent grey (80808080) background.
   $b = $c->addLegend(825, 550, false, "arialbd.ttf", 8);
   $b->setAlignment(TopRight);
   $b->setBackground(0x80808080, -1, 2);
}

# output the chart
header("Content-type: image/png");
print($c->makeChart2(PNG));



?>