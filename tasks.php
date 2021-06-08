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
//require_once('../pqp/classes/PhpQuickProfiler.php');
//$profiler = new PhpQuickProfiler(PhpQuickProfiler::getMicroTime());
//Console::logMemory();
//Console::logSpeed();
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

/********************************************
*	        PERMISSIONS CHECK	    *
********************************************/
$strProject =isset($_FORM['txt_project'])?postedData($_FORM['txt_project']):0;
$previous =isset($_REQUEST['previous']) && $_REQUEST['previous'] == 1?1:0;

if(isset($_REQUEST['txt_project'])){
   $strProject = postedData($_REQUEST['txt_project']);
}
$strProject = getProject($strProject);

$userId = $_SESSION['paev_userID'];
$projectAdmin = isProjectAdmin($strProject, $userId);
$dbAdmin = $_SESSION['dbAdmin'];

$USER_ACCESS=accessLevel($strProject);
debug(10,"User is a DB-Admin if this is a 1(".$_SESSION['dbAdmin'].")");
debug(10,"User is a Admin for project($strProject) if this is a $ACCESS_ADMIN($USER_ACCESS)");
debug(10,"User is a 'USER' for project($strProject) if this is a $ACCESS_USER($USER_ACCESS)");
/********************************************
*	    END PERMISSIONS CHECK	    *
********************************************/
if($strProject > 0){
   $checkProject = "SELECT project_id FROM pev__project WHERE project_id=$strProject";
   $projRow = mysql_fetch_array(dbquery($checkProject));
   if($projRow['project_id'] == NULL){
      set_status("Please select a project");
      $TITLE="Project Tasks";
      show_header();
      show_menu("PROJECTS");
      
	  show_status();
      projectSelector("tasks.php", 0);
      show_footer();
	  echo '</div>';
      exit;
   }
}
include("addons/import_helper.php");
include("treegrid_functions.php");


if(isset($_FORM['txt_action2']) && $_FORM['txt_action2'] == "Record History"){
   recordHistory($strProject);
} // end - Record History 

?>
<!DOCTYPE HTML>
<meta http-equiv="X-UA-Compatible" content="IE=8" >
<!--<script type="text/javascript" src="scripts/prototype.js"></script>-->
<!--<script type="text/javascript" src="modalbox/lib/scriptaculous.js"></script>-->
<!--<script type="text/javascript" src="modalbox/modalbox.js"></script>-->

<script type="text/javascript" src="scripts/jQuery/jQuery/development-bundle/jquery-1.6.2.js"></script>

<script type="text/javascript" src="scripts/jQuery/jQuery/js/jquery-ui-1.8.16.custom.min.js"></script>

<!--<script type="text/javascript" src="scripts/jQuery/jquery.ui.js"></script>-->
<script type="text/javascript" src="scripts/tasks.js"></script>
<link rel="stylesheet" href="modalbox/modalbox.css" type="text/css" media="screen">
<link href="scripts/jQuery/master.css" rel="stylesheet" type="text/css" />
<link href="scripts/jQuery/style.css" rel="stylesheet" type="text/css" />
<link href="scripts/jQuery/treegrid.css" rel="stylesheet" type="text/css" />

