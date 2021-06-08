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
debug(10,"Loading File: projects.php");
debug(10,"User Level is DB-Admin (".$_SESSION['dbAdmin'].")");
checkPermissions($SESSION_TIMEOUT); // if not logged in, or session has timed out...
/******************************************/
/* HEADER: INCLUDE/SECURITY CHECK - END   */
/******************************************/

/********************************************
*           AJAX HANDLER                    *
*********************************************/
if(isset($_POST['action']) && $_SESSION['dbAdmin']){
   switch($_POST['action']){
      case "inactive":
         setactiveProject(0);     //inactivate project
         echo displayActiveProjects();
         break;
      case "active":
         setactiveProject(1);     //activate project
         echo displayActiveProjects();
         break;
     case "active_all":
         setactiveProject(2);     //activate all projects
         echo displayActiveProjects();
         break;
     case "restore":
         restore();     //activate all projects
         echo displayActiveProjects();
         break;
   }
   exit;
}
/********************************************
*           END AJAX HANDLER                    *
*********************************************/
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

if(isset($_FORM['txt_project']))
{
	$strSQL0 = "SELECT project_name FROM $TABLE_PROJECT WHERE project_id = $strProject";
	$result0 = dbquery($strSQL0);
	$row0 = mysql_fetch_array($result0);
	$name = $row0['project_name'];
	set_status("Project has been changed to $name (Id: $strProject)");
}
//Create new Project
if(isset($_FORM['txt_action']) && $_FORM['txt_action'] == "Create" && $_SESSION['dbAdmin'])
{
	$strName = isset($_FORM['projName'])?postedData($_FORM['projName']):"";
  	$strSDate = isset($_FORM['projDate'])?date_save(postedData($_FORM['projDate'])):0;
  	debug(10,"IS_DB_ADMIN && CREATE ACTION POSTED (N:$strName, SD:$strSDate)");
  	$err=0;
  	if(!strlen($strName))
	{
		$err=1;
		set_error("Project name must be defined.");
	}
  	if(!$err)
  	{
   		 //find out if the project already exists 
    		$strSQL0 = "SELECT project_name FROM $TABLE_PROJECT WHERE project_name='$strName'";
    		$result0 = dbquery($strSQL0);
    		$row0 = mysql_fetch_array($result0);
    		if($row0 && $row0['project_name'])
    		{
      			set_error("You can not create another project with the same name ($strName)");
    		}
    		else
    		{
      			$strSQL1 = "INSERT INTO $TABLE_PROJECT SET";
      			$strSQL1.= " project_name='$strName'";
      			$strSQL1.= ",start_date='$strSDate'";
      			$result1 = dbquery($strSQL1);
      			set_status("Success: Project created ($strName).");
      			//remove these from the new create box - as we are creating this project now!
      			$strName = "";
    		}
  	}
}//end create project


