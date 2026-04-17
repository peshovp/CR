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
	$rtcm_raw = $cliente->geomaxima->rtcm_raw;
	$rover_connections = $cliente->geomaxima->rover_connections;
    $streams = $cliente->geomaxima->streams;
    $users = $cliente->geomaxima->users;
    
    $connected_users = $rover_connections->find(['conn_status'=>true]);


    $num_online_streams = $rtcm_raw->count(array('timestamp' => array('$gt' => (time()-60) ) ));
    $num_online_users = $rover_connections->count(array('conn_status' => true));
    $num_users = $users->count();
    $num_streams = $streams->count();
    $num_streams_not_active = $streams->count(array('active' => false));
    $num_solution_networks = $streams->count(array('solution' => true));

    function ago($time) { 
        $timediff=time()-$time; 
    
        $days=intval($timediff/86400);
        $remain=$timediff%86400;
        $hours=intval($remain/3600);
        $remain=$remain%3600;
        $mins=intval($remain/60);
        $secs=$remain%60;
    
        if ($secs>=0) $timestring = "0m".$secs."s";
        if ($mins>0) $timestring = $mins."m".$secs."s";
        if ($hours>0) $timestring = $hours."h".$mins."m";
        if ($days>0) $timestring = $days."d".$hours."h";
    
        return $timestring; 
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
    <?php echo "<meta http-equiv=\"Refresh\" content=\"".htmlspecialchars($dashboard_rate, ENT_QUOTES, 'UTF-8')."\"; url=\"./index.php\">";?>
    
    <link rel="icon" href="./favicon.ico">

    <title>GeoMaxima — Dashboard</title>

    <!-- Bootstrap Core CSS -->
    <link href="./vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- MetisMenu CSS -->
    <link href="./vendor/metisMenu/metisMenu.min.css" rel="stylesheet">

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
                        <h3 class="page-header"><i class="fa fa-dashboard fa-fw"></i> Dashboard</h3>
                    </div>
                    <!-- /.col-lg-12 -->
                </div>
                <!-- /.row -->

                <div class="row">
                    <div class="col-lg-3 col-md-6">
                        <div class="panel panel-info">
                            <div class="panel-heading">
                                <div class="row">
                                    <div class="col-xs-3">
                                        <i class="fa fa-tasks fa-5x"></i>
                                    </div>
                                    <div class="col-xs-9 text-right">
                                        <div class="huge"><?php echo $num_streams;?></div>
                                        <div>Streams</div>
                                    </div>
                                </div>
                            </div>
                            <a href="./editstreams.php">
                                <div class="panel-footer">
                                    <span class="pull-left">View Details</span>
                                    <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                                    <div class="clearfix"></div>
                                </div>
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="panel panel-info">
                            <div class="panel-heading">
                                <div class="row">
                                    <div class="col-xs-3">
                                        <i class="fa fa-users fa-5x"></i>
                                    </div>
                                    <div class="col-xs-9 text-right">
                                        <div class="huge"><?php echo $num_users;?></div>
                                        <div>Registered users</div>
                                    </div>
                                </div>
                            </div>
                            <a href="./editusers.php">
                                <div class="panel-footer">
                                    <span class="pull-left">View Details</span>
                                    <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                                    <div class="clearfix"></div>
                                </div>
                            </a>
                        </div>
                    </div>


                    <div class="col-lg-3 col-md-6">
                        <?php 
                            if ($num_online_streams == 0) {
                                echo "<div class=\"panel panel-red\">";
                            } else {
                                echo "<div class=\"panel panel-green\">";
                            }
                        ?>
                            <div class="panel-heading">
                                <div class="row">
                                    <div class="col-xs-3">
                                        <i class="fa fa-rss fa-5x"></i>
                                    </div>
                                    <div class="col-xs-9 text-right">
                                        <div class="huge"><?php echo $num_online_streams;?></div>
                                        <div>Online streams (single stations)</div>
                                    </div>
                                </div>
                            </div>
                            <a href="./map.php">
                                <div class="panel-footer">
                                    <span class="pull-left">View on map</span>
                                    <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                                    <div class="clearfix"></div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <?php 
                            if ($num_online_users == 0) {
                                echo "<div class=\"panel panel-red\">";
                            } else {
                                echo "<div class=\"panel panel-green\">";
                            }
                        ?>
                            <div class="panel-heading">
                                <div class="row">
                                    <div class="col-xs-3">
                                        <i class="fa fa-user fa-5x"></i>
                                    </div>
                                    <div class="col-xs-9 text-right">
                                        <div class="huge"><?php echo $num_online_users;?></div>
                                        <div>Online users</div>
                                    </div>
                                </div>
                            </div>
                            <a href="./map.php">
                                <div class="panel-footer">
                                    <span class="pull-left">View on map</span>
                                    <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                                    <div class="clearfix"></div>
                                </div>
                            </a>
                        </div>
                    </div>

                </div>
                <!-- /.row -->

                <div class="alert alert-warning alert-dismissable">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <strong><i class="fa fa-exclamation-triangle"></i></strong>  NEAREST stream is not a single station then it could not be monitorized. It <u>does not count</u> as online stream here!
                </div>

                <div class="alert alert-info alert-dismissable">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <strong><i class="fa fa-info-circle"></i></strong>  The refresh rate for this page is defined in <u>conf.php</u> file under <i>$dashboard_rate</i> variable. It is defined as <?php echo htmlspecialchars($dashboard_rate, ENT_QUOTES, 'UTF-8');?> seconds
                </div>

                <div class="row">
                    <div class="col-lg-12">

                        <div class="panel panel-primary">
                            <div class="panel-heading">
                                <h4><i class="fa fa-tasks fa-fw"></i> Stream Status List &nbsp;[<?php echo date("Y-m-d H:i:s");?>]</h4>
                            </div>
                            <!-- /.panel-heading -->
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-lg-8">
                                    
                                        <div class="table-responsive">
                                            <table width="100%" cellspacing="0" class="table table-hover display nowrap" id="streams-table">
                                                <thead>
                                                    <tr>
                                                        <th>Status #</th>
                                                        <th>Status</th>
                                                        <th>Mountpoint</th>
                                                        <th>GPS sats.</th>
                                                        <th>GLO sats.</th>
                                                        <th>Last RTCM update</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                <?php
                                                    $mountpoint = $streams->find();
                                                    foreach ($mountpoint as $doc) {
                                                        $mount_raw = $rtcm_raw -> findOne(array('mountpoint'=>$doc['mountpoint']));
                                                        echo "<tr>";
                                                        if ($doc['solution'] == false) { 
                                                            
                                                            if ($mount_raw['timestamp'] < (time()-60)) {
                                                                
                                                                if ($doc['active']==true) {
                                                                    echo "<td>0</td>";
                                                                    echo "<td><button type=\"button\" class=\"btn btn-danger btn-xs disabled\">Not emmiting</button></td>";
                                                                    echo "<td>".htmlspecialchars($doc['mountpoint'], ENT_QUOTES, 'UTF-8')."</td>";
                                                                    echo "<td>0</td>";
                                                                    echo "<td>0</td>";
                                                                    echo "<td>".date("d F Y H:i:s", $mount_raw['timestamp'])."</td>";
                                                                } else {
                                                                    echo "<td>3</td>";
                                                                    echo "<td><button type=\"button\" class=\"btn btn-warning btn-xs disabled\">Inactive</button></td>";
                                                                    echo "<td>".htmlspecialchars($doc['mountpoint'], ENT_QUOTES, 'UTF-8')."</td>";
                                                                    echo "<td>-</td>";
                                                                    echo "<td>-</td>";
                                                                    echo "<td>".date("d F Y H:i:s", $mount_raw['timestamp'])."</td>";
                                                                }
                                                            } else {
                                                                if ($doc['active']==true) {
                                                                    echo "<td>1</td>";
                                                                    echo "<td><button type=\"button\" class=\"btn btn-success btn-xs disabled\">Emmiting</button></td>";
                                                                    echo "<td>".htmlspecialchars($doc['mountpoint'], ENT_QUOTES, 'UTF-8')."</td>";
                                                                    echo "<td>".htmlspecialchars($mount_raw['n_gps'], ENT_QUOTES, 'UTF-8')."</td>";
                                                                    echo "<td>".htmlspecialchars($mount_raw['n_glo'], ENT_QUOTES, 'UTF-8')."</td>";
                                                                    echo "<td>".date("d F Y H:i:s", $mount_raw['timestamp'])."</td>";
                                                                } else {
                                                                    echo "<td>3</td>";
                                                                    echo "<td><button type=\"button\" class=\"btn btn-warning btn-xs disabled\">Inactive</button></td>";
                                                                    echo "<td>".htmlspecialchars($doc['mountpoint'], ENT_QUOTES, 'UTF-8')."</td>";
                                                                    echo "<td>-</td>";
                                                                    echo "<td>-</td>";
                                                                    echo "<td>".date("d F Y H:i:s", $mount_raw['timestamp'])."</td>";
                                                                }
                                                            }
                                                        } else {
                                                            echo "<td>2</td>";
                                                            echo "<td><button type=\"button\" class=\"btn btn-primary btn-xs disabled\">Solution</button></td>";
                                                            echo "<td>".htmlspecialchars($doc['mountpoint'], ENT_QUOTES, 'UTF-8')."</td>";
                                                            echo "<td>-</td>";
                                                            echo "<td>-</td>";
                                                            echo "<td>-</td>"; 
                                                        }
                                                        echo "</tr>";
                                                    }?>    
                                                </tbody>
                                            </table>
                                        </div>
                                        <!-- /.table-responsive -->
                                    </div>
                                    <!-- /.col-lg-8 (nested) -->
                                    <div class="col-lg-4">
                                        <div class="panel panel-default">
                                            <div class="panel-heading">RTCM status</div>
                                            <div class="panel-body">
                                                <div id="morris-donut-chart"></div>
                                            </div>
                                            <?php 
                                            if ($num_streams_not_active > 0) {
                                                echo "<div class=\"panel panel-default\">
                                                    <div class=\"panel-heading\">
                                                        <div class=\"row\">
                                                            <div class=\"col-xs-3\">
                                                                <i class=\"fa fa-tasks fa-5x\"></i>
                                                            </div>
                                                            <div class=\"col-xs-9 text-right\">
                                                                <div class=\"huge\">".$num_streams_not_active."</div>
                                                                <div>Not active stream/s</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>";
                                            }
                                            ?>
                                            <!-- /.panel-body -->
                                        </div>

                                    </div>
                                    <!-- /.col-lg-4 (nested) -->
                                </div>
                            </div>
                            <!-- /.panel-body -->
                        </div>
                        <!-- /.panel -->
                        
                    </div>
                    <!-- /.col-lg-12 -->
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="panel panel-primary">
                            <div class="panel-heading">
                                <h4><i class="fa fa-flash fa-fw"></i> Online User List &nbsp;[<?php echo date("Y-m-d H:i:s");?>]</h4>
                            </div>
                            <!-- /.panel-heading -->
                            <div class="panel-body">

                                <div class="table-responsive">
                                    <table width="100%" cellspacing="0" class="table table-hover display nowrap" id="users-table">
                                        <thead>
                                            <tr>
                                                <th>Rover</th>
                                                <th>Mountpoint</th>
                                                <th>Sats. used</th>
                                                <th>Quality</th>
                                                <th>Latency</th>
                                                <th>Nearest Station</th>
                                                <th>Login</th>
                                                <th>Last update</th>
                                                <th>NMEA</th>
                                                <th>IP</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                            $user_conndata = $rover_connections->find(['conn_status'=>true]);
                                            foreach ($user_conndata as $dat) {
                                                echo "<tr>";
                                                echo "<td>".htmlspecialchars($dat['username'], ENT_QUOTES, 'UTF-8')."</td>";
                                                echo "<td>".htmlspecialchars($dat['conn_path'], ENT_QUOTES, 'UTF-8')."</td>";
                                                $sats = isset($dat['sat_used']) ? $dat['sat_used'] : null;
                                                if ($sats == null) {
                                                    echo "<td><span class=\"badge badge-pill badge-secondary\">N/A</span></td>";
                                                } else {
                                                    echo "<td>".htmlspecialchars($sats, ENT_QUOTES, 'UTF-8')."</td>";
                                                }
                                                $quality = isset($dat['quality']) ? $dat['quality'] : null;
                                                if ($quality == null) {
                                                    echo "<td><span class=\"badge badge-pill badge-secondary\">Not available</span></td>";
                                                } else {
                                                    echo "<td>".htmlspecialchars($quality, ENT_QUOTES, 'UTF-8')."</td>";
                                                }
                                                $latency = isset($dat['latency']) ? $dat['latency'] : null;
                                                if ($latency == null) {
                                                    echo "<td><span class=\"badge badge-pill badge-secondary\">Not available</span></td>";
                                                } else {
                                                    echo "<td>".htmlspecialchars($latency, ENT_QUOTES, 'UTF-8')."</td>";
                                                }
                                                $dist_near = isset($dat['distance_near']) ? $dat['distance_near'] : null;
                                                $ref_station = isset($dat['ref_station']) ? $dat['ref_station'] : null;
                                                if ($ref_station == null || $dist_near == null) {
                                                    echo "<td><span class=\"badge badge-pill badge-secondary\">Not available</span></td>";
                                                } else {
                                                    echo "<td>".htmlspecialchars($ref_station, ENT_QUOTES, 'UTF-8')."(".htmlspecialchars($dist_near, ENT_QUOTES, 'UTF-8')." Km)</td>";
                                                }
                                                echo "<td>".date("d F Y H:i:s", $dat['login_time'])."</td>";
                                                echo "<td>".ago($dat['last_update'])."</td>";
                                                $nmea = isset($dat['nmea_msg']) ? $dat['nmea_msg'] : null;
                                                if ($nmea == null) {
                                                    echo "<td><span class=\"badge badge-pill badge-secondary\">N/A</span></td>";
                                                } else {
                                                    echo "<td><span class=\"badge badge-pill badge-info\">OK</span></td>";
                                                }
                                                echo "<td>".htmlspecialchars($dat['conn_ip'][0], ENT_QUOTES, 'UTF-8').":".htmlspecialchars($dat['conn_ip'][1], ENT_QUOTES, 'UTF-8')."</td>";
                                                echo "</tr>";
                                            }
                                        ?>    
                                        </tbody>
                                    </table>
                                </div>
                                <!-- /.table-responsive -->
                            </div>
                        </div>
                    </div>
                    <!-- /.col-lg-12 -->
                </div>

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

    <!-- Morris Charts JavaScript -->
    <script src="./vendor/raphael/raphael-min.js"></script>
    <script src="./vendor/morrisjs/morris.min.js"></script>

    <!-- DataTables JavaScript -->
    <script src="./vendor/datatables/js/jquery.dataTables.min.js"></script>
    <script src="./vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
    <script src="./vendor/datatables-responsive/dataTables.responsive.js"></script>

    <script>
    <?php 
        if ($num_online_streams == 0) {
            $notemitting = "{
                label: \"Not emmiting RTCM\",
                value: ".($num_streams)."}";
            $emitting = "";
        } else {
            $notemitting = "{
                label: \"Not emmiting RTCM\",
                value: ".($num_streams_not_active+($num_streams-$num_online_streams-$num_solution_networks))."}";
            $emitting = "{
                label: \"Emmiting RTCM\",
                value: ".($num_online_streams+$num_solution_networks)."}";
        }
        echo "Morris.Donut({element: 'morris-donut-chart',
            data: [".$notemitting.",".$emitting."],
            colors: [\"#dc3545 \", \"#28a745\", \"#adb5bd\"],
            resize: true

        });";


    ?>
        $("[data-toggle=popover]").popover({
            animation: true,
            html: true
        })
        $(document).ready(function() {
    
        $('#users-table').DataTable({
            scrollCollapse: true
        });
        $.fn.dataTable.tables( {visible: true, api: true} ).columns.adjust();

        $('#streams-table').DataTable({
            scrollCollapse: true,
            columnDefs: [{
                "targets": [ 0 ],
                "visible": false,
                "searchable": false
            }],
            order: [[ 0, "asc" ]]
        });
        $.fn.dataTable.tables( {visible: true, api: true} ).columns.adjust();
    });
    </script>
</body>

</html>
