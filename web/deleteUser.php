<?php
	require_once 'session_check.php';
	
	require_once __DIR__ . "/vendor/autoload.php";

	include 'conf.php';
	require_once 'csrf.php';

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		http_response_code(405);
		die('Method not allowed');
	}

	validateCSRFToken($_POST['csrf_token'] ?? null);
	
	$cliente=new MongoDB\Client($conf);
	
	$users = $cliente->geomaxima->users;
	$deleted = $users -> deleteOne(['_id' => new MongoDB\BSON\ObjectID($_POST['iduser']) ]);

	/* Output header */
	header("Content-Type: text/plain");

	if ($deleted -> getDeletedCount()) {
		echo "success";
	} else {
		echo "error";
	}	
?>