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
debug(10,"Loading File: graph-t1.php");
checkPermissions($SESSION_TIMEOUT); // if not logged in, or session has timed out...

$strProject=postedData($_FORM['txt_project']);
$strAccum=(isset($_FORM['txt_accum']))?postedData($_FORM['txt_accum']):0;
$strSmall=postedData($_FORM['txt_small']);

/* FIND THE MAX HOURS to use as a SCALE */
$strSQL0 = "SELECT";
$strSQL0.= " total_phours";
$strSQL0.= ", total_ahours";
$strSQL0.= ", project_name";
$strSQL0.= ", W.wbs_id";
$strSQL0.= " FROM $TABLE_WBS AS W";
$strSQL0.= " LEFT JOIN $TABLE_WBS_HISTORY AS H ON H.wbs_id=W.wbs_id";
$strSQL0.= " LEFT JOIN $TABLE_PROJECT AS P ON P.project_id=W.project_id";
$strSQL0.= " WHERE W.project_id='$strProject'";
$result0 = dbquery($strSQL0);
debug(10,$strSQL0);
$MAX_HOURS=Array();
while($row0 = mysql_fetch_array($result0))
{
  $wbs=$row0['wbs_id'];
  $tp=$row0['total_phours'];
  $ta=$row0['total_ahours'];
  $projName=$row0['project_name'];
  
  if(!isset($MAX_HOURS[$wbs]))$MAX_HOURS[$wbs]=0;
  if($MAX_HOURS[$wbs]<$tp)$MAX_HOURS[$wbs]=$tp;
  if($MAX_HOURS[$wbs]<$ta)$MAX_HOURS[$wbs]=$ta;
  debug(10,"TPH($tp)");
  debug(10,"TAH($ta)");
  debug(10,"MAX HOURS[$wbs]=".$MAX_HOURS[$wbs]);
}


