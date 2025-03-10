<?php
require_once 'db.php'; // Include database connection
// Check if the user is logged in by verifying a session variable, such as 'user_id'
if (isset($_GET['query']) && !empty($_GET['query'])) {
    $query = htmlspecialchars(trim($_GET['query'])); // Sanitize the input

    // Search query
    $sql = "SELECT client_id,title, first_name, middle_name, last_name 
            FROM clients 
            WHERE title LIKE ? OR first_name LIKE ? OR middle_name LIKE ? OR last_name LIKE ?
            LIMIT 10";
    $stmt = $conn->prepare($sql);
    $likeQuery = "%$query%";
    $stmt->bind_param('ssss', $likeQuery, $likeQuery, $likeQuery, $likeQuery);
    $stmt->execute();
    $result = $stmt->get_result();

    $clients = [];
    while ($row = $result->fetch_assoc()) {
        $clients[] = $row;
    }

    echo json_encode($clients);
    exit;
}
?>
