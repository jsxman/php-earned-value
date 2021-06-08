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
debug(10,"Loading File: people.php");
debug(10,"User Level is DB-Admin (".$_SESSION['dbAdmin'].")");
checkPermissions($SESSION_TIMEOUT); // if not logged in, or session has timed out...
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

set_todo("assign a person to projects with access level setting");
set_todo("validate people names only have characters in them. tics and quotes will cause problems");




/******************************************/
/* COMPUTING - NO OUTPUT - START          */
/******************************************/
$strUsername = "";
$strFirst    = "";
$strLast     = "";
$strPassword = "";
$strDBAdmin  = 0;
/* only allow a non-admin to look at their own account */
if(!$_SESSION['dbAdmin'])
{
	$_FORM['txt_person']=$_SESSION['paev_userID'];
}
if(isset($_FORM['txt_action']) && $_FORM['txt_action'] == "Create" && $_SESSION['dbAdmin'])
{
	$strUsername = postedData($_FORM['txt_username']);
  	$strFirst    = postedData($_FORM['txt_first']);
  	$strLast     = postedData($_FORM['txt_last']);
  	$strPassword = postedData($_FORM['txt_password']);
  	$strDBAdmin  = isset($_FORM['txt_dbadmin'])?postedData($_FORM['txt_dbadmin'])||0:0;
  	debug(3,"IS_DB_ADMIN && CREATE ACTION POSTED (U:$strUsername, F:$strFirst, L:$strLast, P:$strPassword, A:$strDBAdmin)");
  	$err=0;
  	if(!strlen($strUsername)){$err=1;set_error("Username must be defined.");}
  	if(!strlen($strFirst)   ){$err=1;set_error("First Name must be defined.");}
  	if(!strlen($strLast)    ){$err=1;set_error("Last Name must be defined.");}
  	if(!strlen($strPassword)){$err=1;set_error("Password must be defined.");}
  	if(!$err)
  	{
    		/* find out if the username already exists */
    		$strSQL0 = "SELECT P.username FROM $TABLE_PERSON AS P WHERE P.username='$strUsername'";
    		$result0 = dbquery($strSQL0);
    		$row0 = mysql_fetch_array($result0);
    		if($row0 && $row0['username'])
    		{
      			set_error("You can not create another account with the same username ($strUsername)");
    		}
    		else
    		{
     			$encPassword=encryptData($strPassword);
      			$strSQL1 = "INSERT INTO $TABLE_PERSON SET";
      			$strSQL1.= " username='$strUsername'";
      			$strSQL1.= ",first='$strFirst'";
      			$strSQL1.= ",last='$strLast'";
      			$strSQL1.= ",db_admin='$strDBAdmin'";
      			$strSQL1.= ",password='$encPassword'";
      			$result1 = dbquery($strSQL1);
      			set_status("Success: Account created.");
      			/* remove these from the new create box - as we are creating this account now! */
      			$strUsername = "";
      			$strFirst    = "";
      			$strLast     = "";
      			$strPassword = "";
      			$strDBAdmin  = 0;
    		}
  	}
}



$str_eda=isset($_FORM['txt_eda'])?postedData($_FORM['txt_eda']):"";
$str_person=isset($_FORM['txt_person'])?postedData($_FORM['txt_person']):"";
debug(10,"Action to edit, delete, assign person ($str_eda) for ($str_person)");
if(isset($_FORM['txt_action']) && $_FORM['txt_action'] == "Do Now" && $str_eda == "delete")
{
  $str_delconfirm=isset($_FORM['txt_delconfirm'])?postedData($_FORM['txt_delconfirm']):"";
  if(!$str_delconfirm)
  {
    set_error("The confirm checkbox was not selected.  No action taken.");
  }
  else if($str_person == $_SESSION['paev_userID'])
  {
    set_error("You can not delete your own account.");
  }
  else
  {
    $strSQL0 = "DELETE FROM $TABLE_PERSON WHERE person_id='$str_person'";
    $result0 = dbquery($strSQL0);
    set_status("User deleted.");
  }
}
if(isset($_FORM['txt_action']) && $_FORM['txt_action'] == "Do Now" && $str_eda == "assign")
{
  set_todo("Need to redirect to the project page as assignments will first be there... then need to put here.");
}
  if(isset($_FORM['txt_action']) && $_FORM['txt_action'] == "Change")
  {
    if($_SESSION['dbAdmin'])
      $tmpPerson   = postedData($_FORM['txt_personid']);
    else
      $tmpPerson   = $_SESSION['paev_userID'];
    $tmpUsername = postedData($_FORM['txt_username']);
    $tmpFirst    = postedData($_FORM['txt_first']);
    $tmpLast     = postedData($_FORM['txt_last']);
    $tmpPassword = postedData($_FORM['txt_password']);
    $tmpDBAdmin  = isset($_FORM['txt_dbadmin'])?postedData($_FORM['txt_dbadmin'])||0:0;
    debug(10,"IS_DB_ADMIN && CHANGE ACTION POSTED (U:$tmpUsername, F:$tmpFirst, L:$tmpLast, P:$tmpPassword, A:$tmpDBAdmin)");
    $strSQL4 = "UPDATE $TABLE_PERSON SET";
    $strSQL4.= " username='$tmpUsername'";
    $strSQL4.= ",first='$tmpFirst'";
    $strSQL4.= ",last='$tmpLast'";
    if($_SESSION['paev_userID']!=$tmpPerson)
      $strSQL4.= ",db_admin='$tmpDBAdmin'";
    if(strlen($tmpPassword))$strSQL4.=",password='".encryptData($tmpPassword)."'";
    $strSQL4.= " WHERE person_id='$tmpPerson'";
    $result4 = dbquery($strSQL4);
    set_status("Person Updated.");
  }

