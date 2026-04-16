<?php
	// MongoDB connection 
	$ip='localhost';
	$port='27017';
	$user='';
	$pasw='';

	// Refresh Dashboard Rate (s)
	$dashboard_rate = 15;

	// Build connection string (support no-auth for local dev)
	if ($user == '' && $pasw == '') {
		$conf = "mongodb://".$ip.":".$port."/casterrep";
	} else {
		$conf = "mongodb://".$user.":".$pasw."@".$ip.":".$port."/casterrep?authMechanism=SCRAM-SHA-1";
	}
?>