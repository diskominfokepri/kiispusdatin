<?php
	
	require_once "Token.php";	

	$query = "SELECT pegawai_id, skpd_id, nip_baru, nip_lama, nuptk, status_kep_id, kppn_id, nama, gelar_depan, gelar_belakang, tempat_lahir, tgl_lahir, nik, jk, agama_id, status_kawin_id, alamat, domisili_id, alamat_domisili, kode_pos, kode_pos_domisili, no_hp, email,  aktif  FROM biodata";
	
	$results = $koneksi->get_results($query);
	echo json_encode($results); 	

?>