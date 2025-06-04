<?php
$db_host = 'localhost';
$db_user = 'yogarp';
$db_pass = 'qwaszx12!@QW';
$db_name = 'yogarp';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>