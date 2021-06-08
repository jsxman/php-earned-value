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
debug(10,"Loading File: graph-cpi.php"); /* stop light chart */
checkPermissions($SESSION_TIMEOUT); // if not logged in, or session has timed out...

$strProject=postedData($_FORM['txt_project']);
//$strSmall=postedData($_FORM['txt_small']);
$strSmall = 0;

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
$strSQL1.= " WHERE W.project_id='$strProject'";
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
 $bcws+=$dataBCWSHours[$key];
 $eh+=$dataEarnedHours[$key];
 $ac+=$dataActualHours[$key];
 $pv+=$PlannedHours[$key];
 debug(10,"WBS($key) BCWS($bcws|".$dataBCWSHours[$key]."), PV($pv|".$PlannedHours[$key]."), AC($ac|".$dataActualHours[$key]."), EH($eh|".$dataEarnedHours[$key].")");
}
$pc=$eh/$pv;
$ev=$pv*$pc;
$CPIvalue=$ev/$ac;
debug(10,"CPI($CPIvalue) EV($ev) PC($pc)");
//$SPIvalue=$ev/$pv;
$SPIvalue=$ev/$bcws;
debug(10,"SPI($SPIvalue)");
if($SPIvalue>2)$SPIvalue=2;

//exit;
//////////////////////////////////////////////////////
//////////////////////////////////////////////////////
//////////////////////////////////////////////////////
//////////////////////////////////////////////////////
//////////////////////////////////////////////////////
//////////////////////////////////////////////////////

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
# Add a title at the bottom of the meter using 10 pts Arial Bold font 
$m->addTitle2(Bottom, "$projectName - SPI\n", "arialbd.ttf", 10); 
# Add a semi-transparent black (80000000) pointer at the specified value 
$m->addPointer($SPIvalue, 0x80000000); 
//exit;
# output the chart 
header("Content-type: image/png"); 
print($m->makeChart2(PNG));
?>
