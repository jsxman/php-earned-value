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
debug(10,"Loading File: index.php");
debug(10,"User Level is DB-Admin (".$_SESSION['dbAdmin'].")");
checkPermissions($SESSION_TIMEOUT); // if not logged in, or session has timed out...
/******************************************/
/* HEADER: INCLUDE/SECURITY CHECK - END   */
/******************************************/



/******************************************/
/* COMPUTING - NO OUTPUT - START          */
/******************************************/

/******************************************/
/* COMPUTING - NO OUTPUT - END            */
/******************************************/

/******************************************/
/* COMPUTING - SHOW OUTPUT - START        */
/******************************************/
$TITLE="Index Page";
show_header();
show_menu("HOME");
show_status();
show_error();


?>
<div style="position:relative; top:-150px">
<table>
	<tr>
		<td>
<?
//Display one of two menu images: One for db Admins, or one for everyone else.
if($_SESSION['dbAdmin'])
{
?>
			<img src=images/logo-welcome2.png usemap="#navigate" />

			<map name="navigate">
				<area shape="rect" coords="43,113,245,147" href="projects.php" alt="projects">
				<area shape="rect" coords="43,167,245,202" href="reports.php" alt="reports">
				<area shape="rect" coords="43,219,245,254" href="help.php" alt="help">
				<area shape="rect" coords="43,275,245,309" href="admin.php" alt="admin">
				<area shape="rect" coords="43,325,245,360" href="logout.php" alt="logout">
			</map>
<?
}
else
{
?>
			<img src=images/logo-welcome.png usemap="#navigate" />

			<map name="navigate">
				<area shape="rect" coords="43,113,245,147" href="projects.php" alt="projects">
				<area shape="rect" coords="43,167,245,202" href="reports.php" alt="reports">
				<area shape="rect" coords="43,219,245,254" href="help.php" alt="help">
				<area shape="rect" coords="43,275,245,309" href="logout.php" alt="logout">
			</map>
<?
}
?>
		</td>
		<td>
			<h1>Welcome to the PAEV Tool</h1>
			<p class=paragraph>
The PAEV tool is a PHP-based web application that allows users to track project 
tasks and project statistics related to cost and schedule performance.
			</p>
		</td>
	</tr>
</table>
</div>



<?

show_footer();


/******************************************/
/* COMPUTING - SHOW OUTPUT - END          */
/******************************************/


?>
