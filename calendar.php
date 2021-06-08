<?
//Check for date selectie from query string
if (isset($_GET['monthno'])) $monthno = $_GET['monthno'];
if (isset($_GET['year']))    $year = $_GET['year'];
if (!isset($monthno)||!$monthno) { $monthno=date('n'); }
if (!isset($year)||!$year||!strlen($year)||($year=="undefined")) { $year = date('Y'); }
//echo "MONTH($monthno) YEAR($year / ".date("Y").")<BR>\n";
//check the current date
$now = mktime(0, 0, 0, $monthno, 1, $year);
$monthfulltext = date('F', $now);
$monthshorttext = date('M', $now);

//number of days
$day_in_mth = date('t', $now) ;
$day_text = date('D', $now);

//Find the selected year and date
$monthno = date('m', $now);
$year = date('Y', $now);
?>
<html>
<head>
<style type="text/css">

.tdday { font-family: Verdana, Arial, Helvetica, sans-serif;
                  background-color: #8BBDDE;
                  font-weight: normal;
                  font-size: 9px;
                  width: 26px;
                  line-height: 20px;
                  color: #fff;
                  vertical-align: middle;
                  text-align: center;
}
.tdtoday { font-family: Verdana, Arial, Helvetica, sans-serif;
                  background-color: #DEECF7;
                  font-weight: bold;
                  font-size: 10px;
                  line-height: 16px;
                  width: 26px;
                  color: #000000;
                  vertical-align: middle;
                  text-align: center;
                  border:solid 1px #87BBDD;
}

.tdheading { font-family: Verdana, Arial, Helvetica, sans-serif;
                  background-color: #DEECF7;
                  font-weight: bold;
                  font-size: 10px;
                  line-height: 18px;
                  color: #000;
                  vertical-align: middle;
                  text-align: center;
                  padding:0px;
}
.tddate { font-family: Verdana, Arial, Helvetica, sans-serif;
                  background-color: #E7F2F9;
                  font-weight: normal;
                  font-size: 10px;
                  line-height: 16px;
                  width: 26px;
                  color: #000000;
                  vertical-align: middle;
                  text-align: center;
 }
.caltable { border: #a0a0a0;
                   border-style: solid;
                   border-top-width: 1px;
                   border-right-width: 1px;
                   border-bottom-width: 1px;
                   border-left-width: 1px;
                   margin-bottom: 0px;
                   margin-top: 0px;
                   margin-right: 0px;
                   margin-left: 0px;
                   padding-top: 0px;
                   padding-right: 0px;
                   padding-bottom: 0px;
                   padding-left: 0px
}
#mnoprev, #mnonext {font-size:10px; font-family: Verdana, Arial, Helvetica, sans-serif; color:#999;}
#mnoprev {position:absolute;left:5px;bottom:5px;}
#mnonext {position:absolute;right:5px;bottom:5px;}
</style>
<title>Calendar</title>
</head>
<body>
<table class="caltable" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="1" width=97%>
<tr><td colspan="7" class="tdheading"><? echo $monthfulltext." ".$year ?></td></tr>
<tr>
<td class="tdday">Sun</td><td class="tdday">Mon</td><td class="tdday">Tue</td><td class="tdday">Wed</td><td class="tdday">Thu</td><td class="tdday">Fri</td><td class="tdday">Sat</td>
</tr>
<tr>
<?
//When the first day in the month is not the first day of the week, add some empty cells
$day_of_wk = date('w', mktime(0, 0, 0, $monthno, 1, $year));

if ($day_of_wk <> 0){
   for ($i=0; $i<$day_of_wk; $i++)
   { echo '<td class="tddate">&nbsp;</td>'; }
}

//Show all days within the month
for ($date_of_mth = 1; $date_of_mth <= $day_in_mth; $date_of_mth++) {

    if ($day_of_wk = 0){
   for ($i=0; $i<$day_of_wk; $i++);
   { echo "<tr>"; }
}
    $day_text = date('D', mktime(0, 0, 0, $monthno, $date_of_mth, $year));
    $date_no = date('j', mktime(0, 0, 0, $monthno, $date_of_mth, $year));
    $day_of_wk = date('w', mktime(0, 0, 0, $monthno, $date_of_mth, $year));
   //$neatdate = sprintf('%04d-%02d-%02d', intval($year), intval($monthno), intval($date_of_mth));
    $test_year=$year-2000;
   $neatdate = sprintf('%02d/%02d/20%02d', intval($monthno), intval($date_of_mth), intval($test_year));
   if ( $date_no ==  date('j') &&  $monthno == date('n') )
     {  echo "<td class=tdtoday>".linkify($date_no, $neatdate)."</td>"; }
   else{
   echo "<td class=tddate>".linkify($date_no, $neatdate)."</td>";  }
   
   
   If ( $day_of_wk == 6 ) {  echo "</tr>"; }

//If last day of the month is not last day of the week, add empty cells.
   If ( $day_of_wk < 6 && $date_of_mth == $day_in_mth ) {
   for ( $i = $day_of_wk ; $i < 6; $i++ ) {
     echo "<td class=tddate>&nbsp;</td>"; }
      echo "</tr>";
      }
 }
?>
</table>
<?php
  //display links to next and previous month;
  echo '<span id="mnoprev"><a href="?monthno='.($monthno).'&year='.($year-1).'">&laquo;&laquo;</a>';
  echo '&nbsp;<a href="?monthno='.($monthno-1).'&year='.$year.'">&lt;&lt;</a></span>';
  echo '<span id="mnonext"><a href="?monthno='.($monthno+1).'&year='.$year.'">&gt;&gt;</a>';
  echo '&nbsp;<a href="?monthno='.($monthno).'&year='.($year+1).'">&raquo;&raquo</a></span>';

  /*
   function linkify generates a clickable day-of-month hyperlink, in order to pass
   any clicks to the parent window.
   */
  function linkify($text, $date)
  {
    return '<a style="cursor:pointer;" onClick="window.opener.calendarPopupClick(\''.$date.'\');window.close();">'.$text.'</a>';
  }
?>  
</body>
</html>
