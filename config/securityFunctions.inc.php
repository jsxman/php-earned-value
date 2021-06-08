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

  # You MUST call this BEFORE any text is written to client!!!
  Function checkPermissions($intTimeOut) {
    global $_SESSION;
    global $_SERVER;
    global $DEBUG;
    global $PAGE_LOGIN;

    if( !isset( $_SESSION['paev_userID'] ) )
    {
      session_destroy();
      session_start();
      $_SESSION['paev_userID']='';
      $_SESSION['time']=0;
      $_SESSION['security']=0;
    }
    if($DEBUG>15)
    {
      echo "function:checkPermissions()<BR>\n";
      echo "<dir>";
      echo "SESSION:userId=".$_SESSION['paev_userID']."<BR>\n";
      echo "SESSION:time=".$_SESSION['time']."BR>\n";
      echo "SESSION:security=".$_SESSION['security']."<BR>\n";
      echo "</dir>";
    }
    if ($_SESSION['paev_userID']) {
        if ($_SESSION['time'] < (time() - $intTimeOut)) {
            // if 30 minutes have passed since the last page request
            //endSession();
	    // give the user one chance, 
            $_ref="http://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
            $_parm="";if(strlen($_ref))$_parm="?ref=$_ref";
            header("Location: $PAGE_LOGIN$_parm");
        } else {
            // let user in!
	    // and do away with the temp $_SESSION['FORM']
            $_SESSION['time'] = time(); # current time in seconds;
        }
    } Else {
        $_ref="http://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
        $_parm="";if(strlen($_ref))$_parm="?ref=$_ref";
        header("Location: $PAGE_LOGIN$_parm");
    }
  }

  function endSession()
  {
    global $CURRENT_USER;
    $CURRENT_USER=null;
    session_destroy();
  }

  Function writeSecurityLevel($intLevel) {
      If ($intLevel == "0") {
          $strLevel = "Full Access";
      } ElseIf ($intLevel == "1") {
          $strLevel = "Limited Access";
      } ElseIf ($intLevel == "2") {
          $strLevel = "Read Only";
      } ElseIf ($intLevel == "3") {
          $strLevel = "No Access";
      }
      Return "<span class=security_level>$strLevel</span>";
  }


/* determine this user's access level for a given project. 0=none, 1=user, 2=admin, */
function accessLevel($_project)
{
  global $TABLE_PROJECT_ACCESS;
  global $ACCESS_NONE;
  global $ACCESS_USER;
  global $ACCESS_ADMIN;
  $personID = $_SESSION['paev_userID'];
  $level=$ACCESS_NONE; /* if nothing returned from query - no access level for this user */
  if($_project)
  {
    $strSQL0 = "SELECT is_admin,person_id FROM $TABLE_PROJECT_ACCESS";
    $strSQL0.= " WHERE project_id='$_project' AND person_id='$personID'";
    $result0 = dbquery($strSQL0);
    if($row0 = mysql_fetch_array($result0))
    {
      $level=$ACCESS_USER; /* record found - at least USER access level */
      if($row0['is_admin'])$level=$ACCESS_ADMIN;
    }
  }
  return $level;
}

  
  $accountID = 1;
?>
