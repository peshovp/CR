<?php
    require_once 'session_check.php';
    
	//Librería necesaria para conectar con mongo
	require_once __DIR__ . "/vendor/autoload.php";

	include 'conf.php';
	include 'iso3166-1-a3.php';
	require_once 'csrf.php';
	
	$cliente=new MongoDB\Client($conf);

	//Conexión con mongo a las coleciones seleccionadas
    $users = $cliente->geomaxima->users;

    if  ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (count($_POST) > 0) {
            $username = isset($_POST['username'])?$_POST['username']:null;
            $password = isset($_POST['password'])?$_POST['password']:null;
            $password_hash = $password ? password_hash($password, PASSWORD_BCRYPT) : null;
            $firstname = isset($_POST['firstname'])?$_POST['firstname']:null;
            $lastname = isset($_POST['lastname'])?$_POST['lastname']:null;
            $organisation = isset($_POST['organisation'])?$_POST['organisation']:null;
            $email = isset($_POST['email'])?$_POST['email']:null;
            $telephone = isset($_POST['telephone'])?$_POST['telephone']:null;
            $city = isset($_POST['city'])?$_POST['city']:null;
            $country = isset($_POST['country'])?$_POST['country']:null;
            $zipcode = isset($_POST['zipcode'])?$_POST['zipcode']:null;
            $description = isset($_POST['description'])?$_POST['description']:null;
            $timestamp = time();
        }
    } else {
        $username = $password = $firstname = $lastname = $organisation = $email = $telephone = $city = $zipcode = $description = $country = null;
    }

    if ($username == null || $password == null || $email == null || $firstname == null) {
        $allrequired = false;
    } else {
        $allrequired  = true;
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="./favicon.ico">

    <title>GeoMaxima — New User</title>

    <!-- Bootstrap Core CSS -->
    <link href="./vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- MetisMenu CSS -->
    <link href="./vendor/metisMenu/metisMenu.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="./css/sb-admin-2.css" rel="stylesheet">

    <!-- Custom Fonts -->
    <link href="./vendor/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">

    <!-- Noty JS -->
    <link href="./vendor/noty/lib/noty.css" rel="stylesheet">
    <link href="./vendor/noty/lib/themes/mint.css" rel="stylesheet">
    <script src="./vendor/noty/lib/noty.js" type="text/javascript"></script>

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->

</head>

<body>
    <div id="wrapper">

        <!-- Navigation -->
        <?php require 'navigation.php';?>

        <!-- Page Content -->
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <h3 class="page-header"><i class="fa fa-plus-square-o fa-fw"></i> New user</h3>
                        <div class="col-lg-6 col-lg-offset-3">
                            <form role="form" action="./newuser.php" class="form-horizontal" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                
                            <div class="panel panel-primary">
                                <div class="panel-heading">
                                    <h3>Login caster data <button type="button" class="btn btn-warning btn-sm disabled">Required</button></h3>
                                </div>
                                <div class="panel-body">
                                    <p class="help-block">This data will be used to connect to Caster NTRIP REP</p>
                                </div>
                                <div class="panel-footer">
                                    <div class="container-fluid">
                                        <div class="row">
                                            <div class="col-lg-10 col-lg-offset-1">
                                                <div class="form-group input-group">
                                                    <span class="input-group-addon"><i class="fa fa-user"></i></span>
                                                    <input type="text" class="form-control" placeholder="Username" name="username" required value="<?php echo htmlspecialchars($username==null? "":$username, ENT_QUOTES, 'UTF-8'); ?>">
                                                    
                                                </div>
                                                <div class="form-group input-group">
                                                    <span class="input-group-addon"><i class="fa fa-key"></i></span>
                                                    <input type="password" class="form-control" placeholder="Password" name="password" required value="<?php echo htmlspecialchars($password==null? "":$password, ENT_QUOTES, 'UTF-8'); ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="panel panel-primary">
                                <div class="panel-heading">
                                    <h3>Additional user data</h3>
                                </div>
                                <div class="panel-body">
                                <p class="help-block">Some basic data about the user</p>
                                </div>
                                <div class="panel-footer">
                                    <div class="container-fluid">
                                        <div class="row">
                                            <div class="col-lg-10 col-lg-offset-1">
                                                <div class="form-group">
                                                    <label>First Name <button type="button" class="btn btn-warning btn-xs disabled">Required</button></label>
                                                    <input type="text" class="form-control" placeholder="First Name" name="firstname" required value="<?php echo htmlspecialchars($firstname==null? "":$firstname, ENT_QUOTES, 'UTF-8'); ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Last Name</label>
                                                    <input type="text" class="form-control" placeholder="Last Name" name="lastname" value="<?php echo htmlspecialchars($lastname==null? "":$lastname, ENT_QUOTES, 'UTF-8'); ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Organisation</label>
                                                    <input type="text" class="form-control" placeholder="Organisation" name="organisation" value="<?php echo htmlspecialchars($organisation==null? "":$organisation, ENT_QUOTES, 'UTF-8'); ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Email <button type="button" class="btn btn-warning btn-xs disabled">Required</button></label>
                                                    <input type="email" class="form-control" placeholder="Email" name="email" required value="<?php echo htmlspecialchars($email==null? "":$email, ENT_QUOTES, 'UTF-8'); ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Telephone</label>
                                                    <input type="text" class="form-control" placeholder="Telephone" name="telephone" value="<?php echo htmlspecialchars($telephone==null? "":$telephone, ENT_QUOTES, 'UTF-8'); ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>City</label>    
                                                    <input type="text" class="form-control" placeholder="City" name="city" value="<?php echo htmlspecialchars($city==null? "":$city, ENT_QUOTES, 'UTF-8'); ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Country (<k>ISO 3166-1-alpha3</k>)</label>
                                                    <select class="form-control" name="country">
                                                        <?php
                                                            foreach ($iso_array as $iso) {
                                                            echo "<option value=\"".htmlspecialchars($iso['code'], ENT_QUOTES, 'UTF-8')."\" ".($country==$iso['code'] ? "selected" : "").">".htmlspecialchars($iso['country'], ENT_QUOTES, 'UTF-8')." (".htmlspecialchars($iso['code'], ENT_QUOTES, 'UTF-8').")</option>";
                                                            } 
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>ZIP Code</label>
                                                    <input type="text" class="form-control" placeholder="ZIP code" name="zipcode" value="<?php echo htmlspecialchars($zipcode==null? "":$zipcode, ENT_QUOTES, 'UTF-8'); ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Description</label>
                                                    <input type="text" class="form-control" placeholder="Description" name="description" value="<?php echo htmlspecialchars($description==null? "":$description, ENT_QUOTES, 'UTF-8'); ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                                

                                

                    
                                <button type="submit" class="btn btn-primary btn-lg">Create user</button>
                                <button type="reset" class="btn btn-warning pull-right">Clear</button>
                                <br><br>
                            </form>
                        </div>
                        <!-- /.col-lg-6 .col-lg-offset-3 (nested) -->
                    </div>
                    <!-- /.col-lg-12 -->
                </div>
                <!-- /.row -->
                <!-- Footer -->
                <?php require 'footer.php';?>
            </div>
            <!-- /.container-fluid -->
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->

    <!-- jQuery -->
    <script src="./vendor/jquery/jquery.min.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="./vendor/bootstrap/js/bootstrap.min.js"></script>

    <!-- Metis Menu Plugin JavaScript -->
    <script src="./vendor/metisMenu/metisMenu.min.js"></script>

    <!-- Custom Theme JavaScript -->
    <script src="./js/sb-admin-2.js"></script>
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
        $("[data-toggle=popover]").popover({
            animation: true,
            html: true
        })
    </script>

</body>
</html>

<?php
    if  ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (count($_POST) > 0) {
            validateCSRFToken($_POST['csrf_token'] ?? null);
            try {

                $useremailvalid = $users->findOne(array('email' => $email));
                $usernamevalid = $users->findOne(array('username' => $username));

                if ($useremailvalid) {
                    echo"<script>showNotification(\"warning\", \"User email already exists\");</script>";
                    echo"<script>showNotification(\"error\", \"ERROR: User could not be created\");</script>";
                } else if ($usernamevalid) {
                    echo"<script>showNotification(\"warning\", \"Username already exists\");</script>";
                    echo"<script>showNotification(\"error\", \"ERROR: User could not be created\");</script>";
                } else {
                    if ($allrequired == false) {
                        echo"<script>showNotification(\"warning\", \"Some fields are required. Check them!\");</script>";
                    } else {
                        $users->insertOne(
                            [
                                'organisation' => $organisation,
                                'first_name' => $firstname,
                                'last_name' => $lastname,
                                'zip_code' => $zipcode,
                                'city' => $city,
                                'country' => $country,
                                'phone' => $telephone,
                                'email' => $email,
                                'description' => $description,
                                'username' => $username,
                                'password_hash' => $password_hash,
                                'valid_from' => $timestamp,
                                'type' => floatval('1'),
                                'active' => true
                            ]
                        );
                        echo"<script>showNotification(\"success\", \"User was created successfully\");</script>";
                        echo"<script>showNotification(\"information\", \"Loading administration panel\");setTimeout(function () {location.href='./index.php';}, 3000);</script>";
                    }
                }

            } catch (Exception $e) {
                echo"<script>showNotification(\"error\", \"ERROR: User could not be created\");</script>";
            }
        }
    }
?>


