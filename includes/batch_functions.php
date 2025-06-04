<?php
require_once 'db.php'; // Ensure that you include the database connection

// Function to fetch all batches and associated studios
function getAllBatchesAndStudios() {
    global $conn;
    $batches_with_studios = [];

    // Step 1: Fetch all batches
     $sql1 = "SELECT batch_id, batch_name, batch_type FROM batch WHERE active = 1 ORDER BY batch_type, batch_start_time"; // Adjust the ORDER BY clause
    $result1 = $conn->query($sql1);

    if ($result1->num_rows > 0) {
        // Loop through each batch
        while ($batch = $result1->fetch_assoc()) {
            $batch_id = $batch['batch_id'];
            $batch_name = $batch['batch_name'];

            // Step 2: Fetch studio_id and studio_name associated with this batch
            $sql2 = "SELECT s.studio_id, s.studio_name 
                     FROM studio s 
                     INNER JOIN studio_batch sb ON sb.studio_id = s.studio_id
                     WHERE sb.batch_id = ?";
            $stmt2 = $conn->prepare($sql2);
            $stmt2->bind_param("i", $batch_id);
            $stmt2->execute();
            $result2 = $stmt2->get_result();

            $studios = [];
            // Step 3: Fetch studio id and name for each associated studio
            while ($row = $result2->fetch_assoc()) {
                $studios[] = [
                    'studio_id' => $row['studio_id'], // Fetch studio_id
                    'studio_name' => $row['studio_name'] // Fetch studio_name
                ];
            }

            $stmt2->close();

            // Add batch and associated studios to the result array
            $batches_with_studios[] = [
                'batch_id' => $batch_id,
                'batch_name' => $batch_name,
                'studios' => $studios // Array of studio ids and names
            ];
        }
    }

    return $batches_with_studios; // Return an array of batches with their associated studios
}
function getBatchesByStudio($studio_id) {
    global $conn;
    $batches = [];

    // Prepare SQL to fetch batches associated with the given studio
    $sql = "SELECT b.batch_id, b.batch_name, b.batch_type, b.batch_start_time
            FROM batch b
            INNER JOIN studio_batch sb ON sb.batch_id = b.batch_id
            WHERE sb.studio_id = ? AND b.active = 1
            ORDER BY b.batch_type, b.batch_start_time"; // Adjust the ORDER BY clause as needed

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $studio_id); // Bind the studio_id parameter
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch each batch and add to the array
    while ($batch = $result->fetch_assoc()) {
        $batches[] = [
            'batch_id' => $batch['batch_id'],
            'batch_name' => $batch['batch_name'],
            'batch_type' => $batch['batch_type'],
            'batch_start_time' => $batch['batch_start_time']
        ];
    }

    $stmt->close();

    return $batches; // Return the array of batches for the given studio
}
?>

