<?php
$servername = "localhost";
$username = "Opencats";
$password = "Opencats@123";
$dbname = "opencats";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$error = '';
?>
