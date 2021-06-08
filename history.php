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
debug(10,"Loading File: history.php");
debug(10,"User Level is DB-Admin (".$_SESSION['dbAdmin'].")");
checkPermissions($SESSION_TIMEOUT); // if not logged in, or session has timed out...
/******************************************/
/* HEADER: INCLUDE/SECURITY CHECK - END   */
/******************************************/
/********************************************
*	    END PERMISSIONS CHECK	    *
********************************************/
$strProject =isset($_FORM['txt_project'])?postedData($_FORM['txt_project']):0;
$strProject = getProject($strProject);

$userId = $_SESSION['paev_userID'];
$projectAdmin = isProjectAdmin($strProject, $userId);
$dbAdmin = $_SESSION['dbAdmin'];
/********************************************
*	    END PERMISSIONS CHECK	    *
********************************************/
/********************************************
*	        AJAX HANDLER     	    *
********************************************/
if(isset($_POST['action']) && ($projectAdmin || $dbAdmin)){
   switch($_POST['action']){
      case "sort":
         display_table();
         break;
      case "create_history":
         create_history();
         display_table();
         break;

   }
   exit;
}

/********************************************
*	        END AJAX HANDLER     	    *
********************************************/



/*Editing a history record*/
if(isset($_FORM['txt_action']) && $_FORM['txt_action'] == "updateEventForm")
{
	$editGo = true;
  	$editId = validInt($_FORM['id']);
    	if($editId <= 0)
	{
		$editGo = false;
    	}
    	else if(postedData($_FORM['del']) == "update")
    	{

  		$editDate = postedData($_FORM['date']);

  		$editDate = date_save($editDate);
  		if($editDate <= 0)
			$editGo = false;

  		$editPHours = validFloat($_FORM['phours']);
   	 	if($editPHours < 0)
			$editGo = false;

		$editAHours = validFloat($_FORM['ahours']);
    		if($editAHours < 0)
			$editGo = false;

  		$editPercent = validInt($_FORM['percent']);
   		if($editPercent < 0)
			$editGo = false;

		$strSQL2 = "SELECT wbs_id FROM $TABLE_WBS_HISTORY WHERE wbs_history_id = $editId";
		$result2 = dbquery($strSQL2);
		$row2 = mysql_fetch_array($result2);

		$wbsId = $row2['wbs_id'];

		$strSQL3 = "SELECT wbs_name FROM $TABLE_WBS WHERE wbs_id = $wbsId";
		$result3 = dbquery($strSQL3);
		$row3 = mysql_fetch_array($result3);

		$wbsName = $row3['wbs_name'];

  		if(isset($editId) && $editGo)
        	{
    	 	 	$strSQL4 = "UPDATE $TABLE_WBS_HISTORY";
          		$strSQL4.= " SET wh_date= '$editDate', total_phours= '$editPHours', total_ahours= '$editAHours', percent_complete= '$editPercent'";
          		$strSQL4.= " WHERE wbs_history_id= '$editId'";
          		$strSQL4.= " LIMIT 1";
          		$result4 = dbquery($strSQL4);

    	 	 	set_status("History record ($editId) for WBS $wbsName has been updated.");
   		}
    	}//end history update
    	else if(postedData($_FORM['del']) == "delete")
    	{
        	$editGo = false;
  		$editId = validInt($_FORM['id']);
  		if($editId > 0)
			$editGo = true;

		$strSQL2 = "SELECT wbs_id FROM $TABLE_WBS_HISTORY WHERE wbs_history_id = $editId";
		$result2 = dbquery($strSQL2);
		$row2 = mysql_fetch_array($result2);

		$wbsId = $row2['wbs_id'];

		$strSQL3 = "SELECT wbs_name FROM $TABLE_WBS WHERE wbs_id = $wbsId";
		$result3 = dbquery($strSQL3);
		$row3 = mysql_fetch_array($result3);

		$wbsName = $row3['wbs_name'];

  		if($editGo == true)
  		{
     	  		$strSQL5 = "DELETE FROM $TABLE_WBS_HISTORY";
     	   		$strSQL5.= " WHERE wbs_history_id = '$editId'";
     	   		$strSQL5.= " LIMIT 1";
     	   		$result5 = dbquery($strSQL5);

    	   		set_status("History Record ($editId) for WBS $wbsName Deleted.");
  		}
    	}//end history delete

    	if($editGo == false)
    	{
     		set_error("There is a problem with changing the history record. If this was an update, check the data for errors");
     		set_error("If this was a delete action, contact a PAEV admin for assistance");
    	}
}

