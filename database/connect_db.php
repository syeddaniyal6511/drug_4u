<?php
#connect on localhost for user westlake
#with password hanchurch
$dbc = mysqli_connect
('localhost' , 'root' , 'Temitope123.' , 'clinic_db')
#if you recall my username is westlake, a password of hanchurch and a database called jonathan_db
OR die
(mysqli_connect_error() );

#set encoding to match PHP script encoding
mysqli_set_charset($dbc, 'utf8');

require_once __DIR__ . '/run_schema.php';
run_create_tables($dbc);

