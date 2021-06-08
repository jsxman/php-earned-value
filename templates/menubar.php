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

global $HACK_CHECK; 
global $PAGE;
if(!isset($HACK_CHECK) || !$HACK_CHECK)exit; // DO NOT DIRECTLY LOAD THIS FILE
if(isset($_which))
{
  $t1=null;$t2=null;$t3=null;$t4=null;$t5=null;$t6=null;$t7=null; 
  switch($_which)
  {
    case 'HOME':	$t1='class=selected';break;
    case 'PROJECTS':	$t2='class=selected';break;
    case 'REPORTS':	$t3='class=selected';break;
    case 'HELP':	$t4='class=selected';break;
    case 'ADMIN':	$t5='class=selected';break;
    case 'LOGIN':	$t6='class=selected';break;
    case 'LOGOUT':	$t7='class=selected';break;
    case 'TEMP':     // show nothing - its okay :-)
                     break;
    default: show_error("Invalid SWITCH Option in the templates/menubar.php");
  }
  debug(10, "WHICH is: $_which");
}

/********************************************
*	        PERMISSIONS CHECK	    *
********************************************/

if(isset($_SESSION['paev_userID'])){
   $strProject = $_SESSION['projectID'];
   $userId = $_SESSION['paev_userID'];
   $dbAdmin = $_SESSION['dbAdmin'];
   $projAdmin = isProjectAdmin($strProject, $userId);
}else{
   $projAdmin = 0;
   $dbAdmin = 0;
}
/********************************************
*	    END PERMISSIONS CHECK	    *
********************************************/

?>
<script type="text/javascript" src="scripts/csshorizontalmenu.js">

/***********************************************

* CSS Horizontal List Menu- by JavaScript Kit (www.javascriptkit.com)
* Menu interface credits: http://www.dynamicdrive.com/style/csslibrary/item/glossy-vertical-menu/ 
* This notice must stay intact for usage
* Visit JavaScript Kit at http://www.javascriptkit.com/ for this script and 100s more

***********************************************/

</script>


<div class="horizontalcssmenu" >
<ul id="cssmenu1">
<li style="border-left: 1px solid #202020;"><a <?=$t1?> href="index.php">Home</a></li>
<li><a <?=$t2?> href="projects.php">Projects</a>
	<ul id="projects">
	<li><a href="tasks.php">Project Data Table</a></li>
<?
if($projAdmin || $dbAdmin)
{
?>
	<li><a href="project-settings.php">Project Settings</a></li>
	<li><a href="history.php">Project History</a></li>
<?
}
?>
	<li><a href="events.php">Project Events and Docs</a></li>
	</ul>
</li>
<li><a <?=$t3?> href="reports.php">Reports</a>
	<ul id="reports">
<?
if($projAdmin || $dbAdmin)
{
?>
	<li><a href="monthly-hours.php">Hours by Month</a></li>
<?
}
if($dbAdmin){
?>
        <li><a href="department_status.php">Department Status</a></li>
<?
}
?>
	<li><a href="graphs.php">Graphs</a></li>
	</ul>
</li>
<li><a <?=$t4?> href="help.php">Help</a></li>
<?
if($dbAdmin)
{
?>
<li><a <?=$t5?> href="admin.php">Admin</a>
    <ul id="admin">
    <li><a href="paev-settings.php">PAEV Settings</a></li>
    <li><a href="people.php">Accounts</a></li>
    </ul>
</li>
<?
}
?>
<?
if(isset($_SESSION) && isset($_SESSION['paev_userID']) && $_SESSION['paev_userID'])
{
?>
<li><a <?=$t7?> href="logout.php">Log Out</a></li>
<?
}
else
{
?>
<li><a <?=$t6?> href="login.php">Log In</a></li>
<?
}
?>

</ul>
<?
if($_SESSION['userName'])
{
  $row0 = Array();
  if($strProject)
  {
    $strSQL0 = "";
    $strSQL0.= "SELECT project_name FROM pev__project WHERE project_id= '$strProject'";
    $result0 = dbquery($strSQL0);
    $row0 = mysql_fetch_array($result0);
  }
  if(!isset($row0['project_name'])){
       $row0['project_name'] = "";
  }
?>
<a class=headerinfo>Logged in: <?=$_SESSION['userName'];?></a>
<a class=headerinfo>&nbsp;&nbsp;&nbsp;&nbsp; Project: <b><?=$row0['project_name'];?></b></a>
<?
}
?>
<br style="clear: left;" />
</div>


