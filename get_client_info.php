<?php
session_start(); // Start the session

require_once 'db.php'; // Include your database connection

header('Content-Type: application/json'); // Set Content-Type to JSON
// Check if the user is logged in by verifying a session variable, such as 'user_id'
if (!isset($_SESSION['user_id'])) {
    // If user is not logged in, redirect to index.php
    header("Location: login.php");
    exit(); // Ensure no further code is executed after redirection
}
// Get the posted data
$data = json_decode(file_get_contents('php://input'), true);
$clientId = isset($data['client_id']) ? $data['client_id'] : '';  // Get client_id from the request

if (empty($clientId)) {
    error_log("Client ID is missing.");
    echo json_encode(['success' => false, 'message' => 'Client ID is required']);
    exit;
}

// Store the client_id in the session
$_SESSION['client_id'] = $clientId;
error_log("Stored client_id in session: " . $clientId); // Log the stored client_id

// Prepare and execute the query to fetch the client info using client_id
try {
    $sql = "SELECT title, first_name, middle_name, last_name FROM clients WHERE client_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $clientId); // Bind the client_id to the query
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Fetch the client information
        $client = $result->fetch_assoc();

        // Ensure no NULL values in the full name
        $title = $client['title'] ?? '';
        $firstName = $client['first_name'] ?? '';
        $middleName = $client['middle_name'] ?? '';
        $lastName = $client['last_name'] ?? '';

        // Combine fields into a full name
        $fullName = trim("$title $firstName $middleName $lastName");

        // Log the fetched client information
        error_log("Fetched client info: Title: $title, First Name: $firstName, Middle Name: $middleName, Last Name: $lastName");

        // Return client info as JSON
        echo json_encode([
            'success' => true,
            'title' => $title,
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'full_name' => $fullName
        ]);
    } else {
        // Log client not found
        error_log("No client found for client_id: $clientId");
        echo json_encode(['success' => false, 'message' => 'Client not found']);
    }

    $stmt->close();
} catch (Exception $e) {
    // Log any database errors
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
