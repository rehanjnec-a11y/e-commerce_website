<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "global_mart";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}


?>