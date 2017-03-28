<?php
session_start();
include_once 'config.php';

if(isset($_SESSION['usr_id'])!="") {
  if (isset($_POST['login'])) {
    echo "Hi!";    
  }
  if(isset($_POST['Command'])){
    switch ($_POST['Command']){
      case "Save":
      //        INSERIMENTO NUOVO CODICE
        if (isset($_POST['Name_new'])){
          if ($_POST['Name_new'] != ""){ 
            /*
            if (isset($_POST['Active_new'])) {
              print_r($_POST); 
              $Active_clean = mysqli_real_escape_string($con, $_POST['Active_new']);
              if ($Active_clean == "on"){
                $Active_clean = 1;
              }
            }else{
              $Active_clean = 0;
            }*/
            $Active_clean = 0;
            if (isset($_POST['Code_new'])) {    
              $Code_clean = mysqli_real_escape_string($con, $_POST['Code_new']);
            }
            if (isset($_POST['Name_new'])) {
              $Name_clean = mysqli_real_escape_string($con, $_POST['Name_new']);
            }
            if (isset($_POST['ActiveFrom_new'])) {
              $ActiveFrom_clean = mysqli_real_escape_string($con, $_POST['ActiveFrom_new']);
            }
            if (isset($_POST['ActiveUntil_new'])) {
              $ActiveUntil_clean = mysqli_real_escape_string($con, $_POST['ActiveUntil_new']);
            }
            $errors = 0;
            if (($_POST['ActiveFrom_new']) == ""){
              echo "Errore: Manca la data di arrivo<BR>";
              //$ok++;
              $errors = 1;
            }
            if (($_POST['ActiveUntil_new']) == ""){
              echo "Errore: Manca la data di partenza<BR>";
              //$ok++;
              $errors = $errors + 2;
            }
            if (($_POST['Name_new']) == ""){
              echo "Errore: Manca la data di partenza<BR>";
              //$ok++;
              $errors = $errors + 4;
            }
            if ($ActiveFrom_clean > $ActiveUntil_clean){
              echo "Errore: La data di partenza non può essere inferiore a quella di arrivo<BR>";
              //$ok++;
              if ($errors != 2 && $errors != 6){
                $errors = $errors + 8;
              }
            }
            if ( ($errors != 1) && ($errors != 3) && ($ActiveFrom_clean == $ActiveUntil_clean) ){
              echo "Errore: La data di partenza e quella di arrivo non possono coincidere<BR>";
              //$ok++;
              if ($errors != 3){
                $errors = $errors + 16;
              }
            }
            $now = time();
            $ActiveFrom_PHP = strtotime($ActiveFrom_clean);    
            if ($ActiveFrom_PHP < $now){
              echo "ERRORE: La data di arrivo è antecedente ad ora";
              $errors = $errors + 32;  
            }
            /*switch ($errors) {
              case 1:
                echo "Errore $errors: Manca la data di arrivo";
                break;
              case 2:
                echo "Errore $errors: Manca la data di partenza";
                break;
              case 4:
                echo "Errore $errors: Manca il nome";
                break;
              case 8:
                echo "Errore $errors: La data di partenza non può essere antecedente a quella di arrivo";
                break;
              case 16:
                echo "Errore $errors: La data di partenza non può essere uguale a quella di arrivo";
                break;
              case 3:
                echo "Errore $errors: Mancano la data di arrivo e quella di partenza";
                break;
              case 5:
                echo "Errore $errors: Manca il nome e la data di arrivo";
                break;
              case 6:
                echo "Errore $errors: Manca il nome e la data di partenza";
                break;
              case 12:
                echo "Errore $errors: Manca il nome e la data di partenza non può essere antecedente a quella di arrivo";
                break;
              case 20:
                echo "Errore $errors: Manca il nome e la data di arrivo e di quella di partenza non possono essere uguali";
                break;
              case 32:
                echo "Errore $errors: La data di arrivo è antecedente ad adesso";
                break;
              case 34:
                echo "Errore $errors: La data di arrivo è antecedente ad adesso e manca la data di partenza";
                break;
              case 38:
                echo "Errore $errors: Manca il nome e la data di arrivo è antecedente ad adesso";
                break;
              case 44:
                echo "Errore $errors: La data di arrivo è antecedente ad adesso e la data di partenza è antecedente a quella di arrivo";
                break;
              case 55:
                // Nessun errore, non vi è alcun inserimento da fare
                break;
              default:
                if ($errors != 0){
                  echo "Error: $errors";
                }
            }*/
            if ($errors == 0){
              $sql = "INSERT INTO Codes (
                        Active,
                        Code,
                        Name,
                        ActiveFrom,
                        ActiveUntil,
                        LastEdited
                        )
                     VALUES (
                        '" . $Active_clean . "',
                        '" . $Code_clean . "',
                        '" . $Name_clean . "',
                        '" . $ActiveFrom_clean . "',
                        '" . $ActiveUntil_clean . "',
                        NOW()
                        )          
              
                      ";
              $result = mysqli_query($con, $sql);
            }
          }
        }
        //        MODIFICA CODICI ESISTENTI
        $newPOST = $_POST;
        if (isset($_POST['Active'])){
          $ActivePOST = $_POST['Active'];
          //echo "ActivePost: ";
          //print_r($ActivePOST);
          foreach ($ActivePOST as $value){
            $trovato = 0;
            $count = 0;
            foreach ($newPOST['ID'] as $valueID){
              if ($value == $valueID){
                //echo "TROVATO!!! $count";
                $isActive[$count] = 1;
              }else{
                //$newPOST['Active'][$count] = 0;
              }
              //echo "<BR>newPOST ID value: " . $newPOST['ID'][$count] . ", value: $value, count= $count<BR>";
              $count++;
            }   
          }
          $count2 = 0;
          while ($count2 < $count){
            if (!isset($isActive[$count2])){
              $isActive[$count2] = 0;
            }
            $count2++;
          }
        }
        $count = 0;
        $sql = "SELECT ID, Code, Name, ActiveFrom, ActiveUntil FROM Codes WHERE ActiveUntil > (CURRENT_TIMESTAMP() - INTERVAL 1 DAY)";
        $result = mysqli_query($con, $sql); 
        while ($row = mysqli_fetch_assoc($result)){
          $ID[$count] = $row['ID'];
          $Name[$count] = $row['Name'];
          $Code[$count] = $row['Code'];
          $ActiveFrom[$count] = $row['ActiveFrom'];
          $ActiveUntil[$count] = $row['ActiveUntil'];
          //$Active[$count] = $row['Active'];
          $count2 = 0;
          while (isset($newPOST['ID'][$count2])){       
            if ($newPOST['ID'][$count2] == $ID[$count]){
              //echo "$ID[$count], $Name[$count], $Code[$count], $ActiveFrom[$count], $ActiveUntil[$count], $Active[$count]<BR>";
              //echo $newPOST['ID'][$count2] . ", " . $newPOST['Name'][$count2] . ", " . $newPOST['Code'][$count2] . ", "        
              //     . $newPOST['ActiveFrom'][$count2] . ", " . $newPOST['ActiveUntil'][$count2] . ", "
              //     . $isActive[$count2] . "<BR>";
              if ($newPOST['Name'][$count2] != $Name[$count] || 
                  $newPOST['ActiveFrom'][$count2] != $ActiveFrom[$count] ||
                  $newPOST['ActiveUntil'][$count2] != $ActiveUntil[$count] ){
                    //echo "<BR>Devo aggiornare il record $ID[$count] (count=$count, count2=$count2)<BR>";
                    $sql = "UPDATE Codes SET
                                         Name='" . $newPOST['Name'][$count2] . "',
                                         Code='" . $newPOST['Code'][$count2] . "',
                                         ActiveFrom='" . $newPOST['ActiveFrom'][$count2] . "',
                                         ActiveUntil='" . $newPOST['ActiveUntil'][$count2] . "',
                                         LastEdited = NOW()
                            WHERE ID='$ID[$count]'";
                    if (!($result2 = mysqli_query($con,$sql))){
                      echo "Problema nell'aggiornamento dei dati. Errore: " . mysqli_error($con) . "<BR>";
                    }
              }
            }
            $count2++;
          }
          
          $count++; 
        }
        break;
      case "UpdateArduino":
        $response = file_get_contents("http://127.0.0.1:1880/UpdateCodes");
        if ($response == "OK!"){
          echo "Codici correttamente inviati alla tastiera!";
        }else{
          echo "Problema invio codici alla tastiera. Risposta ricevuta:$response";
        }
    }
  }
