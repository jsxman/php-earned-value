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

$strSmall=postedData($_FORM['txt_small']);
if(isset($_FORM['txt_active'])){

   if(postedData($_FORM['txt_active']) == "month"){
      //For active projects that have activity in the last month we want to see
      // trailing tails so we can see the past history.
      $timeframe = date("U") - (3600*24*31);
      $title = "All Program Status (Past Month)";
      $where = "AND wh_date > $timeframe";
      $order = "ORDER BY wh_date ASC";
   }else{
      $timeframe = 0;
      $title = "All Program Status";
      $where = "";
      $order = "ORDER BY wh_date DESC LIMIT 1";
   }
}else{
      $timeframe = 0;
      $title = "All Program Status";
      $where = "";
      $order = "ORDER BY wh_date DESC LIMIT 1";
}
$projectNames = Array();   //Holds each project's name keyed by its project_id
$projectIndex = Array();  //Holds each project's SPI and CPI keyed by its index, the number of history records, and spi/cpi

$getProjects = "SELECT p.project_id, project_name FROM pev__project AS p";
$getProjects.= " LEFT JOIN $TABLE_WBS AS w ON p.project_id=w.project_id";
$getProjects.= " LEFT JOIN $TABLE_WBS_HISTORY AS h ON h.wbs_id=w.wbs_id";
$getProjects.= " WHERE wh_date > $timeframe AND active=1 GROUP BY p.project_id ORDER BY project_name";
$project_result = dbquery($getProjects);
while($project_row = mysql_fetch_array($project_result)){

   $project_id = $project_row['project_id'];
   $projectName = $project_row['project_name'];
   if(strlen($projectName) > 17){
      $projectName = substr($projectName, 0, 14);
      $projectName.= "...";
   }
   $projectNames[$project_id] = $projectName;

   $getHistory = "SELECT SUM(total_phours*wh.percent_complete/100) AS ev, SUM(total_ahours) AS ac, SUM(total_phours) AS pv";
   $getHistory.= " FROM pev__wbs_history AS wh LEFT JOIN pev__wbs AS w ON w.wbs_id=wh.wbs_id WHERE project_id=$project_id $where";
   $getHistory.= " GROUP BY wh_date $order";

   $history_result = dbquery($getHistory);
   $count = 0;
   while($history_row = mysql_fetch_array($history_result)){
      if($history_row['pv'] == 0 && $history_row['ev'] == 0){
         //if nothing has been done on this project yet
         $spi = 1;
      }else if($history_row['pv'] == 0 && $history_row['ev'] > 0){
         //Prevent infinate values
         $spi = 2;
      }else{
         $spi = $history_row['ev']/$history_row['pv'];
      }
      if($history_row['ac'] == 0 && $history_row['ev'] == 0){
         //if nothing has been done on this project yet
         $cpi = 1;
      }else if($history_row['pv'] == 0 && $history_row['ev'] > 0){
         //Prevent infinate values
         $cpi = 2;
      }else{
         $cpi = $history_row['ev']/$history_row['ac'];
      }
      $projectIndex[$project_id]["spi"][$count] = $spi;
      $projectIndex[$project_id]["cpi"][$count] = $cpi;
      $count++;
   }


}

require_once($CHARTDIRECTOR);
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
  if($timeframe > 0){
     $xAxisTitle = "SCHEDULE PERFORMANCE \n Showing all active projects and their history within the past month";
  }else{
     $xAxisTitle = "SCHEDULE PERFORMANCE \n Showing lastest historical plot of performance indices for active projects";
  }
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
$c->xAxis()->setLinearScale(0, 2);
$c->xAxis->addMark(1, $c->dashLineColor(0xff0000, DashLine));
$c->yAxis->setWidth(3); 
$c->yAxis()->setLinearScale(0, 2);
$c->yAxis->addMark(1, $c->dashLineColor(0xff0000, DashLine));

$count = 1;
foreach($projectNames as $proj_ID => $proj_Name){
   if(isset($projectIndex[$proj_ID])){
      $color = rand(0,16777215);
      $layer = $c->addLineLayer($projectIndex[$proj_ID]["cpi"], $color, "$count.) $proj_Name");
      //echo "$proj_Name - ";
      //echo "CPI: ";
      //print_r($projectIndex[$proj_ID]['cpi']);
      //echo " SPI: ";
      //print_r($projectIndex[$proj_ID]['spi']);
      //echo "<br>";
      $layer->setXData($projectIndex[$proj_ID]["spi"]);
      $layer->setLineWidth(2);
      //$layer->addCustomDataLabel(0,0,"Start");
      if(!$strSmall){
         $layer->addcustomDataLabel(0,sizeof($projectIndex[$proj_ID]["spi"])-1, "$count", "arialbd.ttf", 12); 
      }
      $lastX = $projectIndex[$proj_ID]["spi"][sizeof($projectIndex[$proj_ID]["spi"])-1];
      $lastY = $projectIndex[$proj_ID]["cpi"][sizeof($projectIndex[$proj_ID]["cpi"])-1];
      $c->addScatterLayer(array($lastX),array($lastY), "", 1, 8, $color);
      //$firstX = $projectIndex[$proj_ID]["spi"][0];
      //$firstY = $projectIndex[$proj_ID]["cpi"][0];
      //$c->addScatterLayer(array($firstX),array($firstY), "", 3, 10, $color);
      $count++;
   }
}

# output the chart
header("Content-type: image/png");
print($c->makeChart2(PNG)); 


?>