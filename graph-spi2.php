<?
$HACK_CHECK=1; Include("config/global.inc.php");
debug(10,"Loading File: graph-cpi.php"); /* stop light chart */
checkPermissions($SESSION_TIMEOUT); // if not logged in, or session has timed out...

$strProject=postedData($_FORM['txt_project']);
//$strSmall=postedData($_FORM['txt_small']);
$strSmall = 0;

//SPI = Earned Value / Planned Value

//Get project information
$getProjectInfo = "SELECT project_name FROM pev__project WHERE project_id=$strProject";
$result = dbquery($getProjectInfo);
$row = mysql_fetch_array($result);
$project_name = $row['project_name'];

//Get current earned value
$getEarnedValue = "SELECT SUM(wt.planned_hours*(wt.percent_complete/100)) AS ev FROM pev__wbs_to_task AS wt";
$getEarnedValue.= " LEFT JOIN pev__wbs AS w ON wt.wbs_id=w.wbs_id WHERE project_id=$strProject AND wt.rollup!=1 GROUP BY project_id";

$result = dbquery($getEarnedValue);
$row = mysql_fetch_array($result);
$earned_value = $row['ev'];

//Get current planned value
$getPlannedValue = "SELECT SUM(wt.planned_hours) AS pv FROM pev__wbs_to_task AS wt";
$getPlannedValue.= " LEFT JOIN pev__wbs AS w ON wt.wbs_id=w.wbs_id WHERE project_id=$strProject AND wt.rollup!=1 AND wt.due_date < UNIX_TIMESTAMP() GROUP BY project_id";

$result = dbquery($getPlannedValue);
$row = mysql_fetch_array($result);
$planned_value = $row['pv'];

//Get current actual cost
$getActualCost = "SELECT SUM(wt.actual_hours) AS ac FROM pev__wbs_to_task AS wt";
$getActualCost.= " LEFT JOIN pev__wbs AS w ON wt.wbs_id=w.wbs_id WHERE project_id=$strProject AND wt.rollup!=1 GROUP BY project_id";

$result = dbquery($getActualCost);
$row = mysql_fetch_array($result);
$actual_cost = $row['ac'];

if($planned_value == 0 && $earned_value == 0){
   //Handle div0 warning
   $SPIvalue = 1;
}elseif($planned_value == 0 && $earned_value > 0){
   //Don't want infinite number or div0
   $SPIvalue = 2;
}else{
   $SPIvalue = $earned_value/$planned_value;
}




require_once($CHARTDIRECTOR);
if($strSmall)
{
  $chartH=300;
  $chartW=350;
  $plotH=150;
  $plotW=250;
}
else
{
  $chartH=600;
  $chartW=650;
  $plotH=450;
  $plotW=550;
}

# The value to display on the meter
//$value = 6.5; 
# Create an AugularMeter object of size 200 x 100 pixels with rounded corners 
$m = new AngularMeter(200, 100); 
$m->setRoundedFrame(); 
# Use a solid light purple (EEBBEE) background color
if($SPIvalue>= 0.9 && $SPIvalue<=1.3)$bgC=0xDDFFDD;
else if($SPIvalue>=0.5 && $SPIvalue<0.9 || $SPIvalue>1.3 && $SPIvalue<=1.7)$bgC=0xFFFFDD;
else $bgC=0xFFDDDD;
$m->setBackground($bgC, 0x000000, -2);
# Set the meter center at (100, 235), with radius 210 pixels, and span from -24 to 
# +24 degress 
$m->setMeter(100, 235, 210, -24, 24); 
# Meter scale is 0 - 100, with a tick every 1 unit 
$m->setScale(0, 2, 1); 
# Set 0 - 6 as green (99ff99) zone, 6 - 8 as yellow (ffff00) zone, and 8 - 10 as red 
# (ff3333) zone 
$m->addZone(0, 0.5, 0xff3333, 0x808080);  // red
$m->addZone(0.5, 0.9, 0xffff00, 0x808080);  // yellow
$m->addZone(0.9, 1.3, 0x99ff99, 0x808080);  // green
$m->addZone(1.3, 1.7, 0xffff00, 0x808080);  // yellow
$m->addZone(1.7, 2, 0xff3333, 0x808080);  // red

$SPIvalue = number_format($SPIvalue, 2);
# Add a title at the bottom of the meter using 10 pts Arial Bold font 
$m->addTitle2(Top, "SPI: $SPIvalue", "arialbd.ttf", 10);
$m->addTitle2(Bottom, "$project_name\n", "arialbd.ttf", 10); 
# Add a semi-transparent black (80000000) pointer at the specified value 
$m->addPointer($SPIvalue, 0x80000000); 
//exit;
# output the chart 
header("Content-type: image/png"); 
print($m->makeChart2(PNG));
?>

?>