?>
<!DOCTYPE html>
<html>
<head>
	<title>CasaMama - Modifica codici</title>
	<meta content="width=device-width, initial-scale=1.0" name="viewport" >
	<link rel="stylesheet" href="css/bootstrap.min.css" type="text/css" />
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css" />
  
<style>
table.GeneratedTable {
  width: 96%;
  margin-left: 2%;
  margin-right: 2%;
  background-color: #ffffff;
  border-collapse: collapse;
  border-width: 2px;
  border-color: #b866ae;
  border-style: solid;
  color: #000000;
}

table.GeneratedTable td, table.GeneratedTable th {
  border-width: 2px;
  border-color: #b866ae;
  border-style: solid;
  padding: 3px;
}

table.GeneratedTable thead {
  background-color: #82d170;
}
</style>

  
</head>
<body>


<nav class="navbar navbar-default" role="navigation">
	<div class="container-fluid">
		<div class="navbar-header">
			<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navbar1">
				<span class="sr-only">Toggle navigation</span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>
			<a class="navbar-brand" href="index.php">CasaMama</a>
		</div>
		<div class="collapse navbar-collapse" id="navbar1">
			<ul class="nav navbar-nav navbar-right">
				<?php if (isset($_SESSION['usr_id'])) { ?>
				<li><p class="navbar-text">Signed in as <?php echo $_SESSION['usr_name']; ?></p></li>
				<li><a href="logout.php">Log Out</a></li>
				<?php } else { ?>
				<li><a href="login.php">Login</a></li>
				<li><a href="register.php">Sign Up</a></li>
				<?php } ?>
			</ul>
		</div>
	</div>
