<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to browser
ini_set('log_errors', 1); // Log errors instead

// Log the incoming request
error_log("Incoming request to search_clients.php: " . print_r($_GET, true));

require_once 'db.php'; // Include database connection
header('Content-Type: application/json'); // Set JSON content type
// Check if the user is logged in by verifying a session variable, such as 'user_id'

try {
    // Test database connection
    if (!$conn) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }
    error_log("Database connection successful");

    // Handle search by client_id
    if (isset($_GET['client_id']) && !empty($_GET['client_id'])) {
        $client_id = htmlspecialchars(trim($_GET['client_id']));
        error_log("Searching for client_id: " . $client_id);

        // First, verify the client exists
        $verifySql = "SELECT client_id FROM clients WHERE client_id = ?";
        $verifyStmt = $conn->prepare($verifySql);
        if (!$verifyStmt) {
            throw new Exception("Verify prepare error: " . $conn->error);
        }

        $verifyStmt->bind_param('s', $client_id);
        if (!$verifyStmt->execute()) {
            throw new Exception("Verify execute error: " . $verifyStmt->error);
        }

        $verifyResult = $verifyStmt->get_result();
        if (!$verifyResult->fetch_assoc()) {
            throw new Exception("Client ID $client_id not found in database");
        }
        error_log("Client verification successful");

        // Get full client details including membership and batch info
        $sql = "SELECT 
                    c.client_id,
                    CONCAT(c.title, ' ', c.first_name, ' ', COALESCE(c.middle_name, ''), ' ', c.last_name) as name,
                    c.fullPhoneNumber,
                    DATE_FORMAT(m.end_date, '%d/%m/%Y') as end_date,
                    b.batch_name,
                    s.studio_name
                FROM clients c
                LEFT JOIN membership m ON c.client_id = m.client_id AND m.active = 1
                LEFT JOIN assign_batch_to_client abc ON c.client_id = abc.assigned_client_id AND abc.is_active = 1
                LEFT JOIN batch b ON abc.assigned_batch_id = b.batch_id
                LEFT JOIN studio s ON abc.assigned_studio_id = s.studio_id
                WHERE c.client_id = ?";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Main query prepare error: " . $conn->error);
        }

        $stmt->bind_param('s', $client_id);
        if (!$stmt->execute()) {
            throw new Exception("Main query execute error: " . $stmt->error);
        }

        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            // Ensure all values are strings
            foreach ($row as $key => $value) {
                $row[$key] = $value === null ? '' : (string)$value;
            }
            error_log("Found client data: " . print_r($row, true));
            echo json_encode($row);
        } else {
            error_log("No complex data found, trying basic query");
            // If no complex data found, get basic client info
            $basicSql = "SELECT 
                            client_id,
                            CONCAT(title, ' ', first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) as name,
                            fullPhoneNumber
                        FROM clients 
                        WHERE client_id = ?";
            $basicStmt = $conn->prepare($basicSql);
            if (!$basicStmt) {
                throw new Exception("Basic query prepare error: " . $conn->error);
            }

            $basicStmt->bind_param('s', $client_id);
            if (!$basicStmt->execute()) {
                throw new Exception("Basic query execute error: " . $basicStmt->error);
            }

            $basicResult = $basicStmt->get_result();
            if ($basicRow = $basicResult->fetch_assoc()) {
                // Add default values for missing fields
                $basicRow['end_date'] = '';
                $basicRow['batch_name'] = '';
                $basicRow['studio_name'] = '';
                error_log("Found basic client data: " . print_r($basicRow, true));
                echo json_encode($basicRow);
            } else {
                throw new Exception("Client data not found in basic query");
            }
        }
        exit;
    }

    // Handle search by query string
    if (isset($_GET['query']) && !empty($_GET['query'])) {
        $query = htmlspecialchars(trim($_GET['query'])); // Sanitize the input
        error_log("Searching with query: " . $query);

        // Search query
        $sql = "SELECT 
                    client_id,
                    CONCAT(title, ' ', first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) as display_name,
                    fullPhoneNumber
                FROM clients 
                WHERE title LIKE ? OR first_name LIKE ? OR middle_name LIKE ? OR last_name LIKE ?
                LIMIT 10";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Search query prepare error: " . $conn->error);
        }

        $likeQuery = "%$query%";
        $stmt->bind_param('ssss', $likeQuery, $likeQuery, $likeQuery, $likeQuery);
        if (!$stmt->execute()) {
            throw new Exception("Search query execute error: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $clients = [];
        while ($row = $result->fetch_assoc()) {
            // Ensure all values are strings
            foreach ($row as $key => $value) {
                $row[$key] = $value === null ? '' : (string)$value;
            }
            $clients[] = $row;
        }
        error_log("Found " . count($clients) . " clients matching query");
        echo json_encode($clients);
        exit;
    }

    // If no valid parameters provided
    throw new Exception("No valid parameters provided");
} catch (Exception $e) {
    error_log("Error in search_clients.php: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
