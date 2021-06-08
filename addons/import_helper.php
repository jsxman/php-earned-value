<?
function import_ui()
{
	//Check to see if this project is locked....
	$projectID = $_SESSION['projectID'];
	$userID = $_SESSION['paev_userID'];
	$admin = $_SESSION['dbAdmin'];

	if($admin == 0)
	{
		$checkUser = "SELECT is_admin FROM pev__project_access WHERE project_id='$projectID' AND person_id='$userID'";
		$checkUserResult = mysql_query($checkUser) or die("MySQL Error: ".mysql_error()."\n$checkUser");
		if($checkRow = mysql_fetch_array($checkUserResult))
		{
			if($checkRow['is_admin'] == 0)
			{
				//This person is not a project admin or a DB admin
				//return;
			}
		}
		else
		{
			//This person does not belong to this project
			return;
		}
	}

	$getLockedStatus = "SELECT locked FROM pev__project WHERE project_id='$projectID'";
	$lockedResult = mysql_query($getLockedStatus) or die("MySQL Error: ".mysql_error()."\n$getLockedStatus");
	if($lockedRow = mysql_fetch_array($lockedResult))
	{
		$lock = $lockedRow['locked'];
		if($lock)
		{
			//This project is locked so simply return and don't offer an import UI
			//return;
		}
	}
	else
	{
      echo "An error occured while checking the project's lock status.";
      return;
   }

	?>
	<div class="">
		<table width="100%" align="center" class="toolbarpanel">
			<tr>
				<th>Import a Microsoft Project</th>
			</tr>
	<?
	if(($checkRow['is_admin'] != 0 || $admin) && !$lock)
	{
	?>
		<tr>
			<td align="center">File must be in CSV format</td>
		</tr>
		<tr>
			<td align="center">
				<form name="upload_file" enctype="multipart/form-data" action="import.php" method="POST">
					<input type="hidden" name="MAX_FILE_SIZE" value="500000" />
						Choose a file to upload: <input id="upload_file_input" name="uploadedfile" type="file" size="50"/><br />
					<input type="button" value="Upload File" class="but" onclick='upload();'/>
				</form>
			</td>
		</tr>
		
		<?
		}
		else if(($checkRow['is_admin'] != 0 || $admin) && $lock)
		{
		?>
			<tr>
				<td align="center">File must be in CSV format</td>
			</tr>
			<tr>
			<td align="center">
				<form name="upload_file" enctype="multipart/form-data" action="import.php" method="POST">
					<input type="hidden" name="MAX_FILE_SIZE" value="500000" />
						Choose a file to upload: <input id="upload_file_input" name="uploadedfile" type="file" size="50"/><br />
					<input type="button" value="Upload File" class="but" onclick='upload();'/>
				</form>
			</td>
		</tr>

	<?
	}
	else
	{
	?>
		<tr>
			<td align="center">To use this feature, you must be a project admin, and the project must be unlocked.</td>
		</tr>
		
	<?
	}
	?>
	</table>
	</div>

<?
}

function import_file()
{
	$userID = $_SESSION['paev_userID'];
	$projectID = $_SESSION['projectID'];
	include("Importer.php");
	$target_path = "addons/import_uploads/";
	$fileName = preg_replace("/\s*/", "", basename($_FILES['uploadedfile']['name'])); //remove spaces
	
	//Check that it is the right filetype
	$fileCheck = explode(".", $fileName);
	if(!isset($fileCheck[1]) || ($fileCheck[1] != "txt" && $fileCheck[1] != "csv") && $fileCheck[1] != "CSV")
	{
		unlink($_FILES['uploadedfile']['tmp_name']);
		echo "This is the wrong file type";
		return 0;
	}
	$target_path = $target_path.$fileName;
	if(move_uploaded_file($_FILES['uploadedfile']['tmp_name'],$target_path))
	{
		set_status("The file ".$fileName." has been uploaded");
	}
	else
	{
		set_error("There was an error uploading the file, please try again!");
		return 0;
	}

	//Import file data
	$importer = new Importer($target_path, $projectID ,$userID);
	if(!$importer->readData())
	{
		set_error($importer->getErrors());
		return 0;
	}

	//Remove the file
	unlink($target_path);

	//Successful Import!
	return 1;

}

