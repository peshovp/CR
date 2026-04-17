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
	include 'iso3166-1-a3.php';
	
	$cliente=new MongoDB\Client($conf);

    $streams = $cliente->geomaxima->streams;

    if  ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (count($_POST) > 0) {
            $id = isset($_POST['id'])?$_POST['id']:null;
            $mountpoint = isset($_POST['mountpoint'])?$_POST['mountpoint']:null;
            $identifier = isset($_POST['identifier'])?$_POST['identifier']:null;
            $formatdetail = isset($_POST['formatdetail'])?$_POST['formatdetail']:null;
            $navsystem = isset($_POST['navsystem'])?$_POST['navsystem']:null;
            $network = isset($_POST['network'])?$_POST['network']:null;
            $country = isset($_POST['country'])?$_POST['country']:null;
            $latitude = isset($_POST['latitude'])?$_POST['latitude']:null;
            $longitude = isset($_POST['longitude'])?$_POST['longitude']:null;
            $generator = isset($_POST['generator'])?$_POST['generator']:null;
            $bitrate = isset($_POST['bitrate'])?$_POST['bitrate']:null;
            $misc = isset($_POST['misc'])?$_POST['misc']:null;
            $carrier = isset($_POST['carrier'])?$_POST['carrier']:null;
            $nmea = isset($_POST['nmea'])?$_POST['nmea']:null;
            $solution = isset($_POST['solution'])?$_POST['solution']:null;
            $idstation = isset($_POST['idstation'])?$_POST['idstation']:null;
            $encoder = isset($_POST['encoder'])?$_POST['encoder']:null;
            $active = isset($_POST['active'])?$_POST['active']:null;
        }
    } else if  ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (count($_GET) == 1) {
            try {
                $oid = new MongoDB\BSON\ObjectID($_GET['idstream']);
                $stream = $streams -> findOne(['_id' => $oid ]);
            } catch (Exception $e) {
                $stream = null;
            }
            
            if ($stream) {
                $id = $stream['_id'];
                $mountpoint = $stream['mountpoint'];
                $identifier = $stream['identifier'];
                $formatdetail = $stream['format_detail'];
                $navsystem = $stream['nav_system'];
                $network = $stream['network'];
                $country = $stream['country'];
                $latitude = $stream['latitude'];
                $longitude = $stream['longitude'];
                $generator = $stream['generator'];
                $bitrate = $stream['bitrate'];
                $misc = $stream['misc'];
                $carrier = $stream['carrier'];
                $nmea = $stream['nmea'];
                $solution = $stream['solution'];
                $idstation = $stream['id_station'];
                $encoder = $stream['encoder_pwd'];
                $active = $stream['active'];

            } else {
                $id = $mountpoint = $identifier = $formatdetail = $navsystem = $network = $country = $latitude = $idstation = null;
                $longitude = $generator = $bitrate = $misc = $carrier = $nmea = $solution = $encoder = $active = null;
            }
        }
    } else {
        $id = $mountpoint = $identifier = $formatdetail = $navsystem = $network = $country = $latitude = $idstation = null;
        $longitude = $generator = $bitrate = $misc = $carrier = $nmea = $solution = $encoder = $active = null;
    }

    if ($mountpoint == null || $identifier == null || $encoder == null) {
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

    <title>GeoMaxima — Edit Stream</title>

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

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->

</head>

<body>

    <?php if (!$stream) echo "<script type=\"text/javascript\">location.href = './index.php';</script>";?>

    <div id="wrapper">

        <!-- Navigation -->
        <?php require 'navigation.php';?>

        <!-- Page Content -->
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <h3 class="page-header"><i class="fa fa-pencil fa-fw"></i> Edit stream: <spam class="text-info"><?php echo $mountpoint;?></spam></h3>

                        <div class="col-lg-8 col-lg-offset-2">
                            <form role="form" action="./editStreamPage.php" class="form-horizontal" method="POST">
                                <div class="form-group">
                                    <input type="text" class="form-control" placeholder="" name="id" value="<?php echo $id==null? "":$id; ?>" style="display:none">
                                </div>                               
                                <div class="panel panel-primary">
                                    <div class="panel-heading">
                                        <h3>Sourcetable data</h3>
                                    </div>
                                    <div class="panel-body">
                                        <p class="help-block">This data will be used to configure the mountpoint information which will be returned on the sourcetable</p>
                                    </div>
                                    <div class="panel-footer">
                                        <div class="container-fluid">
                                            <div class="row">
                                                <div class="col-lg-10 col-lg-offset-1">
                                                    <div class="form-group">
                                                        <label>Mountpoint</label> <button type="button" class="btn btn-warning btn-xs disabled">Required</button>
                                                        <input type="text" class="form-control" placeholder="Mountpoint name" name="mountpoint" value="<?php echo $mountpoint==null? "":$mountpoint; ?>" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Identifier</label> <button type="button" class="btn btn-warning btn-xs disabled">Required</button>
                                                        <input type="text" class="form-control" placeholder="Identifier name" name="identifier" value="<?php echo $identifier==null? "":$identifier; ?>" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Format Detail</label>
                                                        <input type="text" class="form-control" placeholder="RTCM 3.1 Format Detail" name="formatdetail" value="<?php echo $formatdetail==null? "":$formatdetail; ?>">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Carrier</label>
                                                        <div class="radio">
                                                            <label>
                                                                <input type="radio" name="carrier" id="carrier0" value="0" <?php echo $carrier=="0"? "checked":""; ?>>0 = No
                                                            </label>
                                                        </div>
                                                        <div class="radio">
                                                            <label>
                                                                <input type="radio" name="carrier" id="carrier1" value="1" <?php echo $carrier=="1"? "checked":""; ?>>1 = Yes, L1
                                                            </label>
                                                        </div>
                                                        <div class="radio">
                                                            <label>
                                                                <input type="radio" name="carrier" id="carrier2" value="2" <?php echo $carrier=="2"? "checked":($carrier==null?"checked":""); ?>>2 = Yes, L1&L2
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Navigation System(s)</label>
                                                        <input type="navsystem" class="form-control" placeholder="Navigation System, eg GPS, GPS+GLO..." name="navsystem" value="<?php echo $navsystem==null? "":$navsystem; ?>">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Network</label>
                                                        <input type="text" class="form-control" placeholder="Network" name="network" value="<?php echo $network==null? "":$network; ?>">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Country Code (<k>ISO 3166-1-alpha3</k>)</label>
                                                        <select class="form-control" name="country">
                                                            <?php
                                                                foreach ($iso_array as $iso) {
                                                                    echo "<option value=".$iso['code']." ".($country==$iso['code'] ? "selected" : "").">".$iso['country']." (".$iso['code'].")</option>";
                                                                } 
                                                            ?>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Latitude</label>    
                                                        <input type="text" class="form-control" placeholder="Latitude" name="latitude" value="<?php echo $latitude==null? "":$latitude; ?>">
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label>Longitude</label>
                                                        <input type="text" class="form-control" placeholder="Longitude" name="longitude" value="<?php echo $longitude==null? "":$longitude; ?>">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>NMEA</label>
                                                        <div class="radio">
                                                            <label>
                                                                <input type="radio" name="nmea" id="nmea0" value="0" <?php echo $nmea=="0"? "checked":($nmea==null?"checked":""); ?>>0 =  Client must not send NMEA GGA message with approximate position to Caster
                                                            </label>
                                                        </div>
                                                        <div class="radio">
                                                            <label>
                                                                <input type="radio" name="nmea" id="nmea1" value="1" <?php echo $nmea=="1"? "checked":""; ?>>1 =  Client must send NMEA GGA message with approximate position to Caster
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Solution</label>
                                                        <div class="radio">
                                                            <label>
                                                                <input type="radio" name="solution" id="solution0" value="0" <?php echo $solution=="0"? "checked":($solution==null?"checked":""); ?>>0 = Single Base
                                                            </label>
                                                        </div>
                                                        <div class="radio">
                                                            <label>
                                                                <input type="radio" name="solution" id="solution1" value="1" <?php echo $solution=="1"? "checked":""; ?>>1 = Network
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Generator</label>
                                                        <input type="text" class="form-control" placeholder="Generator" name="generator" value="<?php echo $generator==null? "":$generator; ?>">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Bitrate</label>
                                                        <input type="text" class="form-control" placeholder="Bitrate" name="bitrate" value="<?php echo $bitrate==null? "":$bitrate; ?>">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Miscellaneous information</label>
                                                        <input type="text" class="form-control" placeholder="Misc. Info, eg. null antenna" name="misc" value="<?php echo $misc==null? "":$misc; ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                               
                                <div class="panel panel-primary">
                                    <div class="panel-heading">
                                        <h3>Station NTRIP Server</h3>
                                    </div>
                                    <div class="panel-body">
                                        <p class="help-block">This data will be used to configure the station with an NTRIP Server to send data to Caster NTRIP REP.</p>
                                        <div class="alert alert-info">
                                            When an station wants transmit RTMC data to a Caster NTRIP, the station send a message type like "SOURCE password /Mountpoint" to login in Caster NTRIP. 
                                            Then Caster will know if it is a valid RTCM source.
                                        </div>
                                    </div>
                                    <div class="panel-footer">
                                        <div class="container-fluid">
                                            <div class="row">
                                                <div class="col-lg-10 col-lg-offset-1">
                                                    <div class="form-group">
                                                        <label>ID Station</label> <button type="button" class="btn btn-warning btn-xs disabled">Required</button>
                                                        <input type="" class="form-control" placeholder="ID Station Number" pattern="[0-9]{1,6}" name="idstation" value="<?php echo $idstation==null? "":$idstation; ?>" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Encoder password</label> <button type="button" class="btn btn-warning btn-xs disabled">Required</button>
                                                        <input type="text" class="form-control" placeholder="Encoder Password" name="encoder" value="<?php echo $encoder==null? "":$encoder; ?>" required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="panel panel-primary">
                                    <div class="panel-heading">
                                        <h2>Stream status.</h2>
                                    </div>
                                    <div class="panel-body">
                                        <p class="help-block">You could choose to give an online/offline to the stream in you Caster NTRIP REP.</p>
                                    </div>
                                    <div class="panel-footer">
                                        <div class="container-fluid">    
                                            <div class="row">
                                                <div class="col-lg-10 col-lg-offset-1">
                                                    <div class="form-group">
                                                        <label>Stream status</label>
                                                        <div class="radio">
                                                            <label>
                                                                <input type="radio" name="active" id="active0" value=1 <?php echo $active=='1'? "checked":($active==null?"checked":""); ?>> <button type="button" class="btn btn-success btn-xs disabled">Activate this stream</button>
                                                        </div>
                                                        <div class="radio">
                                                            <label>
                                                                <input type="radio" name="active" id="active1" value=0 <?php echo $active=='0'? "checked":""; ?>> <button type="button" class="btn btn-danger btn-xs disabled">Deactivate this stream</button>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                    
                                <button type="submit" class="btn btn-primary btn-lg"><i class="fa fa-save"></i> Save</button>
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
            try {

                $mountpointvalid = $streams -> findOne(array('mountpoint' => $mountpoint, '_id' => array('$ne' => new MongoDB\BSON\ObjectID($id)) ));

                if ($mountpointvalid) {
                    echo"<script>showNotification(\"warning\", \"Mountpoint already exists\");</script>";
                    echo"<script>showNotification(\"error\", \"ERROR: Stream could not be created\");</script>";
                } else {
                    if ($allrequired == false) {
                        echo"<script>showNotification(\"warning\", \"Some fields are required. Check them!\");</script>";
                    } else {
                        $modify = $streams -> updateOne(
                            ['_id' => new MongoDB\BSON\ObjectID($id)],
                            ['$set'=>[
                                'id_station' => intval($idstation),
                                'mountpoint' => $mountpoint,
                                'identifier' => $identifier,
                                'format_detail' => $formatdetail,
                                'carrier' => intval($carrier),
                                'nav_system' => $navsystem,
                                'network' => $network,
                                'country' => $country,
                                'latitude' => floatval($latitude),
                                'longitude' => floatval($longitude),
                                'nmea' => intval($nmea),
                                'solution' => boolval($solution),
                                'generator' => $generator,
                                'bitrate' => intval($bitrate),
                                'misc' => $misc,
                                'encoder_pwd' => $encoder,
                                'active' => boolval($active)
                                ]
                            ]
                        );

                        if ($modify -> getMatchedCount()) {
                            echo"<script>showNotification(\"success\", \"Changes were saved successfully\");</script>";
                            echo"<script>showNotification(\"information\", \"Loading edit streams panel\");setTimeout(function () {location.href='./editStreams.php';}, 2000);</script>";
                        } else {
                            echo"<script>showNotification(\"error\", \"ERROR: Stream could not be edited\");</script>";
                        }
                    }
                }

            } catch (Exception $e) {
                echo"<script>showNotification(\"error\", \"ERROR: Stream could not be edited\");</script>";
            }
        }
    }
?>
