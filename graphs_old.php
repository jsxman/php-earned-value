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

$userId = $_SESSION['userID'];
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



if($strProject)
{
?>
	<img border=0 src=graph-spi.php?txt_project=<?=$strProject;?>>
	<img border=0 src=graph-cpi.php?txt_project=<?=$strProject;?>><BR>

	<!--<a href=<?=$PAGE_SHOW;?>?txt_graph=ev.php&txt_project=<?=$strProject;?>&txt_small=0>
	<img border=0 src=ev.php?txt_project=<?=$strProject;?>&txt_small=1>
	</a>-->

        <a href=<?=$PAGE_SHOW;?>?txt_graph=graph-overview.php&txt_project=<?=$strProject;?>&txt_small=0>
	<img border=0 src=graph-overview.php?txt_project=<?=$strProject;?>&txt_small=1>
	</a>

        <a href=<?=$PAGE_SHOW;?>?txt_graph=graph-wbs.php&txt_project=<?=$strProject;?>&txt_small=0>
	<img border=0 src=graph-wbs.php?txt_project=<?=$strProject;?>&txt_small=1>
	</a>

	<a href=<?=$PAGE_SHOW;?>?txt_graph=graph-t1.php&txt_project=<?=$strProject;?>&txt_small=0>
	<img border=0 src=graph-t1.php?txt_project=<?=$strProject;?>&txt_small=1>
	</a>

	<a href=<?=$PAGE_SHOW;?>?txt_graph=graph-g1.php&txt_project=<?=$strProject;?>&txt_small=0>
	<img border=0 src=graph-g1.php?txt_project=<?=$strProject;?>&txt_small=1>
	</a>

	<a href=<?=$PAGE_SHOW;?>?txt_graph=graph-mh.php&txt_project=<?=$strProject;?>&txt_small=0>
	<img border=0 src=graph-mh.php?txt_project=<?=$strProject;?>&txt_small=1>
	</a>

	<a href=<?=$PAGE_SHOW;?>?txt_graph=graph-pi.php&txt_project=<?=$strProject;?>&txt_small=0>
	<img border=0 src=graph-pi.php?txt_project=<?=$strProject;?>&txt_small=1>
	</a>
<?
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
}

?>
<BR>

<?

projectSelector($PAGE_GRAPH, $strProject);

show_footer();
?>
