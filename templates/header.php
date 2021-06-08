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

global $HACK_CHECK;
global $TITLE;

if(!isset($_SESSION['userName'])){
   $_SESSION = array();
   session_destroy();
}
if(!isset($HACK_CHECK) || !$HACK_CHECK)exit; // DO NOT DIRECTLY LOAD THIS FILE

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>


<head>
<title><?=$TITLE;?></title>
<link href="style.css" rel="stylesheet" type="text/css" >
<link rel="stylesheet" href="scripts/csshorizontalmenu.css" type="text/css" >
</head>
<body>
<div class=headerbackground2>
<div class=header2><img src="images/paev_header.png" alt="PAEV"><a href="http://paev.js-x.com/">PHP Adjusted Earned Value System</a></div>
</div>

