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
debug(10,"Loading File: project-settings.php");
debug(10,"User Level is DB-Admin (".$_SESSION['dbAdmin'].")");
checkPermissions($SESSION_TIMEOUT); // if not logged in, or session has timed out...
/******************************************/
/* HEADER: INCLUDE/SECURITY CHECK - END   */
/******************************************/

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

$TITLE = "Project Settings";
show_header();
show_menu("PROJECTS");

//Update Project Name/Date
if(isset($_FORM['txt_action']) && $_FORM['txt_action'] == "projectUpdate")
{
	$editGo = true;
  	$projId = $strProject;

  	$projName = postedData($_FORM['proj_name']);
  	$projDate = date_save(postedData($_FORM['proj_date']));

        if($projDate == 0 || $projDate == NULL){
           $projDate = date("U");
           set_error("Invalid date entered, please re-submit date. Today's date entered by default.");
        }

	$strSQL0 = "SELECT project_id FROM $TABLE_PROJECT";
	$strSQL0.= " WHERE project_name = '$projName' AND project_id <> $projId";
	$result0 = dbquery($strSQL0);
	$row0 = mysql_fetch_array($result0);
	if($row0 && $row0['project_id'])
	{
		$editGo = false;
		set_error("There is already a project named $projName. Please choose another name.");
	}

  	if($projName && $editGo)
  	{
		$strSQL1 = "UPDATE $TABLE_PROJECT";
		$strSQL1.= " SET project_name='$projName'";
		$strSQL1.= ", start_date='$projDate'";
		$strSQL1.= " WHERE project_id='$projId'";
		$strSQL1.= " LIMIT 1";
		$result1 = dbquery($strSQL1);

		$date = date_read($projDate);
		set_status("Project $projId has been updated. Name: $projName - Start Date: $date");
  	}
}

if(isset($_FORM['txt_action']) && $_FORM['txt_action'] == "updateWbsForm")
{

  	$editGo = true;
  	$editId = validInt($_FORM['wbsID']);

  	if($editId <= 0)
	{
		$editGo = false;
  	}
  	else if(postedData($_FORM['wbs_delete']) == "update")
  	{
		$editName = postedData($_FORM['wbs_name']);
		if($editName == null)
			$editGo = false;

  		$editOrder = validInt($_FORM['wbs_order']);
		if($editOrder < 0)
			$editGo = false;

	  	$checkforduplicateWBS = "SELECT wbs_order, wbs_id FROM pev__wbs WHERE wbs_order = '$editOrder' and project_id = '$_SESSION[projectID]'";
		$duplicateWBSfound = dbquery($checkforduplicateWBS);
		if(mysql_num_rows($duplicateWBSfound)!=0){
			$duplicateWBSrow = mysql_fetch_array($duplicateWBSfound);
			if($duplicateWBSrow['wbs_id'] != $editId) {
				$editGo = false;
			}	
		}
		
		if($editGo)
		{
			$strSQL6 = "UPDATE $TABLE_WBS";
			$strSQL6.= " SET wbs_name='$editName', wbs_order='$editOrder'";
			$strSQL6.= " WHERE wbs_id='$editId'";
			$strSQL6.= " LIMIT 1";
			$result6 = dbquery($strSQL6);
			
			$strSQL61 = "SELECT wbs_to_task_id, wbs_number FROM pev__wbs_to_task WHERE wbs_id = '$editId'";
			$result61 = dbquery($strSQL61);
			
			while($row61 = mysql_fetch_array($result61))
			{
				$id = $row61['wbs_to_task_id'];
				$wbs = $row61['wbs_number'];
				$wbs = substr($wbs, 3);
				$wbs = $editOrder.$wbs;
				$strSQL62 = "UPDATE pev__wbs_to_task SET wbs_number='$wbs' WHERE wbs_to_task_id='$id'";
				$result62 = dbquery($strSQL62);
			
			}

			set_status("WBS $editId has been updated. Name: $editName - Order: $editOrder");
		}
  	}//end wbs update
  	else if(postedData($_FORM['wbs_delete']) == "delete")
  	{
		$strSQL7 = "DELETE FROM $TABLE_WBS";
     		$strSQL7.= " WHERE wbs_id = '$editId'";
     		$strSQL7.= " LIMIT 1";
     		$result7 = dbquery($strSQL7);
		set_status("WBS $editId Has Been Deleted.");

     		$strSQL8 = "SELECT wbs_to_task_id, task_id FROM $TABLE_WBS_TO_TASK";
     		$strSQL8.= " WHERE wbs_id = '$editId'";
     		$result8 = dbquery($strSQL8);

		while($row8 = mysql_fetch_array($result8))
		{
			$wbsTaskId = $row8['wbs_to_task_id'];

			$strSQL9 = "DELETE FROM $TABLE_PERSON_TO_WBSTASK";
     			$strSQL9.= " WHERE wbs_to_task_id ='$wbsTaskId'";
     			$result9 = dbquery($strSQL9);
     			
     		$task_id = $row8['task_id'];
     		
     		$strSQL13 = "DELETE FROM $TABLE_TASK WHERE task_id = $task_id";
     		$result13 = dbquery($strSQL13);
		}

		$strSQL10 = "DELETE FROM $TABLE_WBS_TO_TASK";
     		$strSQL10.= " WHERE wbs_id = '$editId'";
     		$result10 = dbquery($strSQL10);
		set_status("WBS Tasks Deleted");

     		$strSQL11 = "DELETE FROM $TABLE_WBS_HISTORY";
     		$strSQL11.= " WHERE wbs_id = '$editId'";
     		$result11 = dbquery($strSQL11);
		set_status("WBS History Deleted");
  	}//end wbs delete
  	else if(postedData($_FORM['wbs_delete']) == "cancel")
  	{
  		set_status("WBS Delete action cancelled");
  	}

  	if($editGo == false)
  	{
    	set_error("There is a problem with editing the WBS. Please verify that the WBS Order Number is not a duplicate and try again or contact a PAEV admin for assistance.");
  	}
}//end submit of updateWbsForm

