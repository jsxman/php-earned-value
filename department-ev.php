<?
$HACK_CHECK=1; Include("config/global.inc.php");

checkPermissions($SESSION_TIMEOUT); // if not logged in, or session has timed out...
require_once("/usr/share/php5/ChartDirector/lib/phpchartdir.php");

$strSmall=postedData($_FORM['txt_small']);


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
while($row = mysql_fetch_array($value_result)){

   if($start_date > $row['wh_date'] || $start_date == 0){
      //get start date
      $start_date = $row['wh_date'];
   }

   $actualCost = $row['total_ac'];
   $plannedValue = $row['total_pv'];
   $earnedValue = $row['total_ev'];
   $costVariance[] = $earnedValue - $actualCost;
   $xDates[] = chartTime2($row['wh_date']);

   //Set end date
   if($end_date < $row['wh_date'] || $end_date == 0){
      $end_date = $row['wh_date'];
   }
}

//Find Earned value - Get all tasks, group by EC date, percent complete * planned hours



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
   $legendObj = $c->addLegend(300, 30, false, "arialbd.ttf", 9);
   $legendObj->setBackground(Transparent);
}

# Add a title box to the chart using 15 pts Times Bold Italic font, on a light blue
# (CCCCFF) background with glass effect. white (0xffffff) on a dark red (0x800000)
# background, with a 1 pixel 3D border.
$textBoxObj = $c->addTitle("Software Engineering Dept Earned Value", "timesbi.ttf", 15);
$textBoxObj->setBackground(0xccccff, 0x000000, glassEffect());

# Add a title to the y axis
$c->yAxis->setTitle("Hours");

# Set the y-axis scale to be date scale with ticks every 7 days (1 week)
$c->xAxis->setDateScale(chartTime2($start_date), chartTime2($end_date), 86400 * 7);

# Add a red (ff0000) dash line to represent the current day
$c->xAxis->addMark(chartTime2(time()), $c->dashLineColor(0xff0000, DashLine));

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
$layer1 = $c->addLineLayer($costVariance, 0x0000cc, "Cost Variance");
$layer1->setXData($xDates);

//$layer2 = $c->addLineLayer($yDataAC, 0x00ff00, "Actual Cost");
//$layer2->setXData($xDates);

//$layer3 = $c->addLineLayer($earned_hrs, 0x000000, "Earned Value");
//$layer3->setXData($earned_date);

# Set the default line width to 2 pixels
$layer1->setLineWidth(2);
//$layer2->setLineWidth(2);
//$layer3->setLineWidth(2);

# output the chart
header("Content-type: image/png");
print($c->makeChart2(PNG));

?>