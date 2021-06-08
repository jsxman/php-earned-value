<?
$HACK_CHECK=1; Include("config/global.inc.php");
debug(10,"Loading File: department_status.php");
checkPermissions($SESSION_TIMEOUT); // if not logged in, or session has timed out...
$dbAdmin = $_SESSION['dbAdmin'];

if(!$dbAdmin){
   header("Location: index.php");
   exit;
}

require_once("/usr/share/php5/ChartDirector/lib/phpchartdir.php");
#/
#/ We need to handle 3 types of request:
#/ - initial request for the full web page
#/ - partial update (AJAX chart update) to update the chart without reloading the page
#/ - full page update for old browsers that does not support partial updates
#/

# The total date range of all data.
$startDate = null;
$endDate = null;

# The date range of the data that we zoomed into (visible on the chart).
$viewPortStartDate = null;
$viewPortEndDate = null;

#/ <summary>
#/ Handles the initial request
#/ </summary>
#/ <param name="WebChartViewer">The WebChartViewer object to handle the chart.</param>
function createFirstChart(&$viewer) {

    global $startDate, $endDate, $viewPortStartDate, $viewPortEndDate;

    # Initialize the Javascript ChartViewer
    $viewer->setMouseUsage(MouseUsageScroll);

    # In this demo, we allow scrolling the chart for the last 5 years
    list($unused, $unused, $unused, $d, $m, $y, $unused, $unused, $unused) = localtime();

    # The localtime month format is from 0 - 11, while the year is offsetted by 1900. We adjust them
    # to human used format.
    $m = $m + 1;
    $y = $y + 1900;

    $endDate = chartTime($y+1, $m, $d);

    # We roll back 5 years for the start date. Note that if the end date is Feb 29 (leap year only
    # date), we need to change it to Feb 28 in the start year
    if (($m == 2) && ($d == 29)) {
        $d = 28;
    }
    $startDate = chartTime($y - 5, $m, $d);

    # Initially set the view port to show data for the first year
    $viewPortStartDate = chartTime($y - 1, $m, $d);
    $viewPortEndDate = $endDate;

    # We store the scroll range as custom Javascript ChartViewer attributes, so the range can be
    # retrieved later in partial or full update requests
    $viewer->setCustomAttr("startDate", $startDate);
    $viewer->setCustomAttr("endDate", $endDate);

    # In this demo, we set the maximum zoom-in to 10 days
    $viewer->setZoomInWidthLimit(365 * 86400 / ($endDate - $startDate));

    # Draw the chart
    drawChart($viewer);
}


#/ <summary>
#/ Handles partial update (AJAX chart update)
#/ </summary>
#/ <param name="WebChartViewer">The WebChartViewer object to handle the chart.</param>
function processPartialUpdate(&$viewer) {

    global $startDate, $endDate, $viewPortStartDate, $viewPortEndDate;

    # Retrieve the overall date range from custom Javascript ChartViewer attributes.
    $startDate = $viewer->getCustomAttr("startDate");
    $endDate = $viewer->getCustomAttr("endDate");

    # Now we need to determine the visible date range selected by the user. There are two
    # possibilities. The user may use the zoom/scroll features of the Javascript ChartViewer to
    # select the range, or s/he may use the start date / end date select boxes to select the date
    # range.

    if ($viewer->isViewPortChangedEvent()) {
        # Is a view port change event from the Javascript ChartViewer, so we should get the selected
        # date range from the ChartViewer view port settings.
        $duration = $endDate - $startDate;
        $viewPortStartDate = $startDate + (int)(0.5 + $viewer->getViewPortLeft() * $duration);
        $viewPortEndDate = $viewPortStartDate + (int)(0.5 + $viewer->getViewPortWidth() * $duration)
            ;
    } else {
        # The user has changed the selected range by using the start date / end date select boxes.
        # We need to retrieve the selected dates from those boxes. For partial updates, the select
        # box values are sent in as Javascript ChartViewer custom attributes.
        $startYear = (int)($viewer->getCustomAttr("StartYear"));
        $startMonth = (int)($viewer->getCustomAttr("StartMonth"));
        $startDay = (int)($viewer->getCustomAttr("StartDay"));
        $endYear = (int)($viewer->getCustomAttr("EndYear"));
        $endMonth = (int)($viewer->getCustomAttr("EndMonth"));
        $endDay = (int)($viewer->getCustomAttr("EndDay"));

        # Note that for browsers that do not support Javascript, there is no validation on the
        # client side. So it is possible for the day to exceed the valid range for a month (eg. Nov
        # 31, but Nov only has 30 days). So we set the date by adding the days difference to the 1
        # day of a month. For example, Nov 31 will be treated as Nov 1 + 30 days = Dec 1.
        $viewPortStartDate = chartTime($startYear, $startMonth, 1) + ($startDay - 1) * 86400;
        $viewPortEndDate = chartTime($endYear, $endMonth, 1) + ($endDay - 1) * 86400;
    }

    # Draw the chart
    drawChart($viewer);

    #
    # We need to communicate the new start date / end date back to the select boxes on the browser
    # side.
    #

    # The getChartYMD function retrives the date as an 8 digit decimal number yyyymmdd.
    $startYMD = getChartYMD($viewPortStartDate);
    $endYMD = getChartYMD($viewPortEndDate);

    # Send year, month, day components to the start date / end date select boxes through Javascript
    # ChartViewer custom attributes.
    $viewer->setCustomAttr("StartYear", (int)($startYMD / 10000));
    $viewer->setCustomAttr("StartMonth", (int)($startYMD / 100) % 100);
    $viewer->setCustomAttr("StartDay", $startYMD % 100);
    $viewer->setCustomAttr("EndYear", (int)($endYMD / 10000));
    $viewer->setCustomAttr("EndMonth", (int)($endYMD / 100) % 100);
    $viewer->setCustomAttr("EndDay", $endYMD % 100);
}


