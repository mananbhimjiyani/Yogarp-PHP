<?php
require_once 'db.php'; // Include your database connection

if (isset($_GET['country_id'])) {
    $country_id = $_GET['country_id'];
    
    // Prepare a query to fetch country name
    $stmt = $conn->prepare("SELECT name FROM countries WHERE id = ?");
    $stmt->bind_param("s", $country_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $country = $result->fetch_assoc();
    $stmt->close();
    
    // Return JSON data
    echo json_encode($country);
}
?>
