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


/*This page uses events.js to gather the input which then fills in the hidden forms contained
on this page. The form is submitted automatically and the code immediately below validates and 
performs the requested action accordingly*/

/*This portion of code will extract data from the addEventForm and insert a new event
into the event table*/
if(isset($_FORM['txt_action']) && $_FORM['txt_action'] == "addEventForm")
{
	$strProjId = isset($_FORM['proj_id'])?validInt($_FORM['proj_id']):null;
	if($strProjId != $strProject)
  	{
     		set_error("There is a problem with your request. Please contact an admin.");
     		exit;
  	}
  	$strEvent = isset($_POST['txt_event'])?postedData($_POST['txt_event']):null;
  	$strEventLabel = $strEvent>=0?$EVENT_TYPES[$strEvent][0]:"Other";
  	$strEventType = $strEvent>=0?$EVENT_TYPES[$strEvent][1]:"Other";
  	$strPDate = isset($_POST['pdate'])?validInt(date_save($_POST['pdate'])):null;
  	$strEDate = isset($_POST['edate'])?validInt(date_save($_POST['edate'])):null;
  	$strADate = isset($_POST['adate'])?validInt(date_save($_POST['adate'])):null;
  	if($strEvent == -1)
  	{
    		$strOther = isset($_POST['txt_other'])?postedData($_POST['txt_other']):"";
  	}
  	debug(10,"action to add an event ($strEvent:$strEventLabel:$strEventType, $strPDate, $strEDate, $strADate, $strOther)");
  	$strSQL4 = "INSERT INTO $TABLE_EVENT SET";
  	$strSQL4.= " event_type='$strEvent'";
  	$strSQL4.= ",project_id=$strProjId";
  	$strSQL4.= ",event_pdate=$strPDate";
  	if(isset($strOther))
  	{
   		$strSQL4.= ",other='$strOther'";
  	}
  	$strSQL4.= ",event_edate=$strEDate";
  	$strSQL4.= ",event_adate=$strADate";
  	$strSQL4.= ",is_doc=$strEventType";
  	$result4 = dbquery($strSQL4);

  	set_status("Event Added");
}

/*Update event table*/
if(isset($_FORM['txt_action']) && $_FORM['txt_action'] == "updateEventForm")
{
	$str_id=isset($_FORM['event_id'])?validInt($_FORM['event_id']):null;
	$str_pdate=isset($_FORM['pdate'])?validInt(date_save($_FORM['pdate'])):null;
	$str_adate=isset($_FORM['adate'])?validInt(date_save($_FORM['adate'])):null;
	$str_edate=isset($_FORM['edate'])?validInt(date_save($_FORM['edate'])):null;

	debug(10,"EVENTS($str_id): PD($str_pdate), AD($str_adate), ED($str_edate)");
	if(isset($str_id) && ($_FORM['del'] == "delete"))
  	{
    		$strSQL4 = "DELETE FROM $TABLE_EVENT WHERE event_id=$str_id";
    		$result4 = dbquery($strSQL4);

    		set_status("Event deleted.");
  	}
  	elseif(isset($str_id) && ($_FORM['del'] == "update"))
  	{
    		$strSQL4 = "UPDATE $TABLE_EVENT SET";
		if(isset($str_pdate))
			$strSQL4.= " event_pdate=$str_pdate";
		else 
			$strSQL4.= ",event_pdate=NULL";

    		if(isset($str_edate))
			$strSQL4.= ",event_edate=$str_edate";
		else 
			$strSQL4.= ",event_edate=NULL";

		if(isset($str_adate))
			$strSQL4.= ",event_adate=$str_adate";
		else 
			$strSQL4.= ",event_adate=NULL";

    		$strSQL4.= " WHERE event_id='$str_id'";
   		$result4 = dbquery($strSQL4);

    		set_status("Event Updated.");
  	}
}

/******************************************/
/* COMPUTING - NO OUTPUT - END            */
/******************************************/








