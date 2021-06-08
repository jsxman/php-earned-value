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
debug(10,"Loading File: graph-g2.php");
checkPermissions($SESSION_TIMEOUT); // if not logged in, or session has timed out...

$strProject=postedData($_FORM['txt_project']);
$strSmall=(isset($_FORM['txt_small']))?postedData($_FORM['txt_small']):0;


require_once($CHARTDIRECTOR);


$theFirstDate=0;
$theLastDate=0;
$strSQL0 = "SELECT * FROM $TABLE_EVENT AS E";
$strSQL0.=" LEFT JOIN $TABLE_PROJECT AS P ON P.project_id=E.project_id";
$strSQL0.= " WHERE E.project_id='$strProject'";
$strSQL0.= " ORDER BY E.event_pdate";
$result0 = dbquery($strSQL0);
debug(10,$strSQL0);
$my_data=Array(); /* for meetings */
$my_data2=Array(); /* for documents */
$count=-1;
$CLR1=0x0000ff; // PLAN
$CLR2=0x222222; // ?
$CLR3=0x00ff00; // ACTUAL
$CLR4=0xFFFF00; // ESTIMATE
$PLN="PLAN MEETING";
$EST="ESTIMATE MEETING";
$ACT="ACTUAL MEETING";
$PLN2="PLAN DOC";
$EST2="ESTIMATE DOC";
$ACT2="ACTUAL DOC";
$actualStartDate=Array();
$actualEndDate=Array();
while($row0 = mysql_fetch_array($result0))
{
  $count++;
  $eventType=$row0['event_type'];
  $isDoc=$row0['is_doc'];
  $ProjectName=$row0['project_name'];
  if($eventType!=-1) { $_eventName=$EVENT_TYPES[$eventType][0]; }
  else               { $_eventName=$row0['other'];              }
  $_eventDoc=$row0['is_doc'];
  //$_eventPDate = date_read($row0['event_pdate']);
  //$_eventEDate = $row0['event_edate']?date_read($row0['event_edate']):"";
  //$_eventADate = $row0['event_adate']?date_read($row0['event_edate']):"";
  $_date=$row0['event_pdate'];$tmp=split("/",date_read($_date));$chartPD =chartTime("20".$tmp[2],$tmp[0],$tmp[1]);
  $test=0; /* 1=actual,2=estimated,3=none */
  if($_date=$row0['event_adate'])
  {
    $test=1;
    $tmp=split("/",date_read($_date));$chartAD =chartTime("20".$tmp[2],$tmp[0],$tmp[1]);
    if($isDoc)
    {
      $my_data2[count($my_data2)]=Array($count,$chartAD,$ACT2,$CLR3);
      $ACT2="";
    }
    else
    {
      $my_data[count($my_data)]=Array($count,$chartAD,$ACT,$CLR3);
      $ACT="";
    }
    if(!$theFirstDate) $theFirstDate=$chartAD;
    if($theFirstDate>$chartAD)$theFirstDate=$chartAD;
    if(!$theLastDate) $theLastDate=$chartAD;
    if($theLastDate<$chartAD)$theLastDate=$chartAD;
  }
  else // ignore the Estimated date if an actual date is recorded
  {
    $chartAD="";
    if($_date=$row0['event_edate'])
    {
      $test=2;
      $tmp=split("/",date_read($_date));$chartED =chartTime("20".$tmp[2],$tmp[0],$tmp[1]);
      if($isDoc)
      {
        $my_data2[count($my_data2)]=Array($count,$chartED,$EST2,$CLR4);
        $EST2="";
      }
      else
      {
        $my_data[count($my_data)]=Array($count,$chartED,$EST,$CLR4);
        $EST="";
      }
      if(!$theFirstDate) $theFirstDate=$chartED;
      if($theFirstDate>$chartED)$theFirstDate=$chartED;
      if(!$theLastDate) $theLastDate=$chartED;
      if($theLastDate<$chartED)$theLastDate=$chartED;
    }
    else
    {
      $chartED="";
    }
  }

  $labels[]=$_eventName;
  //if($count>0)$PLN="";
  if($isDoc)
  {
    $my_data2[count($my_data2)]=Array($count,$chartPD,$PLN2,$CLR1);
    $PLN2="";
  }
  else
  {
    $my_data[count($my_data)]=Array($count,$chartPD,$PLN,$CLR1);
    $PLN="";
  }
  if($test==1)
  {
    $actualStartDate[count($actualStartDate)]=$chartPD;
    $actualEndDate[count($actualEndDate)]=$chartAD;
  } else if ($test==2)
  {
    $actualStartDate[count($actualStartDate)]=$chartPD;
    $actualEndDate[count($actualEndDate)]=$chartED;
  }
  else
  {
    $actualStartDate[count($actualStartDate)]=null;
    $actualEndDate[count($actualEndDate)]=null;
  }

  if(!$theFirstDate) $theFirstDate=$chartPD;
  if($theFirstDate>$chartPD)$theFirstDate=$chartPD;
  if(!$theLastDate) $theLastDate=$chartPD;
  if($theLastDate<$chartPD)$theLastDate=$chartPD;
}

