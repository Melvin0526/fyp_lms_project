<?php 

$host = "localhost";
$user = "root";
$password = "";
$database = "user_db"; 

// Define constants for use in other files
define('DB_HOST', $host);
define('DB_USER', $user);
define('DB_PASS', $password);
define('DB_NAME', $database);

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>
