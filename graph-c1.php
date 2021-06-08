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
debug(10,"Loading File: graph-c1.php");
checkPermissions($SESSION_TIMEOUT); // if not logged in, or session has timed out...

$strProject=postedData($_FORM['txt_project']);
$strWBS=postedData($_FORM['txt_wbs']);
$strAccum=postedData($_FORM['txt_accum']);
$strSmall=postedData($_FORM['txt_small']);


/* figure out what the total hours planned and actual are - take the max */
/* also read past the last date in the history (in the wbs-to-task), and */
/* compute the planned completion of the rest of the program */
$strSQL0 = "SELECT * FROM $TABLE_WBS AS W";
$strSQL0.= " LEFT JOIN $TABLE_WBS_HISTORY AS H ON H.wbs_id=W.wbs_id";
$strSQL0.= " LEFT JOIN $TABLE_PROJECT AS P ON P.project_id=W.project_id";
$strSQL0.= " WHERE W.project_id='$strProject' AND W.wbs_id='$strWBS'";
$strSQL0.= " ORDER BY H.wh_date DESC";
$strSQL0.= " LIMIT 1";
$result0 = dbquery($strSQL0);
debug(15,$strSQL0);
$row0 = mysql_fetch_array($result0);
$MAX_HOURS=$row0['total_phours'];
$wbsName=$row0['wbs_name'];
$projName=$row0['project_name'];
debug(15,"TPH(".$row0['total_phours'].")");
debug(15,"TAH(".$row0['total_ahours'].")");
$t=$row0['total_ahours'];
if($t>$MAX_HOURS)$MAX_HOURS=$t;
debug(10,"MAX HOURS(spot1)=$MAX_HOURS");

$FDATE=$row0['wh_date'];
$strSQL1 = "SELECT * FROM $TABLE_WBS_TO_TASK";
$strSQL1.= " WHERE wbs_id='$strWBS' AND due_date>'$FDATE' AND rollup = '0'";
$result1 = dbquery($strSQL1);
while($row1 = mysql_fetch_array($result1))
{
  $t=$row1['planned_hours'];
  debug(10,"phours read=$t");
/*
  if($t>$MAX_HOURS)$MAX_HOURS=$t;
*/
  $MAX_HOURS+=$t;
}
debug(10,"MAX HOURS(spot2)=$MAX_HOURS");



$strSQL0 = "SELECT * FROM $TABLE_WBS AS W";
$strSQL0.= " LEFT JOIN $TABLE_WBS_HISTORY AS H ON H.wbs_id=W.wbs_id";
$strSQL0.= " WHERE W.project_id='$strProject' AND W.wbs_id='$strWBS'";
$strSQL0.= " ORDER BY H.wh_date";

$result0 = dbquery($strSQL0);
$count=-1;
$wbsA=Array();
$wbsC0=Array(); /* for the color */
$wbsC1=Array(); /* for the color */
$wbsC2=Array(); /* for the color */
$data0=Array();
$data1=Array();
$data2=Array();
$lastDate="";
$lastph=0; /* used for non-accum */
$lastah=0; /* used for non-accum */
$lastpc=0; /* used for non-accum */
while($row0 = mysql_fetch_array($result0))
{
  $tdate=date_read($row0['wh_date']);
  $tph=$row0['total_phours'];
  $tah=$row0['total_ahours'];
  $tpc=$row0['percent_complete']*$MAX_HOURS/100;
  $wbs=$row0['wbs_id'];
  $wbsA[$wbs]=$row0['wbs_name'];

  if($tph && $tah && $tpc)	/*If the current history record for this WBS is zero, disregard entry*/
  {
  	if($lastDate<>$tdate)
  	{
    	$lastDate=$tdate;
    	$count++;
  	}
  }

  if($tph && $tah && $tpc)
  {

  if(!isset($wbsC0[$wbs]))
  {
    $nRED  =rand(100,200); $nGREEN=rand(0,100); $nBLUE =rand(0,100);
    $c0=$nRED*256*256+$nGREEN*256+$nBLUE;
    $nRED  =rand(0,100); $nGREEN=rand(100,200); $nBLUE =rand(0,100);
    $c1=$nRED*256*256+$nGREEN*256+$nBLUE;
    $nRED  =rand(0,100); $nGREEN=rand(0,100); $nBLUE =rand(100,200);
    $c2=$nRED*256*256+$nGREEN*256+$nBLUE;

    debug(10,"Colors [$wbs] ($c0,$c1,$c2)");
    $wbsC0[$wbs]=$c0;
    $wbsC1[$wbs]=$c1;
    $wbsC2[$wbs]=$c2;
  }

  $labels[$count]=$tdate;
  if(!isset($data0[$wbs])) $data0[$wbs]=Array();
  if(!isset($data1[$wbs])) $data1[$wbs]=Array();
  if(!isset($data2[$wbs])) $data2[$wbs]=Array();
  if($strAccum)
  {
    $data0[$wbs][$count]=$tph;
    $data1[$wbs][$count]=$tah;
    $data2[$wbs][$count]=$tpc;
    debug(10,"Data Values wbs[$wbs] count[$count] ($tph, $tah, $tpc)");
  }
  else
  {
    $x0=$tph-$lastph;
    $x1=$tah-$lastah;
    $x2=$tpc-$lastpc;
    $data0[$wbs][$count]=$x0; unset($x0);
    $data1[$wbs][$count]=$x1; unset($x1);
    $data2[$wbs][$count]=$x2; unset($x2);
    $lastph=$tph;
    $lastah=$tah;
    $lastpc=$tpc;
    debug(10,"Data Values wbs[$wbs] count[$count] ($tph, $tah, $tpc)");
  }

}
}