//Add a new WBS
if(isset($_FORM['new_wbs']))
{
	
	$editGo = true;
	$wbsName = postedData($_FORM['newWBS']);
	if($wbsName == NULL){$editGo = false;}
	$wbsOrder = validInt($_FORM['newOrder']);
	if($wbsOrder < 0){$editGo = false;}
	if(!(is_numeric($wbsOrder))){$editGo = false;}
	$wbsProject = $strProject;
	$checkforduplicateWBS = "SELECT wbs_order FROM pev__wbs WHERE wbs_order = '$wbsOrder' and project_id = '$wbsProject'";
	$duplicateWBSfound = dbquery($checkforduplicateWBS);
	if(!mysql_num_rows($duplicateWBSfound)==0){
		$editGo = false;
	}
	if($editGo == true)
	{
		$strSQL12 = "INSERT INTO $TABLE_WBS SET";
		$strSQL12.= " wbs_name='$wbsName',";
		$strSQL12.= " project_id='$wbsProject',";
		$strSQL12.= " wbs_order='$wbsOrder'";
		$result12 = dbquery($strSQL12);

		set_status("New WBS '$wbsName' created");
	}
	
	if($editGo == false)
  	{
    	set_error("There is a problem with adding the WBS. Please check verify that the WBS Order Number is not a duplicate and try again or contact a PAEV admin for assistance.");
  	}
}

//Edit the people who can view/edit this project
$str_project = $strProject;
$str_update=isset($_FORM['txt_update'])?postedData($_FORM['txt_update']):"";
$str_who=isset($_FORM['txt_who'])?validInt($_FORM['txt_who']):null;
if(isset($_FORM['txt_update']) && $_FORM['txt_update'] == "Access-Admin")
{
	$strSQL3 = "SELECT first, last FROM $TABLE_PERSON WHERE person_id = '$str_who'";
	$result3 = dbquery($strSQL3);
	$row3 = mysql_fetch_array($result3);
	$firstName = $row3['first'];
	$lastName = $row3['last'];

  	debug(3, "Set Access-Admin. Project($str_project), Person($str_who)");
  	$strSQL4 = "DELETE FROM $TABLE_PROJECT_ACCESS";
  	$strSQL4.= " WHERE project_id='$str_project' AND person_id='$str_who'";
  	$result4 = dbquery($strSQL4);
  	$strSQL4 = "INSERT INTO $TABLE_PROJECT_ACCESS SET";
  	$strSQL4.= " project_id='$str_project'";
  	$strSQL4.= ",person_id='$str_who'";
  	$strSQL4.= ",is_admin='1'";
  	$result4 = dbquery($strSQL4);
  	set_status("Set $firstName $lastName to ADMIN for Project Access.");
}
if(isset($_FORM['txt_update']) && $_FORM['txt_update'] == "Access-User")
{
	$strSQL3 = "SELECT first, last FROM $TABLE_PERSON WHERE person_id = '$str_who'";
	$result3 = dbquery($strSQL3);
	$row3 = mysql_fetch_array($result3);
	$firstName = $row3['first'];
	$lastName = $row3['last'];

  	debug(3, "Set Access-User. Project($str_project), Person($str_who)");
  	$strSQL4 = "DELETE FROM $TABLE_PROJECT_ACCESS";
  	$strSQL4.= " WHERE project_id='$str_project' AND person_id='$str_who'";
  	$result4 = dbquery($strSQL4);
  	$strSQL4 = "INSERT INTO $TABLE_PROJECT_ACCESS SET";
  	$strSQL4.= " project_id='$str_project'";
  	$strSQL4.= ",person_id='$str_who'";
  	$strSQL4.= ",is_admin='0'";
  	$result4 = dbquery($strSQL4);
  	set_status("Set $firstName $lastName to USER for Project Access.");
}
if(isset($_FORM['txt_update']) && $_FORM['txt_update'] == "Access-None")
{
	$strSQL3 = "SELECT first, last FROM $TABLE_PERSON WHERE person_id = '$str_who'";
	$result3 = dbquery($strSQL3);
	$row3 = mysql_fetch_array($result3);
	$firstName = $row3['first'];
	$lastName = $row3['last'];

  	debug(3, "Set Access-None. Project($str_project), Person($str_who)");
  	$strSQL4 = "DELETE FROM $TABLE_PROJECT_ACCESS";
  	$strSQL4.= " WHERE project_id='$str_project' AND person_id='$str_who'";
  	$result4 = dbquery($strSQL4);
  	set_status("Set $firstName $lastName to NONE for Project Access.");
}