<link href="scripts/jQuery/jquery.treeTable.css" rel="stylesheet" type="text/css" />
<link type="text/css" href="scripts/jQuery/jQuery/css/redmond/jquery-ui-1.8.16.custom.css" rel="stylesheet" />
<script type="text/javascript" src="scripts/jQuery/persist.js"></script>	
<script type="text/javascript" src="scripts/jQuery/jquery.treeTable.js"></script>
<script type="text/javascript">
$(document).ready(function () {
	 var selectedRow = "";
    $(".treegridbackground").treeTable(
		{
			 persist: true,  //Allows 
		    initialState: "collapsed"
		});

//     $("#treegrid .file, #treegrid .folder").draggable(
// 		{
// 		    helper: "clone",
// 		    opacity: .75,
// 		    refreshPositions: true,
// 		    revert: "invalid",
// 		    revertDuration: 300,
// 		    scroll: true
// 		});
// 
//     $("#treegrid .folder").each(function () {
//         $($(this).parents("tr")[0]).droppable(
// 			{
// 			    accept: ".file, .folder",
// 			    drop: function (e, ui) {
// 			        $($(ui.draggable).parents("tr")[0]).appendBranchTo(this);
// 
// 			        // Issue a POST call to send the new location (this) of the 
// 			        // node (ui.draggable) to the server.
// 			        $.post("move.php", { id: $(ui.draggable).parents("tr")[0].id, to: this.id });
// 			    },
// 			    hoverClass: "accept",
// 			    over: function (e, ui) {
// 			        if (this.id != $(ui.draggable.parents("tr.parent")[0]).id && !$(this).is(".expanded")) {
// 			            $(this).expand();
// 			        }
// 			    }
// 			});
//     });
    
     //Filters Project Data Table by User
	$("#filter").change(function() {
		$("table#treegrid").expandAll();
		$("#filter option:selected").each(function () {
			id = $(this).attr('value');
			$('#filter option').each(function(value) { 
				var temp = $(this).attr('value');
				$("td[class="+temp+"]").parents('tr').removeClass("expanded").addClass("ui-helper-hidden").addClass("collapsed");
				if(id == "showALL") {
					$("td[class="+temp+"]").parents('tr').removeClass("collapsed").removeClass("ui-helper-hidden").addClass("expanded");
				}
				else {
					if(temp == id){
						$("td[class="+id+"]").parents('tr').removeClass("collapsed").removeClass("ui-helper-hidden").addClass("expanded");
					}
					else
					{
						$("td[class="+temp+"]").parents('tr').removeClass("expanded").addClass("ui-helper-hidden").addClass("collapsed");
					}
				}
				
			});
		});
	});

	   
	$("#expand").click(function () {
		$("table#treegrid").expandAll(); 
	});

	$("#collapse").click(function () {
		$("#filter option").filter(function() {
			return $(this).text() == "Show All";
		}).attr('selected', true);
		$("table#treegrid").collapseAll(); 
	});
    
	// Make visible that a row is clicked
	$("table#treegrid tbody tr").mousedown(function () {
		$("tr.selected").removeClass("selected"); // Deselect current selected row
		$(this).addClass("selected");
		selectedRow = this.id.substr(5);  //Saves selected row
	});


    // Double-Click Treegrid row 
    $("table#treegrid tbody tr").dblclick(function () {
        var previous = <?php echo $previous?>;           
        var rowId = this.id.substr(5);
        var pId = "<?php echo $strProject?>";
        if(previous == 0) {
            if($(this).hasClass("project-summary") | $(this).hasClass("blank")) {
            }
            else {
		      	if($(this).hasClass() == "wbs") {
		        		$( "#dialog" ).dialog( "option", "height", 800 );
		        	}
		        	$.ajax({
		            type: 'POST',
		            url: 'ajax_main.php',
		            data: { action: "edit_treegrid_row", id: rowId },
		            beforeSend: function () {
		                // apply loading image
		                $('#ajax-panel').html('<div class="loading"><img src="/images/ajax-loader.gif" alt="Loading..." /></div>');
		            },
		            success: function (data) {
			            
		            	var $editTask = $("<div></div>")
		            		.html(data)
								.dialog({
			      				autoOpen: false,
			        				width: 550,
			        				modal: true,
			        				closeOnEscape: true,
			        				resizable: false,
			        				title: "Edit Task",
			        				buttons: {
		                        "Ok": function () {
		                                dataString = "action=update_treegrid&"+$("#editRow").serialize();
		                                $.ajax({
		                                    type: "POST",
		                                    url: "ajax_main.php",
		                                    data: dataString,
		                                    
		                                    success: function (data) {
		                                    	$.ajax({
		                                        	url: "ajax_main.php",
		                                        	data: { action: "recalculate_affectedarea", RowId: selectedRow },
		                                        	success: function (data) {
		                    	                        //$(this).dialog('close');
		                    	                        document.location.href='tasks.php';
		                	                        },
		                	                        error: function () {
		                    	                        // fail request
		                    	                        $(this).dialog('close');
		                    	                        alert("ERROR: Could not Recalculate Project!");
		                	                        }
		            	                        });
		                                        	
		                                    	//location.reload();
		                                		},
		                                    error: function (data, status) {
				                                 alert(status);
		                                    	alert("ERROR: PAEV was not able to update selected task");
		                                    }
		                                });
		                            $(this).dialog("close");
		                            $(this).remove();
		                        },
		                        "Cancel": function () {
		                            $(this).dialog("close");
		                            $(this).remove();
		                        }
		                    }
		                	});
		            	$editTask.dialog('open');
		            },
		            error: function () {
		                //failed request; give feedback to user
		                $('#statusBox').html('<p class="error"><strong>Oops!</strong> Try that again in a few moments.</p>');
		            }
		        });
            }
        }
    });

    // Make sure row is selected when span is clicked
    $("table#treegrid tbody tr span").mousedown(function () {
        $($(this).parents("tr")[0]).trigger("mousedown");
    });

	$("#replan_btn").click(function () {
		var answer = confirm("Re-planning this project will cause the current version to be read-only.");
		if(answer == true) {
			$.ajax({
					type: 'POST',
					url: 'ajax_main.php',
					data: { action: "replan_project" },
					success: function (data) {
						document.location.href='tasks.php?txt_project='+data;
					},
					error: function () {
						alert("Replan not successful: An error has occured.\nContact a PAEV admin.");
					}
			});
		}
	});
    
    //Lock project
    $("#lock_btn").click(function () {
         
    	   var answer = confirm("Are you sure you want to lock this project from further planning?");
    	   if(answer == true){
        	   $.ajax({
            	   type: 'POST',
            	   url: 'ajax_main.php',
            	   data: { action: "lock_project" },
            	   success: function (data) {
            		   document.forms["projectSelect"].submit();
            		   document.location.href='tasks.php';
            	   },
            	   error: function () {
            		   alert("Project Not Locked: An error has occured.\nContact a PAEV admin.");
            		   $(this).dialog('close');
            		   
            	   }
        	   });
    	   }
    		else{
        		alert("No Action Performed");
     		}
    });
    
 	 //Unlock project
    $("#unlock_btn").click(function () {
         
    	   var answer = confirm("Are you sure you want to unlock this project for further planning? This will erase your project history.");
    	   if(answer == true){
        	   $.ajax({
            	   type: 'POST',
            	   url: 'ajax_main.php',
            	   data: { action: "unlock_project" },
            	   success: function (data) {
            		   document.forms["projectSelect"].submit();
            		   //alert("Project successfully locked. Changes to any planned activity require a re-plan.");
            		   //document.location.href='tasks.php';
            	   },
            	   error: function () {
            		   alert("Project Not Locked: An error has occured.\nContact a PAEV admin.");
            	   }
        	   });
    	   }
    		else{
        		alert("No Action Performed");
     		}
    });

	 // NEW TASK BUTTON is pressed
    $("#new_btn").click(function() {
    		if (selectedRow != "") {
        		var $newTask = $('<div></div>')
    				.html("Is this task a 'child' or a 'sibling' task?")
    				.dialog({
    	      		autoOpen: false,
    	        		width: 250,
    	        		modal: true,
    	        		closeOnEscape: true,
    	        		resizable: false,
    	        		title: "New Task",
    	        		buttons: {
                        "Child": function () {
                            $.ajax({
                                type: 'POST',
                                url: 'ajax_main.php',
                                data: { action: "add_child_task", RowId: selectedRow },
                                success: function (data) {
                                    $(this).dialog('close');
                                    if(data == "WBS" | data == "Rollup"){
                                        alert('Can not add child task to a WBS or Rollup that already have child tasks.\nTo add a child task select one of the child tasks within the WBS or Rollup.\n\n');
                                    }
                                    else{
                                    }
                                    document.location.href='tasks.php';
                                },
                                error: function () {
                                    // fail request
                                    $(this).dialog('close');
                                    alert("ERROR: Could not add Child task!");
                                }
                            });
                        },
                        "Sibling": function () {
    	                    	$.ajax({
        	                        type: 'POST',
        	                        url: 'ajax_main.php',
        	                        data: { action: "add_sibling_task", RowId: selectedRow },
        	                        success: function (data) {
												$(this).dialog('close');
                                    if(data == "WBS"){
													alert("Can not add sibling tasks to a WBS task.\nTo create a new WBS go to 'Project Settings' located under the 'Project' tab.\n\n\n");
                                    }
                                    else{
                                     }
                                     document.location.href='tasks.php';   
                                },
                                error: function () {
                                    $(this).dialog('close');
                                    alert("ERROR: Could not add Sibling task!");
                                }
                            });
                        },
                        "Cancel": function () {
                            $(this).dialog("close");
                        }
                    }		
    				});
           	$newTask.dialog('open');
    		}
	 });

	 // DELETE BUTTON is pressed
    $("#del_btn").click(function() {
		if (selectedRow != "") 
			{
    		var $deleteTask = $('<div></div>')
				.html("Are you sure you want to delete this task?")
				.dialog({
	      		autoOpen: false,
	        		width: 250,
	        		modal: true,
	        		resizable: false,
	        		title: "Delete Task",
	        		buttons: {
	                    "Delete": function () {
	                        $.ajax({
    	                        type: 'POST',
    	                        url: 'ajax_main.php',
    	                        data: { action: "delete_task", RowId: selectedRow },
    	                        success: function (data) {
        	                        $(this).dialog('close');
        	                        if(data == "WBS_NO"){
        	                        	alert("Can not delete a WBS from the TreeGrid.\nTo delete a WBS go to 'Project Settings' located under the 'Project' tab.\n\n\n");
        	                        }
        	                        else{
        	                        	alert("Task has been successfully deleted!");
        	                        }
            	                  document.location.href='tasks.php';
    	                        },
    	                        error: function () {
        	                        // fail request
        	                        $(this).dialog('close');
        	                        alert("ERROR: Could not delete task!");
    	                        }
	                        });
	                    },
	                    "Cancel": function () {
	                        $(this).dialog("close");
	                    }
	                }		
				});
       	$deleteTask.dialog('open');
			}
	 });
});
//END OF JQUERY
</script>

