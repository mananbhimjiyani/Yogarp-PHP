<?php
require_once 'Path.php';
require_once 'db.php';

if (isset($_GET['id'])) {
    $studio_batch_id = $_GET['id'];

    // Delete query
    $sql = "DELETE FROM studio_batch WHERE studio_batch_id = $studio_batch_id";

    if ($conn->query($sql) === TRUE) {
        header("Location: assign_batch_to_studio.php?message=Assignment+deleted+successfully");
        exit;
    } else {
        echo "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
    }
} else {
    echo "<div class='alert alert-danger'>No assignment ID provided!</div>";
}
?>
