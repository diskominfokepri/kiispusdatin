<?php
	
	require_once "Token.php";	

	$query = "SELECT * FROM rwyt_str";
	
	$results = $koneksi->get_results($query);
	echo json_encode($results); 	

?>