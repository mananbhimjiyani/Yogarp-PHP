<?php
require_once 'db.php'; // Ensure database connection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_client_id'])) {
    $client_id = intval($_POST['remove_client_id']);
    $batch_id = intval($_POST['batch_id']);
    $studio_id = intval($_POST['studio_id']);
    $attendance_date = $_POST['attendance_date'] ?? date('Y-m-d');

    if ($client_id && $batch_id && $studio_id) {
        // Check if attendance is already marked
        $checkQuery = "SELECT COUNT(*) AS count FROM attendance 
                       WHERE client_id = ? AND batch_id = ? AND studio_id = ? AND DATE(attendance_date) = ?";
        if ($stmt = $conn->prepare($checkQuery)) {
            $stmt->bind_param("iiis", $client_id, $batch_id, $studio_id, $attendance_date);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($result['count'] > 0) {
                echo "exists"; // Attendance already marked
                exit();
            }
        }

        // Insert attendance record
        $stmt = $conn->prepare("INSERT INTO attendance (client_id, batch_id, studio_id, attendance_date) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("iiis", $client_id, $batch_id, $studio_id, $attendance_date);
            if ($stmt->execute()) {
                header('Content-Type: text/plain'); // Ensure plain text response
                echo "success"; // or "exists" or "error"
                exit();
            } else {
                echo "error"; // Insert failed
            }
            $stmt->close();
        } else {
            echo "error"; // Query preparation failed
        }
    } else {
        echo "error"; // Invalid input
    }
    exit();
}
