<?php	
	
	define( 'DB_HOST', 'localhost' ); // set database host
	define( 'DB_USER', 'root' ); // set database user
	define( 'DB_PASS', 'root' ); // set database password
	define( 'DB_NAME', 'dbname' ); // set database name

	require_once ( 'Connection.php' );

	$koneksi = new Connection();

	$user = base64_decode(!empty($_GET['token'])?$_GET['token']:"");
	
	$query = "SELECT * FROM users WHERE username = '".$user."'";	
	
	if ($koneksi->num_rows($query) <= 0 )
	{
		exit();
	}

?>
