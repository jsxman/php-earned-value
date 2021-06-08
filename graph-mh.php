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
debug(10,"Loading File: graph-mh.php");
checkPermissions($SESSION_TIMEOUT); // if not logged in, or session has timed out...

$strProject=postedData($_FORM['txt_project']);
$strSmall=postedData($_FORM['txt_small']);



/* for each WBS, then select total planned hours, and total actual hours */
$strSQL0 = "SELECT ";
$strSQL0.= " sum(H.planned_hours) AS tph";
$strSQL0.= ",sum(H.actual_hours) AS tah";
$strSQL0.= ",W.wbs_id";
$strSQL0.= ",W.wbs_name";
$strSQL0.= ",P.project_name";
$strSQL0.= " FROM $TABLE_WBS AS W";
$strSQL0.= " LEFT JOIN $TABLE_WBS_TO_TASK AS H ON H.wbs_id=W.wbs_id";
$strSQL0.= " LEFT JOIN $TABLE_PROJECT AS P ON P.project_id=W.project_id";
$strSQL0.= " WHERE W.project_id='$strProject' AND H.rollup!=1";
$strSQL0.= " GROUP BY W.wbs_id";
$strSQL0.= " ORDER BY W.wbs_order ASC";
$result0 = dbquery($strSQL0);
$count=-1;
$wbsA=Array();
/* 2 colors - 1 for planned(data0), 1 for actuals(data1) */
$nRED  =rand(100,200); $nGREEN=rand(0,100); $nBLUE =rand(0,100);
$c0=$nRED*256*256+$nGREEN*256+$nBLUE;
$nRED  =rand(0,100); $nGREEN=rand(100,200); $nBLUE =rand(0,100);
$c1=$nRED*256*256+$nGREEN*256+$nBLUE;
$nRED  =rand(0,100); $nGREEN=rand(0,100); $nBLUE =rand(100,200);
$c2=$nRED*256*256+$nGREEN*256+$nBLUE;
while($row0 = mysql_fetch_array($result0))
{
  $count++;
  $tph=$row0['tph'];
  $tah=$row0['tah'];
  $wbs=$row0['wbs_id'];
  $wbsName=$row0['wbs_name'];
  $labels[$count]=$wbsName;
  $projName=$row0['project_name'];

  if(!isset($data0)) $data0=Array();
  if(!isset($data1)) $data1=Array();
  if(!isset($data2)) $data2=Array();
  if(!isset($data0[$count]))$data0[$count]=0;
  if(!isset($data1[$count]))$data1[$count]=0;
  if(!isset($data2[$count]))$data2[$count]=0;
  $data0[$count]=$tph;
  $data1[$count]=$tah;

  $_ac=$tah;
  $_pv=$tph;
  $_pc=0;
  $strSQL1 = "SELECT percent_complete FROM $TABLE_WBS_HISTORY WHERE wbs_id='$wbs' ORDER BY wh_date DESC LIMIT 1";
  $result1 = dbquery($strSQL1);
  if($row1 = mysql_fetch_array($result1))
  {
    $_pc=$row1['percent_complete']/100;
  }
  $_ev=$_pv*$_pc;
  $_cpi=$_ev/$_ac;
  $_spi=$_ev/$_pc;
  $_eac=$_ac+($_pv-$_ev)/$_cpi;
  $data2[$count]=$_eac;
  debug(10,"MATH: (AC:$_ac, PV:$_pv, PC:$_pc, EV:$_ev, CPI:$_cpi, SPI:$_spi, EAC:$_eac)");
  //debug(10,"Data Values date($tdate) count[$count] ($tph, $tah, $tpc) d0:(".$data0[$count].") d1:(".$data1[$count].") d2:(".$data2[$count].")");
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
elseif(!$strSmall && $count > 3)
{
  $chartH=600;
  $chartW=950;
  $plotH=500;
  $plotW=850;
}
elseif(!$strSmall && $count <= 3)
{
  $chartH=600;
  $chartW=650;
  $plotH=500;
  $plotW=550;
}


# Create an XYChart object of size 600 x 300 pixels, with a light blue (EEEEFF)
# background, black border, 1 pxiel 3D border effect and rounded corners
$c = new XYChart($chartW, $chartH, 0x88AA55, 0xaaaa00, 1);
//$c->setRoundedFrame();

# Set the plotarea at (55, 58) and of size 520 x 195 pixels, with white background.
# Turn on both horizontal and vertical grid lines with light grey color (0xcccccc)
$c->setPlotArea(50, 70, $plotW, $plotH, 0xffffff, -1, -1, 0xcccccc, 0xcccccc);

# Add a legend box at (50, 30) (top of the chart) with horizontal layout. Use 9 pts
# Arial Bold font. Set the background and border color to Transparent.
$legendObj = $c->addLegend(20, 25, false, "arialbd.ttf", 9);
$legendObj->setBackground(Transparent);

# Add a title box to the chart using 15 pts Times Bold Italic font, on a light blue
# (CCCCFF) background with glass effect. white (0xffffff) on a dark red (0x800000)
# background, with a 1 pixel 3D border.
$textBoxObj = $c->addTitle("Man Hours by WBS: $projName", "timesbi.ttf", 12);
unset($x);
$textBoxObj->setBackground(0x559922, 0x000000, glassEffect());

# Add a title to the y axis
$c->yAxis->setTitle("Hours");

if(!$strSmall)
{
	# Set the labels on the x axis.
	$c->xAxis->setLabels($labels);
}

if($strSmall)
{
	# Set the labels on the x axis.
	$c->xAxis->setLabels($labels);

	# Display 1 out of 3 labels on the x-axis.
	# now it will make 5 dates
	$c->xAxis->setLabelStep($count/3);
}

# Add a title to the x axis
//$c->xAxis->setTitle("Date");

# Add a line layer to the chart
$layer = $c->addLineLayer2();

# Set the default line width to 2 pixels
$layer->setLineWidth(2);

# Draw the ticks between label positions (instead of at label positions)
$c->xAxis->setTickOffset(0.5);
# Add a multi-bar layer with 3 data sets
$layer = $c->addBarLayer2(Side);

# Add the three data sets to the line layer. For demo purpose, we use a dash line
# color for the last line
$layer->addDataSet($data0, $c0, "Planned");
$layer->addDataSet($data1, $c1, "Actual");
$layer->addDataSet($data2, $c2, "EAC");

# Set 50% overlap between bars
$layer->setOverlapRatio(0.5);

//if($Debug) exit;
//exit;

# output the chart
header("Content-type: image/png");
print($c->makeChart2(PNG));
?>
