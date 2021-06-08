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
?>
<?

if (isset($_FORM['btnSubmit']))
{
  $strUserName = postedData($_FORM['txtUserName']);
  $strPassword = postedData($_FORM['txtPassword']);
  $column_names = array("persion_id","username","db_admin");
  if($row1 = valid_user($strUserName,$strPassword,$column_names,$TABLE_PERSON,'username'))
  $strSQL1 = "SELECT P.person_id, P.username, P.password, P.db_admin";
  $strSQL1.= " FROM $TABLE_PERSON AS P";
  $strSQL1.= " WHERE P.username='$strUserName'";
  $result1 = dbquery($strSQL1);
  $row1 = mysql_fetch_array($result1);
  if ($row1['person_id'] && $row1['person_id'] != "" && unEncryptData($row1['password']) == $strPassword)
  {
    if($DEBUG){echo "user exists and account active(login)";}
    $_SESSION['userID']   = $row1['person_id'];
    $_SESSION['time']     = time();
    $_SESSION['dbAdmin']  = $row1['db_admin'];
    $_page=$PAGE_PEOPLE;
    if (isset($_FORM['ref']))$_page=$_FORM['ref'];
    header ("Location: $_page");
    exit;
  }
}
$TITLE="Login";
show_header();
show_menu("LOGIN");
set_todo("the mycrypt is not installed on every server...");


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

