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

$HACK_CHECK=1; Include("config/global.inc.php");
debug(10,"Loading File: tasks.php");
function login_session(array $row1){
	if($DEBUG){echo "user exists and account active(login)";}
    	$_SESSION['paev_userID']   = $row1['person_id'];
    	$_SESSION['time']     = time();
    	$_SESSION['dbAdmin']  = $row1['db_admin'];
	$_SESSION['userName'] = $row1['username'];
    	$_page=$PAGE_PEOPLE;
    	//if (isset($_FORM['ref']))$_page=$_FORM['ref'];
    	//header ("Location: $_page");			   //this setup does not currently work with Firefox 2.0
	header ("Location: index.php");  		   //redirect user to the index page
    	exit;
}
?>
<?
$loginType="";
$fileName="admin/testfile.txt";
$fileHandle=fopen($fileName,'r');
$loginType= fread($fileHandle, filesize($fileName));
fclose($fileHandle);

if (isset($_FORM['btnSubmit']) && ($loginType=="Windows AD Login"))
{
  require_once('config/paev_user_auth.php');
  $strUserName = postedData($_FORM['txtUserName']);
  $strPassword = postedData($_FORM['txtPassword']);
  $column_names = array(0=>"person_id","username","db_admin");
  //Skip langroup authorization if username 'root' or 'test' 
  if(($strUserName == 'root')||($strUserName == 'test')){
	$strSQL1 = "SELECT P.person_id, P.username, P.password, P.db_admin";
	$strSQL1.= " FROM $TABLE_PERSON AS P";
	$strSQL1.= " WHERE P.username='$strUserName'";
	$result1 = dbquery($strSQL1);
	$row1 = mysql_fetch_array($result1);
	if(unEncryptData($row1['password']) == $strPassword){
		login_session($row1);
	}
  }
  //Check if valid user on langroup and if listed in user table 
  if($row1 = valid_user($strUserName,$_FORM['txtPassword'],$column_names,$TABLE_PERSON,'username',NULL)){
	login_session($row1);
  }elseif(!$row1){
	set_error("<big>Incorrect Username/Password Combination</big>");
	set_error("<big>Three incorrect login attempts will lock out your LANGROUP account. Contact the help desk if this occurs.</big>");
  }
}

if (isset($_FORM['btnSubmit']) && ($loginType=="PAEV Login"))
{
  $strUserName = postedData($_FORM['txtUserName']);
  $strPassword = postedData($_FORM['txtPassword']);
  $column_names = array(0=>"person_id","username","db_admin");
	$strSQL1 = "SELECT P.person_id, P.username, P.password, P.db_admin";
	$strSQL1.= " FROM $TABLE_PERSON AS P";
	$strSQL1.= " WHERE P.username='$strUserName'";
	$result1 = dbquery($strSQL1);
	$row1 = mysql_fetch_array($result1);
	if(unEncryptData($row1['password']) == $strPassword){
		login_session($row1);
	}else{
		set_error("<big>Incorrect Username/Password Combination</big>");
	}
}
$TITLE="Login";
show_header();
show_menu("LOGIN");
set_todo("the mycrypt is not installed on every server...");
show_error();

if(!isset($LOGGED_IN) || !$LOGGED_IN)
{
?>

<script>

function myLoad()
{
document.form1.txtUserName.focus();
}
onload=myLoad;
function pword()
{
  var _f=document.form1;
  if(_f.txtPassword1.value!='')
  {
    _f.txtPassword.value=_f.txtPassword1.value;
    _f.txtPassword1.value='';
  }
  return true;
}
</script>
<div style="position:relative; top:-150px">
<p></p>
<?if($loginType=="Windows AD Login"){
?>
<table align=center><tr><td><b><font size=3>Please use your LANGROUP or ESA username/password to login</font></b></td></tr></table>
<?
}
?>
<form name="form1" method="POST" action="<?=$PAGE_LOGIN;?>" onsubmit="pword(this.form)">
<input type=hidden name="txtPassword">
  <br><table align=center border='0' cellspacing=0 cellpadding=0>
    <tr><td colspan=3 align=center></td></tr>
    <tr>
      <td class=forms_login >Username:</td>
      <td class=forms_login width=10>&nbsp;</td>
      <td class=forms_login ><input class=forms_login type="text" name="txtUserName" value="<?echo isset($strUserName)?$strUserName:"";?>" size="20"></td>
    </tr>
    <tr>
      <td class=forms_login >Password:</td>
      <td class=forms_login width=10>&nbsp;</td>
      <td class=forms_login ><input class=forms_login type="password" name="txtPassword1" size="20"></td>
    </tr>
    <tr height=10><td colspan=3></td></tr>
    <tr>
      <td class=form_login colspan=3 align=center>
         <input class=but type="submit" value="Submit" name="btnSubmit">
         <input class=but type="reset" value="Reset" name="reset">
         <input type=hidden value="<?=$_FORM['ref'];?>" name="ref">
      </td>
    </tr>
  </table><br>
</form>
Accounts Setup:<BR>
root / password - is a DB admin<BR>
test / password - is not a DB admin<BR>

<?
}
?>
<?
show_footer();
?>
</div>
