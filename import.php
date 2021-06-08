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
include("addons/import_helper.php");
debug(10,"Loading File: import.php");
debug(10,"User Level is DB-Admin (".$_SESSION['dbAdmin'].")");
checkPermissions($SESSION_TIMEOUT); // if not logged in, or session has timed out...
/******************************************/
/* HEADER: INCLUDE/SECURITY CHECK - END   */
/******************************************/ 

if(isset($_FILES['uploadedfile'])){

   $TITLE="Import Page";
   show_header();
   show_menu("PROJECTS");

   if(import_file()){
      set_status("The data has been temporarily loaded into the database...please verify the import data below");
      show_status();
      //Now we need to display the imported information and allow the user to make modifications
      display_import();
   }else{
      set_error("Click the back button on your browser to return to the Project Data Table.");
   }
   show_error();

   show_footer();

}else if(isset($_REQUEST['action'])){
   include("addons/import_ajax.php");
}else{
   header("Location: tasks.php");
}


?>