<?php
    require_once 'session_check.php';
    
	//Librería necesaria para conectar con mongo
	require_once __DIR__ . "/vendor/autoload.php";

	include 'conf.php';
	require_once 'csrf.php';
    $streams = $cliente->geomaxima->streams;    
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
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">

    <title>GeoMaxima — Edit Streams</title>

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
                                                <button type=\"button\" class=\"btn btn-info btn-sm btn-block editdel\" id=\"".htmlspecialchars($doc['_id'], ENT_QUOTES, 'UTF-8')."_edit\"><i class=\"fa fa-pencil\"></i> Edit</button>
                                                <button type=\"button\" class=\"btn btn-danger btn-sm btn-block\"
                                                data-container=\"body\" data-toggle=\"popover\" data-placement=\"bottom\" data-html=\"true\"
                                                data-content=\"<button type='button' class='btn btn-warning editdel' id='".htmlspecialchars($doc['_id'], ENT_QUOTES, 'UTF-8')."_delete'><i class='fa fa-exclamation-triangle'></i> SURE?</button>\">
                                                    <i class=\"fa fa-times\"></i> Delete
                                                </button>
                                                </td>";
                                            echo "<td>".htmlspecialchars($doc['mountpoint'], ENT_QUOTES, 'UTF-8')."</td>";
                                            echo "<td>".htmlspecialchars($doc['identifier'], ENT_QUOTES, 'UTF-8')."</td>";
                                            echo "<td>".htmlspecialchars(strlen($doc['format_detail']) > 12 ? substr($doc['format_detail'],0,12)."..." : $doc['format_detail'], ENT_QUOTES, 'UTF-8')."</td>";
                                            echo "<td>".htmlspecialchars($doc['carrier'], ENT_QUOTES, 'UTF-8')."</td>";
                                            echo "<td>".htmlspecialchars($doc['nav_system'], ENT_QUOTES, 'UTF-8')."</td>";
                                            echo "<td>".htmlspecialchars($doc['network'], ENT_QUOTES, 'UTF-8')."</td>";
                                            echo "<td>".htmlspecialchars($doc['country'], ENT_QUOTES, 'UTF-8')."</td>";
                                            echo "<td>".htmlspecialchars($doc['latitude'], ENT_QUOTES, 'UTF-8')."</td>";
                                            echo "<td>".htmlspecialchars($doc['longitude'], ENT_QUOTES, 'UTF-8')."</td>";
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
                                            echo "<td>".htmlspecialchars($doc['generator'], ENT_QUOTES, 'UTF-8')."</td>";
                                            echo "<td>".htmlspecialchars($doc['bitrate'], ENT_QUOTES, 'UTF-8')."</td>";
                                            echo "<td>".htmlspecialchars($doc['id_station'], ENT_QUOTES, 'UTF-8')."</td>";
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
