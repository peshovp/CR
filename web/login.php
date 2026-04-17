<?php
  ini_set('session.cookie_httponly', 1);
  ini_set('session.cookie_secure', 0);  // Change to 1 when HTTPS is enabled
  ini_set('session.use_strict_mode', 1);
  ini_set('session.cookie_samesite', 'Strict');
  session_start();
  if ( isset( $_SESSION['username'] ) ) {
      // Grab user data from the database using the user_id
      // Let them access the "logged in only" pages
      header("Location: ./index.php");
  }

	//Librería necesaria para conectar con mongo
	require_once __DIR__ . "/vendor/autoload.php";
	include 'conf.php';
	require_once 'csrf.php';
	
	$cliente=new MongoDB\Client($conf);
	
	//Conexión con mongo a las coleciones seleccionadas
	$streams = $cliente->geomaxima->streams;
	$rover_connections = $cliente->geomaxima->rover_connections;
	$users = $cliente->geomaxima->users;
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="./favicon.ico">

    <title>GeoMaxima NTRIP Caster - Sign In</title>
    <!-- Bootstrap core CSS -->
    <link href="./vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <!-- Noty JS -->
    <link href="./vendor/noty/lib/noty.css" rel="stylesheet">
    <link href="./vendor/noty/lib/themes/mint.css" rel="stylesheet">
    <script src="./vendor/noty/lib/noty.js" type="text/javascript"></script>
    <!-- Custom styles for this template -->
    <link href="./css/signin.css" rel="stylesheet">
  </head>

  <body class="text-center">
      <?php if (isset($_GET['timeout']) && $_GET['timeout'] == '1'): ?>
      <div class="alert alert-warning" style="max-width: 380px; margin: 20px auto 0;">
          <strong><i class="fa fa-clock-o"></i></strong> Session expired. Please log in again.
      </div>
      <?php endif; ?>
      <form class="form-signin" action="login.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <img class=logo src="./img/logo200px.png" alt="Logo image" width="200" height="200">
        <img class=logoletter src="./img/logo_navbar_letter.png" alt="Logo letters"  width="300" height="60">
        <!--<h4 class="h5 mb-3 font-weight-normal">Please sign in</h4>-->
        <label for="inputUser" class="sr-only">Username</label>
        <input type="text" id="inputUser" class="form-control" placeholder="Username" name="username" required autofocus>
        <label for="inputPassword" class="sr-only">Password</label>
        <input type="password" id="inputPassword" class="form-control" placeholder="Password" name="password" required>
        <button class="btn btn-lg btn-primary btn-block btn-signin" type="submit">Sign in</button><br><br>
        <p class="text-muted font-weight-bold">GeoMaxima NTRIP Caster v5.0.0</p>
        <p class="text-muted">GNU GPL3 License</p>
        <img class="" src="./img/GPL3.png" alt="" width="110" height="45">
      </form>
      <script>
        function showNotification(type, text) {
          new Noty({
                  theme: 'mint',
                  type: type, /*alert, information, error, warning, notification, success*/
                  text: text,
                  timeout: 3000,
                  layout: "topCenter",
                }).show();
        };
      </script>
  </body>
</html>

<?php
	// Check correct username. If ok, store at cache
  if  ($_SERVER['REQUEST_METHOD'] === 'POST') {
      if (count($_POST)>0) {
          validateCSRFToken($_POST['csrf_token'] ?? null);
          $input_username = $_POST['username'];
          $input_password = $_POST['password'];

          $datos = $users->findOne(array('username' => $input_username, 'type' => 0));

          if ($datos) {
              $authenticated = false;

              // Primary: bcrypt verification
              if (isset($datos['password_hash']) && $datos['password_hash']) {
                  if (password_verify($input_password, $datos['password_hash'])) {
                      $authenticated = true;
                  }
              // Backward compatibility: legacy base64 token_auth
              } elseif (isset($datos['token_auth']) && $datos['token_auth']) {
                  $legacy_token = base64_encode($input_username . ":" . $input_password);
                  if ($datos['token_auth'] === $legacy_token) {
                      $authenticated = true;
                      // Migrate to bcrypt
                      $new_hash = password_hash($input_password, PASSWORD_BCRYPT);
                      $users->updateOne(
                          ['_id' => $datos['_id']],
                          ['$set' => ['password_hash' => $new_hash], '$unset' => ['token_auth' => '']]
                      );
                  }
              }

              if ($authenticated) {
                  session_regenerate_id(true);
                  $_SESSION['username'] = $datos['username'];
                  $_SESSION['last_activity'] = time();

                  echo"<script>showNotification(\"success\", \"Signed in successfully\");showNotification(\"information\", \"Loading administration panel\");setTimeout(function () {location.href='./index.php';}, 1000);</script>";
              } else {
                  echo"<script>showNotification(\"error\", \"ERROR: Credentials are not valid\");</script>";
              }
          } else {
              echo"<script>showNotification(\"error\", \"ERROR: Credentials are not valid\");</script>";
          }
      } else {
          echo"<script>showNotification(\"error\", \"Please, sign in properly\");</script>";
      } 
  }
?>
