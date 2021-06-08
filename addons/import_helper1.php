<?
function import_ui(){
   //Check to see if this project is locked....
   $projectID = $_SESSION['projectID'];
   $userID = $_SESSION['paev_userID'];
   $admin = $_SESSION['dbAdmin'];

   if($admin == 0){
      $checkUser = "SELECT is_admin FROM pev__project_access WHERE project_id='$projectID' AND person_id='$userID'";
      $checkUserResult = mysql_query($checkUser) or die("MySQL Error: ".mysql_error()."\n$checkUser");
      if($checkRow = mysql_fetch_array($checkUserResult)){
         if($checkRow['is_admin'] == 0){
            //This person is not a project admin or a DB admin
            //return;
         }
      }else{
         //This person does not belong to this project
         return;
      }
   }

   $getLockedStatus = "SELECT locked FROM pev__project WHERE project_id='$projectID'";
   $lockedResult = mysql_query($getLockedStatus) or die("MySQL Error: ".mysql_error()."\n$getLockedStatus");
   if($lockedRow = mysql_fetch_array($lockedResult)){
      $lock = $lockedRow['locked'];
      if($lock){
         //This project is locked so simply return and don't offer an import UI
         //return;
      }
   }else{
      echo "An error occured while checking the project's lock status.";
      return;
   }

?>
<div class="">
   <table width="100%" align="center">
      <tr>
         <th>Import a Microsoft Project</th>
      </tr>
<?
if(($checkRow['is_admin'] != 0 || $admin) && !$lock){
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
<?}else{?>
      <tr>
         <td align="center">To use this feature, you must be a project admin, and the project must be unlocked.</td>
      </tr>
<?}?>
   </table>
</div>

<?
}

function import_file(){
   $userID = $_SESSION['paev_userID'];
   $projectID = $_SESSION['projectID'];
   include("Importer.php");
   $target_path = "addons/import_uploads/";
   $fileName = preg_replace("/\s*/", "", basename($_FILES['uploadedfile']['name'])); //remove spaces
   //Check that it is the right filetype
   $fileCheck = explode(".", $fileName);
   if(!isset($fileCheck[1]) || ($fileCheck[1] != "txt" && $fileCheck[1] != "csv") && $fileCheck[1] != "CSV"){
      unlink($_FILES['uploadedfile']['tmp_name']);
      echo "This is the wrong file type";
      return 0;
   }
   $target_path = $target_path.$fileName;
   if(move_uploaded_file($_FILES['uploadedfile']['tmp_name'],$target_path)){
      set_status("The file ".$fileName." has been uploaded");
   }else{
      set_error("There was an error uploading the file, please try again!");
      return 0;
   }

   //Import file data
   $importer = new Importer($target_path, $projectID ,$userID);
   if(!$importer->readData()){
      set_error($importer->getErrors());
      return 0;
   }

   //Remove the file
   unlink($target_path);

   //Successful Import!
   return 1;

}

