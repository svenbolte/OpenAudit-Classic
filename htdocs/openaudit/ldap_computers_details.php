<?php 
$page = "";
$extra = "";
$software = "";
$count = 0;
$total_rows = 0;

$page = "";
include "include.php"; 

$title = "ldap_users_datails.php";
if (isset($_GET["show_all"])){ $count_system = '10000'; } else {}
if (isset($_GET["page_count"])){ $page_count = $_GET["page_count"]; } else { $page_count = 0;}
$page_prev = $page_count - 1;
if ($page_prev < 0){ $page_prev = 0; } else {}
$page_next = $page_count + 1;
$page_current = $page_count;
$page_count = $page_count * $count_system;

echo "<td>\n";


$computer_name = "";

if (isset($_GET['name'])) {$name = $_GET['name'];} else {$name= "none";}
if (isset($_GET['show_details'])) {$show_details = $_GET['show_details'];} else {$show_details= "basic";}

if ($use_ldap_integration == "y") {

// Find name from domain\name

$slash_char = chr(92);

$pos = strrpos($name, $slash_char);

if ($pos === false ) {
    // Dont need to do anything if we didn't find a slash in the username.
    } else {
    // We pick up the right half of the string  if we found the slash
    $pos=$pos+1;
    $name = substr($name,($pos));
//   echo $name;
    }



//
//Note that this LDAP string specifies the OU that contains the User Accounts
//All OUs under it are also retrieved
$dn = $ldap_base_dn;

//domain user fullname and password

$computer = $ldap_user;
$secret = $ldap_secret;
//$name="*".$name;
$attributes = array("displayname","mail","telephonenumber","location","department");
//$attributes = array("displayname","description","userprincipalname","homedirectory","homedrive","profilepath","scriptpath","mail","samaccountname","telephonenumber","location","department","sn","badpwdcount");

//$filter = "(&(objectClass=user)(objectCategory=person)((samaccountname=".$name.")(name=".$name.")(displayname=".$name.")(cn=".$name."))";
//$filter = "(&(objectClass=user)(objectCategory=person)(|(samaccountname=".$name.chr(42).")(name=".$name.chr(42).")(displayname=".$name.chr(42).")(cn=".$name.chr(42).")))";
$filter = "(&(objectClass=computer)(objectCategory=computer)(|(samaccountname=".$name.chr(42).")(name=".$name.chr(42).")(displayname=".$name.chr(42).")(cn=".$name.chr(42).")))";
//$filter = "(&(objectClass=*)(objectCategory=*)(|(name=".$name.chr(42).")(displayname=".$name.chr(42).")(cn=".$name.chr(42).")))";
//if ($show_details == 'dump') {$filter = "(&(objectClass=*)(objectCategory=*))";}
if ($show_details == 'dump') {$filter = "(&(objectCategory=person)(objectClass=user)(telephonenumber=*))";}


//(|(name=$name*)(displayname=$name*)(cn=$name*))

// This throws away some spurious Active Direcrory error related nonsense if you have no phone number or whatever
// should really catch this gracefully
error_reporting(0);


if ($ad = ldap_connect($ldap_server)) {

ldap_set_option($ad, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ad, LDAP_OPT_REFERRALS, 0);
$bd = ldap_bind($ad,$computer,$secret);
if ($bd){
  //echo "Admin - Authenticated<br>";
} else {
        echo "<div class=\"main_each\">\n";
        echo "<form action=\"call_users_details.php?sub=no\" method=\"post\">";
        echo "<table class=\"tftable\"    class=\"content\">";
     	
        echo "<p>"; 
        echo "<tr><td><b>".__("User or Password invalid when attemting to connect to ".$ldap_base_dn.".")."</b></td></tr>";
}


ldap_set_option($ad, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ad, LDAP_OPT_REFERRALS, 0);
$bd = ldap_bind($ad,$computer,$secret);
if ($bd){
  //echo "Admin - Authenticated<br>";
} else {
        echo "<div class=\"main_each\">\n";
        echo "<form action=\"call_users_details.php?sub=no\" method=\"post\">";
        echo "<table class=\"tftable\"    class=\"content\">";
     	
        echo "<p>"; 
        echo "<tr><td><b>".__("User or Password invalid when attemting to connect to ".$ldap_base_dn.".")."</b></td></tr>";
}



if ($show_details == "dump"){$result = ldap_search($ad, $dn, $filter, $attributes);}
    else
    {$result = ldap_search($ad, $dn, $filter);}


//$result = ldap_search($ad, $dn, $filter);
ldap_sort($ad,$result,"displayname");
$entries = ldap_get_entries($ad, $result);



echo "<div class=\"main_each\">\n";

echo "<form action=\"call_users_details.php?sub=no\" method=\"post\">";
echo "<table class=\"tftable\"    class=\"content\">";

$num_found = $entries["count"];

if ($num_found == 0 ){


        echo "<div class=\"main_each\">\n";
        echo "<form action=\"call_users_details.php?sub=no\" method=\"post\">";
        echo "<table class=\"tftable\"    class=\"content\">";
     	
        echo "<p>"; 
        echo "<tr><td><b>".__("Not found in ".$ldap_base_dn.".")."</b></td></tr>";

} else {

for ($computer_record_number = 0; $computer_record_number<$num_found; $computer_record_number++) {
//echo "Next User:<br>";

$record_number = $computer_record_number+1;
      echo "<td><i class='fa fa-user' aria-hidden='true'></i>";
	  echo "<tr><td><h3>" . $entries[$computer_record_number]["name"][0] . "</h3></td><td></td></tr>";
 	  echo "<tr><td>" .__("Full Account Details"). "</td><td></td></tr>";      
      for ($computer_record_field_number=0; $computer_record_field_number<$entries[$computer_record_number]["count"]; $computer_record_field_number++){
      $data =$entries[$computer_record_number][$computer_record_field_number];

      for ($computer_record_field_number_data=0; $computer_record_field_number_data<$entries[$computer_record_number][$data]["count"]; $computer_record_field_number_data++) {
      if  (isEmailAddress($entries[$computer_record_number][$data][$computer_record_field_number_data])){
          // If its a valid email address, highlight it, and add a URL mailto:
      	
     echo "<tr><td><b>".__($data).":</b></td><td><a href='mailto:" . $entries[$computer_record_number][$data][$computer_record_field_number_data] . "'>" . $entries[$computer_record_number][$data][$computer_record_field_number_data] . "</a></td></tr>";
     }
     else 
     {
            // Else just show it. 
      	  
          echo "<tr><td>".__($data).":</td><td>" .$entries[$computer_record_number][$data][$computer_record_field_number_data]. "</td></tr>";
//         echo "<tr><td>".$data.":</td><td>" .$entries[$computer_record_number][$data][$computer_record_field_number_data]. "</td></tr>";

}    
     }
  }
    echo "<p>"; // separate entries
    echo "<tr><td colspan=\"2\"><hr /></td></tr>\n";

 }
}



} else {

        echo "<div class=\"main_each\">\n";
        echo "<form action=\"call_users_details.php?sub=no\" method=\"post\">";
        echo "<table class=\"tftable\"    class=\"content\">";
     	
        echo "<p>"; 
        echo "<tr><td><b>".__("LDAP Not configured. Please set this up in Admin> Config")."</b></td></tr>";


//        echo "<tr>".__("LDAP Not configured. Please set this up in Admin> Config")."</tr>";
}

} else {
        echo "<div class=\"main_each\">\n";
        echo "<form action=\"call_users_details.php?sub=no\" method=\"post\">";
        echo "<table class=\"tftable\"    class=\"content\">";
     	
        echo "<p>"; 
        echo "<tr><td><b>".__("Bind failure attempting to connect to ".$ldap_base_dn.".")."</b></td></tr>";
}

echo "</table>";

echo "</td>\n";



echo "</body>\n</html>\n";


?>
