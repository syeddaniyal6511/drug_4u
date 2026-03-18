<?php
#connect on localhost for user westlake
#with password hanchurch
$dbc = mysqli_connect
('localhost' , 'root' , '' , 'clinic_db')
#if you recall my username is westlake, a password of hanchurch and a database called jonathan_db
OR die
(mysqli_connect_error() );

#set encoding to match PHP script encoding
mysqli_set_charset($dbc, 'utf8');

require_once __DIR__ . '/run_schema.php';
run_create_tables($dbc);



# For PDO connection (optional, if you prefer PDO over mysqli):
    try
	{
		$objPdo = new PDO
				('mysql:host=localhost;port=3306;dbname=clinic_db',
				'root');
        $objPdo -> exec("SET CHARACTER SET utf8");

		//echo "connexion ok<br/>\n";

	} catch( Exception $exception )
	{
	    die($exception->getMessage());
	}



?>
