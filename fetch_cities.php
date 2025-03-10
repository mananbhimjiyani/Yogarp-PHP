<?php
require_once 'db.php'; // Include your database connection

if (isset($_GET['city'])) {
    $city = $_GET['city'];
    
    // Prepare a query to fetch the state and country based on the city
    $stmt = $conn->prepare("SELECT states.name AS state, countries.name AS country 
                             FROM cities 
                             JOIN states ON cities.state_id = states.id
                             JOIN countries ON states.country_id = countries.id 
                             WHERE cities.name = ?");
    $stmt->bind_param("s", $city);
    $stmt->execute();
    $result = $stmt->get_result();
    $cities = [];
    
    while ($row = $result->fetch_assoc()) {
        $cities[] = $row;
    }
    
    $stmt->close();
    
    // Return JSON data
    echo json_encode($cities);
}
?>
