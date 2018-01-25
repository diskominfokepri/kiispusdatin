<?php

	require_once "Token.php";	
	
	$query = "SELECT * FROM kepegawaian";
	
	$results = $koneksi->get_results($query);
	echo json_encode($results); 	
	
?>