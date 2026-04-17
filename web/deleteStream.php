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
	
	require_once __DIR__ . "/vendor/autoload.php";

	include 'conf.php';
	
	$cliente=new MongoDB\Client($conf);
	
	$streams = $cliente->geomaxima->streams;
	$deleted = $streams -> deleteOne(['_id' => new MongoDB\BSON\ObjectID($_GET['idstream']) ]);

	/* Output header */
	header("Content-Type: text/plain");

	if ($deleted -> getDeletedCount()) {
		echo "success";
	} else {
		echo "error";
	}	
?>