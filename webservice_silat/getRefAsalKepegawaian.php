<?php	

	require_once "Token.php";	

	$query = "SELECT * FROM silat_ref.ref_asal_kepegawaian";
	
	$results = $koneksi->get_results($query);
	echo json_encode($results); 	

?>