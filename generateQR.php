<?php
require_once 'db.php'; // Include database connection

if (isset($_GET['query'])) {
    $searchQuery = $conn->real_escape_string($_GET['query']);

    // Fetch clients based on the search query
    $query = "
        SELECT 
            client_id, 
            CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) AS fullname, 
            fullPhoneNumber 
        FROM clients 
        WHERE active = 1
        AND (
            CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) LIKE '%$searchQuery%' OR 
            fullPhoneNumber LIKE '%$searchQuery%' OR 
            client_id LIKE '%$searchQuery%'
        )
        LIMIT 10
    ";

    $result = $conn->query($query);
    $clients = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $clients[] = $row;
        }
    }

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($clients);
    exit;
}
?>