$strSQL0 = "SELECT * FROM $TABLE_WBS AS W";
$strSQL0.= " LEFT JOIN $TABLE_WBS_HISTORY AS H ON H.wbs_id=W.wbs_id";
$strSQL0.= " WHERE W.project_id='$strProject'";
$strSQL0.= " ORDER BY H.wh_date";
$result0 = dbquery($strSQL0);
$count=-1;
$wbsA=Array();
$lastph=0; /* used for non-accum */
$lastah=0; /* used for non-accum */
$lastpc=0; /* used for non-accum */
$labels=Array();
while($row0 = mysql_fetch_array($result0))
{
  $tdate=date_read($row0['wh_date']);
  $tph=$row0['total_phours'];
  $tah=$row0['total_ahours'];
  $wbs=$row0['wbs_id'];
  $tpc=$row0['percent_complete']*$MAX_HOURS[$wbs]/100;
  //$wbsA[$wbs]=$row0['wbs_name'];

  if($tph && $tah && $tpc)
{

  if(!isset($wbsC0)) /* only do this once */
  {
    $nRED  =rand(100,200); $nGREEN=rand(0,100); $nBLUE =rand(0,100);
    $c0=$nRED*256*256+$nGREEN*256+$nBLUE;
    $nRED  =rand(0,100); $nGREEN=rand(100,200); $nBLUE =rand(0,100);
    $c1=$nRED*256*256+$nGREEN*256+$nBLUE;
    $nRED  =rand(0,100); $nGREEN=rand(0,100); $nBLUE =rand(100,200);
    $c2=$nRED*256*256+$nGREEN*256+$nBLUE;

    debug(10,"Colors ($c0,$c1,$c2)");
    $wbsC0=$c0;
    $wbsC1=$c1;
    $wbsC2=$c2;
  }

  if(!isset($revlabels[$tdate]))
  {
    $count=count($labels);
    $labels[$count]=$tdate; $revlabels[$tdate]=$count;
  }
  else
  {
    $count=$revlabels[$tdate];
  }
  if(!isset($data0)) $data0=Array();
  if(!isset($data1)) $data1=Array();
  if(!isset($data2)) $data2=Array();
    $x0=$tph;//-$lastph;
    $x1=$tah;//-$lastah;
    $x2=$tpc;//-$lastpc;
    if(!isset($data0[$count]))$data0[$count]=0;
    if(!isset($data1[$count]))$data1[$count]=0;
    if(!isset($data2[$count]))$data2[$count]=0;
    $data0[$count]=$x0; unset($x0);
    $data1[$count]=$x1; unset($x1);
    $data2[$count]=$x2; unset($x2);
    $lastph=$tph;
    $lastah=$tah;
    $lastpc=$tpc;
    debug(10,"Data Values date($tdate) count[$count] ($tph, $tah, $tpc) d0:(".$data0[$count].") d1:(".$data1[$count].") d2:(".$data2[$count].")");

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
  $chartH=620;
  $chartW=650;
  $plotH=500;
  $plotW=550;
}

# Create an XYChart object of size 600 x 300 pixels, with a light blue (EEEEFF)
# background, black border, 1 pxiel 3D border effect and rounded corners
$c = new XYChart($chartW, $chartH+5, 0xeeee99, 0x000000, 1);
//$c->setRoundedFrame();

# Set the plotarea at (55, 58) and of size 520 x 195 pixels, with white background.
# Turn on both horizontal and vertical grid lines with light grey color (0xcccccc)
if (sizeof($labels) <= 1){

if ($strSmall){
$textBoxObj = $c->addText(30,30,"<*color=FF0000*>REQUIRES ADDITIONAL PROJECT HISTORY",Arial,12);
$textBoxObj = $c->addText(55, 70, "<*img=/srv/www/htdocs/paev4/paevnd.png*>");
}
else{
$textBoxObj = $c->addText(30,30,"<*color=FF0000*>REQUIRES ADDITIONAL PROJECT HISTORY",Arial,20);
$textBoxObj = $c->addText(200, 200, "<*img=/srv/www/htdocs/paev4/paevnd.png*>");
}
}
else {

$legendObj = $c->addLegend(20, 25, false, "arialbd.ttf", 9);
$legendObj->setBackground(Transparent);

}
$c->setPlotArea(50, 70, $plotW, $plotH, 0xffffff, -1, -1, 0xcccccc, 0xcccccc);
$textBoxObj = $c->addTitle("Earned Value$x: $projName - Total", "timesbi.ttf", 12);
# Add a legend box at (50, 30) (top of the chart) with horizontal layout. Use 9 pts
# Arial Bold font. Set the background and border color to Transparent.


# Add a title box to the chart using 15 pts Times Bold Italic font, on a light blue
# (CCCCFF) background with glass effect. white (0xffffff) on a dark red (0x800000)
# background, with a 1 pixel 3D border.
$x=$strAccum?" Accum":"";

unset($x);
$textBoxObj->setBackground(0xcccc77, 0x000000, glassEffect());

# Add a title to the y axis
$c->yAxis->setTitle("Hours");

# Set the labels on the x axis.
$c->xAxis->setLabels($labels);

# Display 1 out of 3 labels on the x-axis.
# now it will make 5 dates
$c->xAxis->setLabelStep($count/5);

# Add a title to the x axis
$c->xAxis->setTitle("Date");

# Add a line layer to the chart
$layer = $c->addLineLayer2();

# Set the default line width to 2 pixels
$layer->setLineWidth(2);

# Add the three data sets to the line layer. For demo purpose, we use a dash line
# color for the last line
/*
$layer->addDataSet($data0, 0xff0000, "Planned");
$layer->addDataSet($data1, 0x008800, "Actual");
$layer->addDataSet($data2, $c->dashLineColor(0x3333ff, DashLine), "Completion");
*/


//foreach($wbsA as $key => $val)
//{
  $dataSetObj = $layer->addDataSet($data0, $wbsC0, "Planned");
  if(!$strSmall)$dataSetObj->setDataSymbol(CircleSymbol, 9);
  $dataSetObj = $layer->addDataSet($data1, $wbsC1, "Actual");
  if(!$strSmall)$dataSetObj->setDataSymbol(DiamondSymbol, 11);
  $dataSetObj = $layer->addDataSet($data2, $c->dashLineColor($wbsC2, DashLine), "Earned");
  if(!$strSmall)$dataSetObj->setDataSymbol(Cross2Shape(), 11);
//}

/* This puts an image at the X,Y coord on the graph */
/*
 * $scatterLayerObj = $c->addScatterLayer(1, 600);
 * $dataSetObj = $scatterLayerObj->getDataSet(0);
 * $dataSetObj->setDataSymbol(dirname(__FILE__)."/images/jsx.gif");
 */


//exit;

# output the chart
header("Content-type: image/png");
print($c->makeChart2(PNG));
?>