//Delete a Project
if(isset($_FORM['txt_action']) && $_FORM['txt_action'] == "deleteProjectForm" && $_SESSION['dbAdmin'])
{
  	$projId = isset($_FORM['proj_id'])?validInt($_FORM['proj_id']):"";
  	$projName = isset($_FORM['proj_name'])?postedData($_FORM['proj_name']):"";

	if($projId == -1)
	{
	}
  	elseif($projId < 0)
  	{
   		set_error("There is a problem with deleting a project, contact a PAEV tool admin");
  	}
  	else
  	{
    		//First we make sure that the project exists
    		$strSQL0 = "SELECT project_name FROM $TABLE_PROJECT";
		$strSQL0.= " WHERE project_name='$projName' and project_id='$projId'";
    		$result0 = dbquery($strSQL0);
    		$row0 = mysql_fetch_array($result0);
    		if($row0 && $row0['project_name'])
    		{
			//Delete the Access List for the Project
			$strSQL1 = "DELETE FROM $TABLE_PROJECT_ACCESS WHERE project_id='$projId'";
    			$result1 = dbquery($strSQL1);
    			set_status("Project Access Permissions Deleted.");

			//Next, Find all WBSes associated with the project and delete all related data
			//prior to deleting the WBSes
			$strSQL2 = "SELECT wbs_id FROM $TABLE_WBS WHERE project_id='$projId'";
			$result2 = dbquery($strSQL2);
			while($row2 = mysql_fetch_array($result2))
			{
				$wbsId = $row2['wbs_id'];
		
				//Delete associated WBS history records
				$strSQL3 = "DELETE FROM $TABLE_WBS_HISTORY WHERE wbs_id='$wbsId'";
				$result3 = dbquery($strSQL3);
				set_status("WBS $wbsId History Records Deleted");

				//Pull all wbs_to_task_id records so their references can be deleted from the person_to_wbstask table
				$strSQL4 = "SELECT wbs_to_task_id FROM $TABLE_WBS_TO_TASK WHERE wbs_id='$wbsId'";
				$result4 = dbquery($strSQL4);
				while($row4 = mysql_fetch_array($result4))
				{
					$wbsToTaskId = $row4['wbs_to_task_id'];
			
					$strSQL5 = "DELETE FROM $TABLE_PERSON_TO_WBSTASK WHERE wbs_to_task_id='$wbsToTaskId'";
					$result5 = dbquery($strSQL5);
					set_status("Deleted Person-WBS Task Records");
				}

				//Delete wbs_to_task records
				$strSQL6 = "DELETE FROM $TABLE_WBS_TO_TASK WHERE wbs_id='$wbsId'";
				$result6 = dbquery($strSQL6);
				set_status("Deleted WBS Tasks");
			}

			//Delete all WBSes associated with a project
			$strSQL7 = "DELETE FROM $TABLE_WBS WHERE project_id='$projId'";
			$result7 = dbquery($strSQL7);
			set_status("Project $projId WBSes Deleted");

			//Delete all Tasks associated with a project
			$strSQL8 = "DELETE FROM $TABLE_TASK WHERE project_id='$projId'";
			$result8 = dbquery($strSQL8);
			set_status("Project $projId Tasks Deleted");

			//Delete all Events associated with a project
			$strSQL9 = "DELETE FROM $TABLE_EVENT WHERE project_id='$projId'";
			$result9 = dbquery($strSQL9);
			set_status("Project $projId Events Deleted");

			//Delete Project
			$strSQL10 = "DELETE FROM $TABLE_PROJECT WHERE project_id='$projId'";
			$result10 = dbquery($strSQL10);
			set_status("Project $projId Deleted");

                        //Update the associated project, if there is one
                        $strSQL11 = "UPDATE pev__project SET associated_id=NULL WHERE associated_id='$projId' LIMIT 1";
                        $result11 = dbquery($strSQL11);
                        set_status("Removed project association");

    		}
    		else
    		{
			set_error("No such project exists ($strName)");
    		}
  	}
}//end project delete

function setActiveProject($option){
   //Activate project -> $option = 1
   //Inactivate project -> $option = 0
   if($option == 2){
      $strSQL = "UPDATE pev__project SET active=1 WHERE project_id > 0";
      dbquery($strSQL);
      return;
   }

   $project_id = validInt($_POST['project_id']);
   $strSQL = "UPDATE pev__project SET active=$option WHERE project_id=$project_id";
   dbquery($strSQL);
}

function restore(){

   //First, deactivate all projects
   $inactivate = "UPDATE pev__project SET active=0 WHERE project_id > 0";
   dbquery($inactivate);

   $threshold = date("U")-(3600*24*60); //today minus 60 days

   //Find all projects that have history activity within last 60 days
   $getActive = "SELECT project_id FROM pev__wbs AS w";
   $getActive.= " LEFT JOIN pev__wbs_history AS wh ON wh.wbs_id=w.wbs_id";
   $getActive.= " WHERE wh_date > $threshold GROUP BY project_id";

   $result = dbquery($getActive);

   while($row = mysql_fetch_array($result)){
      $project_id = $row['project_id'];
      $setActive = "UPDATE pev__project SET active=1 WHERE project_id=$project_id";
      dbquery($setActive);
   }

}

function displayActiveProjects(){
   $active = false;   //These flags tell if there are any returned results
   $inactive = false;
   $str = "<td align='center' valign='top'><table><tr><th colspan='2'>Active Projects</th></tr>";
   $str.= "<tr><th>Set As Inactive</th><th>Project Name</th></tr>";
   $strSQL1 = "SELECT project_id, project_name FROM pev__project WHERE active=1 ORDER BY project_name";
   $result1 = dbquery($strSQL1);
   while($row1 = mysql_fetch_array($result1)){
      $active=true;
      $str.= "<tr><td align='center'><img src='images/edit_remove.png' onclick='inactive({$row1['project_id']});' /></td><td>{$row1['project_name']}</td></tr>";
   }
   if(!$active){
      $str.= "<tr><td colspan='2'>There are no active projects!</td></tr>";
   }
   $str.= "<tr><td colspan='2'><input id='restoreButton' class='but' type='button' value='Restore Active Default' onclick='restore()'></td></tr></table></td><td align='center' valign='top'><table><tr><th colspan='2'>Inactive Projects</th></tr>";
   $str.= "<tr><th>Set As Active</th><th>Project Name</th></tr>";
   $strSQL2 = "SELECT project_id, project_name FROM pev__project WHERE active=0 ORDER BY project_name";
   $result2 = dbquery($strSQL2);
   while($row2 = mysql_fetch_array($result2)){
      $inactive=true;
      $str.= "<tr><td align='center'><img src='images/edit_add.png' onclick='active({$row2['project_id']});'/></td><td>{$row2['project_name']}</td></tr>";
   }
   if(!$inactive){
      $str.= "<tr><td colspan='2'>There are no inactive projects!</td></tr>";
   }
   $str.= "<tr><td colspan='2'><input id='activeAllButton' class='but' type='button' value='Activate All' onclick='activeAll()'></td></tr></table></td>";
   return $str;
}



