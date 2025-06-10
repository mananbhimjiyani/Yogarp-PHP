<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['mark_attendance_bulk']) && $input['mark_attendance_bulk'] === true) {
    $clientIds = $input['client_ids'] ?? [];
    $batchId = intval($input['batch_id'] ?? 0);
    $studioId = intval($input['studio_id'] ?? 0);

    if (!$batchId || !$studioId || empty($clientIds)) {
        echo json_encode(['success' => false, 'error' => 'Missing data']);
        exit;
    }

    $date = date('Y-m-d H:i:s');
    $userId = $_SESSION['user_id'] ?? 0; // Use 0 if not set

    $stmt = $conn->prepare("INSERT INTO attendance (client_id, batch_id, studio_id, attendance_date) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
        exit;
    }

    foreach ($clientIds as $cid) {
        if (strpos($cid, 'DEMO_') === 0) {
            continue;
        }
        if (!$stmt->bind_param("iiis", $cid, $batchId, $studioId, $date)) {
            echo json_encode(['success' => false, 'error' => 'Bind failed: ' . $stmt->error]);
            exit;
        }
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'error' => 'Execute failed: ' . $stmt->error]);
            exit;
        }
    }
    $stmt->close();

    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid request']);
exit;
