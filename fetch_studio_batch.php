<?php
require_once 'db.php'; // Include database connection

if (isset($_GET['enquiry_id'])) {
    $enquiry_id = $_GET['enquiry_id'];

    // Fetch studio_id and batch_id based on enquiry_id
    $sql = "SELECT studio_id, batch_id FROM enquiry WHERE enquiry_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $enquiry_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $studio_id = $row['studio_id'];
        $batch_id = $row['batch_id'];

        // Fetch studio name
        $studio_sql = "SELECT studio_name FROM studio WHERE studio_id = ?";
        $studio_stmt = $conn->prepare($studio_sql);
        $studio_stmt->bind_param("i", $studio_id);
        $studio_stmt->execute();
        $studio_result = $studio_stmt->get_result();
        $studio_name = $studio_result->fetch_assoc()['studio_name'];

        // Fetch batch name
        $batch_sql = "SELECT batch_name FROM batch WHERE batch_id = ?";
        $batch_stmt = $conn->prepare($batch_sql);
        $batch_stmt->bind_param("i", $batch_id);
        $batch_stmt->execute();
        $batch_result = $batch_stmt->get_result();
        $batch_name = $batch_result->fetch_assoc()['batch_name'];

        echo json_encode(['studio_name' => $studio_name, 'batch_name' => $batch_name]);
    }

    $stmt->close();
    $conn->close();
}