/* now go and add in the planned hours/dates for all future due_dates */
$strSQL1 = "SELECT T.due_date, T.planned_hours, T.wbs_id, W.wbs_name FROM $TABLE_WBS_TO_TASK AS T";
$strSQL1.= " LEFT JOIN $TABLE_WBS AS W ON W.wbs_id = T.wbs_id";
$strSQL1.= " WHERE T.wbs_id='$strWBS' AND T.due_date>'$FDATE' AND T.rollup = '0'";
$strSQL1.= " ORDER BY T.due_date ASC";
$result1 = dbquery($strSQL1);
while($row1 = mysql_fetch_array($result1))
{
  $tdate=date_read($row1['due_date']);
  $ph=$row1['planned_hours'];
  $wbs=$row1['wbs_id'];
  $wbsA[$wbs]=$row1['wbs_name'];
  debug(10,"read data: WBS($wbs) WBS-NAME(".$wbsA[$wbs].") PH($ph) date($tdate)");

  if($lastDate<>$tdate)
  {
    $lastDate=$tdate;
    $count++;
  }
  $labels[$count]=$tdate;
  debug(10,"added label($count) = $tdate");
  if(!isset($data0[$wbs])) $data0[$wbs]=Array();
  if($strAccum)
  {
    $data0[$wbs][$count]=$data0[$wbs][$count-1]+$ph;
  }
  else
  {
    $data0[$wbs][$count]=$ph;
  }
}


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
  $plotH=200;
  $plotW=250;
}
else
{
  $chartH=600;
  $chartW=650;
  $plotH=500;
  $plotW=550;
}

if($strAccum)
{
  $chartBG=0xcceecc;
  $headerBG=0xccffcc;
}
else
{
  $chartBG=0xeeeeff;
  $headerBG=0xccccff;
}

/* Create an XYChart object of size 600 x 300 pixels, with a light blue (EEEEFF)
 background, black border, 1 pxiel 3D border effect and rounded corners*/
$c = new XYChart($chartW, $chartH+10, $chartBG, 0x000000, 1);
//$c->setRoundedFrame();

/* Set the plotarea at (55, 58) and of size 520 x 195 pixels, with white background.
 Turn on both horizontal and vertical grid lines with light grey color (0xcccccc)*/

if (sizeof($labels) <= 1){

}
else { 

$legendObj = $c->addLegend(20, 35, false, "arialbd.ttf", 9);
$legendObj->setBackground(Transparent);
}
$c->setPlotArea(50, 70, $plotW, $plotH, 0xffffff, -1, -1, 0xcccccc, 0xcccccc);
/*Add a legend box at (50, 30) (top of the chart) with horizontal layout. Use 9 pts
 Arial Bold font. Set the background and border color to Transparent.*/


/* Add a title box to the chart using 15 pts Times Bold Italic font, on a light blue
(CCCCFF) background with glass effect. white (0xffffff) on a dark red (0x800000)
background, with a 1 pixel 3D border.*/
$x=$strAccum?" Accum":"";

if (sizeof($labels) <= 1){
if ($strSmall){
$textBoxObj = $c->addText(30,30,"<*color=FF0000*>REQUIRES ADDITIONAL PROJECT HISTORY",Arial,12);
$textBoxObj = $c->addText(55, 70, "<*img=/srv/www/htdocs/paev4/paevnd.png*>");
}
else {
$textBoxObj = $c->addText(30,30,"<*color=FF0000*>REQUIRES ADDITIONAL PROJECT HISTORY",Arial,20);
$textBoxObj = $c->addText(200, 200, "<*img=/srv/www/htdocs/paev4/paevnd.png*>");
}
}

$textBoxObj = $c->addTitle("Earned Value$x: $projName - $wbsName", "timesbi.ttf", 12);



unset($x);
$textBoxObj->setBackground($headerBG, 0x000000, glassEffect());

/* Add a title to the y axis*/
$c->yAxis->setTitle("Hours");

/*Set the labels on the x axis.*/
$c->xAxis->setLabels($labels);



/* Display 1 out of 3 labels on the x-axis.
 now it will make 5 dates*/
$c->xAxis->setLabelStep($count/5);

/* Add a title to the x axis*/
$c->xAxis->setTitle("Date");

/* Add a line layer to the chart*/
$layer = $c->addLineLayer2();

/* Set the default line width to 2 pixels */
$layer->setLineWidth(2);

/*Add the three data sets to the line layer. For demo purpose, we use a dash line
color for the last line */



foreach($wbsA as $key => $val)
{
  $dataSetObj=$layer->addDataSet($data0[$key], $wbsC0[$key], "Planned");
  if(!$strSmall)$dataSetObj->setDataSymbol(CircleSymbol, 9);
  $dataSetObj=$layer->addDataSet($data1[$key], $wbsC1[$key], "Actual");
  if(!$strSmall)$dataSetObj->setDataSymbol(DiamondSymbol, 11);
  $dataSetObj=$layer->addDataSet($data2[$key], $c->dashLineColor($wbsC2[$key], DashLine), "Earned");
  if(!$strSmall)$dataSetObj->setDataSymbol(Cross2Shape(), 11);
  // This next line would add in values on the data points
  $dataSetObj=$layer->setDataLabelFormat("{value|0}");
}



if($Debug) exit;
//exit;

/*output the chart*/
header("Content-type: image/png");
print($c->makeChart2(PNG));
?>
