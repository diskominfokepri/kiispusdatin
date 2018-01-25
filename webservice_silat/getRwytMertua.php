<?php
	
	require_once "Token.php";	

	$query = "SELECT * FROM rwyt_mertua";
	
	$results = $koneksi->get_results($query);
	echo json_encode($results); 	

?>