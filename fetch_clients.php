<?php
require_once 'db.php'; // Include database connection

$query = "SELECT client_id, first_name, middle_name, last_name, fullPhoneNumber, email, created_at FROM clients";
$result = $conn->query($query);

if ($result) {
    $clients = [];
    while ($row = $result->fetch_assoc()) {
        $clients[] = $row;
    }
    echo json_encode($clients);
} else {
    echo json_encode([]);
}
$conn->close();
?>