<script>
TaskNames = new Array();
WBSNames = new Array();
function previousVersion(this_id, curr_id){
   window.open('tasks.php?previous=1&txt_project='+curr_id+'&this_proj='+this_id,'project_window_'+this_id,'width=800,height=400,scrollbars=yes,resizable=yes');
}
</script>
<?php

if(isset($_REQUEST['previous']) && $_REQUEST['previous'] == 1){

   $this_project = postedData($_REQUEST['this_proj']);
   //Only display the table if a user is viewing a previous version.
   $getPrevious = "SELECT project_id FROM pev__project WHERE associated_id='$this_project' LIMIT 1";
   $result = dbquery($getPrevious);
   $row = mysql_fetch_array($result);
   $str_project = $row['project_id'];

   /* do the dbquery outside the select list so the debug will appear */
   $strSQL1 = "SELECT project_name FROM $TABLE_PROJECT";
   $strSQL1.= " WHERE project_id='$str_project'";
   $result1 = dbquery($strSQL1);
   $row1 = mysql_fetch_array($result1);
   $str_projectName = $row1['project_name'];
   $cur_project = $strProject;
   
   ?>
   <script>
   	previous = "1";
		$('table#treegrid tbody tr').unbind('dblclick');
	</script>
   <?php

   show_header();
   displayTable();
   show_footer();
   exit;
}

