<?php
// activate_applicant.php - Script to activate an applicant

require_once 'Path.php'; // Include the path file for constants
require_once 'db.php'; // Include database connection

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Display success or error messages
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
}


// Get the applicant ID from the URL
if (isset($_GET['id'])) {
    $applicant_id = intval($_GET['id']);

    // Fetch the applicant's current data before updating
    $sql = "SELECT first_name, middle_name, last_name FROM applicants WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $applicant_id);
    $stmt->execute();
    $stmt->bind_result($first_name, $middle_name, $last_name);
    $stmt->fetch();
    $stmt->close();

    // Update the status to 1 (active)
    $sql = "UPDATE applicants SET status = 1 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $applicant_id);
    
    if ($stmt->execute()) {
        // Successful update
        $_SESSION['success'] = "Applicant $first_name $middle_name $last_name activated successfully!";
    } else {
        // Error updating
        $_SESSION['error'] = "Error activating applicant.";
    }

    $stmt->close();
} else {
    $_SESSION['error'] = "Invalid applicant ID.";
}

// Redirect back to the applicant list
header('Location: employee_list.php');
exit;
?>
