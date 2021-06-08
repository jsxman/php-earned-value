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
debug(10,"Loading File: graph-bulleye.php"); /* stop light chart */
checkPermissions($SESSION_TIMEOUT); // if not logged in, or session has timed out...

//$strProject=postedData($_FORM['txt_project']);
$strSmall=postedData($_FORM['txt_small']);
if(isset($_FORM['txt_active'])){
   if(postedData($_FORM['txt_active']) == "week"){
      $timeframe = date("U") - (3600*24*14);
      $title = "All Program Status (Past 2 Weeks)";
   }else if(postedData($_FORM['txt_active']) == "month"){
      $timeframe = date("U") - (3600*24*31);
      $title = "All Program Status (Past Month)";
   }else{
      $timeframe = 0;
      $title = "All Program Status";
   }
}else{
      $timeframe = 0;
      $title = "All Program Status";
}

$dataY0=Array();
$dataX0=Array();
$labels=Array();
$countX=0;
$maxX=1;
$maxY=1;
$strSQLX ="SELECT p.project_id FROM $TABLE_PROJECT AS p";
$strSQLX.=" LEFT JOIN $TABLE_WBS AS w ON p.project_id=w.project_id";
$strSQLX.=" LEFT JOIN $TABLE_WBS_HISTORY AS h ON h.wbs_id=w.wbs_id";
$strSQLX.=" WHERE wh_date > $timeframe";
$strSQLX.="  GROUP BY project_id ORDER BY project_name";
$resultX = dbquery($strSQLX);
while($rowX = mysql_fetch_array($resultX))
{
  $strProject=$rowX['project_id'];

/* find total planned hours for all WBS for this project */
$PlannedHours=Array();
$strSQL0 = "SELECT P.project_name,W.wbs_id,W.wbs_name,sum(T.planned_hours) as tph FROM $TABLE_WBS AS W";
$strSQL0.= " LEFT JOIN $TABLE_WBS_TO_TASK AS T ON T.wbs_id=W.wbs_id";
$strSQL0.= " LEFT JOIN $TABLE_PROJECT AS P ON P.project_id=W.project_id";
$strSQL0.= " WHERE W.project_id='$strProject'";
$strSQL0.= " GROUP BY W.wbs_id";
$strSQL0.= " ORDER BY W.wbs_order";
$result0 = dbquery($strSQL0);
$wbsA=Array();
while($row0 = mysql_fetch_array($result0))
{
  $projectName=$row0['project_name'];
  $wbsA[$row0['wbs_id']]=$row0['wbs_name'];
  //$PlannedHours[$row0['wbs_id']]=$row0['tph'];
  $wbsID=$row0['wbs_id'];

  /* get the total plan hours in the WBS - this is PV */
  $strSQL4 = "SELECT sum(planned_hours) as total_plan FROM $TABLE_WBS_TO_TASK WHERE wbs_id='$wbsID' GROUP BY wbs_id";
  //echo "$strSQL4";
  $result4 = dbquery($strSQL4);
  $row4 = mysql_fetch_array($result4);
  $total_plan=$row4['total_plan'];
  $PlannedHours[$wbsID]=$total_plan;

  debug(10,"Planned hours for WBS ($wbsID) is ".$total_plan);
}


/* get the WBS-HISTORY for all WBS in this PROJECT */
$strSQL1 = "SELECT H.wh_date,H.percent_complete,H.total_phours,H.total_ahours,H.wbs_id,W.wbs_name FROM $TABLE_WBS AS W";
$strSQL1.= " LEFT JOIN $TABLE_WBS_HISTORY AS H ON H.wbs_id=W.wbs_id";
$strSQL1.= " WHERE W.project_id='$strProject' AND wh_date>0";
$strSQL1.= " ORDER BY H.wh_date ASC";
$result1 = dbquery($strSQL1);
$last_date='';
$counter=-1;
$wbsC0=Array();
$dataEarnedHours=Array();
$dataActualHours=Array();
$dataBCWSHours=Array();
while($row1 = mysql_fetch_array($result1))
{
  if($last_date<>$row1['wh_date'])
  {
    $last_date=$row1['wh_date'];
    $counter++;
  }
  //$date=date_read($row1['wh_date']);
  $wbsName=$row1['wbs_name'];
  $wbs=$row1['wbs_id'];
  //$pv=$PlannedHours[$wbs];
  $pc=$row1['percent_complete']/100;
  //$ac=$row1['total_ahours'];
  //$ev=$pv*$pc;
  //$cpi=$ev/$ac; debug(10, "CPI($cpi)=EV($ev)/AC($ac)");
  //$spi=$ev/$pv; debug(10, "SPI($spi)=EV($ev)/PC($pv)");
  //$tmp1=$pc*$row1['total_phours'];
  $tmp1=$pc*$PlannedHours[$wbs];
  $dataBCWSHours[$wbs]=$row1['total_phours'];
  $dataEarnedHours[$wbs]=$tmp1;
  $dataActualHours[$wbs]=$row1['total_ahours'];
  debug(10,"READ: WBS($wbs) PC($pc), PH(".$dataEarnedHours[$wbs]."|".$PlannedHours[$wbs]."|$tmp1), AH(".$dataActualHours[$wbs].")");
}

$eh=0;$pv=0;$ac=0;$bcws=0;
foreach ($PlannedHours as $key => $value)
{
   if(isset($dataBCWSHours[$key])){
      $bcws+=$dataBCWSHours[$key];
      $eh+=$dataEarnedHours[$key];
      $ac+=$dataActualHours[$key];
      $pv+=$PlannedHours[$key];
   }
 //debug(10,"WBS($key) BCWS($bcws|".$dataBCWSHours[$key]."), PV($pv|".$PlannedHours[$key]."), AC($ac|".$dataActualHours[$key]."), EH($eh|".$dataEarnedHours[$key].")");
}

   if($pv > 0){
      $pc=$eh/$pv;
   }else{
      $pc = 0;
   }
   $ev=$pv*$pc;
   if($ac > 0){
      $CPIvalue=$ev/$ac;
   }else{
      $CPIvalue=0;
   }
   debug(10,"CPI($CPIvalue) EV($ev) PC($pc)");
   //$SPIvalue=$ev/$pv;
   if($bcws > 0){
      $SPIvalue=$ev/$bcws;
   }else{
      $SPIvalue = 0;
   }
   debug(10,"SPI($SPIvalue)");
   if($SPIvalue>2)$SPIvalue=2;

   /*The two commented out lines represent cost/sched variance*/
   //$CV=$ev-$ac;
   //$SV=$ev-$bcws;

   /*These next few lines calculate cost/sched performance*/
   if($ac == 0){
      $CV = 0;
   }else{
      $CV=$ev/$ac;
   }

   if($bcws == 0){
      $SV = 0;
   }else{
      $SV=$ev/$bcws;
   }
   debug(10,"Variances:: COST($CV) SCHEDULE($SV)");

   $dataY0[$countX]=$CV;
   $dataX0[$countX]=$SV;
   $labels[$countX]=$projectName;
   $countX++;

   if($maxY<abs($CV))$maxY=abs($CV);
   //if($maxX<abs($SV))$maxX=abs($SV);
   if($maxX<abs($SV))$maxX=abs($SV);


} /* end of the - for all projects */
//$t=round($maxX/100,0);
//$maxX=$t*100+100;

