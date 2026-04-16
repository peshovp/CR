<?php
    // Always start this first
    session_start();

    if ( isset( $_SESSION['username'] ) ) {
        // Grab user data from the database using the user_id
        // Let them access the "logged in only" pages
    } else {
        // Redirect them to the login page
        header("Location: ./login.php");
    }

	//Librería necesaria para conectar con mongo
	require_once __DIR__ . "/vendor/autoload.php";

	include 'conf.php';
	
	$cliente=new MongoDB\Client($conf);

	//Conexión con mongo a las coleciones seleccionadas
	$rtcm_raw = $cliente->casterrep->rtcm_raw;
	$rover_connections = $cliente->casterrep->rover_connections;
    $streams = $cliente->casterrep->streams;
    $users = $cliente->casterrep->users;

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

    <title>Caster REP - Live Map</title>

    <!-- Bootstrap Core CSS -->
    <link href="./vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- MetisMenu CSS -->
    <link href="./vendor/metisMenu/metisMenu.min.css" rel="stylesheet">

    <!-- Noty JS -->
    <link href="./vendor/noty/lib/noty.css" rel="stylesheet">
    <link href="./vendor/noty/lib/themes/mint.css" rel="stylesheet">
    <script src="./vendor/noty/lib/noty.js" type="text/javascript"></script>

    <!-- Custom CSS -->
    <link href="./css/sb-admin-2.css" rel="stylesheet">

    <!-- Custom Fonts -->
    <link href="./vendor/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">
    
    <!-- OpenLayers CSS -->
    <link rel="stylesheet" href="./css/ol.css" type="text/css">
    <link rel="stylesheet" href="./css/ol3-layerswitcher.css" />

    <link rel="stylesheet" href="./css/map.css" />

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
                        <h3 class="page-header"><i class="fa fa-map fa-fw"></i> Live Map</h3>

                        <div class="panel panel-primary">
                            <div class="panel-heading">
                                <h4>Real-Time Visualization<h4>
                            </div>
                            <div class="panel-body">
                                <div id="map" class="map">
                                    <div id="popup" class="ol-popup">
                                        <div id="popup-content"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="panel-footer">
                                <p class="help-block" id=updatemap>Last refresh: <?php echo date('Y-m-d H:i:s');?></p>
                            </div>
                        </div>
                        <div class="alert alert-info alert-dismissable">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <i class="fa fa-info-circle"></i>  This data map will be refreshed every 30 seconds. Refresh this page is not needed.
                        </div>
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

    <!-- OpenLayers JS - LayerSwitcher-->
    <script type="text/javascript" src="./js/ol.js"></script>
    <script src="./js/ol3-layerswitcher.js"></script>
    <script src="./js/map.js"></script>

    <script>
        $("[data-toggle=popover]").popover({
            animation: true,
            html: true
        })
    </script>

</body>

</html>