$TITLE="Project Tasks";
show_header();
show_menu("PROJECTS");
show_status();
show_error();

/* SHOW THE PROJECT TO VIEW/EDIT */
if(isset($_FORM['txt_action']) && $_FORM['txt_action'] == "Do Now" || $strProject)
{
	$str_project=$strProject;
	$cur_project=$strProject;
	
	?>
	<form name="msf1" action="<?=$PAGE_TASK;?>" method="POST">
		<input type="hidden" name=txt_action2 value='mfs1'>
		<input type="hidden" value="<?=$str_project;?>" name="txt_project">
		<input type="hidden" value="Do Now" name="txt_action">
	        <input type="hidden" name="tmp_row">
		<input type="hidden" name="tmp_taskid">
		<input type="hidden" name="tmp_wbsid">
		<input type="hidden" name="tmp_poc">
		<input type="hidden" name="tmp_due">
		<input type="hidden" name="tmp_ec">
		<input type="hidden" name="tmp_ph">
		<input type="hidden" name="tmp_ah">
		<input type="hidden" name="tmp_pc">
		<input type="hidden" name="tmp_del1">
		<input type="hidden" name="tmp_del2">
	</form>
	<form name="msf1_locked" action="<?=$PAGE_TASK;?>" method="POST">
		<input type="hidden" name=txt_action2 value='mfs1_locked'>
		<input type="hidden" value="<?=$str_project;?>" name="txt_project">
		<input type="hidden" value="Do Now" name="txt_action">
	        <input type="hidden" name="tmp_row">
		<input type="hidden" name="tmp_taskid">
		<input type="hidden" name="tmp_wbsid">
		<input type="hidden" name="tmp_poc">
		<input type="hidden" name="tmp_ec">
		<input type="hidden" name="tmp_ah">
		<input type="hidden" name="tmp_pc">
	</form>
	<form name="msf3" action="<?=$PAGE_TASK;?>" method="POST">
		<input type="hidden" name="txt_action2" value="mfs3">
		<input type="hidden" value="<?=$str_project;?>" name="txt_project">
		<input type="hidden" value="Do Now" name="txt_action">
	        <input type="hidden" name="tmp_row">
		<input type="hidden" name="tmp_taskid">
		<input type="hidden" name="tmp_name">
		<input type="hidden" name="tmp_del1">
		<input type="hidden" name="tmp_del2">
	</form>
	<form name="msf3_locked" action="<?=$PAGE_TASK;?>" method="POST">
		<input type="hidden" name="txt_action2" value="mfs3_locked">
		<input type="hidden" value="<?=$str_project;?>" name="txt_project">
		<input type="hidden" value="Do Now" name="txt_action">
	        <input type="hidden" name="tmp_row">
		<input type="hidden" name="tmp_taskid">
		<input type="hidden" name="tmp_name">
	</form>
	<form name=na action="<?=$PAGE_TASK;?>" method="POST">
		<input type="hidden" value='Do Now' name="txt_action">
		<input type="hidden" value='' name="txt_project">
		<input type="hidden" value='' name="txt_action2">
		<input type="hidden" value='' name="txt_wbs_id">
		<input type="hidden" value='' name="txt_task_id">
	</form>
	<?
   /* do the dbquery outside the select list so the debug will appear */
   $strSQL1 = "SELECT project_name FROM $TABLE_PROJECT";
   $strSQL1.= " WHERE project_id='$str_project'";
   $result1 = dbquery($strSQL1);
   $row1 = mysql_fetch_array($result1);
   $str_projectName = $row1['project_name'];

	?>
	<?
	displayTable();
}

