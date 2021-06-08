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
/******************************************/
/* HEADER: INCLUDE/SECURITY CHECK - START */
/******************************************/
$HACK_CHECK=1; include("config/global.inc.php");
debug(10,"Loading File: help.php");
debug(10,"User Level is DB-Admin (".$_SESSION['dbAdmin'].")");
//checkPermissions($SESSION_TIMEOUT); // if not logged in, or session has timed out...
/******************************************/
/* HEADER: INCLUDE/SECURITY CHECK - END   */
/******************************************/
$TITLE = "PAEV Help Page";
show_header();
show_menu("HELP");
?>
<div style="position:relative; top:-150px">
<img src="images/logo-help.png">

<h1>Description of PAEV Metrics</h1>
<img src="images/help_images/PAEV_metrics_tables1.png" alt="Description of PAEV metrics">
<br/>
<img src="images/help_images/PAEV_metrics_tables2.png" alt="Description of PAEV metrics">

<h1>Project Data Table</h1>
<p class="description">
<b>Purpose</b><br>
The Project Data Table is the primary source of data entry for a project. It allows
project administrators (managers) and users to enter specific data for tasks that
make up a project.<br><br>

<b>Organization</b><br>
The table divides each Work Breakdown Structure (WBS) into a column that is sub-divided into:<br>
<ul>
	<li>Point of Contact (POC)</li>
	<li>Due Date (Due)</li>
	<li>Event Compelete Date (EC)</li>
	<li>Planned Hours (Plan Hours)</li>
	<li>Actual Hours (Actual Hours)</li>
	<li>Percent Complete (%)</li> 
</ul>
These sub-columns are used to describe the information
for each task that falls under the WBS.<br><br>

Each row represents a task. When a task is created, spaces are available under each WBS in case a 
task needs to be repeated in each WBS throughout a project.<br><br>

The table also calculates the planned hours, actual hours, and percent complete totals for each WBS at the
bottom of the table. In addition, the planned and actual hours for each task is calculated and totaled at
on the right columns.<br>

</p>
<img src="images/sampleTable.png" alt="Sample Table with Instructions">

<?

show_footer();

?>
</div>