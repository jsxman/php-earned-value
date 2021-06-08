<?
include("treegrid_functions.php");
/******************************************/
/* HEADER: INCLUDE/SECURITY CHECK - START */
/******************************************/
$HACK_CHECK=1; Include("config/global.inc.php");
debug(10,"Loading File: tasks.php");
debug(10,"User Level is DB-Admin (".$_SESSION['dbAdmin'].")");
checkPermissions($SESSION_TIMEOUT); // if not logged in, or session has timed out...
/******************************************/
/* HEADER: INCLUDE/SECURITY CHECK - END   */
/******************************************/

if(isset($_REQUEST['action'])) {
	
	error_log("ACTION: $_REQUEST[action]");

   switch($_REQUEST['action']) {

      case "project_sel":

         projectSelector($_REQUEST['page'], $_REQUEST['proj']);

         break;

      case "lock_project":

         //Check to see if this user is an admin for this project
         $strCheck = "SELECT is_admin FROM pev__project_access WHERE project_id='$_SESSION[projectID]' AND person_id='$_SESSION[paev_userID]'";
         $checkResult = dbquery($strCheck);

         $checkRow = mysql_fetch_array($checkResult);

         if($checkRow['is_admin'] == 1 || $_SESSION['dbAdmin']) {

            $strLock = "UPDATE pev__project SET locked='1' WHERE project_id='$_SESSION[projectID]'";
            dbquery($strLock);

            recordHistory($_SESSION['projectID']);

            echo "1";

        	} else {

            echo "0";
        	}

         //TODO: Need to implement no plan edit functionality

         break;

      case "unlock_project":

         if($_SESSION['dbAdmin']) {

            $strLock = "UPDATE pev__project SET locked='0' WHERE project_id='$_SESSION[projectID]'";

            dbquery($strLock);

            recordHistory($_SESSION['projectID']);

            echo "1";

        	} else {

            echo "0";
        	}

         break;

      case "replan_project":

         $new_proj_id = replanProject($_SESSION['projectID']);

         recalculate_WBSs($new_proj_id);

         break;

      case "edit_treegrid_row":

      	$rowID = $_REQUEST['id'];
      	$strProject = $_SESSION['projectID'];
      	$LOCKED = "";

      	$lock_stat_sql = "SELECT locked FROM pev__project WHERE project_id = '$strProject'";

      	$lock_stat_result = dbquery($lock_stat_sql);

      	$prj_lock_status = mysql_fetch_array($lock_stat_result);

      	if($prj_lock_status['locked'] == "1") {

      		$LOCKED = "DISABLED";
      	}

      	$strSQL1 = "SELECT T.task_id as TID,T.task_name,T.scope_growth,WT.wbs_id, WT.parent_id, WT.wbs_number, WT.rollup, WT.due_date, WT.ec_date, WT.planned_hours, WT.actual_hours, WT.percent_complete, W.wbs_name, P.first, P.last, W.wbs_order, P.person_id ";
			$strSQL1.= " FROM pev__task AS T ";
			$strSQL1.= " LEFT JOIN pev__wbs_to_task AS WT ON T.task_id=WT.task_id";
			$strSQL1.= " LEFT JOIN pev__wbs AS W ON W.wbs_id=WT.wbs_id";
			$strSQL1.= " LEFT JOIN pev__person_to_wbstask AS PW ON PW.wbs_to_task_id=WT.wbs_to_task_id";
			$strSQL1.= " LEFT JOIN pev__person AS P ON P.person_id=PW.person_id";
			$strSQL1.= " WHERE WT.wbs_to_task_id='$rowID' AND W.project_id='$strProject'";
			$strSQL1.= " ORDER BY T.task_name ASC, T.task_id ASC, W.wbs_order ASC, W.wbs_name ASC, W.wbs_id ASC";
			$result = dbquery($strSQL1);
			
			if(mysql_num_rows($result) <= 0) {

				//Selected row from TreeGrid is a WBS
				$strSQL = "";
				$strSQL.= "SELECT wbs_name, wbs_order FROM $TABLE_WBS WHERE wbs_id = ".removeWBSMarker($rowID)."";
				$result = dbquery($strSQL);
				$row = mysql_fetch_array($result);?>

			  	<div class='editTask'>
			  	<form id='editRow'>
			  	<input type='hidden' name='row_type' value='wbs'>
				<input type='hidden' name='tmp_id' value='<?=$rowID?>'>
		 		<label style= "font-weight: bold;">WBS Name:</label>&nbsp;&nbsp;&nbsp;<?=$LOCKED === "DISABLED" ? "$row[wbs_name]<br>" : "<input type='text' name='tmp_name' size='80%' value='$row[wbs_name]'/></input><br/>"?>
				<label style= "font-weight: bold;">WBS Number:</label>&nbsp;&nbsp;&nbsp;<?=$row['wbs_order']?><br/></br/>
				</form>
				</div><?
		
			} else {

				//Selected row from TreeGrid is a Rollup or Task
				$row = mysql_fetch_array($result);
				
		   	$due_date = date_read($row['due_date']);
		   	$ec_date = date_read($row['ec_date']);
		   	$taskID = $row['TID'];

				if ($row['rollup'] == '0') { //Selected row from TreeGrid is a Task?>

		  			<div class='editTask'>
		  			<form id='editRow'>
		  			<input type='hidden' name='row_type' value='task'>
		  			<input type='hidden' name='tmp_id' value='<?=$rowID?>'>
		  			<input type='hidden' name='tmp_tid' value='<?=$taskID?>'>
			  		<label style= "font-weight: bold;">Task Name:</label>&nbsp;&nbsp;&nbsp;<?=$LOCKED === "DISABLED" ? $row['task_name'] : "<input type='text' name='tmp_name' size='80%' value='$row[task_name]'/></input>"?><br/>
			  		<label style= "font-weight: bold;">WBS Parent:</label>&nbsp;&nbsp;&nbsp;<?=$row['wbs_name']?><br/>
			  		<label style= "font-weight: bold;">WBS Number:</label>&nbsp;&nbsp;&nbsp;<?=$row['wbs_number']?><br/></br/>
			  		<table align=center class=mtab width=85%><tr><th>POC</th><th>DUE</th><th>EC</th><th>Plan Hours</th><th>Actual Hours</th><th>Percent</th></tr>
					<tr>
						<td class=poc><select name=tmp_poc>
						<option value = 'none'>-None-</option><?
			  		
			  		//Grabs list of POCs from current project
			  		$strSQL2 = "SELECT PA.person_id,P.first,P.last";
					$strSQL2 .= " FROM pev__project_access AS PA ";
					$strSQL2 .= " LEFT JOIN pev__person AS P ON P.person_id=PA.person_id";
					$strSQL2 .= " WHERE PA.project_id='$_SESSION[projectID]' AND P.first != '' AND P.last != ''";
					$strSQL2 .= " GROUP BY P.last ORDER BY P.last ASC";

					$result2 = dbquery($strSQL2);

					while ($row2 = mysql_fetch_array($result2)) {

						if ((strlen($row2['first'])) > 0 && (strlen($row2['last'])) > 0) { // filter out blank names;

							//If one, selects the assigned POC of the task
							if ($row['person_id'] == $row2['person_id']) {?>
	
								<option value='<?=$row2['person_id']?>' SELECTED><?=$row2['last']?>, <?=$row2['first']?></option><?
	
							} else {?>
	
								<option value='<?=$row2['person_id']?>'><?=$row2['last']?>, <?=$row2['first']?></option><?
							}
						}
		    		}?>

						</select></td>
						<td class=date><input id='ms1' <?=$LOCKED?> type=text name=tmp_due value='<?=$due_date?>'></td>
						<td class=date><input id='ms2' type=text name=tmp_ec value='<?=$ec_date?>'></td>
						<td class=hours><input type=text <?=$LOCKED?> name=tmp_ph value='<?=$row['planned_hours']?>'></td>
						<td class=hours><input type=text name=tmp_ah value='<?=$row['actual_hours']?>'></td>
						<td class=percent><input type=text name=tmp_pc value='<?=$row['percent_complete']?>'></td>
			  		</tr>
			  		<tr>
						<td colspan=6 align=center>&nbsp;</td>
			  		</tr>
			  		</table>
					</form>
			  		</div><?

				} else if($row['rollup'] == "1")	{?>

		  			<div class='editTask'>
		  			<form id='editRow'>
		  			<input type='hidden' name='row_type' value='rollup'>
		  			<input type='hidden' name='tmp_id' value='<?=$rowID?>'>
		  			<input type='hidden' name='tmp_tid' value='<?=$taskID?>'>
			  		<label style= "font-weight: bold;">Rollup Task Name:</label>&nbsp;&nbsp;&nbsp;<?=$LOCKED === "DISABLED" ? "$row[task_name]<br/>" : "<input type='text' name='tmp_name' size='80%' value='$row[task_name]'/></input><br/>"?>
			  		<label style= "font-weight: bold;">WBS Parent:</label>&nbsp;&nbsp;&nbsp;<?=$row['wbs_name']?><br/>
			  		<label style= "font-weight: bold;">WBS Number:</label>&nbsp;&nbsp;&nbsp;<?=$row['wbs_number']?><br/></br/>
			  		<table align=center class=mtab width=85%>
					<tr><th>DUE</th><th>EC</th><th>Plan Hours</th><th>Actual Hours</th><th>Percent</th></tr>
					</tr>					
						<td class=date><?=$due_date?></td>
						<td class=date><?$ec_date?></td>
						<td class=hours><?=$row['planned_hours']?></td>
						<td class=hours><?=$row['actual_hours']?></td>
						<td class=percent><?=$row['percent_complete']?></td>
			  		</tr>
			  		<tr>
						<td colspan=6 align=center>&nbsp;</td>
			  		</tr>
			  		</table>
					</form>
			  		</div><?

					}
				}

				break;
			
      case "update_treegrid":

   		$LOCKED = "0";

      	$strSQL0 = "SELECT locked FROM pev__project WHERE project_id = '$_SESSION[projectID]'";
      	$result0 = dbquery($strSQL0);

      	$row0 = mysql_fetch_array($result0);

      	if($row0['locked'] == "1") {

      		$LOCKED = "1";
     		}

			if($_REQUEST['row_type'] == "wbs") {

				$rowID = removeWBSMarker($_REQUEST['tmp_id']);

				if ((strlen($_REQUEST['tmp_name'])) > 0) {

					$strSQL  = "";
					$strSQL .= " UPDATE $TABLE_WBS SET";
					$strSQL .= " wbs_name = '$_REQUEST[tmp_name]' WHERE";
					$strSQL .= " wbs_id = '$rowID' AND";
					$strSQL .= " project_id = '$_SESSION[projectID]'";
	
					$result = dbquery($strSQL);
				}

				return;

				break;

			} else if($_REQUEST['row_type'] == "rollup" && (strlen($_REQUEST['tmp_name'])) > 0) {

				$strSQL  = "";
				$strSQL .= " UPDATE $TABLE_TASK SET";
				$strSQL .= " task_name = '$_REQUEST[tmp_name]' WHERE";
				$strSQL .= " task_id = '$_REQUEST[tmp_tid]' AND";
				$strSQL .= " project_id = '$_SESSION[projectID]'";

				error_log("ROLLUP NAME UPDATE: $strSQL");
				$result = dbquery($strSQL);

				break;

			} else if($_REQUEST['row_type'] == "task") {

				$due = date_save($_REQUEST['tmp_due']);

				$ec = date_save($_REQUEST['tmp_ec']);
				
				if(!$LOCKED) {

					//UPDATE due_date, ec_date, planned_hours, actual_hours, percent_complete
					//in the TABLE pev__wbs_to_task 
					$strSQL  = "";
					$strSQL .= " UPDATE pev__wbs_to_task SET";
					$strSQL .= " due_date = '$due'";
					$strSQL .= ",ec_date = '$ec'";
					$strSQL .= ",planned_hours = '$_REQUEST[tmp_ph]'";
					$strSQL .= ",actual_hours = '$_REQUEST[tmp_ah]'";
					$strSQL .= ",percent_complete = '$_REQUEST[tmp_pc]'";
					$strSQL .= " WHERE wbs_to_task_id = '$_REQUEST[tmp_id]'";

					$result = dbquery($strSQL);

				} else {

					$strSQL  = "";
					$strSQL .= " UPDATE pev__wbs_to_task SET";
					$strSQL .= " ec_date = '$ec'";
					$strSQL .= ",actual_hours = '$_REQUEST[tmp_ah]'";
					$strSQL .= ",percent_complete = '$_REQUEST[tmp_pc]'";
					$strSQL .= " WHERE wbs_to_task_id = '$_REQUEST[tmp_id]'";

					$result = dbquery($strSQL);
				}
				
				$strSQL3 = "SELECT person_id FROM pev__person_to_wbstask WHERE wbs_to_task_id='$_REQUEST[tmp_id]'";

				$result3 = dbquery($strSQL3);

				if(mysql_num_rows($result3) != '0') {

					//DELETE any person currently assigned to task
					$strSQL2 = "DELETE FROM pev__person_to_wbstask WHERE wbs_to_task_id='$_REQUEST[tmp_id]'";

				  	$result2 = dbquery($strSQL2);
				}

			   if($_REQUEST['tmp_poc'] != "none") {

			   	//INSERT new person to be assigned to task
			   	$strSQL3 = "INSERT INTO pev__person_to_wbstask SET wbs_to_task_id = '$_REQUEST[tmp_id]', person_id = '$_REQUEST[tmp_poc]'";

			   	$result3 = dbquery($strSQL3);
			   }
				
				if ((strlen($_REQUEST['tmp_name'])) > 0) {

					//UPDATE the name of the task being updated if it's not empty
					$strSQL4  = "";
					$strSQL4 .= " UPDATE pev__task SET";
					$strSQL4 .= " task_name = '$_REQUEST[tmp_name]'";
					$strSQL4 .= " WHERE task_id = '$_REQUEST[tmp_tid]'";
	
					$result4 = dbquery($strSQL4);
				}

			}

			break;
			
      case "add_child_task":
      
     	   // Create new Node
			// Link new Node to Parent Node
			// Find all related Nodes(search by Parent Node) 001.002 -> 001
			// IF new Node == Highest wbs_number || results == 0
			//					-> Update Parent Node to be rollup, if not already
			//					-> Then BREAK
			// WHILE($row = mysql_fetch_array($result)
			// 	IF row[wbs_number].charAt(Last 3 positions of new Node) >= New Node.last_3_numbers of New Node
			//					-> +1 to row[wbs_number].charAt(Last 3 positions of new Node) 

      	$selectedRowId = $_REQUEST['RowId'];
      	$strProject = $_SESSION['projectID'];
      	$strSQL = "";
      	$strSQL.= "SELECT WT.wbs_number, W.wbs_id, WT.rollup, WT.due_date, WT.ec_date, WT.planned_hours, WT.actual_hours, WT.percent_complete ";
      	$strSQL.= "FROM pev__wbs_to_task AS WT ";
      	$strSQL.= "LEFT JOIN pev__wbs AS W "; 
      	$strSQL.= "ON W.wbs_id = WT.wbs_id "; 
      	$strSQL.= "WHERE WT.wbs_to_task_id = '$selectedRowId' ";
      	$strSQL.= "AND W.project_id = '$strProject'";
			$result = dbquery($strSQL);
			
			// Check if the selected task is a rollup or task
			if(!mysql_num_rows($result) == 0) 
				{
				$row = mysql_fetch_array($result);
				$parentWBS_Number = $row['wbs_number'];
				$childWBS_Number = $parentWBS_Number.'.001';
				$strSQL2 = "";
				$strSQL2.= "SELECT WT.wbs_to_task_id, WT.wbs_number, W.wbs_id FROM pev__wbs_to_task AS WT ";
				$strSQL2.= "LEFT JOIN pev__wbs AS W ON W.wbs_id = WT.wbs_id ";
				$strSQL2.= "WHERE WT.wbs_number LIKE '$parentWBS_Number%' AND W.project_id = '$strProject' ";
				$result2 = dbquery($strSQL2);
				
				//Checks if Parent task has no Childern
				if(mysql_num_rows($result2) == 1) 
					{
					$row2 = mysql_fetch_array($result2);
					if ($row2 ['wbs_number'] == $parentWBS_Number)
						{
						$revisionNumber = getRevisionNumber($strProject);
						if($revisionNumber != false || $revisionNumber == "")
							{
							//Creates new task and connects to Parent task
							$strSQL3 = "";
							$strSQL3.= "INSERT INTO pev__task SET project_id = '$strProject', task_name = 'NEW CHILD TASK (double click to edit)', scope_growth = '$revisionNumber'"; //NEED TO APPEND SCOPE GROWTH HERE
							$result3 = dbquery($strSQL3);
							$newTaskId = mysql_insert_id();
						
							//
							$strSQL4 = "";
				   		$strSQL4.= "INSERT INTO pev__wbs_to_task SET wbs_id = '".$row2['wbs_id']."', parent_id = '$selectedRowId', wbs_number = '$childWBS_Number', task_id = '$newTaskId', ";
				   		$strSQL4.= "due_date = '".$row['due_date']."', ec_date = '".$row['ec_date']."', planned_hours = '".$row['planned_hours']."', actual_hours = '".$row['actual_hours']."', percent_complete = '".$row['percent_complete']."'";
				   		$result4 = dbquery($strSQL4);
				   	
				   		//Updates Parent task rollup to '1' to mark that it has a child
				   		$strSQL5 = "";
				   		$strSQL5 = "UPDATE pev__wbs_to_task SET rollup = '1' WHERE wbs_to_task_id = '$selectedRowId' AND wbs_id = '".$row2['wbs_id']."'";
				   		$result5 = dbquery($strSQL5);
				   		recalculate($row2['wbs_to_task_id']);
				   		break;
							}
						}
					}
				else
					{
					//Need to implement, currently echos a "Rollup" back to jQuery function which popups up a error message, saying you can't add a new child task to a rollup
					echo "Rollup";
//					//Goes through all related Childern of Parent task
//					while($row2 = mysql_fetch_array($result2))
//						{
//						//Checks if Parent task as childern
//						if($row2['wbs_number'])
//						$strSQL3 = "";
//						$strSQL3.= "SELECT WT.wbs_number, W.wbs_id FROM pev__wbs_to_task AS WT ";
//						$strSQL3.= "LEFT JOIN pev__wbs AS W ON W.wbs_id = WT.wbs_id ";
//						$strSQL3.= "WHERE WT.wbs_number LIKE '$parentWBS_Number%' AND W.project_id = '$strProject' ";
//						}
					}
				}
			else
				{
				$strSQL2 = "";
				$strSQL2 = "SELECT wbs_id, wbs_order ";
				$strSQL2.= "FROM pev__wbs ";
				$strSQL2.= "WHERE wbs_id = '".removeWBSMarker($selectedRowId)."' AND project_id = '$strProject'";
				$result2 = dbquery($strSQL2);
				
				//Check if selected row is a WBS
				if($result2)
					{
					$row = mysql_fetch_array($result2);
					$parentWBS_Id = $row['wbs_id'];
					$parentWBS_Number = $row['wbs_order'];
					$childWBS_Number = $parentWBS_Number.'.001';
					
					$strSQL2 = "";
					$strSQL2.= "SELECT WT.wbs_number, W.wbs_id FROM pev__wbs_to_task AS WT ";
					$strSQL2.= "LEFT JOIN pev__wbs AS W ON W.wbs_id = WT.wbs_id ";
					$strSQL2.= "WHERE WT.wbs_number LIKE '$parentWBS_Number%' AND W.project_id = '$strProject' ";
					$result2 = dbquery($strSQL2);
					
					//Checks if parent task has no childern
					if(mysql_num_rows($result2) == 0) 
						{
						//Create a new task and connect it to the parent task
						$revisionNumber = getRevisionNumber($strProject);
						if($revisionNumber != false || $revisionNumber == "")
							{
							$strSQL3 = "";
							$strSQL3 = "INSERT INTO pev__task SET project_id = '$strProject', task_name = 'NEW CHILD TASK (double click to edit)', scope_growth = '$revisionNumber'";
							$result3 = dbquery($strSQL3);
							$newTaskId = mysql_insert_id();
						
							$strSQL4 = "";
				   		$strSQL4 = "INSERT INTO pev__wbs_to_task SET wbs_id = '$parentWBS_Id', parent_id = '".addWBSMarker($parentWBS_Id)."', wbs_number = '$childWBS_Number', task_id = '$newTaskId'";
				   		$result4 = dbquery($strSQL4);
				   		break;
							}
						}
					else
						{
						echo "WBS";
						//Need to implement, currently echos a "WBS" back to jQuery function which popups up a error message, saying you can't add a new child task to a WBS that already has child tasks
	//					//Goes through all related Childern of Parent task
	//					while($row2 = mysql_fetch_array($result2))
	//						{
	//						//Checks if Parent task as childern
	//						if($row2['wbs_number'])
	//						$strSQL3 = "";
	//						$strSQL3.= "SELECT WT.wbs_number, W.wbs_id FROM pev__wbs_to_task AS WT ";
	//						$strSQL3.= "LEFT JOIN pev__wbs AS W ON W.wbs_id = WT.wbs_id ";
	//						$strSQL3.= "WHERE WT.wbs_number LIKE '$parentWBS_Number%' AND W.project_id = '$strProject' ";
	//						}
						}
					}
				}
			break;
			
      case "add_sibling_task":
      
      	// GET AffectedArea
      	// WHILE(AffectedArea)
      	//		IF(AffectedArea[target] > selected_WBS_Num_target)
      	//			AffectedArea[target] = AffectedArea[target]+1
      	//		ELSE
      	//			CREATE New Node with link to selected Task's Parent_Id & wbs_number = selected Task WBS +1
      	
      	$selectedRowId = $_REQUEST['RowId'];
      	$strProject = $_SESSION['projectID'];
      	$strSQL = "";
      	$strSQL.= "SELECT WT.wbs_number, WT.parent_id, W.wbs_id ";
      	$strSQL.= "FROM pev__wbs_to_task AS WT ";
      	$strSQL.= "LEFT JOIN pev__wbs AS W "; 
      	$strSQL.= "ON W.wbs_id = WT.wbs_id "; 
      	$strSQL.= "WHERE WT.wbs_to_task_id = '$selectedRowId' ";
      	$strSQL.= "AND W.project_id = '$strProject'";
			$result = dbquery($strSQL);
			
			// Check if selected task not a WBS
			if(!mysql_num_rows($result) == 0) 
				{
				$row = mysql_fetch_array($result);
				$selectedWBS_Number = $row['wbs_number'];
				$splitWBS = explode(".", $selectedWBS_Number);
				$count = count($splitWBS);
				$selectedTarget = end($splitWBS);
				$affectedArea = substr($selectedWBS_Number, 0, -4);
				
				$strSQL2 = "";
				$strSQL2.= "SELECT WT.wbs_to_task_id, WT.wbs_number, W.wbs_id, WT.parent_id ";
				$strSQL2.= "FROM pev__wbs_to_task AS WT ";
				$strSQL2.= "LEFT JOIN pev__wbs AS W ON W.wbs_id = WT.wbs_id ";
				$strSQL2.= "WHERE WT.wbs_number LIKE '$affectedArea%' AND W.project_id = '$strProject' ";
				$strSQL2.= "ORDER BY WT.wbs_number DESC";
				$result2 = dbquery($strSQL2);
				
				while($row2 = mysql_fetch_array($result2))
					{
					// If current wbs_number from results > than the deleted wbs_number
					$splitAffectedWBS = explode('.', $row2['wbs_number']);
					$affectedTarget = $splitAffectedWBS[($count-1)];
					if($affectedTarget > $selectedTarget)
						{
						$splitAffectedWBS[($count-1)] = sprintf("%03d",($affectedTarget+1));
						$updatedAffectedWBS = implode('.', $splitAffectedWBS);
						
						$strSQL3 = "";
						$strSQL3.= "UPDATE pev__wbs_to_task ";
						$strSQL3.= "SET wbs_number = '$updatedAffectedWBS' ";
						$strSQL3.= "WHERE wbs_to_task_id = '".$row2['wbs_to_task_id']."' AND wbs_id = '".$row2['wbs_id']."'";
						$result3 = dbquery($strSQL3);
						}
					else
						{
						$splitWBS[($count-1)] = sprintf("%03d",($selectedTarget+1));
						$updatedNewWBS = implode('.', $splitWBS);
						$revisionNumber = getRevisionNumber($strProject);
						if($revisionNumber != false || $revisionNumber == "")
							{
							$strSQL4 = "";
							$strSQL4 = "INSERT INTO pev__task SET project_id = '$strProject', task_name = 'NEW SIBLING TASK (Double-Click to Edit)', scope_growth = '$revisionNumber'"; //NEED TO APPEND SCOPE GROWTH HERE
							$result4 = dbquery($strSQL4);
							$newTaskId = mysql_insert_id();
							$strSQL5 = "";
				   		$strSQL5 = "INSERT INTO pev__wbs_to_task SET wbs_id = '".$row['wbs_id']."', parent_id = '".$row['parent_id']."', wbs_number = '$updatedNewWBS', task_id = '$newTaskId'";
				   		$result5 = dbquery($strSQL5);
				   		
				   		if(!recalculate($selectedRowId))
					   		{
					   			break;
					   		}
				   		break;
							}
						}
					}
				}
			else
				{
       		echo "WBS";
				}
      	break;
      	
		case "delete_task":
			
			$selectedRowId = $_REQUEST['RowId'];
      	$strProject = $_SESSION['projectID'];
      	$parentID;
      	$strSQL = "";
      	$strSQL.= "SELECT WT.wbs_number, W.wbs_id, WT.parent_id ";
      	$strSQL.= "FROM pev__wbs_to_task AS WT ";
      	$strSQL.= "LEFT JOIN pev__wbs AS W "; 
      	$strSQL.= "ON W.wbs_id = WT.wbs_id "; 
      	$strSQL.= "WHERE WT.wbs_to_task_id = '$selectedRowId' ";
      	$strSQL.= "AND W.project_id = '$strProject'";
			$result = dbquery($strSQL);
			
			// Checks if selected Row is Task/Rollup Task
			if(!mysql_num_rows($result) == 0) 
				{
				$row = mysql_fetch_array($result);
				$parentID = $row['parent_id'];
				$selectedWBS_Number = $row['wbs_number'];
				$splitWBS = explode(".", $selectedWBS_Number);
				$count = count($splitWBS);
				$check = end($splitWBS);
				$affectedArea = substr($selectedWBS_Number, 0, -4);
				
				// Find selected Task and it's Children
	      	$strSQL2 = "";
				$strSQL2.= "SELECT WT.wbs_to_task_id, WT.wbs_number, WT.task_id, W.wbs_id ";
				$strSQL2.= "FROM pev__wbs_to_task AS WT ";
				$strSQL2.= "LEFT JOIN pev__wbs AS W ON W.wbs_id = WT.wbs_id ";
				$strSQL2.= "WHERE WT.wbs_number LIKE '$selectedWBS_Number%' AND W.project_id = '$strProject' ";
				$strSQL2.= "ORDER BY WT.wbs_number DESC";
				$result2 = dbquery($strSQL2);
				
				// DELETE Task and all Children under selected Task
				while($row2 = mysql_fetch_array($result2))
					{
					// DELETE from pev__person_to_wbstask
					$strSQL3 = "";
					$strSQL3.= "DELETE FROM pev__person_to_wbstask WHERE wbs_to_task_id = '".$row2['wbs_to_task_id']."'";
			  		$result3 = dbquery($strSQL3);
			  		
			  		// DELETE from pev__task
			  		$strSQL4 = "";
			  		$strSQL4.= "DELETE FROM pev__task WHERE task_id = '".$row2['task_id']."' and project_id = '$strProject'";
			  		$result4 = dbquery($strSQL4);
			  		
			  		// DELETE from pev__wbs_to_task
			  		$strSQL5 = "";
			  		$strSQL5.= "DELETE FROM pev__wbs_to_task WHERE wbs_to_task_id = '".$row2['wbs_to_task_id']."' and wbs_id = '".$row2['wbs_id']."'";
			  		$result5 = dbquery($strSQL5);
					}
					
				// Find all affected tasks
				$strSQL6 = "";
				$strSQL6.= "SELECT wbs_to_task_id, wbs_id, wbs_number ";
				$strSQL6.= "FROM pev__wbs_to_task ";
				$strSQL6.= "WHERE wbs_number like '$affectedArea%' AND wbs_id = '".$row['wbs_id']."' ";
				$strSQL6.= "ORDER BY wbs_number DESC";
				$result6 = dbquery($strSQL6);

				if(!mysql_num_rows($result6) == 0)
					{
					$skipRecalculating = false;
					while($row3 = mysql_fetch_array($result6))
						{
						// If current wbs_number from results > than the deleted wbs_number
						$temp = explode('.', $row3['wbs_number']);
						if($temp[($count-1)] > $check)
							{
							$temp[($count-1)] = sprintf("%03d",($temp[($count-1)]-1));
							$updatedWBS = implode('.', $temp);
							$strSQL7 = "";
							$strSQL7.= "UPDATE pev__wbs_to_task ";
							$strSQL7.= "SET wbs_number = '$updatedWBS' ";
							$strSQL7.= "WHERE wbs_to_task_id = '".$row3['wbs_to_task_id']."' AND wbs_id = '".$row3['wbs_id']."'";
							$result7 = dbquery($strSQL7);
							}
						else if($row3['wbs_number'] == $affectedArea)
							{
							$strSQL8 = "";
							$strSQL8.= "SELECT WT.wbs_to_task_id, WT.wbs_number, WT.task_id, W.wbs_id ";
							$strSQL8.= "FROM pev__wbs_to_task AS WT ";
							$strSQL8.= "LEFT JOIN pev__wbs AS W ON W.wbs_id = WT.wbs_id ";
							$strSQL8.= "WHERE WT.wbs_number LIKE '$affectedArea%' AND W.project_id = '$strProject' ";
							$strSQL8.= "ORDER BY WT.wbs_number DESC";
							$result8 = dbquery($strSQL8);
							
							// IF SQL Results returns 1, then Affected Area has no children, so update rollup to 0, ELSE do nothing
							if(mysql_num_rows($result8) == 1)
								{
								$skipRecalculating = true;
								$strSQL9 = "";
								$strSQL9.= "UPDATE pev__wbs_to_task ";
								$strSQL9.= "SET rollup = '0', due_date = '', ec_date = '', planned_hours = '0', actual_hours = '0', percent_complete = 0 ";
								$strSQL9.= "WHERE wbs_to_task_id = '".$row3['wbs_to_task_id']."' AND wbs_id = '".$row3['wbs_id']."'";
								$result9 = dbquery($strSQL9);
								}
								break;
							}
						}
						recalculate($parentID);
					}
				else 
					{	
					$strSQL7 = "";
					$strSQL7.= "SELECT wbs_id FROM pev__wbs WHERE wbs_order = '$affectedArea' AND project_id = '$strProject'";
					$result7 = dbquery($strSQL7);
					$row7 = mysql_fetch_array($result7);
					
					recalculate($row7['wbs_id']);
					}				
				// NEED TO ADD A FUNCTION TO CHECK THE INTEGRITY OF THE TREEGRID
				}
			else
				{
				echo "WBS_NO";
				}
				break;
				
		case "recalculate_by_WBS":
	   	$project = $_REQUEST['project'];
		   $strSQL = "";
		   $strSQL.= "SELECT wbs_id FROM pev__wbs WHERE project_id = '$project' ORDER BY wbs_order";
		   $result = dbquery($strSQL);
		   
		   //Get all WBSes and recalculate each
		   while($row = mysql_fetch_array($result))
		   {
		   echo $row['wbs_id'];
		   	$wbs = $row['wbs_id'];
		   	recalculate($wbs);
		   }
		   break;
		
		case "recalculate_affectedarea":
			$selectedRowId = $_REQUEST['RowId'];
			recalculate($selectedRowId);
			break;
			
		case "add_up_treegrid":
			AddUpTreeGrid();
   	}  //END of SWITCH
   	
	}
	
	function recalculate_WBSs($strProject) {
	   $strSQL = "";
	   $strSQL.= "SELECT wbs_id FROM pev__wbs WHERE project_id = '$strProject' ORDER BY wbs_order";
	   $result = dbquery($strSQL);
	   
		error_log("recalculate_WBSs($str_roject): $strSQL");

	   //Get all WBSes and recalculate each
	   while($row = mysql_fetch_array($result)) {

	   	//echo $row['wbs_id'];

	   	recalculate($row['wbs_id']);
	   }
	}
	
	function getRevisionNumber($strProject)
		{
		$getSG = "SELECT revision, locked FROM pev__project WHERE project_id='$strProject'";
      $sgResult = dbquery($getSG);
      $sgRow = mysql_fetch_array($sgResult);
      $strScopeGrowth = $sgRow['revision'];
      $locked = $sgRow['locked'];
      if(!$locked)
      	{
      	if($strScopeGrowth == "0"){
      		$strScopeGrowth = "";
      	}
      	return $strScopeGrowth;
      	}
      return false;
		}
	
	function checkIfRowExists($wbs_id) {

		$ret_val = false;

		//$strProject = $_SESSION['projectID'];
		
		$strSQL = "";
      $strSQL.= "SELECT WT.wbs_number, W.wbs_id ";
      $strSQL.= "FROM pev__wbs_to_task AS WT ";
      $strSQL.= "LEFT JOIN pev__wbs AS W "; 
      $strSQL.= "ON W.wbs_id = WT.wbs_id "; 
      $strSQL.= "WHERE WT.wbs_to_task_id = '$wbs_id' ";
      $strSQL.= "AND W.project_id = '$_SESSION[projectID]'";
		$result = dbquery($strSQL);

		error_log("\tcheckIfRowExists($wbs_id) - $strSQL");

		if ($result) {

			$ret_val = mysql_fetch_array($result);
		}
	
		return $ret_val;
	}
		
	function checkIfWBSExists($selectedRowId) {

		$ret_val = false;

		$strProject = $_SESSION['projectID'];
		$strSQL = "";
		$strSQL.= "SELECT wbs_id, wbs_order FROM pev__wbs WHERE project_id = '$strProject' AND wbs_id = '".removeWBSMarker($selectedRowId)."'";
		$result = dbquery($strSQL);

		error_log("\tcheckIfWBSExists($selectedRowId) - $strSQL");

		if ($result) {

			$ret_val = mysql_fetch_array($result);
		}

		return $ret_val;
	}
	
	function recalculate($wbs_id) {
		error_log("recalculate($wbs_id): Called!");
		$wbsTrigger;
		$strProject = $_SESSION['projectID'];
		$due_date = "";
		$ec_date = "";
		$SUM_planned_hrs = 0;
		$SUM_actual_hrs = 0;
		$SUM_percent = 0;
		$parentRow = checkIfRowExists($wbs_id);
		
		if($parentRow) {
		
			$strSQL = "";
			$strSQL.= "SELECT WT.wbs_to_task_id, WT.wbs_number, WT.due_date, WT.ec_date, WT.planned_hours, WT.actual_hours, WT.percent_complete, W.wbs_id, WT.parent_id ";
			$strSQL.= "FROM pev__wbs_to_task AS WT ";
			$strSQL.= "LEFT JOIN pev__wbs AS W ON W.wbs_id = WT.wbs_id ";
			$strSQL.= "WHERE WT.wbs_number LIKE '".$parentRow['wbs_number']."%' AND LENGTH(WT.wbs_number) <= '".(strlen($parentRow['wbs_number'])+4)."' AND W.project_id = '$strProject' ";
			$strSQL.= "ORDER BY WT.wbs_number DESC";
			$result = dbquery($strSQL);
			
			error_log("Have parent row: $strSQL");

			if(mysql_num_rows($result) == 1) {

				echo "\nNOTHING TO ADD\n\n";

				$row = mysql_fetch_array($result);

				recalculate($row['parent_id']);

			} else {

				while($row = mysql_fetch_array($result)) {

					$wbsTrigger = true;

					if($row['wbs_number'] == $parentRow['wbs_number']) {

						$wbsTrigger = false;

						$SUM_percent = round(($SUM_percent/$SUM_planned_hrs)*100,1);

						$strSQL2 = "";
						$strSQL2.= "UPDATE pev__wbs_to_task ";
						$strSQL2.= "SET due_date = '$due_date', ec_date = '$ec_date', planned_hours = $SUM_planned_hrs, actual_hours = $SUM_actual_hrs, percent_complete = $SUM_percent ";
						$strSQL2.= "WHERE wbs_to_task_id = ".$row['wbs_to_task_id']." and wbs_number = '".$parentRow['wbs_number']."'";

						$result = dbquery($strSQL2);

						error_log("UPDATE: $strSQL2");
						
						recalculate($row['parent_id']);

						break;
					}

					$SUM_planned_hrs = ($SUM_planned_hrs + $row['planned_hours']);
					$SUM_actual_hrs = ($SUM_actual_hrs + $row['actual_hours']);
					$SUM_percent += round(($row['planned_hours'] * $row['percent_complete'] / 100), 1);
					
					if($due_date < $row['due_date']) {

						$due_date = $row['due_date'];

					}

					if($ec_date < $row['ec_date']) {

						$ec_date = $row['ec_date'];
					}
				}
			}
			
			return true;
		
		} else {

			$parentRow = checkIfWBSExists($wbs_id);

			error_log("NO parent row($parentRow): $strSQL");

			if($parentRow) {

				$strSQL = "";
				$strSQL.= "SELECT WT.wbs_to_task_id, WT.wbs_number, WT.due_date, WT.ec_date, WT.planned_hours, WT.actual_hours, WT.percent_complete, W.wbs_id, WT.parent_id ";
				$strSQL.= "FROM pev__wbs_to_task AS WT LEFT JOIN pev__wbs AS W ON W.wbs_id = WT.wbs_id ";
				$strSQL.= "WHERE WT.wbs_number like '".$parentRow['wbs_order']."%' AND LENGTH(WT.wbs_number) <= 7 AND W.project_id = '$strProject' ";
				$strSQL.= "ORDER BY WT.wbs_number DESC";

				$result = dbquery($strSQL);

				

				while($row = mysql_fetch_array($result)) {

					$SUM_planned_hrs += $row['planned_hours'];
					$SUM_actual_hrs += $row['actual_hours'];
					$SUM_percent += round(($row['planned_hours'] * $row['percent_complete'] / 100), 1);
					
					if($due_date < $row['due_date']) {

						$due_date = $row['due_date'];
					}

					if($ec_date < $row['ec_date']) {

						$ec_date = $row['ec_date'];
					}
				}

				$SUM_percent = round(($SUM_percent/$SUM_planned_hrs)*100,1);

				$strSQL2 = "";
				$strSQL2.= "UPDATE pev__wbs ";
				$strSQL2.= "SET due_date = '$due_date', ec_date = '$ec_date', planned_hours = $SUM_planned_hrs, actual_hours = $SUM_actual_hrs, percent_complete = '$SUM_percent' ";
				$strSQL2.= "WHERE wbs_id = ".$parentRow['wbs_id']." and project_id = $strProject";

				$result2 = dbquery($strSQL2);
			}

			return false;
			}
		}
		
	//NEED TO UPDATE/MODIFY TO USE WHEN IMPORTING PROJECTS INTO PAEV
	function AddUpTreeGrid() 
		{
		$strProject = $_SESSION['projectID'];
		echo "[START]: AddUpTreeGrid (Project = $strProject)\n**********************************\n\n";	
		$strSQL = "";
		$strSQL = "SELECT wbs_id, wbs_order, due_date, ec_date, planned_hours, actual_hours FROM pev__wbs WHERE project_id = '$strProject'";
		$result = dbquery($strSQL);
			
		while($row = mysql_fetch_array($result))
			{
			echo "[WBS#: ".$row['wbs_order']."] -------------| BEGIN |----------------\n";
			AddUpTasks($row['wbs_id']);
			echo "[WBS#:".$row['wbs_order']."] --------------| FINISH |----------------\n";
			$TOTAL_planned_hrs=0;
			$TOTAL_actual_hrs=0;
			$TOTAL_percent=0;
			$TOP_due_date='';
			$TOP_ec_date='';
			$count=0;
			
			$strSQL2 = "";
			$strSQL2.= "SELECT WT.wbs_to_task_id, WT.wbs_number, WT.due_date, WT.ec_date, WT.planned_hours, WT.actual_hours, WT.percent_complete, W.wbs_id, WT.parent_id ";
			$strSQL2.= "FROM pev__wbs_to_task AS WT LEFT JOIN pev__wbs AS W ON W.wbs_id = WT.wbs_id ";
			$strSQL2.= "WHERE WT.wbs_id = '".$row['wbs_id']."' AND WT.parent_id = '".addWBSMarker($row['wbs_id'])."' AND W.project_id = '$strProject' ";
			$strSQL2.= "ORDER BY WT.wbs_number DESC";
			$result2 = dbquery($strSQL2);
			
			echo "[WBS#: ".$row['wbs_order']."] ADDING UP 1ST LVL CHILDERN (START)\n";
			while($row2 = mysql_fetch_array($result2))
				{
				echo "    WBS = ".$row2['wbs_number']."\n";
				$count++;
				$TOTAL_planned_hrs = ($TOTAL_planned_hrs+$row2['planned_hours']);
				$TOTAL_actual_hrs = ($TOTAL_actual_hrs+$row2['actual_hours']);
				$TOTAL_percent = $TOTAL_percent+= round(($row2['planned_hours']*$row2['percent_complete']/100),1);
				echo "    Planned_Hours = $TOTAL_planned_hrs \n    Actual_Hours = $TOTAL_actual_hrs \n    Percent = $TOTAL_percent \n";
				
				if($TOP_due_date < $row2['due_date'])
					{
					$TOP_due_date = $row2['due_date'];
					echo "    **DUE_DATE UPDATED**\n";
					}
				if($TOP_ec_date < $row2['ec_date'])
					{
					$TOP_ec_date = $row2['ec_date'];
					echo "    **EC_DATE UPDATED**\n";
					}
				echo "    Due_Date = ".date_read($TOP_due_date)." \n";
				echo "    EC_Date = ".date_read($TOP_ec_date)." \n";
				}
				echo "\n[WBS#: ".$row['wbs_number']." FINISHED ADDING UP 1ST LVL CHILDREN (END)\n\n";
				$TOTAL_percent = round(($TOTAL_percent/$TOTAL_planned_hrs)*100,1);
				echo "TOTAL_Planned_Hours = $TOTAL_planned_hrs \nTOTAL_Actual_Hours = $TOTAL_actual_hrs \nTOTAL_Percent = $TOTAL_percent \n";
				echo "TOP_Due_Date = $TOP_due_date\nTOP_EC_Date = $TOP_ec_date\n";
				$strSQL3 = "";
				$strSQL3.= "UPDATE pev__wbs ";
				$strSQL3.= "SET due_date = '$TOP_due_date', ec_date = '$TOP_ec_date', planned_hours = '$TOTAL_planned_hrs', actual_hours = '$TOTAL_actual_hrs', percent_complete = '$TOTAL_percent' ";
				$strSQL3.= "WHERE wbs_id = ".$row['wbs_id']." AND project_id = $strProject";
				$result3 = dbquery($strSQL3);
				echo "WBS = ".$row['wbs_number']." UPDATED!   <END> \n\n";
			}
			echo "\n\n[END]: AddUpTreeGrid \n************************************\n\n";
		}
		
	function AddUpTasks($wbs_id)
		{
		echo "[START]: AddUpTasks (wbs_id = $wbs_id)\n...\n";
		$strProject = $_SESSION['projectID'];
		$strSQL = "";
		$strSQL.= "SELECT WT.wbs_to_task_id, WT.wbs_number, WT.due_date, WT.ec_date, WT.planned_hours, WT.actual_hours, WT.percent_complete, W.wbs_id, WT.parent_id ";
		$strSQL.= "FROM pev__wbs_to_task AS WT LEFT JOIN pev__wbs AS W ON W.wbs_id = WT.wbs_id ";
		$strSQL.= "WHERE WT.wbs_id = '$wbs_id' AND WT.rollup = '1' AND W.project_id = '$strProject' ";
		$strSQL.= "ORDER BY WT.wbs_number DESC";
		$result = dbquery($strSQL);
		
		while($row = mysql_fetch_array($result))
			{
			echo "WBS = ".$row['wbs_number']."\n=======================\n";
			$SUM_planned_hrs=0;
			$SUM_actual_hrs=0;
			$SUM_percent=0;
			$TOP_due_date='';
			$TOP_ec_date='';
			$count=0;
			
			$strSQL2 = "";
			$strSQL2.= "SELECT WT.wbs_to_task_id, WT.wbs_number, WT.due_date, WT.ec_date, WT.planned_hours, WT.actual_hours, WT.percent_complete, W.wbs_id, WT.parent_id ";
			$strSQL2.= "FROM pev__wbs_to_task AS WT LEFT JOIN pev__wbs AS W ON W.wbs_id = WT.wbs_id ";
			$strSQL2.= "WHERE WT.wbs_id = '$wbs_id' AND WT.wbs_number LIKE '".$row['wbs_number'].".%' AND W.project_id = '$strProject' AND LENGTH(WT.wbs_number) = ".(strlen($row['wbs_number'])+4)." ";
			$strSQL2.= "ORDER BY WT.wbs_number DESC";
			$result2 = dbquery($strSQL2);
			
			while($row2 = mysql_fetch_array($result2))
				{
				echo "    WBS = ".$row2['wbs_number']."\n     Actual Hours = ".$row2['actual_hours']."\n";
				$count++;
				$SUM_planned_hrs = ($SUM_planned_hrs+$row2['planned_hours']);
				$SUM_actual_hrs = ($SUM_actual_hrs+$row2['actual_hours']);
				$SUM_percent = $SUM_percent+= round(($row2['planned_hours']*$row2['percent_complete']/100),1);
				echo "    Planned_Hours = $SUM_planned_hrs \n    Actual_Hours = $SUM_actual_hrs \n    Percent = $SUM_percent \n";
				
				if($TOP_due_date < $row2['due_date'])
					{
					$TOP_due_date = $row2['due_date'];
					echo "    **DUE_DATE UPDATED**\n";
					}
				if($TOP_ec_date < $row2['ec_date'])
					{
					$TOP_ec_date = $row2['ec_date'];
					echo "    **EC_DATE UPDATED**\n";
					}
				echo "    Due_Date = ".date_read($TOP_due_date)." \n";
				echo "    EC_Date = ".date_read($TOP_ec_date)." \n";
				}
			$SUM_percent = round(($SUM_percent/$SUM_planned_hrs)*100,1);
			echo "SUM_Planned_Hours = $SUM_planned_hrs \nSUM_Actual_Hours = $SUM_actual_hrs \nSUM_Percent = $SUM_percent \n";
			echo "TOP_Due_Date = $TOP_due_date\nTOP_EC_Date = $TOP_ec_date\n";
			$strSQL3 = "";
			$strSQL3.= "UPDATE pev__wbs_to_task ";
			$strSQL3.= "SET due_date = '$TOP_due_date', ec_date = '$TOP_ec_date', planned_hours = $SUM_planned_hrs, actual_hours = $SUM_actual_hrs, percent_complete = '$SUM_percent' ";
			$strSQL3.= "WHERE wbs_to_task_id = ".$row['wbs_to_task_id']." AND rollup = '1' AND wbs_number = '".$row['wbs_number']."'";
			$result3 = dbquery($strSQL3);
			echo "WBS = ".$row['wbs_number']." UPDATED!   <END> \n\n";
			echo "END OF: ".$row['wbs_number']."\n=======================\n\n";
			}
		echo "\n\n[END OF]: AddUpTasks\n---------------------------------------\n";
		
		}
?>