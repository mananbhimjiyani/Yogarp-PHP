<?php
require_once 'db.php'; // Include your database connection file
// Check if the user is logged in by verifying a session variable, such as 'user_id'

if (isset($_GET['asana_type'])) {
    $asanaType = $_GET['asana_type'];

    // Prepare SQL statement to fetch asana names based on the selected asana type
    $query = "SELECT asana_id, asana_name FROM asana WHERE asana_type = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $asanaType);
    $stmt->execute();
    $result = $stmt->get_result();

    $asanas = [];
    while ($row = $result->fetch_assoc()) {
        $asanas[] = $row; // Add each asana to the array
    }

    // Return as a JSON response
    echo json_encode($asanas);
}
?>
