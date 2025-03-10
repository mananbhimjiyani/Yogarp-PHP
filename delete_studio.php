<?php
require_once 'Path.php';
require_once 'db.php';

// Check if studio ID is provided
if (isset($_GET['id'])) {
    $studio_id = $_GET['id'];

    // Check if there are any batches assigned to this studio
    $check_sql = "SELECT COUNT(*) as count FROM studio_batch WHERE studio_id = $studio_id";
    $check_result = $conn->query($check_sql);
    $check_row = $check_result->fetch_assoc();

    if ($check_row['count'] > 0) {
        // Batches are assigned, prompt user to view assignments
        echo "<div class='alert alert-warning'>
                This studio has batches assigned to it. 
                <a href='assign_batch_to_studio.php?studio_id=$studio_id' class='btn btn-info'>View Assignments</a>
                <a href='delete_studio.php?confirm=1&id=$studio_id' class='btn btn-danger'>Delete Anyway</a>
              </div>";
    } else {
        // No batches assigned, proceed with deletion
        if (isset($_GET['confirm']) && $_GET['confirm'] == '1') {
            // Confirm deletion
            $sql = "DELETE FROM studio WHERE studio_id = $studio_id";
            if ($conn->query($sql) === TRUE) {
                header("Location: add_studio.php?message=Studio+deleted+successfully");
                exit; // Stop further script execution after redirect
            } else {
                echo "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>
                    Confirmation not provided for deletion.
                    <a href='add_studio.php' class='btn btn-primary'>Back to Studio List</a>
                  </div>";
        }
    }
} else {
    echo "<div class='alert alert-danger'>No studio ID provided!</div>";
}
?>

