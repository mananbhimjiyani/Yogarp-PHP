<?php
// Include necessary files for database connection and constants
require_once 'db.php'; // Include the database connection

if (isset($_GET['client_id'])) {
    $client_id = intval($_GET['client_id']); // Sanitize client_id

    // Prepare the query to fetch data from multiple tables
    $query = "
        SELECT 
            abc.assigned_client_id AS client_id, 
            CONCAT(c.title, ' ', c.first_name, ' ', c.middle_name, ' ', c.last_name) AS name, 
            b.batch_name, 
            s.studio_name, 
            DATE_FORMAT(abc.batch_start_date, '%d/%m/%Y') AS start_date, 
            DATE_FORMAT(m.end_date, '%d/%m/%Y') AS end_date,  
            d.bed_required, 
            d.chair_required, 
            d.mat_required, 
            d.special_required, 
            d.remarks
        FROM assign_batch_to_client abc
        INNER JOIN clients c ON abc.assigned_client_id = c.client_id
        INNER JOIN batch b ON abc.assigned_batch_id = b.batch_id
        INNER JOIN studio s ON abc.assigned_studio_id = s.studio_id
        LEFT JOIN demo d ON c.demo_id = d.demo_id
        LEFT JOIN membership m ON c.client_id = m.client_id
        WHERE abc.assigned_client_id = ? AND abc.is_active = 1";

    // Prepare and execute the query
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $client_id); // Bind the client_id
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $client_data = $result->fetch_assoc();

            // Prepare the response data
            $response = [
                'client_id' => $client_data['client_id'],
                'name' => $client_data['name'],
                'batch_name' => $client_data['batch_name'],
                'studio_name' => $client_data['studio_name'],
                'start_date' => $client_data['start_date'],
                'end_date' => $client_data['end_date'],
                'requirements' => [
                    'bed_required' => $client_data['bed_required'],
                    'chair_required' => $client_data['chair_required'],
                    'mat_required' => $client_data['mat_required'],
                    'special_required' => $client_data['special_required'],
                    'remarks' => $client_data['remarks']
                ]
            ];

            // Return the data as JSON
            echo json_encode($response);
        } else {
            echo json_encode(['error' => 'No client found with this ID']);
        }
    } else {
        echo json_encode(['error' => 'Database query failed: ' . $conn->error]);
    }
} else {
    echo json_encode(['error' => 'Client ID is required']);
}
?>
