<?php
require_once 'Path.php';
require_once 'db.php';

// Check if batch ID is provided
if (isset($_GET['id'])) {
    $batch_id = $_GET['id'];

    // Delete the batch from the database
    $sql = "DELETE FROM batch WHERE batch_id = $batch_id";

    if ($conn->query($sql) === TRUE) {
        // Redirect to add_batch.php on success
        header("Location: add_batch.php?message=Batch+deleted+successfully");
        exit;
    } else {
        echo "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
    }
} else {
    echo "<div class='alert alert-danger'>No batch ID provided!</div>";
}
?>
