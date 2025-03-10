<?php
require_once 'Path.php'; // Include the path file for constants
require_once 'db.php'; // Include database connection

session_start(); // Start the session

// Check if the user is logged in
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    // Prepare the SQL query to remove last login details
    $sql = "UPDATE User SET last_login = NULL, last_ip = NULL WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
}

// Unset all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect to the login page or homepage
header("Location: login.php");
exit();
?>