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


set_todo("complete adding the events as meetings or documents.");
set_todo("add ability to edit an event.");
set_todo("add graph that shows events.");









/******************************************/
/* COMPUTING - NO OUTPUT - START          */
/******************************************/
$str_project=isset($_FORM['txt_project'])?postedData($_FORM['txt_project']):"";
$strName = "";
/* only allow a non-admin to look at their own projects */
//if(!$_SESSION['dbAdmin'])
if(isset($_FORM['txt_eupdate']) && $_FORM['txt_eupdate'] == "Add")
{
  $strEvent = isset($_FORM['txt_event'])?postedData($_FORM['txt_event']):"";
  $strEventLabel = $strEvent>=0?$EVENT_TYPES[$strEvent][0]:"Other";
  $strEventType = $strEvent>=0?$EVENT_TYPES[$strEvent][1]:"Other";
  $strEDate = isset($_FORM['txt_edate'])?date_save(postedData($_FORM['txt_edate'])):"";
  $strOther = isset($_FORM['txt_other'])?postedData($_FORM['txt_other']):"";
  debug(10,"action to add an event ($strEvent:$strEventLabel:$strEventType, $strEDate, $strOther)");
  $strSQL1 = "INSERT INTO $TABLE_EVENT SET";
  $strSQL1.= " event_type='$strEvent'";
  $strSQL1.= ",project_id='$str_project'";
  $strSQL1.= ",event_pdate='$strEDate'";
  $strSQL1.= ",other='$strOther'";
  $strSQL1.= ",event_edate=NULL";
  $strSQL1.= ",event_adate=NULL";
  $strSQL1.= ",is_doc='$strEventType'";
  $result1 = dbquery($strSQL1);
  set_status("Success: Event stored.");
}
if(isset($_FORM['txt_action']) && $_FORM['txt_action'] == "Create" && $_SESSION['dbAdmin'])
{
  $strName = isset($_FORM['txt_name'])?postedData($_FORM['txt_name']):"";
  $strSDate = isset($_FORM['txt_sdate'])?date_save(postedData($_FORM['txt_sdate'])):"";
  debug(10,"IS_DB_ADMIN && CREATE ACTION POSTED (N:$strName, SD:$strSDate)");
  $err=0;
  if(!strlen($strName)){$err=1;set_error("Project name must be defined.");}
  if(!$err)
  {
    /* find out if the project already exists */
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
      set_status("Success: Project created.($strName)");
      /* remove these from the new create box - as we are creating this project now! */
      $strName = "";
    }
  }
}



$str_eda=isset($_FORM['txt_eda'])?postedData($_FORM['txt_eda']):"";
$str_project=isset($_FORM['txt_project'])?postedData($_FORM['txt_project']):"";
debug(3,"Action to edit, delete project ($str_eda) for ($str_project)");
if(isset($_FORM['txt_action']) && $_FORM['txt_action'] == "Do Now" && $str_eda == "delete")
{
  $str_delconfirm=isset($_FORM['txt_delconfirm'])?postedData($_FORM['txt_delconfirm']):"";
  if(!$str_delconfirm)
  {
    set_error("You must confirm the delete action.  No action performed.");
  }
  else
  {
    $strSQL0 = "DELETE FROM $TABLE_PROJECT WHERE project_id='$str_project'";
    $result0 = dbquery($strSQL0);
    set_status("Project deleted.");
    $strSQL0 = "DELETE FROM $TABLE_PROJECT_ACCESS WHERE project_id='$str_project'";
    $result0 = dbquery($strSQL0);
    set_status("Project Accesses for this project deleted.");
    set_todo("must go find all WBSes for this project, and delete tables that have that WBS: wbs-to-task, tasks,wbs-history");
    $strSQL0 = "DELETE FROM $TABLE_WBS WHERE project_id='$str_project'";
    $result0 = dbquery($strSQL0);
    set_status("WBSs for this project deleted.");
  }
}
  if(isset($_FORM['txt_action']) && $_FORM['txt_action'] == "Change")
  {
    $tmpProject   = postedData($_FORM['txt_project']);
    $tmpName      = postedData($_FORM['txt_name']);
    $tmpSDate     = isset($_FORM['txt_sdate'])?date_save(postedData($_FORM['txt_sdate'])):"";
    debug(3,"IS_DB_ADMIN && CHANGE ACTION POSTED (P:$tmpProject, N:$tmpName)");
    $strSQL4 = "UPDATE $TABLE_PROJECT SET";
    $strSQL4.= " project_name='$tmpName'";
    $strSQL4.= ",start_date='$tmpSDate'";
    $strSQL4.= " WHERE project_id='$tmpProject'";
    $result4 = dbquery($strSQL4);
    set_status("Project Updated.");
  }

