<?php
require_once 'db.php'; // Include your database connection

if (isset($_GET['state_id'])) {
    $state_id = $_GET['state_id'];
    
    // Prepare a query to fetch state name
    $stmt = $conn->prepare("SELECT name FROM states WHERE id = ?");
    $stmt->bind_param("s", $state_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $state = $result->fetch_assoc();
    $stmt->close();
    
    // Return JSON data
    echo json_encode($state);
}
?>
