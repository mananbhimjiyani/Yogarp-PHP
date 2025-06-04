<?php
// Include database connection
require_once 'db.php'; // Assuming $conn is defined here

// Get the raw POST data
$data = json_decode(file_get_contents('php://input'), true);

// Check if client_id is set and valid
if (isset($data['client_id'])) {
    $client_id = $data['client_id'];

    // Get the system's current date in the format YYYY-MM-DD
    $attendance_date = date('Y-m-d');

    // Check if the attendance for the client and the date already exists
    $check_query = "SELECT id FROM attendance WHERE client_id = ? AND attendance_date = ?";
    
    if ($stmt = $conn->prepare($check_query)) {
        // Bind parameters to prevent SQL injection
        $stmt->bind_param("is", $client_id, $attendance_date);

        // Execute the statement
        $stmt->execute();
        
        // Store result
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // If attendance already recorded for this client and date
            echo json_encode(['success' => false, 'message' => 'Attendance already recorded']);
        } else {
            // If attendance is not recorded, insert a new record
            $insert_query = "INSERT INTO attendance (client_id, attendance_date) VALUES (?, ?)";

            if ($insert_stmt = $conn->prepare($insert_query)) {
                // Bind parameters to prevent SQL injection
                $insert_stmt->bind_param("is", $client_id, $attendance_date);

                // Execute the statement
                if ($insert_stmt->execute()) {
                    // If insertion is successful, send success response
                    echo json_encode(['success' => true, 'message' => 'Attendance recorded successfully']);
                } else {
                    // If query execution fails, send failure response
                    echo json_encode(['success' => false, 'message' => 'Failed to record attendance']);
                }

                // Close the insert statement
                $insert_stmt->close();
            } else {
                // If statement preparation fails
                echo json_encode(['success' => false, 'message' => 'Failed to prepare SQL statement']);
            }
        }

        // Close the check statement
        $stmt->close();
    } else {
        // If statement preparation fails
        echo json_encode(['success' => false, 'message' => 'Failed to prepare SQL statement']);
    }
} else {
    // Missing client_id
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
}
?>