$str_update=isset($_FORM['txt_update'])?postedData($_FORM['txt_update']):"";
$str_who=isset($_FORM['txt_who'])?postedData($_FORM['txt_who']):"";
if(isset($_FORM['txt_update']) && $_FORM['txt_update'] == "Access-Admin")
{
  debug(3, "Set Access-Admin. Project($str_project), Person($str_who)");
  $strSQL4 = "DELETE FROM $TABLE_PROJECT_ACCESS";
  $strSQL4.= " WHERE project_id='$str_project' AND person_id='$str_who'";
  $result4 = dbquery($strSQL4);
  $strSQL4 = "INSERT INTO $TABLE_PROJECT_ACCESS SET";
  $strSQL4.= " project_id='$str_project'";
  $strSQL4.= ",person_id='$str_who'";
  $strSQL4.= ",is_admin='1'";
  $result4 = dbquery($strSQL4);
  set_status("Set user to ADMIN for Project Access.");
}
if(isset($_FORM['txt_update']) && $_FORM['txt_update'] == "Access-User")
{
  debug(3, "Set Access-User. Project($str_project), Person($str_who)");
  $strSQL4 = "DELETE FROM $TABLE_PROJECT_ACCESS";
  $strSQL4.= " WHERE project_id='$str_project' AND person_id='$str_who'";
  $result4 = dbquery($strSQL4);
  $strSQL4 = "INSERT INTO $TABLE_PROJECT_ACCESS SET";
  $strSQL4.= " project_id='$str_project'";
  $strSQL4.= ",person_id='$str_who'";
  $strSQL4.= ",is_admin='0'";
  $result4 = dbquery($strSQL4);
  set_status("Set user to USER for Project Access.");
}
if(isset($_FORM['txt_update']) && $_FORM['txt_update'] == "Access-None")
{
  debug(3, "Set Access-None. Project($str_project), Person($str_who)");
  $strSQL4 = "DELETE FROM $TABLE_PROJECT_ACCESS";
  $strSQL4.= " WHERE project_id='$str_project' AND person_id='$str_who'";
  $result4 = dbquery($strSQL4);
  set_status("Set user to NONE for Project Access.");
}






$str_wbs=isset($_FORM['txt_wbs'])?postedData($_FORM['txt_wbs']):"";
$str_newwbs=isset($_FORM['txt_newwbs'])?postedData($_FORM['txt_newwbs']):"";
$str_wbsorder=isset($_FORM['txt_wbsorder'])?postedData($_FORM['txt_wbsorder']):0;
//echo "WBS($str_wbs) New-wbs($str_newsbs)<BR>\n";
if(isset($_FORM['txt_update']) && $_FORM['txt_update'] == "Delete-WBS" && strlen($str_wbs))
{
  //echo "TEST1";
  debug(10, "Delete-WBS. Project($str_project), WBS($str_wbs)");
  $strSQL4 = "DELETE FROM $TABLE_WBS";
  $strSQL4.= " WHERE project_id='$str_project' AND wbs_id='$str_wbs'";
  $result4 = dbquery($strSQL4);
  set_status("Deleted WBS from Project.");

  $strSQL4 = "DELETE FROM $TABLE_WBS_TO_TASK";
  $strSQL4.= " WHERE wbs_id='$str_wbs'";
  $result4 = dbquery($strSQL4);
  set_status("Deleted tasks from deleted WBS WBS.");
}
if(isset($_FORM['txt_update']) && $_FORM['txt_update'] == "New-WBS" && strlen($str_newwbs))
{
  //echo "TEST2";
  debug(3, "New-WBS. Project($str_project), NEW-WBS($str_newwbs)");
  $strSQL4 = "INSERT INTO $TABLE_WBS SET";
  $strSQL4.= " project_id='$str_project'";
  $strSQL4.= ",wbs_name='$str_newwbs'";
  $strSQL4.= ",wbs_order='$str_wbsorder'";
  $result4 = dbquery($strSQL4);
  set_status("Created new WBS for Project.");
}

