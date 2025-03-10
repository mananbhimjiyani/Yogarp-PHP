<?php
require_once 'db.php'; // Include database connection
// Check if the user is logged in by verifying a session variable, such as 'user_id'
if (!isset($_SESSION['user_id'])) {
    // If user is not logged in, redirect to index.php
    header("Location: login.php");
    exit(); // Ensure no further code is executed after redirection
}
if (isset($_POST['batch_id'], $_POST['studio_id'])) {
    $batch_id = intval($_POST['batch_id']);
    $studio_id = intval($_POST['studio_id']);

    // Query to fetch batch data
    $batchQuery = "SELECT * FROM assign_batch_to_client WHERE batch_id = ? AND studio_id = ?";
    $stmt = $db->prepare($batchQuery);
    $stmt->bind_param("ii", $batch_id, $studio_id);
    $stmt->execute();
    $batchResult = $stmt->get_result();

    $response = [];

    while ($row = $batchResult->fetch_assoc()) {
        $response[] = [
            'client_id' => $row['client_id'],
            'client_name' => $row['client_name'],
            'batch_name' => $row['batch_name'],
            'studio_name' => $row['studio_name'],
            'batch_assigned_date' => date('d/m/Y', strtotime($row['batch_assigned_date'])),
            'membership_end_date' => date('d/m/Y', strtotime($row['membership_end_date'])),
        ];
    }

    echo json_encode($response);
    exit;
}
