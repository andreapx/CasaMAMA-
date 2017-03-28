<?php
//Ordine connessioni:
RX - TX - VCC - GND


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$db_server = "127.0.0.1";
$db_name = "CasaMama";
$db_user = "CasaMama";
$db_password = "m7JuqPAcJ05ndOQg";
$con=mysqli_connect($db_server, $db_user, $db_password, $db_name);
if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
}
?>
