<?php
require_once 'db.php';
header('Content-Type: application/json');

if (isset($_GET['client_id'])) {
    $client_id = intval($_GET['client_id']);

    // Get the latest membership details and client information
    $query = "SELECT 
        c.client_id,
        CONCAT(c.title, ' ', c.first_name, ' ', COALESCE(c.middle_name, ''), ' ', c.last_name) as client_name,
        m.plan_id,
        m.end_date as last_end_date,
        mp.plan_type,
        mp.plan_duration,
        mp.amount
    FROM clients c
    LEFT JOIN (
        SELECT client_id, plan_id, end_date
        FROM membership
        WHERE client_id = ? AND active = 1
        ORDER BY end_date DESC
        LIMIT 1
    ) m ON c.client_id = m.client_id
    LEFT JOIN membership_plan mp ON m.plan_id = mp.plan_id
    WHERE c.client_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $client_id, $client_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $lastEndDate = $row['last_end_date'];
        $nextStartDate = null;

        if ($lastEndDate) {
            // Calculate next start date (previous end date + 1 day)
            $nextStartDate = date('Y-m-d', strtotime($lastEndDate . ' +1 day'));
        } else {
            // If no previous membership, use current date
            $nextStartDate = date('Y-m-d');
        }

        echo json_encode([
            'client_id' => $row['client_id'],
            'client_name' => $row['client_name'],
            'plan_id' => $row['plan_id'],
            'last_end_date' => $row['last_end_date'],
            'next_start_date' => $nextStartDate,
            'plan_type' => $row['plan_type'],
            'plan_duration' => $row['plan_duration'],
            'amount' => $row['amount']
        ]);
    } else {
        echo json_encode(['error' => 'Client not found']);
    }
} else {
    echo json_encode(['error' => 'No client ID provided']);
}
