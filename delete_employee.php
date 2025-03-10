<?php
require_once 'Path.php';
require_once 'db.php';
include 'includes/header.php';

if (isset($_GET['id'])) {
    $employee_id = $_GET['id'];

    // Check if any batches are assigned to the employee
    $check_sql = "SELECT COUNT(*) as count FROM instructor_batch_assign WHERE instructor_id = $employee_id";
    $check_result = $conn->query($check_sql);
    $check_row = $check_result->fetch_assoc();

    if ($check_row['count'] > 0) {
        echo "<div class='alert alert-danger'>Cannot delete employee as they are assigned to batches. Please remove the assignments first.</div>";
    } else {
        // Confirm deletion
        echo "
        <script>
            var confirmDeletion = confirm('Are you sure you want to delete this employee? This action cannot be undone.');
            if (confirmDeletion) {
                window.location.href = 'delete_employee_confirm.php?id=$employee_id';
            } else {
                window.location.href = 'employee.php';
            }
        </script>";
    }
} else {
    echo "<div class='alert alert-danger'>No employee ID provided!</div>";
}
?>