/******************************************/
/* COMPUTING - NO OUTPUT - END            */
/******************************************/

/******************************************/
/* COMPUTING - SHOW OUTPUT - START        */
/******************************************/
$TITLE="Project Edits";
show_header();
show_menu("PROJECTS");
show_status();
show_error();

?>
<script>
  var ffield_id;
  function addCal(field_id)
  {
    document.write('<a href="calendar.php" target="_blank" style="margin:2px;" onClick="return showCal(' + field_id + ');"><img height="16" width="16" src="i/calendar.gif" border="0"></a>');
  }
  function showCal(field_id)
  {
    ffield_id = field_id;
    _obj=document.getElementById(ffield_id);
    _parm="";
    if(_obj){ _value=_obj.value;_t=_value.split("/");_mon=_t[0];_year=_t[2];_parm="?monthno="+_mon+"&year="+_year;}


    window.open('calendar.php'+_parm, 'popcalendar','height=200,width=200,scrollbars=no,resizable=no,menubar=no,toolbar=no,location=no');
    return false;
  }
  function calendarPopupClick(value)
  {
    status = value;
    _obj=document.getElementById(ffield_id);
    if(_obj) _obj.value = value;
  }
</script>

<?
echo "<a href=$PAGE_SHOW?txt_graph=graph-bulleye.php&txt_small=0>";
echo "<img border=0 src=graph-bulleye.php?txt_small=1>";
echo "</a>&nbsp;";
if($str_project)
{
echo "<a href=$PAGE_SHOW?txt_graph=graph-g2.php&txt_project=$str_project&txt_small=0>";
echo "<img border=0 src=graph-g2.php?txt_project=$str_project&txt_small=1>";
echo "</a>";
}
?>




<fieldset>
<legend><b>Notes</b></legend>
Access Levels:
<ul>
<li>DB Admin - User can do anything, except delete own account</li>
<li>Project Admin - User can do anything to that specific project</li>
<li>Project Access - User can manipulate tasks assigned to them, view rest of tasks (no edit), and view all graphs</li>
</ul>
</fieldset>
<?
if(isset($_FORM['txt_action']) && $_FORM['txt_action'] == "Do Now" && $str_eda == "wbs")
{
  /* look up the project name */
  $strSQL6 = "SELECT project_name,start_date FROM $TABLE_PROJECT WHERE project_id='$str_project'";
  $result6 = dbquery($strSQL6);
  $row6 = mysql_fetch_array($result6);
  $str_projectName=$row6['project_name'];
  /* do the dbquery outside the select list so the debug will appear */
  $strSQL3 = "SELECT * FROM $TABLE_WBS WHERE project_id='$str_project' ORDER BY wbs_order ASC, wbs_name ASC";
  $result3 = dbquery($strSQL3);
?>
<BR>
<form action="<?=$PAGE_PROJECT;?>" method=POST>
<input type=hidden value='<?=$str_project;?>' name=txt_project>
<input type=hidden value='wbs' name=txt_eda>
<input type=hidden value='Do Now' name=txt_action>
<fieldset>
<legend><b>Project WBSes: <?=$str_projectName;?></b></legend>
<table>
<tr><td>
<select name=txt_wbs>
<?
while($row3 = mysql_fetch_array($result3))
{
  $t1=$row3['wbs_id'];
  $t2=$row3['wbs_name'];
  $t3=$row3['wbs_order'];
  echo "<option value='$t1'>$t2 ($t3)</option>";
}
?>
</select>
</td>
<td><input class=but type=submit name=txt_update value="Delete-WBS"></form></td></tr>
<tr><td>
<form action="<?=$PAGE_PROJECT;?>" method=POST>
<input type=hidden value='<?=$str_project;?>' name=txt_project>
<input type=hidden value='wbs' name=txt_eda>
<input type=hidden value='Do Now' name=txt_action>
<input type=text name=txt_newwbs></td>
<td>Order:<input type=text name=txt_wbsorder></td>
<td><input class=but type=submit name=txt_update value="New-WBS"></td></tr>
</table>
</fieldset>
</form>
<?
} /* end if for "wbs" setting */







