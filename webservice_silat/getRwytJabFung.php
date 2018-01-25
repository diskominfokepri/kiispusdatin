<?php
	
	require_once "Token.php";	

	$query = "SELECT * FROM rwyt_jab_fungsional";
	
	$results = $koneksi->get_results($query);
	echo json_encode($results); 	

?>