$TITLE = "PAEV Projects";
show_header();
show_menu("PROJECTS");
show_error();
show_status();
echo "<img src=images/logo-projects.png alt=\"Projects\">";



//Only a dbAdmin can create or delete a project
if($_SESSION['dbAdmin'])
{
?>
<script type="text/javascript" src="modalbox/lib/prototype.js"></script>
<script type="text/javascript" src="modalbox/lib/scriptaculous.js"></script>
<script type="text/javascript" src="modalbox/modalbox.js"></script>
<script type="text/javascript" src="scripts/projects.js"></script>
<link rel="stylesheet" href="modalbox/modalbox.css" type="text/css" media="screen">
<div style="position:relative; top:-290px">
<form name="deleteProjectForm" action="projects.php" action="POST">
<input type="hidden" name="txt_action" value="deleteProjectForm">
<input type="hidden" name="proj_id" >
<input type="hidden" name="proj_name" >
</form>

<fieldset>
	<legend>Status For All Programs</legend>
        <a href="<?=$PAGE_SHOW;?>?txt_graph=new-bullseye.php&txt_small=0">
	<img border=0 src="new-bullseye.php?txt_small=1">
	</a>&nbsp;

        <a href="<?=$PAGE_SHOW;?>?txt_graph=new-bullseye.php&txt_small=0&txt_active=month">
	<img border=0 src="new-bullseye.php?txt_small=1&txt_active=month">
	</a>
</fieldset>

<fieldset>
	<legend>Create New Project</legend>
	<form name="newProject">
	<table align="center" class="list">
		<tr>
			<th colspan="3">Create New Project</th>
		</tr>
		<tr>
			<th>Project Name</th>
			<th>Start Date</th>
			<th>Action</th>
		</tr>
		<tr>
			<td><input type="text" name="projName"></td>
			<td><input type="text" onclick="showCal(this.id)" name="projDate" id="projDate"></td>
			<td><input type="submit" class="but" name="txt_action" value="Create"></td>
		</tr>
	</table>
	<form>
	<br />
</fieldset>
<br />
<fieldset>
   <legend>Active/Inactive Projects</legend>
   <p align="center"> 
      <input id="activeButton" class="but" type="button" value="Show Active Projects Table" onclick="showActive()">
   </p>
   <table width="90%" align="center">
      <tr style="display: none" id="active_projects">
      <?=displayActiveProjects();?>
      </tr>
   </table>
</fieldset>
<?
$strSQL2 = "SELECT project_id, project_name FROM $TABLE_PROJECT WHERE associated_id IS NULL ORDER BY project_name ASC,project_id ASC";
$result2 = dbquery($strSQL2);

?>
<fieldset>
	<legend>Delete Project</legend>
	<p align="center"> 
		<input id="delButton" class="but" type="button" value="Show Delete Project Options" onclick="showTable()">
	</p>
	<form name="projectList">
	<table style="display: none" align="center" class="list" id="deleteProject">
		<tr>
			<th colspan=2>Delete Project</th>
		</tr>
		<tr>
			<th>Project Name</th>
			<th>Delete</th>
		</tr>
		<tr>
			<td><select id="projectSelect">
				<option value=-1>Select a project to delete</option>
<?
while($row2 = mysql_fetch_array($result2))
{
  $t1=$row2['project_id'];
  $t2=$row2['project_name'];
  echo "\t\t\t\t<option value='$t1'>$t2</option>\n";

}
?>
			</select></td>
			<td><input type="button" onclick="deleteProject();" class="but" value="Delete"></td>
		</tr>
	</table>
	<form>
	<br />
</fieldset>


<?
}

projectSelector($PAGE_PROJECTS, $strProject);

show_footer();
?>
</div>