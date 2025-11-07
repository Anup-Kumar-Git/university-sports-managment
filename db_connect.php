<?php
// db_connect.php
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';      // set your DB password
$DB_NAME = 'unisports'; // set your DB name

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// set charset
$conn->set_charset("utf8mb4");
?>
