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
include("addons/export_helper.php");
checkPermissions($SESSION_TIMEOUT); // if not logged in, or session has timed out...
/******************************************/
/* HEADER: INCLUDE/SECURITY CHECK - END   */
/******************************************/
$projectID = $_SESSION['projectID'];
$userID = $_SESSION['paev_userID'];
//Get project name
$getProjectName = "SELECT project_name FROM pev__project WHERE project_id='$projectID'";
$nameResult = mysql_query($getProjectName) or die("MySQL Error: ".mysql_error()."\n$nameResult");
if($nameRow = mysql_fetch_array($nameResult)){
   $projectName = $nameRow['project_name'];
}else{
   //Error occurred. User might remedy problem by trying to log back in.
   die("There was an error processing your request. Please log out and try again.");
}

if(isset($_REQUEST['action']))
	{
   switch($_REQUEST['action'])
   	{
   	case "download":
   		header('Content-type: application/force-download');
			header('Content-disposition: attachment; filename="Project_Export.txt"');
			print_header();
			print_data();
   		break;
   	case "extract":
   		header('Content-type: application/force-download');
			header('Content-disposition: attachment; filename="Project_Export.txt"');
			print_extract_header();
   		break;
   	}
	}

?>