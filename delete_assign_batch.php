<?php
require_once 'Path.php';
require_once 'db.php';

// Check for ID in the query string
$id = $_GET['id'] ?? '';

if (!$id) {
    echo '<div class="alert alert-danger">No ID provided.</div>';
    exit;
}

// Handle deletion
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $id = $conn->real_escape_string($id);
    $query = "DELETE FROM client_batch_assign WHERE id='$id'";

    if ($conn->query($query)) {
        echo '<div class="alert alert-success">Assignment deleted successfully.</div>';
        header("Location: assign_batch.php");
        exit;
    } else {
        echo '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
    }
}