/******************************************/
/* COMPUTING - SHOW OUTPUT - START        */
/******************************************/
$TITLE = "Project Events";
show_header();
show_menu("PROJECTS");
echo '<div style="position:relative; top:-150px">';
show_status();
if($strProject)
{
	/*This eventList will be used for selecting an event when one is created*/
	$eventList = "";
	for($i=0;$i<count($EVENT_TYPES);$i++)
	{
  		$tmp=$EVENT_TYPES[$i];
  		$tmp0=$tmp[0];
  		$tmp1=$tmp[1]?"DOCUMENT":"MEETING";
  		$eventList.= "<option value=\'$i\'>$tmp0 - $tmp1</option>";
	}
	unset($tmp);unset($tmp0);unset($tmp1);unset($i);

	$eventList.= "<option value=\'-1\'>Other</option>"

?>
<script type="text/javascript" src="modalbox/lib/prototype.js"></script>
<script type="text/javascript" src="modalbox/lib/scriptaculous.js"></script>
<script type="text/javascript" src="modalbox/modalbox.js"></script>
<script type="text/javascript" src="scripts/events.js"></script>
<link rel="stylesheet" href="modalbox/modalbox.css" type="text/css" media="screen">

<form name="addEventForm" action="<?=$PAGE_EVENTS;?>" method="POST">
<input type="hidden" name="txt_action" value="addEventForm">
<input type="hidden" value="<?=$strProject;?>" name="txt_project">
<input type="hidden" name="txt_event">
<input type="hidden" name="txt_other">
<input type="hidden" name="pdate">
<input type="hidden" name="edate">
<input type="hidden" name="adate">
<input type="hidden" name="proj_id">
</form>
<form name="updateEventForm" action="<?=$PAGE_EVENTS;?>" method="POST">
<input type="hidden" name="txt_action" value="updateEventForm">
<input type="hidden" value="<?=$strProject;?>" name="txt_project">
<input type="hidden" name="pdate">
<input type="hidden" name="edate">
<input type="hidden" name="adate">
<input type="hidden" name="event_id">
<input type="hidden" name="del">
</form>


<p align=center>
	<a href="<?=$PAGE_SHOW?>?txt_graph=graph-g2.php&txt_project=<?=$strProject?>&txt_small=0">
	<img border=0 src=graph-g2.php?txt_project=<?=$strProject?>&txt_small=0>
	</a>
</p>

<p align="center">
	<input type="submit" class="but" onclick="addEvent(<?=$strProject?>,'<?=$eventList?>')" value="Add Event">
</p>


<br /><br />
<table align="center">
	<tr><td valign="top">
		<table align="center" class="list">
			<form action="<?=$PAGE_EVENTS;?>" method="POST">
			<input type="hidden" value="<?=$strProject;?>" name="txt_project">
			<input type="hidden" value="events_update" name="txt_editEvents">
			<input type="hidden" value="" name="txt_which">
			<input type="hidden" value="" name="txt_type">

			<tr>
				<th colspan="6">Project Meeting Schedule</th>
			</tr>
			<tr>
				<th>Meetings</th>
				<th>Planned Date</th>
				<th>Estimated Date</th>
				<th>Actual Date</th>
				<th>Click to Modify</th>
			</tr>

<?
	$strSQL1 = "SELECT event_id, event_type, event_pdate, event_edate, event_adate";
	$strSQL1.= " FROM $TABLE_EVENT WHERE project_id='$strProject' AND is_doc='0'";
	$result1 = dbquery($strSQL1);

	$rowClass=0;
	$x=0;
	while($row1 = mysql_fetch_array($result1))
	{
		$eId = $row1['event_id'];
		$eType = $row1['event_type'];
		$pDate = $row1['event_pdate'];
		$pDate = date_read($pDate);
		$eDate = $row1['event_edate'];
		$eDate = date_read($eDate);
		$aDate = $row1['event_adate'];
		$aDate = date_read($aDate);

		if($eType!=-1)
		{
			$label=$EVENT_TYPES[$eType][0];
?>
			<tr class="row<?=$rowClass;?>" name="<?=$eId;?>">
	   			<td><?=$label;?></td>
	   			<td align="center"><input readonly="readonly" id="pdate<?=$x;?>" type="text" name="txt_pdate" size="8" value="<?=$pDate;?>"></td>
	   			<td align="center"><input readonly="readonly" id="edate<?=$x;?>" type="text" name="txt_edate" size="8" value="<?=$eDate;?>"></td>
	   			<td align="center"><input readonly="readonly" id="adate<?=$x;?>" type="text" name="txt_adate" size="8" value="<?=$aDate;?>"></td>
	   			<td align="center"><input type="button" class="but" name="txt_update" id="txt_update" value="Modify" onclick="updateEvent('<?=$eId;?>','<?=$label;?>','<?=$pDate;?>','<?=$eDate;?>','<?=$aDate;?>');"></td>
	   		</tr>
<?
	   		$x++;
	   		$rowClass = (++$rowClass)%2;
		}
	}	

?>
		</table>
	</td>
	<td valign="top">
		<table align="center" class="list">
			<tr>
				<th colspan="6">Project Documents</th>
			</tr>
			<tr>
				<th>Documents</th>
				<th>Planned Date</th>
				<th>Estimated Date</th>
				<th>Actual Date</th>
				<th>Click to Modify</th>
			</tr>
<?
	$strSQL2 = "SELECT event_id, event_type, event_pdate, event_edate, event_adate";
	$strSQL2.= " FROM $TABLE_EVENT WHERE project_id='$strProject' AND is_doc='1'";
	$result2 = dbquery($strSQL2);

	$rowClass=0;
	while($row2 = mysql_fetch_array($result2))
	{
		$eId = $row2['event_id'];
		$eType = $row2['event_type'];
		$pDate = $row2['event_pdate'];
		$pDate = date_read($pDate);
		$eDate = $row2['event_edate'];
		$eDate = date_read($eDate);
		$aDate = $row2['event_adate'];
		$aDate = date_read($aDate);

		if($eType!=-1)
		{
	   		$label=$EVENT_TYPES[$eType][0];
?>
	   		<tr class="row<?=$rowClass;?>">
	   			<td><?=$label;?></td>
	   			<td align="center"><input readonly="readonly" id="pdate<?=$x;?>" type="text" size="8" value="<?=$pDate;?>"></td>
	   			<td align="center"><input readonly="readonly" id="edate<?=$x;?>" type="text" size="8" value="<?=$eDate;?>"></td>
	   			<td align="center"><input readonly="readonly" id="adate<?=$x;?>" type="text" size="8" value="<?=$aDate;?>"></td>
	   			<td align="center"><input type="button" class="but" name="txt2_eupdate" value="Modify" onclick="updateEvent('<?=$eId;?>','<?=$label;?>','<?=$pDate;?>','<?=$eDate;?>','<?=$aDate;?>');"></td>
	   		</tr>
<?
	   		$x++;
	   		$rowClass = (++$rowClass)%2;
		}
	}
?>
		</table>

	</td>
	<td valign="top">
		<table align=center class=list>
			<tr>
				<th colspan=6>Other Project Events</th>
			</tr>
			<tr>
				<th>Other Events</th>
				<th>Planned Date</th>
				<th>Estimated Date</th>
				<th>Actual Date</th>
				<th>Click to Modify</th>
			</tr>

<?
	$strSQL3 = "SELECT event_id, other, event_pdate, event_edate, event_adate";
	$strSQL3.= " FROM $TABLE_EVENT WHERE project_id='$strProject' AND event_type='-1'";
	$result3 = dbquery($strSQL3);

	$rowClass=0;
	while($row3 = mysql_fetch_array($result3))
	{
		$eId = $row3['event_id'];
		$eType = $row3['other'];
		$pDate = $row3['event_pdate'];
		$pDate = date_read($pDate);
		$eDate = $row3['event_edate'];
		$eDate = date_read($eDate);
		$aDate = $row3['event_adate'];
		$aDate = date_read($aDate);
?>
			<tr class="row<?=$rowClass;?>">
				<td><?=$eType;?></td>
				<td align="center"><input readonly="readonly" id="pdate<?=$x;?>" type="text" size="8" value="<?=$pDate;?>"></td>
				<td align="center"><input readonly="readonly" id="edate<?=$x;?>" type="text" size="8" value="<?=$eDate;?>"></td>
				<td align="center"><input readonly="readonly" id="adate<?=$x;?>" type="text" size="8" value="<?=$aDate;?>"></td>
				<td align="center"><input type="button" class="but" name="txt2_eupdate" value="Modify" onclick="updateEvent('<?=$eId;?>', '<?=$eType;?>','<?=$pDate;?>','<?=$eDate;?>','<?=$aDate;?>');"></td>
			</tr>
<?
		$x++;
		$rowClass = (++$rowClass)%2; //Alternate row class
	}
?>
			</form>
		</table>
	</td>
	</tr>
</table>

<?
}/*end page content*/

projectSelector($PAGE_EVENTS, $strProject);

show_footer();
?>
</div>