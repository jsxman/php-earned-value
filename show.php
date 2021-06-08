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
debug(10,"Loading File: show.php");
checkPermissions($SESSION_TIMEOUT); // if not logged in, or session has timed out...


$strGraph =isset($_FORM['txt_graph'])?postedData($_FORM['txt_graph']):0;
$strProject =isset($_FORM['txt_project'])?validInt($_FORM['txt_project']):0;
$strSmall   =isset($_FORM['txt_small'])?validInt($_FORM['txt_small']):0;
$strWBS     =isset($_FORM['txt_wbs'])?validInt($_FORM['txt_wbs']):0;
$strACCUM   =isset($_FORM['txt_accum'])?postedData($_FORM['txt_accum']):0;
$strPlanned =isset($_FORM['txt_planned'])?postedData($_FORM['txt_planned']):0;
$strActive  =isset($_FORM['txt_active'])?postedData($_FORM['txt_active']):0;
$projectName = isset($_REQUEST['xLabel'])?postedData($_REQUEST['xLabel']):0;     //Handles requests from department clickable graph

if(isset($_REQUEST['xLabel'])){
   //This handles requests from the department status overview clickable graph
   $getProjId = "SELECT project_id FROM pev__project WHERE project_name='$projectName' LIMIT 1";
   $result = dbquery($getProjId);
   $proj_row = mysql_fetch_array($result);
   $strProject = $proj_row['project_id'];
   $strSmall = 0;
   //Reset project id to the one selected to prevent confusion with project labels because we jumped to this project
   $_SESSION['projectID'] = $strProject;
   $strGraph = "graph-overview.php";
}

$TITLE="Show Graph";
show_header();
show_menu("");
echo '<div style="position:relative; top:-150px">';
echo "<img src='$strGraph?txt_project=$strProject&txt_small=$strSmall&txt_wbs=$strWBS&txt_accum=$strACCUM&txt_planned=$strPlanned&txt_active=$strActive'>";

?>
<fieldset><legend>PAEV Metrics</legend>
<b>Planned Value (Hours)</b> - Amount of time each task is expected to consume<br/>
<b>Actual Value (Hours)</b> - Actual amount of time each task consumed<br/>
<b>Earned Value (Hours)</b> - Value of work completed (Planned Value multiplied by Percent complete)<br/>
<b>Cost Performance Index (CPI)</b> - Earned Value/Actual Value - Favorable is > 1.0, Unfavorable is < 1.0<br/>
<b>Schedule Performace Index (SPI)</b> - Earned Value/Planned Value - Favorable is > 1.0, Unfavorable is < 1.0<br/>
<b>Estimate At Completion (EAC)</b> - Estimate of total cost of a project or work unit when finished
</fieldset>
<?
show_footer();
?>
</div>