//Determine if the project is locked or not.
$strLocked = "SELECT locked FROM pev__project WHERE project_id='$str_project'";
$lockedRow = mysql_fetch_array(dbquery($strLocked));
if($lockedRow['locked'] == 0){
   $disable = "";
}else{
   $disable = "DISABLED";
}

show_status();
show_error();
echo '<div style="position:relative; top:-160px">';
if($strProject && ($projectAdmin || $dbAdmin)){
?>
<script type="text/javascript" src="modalbox/lib/prototype.js"></script>
<script type="text/javascript" src="modalbox/lib/scriptaculous.js"></script>
<script type="text/javascript" src="modalbox/modalbox.js"></script>
<script type="text/javascript" src="scripts/project-settings.js"></script>
<link rel="stylesheet" href="modalbox/modalbox.css" type="text/css" media="screen">

<form name="updateWbsForm" action="<?=$PAGE_SETTINGS;?>" method="POST">
<input type="hidden" name="txt_action" value="updateWbsForm">
<input type="hidden" readonly="readonly" name="wbsID">
<input type="hidden" name="wbs_name">
<input type="hidden" name="wbs_order">
<input type="hidden" name="wbs_delete">
</form>


<fieldset>
	<legend>Project Setup</legend>
	<p align="center">Rename your project or provide a new starting date.</p>
	<table align="center" class="list">
		<tr>
			<th colspan="3">Edit Project Name/Start Date</th>
		</tr>
<?
$strSQL1 = "SELECT project_name, start_date FROM $TABLE_PROJECT WHERE project_id='$strProject'";
$result1 = dbquery($strSQL1);
$row1 = mysql_fetch_array($result1);

$projectName = $row1['project_name'];
$startDate = $row1['start_date'];
$startDate = date_read($startDate);

?>
		<form name="updateProject" action="<?=$PAGE_SETTINGS;?>" method="POST">
		<input type="hidden" name="txt_action" value="projectUpdate">
		<tr>
			<td>Project Name</td>
			<td>Project Start Date</td>
		</tr>
		<tr>
			<td><input type="text" value="<?=$projectName?>" name="proj_name" <?=$disable;?>></td>
			<td><input onclick="showCal(this.id)" type="text" value="<?=$startDate?>" name="proj_date" id="proj_date" <?=$disable;?>></td>
			<td><input class="but" type="submit" value="Update Changes" name="project_edit" <?=$disable;?>></td>
		</tr>
	</form>
</table>
<br />
</fieldset>
<br>
<fieldset>
	<legend>Project WBS Settings</legend>
	<p align="center">Edit a project WBS by clicking on the corresponding 'Edit' button.<br>
WBS Order determines the order a WBS will appear in tables and graphs.<br>
Deleting a WBS will remove all associated tasks and history.</p>
	<table align="center" class="list">
		<tr>
			<th>Click to Edit</th>
			<th HIDDEN>WBS ID</th>
			<th>WBS Name</th>
			<th>WBS Order</th>
		</tr>
<?
$strSQL2 = "SELECT wbs_name, wbs_id, wbs_order FROM $TABLE_WBS WHERE project_id ='$strProject' ORDER BY wbs_order ASC";
$result2 = dbquery($strSQL2);
$rowClass = 0;
while($row2 = mysql_fetch_array($result2)){
	$wbsName = $row2['wbs_name'];
	$wbsID = $row2['wbs_id'];
	$wbsOrder = $row2['wbs_order'];
?>
		<tr class="row<?=$rowClass;?>" id="<?=$wbsID;?>">
			<td align="center"><input type="button" class="but" value="Edit" onclick="updateWbs(<?=$wbsID;?>)" <?=$disable;?>></td>
			<td align="center" HIDDEN><?=$wbsID;?></td>
			<td><?=$wbsName;?></td>
			<td align="right"><?=$wbsOrder;?></td>
		</tr>
<?
	$rowClass = (++$rowClass)%2; //Alternate row class
}

?>
	</table>
<br><br>
	<form action="<?=$PAGE_SETTINGS;?>" method="POST">
	<table align="center" class="list">
		<tr>
			<th colspan="3">Add New WBS</th>
		</tr>
		<tr>
			<td>WBS Name</td>
			<td>WBS Order</td>
		</tr>
		<tr>
			<td><input type="text" value="" id="newWBS" name="newWBS" <?=$disable;?>></td>
			<td><input type="text" value="" id="newOrder" onChange="numericalWBSOrder(event)" name="newOrder" <?=$disable;?>></td>
			<td><input class="but" type="submit" value="Add" name="new_wbs" <?=$disable;?>></td>
		</tr>
	</table>
	</form>
<br>
</fieldset>

<fieldset>
	<legend>Project Access</legend>
	<p align="center">Edit the people who can access your project.<br>
Project Admins have full control over all actions associated with a project<br>
Project Users can only edit tasks that are assigned to them and view some project information<br>
Setting access to none will remove that user from the project.
	</p>
	<table align="center" class="list">
		<tr>
			<th colspan="3">Persons Assigned to this Project</th>
		</tr>
		<tr>
			<th>Username</th>
			<th>Name</th>
			<th>Access Level</th>
		</tr>
<?
$strSQL3 = "SELECT person_id, is_admin";
$strSQL3.= " FROM $TABLE_PROJECT_ACCESS";
$strSQL3.= " WHERE project_id='$strProject'";
$strSQL3.= " ORDER BY is_admin DESC";
$result3 = dbquery($strSQL3);
$rowClass = 0;
while($row3 = mysql_fetch_array($result3)){
	$personID = $row3['person_id'];
	$admin = $row3['is_admin'];
	
	$strSQL4 = "SELECT first, last, username";
	$strSQL4.= " FROM $TABLE_PERSON";
	$strSQL4.= " WHERE person_id ='$personID'";
	$result4 = dbquery($strSQL4);

	$row4 = mysql_fetch_array($result4);
	$firstName = $row4['first'];
	$lastName = $row4['last'];
	$userName = $row4['username'];

	echo "\t\t<tr class=row$rowClass>\n\t\t\t<td>$userName</td>\n\t\t\t<td align=center>$lastName, $firstName</td>\n";

	if($admin){
		echo "\t\t\t<td align=center>Project Admin</td>\n\t\t</tr>\n";
	}else{
		echo "\t\t\t<td align=center>User</td>\n\t\t</tr>\n";
	}
	$rowClass = (++$rowClass)%2; //Alternate row class
}
?>
	</table>
<br><br>
<form action="<?=$PAGE_SETTINGS;?>" onsubmit="" method="POST">
<input type="hidden" value="<?=$str_project;?>" name="txt_project">
<input type="hidden" value="access" name="txt_eda">
<input type="hidden" value="Do Now" name="txt_action">
	<table align="center">
		<tr>
			<td colspan="3" align="center">Change Access</td>
		</td>
		<tr>
			<td colspan="3" align="center">
				<select name="txt_who">
<?
$str_project = $strProject;
$strSQL5 = "SELECT P.person_id AS pid,P.first,P.last,PA.is_admin,PA.project_access_id FROM $TABLE_PERSON AS P";
$strSQL5.= " LEFT JOIN $TABLE_PROJECT_ACCESS AS PA ON P.person_id=PA.person_id AND PA.project_id='$str_project'";
$strSQL5.= " ORDER BY P.last ASC";
$result5 = dbquery($strSQL5);

while($row5 = mysql_fetch_array($result5))
{
  $t1=$row5['pid'];
  $t2=$row5['first'];
  $t3=$row5['last'];
  $t4=$row5['is_admin']?" (Admin)":"";
  $t5=$row5['project_access_id']?" (User)":" (None)";
  $t6=$t4?$t4:$t5;
  echo "\t\t\t\t\t<option value='$t1'>$t3, $t2$t6</option>\n";
}


?>
				</select>
			</td>
		</tr>
		<tr>
			<td><input class="but" type="submit" name="txt_update" value="Access-Admin"></td>
			<td><input class="but" type="submit" name="txt_update" value="Access-User"></td>
			<td><input class="but" type="submit" name="txt_update" value="Access-None"></td>
		</tr>
	</table>
</form>
</fieldset>
<?
}//End display of page if $strProject is set


if($strProject && !($projectAdmin || $dbAdmin))
{
  show_permission_error(); //If user does not have the proper permissions
}


projectSelector($PAGE_SETTINGS, $strProject);
show_footer();
?>
</div>