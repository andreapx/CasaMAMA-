<?PHP
include_once 'config.php';

if (php_sapi_name() != "cli"){
  ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<meta http-equiv="Refresh" content="5; url=<?php echo $_SERVER['HTTP_REFERER'];?>"> 

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>Update codes</title>
</head>
<body>   
  <?PHP
}else{
  if (isset($argv[1])){
    $_GET['action'] = $argv[1];
  }
  if (isset($argv[2])){
    $_GET['forceUpdate'] = $argv[2];
  }  
}

if (isset($_GET['forceUpdate'])){
  if ($_GET['forceUpdate'] == "yes"){
    $updateArduino = 1;
  }else{
    $updateArduino = 0;
  }
}else{
  $updateArduino = 0;
}

function updateArduino(){
  $response = file_get_contents("http://127.0.0.1:1880/UpdateCodes");
  //echo "HTTP Response: \n$response<BR>\n";
  if ($response == "OK!"){
    echo date("Y-m-d H:i:s") . " - Codici correttamente inviati alla tastiera!<BR>\n";
  }else{
    echo date("Y-m-d H:i:s") . " - Problema invio codici alla tastiera. Risposta ricevuta:$response<BR>\n";
  }
}

function checkCodeConsistency($MySQLconnection){
  $ArduinoCodes = file_get_contents("http://127.0.0.1:1880/DownloadCodesFromArduino");
  $lines = explode("\n", $ArduinoCodes);
  $sql = "SELECT Code FROM Codes WHERE ActiveFrom <= (CURRENT_TIMESTAMP()) AND ActiveUntil > (CURRENT_TIMESTAMP()) AND Active = 1";
  $result = mysqli_query($MySQLconnection,$sql);
  foreach($lines as $word){
    $pos = strpos($word, "Code");
    if ( $pos !== false){
      $start = strpos($word, ":");
      $ArduinoCode = substr($word, $start + 2, 6);
      if ($ArduinoCode == "000000"){
        //echo date("Y-m-d H:i:s") . " - Codici finiti<BR>\n";
        break;
      }else{ 
        $row = mysqli_fetch_assoc($result);
        //echo date("Y-m-d H:i:s") . " - Arduino code : $ArduinoCode, MySQL code: " . $row['Code'] . "<BR>\n";
        if ($ArduinoCode != $row['Code']){
          echo date("Y-m-d H:i:s") . " - Oh oh, abbiamo un problema! Questi codici non coincidono: Arduino: $ArduinoCode, MySQL: " . $row['Code'] . "<BR>\n";
          return "Error";        
        }
      } 
    } 
  }
}



if($_GET['action'] == "updateCodes"){
  //    Aggiungo ad Arduino i codici attivi ora
  $sql = "SELECT ID, Active FROM Codes WHERE ActiveFrom <= (CURRENT_TIMESTAMP()) AND ActiveUntil > (CURRENT_TIMESTAMP()) AND Active = 0 LIMIT 19";
  $result = mysqli_query($con, $sql);
  while ($row = mysqli_fetch_assoc($result)){
      echo date("Y-m-d H:i:s") . " - Aggiungo ad Arduino ID " . $row['ID'] . "<BR>\n";
      $updateArduino = 1;
      $sql = "UPDATE Codes SET Active = 1 WHERE ID = '" . $row['ID'] . "'";
      $result2 = mysqli_query($con,$sql);
    //}
  }

  //    Tolgo da Arduino i codici non più attivi
  $sql = "SELECT ID, Active FROM Codes WHERE ( ActiveUntil < CURRENT_TIMESTAMP() OR ActiveFrom > CURRENT_TIMESTAMP() ) AND Active = 1";
  $result2 = mysqli_query($con,$sql);
  while ($row = mysqli_fetch_assoc($result2)){
    echo date("Y-m-d H:i:s") . " - Elimino da Arduino ID " . $row['ID'] . "<BR>\n";
    $updateArduino = 1;
    $sql = "UPDATE Codes SET Active = 0 WHERE ID = '" . $row['ID'] . "'";
    $result3 = mysqli_query($con,$sql);         
  }  
  
  //echo date("Y-m-d H:i:s") . " - updateArduino = $updateArduino<BR>\n";
  if ($updateArduino != 0){
    updateArduino();
  }
  //sleep(5); 
  $consistencyError = checkCodeConsistency($con);
  if ($consistencyError == "Error"){
    echo date("Y-m-d H:i:s") . " - Secondo tentativo di aggiornamento Arduino<BR>\n";
    updateArduino();
    $consistencyError = checkCodeConsistency($con); 
    if ($consistencyError == "Error"){
      echo date("Y-m-d H:i:s") . " - ERROR: secondo tentativo di aggiornamento Arduino fallito<BR>\n";
    }else{
      echo date("Y-m-d H:i:s") . " - Secondo controllo andato a buon fine!<BR>\n";
    }
  }
}
if (php_sapi_name() != "cli"){
  ?>
</body>
</html>
  <?PHP
}
?>