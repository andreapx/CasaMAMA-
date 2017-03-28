<?php
session_start();
include_once 'config.php';

if(isset($_SESSION['usr_id'])!="") {
  if (isset($_POST['login'])) {
    echo "Hi!";    
  }

//if (isset($_POST['login'])) {
?>
<!DOCTYPE html>
<html>
<head>
	<title>Modifica codici</title>
	<meta content="width=device-width, initial-scale=1.0" name="viewport" >
	<link rel="stylesheet" href="css/bootstrap.min.css" type="text/css" />
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css" />
  


  
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

<div class="container-fluid">
	<div class="row">
		<form role="form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" name="codesform">
      <fieldset>
        <div class="col-sm-10 col-md-offset-1" style="background-color:lavender;"><legend>Codici</legend>
          
            <div class="row">
              <div class="col-sm-1 col-md-offset-1" style="background-color:lightcyan;">
                <label for="name">Attivo</label>
              </div>
              <div class="col-sm-2" style="background-color:lavenderblush;">
                <label for="name">Nome</label>
              </div>
              <div class="col-sm-2" style="background-color:lightgray;">
                <label for="name">Codice</label>
              </div>
              <div class="col-sm-2" style="background-color:lightgray;">
                <label for="name">Attivo dal</label>
              </div>
              <div class="col-sm-2" style="background-color:lightgray;">
                <label for="name">Al</label>
              </div>
            </div>
            <?PHP
            $sql = "SELECT ID, Code, Name, ActiveFrom, ActiveUntil, Active FROM Codes WHERE ActiveUntil > (CURDATE() -1)";
            $result = mysqli_query($con, $sql);
            while ($row = mysqli_fetch_assoc($result)){
              ?>
            <div class="row">
              <div class="col-sm-1 col-md-offset-1" style="background-color:lightcyan;">
                <input type="checkbox" name="Active" value="<?PHP echo $row["ID"] ?>"<?PHP if ($row["Active"]){echo "checked";} ?> required class="form-control" />
              </div>
              <div class="col-sm-2" style="background-color:lavenderblush;">
                <input type="text" name="Name" value="<?PHP echo $row["Name"] ?>" required class="form-control" />
              </div>
              <div class="col-sm-2" style="background-color:lightgray;">
                <input type="text" name="Code" value="<?PHP echo $row["Code"] ?>" required class="form-control" />
              </div>
              <div class="col-sm-2" style="background-color:lightgray;">
                <input type="date" class="form-control floating-label" placeholder="Begin Date Time" name="ActiveFrom" value="<?PHP echo $row["ActiveFrom"] ?>" required class="form-control" />
              </div>
              <div class="col-sm-2" style="background-color:lightgray;">
                <input type="date" class="form-control floating-label" placeholder="Begin Date Time"name="ActiveUntil" value="<?PHP echo $row["ActiveUntil"] ?>" required class="form-control" />
              </div>
            </div>  
              <?PHP              
            }
            ?>
            
            <div class="row">
              <div class="col-sm-1 col-md-offset-1" style="background-color:lightcyan;"><label for="name"></label></div>
              <div class="col-sm-2" style="background-color:lavenderblush;"><label for="name"></label></div>
              <div class="col-sm-2" style="background-color:lightgray;"><label for="name"></label></div>
              <div class="col-sm-2" style="background-color:lightgray;"><label for="name"></label></div>
              <div class="col-sm-2" style="background-color:lightgray;"><label for="name"></label></div>
            </div>
            <div class="row">
              <div class="form-group">
                <div class="col-sm-1 col-md-offset-1"><input type="submit" name="Edit" value="Edit" class="btn btn-primary" /></div>
              </div>
            </div>
          </div>
               
			</fieldset>
		</form>
			<span class="text-danger"><?php if (isset($errormsg)) { echo $errormsg; } ?></span>
	</div>
  
<div class="row">

	</div>
</div>        

<!-- cdn for modernizr, if you haven't included it already -->
<script src="http://cdn.jsdelivr.net/webshim/1.12.4/extras/modernizr-custom.js"></script>
<!-- polyfiller file to detect and load polyfills -->
<script src="http://cdn.jsdelivr.net/webshim/1.12.4/polyfiller.js"></script>
<script>
  webshims.setOptions('waitReady', false);
  webshims.setOptions('forms-ext', {types: 'date'});
  webshims.polyfill('forms forms-ext');
</script>

<script src="js/jquery-1.10.2.js"></script>
<script src="js/bootstrap.min.js"></script>

</body>
</html>

<?PHP
}else{
  header("Location: login.php");
}

?>