//////////////////////////////////////////////////////
//////////////////////////////////////////////////////
//////////////////////////////////////////////////////
//////////////////////////////////////////////////////
//////////////////////////////////////////////////////
//////////////////////////////////////////////////////

if($strSmall)
{
  $chartH=300;
  $chartW=350;
  $plotH=200;
  $plotW=320;
  $boxW=20;
  $height1=3;
  for($i=0;$i<=$count;$i++)$labels[$i]="";
}
else
{
  $chartH=180+18*$count;
  $chartW=1000;
  $plotH=40+18*$count;
  $plotW=820;
  $boxW=160;
  $height1=3;
}

# Create a XYChart object of size 620 x 280 pixels. Set background color to light
# green (ccffcc) with 1 pixel 3D border effect.
$c = new XYChart($chartW, $chartH, 0xccffcc, 0x000000, 1);

# Add a title box to the chart using 15 pts Times Bold Italic font, on a light blue
# (CCCCFF) background with glass effect. white (0xffffff) on a dark red (0x800000)
# background, with a 1 pixel 3D border.
$textBoxObj = $c->addTitle("Project Events: $ProjectName", "timesbi.ttf", 12);
$textBoxObj->setBackground(0x88A88A);

# Set the plotarea at (140, 55) and of size 460 x 200 pixels. Use alternative 
# white/grey background. Enable both horizontal and vertical grids by setting their 
# colors to grey (c0c0c0). Set vertical major grid (represents month boundaries) 2 
# pixels in width
$plotAreaObj = $c->setPlotArea($boxW, 85, $plotW, $plotH, 0xffffff, 0xeeeeee, LineColor, 0xc0c0c0, 0xc0c0c0);
$plotAreaObj->setGridWidth(2, 1, 1, 1); 

# swap the x and y axes to create a horziontal box-whisker chart
$c->swapXY(); 

# Set the y-axis scale to be date scale from Aug 16, 2004 to Nov 22, 2004, with ticks 
# every 7 days (1 week)
$theFirstDate-=60*60*24*3;
$theLastDate+=60*60*24*3;
$c->yAxis->setDateScale($theFirstDate, $theLastDate, 86400 * 7); 
//$c->yAxis->setDateScale(chartTime(2004, 8, 1), chartTime(2004, 12, 1), 86400 * 7); 

# Add a red (ff0000) dash line to represent the current day
$c->yAxis->addMark(chartTime(date("Y"), date("n"), date("j")), $c->dashLineColor(0xff0000, DashLine)); 
//$c->yAxis->addMark(chartTime(2004, 10, 1), $c->dashLineColor(0xff0000, DashLine)); 

