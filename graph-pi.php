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
debug(10,"Loading File: graph-pi.php");
checkPermissions($SESSION_TIMEOUT); // if not logged in, or session has timed out...

$strProject=postedData($_FORM['txt_project']);
$strSmall=postedData($_FORM['txt_small']);

//Get project name
$strName = "SELECT project_name FROM pev__project WHERE project_id=$strProject";
$name_result = dbquery($strName);
$name_row = mysql_fetch_array($name_result);
$projName = $name_row['project_name'];

/* find total planned hours for all WBS for this project */
$PlannedHours=Array();
$strSQL0 = "SELECT W.wbs_id,W.wbs_name,sum(T.planned_hours) as tph FROM $TABLE_WBS AS W";
$strSQL0.= " LEFT JOIN $TABLE_WBS_TO_TASK AS T ON T.wbs_id=W.wbs_id";
$strSQL0.= " WHERE W.project_id='$strProject' AND T.rollup != 1";
$strSQL0.= " GROUP BY W.wbs_id";
$strSQL0.= " ORDER BY W.wbs_order";
$result0 = dbquery($strSQL0);
$wbsA=Array();
while($row0 = mysql_fetch_array($result0))
{
  $wbsA[$row0['wbs_id']]=$row0['wbs_name'];
  $PlannedHours[$row0['wbs_id']]=$row0['tph'];
  debug(10,"Planned hours for WBS (".$row0['wbs_id'].") is ".$row0['tph']);
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
while($row1 = mysql_fetch_array($result1))
{
  if($last_date<>$row1['wh_date'])
  {
    $last_date=$row1['wh_date'];
    $counter++;
  }
  $date=date_read($row1['wh_date']);
  $wbsName=$row1['wbs_name'];
  $wbs=$row1['wbs_id'];
  $pv=$PlannedHours[$wbs];
  $pc=$row1['percent_complete']/100;
  $ac=$row1['total_ahours'];

  /* do math */
  $ev=$pv*$pc;
  $cpi=$ev/$ac; debug(10, "CPI($cpi)=EV($ev)/AC($ac)");
  $spi=$ev/$pv; debug(10, "SPI($spi)=EV($ev)/PC($pv)");

  if($cpi>0 && $spi>0)
  {
    $dates[$counter]=$date;
    $label[$counter]=$wbsName;
    if(!isset($data0[$wbs])) $data0[$wbs]=Array();
    if(!isset($data1[$wbs])) $data1[$wbs]=Array();
    $data0[$wbs][$counter]=$cpi;
    $data1[$wbs][$counter]=$spi;
    if(!isset($wbsC0[$wbs]))
    {
      $nRED  =rand(0,200); $nGREEN=rand(0,200); $nBLUE =rand(0,200);
      $c0=$nRED*256*256+$nGREEN*256+$nBLUE;
      $wbsC0[$wbs]=$c0;
    }
    debug(10,"COUNTER:$counter, PV:$pv, PC:$pc, date:$date EV:$ev, CPI:$cpi, SPI:$spi, COLOR:".$wbsC0[$wbs].", WBS:$wbs");
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

$chartBG=0xeebbaa;
$headerBG=0xccbbcc;

# Create an XYChart object of size 600 x 300 pixels, with a light blue (EEEEFF)
# background, black border, 1 pxiel 3D border effect and rounded corners
$c = new XYChart($chartW, $chartH, $chartBG, 0x000000, 1);
//$c->setRoundedFrame();

# Set the plotarea at (55, 58) and of size 520 x 195 pixels, with white background.
# Turn on both horizontal and vertical grid lines with light grey color (0xcccccc)
if (sizeof($dates) <= 1) {

if ($strSmall){
$textBoxObj = $c->addText(30,30,"<*color=FF0000*>REQUIRES ADDITIONAL PROJECT HISTORY",Arial,12);
$textBoxObj = $c->addText(55, 80, "<*img=/srv/www/htdocs/paev4/paevnd.png*>");
} 
else {
$textBoxObj = $c->addText(30,30,"<*color=FF0000*>REQUIRES ADDITIONAL PROJECT HISTORY",Arial,20);
$textBoxObj = $c->addText(200, 200, "<*img=/srv/www/htdocs/paev4/paevnd.png*>");

}
}
else{

$legendObj = $c->addLegend(20, 25, false, "arialbd.ttf", 8);
$legendObj->setBackground(Transparent);

}
$c->setPlotArea(50, 100, $plotW, $plotH, 0xffffff, -1, -1, 0xcccccc, 0xcccccc);
$textBoxObj = $c->addTitle("Performance Indexes: $projName", "timesbi.ttf", 12);

# Add a legend box at (50, 30) (top of the chart) with horizontal layout. Use 9 pts
# Arial Bold font. Set the background and border color to Transparent.


# Add a title box to the chart using 15 pts Times Bold Italic font, on a light blue
# (CCCCFF) background with glass effect. white (0xffffff) on a dark red (0x800000)
# background, with a 1 pixel 3D border.

unset($x);
$textBoxObj->setBackground($headerBG, 0x000000, glassEffect());

# Add a title to the y axis
$c->yAxis->setTitle("Index");

# Set the labels on the x axis.
$c->xAxis->setLabels($dates);

# Display 1 out of 3 labels on the x-axis.
# now it will make 5 dates
$c->xAxis->setLabelStep($counter/5);

# Add a title to the x axis
$c->xAxis->setTitle("Date");

# Add a line layer to the chart
$layer = $c->addLineLayer2();

# Set the default line width to 2 pixels
$layer->setLineWidth(2);

# Add the three data sets to the line layer. For demo purpose, we use a dash line
# color for the last line
foreach($wbsA as $key => $val)
{
  $layer->addDataSet($data0[$key], $wbsC0[$key], "CPI:".$val);
  //$layer->addDataSet($data1[$key], $wbsC1[$key], "SPI:".$val);
  $layer->addDataSet($data1[$key], $c->dashLineColor($wbsC0[$key], DashLine), "SPI:".$val);
}



//if($Debug) exit;
//exit;

# output the chart
header("Content-type: image/png");
print($c->makeChart2(PNG));
?>