if(isset($_FORM['txt_action']) && $_FORM['txt_action'] == "Do Now" && $str_eda == "events2")
{
  $str_which=isset($_FORM['txt2_which'])?postedData($_FORM['txt2_which']):"";
  $str_id=isset($_FORM['txt2_db_id'.$str_which])?postedData($_FORM['txt2_db_id'.$str_which]):"";
  $str_pdate=isset($_FORM['txt2_pdate'.$str_which])?postedData(date_save($_FORM['txt2_pdate'.$str_which])):"";
  $str_adate=isset($_FORM['txt2_adate'.$str_which])?postedData(date_save($_FORM['txt2_adate'.$str_which])):"";
  $str_edate=isset($_FORM['txt2_edate'.$str_which])?postedData(date_save($_FORM['txt2_edate'.$str_which])):"";
  $str_del1=isset($_FORM['txt2_del1'.$str_which])?postedData($_FORM['txt2_del1'.$str_which]):0;
  $str_del2=isset($_FORM['txt2_del2'.$str_which])?postedData($_FORM['txt2_del2'.$str_which]):0;
  debug(10,"EVENTS2($str_id): which:$str_which, PD($str_pdate), AD($str_adate), ED($str_edate), D1($str_del1), D2($str_del2)");
  if($str_del1 && $str_del2)
  {
    $strSQL0 = "DELETE FROM $TABLE_EVENT WHERE event_id='$str_id'";
    $result0 = dbquery($strSQL0);
    set_status("Event deleted.");
  }
  else
  {
    $strSQL4 = "UPDATE $TABLE_EVENT SET";
    $strSQL4.= " event_pdate='$str_pdate'";
    if($str_edate)$strSQL4.= ",event_edate='$str_edate'";else $strSQL4.= ",event_edate=NULL";
    if($str_adate)$strSQL4.= ",event_adate='$str_adate'";else $strSQL4.= ",event_adate=NULL";
    $strSQL4.= " WHERE event_id='$str_id'";
    $result4 = dbquery($strSQL4);
    set_status("Event Updated.");
  }

  $str_eda="events"; /* now show the events menu ... */
}
if(isset($_FORM['txt_action']) && $_FORM['txt_action'] == "Do Now" && $str_eda == "events")
{
  /* look up the project name */
  $strSQL6 = "SELECT project_name FROM $TABLE_PROJECT WHERE project_id='$str_project'";
  $result6 = dbquery($strSQL6);
  $row6 = mysql_fetch_array($result6);
  $str_projectName=$row6['project_name'];
?>
<form action="<?=$PAGE_PROJECT;?>" method=POST>
<input type=hidden value='<?=$str_project;?>' name=txt_project>
<input type=hidden value='events' name=txt_eda>
<input type=hidden value='Do Now' name=txt_action>
<fieldset>
<legend><b>Project Events: <?=$str_projectName;?></b></legend>
<table>
<tr><td>
<select name=txt_event>
<?
for($i=0;$i<count($EVENT_TYPES);$i++)
{
  $tmp=$EVENT_TYPES[$i];
  $tmp0=$tmp[0];
  $tmp1=$tmp[1]?"DOCUMENT":"MEETING";
  echo "<option value='$i'>$tmp0($tmp1)</option>";
}
unset($tmp);unset($tmp0);unset($tmp1);unset($i);
?>
<option value='-1'>Other</option>
</select> 
<input type=text name=txt_other>
<input onclick='showCal(this.id)' id='edate1' type=text name=txt_edate value="<?=date("n/j/y");?>">
</td><td><input class=but type=submit name=txt_eupdate value="Add"></td></tr>
<tr><td colspan=2>Note: The text field is the label to apply if you select <i>Other</i>.</td></tr>
</table>
</form>
<BR>
<form action="<?=$PAGE_PROJECT;?>" method=POST>
<input type=hidden value='<?=$str_project;?>' name=txt_project>
<input type=hidden value='events2' name=txt_eda>
<input type=hidden value='Do Now' name=txt_action>
<input type=hidden value='' name=txt2_which>
<?
/* find all events already entered - ask for editing or deleting */
$strSQL6 = "SELECT * FROM $TABLE_EVENT WHERE project_id='$str_project' ORDER BY event_pdate ASC";
$result6 = dbquery($strSQL6);
echo "<table class='wrap'><tr><th>Label</th><th>Planed Date</th><th>Estimated Date</th><th>Actual Date</th><th>Update</th><th>Delete</th></tr>";
$row=0;
$x=-1;
while($row6 = mysql_fetch_array($result6))
{
  $x++;
  $row=($row+1)%2;
  $t_eventType=$row6['event_type'];
  $t_eventID=$row6['event_id'];
  $t_event_pdate=date_read($row6['event_pdate']);
  $t_event_edate=date_read($row6['event_edate']);
  $t_event_adate=date_read($row6['event_adate']);
  $t_isdoc=$row6['is_doc']?"Document":"Meeting";
  $t_other=$row6['other'];
  $label=$t_other;
  if($t_eventType!=-1)$label=$EVENT_TYPES[$t_eventType][0];
  echo "<tr class=row$row>";
  echo "<td>$label<input type=hidden name=txt2_db_id$x value='$t_eventID'></td>";
  echo "<td class=date><input onclick='showCal(this.id)' id='pdate$x' type=text name=txt2_pdate$x value='$t_event_pdate'></td>";
  echo "<td class=date><input onclick='showCal(this.id)' id='edate$x' type=text name=txt2_edate$x value='$t_event_edate'></td>";
  echo "<td class=date><input onclick='showCal(this.id)' id='adate$x' type=text name=txt2_adate$x value='$t_event_adate'></td>";
  echo "<td><input type=button name=txt2_eupdate value='Update' onclick='this.form.txt2_which.value=\"$x\";this.form.submit();'></td>";
  echo "<td><input type=checkbox name=txt2_del1$x value=1><input type=checkbox name=txt2_del2$x value=1></td>";
  echo "</tr>\n";
}
echo "</table>\n";
echo "<i>Note: Each 'update' affects only that row</i>\n";
echo "</form>";
echo "</fieldset>";

} /* end if for "events" form building */
if(isset($_FORM['txt_action']) && $_FORM['txt_action'] == "Do Now" && $str_eda == "access")
{
  /* look up the project name */
  $strSQL6 = "SELECT project_name FROM $TABLE_PROJECT WHERE project_id='$str_project'";
  $result6 = dbquery($strSQL6);
  $row6 = mysql_fetch_array($result6);
  $str_projectName=$row6['project_name'];
  /* do the dbquery outside the select list so the debug will appear */
  $strSQL3 = "SELECT P.person_id AS pid,P.first,P.last,PA.is_admin,PA.project_access_id FROM $TABLE_PERSON AS P";
  $strSQL3.= " LEFT JOIN $TABLE_PROJECT_ACCESS AS PA ON P.person_id=PA.person_id AND PA.project_id='$str_project'";
  $result3 = dbquery($strSQL3);
?>
<BR>
<form action="<?=$PAGE_PROJECT;?>" method=POST>
<input type=hidden value='<?=$str_project;?>' name=txt_project>
<input type=hidden value='access' name=txt_eda>
<input type=hidden value='Do Now' name=txt_action>
<fieldset>
<legend><b>Project Access: <?=$str_projectName;?></b></legend>
<table>
<tr><td>
<select name=txt_who>
<?
while($row3 = mysql_fetch_array($result3))
{
  $t1=$row3['pid'];
  $t2=$row3['first'];
  $t3=$row3['last'];
  $t4=$row3['is_admin']?" (Admin)":"";
  $t5=$row3['project_access_id']?" (User)":" (None)";
  $t6=$t4?$t4:$t5;
  echo "<option value='$t1'>$t3, $t2$t6</option>";
}
?>
</select>
</td></tr>
<tr><td><input class=but type=submit name=txt_update value="Access-Admin"></td></tr>
<tr><td><input class=but type=submit name=txt_update value="Access-User"></td></tr>
<tr><td><input class=but type=submit name=txt_update value="Access-None"></td></tr>
</table>
</fieldset>
</form>
<?
} /* end if for "access" form building */


  if(isset($_FORM['txt_action']) && $_FORM['txt_action'] == "Do Now" && $str_eda == "edit")
  {
        /* do the dbquery outside the select list so the debug will appear */
        $strSQL3 = "SELECT * FROM $TABLE_PROJECT WHERE project_id='$str_project'";
        $result3 = dbquery($strSQL3);
        $row3 = mysql_fetch_array($result3);
        $tmpName=$row3['project_name'];
        $tmpSDate=date_read($row3['start_date']);
?>
<BR>
<form action="<?=$PAGE_PROJECT;?>" method=POST>
<input type=hidden value='<?=$str_project;?>' name=txt_project>
<fieldset>
<legend><b>Edit Project</b></legend>
<table align=center>
<tr><td>Name</td><td><input type=text name=txt_name value='<?=$tmpName;?>'></td></tr>
<tr><td>Start Date:</td><td class=date><input onclick='showCal(this.id)' id='sdate2' type=text name=txt_sdate value="<?=$tmpSDate;?>"></td></tr>
<tr><td colspan=1><input class=but type=submit name=txt_action value="Change"></td></tr>
</table>
</fieldset>
</form>
<?
  }
