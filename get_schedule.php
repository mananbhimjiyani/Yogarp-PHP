<?php
require_once 'db.php'; // Include database connection
// Check if the user is logged in by verifying a session variable, such as 'user_id'
if (!isset($_SESSION['user_id'])) {
    // If user is not logged in, redirect to index.php
    header("Location: login.php");
    exit(); // Ensure no further code is executed after redirection
}
if (isset($_GET['studio_id']) && isset($_GET['Scheduledate'])) {
    $studio_id = $_GET['studio_id'];
    $Scheduledate = $_GET['Scheduledate'];

    // Fetch the existing schedule data
    $query = "SELECT * FROM studio_schedule WHERE studio_id = ? AND Scheduledate = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $studio_id, $Scheduledate);
    $stmt->execute();
    $result = $stmt->get_result();

    // Output the schedule data
    if ($result->num_rows > 0) {
        while ($schedule = $result->fetch_assoc()) {
            // Output the necessary data in HTML format, adjust this as needed
            echo '<div class="row">';
            echo '<input type="hidden" name="batch_id[]" value="' . htmlspecialchars($schedule['batch_id']) . '">';
            // Output other fields as needed
            echo '<div class="col-md-4"><label>' . htmlspecialchars($schedule['batch_name']) . '</label></div>';
            // Add the dropdowns or other input elements as needed
            echo '</div><br>';
        }
    } else {
        echo "<p>No schedule found for this studio on the selected date.</p>";
    }
} else {
    echo "<p>Studio ID or Scheduledate not provided.</p>";
}
?>
