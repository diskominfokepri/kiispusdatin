<?php
	
	require_once "Token.php";	

	$query = "SELECT * FROM silat_bezzeting.sotk";
	
	$results = $koneksi->get_results($query);
	echo json_encode($results); 	

?>