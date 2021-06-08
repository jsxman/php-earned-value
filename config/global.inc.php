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

error_reporting(E_ERROR|E_PARSE); // REPORT ONLY FATAL ERRORS AND PARSE ERRORS
if(!isset($HACK_CHECK) || !$HACK_CHECK)exit; // DO NOT DIRECTLY LOAD THIS FILE

  /* start the timer on the page loading */
  /* dont change this */
  if(!isset($PAGE_TIME_START)) $PAGE_TIME_START=microtime();

  $DEBUG=3; // debug output on or off (1 is on , 0 is off)
  $SHOW_TODO=0; // set to 1 to show the message. 0 to not show them

  $SESSION_TIMEOUT=60*60; // 60 minutes - This keeps the session alive for 60 minutes longer

   // Chart Directors Path as on esx-softeng
  $CHARTDIRECTOR = "/usr/local/lib/chartdirector/lib/phpchartdir.php";
  
  // These are the templates to use
  $TEMPLATE_PATH="templates";
  $HEADER_FILE="$TEMPLATE_PATH/header.php";
  $MENU_FILE  ="$TEMPLATE_PATH/menubar.php";
  $FOOTER_FILE="$TEMPLATE_PATH/footer.php";


  $EVENT_MEETING=0; $EVENT_DOCUMENT=1; /* DO NOT CHANGE - THESE ARE CONSTANTS */
  $EVENT_TYPES=Array(); $i=0; /* i is just a temp. variable */
  $EVENT_TYPES[$i++]=Array("ARO",$EVENT_MEETING);
  $EVENT_TYPES[$i++]=Array("KOM",$EVENT_MEETING);
  $EVENT_TYPES[$i++]=Array("SRR",$EVENT_MEETING);
  $EVENT_TYPES[$i++]=Array("SSR",$EVENT_MEETING);
  $EVENT_TYPES[$i++]=Array("ADR",$EVENT_MEETING);
  $EVENT_TYPES[$i++]=Array("PDR",$EVENT_MEETING);
  $EVENT_TYPES[$i++]=Array("CDR",$EVENT_MEETING);
  $EVENT_TYPES[$i++]=Array("TRR",$EVENT_MEETING);
  $EVENT_TYPES[$i++]=Array("FQT",$EVENT_MEETING);
  $EVENT_TYPES[$i++]=Array("TCM",$EVENT_MEETING);
  $EVENT_TYPES[$i++]=Array("TIM",$EVENT_MEETING);
  $EVENT_TYPES[$i++]=Array("SDP",$EVENT_DOCUMENT);
  $EVENT_TYPES[$i++]=Array("CMMI",$EVENT_DOCUMENT);
  $EVENT_TYPES[$i++]=Array("STP",$EVENT_DOCUMENT);
  $EVENT_TYPES[$i++]=Array("SRS",$EVENT_DOCUMENT);
  $EVENT_TYPES[$i++]=Array("IRS",$EVENT_DOCUMENT);
  $EVENT_TYPES[$i++]=Array("SDD",$EVENT_DOCUMENT);
  $EVENT_TYPES[$i++]=Array("IDD",$EVENT_DOCUMENT);
  $EVENT_TYPES[$i++]=Array("STD",$EVENT_DOCUMENT);
  $EVENT_TYPES[$i++]=Array("STR",$EVENT_DOCUMENT);
  $EVENT_TYPES[$i++]=Array("VDD",$EVENT_DOCUMENT);
  unset($i); /* get rid of the temp variable */

  $COLORS = Array(); $i=0;
  $COLORS[$i++] = 0x00FF0000;	//RED
  $COLORS[$i++] = 0x00FF6600;	//ORANGE
  $COLORS[$i++] = 0x00FFCC00;	//YELLOW-ORANGE
  $COLORS[$i++] = 0x00FF0066;	//DARK PINK
  $COLORS[$i++] = 0x00FFFFCC;	//PALE YELLOW
  $COLORS[$i++] = 0x00CCCC99;	//TAN
  $COLORS[$i++] = 0x00CC9966;	//DARK TAN
  $COLORS[$i++] = 0x00CC6633;	//BROWN-RED
  $COLORS[$i++] = 0x00CCFFCC;	//SEA GREEN
  $COLORS[$i++] = 0x00CCFF99;	//LIGHT GREEN
  $COLORS[$i++] = 0x00996666;	//PURPLE-BROWN
  $COLORS[$i++] = 0x0099CCFF;	//LIGHT-BLUE
  $COLORS[$i++] = 0x009999FF;	//BLUE-PURPLE
  $COLORS[$i++] = 0x009933FF;	//PURPLE
  $COLORS[$i++] = 0x006633FF;	//BLUE
  unset($i); 


  # -------------------------------------------------------------------- #
  /* these are the names of the pages - change only if you change file names */
  /* there is no need to change them */
  $PAGE_INDEX        ="index.php";
  $PAGE_LOGIN        ="login.php";

  $PAGE_PROJECTS     ="projects.php";
  $PAGE_TASK         ="tasks.php";
  $PAGE_SETTINGS     ="project-settings.php";
  $PAGE_HISTORY      ="history.php";
  $PAGE_EVENTS       ="events.php";

  $PAGE_REPORTS      ="reports.php";
  $PAGE_HOURS        ="monthly-hours.php";
  $PAGE_GRAPH        ="graphs.php";
  $PAGE_SHOW         ="show.php";

  $PAGE_HELP         ="help.php";

  $PAGE_ADMIN        ="admin.php";
  $PAGE_P_SETTINGS   ="paev-settings.php";
  $PAGE_PEOPLE       ="people.php";

  # -------------------------------------------------------------------- #
  /* all access to Mysql table names is via these defined names */
  /* there is no need to change them */

  $MYSQL_PREFIX="pev__";
  $TABLE_PERSON            =$MYSQL_PREFIX."person";
  $TABLE_PROJECT           =$MYSQL_PREFIX."project";
  $TABLE_PROJECT_ACCESS    =$MYSQL_PREFIX."project_access";
  $TABLE_WBS               =$MYSQL_PREFIX."wbs";
  $TABLE_TASK              =$MYSQL_PREFIX."task";
  $TABLE_WBS_TO_TASK       =$MYSQL_PREFIX."wbs_to_task";
  $TABLE_PERSON_TO_WBSTASK =$MYSQL_PREFIX."person_to_wbstask";
  $TABLE_WBS_HISTORY       =$MYSQL_PREFIX."wbs_history";
  $TABLE_EVENT             =$MYSQL_PREFIX."event";

  ## This defines your connection to your MySQL database
  $DB_HOST     ="localhost";
  $DB_TABLE    ="paev_tool_4";
  $DB_USER     ="pev";
  $DB_PASSWORD ='online';

  //change before you create any passwords! or you will have to
  //change them all after you set a new encrypt key
  $encryptKey="something that is your secret. shh!";

  // If you change something other than the above, change this VERSION
  $PAEV_VERSION="0.3.0"; /* this is the version of this tool */


  /************** FROB NO FURTHER **********************/
  /************** FROB NO FURTHER **********************/
  /************** FROB NO FURTHER **********************/
  /************** FROB NO FURTHER **********************/
  /************** FROB NO FURTHER **********************/
  /************** FROB NO FURTHER **********************/
  /************** FROB NO FURTHER **********************/
  /************** FROB NO FURTHER **********************/
  /************** FROB NO FURTHER **********************/
  /************** FROB NO FURTHER **********************/

  if($DEBUG>10) echo "DEBUG(10): Loading File: global.inc.php<BR>\n";

  // no need to change these - these are used to lookup access levels
  $ACCESS_NONE=0;$ACCESS_USER=1;$ACCESS_ADMIN=2;

  /* declare global variables that will be used */
  $TODO;
  $STATUS;
  $ERROR;

  /* don't change these */
  session_register("paev_userID");
  session_register("time");
  session_register("dbAdmin");
  session_register("projectID");
  //echo "SESS(userID)=".$_SESSION['userID']."<BR>\n";

  $strIncludePrefix = "config";
  $sequence1="seq1";
  Include($strIncludePrefix."/db.inc.php");
  Include($strIncludePrefix."/generalFunctions.inc.php");
  Include($strIncludePrefix."/securityFunctions.inc.php");

  $DEBUG_POSTED=getPostedData(); // This builds a global variable called $_FORM[] - keys are posted varaible name, values are values of posted data

  header("Cache-control: private"); // enables forms to retain values when user hits 'back' button

?>
