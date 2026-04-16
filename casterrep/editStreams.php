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
    $streams = $cliente->casterrep->streams;    
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

    <title>Caster REP - Edit Streams</title>

    <!-- Bootstrap Core CSS -->
    <link href="./vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- MetisMenu CSS -->
    <link href="./vendor/metisMenu/metisMenu.min.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link href="./vendor/datatables-plugins/dataTables.bootstrap.css" rel="stylesheet">

    <!-- DataTables Responsive CSS -->
    <link href="./vendor/datatables-responsive/dataTables.responsive.css" rel="stylesheet">

    <!-- Noty JS -->
    <link href="./vendor/noty/lib/noty.css" rel="stylesheet">
    <link href="./vendor/noty/lib/themes/mint.css" rel="stylesheet">
    <script src="./vendor/noty/lib/noty.js" type="text/javascript"></script>

    <!-- Custom CSS -->
    <link href="./css/sb-admin-2.css" rel="stylesheet">

    <!-- Custom Fonts -->
    <link href="./vendor/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">

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
                        <h3 class="page-header"><i class="fa fa-edit fa-fw"></i> Edit streams</h3>

                        <div class="table-responsive">                      
                            <table width="100%" cellspacing="0" class="table table-hover display nowrap" id="streams-table">
                                <thead>
                                    <tr>
                                        <th class="active">Actions</th>
                                        <th>Mountpoint</th>
                                        <th>Identifier</th>
                                        <th>Format Detail</th>
                                        <th>Carrier</th>
                                        <th>Nav. System</th>
                                        <th>Network</th>
                                        <th>Country Code</th>
                                        <th>Lat.</th>
                                        <th>Long.</th>
                                        <th>NMEA</th>
                                        <th>Solution</th>
                                        <th>Generator</th>
                                        <th>Bitrate</th>
                                        <th>ID Station</th>
                                        <th>Encoder Passwd</th>
                                        <th>Active</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                        $mountpoint = $streams->find();
                                        foreach ($mountpoint as $doc) {
                                            echo "<tr>";
                                            echo "<td class=\"active\">
                                                <button type=\"button\" class=\"btn btn-info btn-sm btn-block editdel\" id=\"".$doc['_id']."_edit\"><i class=\"fa fa-pencil\"></i> Edit</button>
                                                <button type=\"button\" class=\"btn btn-danger btn-sm btn-block\"
                                                data-container=\"body\" data-toggle=\"popover\" data-placement=\"bottom\" data-html=\"true\"
                                                data-content=\"<button type='button' class='btn btn-warning editdel' id='".$doc['_id']."_delete'><i class='fa fa-exclamation-triangle'></i> SURE?</button>\">
                                                    <i class=\"fa fa-times\"></i> Delete
                                                </button>
                                                </td>";
                                            echo "<td>".$doc['mountpoint']."</td>";
                                            echo "<td>".$doc['identifier']."</td>";
                                            echo "<td>".(strlen($doc['format_detail']) > 12 ? substr($doc['format_detail'],0,12)."..." : $doc['format_detail'])."</td>";
                                            echo "<td>".$doc['carrier']."</td>";
                                            echo "<td>".$doc['nav_system']."</td>";
                                            echo "<td>".$doc['network']."</td>";
                                            echo "<td>".$doc['country']."</td>";
                                            echo "<td>".$doc['latitude']."</td>";
                                            echo "<td>".$doc['longitude']."</td>";
                                            if ($doc['nmea'] == "1") {
                                                echo "<td><button type=\"button\" class=\"btn btn-success btn-xs disabled\">Need<br>GGA</button></td>";
                                            } else {
                                                echo "<td><button type=\"button\" class=\"btn btn-danger btn-xs disabled\">Don't<br>need<br>GGA</button></td>";
                                            }
                                            if ($doc['solution'] == true) { 
                                                echo "<td><button type=\"button\" class=\"btn btn-info btn-xs disabled\">Network</button></td>";
                                            } else {
                                                echo "<td><button type=\"button\" class=\"btn btn-warning btn-xs disabled\">Single<br>Base</button></td>";
                                            }
                                            echo "<td>".$doc['generator']."</td>";
                                            echo "<td>".$doc['bitrate']."</td>";
                                            echo "<td>".$doc['id_station']."</td>";
                                            $string = str_split($doc['encoder_pwd']);
                                            echo "<td>";
                                            foreach ($string as $char) {
                                                echo "*";
                                            }
                                            echo "</td>";
                                            if ($doc['active'] == true) {
                                                echo "<td><button type=\"button\" class=\"btn btn-success btn-xs disabled\">Active</button></td>";
                                            } else {
                                                echo "<td><button type=\"button\" class=\"btn btn-danger btn-xs disabled\">Inactive</button></td>";
                                            }
                                            echo "</tr>";
                                        }
                                    ?>                                   
                                </tbody>
                            </table>

                        </div>
                        <!-- /.table-responsive -->
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

    <!-- DataTables JavaScript -->
    <script src="./vendor/datatables/js/jquery.dataTables.min.js"></script>
    <script src="./vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
    <script src="./vendor/datatables-responsive/dataTables.responsive.js"></script>

    <!-- Custom Theme JavaScript -->
    <script src="./js/sb-admin-2.js"></script>

    <!-- Custom JavaScript -->
    <script src="./js/editstreams.js"></script>

</body>

</html>