/******************************************/
/* COMPUTING - NO OUTPUT - END            */
/******************************************/

/******************************************/
/* COMPUTING - SHOW OUTPUT - START        */
/******************************************/
$TITLE="People Editing";
show_header();
show_menu("ADMIN");
echo '<div style="position:relative; top:-150px">';
show_status();
show_error();

if($dbAdmin)
{


  if(isset($_FORM['txt_action']) && $_FORM['txt_action'] == "Do Now" && $str_eda == "edit")
  {
        /* do the dbquery outside the select list so the debug will appear */
        $strSQL3 = "SELECT * FROM $TABLE_PERSON AS P WHERE P.person_id='$str_person'";
        $result3 = dbquery($strSQL3);
        $row3 = mysql_fetch_array($result3);
        $tmpUsername=$row3['username'];
        $tmpFirst=$row3['first'];
        $tmpLast=$row3['last'];
        $tmpPersonID=$row3['person_id'];
        $tmpDBAdmin=$row3['db_admin'];
?>
<BR>
<form action="<?=$PAGE_PEOPLE;?>" method=POST>
<input type=hidden value='<?=$tmpPersonID;?>' name=txt_personid>
<fieldset>
	<legend><b>Edit Person</b></legend>
	<table align=center>
		<tr><td>username</td><td><input type=text name=txt_username value='<?=$tmpUsername;?>'></td></tr>
		<tr><td>First Name</td><td><input type=text name=txt_first value='<?=$tmpFirst;?>'></td></tr>
		<tr><td>Last Name</td><td><input type=text name=txt_last value='<?=$tmpLast;?>'></td></tr>
		<tr><td>Password</td><td><input type=text name=txt_password value=''></td></tr>
<?  if($_SESSION['dbAdmin']) { ?>
		<tr><td>Is DB Admin?</td><td>Yes if Checked:<input type=checkbox value='1' <? if($tmpDBAdmin)echo "CHECKED"; ?> name='txt_dbadmin'></td></tr>
<? } ?>
		<tr><td colspan=1><input class=but type=submit name=txt_action value="Change"></td></tr>
	</table>
</fieldset>
</form>
<?
  }
if($_SESSION['dbAdmin'])
{
  /* do the dbquery outside the select list so the debug will appear */
  $strSQL2 = "SELECT P.first,P.last,P.person_id FROM $TABLE_PERSON AS P ORDER BY P.last ASC,P.first ASC, P.person_id ASC";
  $result2 = dbquery($strSQL2);
?>
<BR><br>
<form action="<?=$PAGE_PEOPLE;?>" method=POST>
<fieldset>
	<legend><b>Modify Person</b></legend>
	<table align=center>
		<tr><td>Pick A Person: <select name=txt_person>
<?
  while($row2 = mysql_fetch_array($result2))
  {
    $lf=$row2['last'].", ".$row2['first'];
    echo "<option value='".$row2['person_id']."'>$lf</option>\n";
  }
?>
</select></td></tr>
		<tr><td>Pick an Action: <select name=txt_eda>
			<option value='edit'>Edit</option>
			<option value='delete'>Delete</option>
			</select></td></tr>
		<tr><td>Must check to confirm a delete selection: <input type=checkbox name=txt_delconfirm value=1><td></tr></tr>
		<tr><td align=center><input class=but type=submit value='Do Now' name='txt_action'></td></tr>
	</table>
</fieldset>
<BR>
<form action="<?=$PAGE_PEOPLE;?>" method=POST>
<fieldset>
<legend><b>Create New Account</b></legend>
	<table align=center>
		<tr><td>username</td><td><input type=text name=txt_username value='<?=$strUsername;?>'></td></tr>
		<tr><td>First Name</td><td><input type=text name=txt_first value='<?=$strFirst;?>'></td></tr>
		<tr><td>Last Name</td><td><input type=text name=txt_last value='<?=$strLast;?>'></td></tr>
		<tr><td>Password</td><td><input type=text name=txt_password value='<?=$strPassword;?>'></td></tr>
		<tr><td>Is DB Admin?</td><td>Yes if Checked:<input type=checkbox value='1' <? if($strDBAdmin)echo "CHECKED"; ?> name='txt_dbadmin'></td></tr>
		<tr><td colspan=1 align=center><input class=but type=submit name=txt_action value="Create"></td></tr>
	</table>
</fieldset>
</form>
<?
} /* end check for DB ADMIN */
else
{
?>
<form action="<?=$PAGE_PEOPLE;?>" method=POST>
<input type=hidden name=txt_eda value='edit'>
<tr><td>Edit Account: <input class=but type=submit value='Do Now' name='txt_action'></td></tr>
</form>
<?
}
}//End Page Content
show_footer();


/******************************************/
/* COMPUTING - SHOW OUTPUT - END          */
/******************************************/


?>
</div>