//exit;
//////////////////////////////////////////////////////
//////////////////////////////////////////////////////
//////////////////////////////////////////////////////
//////////////////////////////////////////////////////
//////////////////////////////////////////////////////
//////////////////////////////////////////////////////

require_once("/usr/share/php5/ChartDirector/lib/phpchartdir.php");
if($strSmall)
{
  $chartH=300;
  $chartW=390;
  $plotH=150;
  $plotW=250;
  $yAxisTitle = "CP";
  $xAxisTitle = "SP";
}
else
{
  $chartH=600;
  $chartW=800;
  $plotH=450;
  $plotW=550;
  $yAxisTitle = "COST PERFORMANCE";
  $xAxisTitle = "SCHEDULE PERFORMANCE";
}

# Create a XYChart object of size 450 x 420 pixels 
$c = new XYChart($chartW, $chartH); 
# Set the plotarea at (55, 65) and of size 350 x 300 pixels, with a light grey border 
# (0xc0c0c0). Turn on both horizontal and vertical grid lines with light grey color 
# (0xc0c0c0) 
$c->setPlotArea(65, 70, $plotW, $plotH, -1, -1, 0xc0c0c0, 0xc0c0c0, -1); 
# Add a legend box at (50, 30) (top of the chart) with horizontal layout. Use 12 pts 
# Times Bold Italic font. Set the background and border color to Transparent. 
if(!$strSmall){
   $legendObj = $c->addLegend(630, 30, true, "timesbi.ttf", 10); 
   $legendObj->setBackground(Transparent); 
}
# Add a title to the chart using 18 pts Times Bold Itatic font. 
$c->addTitle("$title", "timesbi.ttf", 16); 
# Add a title to the y axis using 12 pts Arial Bold Italic font 
$c->yAxis->setTitle("$yAxisTitle", "arialbi.ttf", 10); 
# Add a title to the x axis using 12 pts Arial Bold Italic font 
$c->xAxis->setTitle("$xAxisTitle", "arialbi.ttf", 10); 
# Set the axes line width to 3 pixels 
$c->xAxis->setWidth(3); 
//$c->xAxis()->setLinearScale(-$maxX, $maxX, round($maxX/5,0));
$c->xAxis->addMark(1, $c->dashLineColor(0xff0000, DashLine));
$c->yAxis->setWidth(3); 
//$c->yAxis()->setLinearScale(-$maxY, $maxY, round($maxY/5,0));
$c->yAxis->addMark(1, $c->dashLineColor(0xff0000, DashLine));
# Add an orange (0xff9933) scatter chart layer, using 13 pixel diamonds as symbols 
//$layer=$c->addScatterLayer($dataX0, $dataY0, "Project CV/SV", DiamondSymbol, 13, 0xff9933, 0xff3333); 
# Add labels to the chart as an extra field 
//$layer->addExtraField($labels); 
# Set the data label format to display the extra field 
//$layer->setDataLabelFormat("{field0}"); 
# Use 8pts Arial Bold to display the labels 
//$textbox = $layer->setDataLabelStyle("arialbd.ttf", 8); 
# Set the background to purple with a 1 pixel 3D border 
//$textbox->setBackground(0xcc99ff, Transparent, 1); 
# Put the text box 4 pixels to the right of the data point 
//$textbox->setAlignment(Left); 
//$textbox->setPos(4, 0); 
//$c->xAxis->addZone(0, 0.75, 0xaaff0000);
//$c->xAxis->addZone(0.75, 1, 0xaaffff00);
//$c->xAxis->addZone(1, 10, 0xaa00ffff);
//$c->yAxis->addZone(0, 0.75, 0xaaff0000);
//$c->yAxis->addZone(0.75, 1, 0xaa00ffff);
//$c->yAxis->addZone(1, 10, 0xaa00ffff);

//Set points
for($i=0; $i<count($dataX0); ++$i){
   $scatterLayerObj = $c->addScatterLayer(array($dataX0[$i]), array($dataY0[$i]), $labels[$i]);
   $dataSetObj = $scatterLayerObj->getDataSet(0);
   $symbol = rand(1,7);  //Get a random integer that represents a ChartDirector shape
   $fillColor = rand(0,16777215);
   $dataSetObj->setDataSymbol($symbol, 10, $fillColor, 0x000000, 1);
}

# output the chart
header("Content-type: image/png");
print($c->makeChart2(PNG)); 
?>
