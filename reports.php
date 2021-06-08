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
$HACK_CHECK=1; Include("config/global.inc.php");
debug(10,"Loading File: help.php");
debug(10,"User Level is DB-Admin (".$_SESSION['dbAdmin'].")");
//checkPermissions($SESSION_TIMEOUT); // if not logged in, or session has timed out...
/******************************************/
/* HEADER: INCLUDE/SECURITY CHECK - END   */
/******************************************/
/********************************************
*	        PERMISSIONS CHECK	    *
********************************************/
$strProject =isset($_FORM['txt_project'])?postedData($_FORM['txt_project']):0;
if($strProject){debug(10,"was set($strProject)");$_SESSION['projectID']=$strProject;}
$strProject=$_SESSION['projectID'];

$userId = $_SESSION['paev_userID'];

#Conduct permissions check for user level for this project
$strSQL0 = "SELECT is_admin FROM $TABLE_PROJECT_ACCESS WHERE project_id='$strProject'";
$strSQL0.= " AND person_id='$userId'";
$result0 = dbquery($strSQL0);
$row0 = mysql_fetch_array($result0);

$projectAdmin = $row0['is_admin'];
$dbAdmin = $_SESSION['dbAdmin'];
/********************************************
*	    END PERMISSIONS CHECK	    *
********************************************/


$TITLE = "PAEV Reports";
show_header();
show_menu("REPORTS");
echo '<div style="position:relative; top:-150px">';
echo "<img src=images/logo-reports.png>";
?>

<h1>PAEV Generates Reports</h1>
<p class=paragraph>
<?
if($projectAdmin || $dbAdmin)
{
?>
&nbsp;&nbsp;&nbsp;The PAEV tool generated reports for you. Clicking on <a href="monthly-hours.php" >"Hours By Month"</a> will generate a table that provides the planned hours 
and actual hours worked for each person assigned to a project. The hours are broken down by month for the lifetime of
the project. In addition, graphs are generated containing the same information, but they are split up into two graphs:
one for planned hours and one for actual hours.<br />
<?
}
?>
&nbsp;&nbsp;&nbsp;Clicking on <a href="graphs.php" >"Graphs"</a> will bring you to a page that provides all of the available graphs you may view that belong to a 
project. Two meters at the top display the Schedule Performance Index (SPI) and the Cost Performance Index (CPI). In
addition,depending on your permissions with the project, you may view earned value totals and schedule charts for the
entire project and for each Work Breakdown Structure (WBS) belonging to a project.
</p>


<?
show_footer();

?>
</div>