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
debug(10,"Loading File: graphs.php");
checkPermissions($SESSION_TIMEOUT); // if not logged in, or session has timed out...

/********************************************
*	        PERMISSIONS CHECK	    *
********************************************/
$strProject =isset($_FORM['txt_project'])?postedData($_FORM['txt_project']):0;
$strProject = getProject($strProject);

$userId = $_SESSION['paev_userID'];
$projectAdmin = isProjectAdmin($strProject, $userId);
$dbAdmin = $_SESSION['dbAdmin'];
/********************************************
*	    END PERMISSIONS CHECK	    *
********************************************/

if(isset($_GET['project'])){
   $strProject = $_GET['project'];
   $_SESSION['projectID'] = $strProject;
}


$TITLE="Project Graphs";
show_header();
show_menu("REPORTS");
$render = 0;
echo'<div style="position:relative; top:-150px">';

if($strProject > 0){
   //If the project is in a replan phase, show the EV combined graph
   $getRevision = "SELECT revision, locked FROM pev__project WHERE project_id='$strProject'";
   $getWbs = "SELECT wbs_id FROM pev__wbs WHERE project_id = '$strProject'";
   $wbs_result = mysql_query($getWbs);
   
      $row1 = mysql_fetch_array($wbs_result);
      $wbs_id=$row1['wbs_id'];
	  //echo "$wbs_id";
	  $checkhist = "SELECT * FROM pev__wbs_history WHERE wbs_id='$wbs_id'";
	  $result = mysql_query($checkhist);
	  while($row = mysql_fetch_array($result)){ 
	  //echo "$row[wbs_history_id] <br>";
      $num_results = mysql_num_rows($result); 
	  //echo "$num_results";
      if ($num_results > 0){ 
		$render = 1;
		//echo "render";
	  }else{ 
		$render = 0; 
		//echo "dont render";
      } 
	 }

	  
   
   
   $rowRev = mysql_fetch_array(dbquery($getRevision));
   if($rowRev['revision'] > 0 && $rowRev['locked'] == 1){
      $replan = 1;
   }else{
      $replan = 0;
   }



 if ($render == 1){?>
<fieldset><legend>PAEV Metrics</legend>
<b>Planned Value (Hours)</b> - Amount of time each task is expected to consume<br/>
<b>Actual Value (Hours)</b> - Actual amount of time each task consumed<br/>
<b>Earned Value (Hours)</b> - Value of work completed (Planned Value multiplied by Percent complete)<br/>
<b>Cost Performance Index (CPI)</b> - Earned Value/Actual Value - Favorable is > 1.0, Unfavorable is < 1.0<br/>
<b>Schedule Performace Index (SPI)</b> - Earned Value/Planned Value - Favorable is > 1.0, Unfavorable is < 1.0<br/>
<b>Estimate At Completion (EAC)</b> - Estimate of total cost of a project or work unit when finished
</fieldset><br/>
	<!--<img border=0 src=graph-spi.php?txt_project=<?=$strProject;?>>
	<img border=0 src=graph-cpi.php?txt_project=<?=$strProject;?>>-->
        <img border=0 src=graph-spi2.php?txt_project=<?=$strProject;?>>
        <img border=0 src=graph-cpi2.php?txt_project=<?=$strProject;?>><BR>

	<!--<a href=<?=$PAGE_SHOW;?>?txt_graph=ev.php&txt_project=<?=$strProject;?>&txt_small=0>
	<img border=0 src=ev.php?txt_project=<?=$strProject;?>&txt_small=1>
	</a>-->

        <!--<a href=<?=$PAGE_SHOW;?>?txt_graph=graph-cpi2.php&txt_project=<?=$strProject;?>&txt_small=0>
	<img border=0 src=graph-cpi2.php?txt_project=<?=$strProject;?>&txt_small=1>
	</a>-->

        <a href=<?=$PAGE_SHOW;?>?txt_graph=graph-ev.php&txt_project=<?=$strProject;?>&txt_small=0&txt_planned=1>
	<img border=0 src=graph-ev.php?txt_project=<?=$strProject;?>&txt_small=1&txt_planned=1>
	</a>

        <a href=<?=$PAGE_SHOW;?>?txt_graph=graph-ev.php&txt_project=<?=$strProject;?>&txt_small=0>
	<img border=0 src=graph-ev.php?txt_project=<?=$strProject;?>&txt_small=1>
	</a>

        <a href=<?=$PAGE_SHOW;?>?txt_graph=graph-ev-expand.php&txt_project=<?=$strProject;?>&txt_small=0>
	<img border=0 src=graph-ev-expand.php?txt_project=<?=$strProject;?>&txt_small=1>
	</a>

        <a href=<?=$PAGE_SHOW;?>?txt_graph=graph-overview.php&txt_project=<?=$strProject;?>&txt_small=0>
	<img border=0 src=graph-overview.php?txt_project=<?=$strProject;?>&txt_small=1>
	</a>
<?
	$wbs_query = "SELECT wbs_id FROM pev__wbs WHERE project_id = '$strProject'";

	$wbs_result = dbquery($wbs_query);

	while ($wbs_row = mysql_fetch_array($wbs_result)) {

		error_log("WBS CHART: $wbs_row[wbs_id]");?>

		<a href=<?=$PAGE_SHOW?>?txt_graph=graph-c1.php&txt_project=<?=$strProject?>&txt_wbs=<?=$wbs_row["wbs_id"]?>&txt_accum=1&txt_small=0>
		<img border=0 src=graph-c1.php?txt_project=<?=$strProject?>&txt_wbs=<?=$wbs_row["wbs_id"]?>&txt_accum=1&txt_small=1>
		</a>
		<a href=<?=$PAGE_SHOW?>?txt_graph=graph-c1.php&txt_project=<?=$strProject?>&txt_wbs=<?=$wbs_row["wbs_id"]?>&txt_accum=0&txt_small=0>
		<img border=0 src=graph-c1.php?txt_project=<?=$strProject?>&txt_wbs=<?=$wbs_row["wbs_id"]?>&txt_accum=0&txt_small=1>
		</a>
		<!--<a href=<?=$PAGE_SHOW?>?txt_graph=graph-g1.php&txt_project=<?=$strProject?>&txt_wbs=<?=$wbs_row["wbs_id"]?>&txt_small=0>
		<img border=0 src=graph-g1.php?txt_project=<?=$strProject?>&txt_wbs=<?=$wbs_row["wbs_id"]?>&txt_small=1>
		</a>-->
<?
	}?>

        <!--<a href=<?=$PAGE_SHOW;?>?txt_graph=graph-wbs.php&txt_project=<?=$strProject;?>&txt_small=0>
	<img border=0 src=graph-wbs.php?txt_project=<?=$strProject;?>&txt_small=1>
	</a>-->

	<a href=<?=$PAGE_SHOW;?>?txt_graph=graph-t1.php&txt_project=<?=$strProject;?>&txt_small=0>
	<img border=0 src=graph-t1.php?txt_project=<?=$strProject;?>&txt_small=1>
	</a>

	<!--<a href=<?=$PAGE_SHOW;?>?txt_graph=graph-g1.php&txt_project=<?=$strProject;?>&txt_small=0>
	<img border=0 src=graph-g1.php?txt_project=<?=$strProject;?>&txt_small=1>
	</a>-->

	<a href=<?=$PAGE_SHOW;?>?txt_graph=graph-mh.php&txt_project=<?=$strProject;?>&txt_small=0>
	<img border=0 src=graph-mh.php?txt_project=<?=$strProject;?>&txt_small=1>
	</a>

	<a href=<?=$PAGE_SHOW;?>?txt_graph=graph-pi.php&txt_project=<?=$strProject;?>&txt_small=0>
	<img border=0 src=graph-pi.php?txt_project=<?=$strProject;?>&txt_small=1>
	</a>

<?
        if($replan){
?>
        <a href=<?=$PAGE_SHOW;?>?txt_graph=graph-ev-combined.php&txt_project=<?=$strProject;?>&txt_small=0>
	<img border=0 src=graph-ev-combined.php?txt_project=<?=$strProject;?>&txt_small=1>
	</a>
<?
        }
	if($projectAdmin == 1 || $dbAdmin == 1)
	{
?>
		<a href=<?=$PAGE_SHOW;?>?txt_graph=graph-month.php&txt_project=<?=$strProject;?>&txt_small=0&txt_planned=1>
		<img border=0 src=graph-month.php?txt_project=<?=$strProject;?>&txt_small=1&txt_planned=1>
		</a>

		<a href=<?=$PAGE_SHOW;?>?txt_graph=graph-month.php&txt_project=<?=$strProject;?>&txt_small=0&txt_planned=0>
		<img border=0 src=graph-month.php?txt_project=<?=$strProject;?>&txt_small=1&txt_planned=0>
		</a>
<?
	}
        /*REMOVED ALL GANTT CHARTS
	$strSQL2 = "SELECT project_id, wbs_id FROM $TABLE_WBS AS W";
	$strSQL2.= " WHERE W.project_id='$strProject'";
	$strSQL2.= " ORDER BY wbs_order ASC, wbs_name ASC";
	$result2 = dbquery($strSQL2);
	$x="";
	$y="";
	while($row2 = mysql_fetch_array($result2))
	{
		echo "<a href=$PAGE_SHOW?txt_graph=graph-c1.php&txt_project=$strProject&txt_wbs=".$row2['wbs_id']."&txt_accum=1&txt_small=0>";
  		echo "<img border=0 src=graph-c1.php?txt_project=$strProject&txt_wbs=".$row2['wbs_id']."&txt_accum=1&txt_small=1>";
  		echo "</a>\n";
  		$x.= "<a href=$PAGE_SHOW?txt_graph=graph-c1.php&txt_project=$strProject&txt_wbs=".$row2['wbs_id']."&txt_accum=0&txt_small=0>";
  		$x.= "<img border=0 src=graph-c1.php?txt_project=$strProject&txt_wbs=".$row2['wbs_id']."&txt_accum=0&txt_small=1>";
  		$x.= "</a>\n";
  		$y.= "<a href=$PAGE_SHOW?txt_graph=graph-g1.php&txt_project=$strProject&txt_wbs=".$row2['wbs_id']."&txt_small=0>";
  		$y.= "<img border=0 src=graph-g1.php?txt_project=$strProject&txt_wbs=".$row2['wbs_id']."&txt_small=1>";
  		$y.= "</a>\n";
	}
	echo $x;unset($x);
	echo $y;unset($y);
        */
} else {
     echo " <p style='color:red' align='center'><b>This project has no recorded history. Please return to your project's data table and record history before attempting to gather project metrics</b></p>";

     }

?>
<BR>

<? } 

projectSelector($PAGE_GRAPH, $strProject);

show_footer();
?>
</div>