//Setup variables for admin status
$dbadmin=0;
$projectadmin=0;

if($_SESSION['dbAdmin'])
{
  /* do the dbquery outside the select list so the debug will appear */
  $strSQL2 = "SELECT project_name, project_id FROM $TABLE_PROJECT ORDER BY project_name ASC, project_id ASC";
  $dbadmin=1;
}
else
{
  $strSQL2 = "SELECT P.project_id, P.project_name, PA.is_admin FROM $TABLE_PROJECT_ACCESS AS PA";
  $strSQL2.= " LEFT JOIN $TABLE_PROJECT AS P ON P.project_id=PA.project_id";
  $strSQL2.= " WHERE PA.person_id='".$_SESSION['userID']."'";
  $strSQL2.= " ORDER BY P.project_name ASC, P.project_id ASC";
  $dbadmin=0;
}
  $result2 = dbquery($strSQL2);
?>
<BR>
<form action="<?=$PAGE_PROJECT;?>" method=POST>
<fieldset>
<legend><b>Modify Project</b></legend>
<table align=center>
<tr><td>Pick A Project</td><td><select name=txt_project>
<?
  while($row2 = mysql_fetch_array($result2))
  {
    if($dbadmin || $row2['is_admin'])
    {		
    $name=$row2['project_name'];
    echo "<option value='".$row2['project_id']."'>$name</option>\n";
    $projectadmin=1;
    }
  }