?>

<div id="projectSelectorDiv" >
	<br />
	<?
	projectSelector('tasks.php', $strProject);
	?>
</div>
<fieldset>
	<legend><b>NOTES</b></legend>
	<ul>
		<li>Click on WBS/rollup triangles to expand or collapse child tasks</li>
		<li>Double-click task row to open task's properties; Double-click WBS names to hide WBS</li>
		<li>Adding a new task, Select desired task within TreeGrid and then press the "New Task" button</li>
		<li>Deleting a rollup/task, Select desired task within TreeGrid and then press the "Delete Task" button</li>
		<li>Adding, deleting and ordering a WBS is handled in "Project Settings" page</li>
		<li>WBS and rollups are auto calculated from the child tasks within them</li>
		<li>Scope Growth is a new task defined after the baseline.</li>
		<li>Adding a new task after the baseline that is not scope growth would have 0(zero) for the planned hours...</li>
		<li>Dates are in format: MM/DD/YYYY</li>
	</ul>
</fieldset>

<?php
show_footer();

function displayTable()
{
   global $project_status;
   global $str_projectName;
   global $project_status;
   global $str_project;
   global $previousProject;
   global $cur_project;
   global $ACCESS_ADMIN;
   global $USER_ACCESS;
   global $projectLocked;
   global $PAGE_TASK;
   $Total_PH=0;
	$Total_AH=0;
	$Total_P=0;
	$Count=0;
   
   //Compute Project Status
   $strProjStatus = "SELECT locked, revision, associated_id from pev__project WHERE project_id='$str_project'";
   $statusResult = dbquery($strProjStatus);
   $statusRow = mysql_fetch_array($statusResult);

   if ($statusRow['associated_id'] != NULL) { 

      $project_status = "Revised";
      $replanDisable = "DISABLED";
      $lockPlanDisable = "DISABLED";
		$importDisable = "DISABLED";
		$exportDisable = "DISABLED";
      $previousProject = true;
      $projectLocked = 1;
      $recordHistoryDisable = "DISABLED";

   } else {

      if ($statusRow['locked'] == 1) {

         $project_status = "Locked";
         $replanDisable = "";
         $lockPlanDisable = "DISABLED";
         $projectLocked = 1;
         $locked = "_locked";
         $recordHistoryDisable = "";

      } else {

         $project_status = "Unlocked";
         $replanDisable = "DISABLED";
         $lockPlanDisable = "";
         $projectLocked = 0;
         $locked = "";
         $recordHistoryDisable = "DISABLED";
      }

      $previousProject = false;
   }

   if($statusRow['revision'] == 0){
      $viewPreviousRevision = 0;
   }else{
      $viewPreviousRevision = 1;
   }
   $projectRevision = $statusRow['revision'];
?>
				<div id="statusBox" >
				</div>
					
					<div >
					
						<table class="toolbar" style="border-collapse: collapse;" >
							<tr>
								<td style="padding:0;">
									<form>
									<?php
									if($ACCESS_ADMIN==$USER_ACCESS || $_SESSION['dbAdmin'])
									{
										if($viewPreviousRevision)
										{?> 
											<input class="myButton" type="button" value="View Previous Version" onclick="previousVersion(<?=$str_project;?>, <?=$cur_project;?>);">
										<?}
										if($_SESSION['dbAdmin'] || isProjectAdmin($_SESSION['projectID'], $_SESSION['paev_userID']))
										{?>
											<input id="unlock_btn" class="myButton" type="button" value="Unlock Plan" <?=$replanDisable;?> onclick="">
										<?}
									}
									?>
									<input id="lock_btn" class="myButton" type="button" value="Lock Plan" <?=$lockPlanDisable;?> onclick="">
					         	<input id="replan_btn" class="myButton" type="button" value="Re-plan" <?=$replanDisable;?> onclick="">
									<input class="myButton" type="submit" name="txt_action2" value="Record History" <?=$recordHistoryDisable;?>>
								
									<input class="myButton" type="button" onclick="return openhide('importView','importButton');" value="Import" id="importButton" <?=$importDisable?>>
									<input class="myButton" type="button" onclick="return openhide('exportView','exportButton');" value="Export" id="exportButton" <?=$exportDisable?>>
									<input id="new_btn" class="myButton" type="button" value="New Task" <?=$lockPlanDisable;?> >
									<input id="del_btn" class="myButton" type="button" value="Delete Task" <?=$lockPlanDisable;?>>

									</form>
									<div id="importView">
										<?import_ui();?>
									</div>
									<div id="exportView">
										<table align="center" width="100%" class="toolbarpanel">
		         						<tr><th>Export Project Data</th></tr>
		         						<tr>
		            						<td align="center">
		               						<input class="but" type="button" value="Download Project Data" onclick="window.location='export.php?action=download';"/>
		               						<!--<input class="but" type="button" value="Extract Whole Project" onclick="window.location='export.php?action=extract';"/>-->
		            						</td>
		         						</tr>
		      						</table>
		      					</div>
									<script>
										openhide('exportView', 'exportButton');
										openhide('importView', 'importButton');
									</script>
								</td>
								<td style="padding:0;">
									<input id="expand" class="myButton" type="button" value="Expand">
									<input id="collapse" class="myButton" type="button" value="Collapse">
								</td>
								<td style="padding:0;">
									<label>Filter:</label>
									<select id="filter">
										<option value = 'showALL'>Show All</option SELECTED>
										<option value = '0'>Tasks with no POC</option SELECTED><?
									  		//Grabs list of POCs from current project
									  		$strSQL2 = "SELECT PA.person_id,P.first,P.last";
											$strSQL2 .= " FROM pev__project_access AS PA ";
											$strSQL2 .= " LEFT JOIN pev__person AS P ON P.person_id=PA.person_id";
											$strSQL2 .= " WHERE PA.project_id='$_SESSION[projectID]' AND P.first != '' AND P.last != ''";
											$strSQL2 .= " GROUP BY P.last ORDER BY P.last ASC";
						
											$result2 = dbquery($strSQL2);
						
											while ($row2 = mysql_fetch_array($result2)) {
												if ((strlen($row2['first'])) > 0 && (strlen($row2['last'])) > 0) { // filter out blank names;
														?><option value='<?=$row2['person_id']?>'><?=$row2['last']?>, <?=$row2['first']?></option><?
												}
								    		}?>
								   </select>
								</td>
							</tr>
						</table>
					</div>
					
					
					<!--<form action="tasks.php" method="post">
						<select name="username" width="30" align="left">
							<option value = "none">-None-</option>
							<?php
							//Build the list to pass to the modal-dialog box
							//This will display POC options for a project task
							$strSQL7 = "SELECT PA.person_id,P.first,P.last";
							$strSQL7.= " FROM pev__project_access AS PA ";
							$strSQL7.= " LEFT JOIN pev__person AS P ON P.person_id=PA.person_id";
							$strSQL7.= " WHERE PA.project_id='$str_project'";
							$strSQL7.= " ORDER BY P.last ASC,P.first ASC,P.person_id ASC";
							$result7 = dbquery($strSQL7);
							while($row7 = mysql_fetch_array($result7))
    						{
							?>
  								<option value="<?php echo $row7['person_id'] ?>"><?php echo $row7['last'].", ".$row7['first']?></option>
  							<?php 
    						}
    						?>
						</select>
						<input type="submit" value="Filter">
					</form>	
				-->
			    <div style="position:relative; top:-150px">
				<fieldset id="fieldbox" class="<?=$project_status;?>">
					<legend>Project: <?php echo $str_projectName?><br/>Status: <?php echo $project_status?></legend>
					<table class="treegridbackground" id="treegrid" align="right" style="position:inherit;  padding:0 5px;">
						<thead>
							<tr height="20">
								<th>WBS/Task</th>
								<th width="5%">Scope Growth</th>
								<th>WBS Number</th>
								<th>POC</th>
								<th>Due Date</th>
								<th>EC Date</th>
								<th>Planned Hours</th>
								<th>Actual Hours</th>
								<th>%</th>
							</tr>
						</thead>
						<tbody>
						
						<?php 		
						$WBSquery = "SELECT wbs_id, wbs_name, wbs_order, due_date, ec_date, planned_hours, actual_hours, percent_complete FROM pev__wbs WHERE project_id = '$str_project' ORDER BY wbs_order";
						$WBSresult = dbquery($WBSquery);
						if (mysql_num_rows($WBSresult) == "0"){
							?>
							<tr class="blank">
								<td colspan="9" style="text-align: center; padding:30px 0px; "><span style="font-style:italic; font-size:small; color:#999999;">Project does not have any WBSes nor Tasks associated to it.  To begin, go to 'Project Settings' under the 'Projects' tab to add WBSes to this project.</span></td>
							</tr>
							<?php
						}
						else 
						{
							$TOP_due_date = "";
							$TOP_ec_date = "";
							while($WBSrow = mysql_fetch_array($WBSresult))
	    					{
	    					?>
	    						<tr id="node-<?php echo addWBSMarker($WBSrow['wbs_id']) ?>" class="tr-wbs">
									<td id="left" width="35%">
										<span class="wbs">&nbsp;&nbsp;&nbsp;<?php echo $WBSrow['wbs_name'] ?></span>
									</td>
									<td class="td_hidden"></td>
									<td class="td_hidden"><?php echo $WBSrow['wbs_order'] ?></td>
									<td class="td_hidden"></td>
									<td class="td_hidden"><?php echo date_read($WBSrow['due_date']) ?></td>
									<td class="td_hidden"><?php echo date_read($WBSrow['ec_date']) ?></td>
									<td class="td_hidden"><?php $Total_PH = ($Total_PH+$WBSrow['planned_hours']); echo $WBSrow['planned_hours'] ?></td>
									<td class="td_hidden"><?php $Total_AH = ($Total_AH+$WBSrow['actual_hours']); echo $WBSrow['actual_hours'] ?></td>
									<td class="td_hidden"><?php $Total_P+= round(($WBSrow['planned_hours']*$WBSrow['percent_complete'])/100, 1); $Count++; echo $WBSrow['percent_complete'] ?></td>
								</tr>
								<?php
								
	    						if($TOP_due_date < $WBSrow['due_date'])
									{
									$TOP_due_date = $WBSrow['due_date'];
									}
								if($TOP_ec_date < $WBSrow['ec_date'])
									{
									$TOP_ec_date = $WBSrow['ec_date'];
									}
								
								$TASKquery = "SELECT WT.wbs_to_task_id, T.task_name, T.scope_growth, WT.wbs_id, WT.parent_id, WT.due_date, WT.ec_date, WT.planned_hours, WT.actual_hours, WT.percent_complete, WT.wbs_number, WT.rollup ";
								$TASKquery.= "FROM pev__task AS T ";
								$TASKquery.= "LEFT JOIN pev__wbs_to_task AS WT ON T.task_id = WT.task_id ";
								$TASKquery.= "WHERE T.project_id = '$str_project' AND WT.wbs_id = '".$WBSrow['wbs_id']."' ORDER BY WT.wbs_number";
								$result = dbquery($TASKquery);
								error_log("Child Task: $TASKquery");
								if(!$result)
								{
								}
								else
								{
									while($row = mysql_fetch_array($result))
									{
									  	$wtt_id=$row['wbs_to_task_id'];
									  	$wtt_WBSid=$row['wbs_id'];
									  	$wtt_parent_id=$row['parent_id'];
									  	$t_name=$row['task_name'];
									  	$t_scope=($row['scope_growth'] == "0")?"":$row['scope_growth'];
									  	$wtt_dueDate=date_read($row['due_date']);
									  	$wtt_ecDate=date_read($row['ec_date']);
									  	$wtt_plannedHours=$row['planned_hours'];
									  	$wtt_actualHours=$row['actual_hours'];
									  	$wtt_percentComplete=$row['percent_complete'];
									  	$wtt_wbsNumber=$row['wbs_number'];
									  	$wtt_rollup=$row['rollup'];
									  	
//										if($TOP_due_date < $wtt_dueDate)
//											{
//											$TOP_due_date = $wtt_dueDate;
//											}
//										if($TOP_ec_date < $wtt_ecDate)
//											{
//											$TOP_ec_date = $wtt_ecDate;
//											}
									  	if($wtt_rollup == '0')
										{?>
									  		<tr id="node-<?php echo $wtt_id ?>"<?php echo " class=\"child-of-node-{$wtt_parent_id} tr-task\"" ?>>
									  		<td><span class="file">&nbsp;&nbsp;&nbsp;<?php echo $t_name ?></span></td>
									  	<?}
									  	else {?>
									  		<tr id="node-<?php echo $wtt_id ?>"<?php echo " class=\"child-of-node-{$wtt_parent_id} tr-rollup\"" ?>>
								  			<td><span class="folder">&nbsp;&nbsp;&nbsp;<?php echo $t_name ?></span></td>
									  	<?}?>
											<td class="td_hidden"><?php echo $t_scope ?></td>
											<td class="td_hidden"><?php echo $wtt_wbsNumber ?></td>
											<?php 
											if($wtt_rollup == '0') 
												{
												$strSQL2 = "";
												$strSQL2 = "SELECT PA.person_id,P.first,P.last ";
												$strSQL2.= "FROM pev__person_to_wbstask AS PA ";
												$strSQL2.= "LEFT JOIN pev__person AS P ON P.person_id=PA.person_id ";
												$strSQL2.= "WHERE PA.wbs_to_task_id ='$wtt_id' ";
												$strSQL2.= "ORDER BY P.last ASC,P.first ASC,P.person_id ASC";
												$result2 = dbquery($strSQL2);
												if(mysql_num_rows($result2) == 1) 
													{
													$row2 = mysql_fetch_array($result2);
													if($row2['person_id'] != "0")
														{
														?><td class="<?	
														echo $row2['person_id'];
														?>"><?echo $row2['last'].", ".$row2['first'];
														}
													else
														{
														?><td class="0"><?
														echo "";
														}
													}
												else
													{
													?><td class="0"><?
												   echo "";
													}
												}
											else
												{
												?><td class="td_hidden" id="0"><?
												echo "";
												}
	    									?></td>
									  		<td class="td_hidden"><?php echo $wtt_dueDate ?> </td>
									  		<td class="td_hidden"><?php echo $wtt_ecDate ?> </td>
									  		<td class="td_hidden"><?php echo $wtt_plannedHours ?> </td>
									  		<td class="td_hidden"><?php echo $wtt_actualHours ?> </td>
									  		<td class="td_hidden"><?php echo $wtt_percentComplete ?> </td>
									  	</tr>
									  	<?php
									}	
								}
							}
						}
						?>
						<tr class ="blank">
							<td colspan="9" style="height:10px;"></td>
						</tr>
						<tr class="project-summary" style="border-top:1px solid #999999">
							<td></td><td></td><td></td>
							<td style="font-size:20px; text-align:left; padding:5px 14px;">TOTAL </td>
							<td style="font-size:20px; text-align:left; padding:5px 14px;"><?php echo date_read($TOP_due_date)?></td>
							<td style="font-size:20px; text-align:left; padding:5px 14px;"><?php echo date_read($TOP_ec_date)?></td>
							<td style="font-size:20px; text-align:left; padding:5px 14px;"><?php echo $Total_PH ?></td>
							<td style="font-size:20px; text-align:left; padding:5px 14px;"><?php echo $Total_AH ?></td>
							<td style="font-size:20px; text-align:left; padding:5px 14px;"><?php echo round(($Total_P/$Total_PH)*100,1); ($Total_P/$Count) ?> </td>
						</tr>
						</tbody>
					</table>
					<div id="dialog"></div>
				</fieldset>
				
				</html>
			<?php
}		// End of displayTable function

//$profiler->display();
?>