function display_import()
{
	$userID = $_SESSION['paev_userID'];
	$projectID = $_SESSION['projectID'];
   
	$getLockedStatus = "SELECT locked FROM pev__project WHERE project_id='$projectID'";
	$lockedResult = mysql_query($getLockedStatus);
	if($lockedRow = mysql_fetch_array($lockedResult))
	{
		$lock = $lockedRow['locked'];
		if($lock)
		{
			//This project is locked, only pulls Name, WBS, ETC, $ Percent Complete from pev__temp_tasks table
			?>
         <script>
				alert("This Project is LOCKED! You can ONLY IMPORT Actual Hours, ETCs, & Percent Completes?\n(All other data from import will be disregarded).");
			</script>
			<?
			$getImportedData = "SELECT id, task_name, three_switch, is_wbs, wbs, ec_date, actual_hours, percent_complete, wbs_type";
			$getImportedData.= " FROM pev__temp_tasks WHERE uploader_id='$userID' ORDER BY wbs ASC";
			$result6 = mysql_query($getImportedData) or die("MySQL error: ".mysql_error());
		}
		else
		{
			$getImportedData = "SELECT id, task_name, three_switch, is_wbs, wbs, due_date, ec_date, plan_hours, actual_hours, percent_complete, person_id, person_name, wbs_type";
			$getImportedData.= " FROM pev__temp_tasks WHERE uploader_id='$userID' ORDER BY wbs ASC";
			$result6 = mysql_query($getImportedData) or die("MySQL error: ".mysql_error());
		}
	}
	else
	{
		echo "An error occured while checking the project's lock status.";
		return;
	}
	?>
	<script type="text/javascript" src="scripts/jQuery/jQuery/development-bundle/jquery-1.6.2.js"></script>
	<script type="text/javascript" src="scripts/jQuery/jQuery/js/jquery-ui-1.8.16.custom.min.js"></script>

	<br/><br/>
	
	<?
	if ($lock){
	echo '<div align="center">';
	echo '<p align="center" style="background-color:#30ff29; border:1px solid #555555; padding:0px; margin:0px; width:200px; font-style: bold; font-size:20px">PROJECT LOCKED</p>';
	echo '<p align="center" style="color:red"> Limited updates may be made</p>';
	echo '</div>';
	}
	else if (!$lock){
	echo '<div align="center">';
	echo '<p align="center" style="background-color:#ffbd24; border:1px solid #555555; padding:0px; margin:0px; width:200px; font-style: bold; font-size:20px">PROJECT UNLOCKED</p>';
	echo '<p align="center" style="color:red"> Changes are highlighted. Imported information will replace current project information.</p>';
	echo '</div>';
	}
	
	?>
	
	<div align="center">
	<form name="myform">
		<div style="border:1px solid #555555; padding:1px; margin:10px;">
			<table width="100%" style="border-collapse: collapse; background-color: #ddd;">
				<tr style="background-color: #999999; height:30px; border-bottom: 1px solid #555555;">
					<?
					if($lock)
					{
					?>
						<th width="10%">Ignore</th>
						<th width="10%">Found in Project</th>
						<th width="50%">Name</th>
						<th width="10%">Est. Comp. Date</th>
						<th width="10%">Actual Hours</th>
						<th width="10%">% Complete</th>
					<?
					}
					else
					{
					?>
						<th>Ignore</th>
						<th>Found in Project</th>
						<th>Name</th>
						<th>WBS</th>
						<th>POC</th>
						<th>Due Date</th>
						<th>Est. Comp. Date</th>
						<th align="right">Plan Hours</th>
						<th align="right">Actual Hours</th>
						<th align="right">% Complete</th>
					<?
					}
					?>
				</tr>
				<tr>
				<?
				while($row = mysql_fetch_array($result6))
				{
					if($lock && $row['id'] == 0)  /* If the id field is zero, this means that the Importer object could not find this task in the existing project, so the task cannot be imported into the locked project and is highlighted orange   */
					{
							echo "<tr style='font-style: bold; background-color: #FF6600;'>\n";
							echo "<td align='center'>
										<input type='checkbox' name='ignore_task' value='true'CHECKED DISABLED/>
									</td>\n";
					}
					else if(!$lock && $row['id'] == 0) /*No matching existing task was found, but the project is unlocked so we just highlight it yellow so peopel are aware that this is a new task */
					{
							echo "<tr style='font-style: bold; background-color: #f0ff1c;'>\n";
							echo "<td align='center'>
										<input type='checkbox' name='ignore_wbs' value='{".$row['wbs']."}' onclick='toggle_wbs_ignore(this);'/>
									</td>\n";
					}
					
					else if($row['is_wbs'])  /* if the field 'is_wbs' is activated when Importer is importing the project, then this row is a main WBS, and is highlighted green to show its signifigance */
					{
						echo "<tr style='background-color: #AACCCC;'>\n
									<td align='center'>
										<input type='checkbox' name='ignore_wbs' value='{".$row['wbs']."}' onclick='toggle_wbs_ignore(this);'/>
									</td>\n";
					}
					else if ($row['wbs_type'] != 2) /*These task are ignored. */
					{
						echo "<tr style='background-color: #FFFFFF;'>\n
									<td align='center'>
										<input type='checkbox' name='ignore_task' value='{".$row['wbs']."}'/>
									</td>\n";
					}
					else /*All other task are just printed with a white background */
					{
						echo "<tr>\n";
						echo "<td align='center'>
									<input type='checkbox' name='ignore_task' value='{".$row['wbs']."}' onclick='toggle_wbs_ignore(this);'/>
								</td>\n";
					}
					
					if($row['id'] == 0) /* Don't display the checkmark if the task is not already in the project */
					{
						echo "   <td align='center'></td>\n";
					}
					else /*displays checkmark to signify that task was in previous project */
					{
						echo "   <td align='center'><img src='images/check.png'></td>\n";
					}
					$acount=count_chars($row['wbs'],0);
					$count=$acount['46'];
					$i=0;
					if ($count == 0) 
					{
						echo "   <td style='text-align:left;'>{$row['task_name']}</td>\n";
					}
					else
					{
						$depth="";
						$i=0;
						while ($i < $count) 
						{
							$depth="___".$depth;
							$i++;
						}
						echo "   <td style='text-align:left;'>".$depth."{$row['task_name']}</td>\n";
					}
					$wbs = $row['wbs'];
					
					if($lock) /*I think threeswitch is set in the Importer if the information in one of these fields is different that the information in the existing project, this is used to show which fields can be updated for a locked project */
					{
							if($row['three_switch']{1} == "1" & $row['id'] != 0)
							{
								echo "   <td align='center' style='font-size: 12pt; font-style: bold; background-color: #99FF33;'>".date_read($row['ec_date'])."</td>\n";
							}
							else
							{
								echo "   <td align='center'>".date_read($row['ec_date'])."</td>\n";
							}
							if($row['three_switch']{0} == "1" & $row['id'] != 0) 
							{
								echo "   <td align='right' style='font-size: 12pt; font-style: bold; background-color: #99FF33;'>{$row['actual_hours']}</td>\n";
							}
							else
							{
								echo "   <td align='right'>{$row['actual_hours']}</td>\n";
							}
							if($row['three_switch']{2} == "1" & $row['id'] != 0) 
							{
								echo "   <td align='right' style='font-size: 12pt; font-style: bold; background-color: #99FF33;'>{$row['percent_complete']}</td>\n";
							}
							else
							{
								echo "   <td align='right'>{$row['percent_complete']}</td>\n";
							}
							if($row['three_switch'] == "000" & $row['id'] != 0)
							{
								
							}
					}
					else
					{
						echo "   <td align='left'>$wbs</td>\n";
						echo "   <td>{$row['person_name']}</td>\n";
						
						if($row['three_switch']{4} == "1" & $row['id'] != 0) {
						echo "   <td align='center' style='background-color:yellow'>".date_read($row['due_date'])."</td>\n";
						}
						else {
						echo "   <td align='center'>".date_read($row['due_date'])."</td>\n";
						}
						
						if($row['three_switch']{1} == "1" & $row['id'] != 0) {
						echo "   <td align='center' style='background-color:yellow'>".date_read($row['ec_date'])."</td>\n";
						}
						else {
						echo "   <td align='center'>".date_read($row['ec_date'])."</td>\n";
						}
						
						if($row['three_switch']{3} == "1" & $row['id'] != 0) {
						echo "<td align='right' style='background-color:yellow'>{$row['plan_hours']}</td>\n";
						}
						else{
						echo "<td align='right'>{$row['plan_hours']}</td>\n";
						}
						
						if($row['three_switch']{0} == "1" & $row['id'] != 0) {
						echo "   <td align='right' style='background-color:yellow'>{$row['actual_hours']}</td>\n";
						}
						else {
						echo "   <td align='right'>{$row['actual_hours']}</td>\n";
						}
						
						if($row['three_switch']{2} == "1" & $row['id'] != 0) {
						echo "   <td align='right' style='background-color:yellow'>{$row['percent_complete']}</td>\n";
					    }
						else {
						echo "   <td align='right'>{$row['percent_complete']}</td>\n";
						}
					}
					echo "</tr>\n";
				}
				?>
			</table>
			
			<table width="80%" align="center" style="border-collapse: collapse; background-color: #ddd;">
				<tr>
					<td style="background-color: #FFFFFF;">
						<?if ($lock) 
						{?>
							<img align="center" src='images/locked-legend.png' alt='LockedLegend'/>
						<?}
						else 
						{?>
							<img align="center" src='images/legend.png' alt='Legend'/>
						<?}?>
					</td>
				</tr>
				<tr>
					<td style="background-color: #FFFFFF;"></td>
				</tr>
			</table>
		</div>
		
		<?
		//Get list of all users in db
		$getDBUsers = "SELECT first, last, person_id FROM pev__person ORDER BY last, first";
		$userResult = mysql_query($getDBUsers) or (set_error("MySQL Error: ".mysql_error()."<br/> $getDBUsers"));
		$optionList = "<option value='-1'>Remove this person</option><option value='0' selected='selected'>No Action</option>";
		while($row = mysql_fetch_array($userResult))
		{
			$optionList.= "<option value='{$row['person_id']}'>{$row['last']}, {$row['first']}</option>";
		}

		//Here we need to create a mapping table that allows the user connect an unknown POC with an actual person in the database
		$getTempPeople = "SELECT person_id, person_name FROM pev__temp_tasks WHERE uploader_id='$userID' AND person_name != '' GROUP BY person_name ORDER BY person_name";
		$peopleResult = mysql_query($getTempPeople) or (set_error("MySQL Error: ".mysql_error()."<br/> $getTempPeople"));
		$peopleInProject = false;

		?>
		
		<br/><br/>
		<div>
			<input type="hidden" id="member_reassignments" value=""/>
			
			<table width="50%" align="center" style="border-collapse: collapse; background-color: #ddd;">
         	<tr>
         		<th colspan="3">Project Member Verification</th>
         	</tr>
				<tr>
					<th>Project Member</th>
					<th align="center">Found in Database?</th>
					<th align="center">Assign member's tasks to...</th>
				</tr>
				
				<?
				while($row = mysql_fetch_array($peopleResult))
				{
					$peopleInProject = true;
					echo "<tr>\n";
					echo "   <td>{$row['person_name']}</td>\n";
					
					if($row['person_id'] == 0)
					{
						echo "   <td align='center'><img src='images/cancel.png'></td>";
					}
					else
					{
						echo "   <td align='center'><img src='images/check.png'></td>\n";
					}
					echo "   <td align='center'><select onchange='change_reassignment(\"{$row['person_name']}\", this);'>$optionList</select></td>\n";
					echo "</tr>\n";
				}
				if(!$peopleInProject)
				{
					//There is nobody listed in the project
					echo "<tr><td colspan='3' align='center'>No project members were listed in the import file</td></tr>";
				}
				?>
			</table>
			<br/>
			
			<table align="center" width="50%" style="padding:10px; border-collapse: collapse; background-color: #ddd;">
				<tr>
					
					<?
					if (!$lock){
					echo '<th colspan="2" style="color:red">This import will replace all existing project information. This cannot be reversed.</th>';
	                }
					else if ($lock){
					echo '<th colspan="2" style="color:red">This import will update all indicated existing project information. This cannot be reversed.</th>';
					}
					?>			
				
				</tr>
				<tr>
				<!--	<th>Option</th>
					<th>Selection</th> -->
				</tr>
					<?
					if($lock)
					{
					?><tr><td></td></tr><?
					}
					else
					{
					?>
						<tr style="display:none"> <!-- This field still exist, but is hidden. Default action should be to delete previous information when doing imports, the user should not see this option field or be able to use it. 
													 The default on this field is set to 'yes', it was the easiest way to force the delete as default without risking bugs by changing the code and removing the field.   -->
							<td>Delete all other WBS items and tasks from the project that are not imported?</td>
							<td align="center" ><input type="radio"  value="1" name="delete_tasks" />No <input type="radio" value="1"  name="delete_tasks" checked />Yes</td>
						</tr>
					<?
					}
					?>
				<tr>
					<td colspan="2" align="center"><input type="button" class="but" value="Finish Import" onclick='finishImport();'/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						<input type="button" class="but" value="Cancel" onclick="window.location='tasks.php';"/>
					</td>
				</tr>
			</table>
		</div>
	</form>
	<br />
	<br />
	<br />
	</div>

<script>
	function change_reassignment(person_id, sel_box){
		//Format: (personID_assignment,)
		var reassignments = document.getElementById('member_reassignments').value;
		//alert(reassignments);
		var assignmentArray = reassignments.split("|");
		var userFound = false;
		var new_reassignment = '';
		
		for(var t=0; t<assignmentArray.length-1; t++)
		{
			//alert(assignmentArray[t]);
			var tArray = assignmentArray[t].split("_");
			if(tArray[0] == person_id)
			{
				new_reassignment += tArray[0]+"_"+sel_box.value+"|";
				userFound = true;
			}
			else
			{
				new_reassignment += tArray[0]+"_"+tArray[1]+"|";
			}
		}
		if(!userFound)
		{
			new_reassignment += person_id+"_"+sel_box.value+"|";
		}
		//alert(new_reassignment);
		document.myform.elements['member_reassignments'].value = new_reassignment;
	}

	function finishImport()
	{
		var answer = confirm("Are you sure you want to complete the import process?\nThis process cannot be undone.");
		if(answer == true)
		{
			var box = createMsgBox();
			var reassignments = document.getElementById('member_reassignments').value;
			var delete_tasks_radio = document.getElementsByName('delete_tasks');
			var ignore_tasks = document.getElementsByName('ignore_task');
			var ignore_list = '';
			var ignore_wbs = document.getElementsByName('ignore_wbs');

			for(var i=0; i<delete_tasks_radio.length; i++)
			{
				if(delete_tasks_radio[i].checked)
				{
						var delete_tasks = delete_tasks_radio[i].value;
				}
			}
			for(var i=0; i<ignore_tasks.length; i++)
			{
				if(ignore_tasks[i].checked)
				{
					ignore_list += ignore_tasks[i].value+',';
				}
			}
			for(var i=0; i<ignore_wbs.length; i++)
			{
				if(ignore_wbs[i].checked)
				{
					ignore_list += ignore_wbs[i].value+',';
				}
			}

			if(answer == true) {
				$.ajax({
						type: 'POST',
						url: 'import.php',
						data: { action: "finish_import", reassign: reassignments, deletes: delete_tasks, ignore: ignore_list },
						success: function (data) {
							$.ajax({
								type: 'POST',
								url: "ajax_main.php",
								data: { action: "add_up_treegrid" },
								success: function (data) {
									window.location='tasks.php';
								},
								error: function () {
									alert("Recalculating not successful: An error has occured.\nContact a PAEV admin.\n");
								}
							});
						},
						error: function () {
							alert("Import was not successful.\nContact a PAEV admin for further assistance.\n");
						}
				});
			}
		}
		else
		{
			alert("No Action Performed");
		}
	}

	function toggle_wbs_ignore(checkbox)
	{
		var wbs = checkbox.value;
		wbs = wbs.replace("{","");
		wbs = wbs.replace("}","");
		//alert(wbs);
		var ignore_tasks = document.getElementsByName('ignore_task');
		//alert(wbs);
		for(var i=0; i<ignore_tasks.length; i++)
		{
			var wbs_hierarchy = ignore_tasks[i].value;
			var parent_wbs = wbs_hierarchy;
			parent_wbs = parent_wbs.replace("{","");
			parent_wbs = parent_wbs.replace("}","");
			var subWBS;
			var pos = wbs.lastIndexOf(".");
			//alert(pos);
			if(pos > "0") 
			{
				pos = pos+4;
				subWBS = parent_wbs.substring(0,pos);
			}
			else
			{
				subWBS = parent_wbs.substring(0,3);
				
			}
			//alert(subWBS+"    "+wbs);
			if(subWBS == wbs && checkbox.checked)
			{
				ignore_tasks[i].checked = true;
				//alert(parent_wbs+"     "+wbs);
				if(parent_wbs != wbs)
				{
					ignore_tasks[i].disabled = true;
				}
			}
			else if(subWBS == wbs && !checkbox.checked)
			{
				ignore_tasks[i].disabled = false;
				ignore_tasks[i].checked = false;
			}
		}
	}

	function createMsgBox()
	{
		str = "<div class=mwin>";
		str+= "<table align='center'>";
		str+= "<tr><td align='center'><img src='images/ajax-loader.gif'/></td></tr>";
		str+= "<tr><td align='center'>Please Wait...</td></tr>";
		str+= "</table>";
		str+= "</div>";
		return str;
	}
</script>
<?
}
?>