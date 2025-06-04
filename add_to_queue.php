<?php
require_once 'Path.php'; // Include the path file for constants
require_once 'db.php'; // Include database connection
include 'includes/header.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if $db is initialized
if (!isset($db)) {
    die("Database connection not established.");
}

// Ensure user is logged in
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Get selected client IDs
        $client_ids = isset($_POST['client_ids']) ? $_POST['client_ids'] : [];

        if (!empty($client_ids)) {
            // Prepare query to add clients to queue
            $query = "INSERT INTO queue (client_id, added_by) VALUES ";

            // Create placeholders for the query
            $placeholders = [];
            $values = [];
            foreach ($client_ids as $client_id) {
                $placeholders[] = "(?, ?)";
                $values[] = $client_id;
                $values[] = $_SESSION['user_id'];
            }
            $query .= implode(', ', $placeholders);

            // Prepare and execute the query
            if ($stmt = $db->prepare($query)) {
                $stmt->execute($values);
                echo "<p>Selected clients have been added to the queue.</p>";
            } else {
                echo "Error adding clients to the queue: " . $db->error;
            }
        } else {
            echo "<p>No clients selected to add to the queue.</p>";
        }
    } else {
        echo "<p>Invalid request method.</p>";
    }
} else {
    echo "You are not authorized to perform this action.";
}
?>