#/ <summary>
#/ Handles full update
#/ </summary>
#/ <param name="WebChartViewer">The WebChartViewer object to handle the chart.</param>
function processFullUpdate(&$viewer) {
    # A full chart update is essentially the same as a partial chart update. The main difference is
    # that in a full chart update, the start date / end date select boxes are in Form Post
    # variables, while in partial chart update, they are in Javascript ChartViewer custom
    # attributes.
    #
    # So a simple implementation of the full chart update is to copy the Form Post values to the
    # Javascript ChartViewer custom attributes, and then call the partial chart update.

    # Controls to copy
    $ctrls = array("StartYear", "StartMonth", "StartDay", "EndYear", "EndMonth", "EndDay");

    # Copy control values to Javascript ChartViewer custom attributes
    for($i = 0; $i < count($ctrls); ++$i) {
        $viewer->setCustomAttr($ctrls[$i], $_REQUEST[$ctrls[$i]]);
    }

    # Now can use partial chart update
    processPartialUpdate($viewer);
}


#/ <summary>
#/ Draw the chart
#/ </summary>
#/ <param name="WebChartViewer">The WebChartViewer object to handle the chart.</param>
function drawChart(&$viewer) {

    global $startDate, $endDate, $viewPortStartDate, $viewPortEndDate;
    $strSmall = 0;
    #
    # Validate and adjust the view port dates.
    #

    # Verify if the view port dates are within limits
    $totalDuration = $endDate - $startDate;
    $minDuration = $viewer->getZoomInWidthLimit() * $totalDuration;
    if ($viewPortStartDate < $startDate) {
        $viewPortStartDate = $startDate;
    }
    if ($endDate - $viewPortStartDate < $minDuration) {
        $viewPortStartDate = $endDate - $minDuration;
    }
    if ($viewPortEndDate > $endDate) {
        $viewPortEndDate = $endDate;
    }
    if ($viewPortEndDate - $viewPortStartDate < $minDuration) {
        $viewPortEndDate = $viewPortStartDate + $minDuration;
    }

    # Adjust the view port to reflect the selected date range
    $viewer->setViewPortWidth(($viewPortEndDate - $viewPortStartDate) / $totalDuration);
    $viewer->setViewPortLeft(($viewPortStartDate - $startDate) / $totalDuration);

    #
    # Now we have the date range, we can get the necessary data. In this demo, we just use a random
    # number generator. In practice, you may get the data from a database or XML or by other means.
    # (See "Using Data Sources with ChartDirector" in the ChartDirector documentation if you need
    # some sample code on how to read data from database to array variables.)
    #

    //Get all projects that are active
$getProjects = "SELECT project_name, project_id FROM pev__project";// WHERE active='1'";
$getProjects.= " ORDER BY project_name ASC";
$proj_result = dbquery($getProjects);



$labels = array();        //Holds name of project
$startDate1 = array();     //Holds start date for project
$endDate1 = array();       //Holds end date for project
$percentStart = array();  //This will be the same as $startDate
$percentEnd = array();    //This will be a date that represents the progress of a project

$chartStart = 0;
$chartEnd = 0;

//Loop through each project result
while($proj_row = mysql_fetch_array($proj_result)){
   $strProject = $proj_row['project_id'];
   $projName = $proj_row['project_name'];

   //Push project name onto stack
   $labels[] = $projName;

   //Get the maximum due_date and total hours for the project
   $getMax = "SELECT MAX(due_date) as end, SUM(planned_hours) as total_hours";
   $getMax.= " FROM pev__wbs_to_task";
   $getMax.= " LEFT JOIN pev__wbs ON pev__wbs.wbs_id=pev__wbs_to_task.wbs_id";
   $getMax.= " LEFT JOIN pev__project ON pev__project.project_id=pev__wbs.project_id"; 
   $getMax.= " WHERE pev__wbs.project_id=$strProject AND due_date>0";
   $getMax.= " GROUP By pev__wbs.project_id";

   $max_result = dbquery($getMax);
   $max_row = mysql_fetch_array($max_result);

   if(isset($max_row['end']) && $max_row['total_hours'] > 0){
      $total_hours = $max_row['total_hours'];
      $endDate1[] = chartTime2($max_row['end']);        //Push date onto stack (array)

      //Set the ending chart time
      if($chartEnd < $max_row['end'] || $chartEnd == 0){
         $chartEnd = $max_row['end'];
      } 

      //Get all project tasks and find the start date
      $getTasks = "SELECT due_date, planned_hours";
      $getTasks.= " FROM pev__wbs_to_task";
      $getTasks.= " LEFT JOIN pev__wbs ON pev__wbs.wbs_id=pev__wbs_to_task.wbs_id";
      $getTasks.= " LEFT JOIN pev__project ON pev__project.project_id=pev__wbs.project_id";
      $getTasks.= " WHERE pev__wbs.project_id=$strProject AND due_date>0";

      $task_result = dbquery($getTasks);

      //Find the beginning time for the Project
      $flag = true;
      while($task_row = mysql_fetch_array($task_result)){
         if($flag){           //Only allow defined tasks set the start_date
            $workdays = $task_row['planned_hours']/8;
            $startTime = $task_row['due_date'];
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
            $workdays = $task_row['planned_hours']/8;
            $test_start_date = $task_row['due_date'];
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

      //Set the chart's beginning time
      if($chartStart > $startTime || $chartStart == 0){
         $chartStart = $startTime;
      }

      //Push project's start date onto stack (array)
      $startDate1[] = chartTime2($startTime);
      $percentStart[] = chartTime2($startTime);
      $todayTime = time(); //1272672000;

      //Get all tasks for the project and group them by due_date
      $proj_tasks = "SELECT due_date, SUM(planned_hours) AS planned_hours";
      $proj_tasks.= " FROM pev__wbs_to_task";
      $proj_tasks.= " LEFT JOIN pev__wbs ON pev__wbs.wbs_id=pev__wbs_to_task.wbs_id";
      $proj_tasks.= " LEFT JOIN pev__project ON pev__wbs.project_id=pev__project.project_id";
      $proj_tasks.= " WHERE pev__wbs.project_id=$strProject AND due_date>0 GROUP BY due_date ORDER BY due_date";
      $proj_tasks_result = dbquery($proj_tasks);

      //Get the project's percent completeness by hours
      $getPercent = "SELECT SUM(planned_hours*(percent_complete/100)/$total_hours) as percent_complete";
      $getPercent.= " FROM pev__wbs_to_task";
      $getPercent.= " LEFT JOIN pev__wbs ON pev__wbs.wbs_id=pev__wbs_to_task.wbs_id";
      $getPercent.= " LEFT JOIN pev__project ON pev__wbs.project_id=pev__project.project_id";
      $getPercent.= " WHERE pev__wbs.project_id=$strProject GROUP BY pev__wbs.project_id";
      $percent_result = dbquery($getPercent);
      $percent_row = mysql_fetch_array($percent_result);

      $prev_due_date = 0;
      $prev_percent_complete = 0;
      $cumulative_percent = 0;
      $cumulative_hours = 0;
      $due_date = 0;
      $taskHours = 0;

      //Tricky Part: Go through the project's tasks and figure out where the progress is on its timeline according to the hours earned (percent complete)
      while(($proj_tasks_row = mysql_fetch_array($proj_tasks_result)) && ($cumulative_percent <= $percent_row['percent_complete']))
      {
        $taskHours = $proj_tasks_row['planned_hours'];
        $cumulative_hours += $taskHours;
        $prev_percent_complete = $cumulative_percent;
        $cumulative_percent = $cumulative_hours / $total_hours;
        $prev_due_date = $due_date;
        $due_date = $proj_tasks_row['due_date'];
//echo "Due Date: " . date("d M Y",$due_date) . ", Cumul. %: $cumulative_percent<br>";
      }

      $sched_var_percent = ($percent_row['percent_complete'] - $prev_percent_complete)/($cumulative_percent - $prev_percent_complete);
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
          if ($percent_row['percent_complete'] == 0)
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
      $endDate1[] = null;
      $startDate1[] = null;
      $percentStart[] = null;
      $percentEnd[] = null;
   }
}//end project row while loop

//$viewPortStartDate, $viewPortEndDate
foreach($endDate1 AS $key => $value){
   if($value < $viewPortStartDate){
      $endDate1[$key] = $viewPortStartDate;
   }else if($value > $viewPortEndDate){
      $endDate1[$key] = $viewPortEndDate;
   }
}

foreach($startDate1 AS $key => $value){
   if($value < $viewPortStartDate){
      $startDate1[$key] = $viewPortStartDate;
   }else if($value > $viewPortEndDate){
      $startDate1[$key] = $viewPortEndDate;
   }
}

foreach($percentStart AS $key => $value){
   if($value < $viewPortStartDate){
      $percentStart[$key] = $viewPortStartDate;
   }else if($value > $viewPortEndDate){
      $percentStart[$key] = $viewPortEndDate;
   }
}

foreach($percentEnd AS $key => $value){
   if($value < $viewPortStartDate){
      $percentEnd[$key] = $viewPortStartDate;
   }else if($value > $viewPortEndDate){
      $percentEnd[$key] = $viewPortEndDate;
   }
}
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
  $offsetH=25;
}


# Create a XYChart object of size 620 x 280 pixels. Set background color to light
# blue (ccccff), with 1 pixel 3D border effect.
$c = new XYChart($chartW, $chartH, 0xccccff, 0xff000000, 0);

# Set the plotarea at (140, 55) and of size 460 x 200 pixels. Use alternative
# white/grey background. Enable both horizontal and vertical grids by setting their
# colors to grey (c0c0c0). Set vertical major grid (represents month boundaries) 2
# pixels in width
$plotAreaObj = $c->setPlotArea($offsetW, $offsetH, $plotW, $plotH, 0xffffff, -1, -1, 0xcccccc, 0xcccccc);
$plotAreaObj->setGridWidth(2, 1, 1, 1);

# swap the x and y axes to create a horziontal box-whisker chart
$c->swapXY();

# Add a red (ff0000) dash line to represent the current day
$c->yAxis->addMark(chartTime2(time()), $c->dashLineColor(0xff0000, DashLine));

# Set multi-style axis label formatting. Month labels are in Arial Bold font in "mmm
# d" format. Weekly labels just show the day of month and use minor tick (by using
# '-' as first character of format string).
$labelsObj = $c->yAxis->setMultiFormat(StartOfWeekFilter(), "<*font=arial.ttf*>{value|d mmm yyyy}",
    StartOfWeekFilter(), "");

$c->yAxis->setLabelStyle("", 8, 0x000000, 90);

# Reverse the x-axis scale so that it points downwards.
$c->xAxis->setReverse();

# Set the horizontal ticks and grid lines to be between the bars
$c->xAxis->setTickOffset(0.5);

# Set the y-axis scale to be date scale from Aug 16, 2004 to Nov 22, 2004, with ticks
# every 7 days (1 week)
//$c->yAxis->setDateScale(chartTime2($chartStart), chartTime2($chartEnd), 86400 * 7);

if($strSmall){
   # Set the labels on the x axis
   $c->xAxis->setLabels($smallLabels);
}else{
   $c->xAxis->setLabels($labels);

}
$c->yAxis->setTitle("Date");
$c->xAxis->setTitle("Projects");
//$c->yAxis->setLabelStep(4);

   # Add a box whisker layer to represent the actual dates. We add the actual dates
   # layer first, so it will be the top layer.
   $actualLayer = $c->addBoxLayer($percentStart, $percentEnd, 0x00cc00,"Completed");


   # Add a box-whisker layer to represent the planned schedule date
   $boxLayerObj = $c->addBoxLayer($startDate1, $endDate1, 0xeeaaaa, "Planned");
   $boxLayerObj->setBorderColor(SameAsMainColor);



   # Add a legend box on the top right corner (595, 60) of the plot area with 8 pt Arial
   # Bold font. Use a semi-transparent grey (80808080) background.
   $b = $c->addLegend(825, 550, false, "arialbd.ttf", 8);
   $b->setAlignment(TopRight);
   $b->setBackground(0x80808080, -1, 2);


/******************************CREATE VARIANCE CHART**********************************/
//Tables joins not needed, but are in place in case other limitations need to be placed in the future
$strGetValues = "SELECT SUM(total_phours) AS total_pv, SUM(total_ahours) AS total_ac, SUM(total_phours*(percent_complete/100)) AS total_ev, wh_date FROM pev__wbs_history AS WH";
$strGetValues.= " LEFT JOIN pev__wbs AS W ON W.wbs_id=WH.wbs_id";
$strGetValues.= " LEFT JOIN pev__project AS P ON W.project_id=P.project_id";
$strGetValues.= " WHERE wh_date > 0";
$strGetValues.= " GROUP BY wh_date ORDER BY wh_date";

$value_result = dbquery($strGetValues);
//$yDataEV = Array();    //earned value
//$yDataAC = Array();    //actual cost
$costVariance = Array();
$xDates = Array();     //history dates
$start_date = 0;
$end_date = 0;
$count = 0;
$costMovingAverage = Array();
$scheduleMovingAverage = Array();
$hoursCost = Array(0=>0,0,0,0,0,0,0,0,0);
$hoursSchedule = Array(0=>0,0,0,0,0,0,0,0,0);
while($row = mysql_fetch_array($value_result)){
   $costSum = 0;
   $scheduleSum = 0;
   if(chartTime2($row['wh_date']) > $viewPortStartDate && chartTime2($row['wh_date']) < $viewPortEndDate){
      $actualCost = $row['total_ac'];
      $plannedValue = $row['total_pv'];
      $earnedValue = $row['total_ev'];
      $costVariance[] = $earnedValue - $actualCost;
      $scheduleVariance[] = $earnedValue - $plannedValue;
      $xDates[] = chartTime2($row['wh_date']);
      $last_date = chartTime2($row['wh_date']);
      $index = $count % 6;
      $hoursCost[$index] = $earnedValue - $actualCost;
      $hoursSchedule[$index] = $earnedValue - $plannedValue;
      for($i=0;$i<6;$i++){
         $costSum+= $hoursCost[$i];
         $scheduleSum+= $hoursSchedule[$i];
      }
      $costMovingAverage[] = $costSum/6;
      $scheduleMovingAverage[] = $scheduleSum/6;
      $count++;
   }
}


$chart2H=250;
$chart2W=850;
$plot2H=120;
$plot2W=680;
$offsetW=150;
$offsetH=110;


# Create an XYChart object of size 600 x 300 pixels, with a light blue (EEEEFF)
# background, black border, 1 pxiel 3D border effect and rounded corners
$c2 = new XYChart($chart2W, $chart2H, 0xccccff, 0xff000000, 0);

# Set the plotarea at (55, 58) and of size 520 x 195 pixels, with white background.
# Turn on both horizontal and vertical grid lines with light grey color (0xcccccc)
$c2->setPlotArea($offsetW, $offsetH, $plot2W, $plot2H, 0xffffff, -1, -1, 0xcccccc, 0xcccccc);


# Add a legend box at (50, 30) (top of the chart) with horizontal layout. Use 9 pts
# Arial Bold font. Set the background and border color to Transparent.
$legendObj = $c2->addLegend(830, 5, false, "arialbd.ttf", 8);
$legendObj->setAlignment(TopRight);
$legendObj->setBackground(0x80808080, -1, 2);

# Add a title to the y axis
$c2->yAxis->setTitle("Variance (Hours)");


$labelsObj = $c2->xAxis->setMultiFormat(StartOfWeekFilter(), "<*font=arial.ttf*>{value|d mmm yyyy}",
    StartOfWeekFilter(), "");
$c2->xAxis->setLabelStyle("", 8, 0x000000, 90);

$c2->setXAxisOnTop();
# Add a title to the x axis
$c2->xAxis->setTitle("Date");

# Add a line layer to the chart
//$layer1 = $c2->addLineLayer($costVariance, 0xbb0000cc, "Cost Variance");
//$layer1->setXData($xDates);

$curve = new ArrayMath($costMovingAverage);
$curve->lowess();
$curveArray = $curve->result();

$curve1 = new ArrayMath($scheduleMovingAverage);
$curve1->lowess();
$curve1Array = $curve1->result();

$layer2 = $c2->addSplineLayer($costMovingAverage, 0x0000cc, "Average Cost Variance");
$layer2->setXData($xDates);

//$layer3 = $c2->addLineLayer($scheduleVariance, 0xbbff0000, "Schedule Variance");
//$layer3->setXData($xDates);

$layer4 = $c2->addSplineLayer($scheduleMovingAverage, 0xff0000, "Average Schedule Variance");
$layer4->setXData($xDates);

$layer5 = $c2->addSplineLayer(Array(0), 0xff000000, "");
$layer5->setXData(Array($viewPortEndDate));

$layer6 = $c2->addSplineLayer(Array(0), 0xff000000, "");
$layer6->setXData(Array($viewPortStartDate));

# Set the default line width to 2 pixels
//$layer1->setLineWidth(2);
$layer2->setLineWidth(2);
//$layer3->setLineWidth(2);
$layer4->setLineWidth(2);

$c2->layout();
$c2->yAxis->addZone($c2->yAxis->getMinValue(), 0, 0x80ff3333);
$c2->yAxis->addZone(0, $c2->yAxis->getMaxValue(), 0x8099ff99);
/************************************************MultiChart****************************************/

$m = new MultiChart($chartW, $chartH+$chart2H+10);
$m->setRoundedFrame();

$textBoxObj = $m->addTitle("Department Overview", "arialbi.ttf");
$textBoxObj->setBackground(0xccbbcc, 0x000000, glassEffect());

$m->addChart(0, $chart2H+10, $c);

$m->addChart(0, 25, $c2);
    #================================================================================
    # Step 4 - Set up y-axis scale
    #================================================================================

    if ($viewer->getZoomDirection() == DirectionHorizontal) {
        # y-axis is auto-scaled - so vertically, the view port always cover the entire y data range.
        # We save the y-axis scale for supporting xy-zoom mode if needed in the future.
        $c->layout();
        $yAxisObj = $c->yAxis;
        $viewer->setCustomAttr("minValue", $yAxisObj->getMinValue());
        $yAxisObj = $c->yAxis;
        $viewer->setCustomAttr("maxValue", $yAxisObj->getMaxValue());
        $viewer->setViewPortTop(0);
        $viewer->setViewPortHeight(1);
    } else {
        # xy-zoom mode - retrieve the auto-scaled axis range, which contains the entire y data
        # range.
        $minValue = $viewer->getCustomAttr("minValue");
        $maxValue = $viewer->getCustomAttr("maxValue");

        # Compute the view port axis range
        $axisLowerLimit = $maxValue - ($maxValue - $minValue) * ($viewer->getViewPortTop() +
            $viewer->getViewPortHeight());
        $axisUpperLimit = $maxValue - ($maxValue - $minValue) * $viewer->getViewPortTop();

        # Set the axis scale to the view port axis range
        $c->yAxis->setLinearScale($axisLowerLimit, $axisUpperLimit);
        $c2->xAxis->setLinearScale($axisLowerLimit, $axisUpperLimit);
        //$c2->xAxis->setDateScale($axisLowerLimit, $axisUpperLimit, 86400 * 7);

        # By default, ChartDirector will round the axis scale to the tick position. For zooming, we
        # want to use the exact computed axis scale and so we disable rounding.
        $c->yAxis->setRounding(false, false);
    }

    #================================================================================
    # Step 5 - Output the chart
    #================================================================================

    # Create the image and save it in a temporary location
    $chartQuery = $m->makeSession($viewer->getId());

    # Include tool tip for the chart
    $imageMap = $m->getHTMLImageMap("show.php", "",
        "title='{xLabel}'");

    # Set the chart URL, image map, and chart metrics to the viewer. For the image map, we use
    # delayed delivery and with compression, so the chart image will show up quicker.
    $viewer->setImageUrl("getchart.php?".$chartQuery);
    $viewer->setImageMap("getchart.php?".$viewer->makeDelayedMap($imageMap, true));
    $viewer->setChartMetrics($m->getChartMetrics());
}


#/ <summary>
#/ A utility to create the <option> tags for the date range <select> boxes
#/ </summary>
#/ <param name="startValue">The minimum selectable value.</param>
#/ <param name="endValue">The maximum selectable value.</param>
#/ <param name="selectedValue">The currently selected value.</param>
function createSelectOptions($startValue, $endValue, $selectedValue) {
    $ret = array_pad(array(), ($endValue - $startValue + 1), null);
    for($i = $startValue; $i < $endValue + 1; ++$i) {
        if ($i == $selectedValue) {
            # Use a "selected" <option> tag if it is the selected value
            $ret[$i - $startValue] = "<option value='$i' selected>$i</option>";
        } else {
            # Use a normal <option> tag
            $ret[$i - $startValue] = "<option value='$i'>$i</option>";
        }
    }
    return join("", $ret);
}


# Create the WebChartViewer object
$viewer = new WebChartViewer("chart1");
if ($viewer->isPartialUpdateRequest()) {
    # Is a partial update request (AJAX chart update)
    processPartialUpdate($viewer);
    # Since it is a partial update, there is no need to output the entire web page. We stream the
    # chart and then terminate the script immediately.
    print($viewer->partialUpdateChart());
    exit();
} else if ($viewer->isFullUpdateRequest()) {
    # Is a full update request
    processFullUpdate($viewer);
} else {
    # Is a initial request
    createFirstChart($viewer);
}

# Create the <option> tags for the start date / end date select boxes to reflect the currently
# selected data range
$startYearSelectOptions = createSelectOptions((int)(getChartYMD($startDate) / 10000), (int)(
    getChartYMD($endDate) / 10000), (int)(getChartYMD($viewPortStartDate) / 10000));
$startMonthSelectOptions = createSelectOptions(1, 12, (int)(getChartYMD($viewPortStartDate) / 100) %
    100);
$startDaySelectOptions = createSelectOptions(1, 31, (int)(getChartYMD($viewPortStartDate) % 100));
$endYearSelectOptions = createSelectOptions((int)(getChartYMD($startDate) / 10000), (int)(
    getChartYMD($endDate) / 10000), (int)(getChartYMD($viewPortEndDate) / 10000));
$endMonthSelectOptions = createSelectOptions(1, 12, (int)(getChartYMD($viewPortEndDate) / 100) % 100
    );
$endDaySelectOptions = createSelectOptions(1, 31, (int)(getChartYMD($viewPortEndDate) % 100));


?>
<html>
<head>
    <title>Software Engineering Project Status</title>
    <link href="style.css" rel="stylesheet" type="text/css" >
    <link rel="stylesheet" href="scripts/csshorizontalmenu.css" type="text/css" >
    <script language="Javascript" src="scripts/CharDirZoomScroll/cdjcv.js"></script>
    <style>
        div.chartPushButtonSelected { padding:5px; background:#ccffcc; cursor:hand; }
        div.chartPushButton { padding:5px; cursor:hand; }
        td.chartPushButton { font-family:Verdana; font-size:9pt; cursor:pointer; border-bottom:#000000 1px solid; }
    </style>
</head>
<body leftMargin="0" topMargin="0" onload="initJsChartViewer()">
<div class=header><img src="images/paev_header.png" alt="PAEV"><a href="http://paev.js-x.com/">PHP Adjusted Earned Value System</a></div>
<?
show_menu("REPORTS");
show_status();
show_error();
?>

<script>
// Initialize browser side Javascript controls
function initJsChartViewer()
{
    // Check if the Javascript ChartViewer library is loaded
    if (!window.JsChartViewer)
        return;

    // Get the Javascript ChartViewer object
    var viewer = JsChartViewer.get('<?php echo $viewer->getId()?>');

    // Connect the mouse usage buttons to the Javascript ChartViewer object
    connectViewerMouseUsage('ViewerMouseUsage1', viewer);
    // Connect the xy zoom mode buttons to the Javascript ChartViewer object
    connectViewerZoomControl('ViewerZoomControl1', viewer);

    // Detect if browser is capable of support partial update (AJAX chart update)
    if (JsChartViewer.canSupportPartialUpdate())
    {
        // Browser can support partial update, so connect the view port change event and
        // the submit button to trigger a partial update
        viewer.attachHandler("ViewPortChanged", viewer.partialUpdate);
        document.getElementById('SubmitButton').onclick = function() { viewer.partialUpdate(); return false; };

        // For partial updates, we need to pass the start date / end date select boxes values to/from
        // the server via Javascript ChartViewer custom attributes
        var controlsToSync = ['StartYear', 'StartMonth', 'StartDay', 'EndYear', 'EndMonth', 'EndDay'];
        viewer.attachHandler("PreUpdate", function() { copyToViewer(viewer, controlsToSync); });
        viewer.attachHandler("PostUpdate", function() { copyFromViewer(viewer, controlsToSync); });
    }
    else
        // Browser cannot support partial update - so use full page update
         viewer.attachHandler("ViewPortChanged", function() { document.forms[0].submit(); });
}
// A utility to copy HTML control values to Javascript ChartViewer custom attributes
function copyToViewer(viewer, controlsToSync)
{
    for (var i = 0; i < controlsToSync.length; ++i)
    {
        var obj = document.getElementById(controlsToSync[i]);
        if (obj && !{"button":1, "file":1, "image":1, "reset":1, "submit":1}[obj.type])
        {
            if ((obj.type == "checkbox") || (obj.type == "radio"))
                viewer.setCustomAttr(obj.id, obj.checked ? 1 : 0);
            else
                viewer.setCustomAttr(obj.id, obj.value);
        }
    }
}
// A utility to copy Javascipt ChartViewer custom attributes to HTML controls
function copyFromViewer(viewer, controlsToSync)
{
    for (var i = 0; i < controlsToSync.length; ++i)
    {
        var obj = document.getElementById(controlsToSync[i]);
        if (obj)
        {
            var value = viewer.getCustomAttr(obj.id);
            if (typeof value != "undefined")
            {
                if ((obj.type == "checkbox") || (obj.type == "radio"))
                    obj.checked = parseInt(value);
                else
                    obj.value = value;

                if (obj.validate)
                    obj.validate();
            }
        }
    }
}
</script>
<div style="margin-top:1%; margin-left:1%;">
<form method="post">
<table cellSpacing="0" cellPadding="0" border="0">
    <tr>
        <td align="right" bgColor="#000088" colSpan="2">
            <div style="padding-bottom:2px; padding-right:3px; font-weight:bold; font-size:10pt; font-style:italic; font-family:Arial;">
                <A style="color:#FFFF00; text-decoration:none" href="http://softeng">Software Engineering</a>
            </div>
        </td>
    </tr>
    <tr valign="top">
        <td style="border-left:black 1px solid; border-right:black 1px solid; border-bottom:black 1px solid;" width="150" bgColor="#c0c0ff">
            <!-- The following table is to create 3 cells for 3 buttons. The buttons are used to control
                 the mouse usage mode of the Javascript ChartViewer. -->
            <table id="ViewerMouseUsage1" cellSpacing="0" cellPadding="0" width="100%" border="0">
                <tr>
                    <td class="chartPushButton">
                        <div class="chartPushButton" id="ViewerMouseUsage1_Scroll" title="Pointer">
                            <IMG src="images/pointer.gif" align="absMiddle" width="16" height="16">  Pointer
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="chartPushButton">
                        <!--<div class="chartPushButton" id="ViewerMouseUsage1_ZoomIn" title="Zoom In">
                            <IMG src="images/zoomInIcon.gif" align="absMiddle" width="16" height="16">  Zoom In
                        </div>-->
                    </td>
                </tr>
                <tr>
                    <td class="chartPushButton">
                       <!-- <div class="chartPushButton" id="ViewerMouseUsage1_ZoomOut" title="Zoom Out">
                            <IMG src="images/zoomOutIcon.gif" align="absMiddle" width="16" height="16">  Zoom Out
                        </div>-->
                    </td>
                </tr>
            </table>
            <script>
            // Connect the mouse usage buttons to the Javascript ChartViewer
            function connectViewerMouseUsage(controlId, viewer)
            {
                // A cross browser utility to get the object by id.
                function getObj(id) { return document.getElementById ? document.getElementById(id) : document.all[id]; }

                // Set the button styles (colors) based on the current mouse usage mode of the Javascript ChartViewer
                function syncButtons()
                {
                    getObj(controlId + "_Scroll").className = (viewer.getMouseUsage() == JsChartViewer.Scroll) ?
                        "chartPushButtonSelected" : "chartPushButton";
                    //getObj(controlId + "_ZoomIn").className = (viewer.getMouseUsage() == JsChartViewer.ZoomIn) ?
                     //   "chartPushButtonSelected" : "chartPushButton";
                    //getObj(controlId + "_ZoomOut").className = (viewer.getMouseUsage() == JsChartViewer.ZoomOut) ?
                      //  "chartPushButtonSelected" : "chartPushButton";
                }
                syncButtons();

                // Run syncButtons whenever the Javascript ChartViewer is updated
                viewer.attachHandler("PostUpdate", syncButtons);

                // Set the Javascript ChartViewer mouse usage mode if a button is clicked.
                getObj(controlId + "_Scroll").onclick = function() { viewer.setMouseUsage(JsChartViewer.Scroll); syncButtons(); }
                //getObj(controlId + "_ZoomIn").onclick = function() { viewer.setMouseUsage(JsChartViewer.ZoomIn); syncButtons(); }
                //getObj(controlId + "_ZoomOut").onclick = function() { viewer.setMouseUsage(JsChartViewer.ZoomOut); syncButtons(); }
            }
            </script>
            <div style="font-size:9pt; margin:15px 5px 0px; font-family:verdana"><b>Zoom Mode</b></div>
            <!-- The following table is to create 2 cells for 2 buttons. The buttons are used to control
                 the zoom/scroll directions of the Javascript ChartViewer. -->
            <table id="ViewerZoomControl1" cellSpacing="0" cellPadding="0" width="100%" border="0">
                <tr>
                    <td class="chartPushButton" style="border-bottom: #000000 1px solid; border-top: #000000 1px solid;">
                        <div class="chartPushButton" id="ViewerZoomControl1_Xmode" title="X-Axis scrollable / Y-Axis auto-scaled">
                            <img src="images/xrange.gif" align="absMiddle" width="16" height="16">  X Scroll / Y Auto
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="chartPushButton" style="border-bottom: #000000 1px solid;">
                        <!--<div class="chartPushButton" id="ViewerZoomControl1_XYmode" title="X-Axis and Y-Axis zoomable" height="0px">
                            <img src="images/xyrange.gif" align="absMiddle" width="16" height="16">  XY Zoom
                        </div>-->
                    </td>
                </tr>
            </table>
            <script>
            // Connect the zoom/scroll direction buttons to the Javascript ChartViewer
            function connectViewerZoomControl(controlId, viewer)
            {
                // A cross browser utility to get the object by id.
                function getObj(id) { return document.getElementById ? document.getElementById(id) : document.all[id]; }

                // Set the button styles (colors) based on current zoom/scroll direction settings of the Javascript ChartViewer
                function syncButtons()
                {
                    getObj(controlId + "_Xmode").className = (viewer.getZoomDirection() == JsChartViewer.Horizontal) ?
                        "chartPushButtonSelected" : "chartPushButton";
                    //getObj(controlId + "_XYmode").className = (viewer.getZoomDirection() == JsChartViewer.HorizontalVertical) ?
                        //"chartPushButtonSelected" : "chartPushButton";
                }
                syncButtons();

                // Run syncButtons whenever the Javascript ChartViewer is updated
                viewer.attachHandler("PostUpdate", syncButtons);

                // Set the Javascript ChartViewer zoom/scroll direction if a button is clicked.
                function setViewerDirection(d)
                {
                    viewer.setScrollDirection(d);
                    viewer.setZoomDirection(d);
                    syncButtons();
                }
                getObj(controlId + "_Xmode").onclick = function() { setViewerDirection(JsChartViewer.Horizontal); }
                //getObj(controlId + "_XYmode").onclick = function() { setViewerDirection(JsChartViewer.HorizontalVertical); }
            }
            </script>
            <div style="font-size:9pt; margin:15px 5px 0px; font-family:Verdana">
                <b>Start Time</b><br>
                <table cellSpacing="0" cellPadding="0" border="0">
                    <tr>
                        <td style="font-size:8pt; font-family:Arial">Year</td>
                        <td style="font-size:8pt; font-family:Arial">Mon</td>
                        <td style="font-size:8pt; font-family:Arial">Day</td>
                    </tr>
                    <tr>
                        <td><select id="StartYear" name="StartYear" style="width:60">
                            <?php echo $startYearSelectOptions?>
                        </select></td>
                        <td><select id="StartMonth" name="StartMonth" style="width:40">
                            <?php echo $startMonthSelectOptions?>
                        </select></td>
                        <td><select id="StartDay" name="StartDay" style="width:40">
                            <?php echo $startDaySelectOptions?>
                        </select></td>
                    </tr>
                </table>
            </div>
            <div style="font-size:9pt; margin:15px 5px 0px; font-family:Verdana">
                <b>End Time</b><br>
                <table cellSpacing="0" cellPadding="0" border="0">
                    <tr>
                        <td style="font-size:8pt; font-family:Arial">Year</td>
                        <td style="font-size:8pt; font-family:Arial">Mon</td>
                        <td style="font-size:8pt; font-family:Arial">Day</td>
                    </tr>
                    <tr>
                        <td><select id="EndYear" name="EndYear" style="width:60">
                            <?php echo $endYearSelectOptions?>
                        </select></td>
                        <td><select id="EndMonth" name="EndMonth" style="width:40">
                            <?php echo $endMonthSelectOptions?>
                        </select></td>
                        <td><select id="EndDay" name="EndDay" style="width:40">
                            <?php echo $endDaySelectOptions?>
                        </select></td>
                    </tr>
                </table>
            </div>
            <script>
            // A utility to validate the day of month for the start date / end date HTML controls.
            // It sets the day of month select so that it only shows the legal range.
            function validateYMDControls(yearObj, monthObj, dayObj)
            {
                // Get the number of days in a month
                var noOfDays = [31, (parseInt(yearObj.value) % 4 == 0) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31]
                    [monthObj.selectedIndex];

                // Ensure the selected day of month is not bigger than the days in month
                dayObj.selectedIndex = Math.min(noOfDays - 1, dayObj.selectedIndex);

                // Extend/Shrink the day of month select box to ensure it covers the legal day range
                for (var i = dayObj.options.length; i < noOfDays; ++i)
                    dayObj.options[i] = new Option(i + 1, i + 1);
                for (var j = dayObj.options.length; j > noOfDays; --j)
                    dayObj.remove(j - 1);
            }
            // Initialize the HTML select controls for selecting dates
            function initYMDControls(yearId, monthId, dayId)
            {
                // A cross browser utility to get the object by id.
                var getObj = function(id) { return document.getElementById ? document.getElementById(id) : document.all[id]; }

                // Connect the onchange event to validateYMDControls
                getObj(yearId).onchange = getObj(yearId).validate = getObj(monthId).onchange = getObj(monthId).validate =
                    function() { validateYMDControls(getObj(yearId), getObj(monthId), getObj(dayId)); };

                // Validate once immediately
                getObj(yearId).validate();
            }
            // Connnect the start date / end date HTML select controls
            initYMDControls("StartYear", "StartMonth", "StartDay");
            initYMDControls("EndYear", "EndMonth", "EndDay");
            </script>
            <div style="margin-top:20px; font-family:Verdana; font-size:9pt; text-align:center">
                <input type="submit" id="SubmitButton" name="SubmitButton" value="Update Chart"></input>
            </div>
        </td>
        <td>
            <div style="font-weight:bold; font-size:20pt; margin:5px 0px 0px 5px; font-family:Arial">
                Software Engineering Project Status
            </div>
            <hr color="#000088">
            <div style="padding:0px 5px 0px 10px">
                <!-- ****** Here is the chart image ****** -->
                <?php echo $viewer->renderHTML()?>
            </div>
        </td>
    </tr>
</table>
</form>
</div>
<?
show_footer();
?>