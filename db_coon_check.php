<?php
// Database credentials
$db_host = 'localhost';
$db_user = 'yogarp';
$db_pass = 'qwaszx12!@QW';
$db_name = 'yogarp';


// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
} else {
    echo "Database connected successfully!";
}

// Close connection
$conn->close();
?>