# Set multi-style axis label formatting. Month labels are in Arial Bold font in "mmm
# d" format. Weekly labels just show the day of month and use minor tick (by using
# '-' as first character of format string).
$c->yAxis->setMultiFormat(StartOfMonthFilter(), "<*font=arialbd.ttf*>{value|mmm d}", StartOfDayFilter(), "-{value|d}"); 

# Set the y-axis to shown on the top (right + swapXY = top)
$c->setYAxisOnRight(); 

# Set the labels on the x axis
$c->xAxis->setLabels($labels); 

# Reverse the x-axis scale so that it points downwards.
$c->xAxis->setReverse(); 

# Set the horizontal ticks and grid lines to be between the bars
$c->xAxis->setTickOffset(0.5); 

# Use blue (0000aa) as the color for the planned schedule
$plannedColor = 0x0000aa; 

# Use a red hash pattern as the color for the actual dates. The pattern is created as 
# a 4 x 4 bitmap defined in memory as an array of colors.
$actualColor = $c->patternColor(array(0xffffff, 0xffffff, 0xffffff, 0xff0000, 0xffffff, 0xffffff, 0xff0000, 0xffffff, 0xffffff, 0xff0000, 0xffffff, 0xffffff, 0xff0000, 0xffffff, 0xffffff, 0xffffff), 4); 

# Add a box whisker layer to represent the actual dates. We add the actual dates 
# layer first, so it will be the top layer.
//$actualLayer = $c->addBoxLayer($actualStartDate, $actualEndDate, $actualColor, "Actual"); 

# Set the bar height to 8 pixels so they will not block the bottom bar
//$actualLayer->setDataWidth($height1); 

# Add a box-whisker layer to represent the planned schedule date
//$boxLayerObj = $c->addBoxLayer($startDate, $endDate, $plannedColor, "Plan");
//$boxLayerObj->setBorderColor(SameAsMainColor); 

# Add a legend box on the top right corner (590, 60) of the plot area with 8 pt Arial 
# Bold font. Use a semi-transparent grey (80808080) background.
$b = $c->addLegend(950, 23, false, "arialbd.ttf", 8);
$b->setAlignment(TopRight);
$b->setBackground(0x80808080, -1, 2);

# Use a red hash pattern as the color for the actual dates. The pattern is created as
# a 4 x 4 bitmap defined in memory as an array of colors.
$actualColor = $c->patternColor(array(0xffffff, 0xffffff, 0xffffff, 0xffff00, 0xffffff, 0xffffff, 0xffff00, 0xffffff, 0xffffff, 0xffff00, 0xffffff, 0xffffff, 0xffff00, 0xffffff, 0xffffff, 0xffffff), 4);

# Add a box whisker layer to represent the actual dates. We add the actual dates
# layer first, so it will be the top layer.
$actualLayer = $c->addBoxLayer($actualStartDate, $actualEndDate, $actualColor, "");

# Set the bar height to 8 pixels so they will not block the bottom bar
$actualLayer->setDataWidth($height1);


for($i=0;$i<count($my_data);$i++)
{
  //$c->addScatterLayer(array(5), array($chartPD), $_eventName, TriangleSymbol, 13, $C1);
  $c->addScatterLayer(array($my_data[$i][0]), array($my_data[$i][1]), $my_data[$i][2], TriangleSymbol, 13, $my_data[$i][3]);
}
for($i=0;$i<count($my_data2);$i++)
{
  //$c->addScatterLayer(array(5), array($chartPD), $_eventName, TriangleSymbol, 13, $C1);
  $c->addScatterLayer(array($my_data2[$i][0]), array($my_data2[$i][1]), $my_data2[$i][2], DiamondSymbol, 13, $my_data2[$i][3]);
}


//exit;

# output the chart
header("Content-type: image/png");
print($c->makeChart2(PNG));
?>
