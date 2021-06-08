<?php
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

if (!isset($db))
{ /* only include this once - only make 1 database connection */
   $DB_Q_TIME=0;
   $DB_Q_COUNT=0;
   function show_dbstat()
   {
     global $DB_Q_TIME,$DB_Q_COUNT;
     $_temp=$DB_Q_TIME*1000;
     settype($_temp,"integer");
     $DB_Q_TIME=$_temp/1000;
     echo "<fieldset class=dbstat><legend><b>MySQL Info</b></legend>";
     echo "<table width=100%><tr><td align=right>Total of $DB_Q_COUNT MySQL querries in $DB_Q_TIME seconds.</td></tr></table>\n";
     echo "</fieldset>";
   }

   function dbquery($strSQL)
   {
       global $db;
       global $DB_Q_TIME,$DB_Q_COUNT;
       global $adminEmail;
       global $_SESSION;
       global $_FORM;
       global $CURRENT_USER;
       global $MAIL_HEADER;
       global $PHP_SELF;
       global $_SERVER;
       $this_page=$_SERVER['PHP_SELF'];
       $DB_Q_COUNT++;
       debug(3,"DB QUERY:<DIR>$strSQL</DIR>");
       $_start=microtime();
       $queryValue = @mysql_query($strSQL, $db);
       $_end=microtime();
       $delta=0;
       if($_end>$_start)$delta=$_end-$_start;
       $DB_Q_TIME+=$delta;
       if (!$queryValue)
       {
          $msgBody ="User :".$CURRENT_USER['LAST_NAME'].", ".$CURRENT_USER['FIRST_NAME']." [USER ID=".$CURRENT_USER['ID']."]<BR><BR><BR>\n";
          $msgBody.="Query:<DIR>".$strSQL."</DIR><BR><BR>\n";
          $msgBody.="PAGE: $this_page<BR><BR><BR>\n";
          $msgBody.="Mysql Error()<DIR>".mysql_error()."</DIR><BR><BR>\n";
	  $x="";reset($_SESSION);while(list($n,$v)=each($_SESSION))$x.="$n = $v<BR>";
          $msgBody.="SESSION DATA:<DIR>$x</DIR><BR><BR>\n";
	  $x="";reset($_FORM);while(list($n,$v)=each($_FORM))$x.="$n = $v<BR>";
          $msgBody.="FORM DATA:<DIR>$x</DIR><BR><BR>\n";
          //mail($adminEmail,"DB MySQL Error: ".date("m-d-Y"),$msgBody,$MAIL_HEADER);
	  //exit;
          //die("An Error with the database has occurred.");
          die("<br><table class=warn align=center><tr><td>MySQL Error: ".mysql_error()."</td></tr><tr><td><BR><BR>QUERY: $strSQL</td></tr></table>");
       }
       else
       {
           return $queryValue;
       }
   }

   function dbqueryWithAlert($strSQL, $adminEmail, $errorMessage)
   {
       global $db, $strError, $criticalTransactionError;
       if (!$queryValue = @mysql_query($strSQL, $db)) {
           mail($adminEmail, "DB MySQL Error: ".date("m-d-Y"), $errorMessage);
           $strError = $errorMessage;
           $criticalTransactionError = TRUE;
       }
       else
       {
           return $queryValue;
       }
   }

   $db = mysql_connect($DB_HOST,$DB_USER,$DB_PASSWORD);
   mysql_select_db($DB_TABLE,$db);
}
?>
