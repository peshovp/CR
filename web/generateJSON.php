<?php
	require_once __DIR__ . "/vendor/autoload.php";
	include 'conf.php';
	$cliente=new MongoDB\Client($conf);
	$rtcm_raw = $cliente->geomaxima->rtcm_raw;
	$streams = $cliente->geomaxima->streams;
	$rover_connections = $cliente->geomaxima->rover_connections;

	// STREAMS
	$mountpoints = $streams->find(array('solution' => false));
	foreach ($mountpoints as $mp) {
		$lat = $mp['latitude'];
		$lon = $mp['longitude'];
		$mountpoint = $mp['mountpoint'];

		$geometry = array(
			"type" => "Point", 
			"coordinates" => [$lon,$lat]
		);

		$raw = $rtcm_raw -> findOne(array('mountpoint'=>$mp['mountpoint']));
		$n_gps = isset($raw['n_gps'])? $raw['n_gps'] : 0 ;
		$n_glo = isset($raw['n_glo'])? $raw['n_glo'] : 0;
		$last_update = isset($raw['timestamp'])? $raw['timestamp'] : 0;
		if ( isset($last_update) ) {
			if ( $last_update < (time()-60) ) {
				$status = false;
			} else {
				$status = true;
			}

			$properties = array(
				"type" => "stream",
				"mountpoint" => $mountpoint,
				"status" => $status,
				"n_gps" => $n_gps,
				"n_glo" => $n_glo,
				"last_update" => $last_update
			  );
	
			$feature[] = array(
				"type" => "Feature",
				"geometry" => $geometry,
				"properties" => $properties
			);
	
			$FeatureCollection = array(
				'type' => 'FeatureCollection',
				'features' => $feature
			);

		}
				
	}

	// USERS
	$rovers = $rover_connections -> find(array('conn_status' => true));
	foreach ($rovers as $rover) {
		if ($rover['coordinates'] !== null) {
			if ( isset($rover['coordinates'][0]) && isset($rover['coordinates'][1]) ) {
				$username = $rover['username'];
				$sats = isset($rover['sats_used'])? $rover['sats_used'] : 0;
				$latency= isset($rover['latency'])? $rover['latency'] : "N/A";
				$quality= isset($rover['quality'])? $rover['quality'] : "N/A";
				$near_station= isset($rover['ref_station'])? $rover['ref_station'] : "N/A";
				$latitude = $rover['coordinates'][0];
				$longitude = $rover['coordinates'][1];
				$last_update = isset($rover['timestamp_last_msg'])? $rover['timestamp_last_msg'] : $rover['last_update'];
				$conn = $rover['conn_ip'][0].":".$rover['conn_ip'][1];
				$requested	= $rover['conn_path'];
				
				$properties = array(
					"type" => 'rover',
					"username" => $username,
					"sats_used" => $sats,
					"latency" => $latency,
					"quality" => $quality,
					"user_agent" => $rover['conn_useragent'],
					"distance_near" => isset($rover['distance_near'])? $rover['distance_near'] : "N/A",
					"near_station" => $near_station,
					"coordinates" => "lat:".$latitude.", lon:".$longitude,
					"connection" => $conn,
					"last_update" => $last_update,
				);
				$geometry = array(
					"type" => "Point", 
					"coordinates" => [$longitude, $latitude]
				);
				$feature[] = array(
					"type" => "Feature",
					"geometry" => $geometry,
					  "properties" => $properties
				);
				$FeatureCollection = array(
					'type' => 'FeatureCollection',
					'features' => $feature
				);
			}
		}
	}
		
	// Return data
	echo json_encode($FeatureCollection);
?>