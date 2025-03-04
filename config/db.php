<?php
$servername = "localhost";
$username = "root";
$password = "khadija123atiq456helloWorld12345678910";
$dbname = "recipes";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
