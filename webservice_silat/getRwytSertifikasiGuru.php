<?php
	
	require_once "Token.php";	

	$query = "SELECT * FROM rwyt_sertifikasi_guru";
	
	$results = $koneksi->get_results($query);
	echo json_encode($results); 	

?>