function create_history(){
   //This function creates a new set of history records for each WBS in a given project
   $project_id = validInt($_POST['project_id']);
   $today = date_save(date("n/j/Y"));

   $getWbs = "SELECT wbs_id FROM pev__wbs WHERE project_id = $project_id";
   $wbs_result = dbquery($getWbs);

   while($row = mysql_fetch_array($wbs_result)){
      $wbs_id=$row['wbs_id'];

      //Remove duplicate history entry if exists (keeps graphs form behaving unexpectedly)
      $deleteHistory = "DELETE FROM pev__wbs_history WHERE wbs_id='$wbs_id' AND wh_date='$today'";
      dbquery($deleteHistory);

      //Insert new history with everything zeroed out
      $insertHistory = "INSERT INTO pev__wbs_history (wbs_id, wh_date, total_phours, total_ahours, percent_complete)";
      $insertHistory.= " VALUES($wbs_id, $today, 0,0,0)";
      dbquery($insertHistory);
   }

}

function display_table($project){
   if(isset($_POST['project_id'])){
      $strProject = validInt($_POST['project_id']);
      $sort_flag = validInt($_POST['sort_flag']);
   }else{
      $strProject = getProject($project);
      $sort_flag = 1;
   }

   //if $sort_flag = 1, sort by wbs first
   if($sort_flag){
      $order_by = "wbs_order, wh_date";
   }else{
      $order_by = "wh_date, wbs_order";
   }
?>
   <table class="list" align="center">
	<thead>
		<tr>
			<th>Click to Edit</th>
			<th>WBS Name</th>
			<th>WBS ID</th>
			<th>History ID</th>
			<th>Date Recorded</th>
			<th>Total Planned Hours</th>
			<th>Total Actual Hours</th>
			<th>Percent Complete</th>
		</tr>
	</thead>
	<tbody>
<?
   $rowClass = 0; //Row counter

   $strSQL1 = "SELECT wh.wbs_id, wbs_name, wbs_history_id, wh_date, total_phours, total_ahours, wh.percent_complete";
   $strSQL1.= " FROM pev__wbs AS w LEFT JOIN pev__wbs_history AS wh ON w.wbs_id=wh.wbs_id";
   $strSQL1.= " WHERE project_id = '$strProject' ORDER BY $order_by";
   $result1 = dbquery($strSQL1);

   while($row1 = mysql_fetch_array($result1)){
     $wbsId = $row1['wbs_id'];
     $wbsName = $row1['wbs_name'];

	$historyId = $row1['wbs_history_id'];
	$whdate = $row1['wh_date'];
	$whdate = date_read($whdate);
	$pHours = $row1['total_phours'];
	$aHours = $row1['total_ahours'];
	$percent = $row1['percent_complete'];
        ?>
		<tr class="row<?=$rowClass;?>" id="<?=$historyId;?>">
			<td align="center"><input class="but" type="button" value="Edit" onclick="sendData(<?=$historyId;?>)"></td>
			<td><?=$wbsName;?></td>
			<td align="center"><?=$wbsId;?></td>
			<td align="center"><?=$historyId;?></td>
			<td class="date"><?=$whdate;?></td>
			<td class="hours"><?=$pHours;?></td>
			<td class="hours"><?=$aHours;?></td>
			<td class="percent"><?=$percent;?></td>
		</tr>
        <?
	$rowClass = (++$rowClass)%2;

  //}	
    }
?>
	</tbody>
</table>
<?
}//end display_table()


$TITLE = "Edit Project History";
show_header();
show_menu("PROJECTS");
echo '<div style="position:relative; top:-160px">';
show_status();
show_error();


if($strProject && ($projectAdmin || $dbAdmin))
{
?>
<script type="text/javascript" src="modalbox/lib/prototype.js"></script>
<script type="text/javascript" src="modalbox/lib/scriptaculous.js"></script>
<script type="text/javascript" src="modalbox/modalbox.js"></script>
<script type="text/javascript" src="scripts/history.js"></script>
<link rel="stylesheet" href="modalbox/modalbox.css" type="text/css" media="screen">

<form name="updateHistoryForm" action="<?=$PAGE_HISTORY;?>" method="POST">
<input type="hidden" name="txt_action" value="updateEventForm">
<input type="hidden" name="id">
<input type="hidden" name="date">
<input type="hidden" name="phours">
<input type="hidden" name="ahours">
<input type="hidden" name="percent">
<input type="hidden" name="del">
</form>

<br><br>
<p align="center" class="rulehead">Project History Log</p>
<div id="history_table">
<?display_table($strProject);?>
</div>
<p align="center"><input type="button" class="but" value="Sort By Date" id="sort_button" onclick="sortTable(<?=$strProject;?>);"/> <input type="button" class="but" value="Create Blank Record(s)" onclick="createHistory(<?=$strProject;?>);"/></p>
<p align="center">Creating blank history records allows you to manually enter history data.<br/>If you want history records entered automatically, go to the project data table and click on "Record History".</p>
<?
}//end show page content

if($strProject && !($projectAdmin || $dbAdmin))
{
	show_permission_error(); //If user does not have permission
}

projectSelector($PAGE_HISTORY, $strProject);

show_footer();
?>
</div>