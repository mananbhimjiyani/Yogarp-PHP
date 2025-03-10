<?php
require_once 'db.php'; // Include your database connection

if (isset($_GET['client_id'])) {
    $client_id = intval($_GET['client_id']); // Ensure the client ID is an integer

    // Query to check active membership
    $sql = "SELECT end_date FROM fees WHERE client_id = ? AND active = 1 AND end_date > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $existing_record = $result->fetch_assoc();
        $existing_end_date = $existing_record['end_date'];

        // Calculate remaining days
        $remaining_days = (strtotime($existing_end_date) - time()) / (60 * 60 * 24);
        echo json_encode(['status' => 'active', 'remaining_days' => round($remaining_days)]);
    } else {
        echo json_encode(['status' => 'inactive']);
    }

    $stmt->close();
}
?>
