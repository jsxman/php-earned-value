<?
if(!isset($HACK_CHECK) || !$HACK_CHECK)exit;
##########################################################################
#function authenticate_user
# @params:
#     username: the username as entered by the user,
#     password: the password entered by the  user .
#
# Purpose: to check to see if the username and password comabination  will bind against 
#     the Active Directory service using the LDAP connection model
#
# Returns: True when username and password pair exist within the active directory server
#          False when username and password pair do not exist
##########################################################################
function authenticate_user($username,$password){
   $username = trim($username);
   $password = trim($password);
   if( $password && $password != ''){
      require_once("Net/LDAP.php");
      $configs[0] = array('basedn'=> 'dc=efw,dc=com','host'=>'efw.com','binddn'=>$username.'@efw.com','bindpw'=>$password);
      $configs[1] = array('basedn'=> 'dc=esa,dc=local','host'=>'10.1.32.254','binddn'=>$username.'@esa.local','bindpw'=>$password);
	  foreach($configs as $config){
         try{
            $ldap = Net_LDAP::connect($config);
         
            if (Net_LDAP::isError($ldap)){
               $_SESSION['LDAP_ERROR']= "\n<br>Invalid Username or Password<br>\n"; //this will tell you what went wrong!
            }
            else{
               $ldap->done();
               return true;
            }
         }catch(Exception $e){
            $_SESSION['LDAP_ERROR']= "LDAP Exception Thown during User Authentication";
         }
	  }
   }
   return false;
}

###########################################################################
#function valid_user
#  parameters:
#      username: as entered by the user
#      password: as entered by the user
#      column_names: the names of the columns within the MySQL table denoted table
#      table: the name of the MySQL that contains user information including usernames
#      username_field: the name of the username column in the MySQL table 
#      database: the database resource that is connected to the MySQL database
#
#  purpose: this function validates that a username exists within a MySQL
#      table as well as within the Active Directory server. the password is only
#      checked against the AD server.
#
#  returns: returns all values from the first row returned by the query for
#      the columns denoted by column_names, using the conditional statment of
#      username_field = username, On success
#    false on failure
###########################################################################
function valid_user($username, $password,array $column_names, $table = 'users', $username_field = 'username', $database = NULL ){
   $username = trim($username);
   $password = trim($password);
   $column_names = implode(',',$column_names);
   
   $sanitizevals = array("\\","\g",";","'");
   //Make SQL safe
   $username = str_replace($sanitizevals,'',$username);
   $password = str_replace($sanitizevals,'',$password);
   $table = str_replace($sanitizevals,'',$table);
   $column_names = str_replace($sanitizevals,'',$column_names);
   $username_field = str_replace($sanitizevals,'',$username_field);
   
   $query = "SELECT ". $column_names . " FROM " . $table . " WHERE " . $username_field . "=\"" . $username."\"";
   if($database)
      $result = mysql_query($query,$database) or die($query."  ".mysql_error($database));
   else
      $result = mysql_query($query) or die($query. "  ". mysql_error($database));
   if($row = mysql_fetch_array($result)){
      //echo "user name is in table ";
      if(authenticate_user($username,$password))
         return $row;
   }else{
        //echo "user name not in table ".$query; 
   }
   return false;
}

?>