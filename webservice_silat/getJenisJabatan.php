<?php

	require_once "Token.php";	
	
	$query = "SELECT * FROM jenis_jabatan";
	
	$results = $koneksi->get_results($query);
	echo json_encode($results); 	

?>