</nav>
<p>ATTENZIONE: controllo nome e date non ancora implementato per i nuovi inserimenti</p>
		<form role="form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" name="codesform">          
          <table class="GeneratedTable">
          <thead>
            <tr>
              <th width="30">Attivo</th>
              <th width="250">Nome</th>
              <th width="150">Codice</th>
              <th width="150">Attivo dal</th>
              <th width="150">Al</th>
            </tr>
          </thead>
          <tbody>
            <?PHP
              $sql = "SELECT ID, Code, Name, ActiveFrom, ActiveUntil, Active FROM Codes WHERE ActiveUntil > (CURRENT_TIMESTAMP() - INTERVAL 1 DAY)";
              $result = mysqli_query($con, $sql);
              while ($row = mysqli_fetch_assoc($result)){             
            ?>
              <tr>
                <td>
                  <input type="text" hidden name="ID[]" value="<?PHP echo $row['ID']; ?>"/>
                  <input type="checkbox" name="Active[]" value="<?PHP echo $row['ID']; ?>" <?PHP if ($row["Active"]){echo "checked";} ?> readonly/>
                </td>
                <td>
                  <input type="text" name="Name[]" value="<?PHP echo $row["Name"] ?>"/>                  
                </td>
                <td>
                  <input type="text" name="Code[]" value="<?PHP echo $row["Code"] ?>" readonly/>
                  </td>
                <td>
                  <input type="date" class="form-control floating-label" name="ActiveFrom[]" value="<?PHP echo $row["ActiveFrom"] ?>"/>
                  </td>
                <td>
                  <input type="date" class="form-control floating-label" name="ActiveUntil[]" value="<?PHP echo $row["ActiveUntil"] ?>"/>
                  </td>
              </tr>
            <?PHP              
            }
            ?>
              <tr>
                <td>
                  <input type="checkbox" name="Active_new"/>
                </td>
                <td>
                  <input type="text" name="Name_new" placeholder="Nome inquilino"/>                  
                </td>
                <td>
                  <input type="text" name="Code_new" value="<?PHP echo (rand(100000,999999)); ?>" readonly/>
                  </td>
                <td>
                  <input type="date" class="form-control floating-label" placeholder="Data Arrivo" name="ActiveFrom_new"/>
                  </td>
                <td>
                  <input type="date" class="form-control floating-label" placeholder="Data Partenza" name="ActiveUntil_new"/>
                  </td>
              </tr>
          <tbody>
        </table>  
		    <p></p>
        <!--<input type="submit" name="Command" value="Salva" class="btn btn-primary" />
        <input type="submit" name="Command" value="Forza aggiornamento" class="btn btn-primary" /> -->
        <button type="text" name="Command" value="Save" class="btn btn-primary">Aggiorna codici</button>
        <!--<button type="text" name="Command" value="UpdateArduino" class="btn btn-primary">Forza aggiornamento tastiera</button> -->
        <a href="/engine.php?action=updateCodes&forceUpdate=yes" class="btn btn-primary" role="button">Forza aggiornamento tastiera</a>
    </form>
    <?PHP
      /*if (isset($_POST['Edit'])){
        if ($_POST['Edit'] == "Save"){
          print_r($newPOST);    
        }
      }
      print_r($isActive);
      */
      /*
      while($isActive[$count]){       
        echo $isActive[$count];
        $count++;
      }
      */
      //print_r($_POST); 
    ?>
</body>
</html>

<?php
}else{
  header("Location: login.php");
}
?>