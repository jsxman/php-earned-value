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
debug(10,"Loading File: admin.php");
debug(10,"User Level is DB-Admin (".$_SESSION['dbAdmin'].")");
checkPermissions($SESSION_TIMEOUT);// if not logged in, or session has timed out...
/******************************************/
/* HEADER: INCLUDE/SECURITY CHECK - END   */
/******************************************/
/********************************************
*	        PERMISSIONS CHECK	    *
********************************************/
$dbAdmin = $_SESSION['dbAdmin'];
/********************************************
*	    END PERMISSIONS CHECK	    *
********************************************/

$fileName="";
$fileName="admin/testfile.txt";
$fileHandle=fopen($fileName,'r');
$loginType= fread($fileHandle, filesize($fileName));
fclose($fileHandle);

//PAEV Login Change
if(isset($_FORM['login_action']) && $_FORM['login_action'] == "Change" && $dbAdmin){
   if($_SESSION['dbAdmin']){  //Security Check: Only a logged in DB admin can change
	$loginProcess = "";
	$loginProcess = isset($_FORM['loginProcess'])?postedData($_FORM['loginProcess']):0;
	$fileName="admin/testfile.txt";

	switch($loginProcess){
		case 0:
			break;
		case 1:
			$fileHandle=fopen($fileName,'w') or die("can't write to file: $fileName");
			$login = "PAEV Login";
			fwrite($fileHandle,$login);
			fclose($fileHandle);
			set_status("Login Process Changed! Username/Password will be veirifed by PAEV Database.");
			break;
		case 2:
			$fileHandle=fopen($fileName,'w') or die("can't write to file: $fileName");
			$login = "Windows AD Login";
			fwrite($fileHandle,$login);
			fclose($fileHandle);
			set_status("Login Process Changed! Username/Password will be verified by Windows AD Server.");
			break;
		default:
			set_status("Error Occured: No Change Made");
	}
   }	
}

$TITLE="PAEV Settings";
show_header();
show_menu("ADMIN");
echo '<div style="position:relative; top:-150px">';
show_status();

if($dbAdmin)
{
?>

Welcome to the PAEV adminstrator page!</BR>
<?
show_rules();
?>

<fieldset><legend><b>Login Rules</b></legend>
	<table width=100%>
		<tr><b>PAEV DB Login:</b> This will only query the PAEV DB for login authorization for user access.</tr></br>
		<tr><b>Microsoft AD Server Login:</b> If a Microsoft Active Directory server is available for login authorization, this option will determine if the username exists in the PAEV DB and then query the Active Directory server. </tr></br></br>
		<tr><td>
<form>
Login Process:
<SELECT NAME="loginProcess">
<OPTION value="0">Select Login Process
<OPTION VALUE="1">Use PAEV DB Login
<OPTION VALUE="2">Use Microsoft AD Server Login
</SELECT>
<input class=but type=submit name=login_action value="Change">
</form>
		</td></tr>
	</table>
</fieldset>

<?
}
show_footer();
?>
</div>