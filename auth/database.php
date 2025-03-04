<?php
$host = "Localhost";
$dbname = "login_db"; 
$username = "root";
$password="khadija123atiq456helloWorld12345678910";
$mysqli = new mysqli(hostname: $host, 
                    username:$username, 
                    password: $password, 
                    database:$dbname);
if($mysqli -> connect_errno){
    die("Connection error: " . $mysqli->connect_error);
}
return $mysqli;