function display_import(){
   $userID = $_SESSION['paev_userID'];
   $projectID = $_SESSION['projectID'];

   $getLockedStatus = "SELECT locked FROM pev__project WHERE project_id='$projectID'";
   $lockedResult = mysql_query($getLockedStatus);
   if($lockedRow = mysql_fetch_array($lockedResult)){
      $lock = $lockedRow['locked'];
      if($lock){
         //This project is locked so simply return and don't offer an import UI
         echo "An error has occured. This project is locked and cannot accept imports at this time.";
         return;
      }
   }else{
      echo "An error occured while checking the project's lock status.";
      return;
   }

   $getImportedData = "SELECT id, task_name, is_wbs, wbs, due_date, ec_date, plan_hours, actual_hours, percent_complete, person_id, person_name";
   $getImportedData.= " FROM pev__temp_tasks WHERE uploader_id='$userID' ORDER BY wbs ASC, is_wbs DESC";
   $result = mysql_query($getImportedData) or die("MySQL error: ".mysql_error());

?>
   <script src="modalbox/lib/prototype.js" type="text/javascript"></script>
   <script src="modalbox/lib/scriptaculous.js" type="text/javascript"></script>
   <script src="modalbox/lib/builder.js" type="text/javascript"></script>
   <script src="modalbox/lib/effects.js" type="text/javascript"></script>
   <script src="modalbox/modalbox.js" type="text/javascript"></script>
   <link media="screen" type="text/css" href="modalbox/modalbox.css" rel="stylesheet">

   <br/><br/>
   <form name="myform">
   <div>
      <table width="80%" align="center" style="border-collapse: collapse; background-color: #ddd;">
         <tr>
            <th>Ignore</th>
            <th>Found in Project</th>
            <th>Name</th>
            <th>WBS</th>
            <th>POC</th>
            <th>Due Date</th>
            <th>Est. Comp. Date</th>
            <th>Plan Hours</th>
            <th>Actual Hours</th>
            <th>% Complete</th>
         </tr>
<?
   while($row = mysql_fetch_array($result)){
      if($row['is_wbs']){
         echo "<tr class='highlightrow'>\n";
         echo "<td align='center'><input type='checkbox' name='ignore_wbs' value='{$row['wbs']}' onclick='toggle_wbs_ignore(this);'/></td>\n";
      }else{
         echo "<tr>\n";
         echo "<td align='center'><input type='checkbox' name='ignore_task' value='{$row['wbs']}'/></td>\n";
      }
      if($row['id'] == 0){
         echo "   <td align='center'></td>\n";
      }else{
         echo "   <td align='center'><img src='images/check.png'></td>\n";
      }
      echo "   <td>{$row['task_name']}</td>\n";
      $wbs = explode(".", $row['wbs']);
      echo "   <td align='center'>$wbs[0]</td>\n";
      echo "   <td>{$row['person_name']}</td>\n";
      echo "   <td align='center'>".date_read($row['due_date'])."</td>\n";
      echo "   <td align='center'>".date_read($row['ec_date'])."</td>\n";
      echo "   <td align='right'>{$row['plan_hours']}</td>\n";
      echo "   <td align='right'>{$row['actual_hours']}</td>\n";
      echo "   <td align='right'>{$row['percent_complete']}</td>\n";
      echo "</tr>\n";
   }
?>
      </table>
   </div>
<?
   //Get list of all users in db
   $getDBUsers = "SELECT first, last, person_id FROM pev__person ORDER BY last, first";
   $userResult = mysql_query($getDBUsers) or (set_error("MySQL Error: ".mysql_error()."<br/> $getDBUsers"));
   $optionList = "<option value='-1'>Remove this person</option><option value='0' selected='selected'>No Action</option>";
   while($row = mysql_fetch_array($userResult)){
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
         <tr><th colspan="3">Project Member Verification</th></tr>
         <tr>
            <th>Project Member</th>
            <th align="center">Found in Database?</th>
            <th align="center">Assign member's tasks to...</th>
         </tr>
<?
   while($row = mysql_fetch_array($peopleResult)){
      $peopleInProject = true;
      echo "<tr>\n";
      echo "   <td>{$row['person_name']}</td>\n";
      if($row['person_id'] == 0){
         echo "   <td align='center'><img src='images/cancel.png'></td>";
      }else{
         echo "   <td align='center'><img src='images/check.png'></td>\n";
      }
      echo "   <td align='center'><select onchange='change_reassignment(\"{$row['person_name']}\", this);'>$optionList</select></td>\n";
      echo "</tr>\n";
   }
   if(!$peopleInProject){
      //There is nobody listed in the project
      echo "<tr><td colspan='3' align='center'>No project members were listed in the import file</td></tr>";
   }
?>
      </table>
      <br/>
      <table align="center" width="40%" style="border-collapse: collapse; background-color: #ddd;">
         <tr>
            <th colspan="2">Project Options</th>
         <tr>
         <tr>
            <th>Option</th>
            <th>Selection</th>
         </tr>
         <tr>
            <td>Delete all other WBS items and tasks from the project that are not imported?</td>
            <td align="center"><input type="radio" value="0" name="delete_tasks" checked/>No <input type="radio" value="1" name="delete_tasks" />Yes</td>
         </tr>
         <tr>
            <td colspan="2" align="center"><input type="button" class="but" value="Finish Import" onclick='finishImport();'/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                  <input type="button" class="but" value="Cancel" onclick="window.location='tasks.php';"/>
            </td>
         </tr>
      </table>
   </div>
   </form>
<script>
function change_reassignment(person_id, sel_box){
   //Format: (personID_assignment,)
   var reassignments = $('member_reassignments').value;
   //alert(reassignments);
   var assignmentArray = reassignments.split("|");
   var userFound = false;
   var new_reassignment = '';
   for(var t=0; t<assignmentArray.length-1; t++){
      //alert(assignmentArray[t]);
      var tArray = assignmentArray[t].split("_");
      if(tArray[0] == person_id){
         new_reassignment += tArray[0]+"_"+sel_box.value+"|";
         userFound = true;
      }else{
         new_reassignment += tArray[0]+"_"+tArray[1]+"|";
      }
   }
   if(!userFound){
      new_reassignment += person_id+"_"+sel_box.value+"|";
   }
   $('member_reassignments').value = new_reassignment;
   //alert(new_reassignment);
}

function finishImport(){
   var answer = confirm("Are you sure you want to complete the import process?\nThis process cannot be undone.");
   if(answer == true){
      var box = createMsgBox();
      Modalbox.show(box, {title: "Working", width: 100});
      var reassignments = $('member_reassignments').value;
      var delete_tasks_radio = document.getElementsByName('delete_tasks');
      var ignore_tasks = document.getElementsByName('ignore_task');
      var ignore_list = '';
      var ignore_wbs = document.getElementsByName('ignore_wbs');
      for(var i=0; i<delete_tasks_radio.length; i++){
        if(delete_tasks_radio[i].checked){
           var delete_tasks = delete_tasks_radio[i].value;
        }
       }
      for(var i=0; i<ignore_tasks.length; i++){
         if(ignore_tasks[i].checked){
           ignore_list += ignore_tasks[i].value+',';
         }
      }
      for(var i=0; i<ignore_wbs.length; i++){
        if(ignore_wbs[i].checked){
           ignore_list += ignore_wbs[i].value+',';
        }
     }

      var data = '&reassign='+reassignments+'&delete='+delete_tasks+'&ignore='+ignore_list;
      new Ajax.Request('import.php?action=finish_import'+data,{
         onSuccess: function(response){
            if(response.responseText == 1){
               Modalbox.hide();
               //alert("Import Successful!\nCarefully inspect the project data before locking the project.");
               window.location='tasks.php';
            }else{
               Modalbox.hide();
               alert("Import was not successful.\nContact a PAEV admin for further assistance.\n"+response.responseText);
            }
         }
      });
   }else{
      alert("No Action Performed");
   }
}

function toggle_wbs_ignore(checkbox){
   var wbs = checkbox.value;
   var ignore_tasks = document.getElementsByName('ignore_task');

   for(var i=0; i<ignore_tasks.length; i++){
      var wbs_hierarchy = ignore_tasks[i].value;
      var parent_wbs = wbs_hierarchy.split(".", 2);
      if(parent_wbs[0] == wbs && checkbox.checked){
         ignore_tasks[i].checked = true;
         ignore_tasks[i].disabled = true;
      }else if(parent_wbs[0] == wbs && !checkbox.checked){
         ignore_tasks[i].disabled = false;
         ignore_tasks[i].checked = false;
      }
   }
}

function createMsgBox(){
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
