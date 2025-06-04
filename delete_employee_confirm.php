<?php
require_once 'Path.php';
require_once 'db.php';

if (isset($_GET['id'])) {
    $employee_id = $_GET['id'];

    // Delete query
    $sql = "DELETE FROM employees WHERE employee_id = $employee_id";

    if ($conn->query($sql) === TRUE) {
        header("Location: employee.php?message=Employee+deleted+successfully");
        exit;
    } else {
        echo "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
    }
} else {
    echo "<div class='alert alert-danger'>No employee ID provided!</div>";
}
?>