//If user is a project admin or db admin, show the following actions
if($projectadmin || $dbadmin)
{
?>
</select></td></tr>
<tr><td>Pick an Action</td><td><select name=txt_eda>
<option value='edit'>Edit</option>
<option value='delete'>Delete</option>
<option value='wbs'>WBS</option>
<option value='access'>Access</option>
<option value='events'>Events</option>
</select></td></tr>
<tr><td align=center colspan=2>You must check this box to confirm a delete action: <input type=checkbox value=1 name=txt_delconfirm></td></tr>
<tr><td align=center colspan=2><input class=but type=submit value='Do Now' name='txt_action'></td></tr>
<?
}
?>
</table>
<center><h5>Note: A project name will appear if you are set as an administrator for that project.</h5></center>
</fieldset>
<?
//Only DB Admins can create new projects
if($_SESSION['dbAdmin'])
{
?>
<BR>
<form action="<?=$PAGE_PROJECT;?>" method=POST>
<fieldset>
<legend><b>Create New Project</b></legend>
<table align=center>
<tr><td>Name:</td><td><input type=text name=txt_name value=''></td></tr>
<tr><td>Start Date:</td><td class=date><input onclick='showCal(this.id)' id='sdate' type=text name=txt_sdate value=""></td></tr>
<tr><td colspan=2 align=center><input class=but type=submit name=txt_action value="Create"></td></tr>
</table>
</fieldset>
</form>
<?
}

show_footer();


/******************************************/
/* COMPUTING - SHOW OUTPUT - END          */
/******************************************/


?>
