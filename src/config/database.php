<?php
$servername = "db";  // matches docker-compose service
$username = "user";
$password = "pass";
$dbname = "testdb";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>