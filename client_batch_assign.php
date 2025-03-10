<?php
require_once 'db.php'; // Include your database connection
session_start(); // Start session for messages

$clients = [];
$studios = [];
$batches = [];
$batch_name = ""; // Initialize batch name variable
$selected_client_id = ""; // Store selected client ID

// Fetch all studios
$studio_query = "SELECT studio_id, studio_name FROM studio";
$studio_result = $conn->query($studio_query);
while ($row = $studio_result->fetch_assoc()) {
    $studios[] = $row;
}

// Handle client search
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['search_client']) && !empty($_GET['search_client'])) {
    $search_client = $conn->real_escape_string($_GET['search_client']);
    $client_query = "SELECT client_id, title, first_name, last_name, mobile_number FROM clients 
                     WHERE (first_name LIKE '%$search_client%' OR last_name LIKE '%$search_client%' OR mobile_number LIKE '%$search_client%')";
    $client_result = $conn->query($client_query);
    
    while ($row = $client_result->fetch_assoc()) {
        $clients[] = $row;
    }
}

// Handle form submission for assigning the batch
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if client_id and batch_id have been submitted
    if (isset($_POST['client_id']) && isset($_POST['batch_id'])) {
        $client_id = $conn->real_escape_string($_POST['client_id']);
        $batch_id = $conn->real_escape_string($_POST['batch_id']);
        $start_date = $conn->real_escape_string($_POST['start_date']);
        $end_date = $conn->real_escape_string($_POST['end_date']);

        // Check if studio_batch_id is available for the selected studio and batch
        $studio_batch_query = "SELECT sb.studio_batch_id FROM studio_batch sb 
                                JOIN batch b ON sb.batch_id = b.batch_id 
                                WHERE sb.studio_id = '$studio_id' AND sb.batch_id = '$batch_id'";
        $studio_batch_result = $conn->query($studio_batch_query);

        if ($studio_batch_result && $studio_batch_result->num_rows > 0) {
            $studio_batch_row = $studio_batch_result->fetch_assoc();
            $studio_batch_id = $studio_batch_row['studio_batch_id']; // Capture studio_batch_id
        } else {
            // Handle case where studio_batch_id could not be found
            $_SESSION['message'] = 'No studio batch found for the selected studio and batch.';
            header('Location: assign_batch.php');
            exit();
        }

        // Insert into client_batch_assign table
        $insert_query = "INSERT INTO client_batch_assign (studio_batch_id, client_id, start_date, end_date, active) 
                         VALUES ('$studio_batch_id', '$client_id', '$start_date', '$end_date', 1)";

        if ($conn->query($insert_query) === TRUE) {
            // Set a success message in session
            $_SESSION['message'] = 'Batch assigned successfully.';
            // Redirect to assign_batch.php
            header('Location: assign_batch.php');
            exit(); // Make sure to exit after redirection
        } else {
            // Set an error message in session
            $_SESSION['message'] = 'Error: ' . $conn->error;
            header('Location: assign_batch.php');
            exit();
        }
    }
}

// Fetch batches based on selected studio using AJAX
if (isset($_GET['studio_id'])) {
    $studio_id = $conn->real_escape_string($_GET['studio_id']);
    $batch_query = "SELECT b.batch_id, b.batch_name FROM studio_batch sb 
                    JOIN batch b ON sb.batch_id = b.batch_id 
                    WHERE sb.studio_id = '$studio_id'";
    $batch_result = $conn->query($batch_query);

    $batches = [];
    while ($row = $batch_result->fetch_assoc()) {
        $batches[] = $row;
    }

    echo json_encode($batches); // Return batches as JSON
    exit(); // Stop further execution
}

?>

<div class="container mt-4">
    <h2>Assign Batch to Client</h2>

    <!-- Client Search Form -->
    <form method="GET" class="mb-3">
        <input type="text" name="search_client" class="form-control" placeholder="Search Client by Name or Mobile" 
               value="<?= isset($_GET['search_client']) ? htmlspecialchars($_GET['search_client']) : '' ?>">
        <button type="submit" class="btn btn-primary mt-2">Search</button>
    </form>

    <!-- Client Selection Form -->
    <form method="POST" id="assignBatchForm" class="mb-3">
        <div class="row" style="padding-bottom: 10px;">
            <div class="col-md-6">
                <select name="client_id" class="form-control" required>
                    <option value="">Select Client</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?= htmlspecialchars($client['client_id']) ?>" 
                                <?= ($selected_client_id == $client['client_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($client['title'] . ' ' . $client['first_name'] . ' ' . $client['last_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label for="studio">Select Studio:</label>
                <select name="studio_id" id="studio" class="form-control" required>
                    <option value="">Select Studio</option>
                    <?php foreach ($studios as $studio): ?>
                        <option value="<?= htmlspecialchars($studio['studio_id']) ?>">
                            <?= htmlspecialchars($studio['studio_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="row" style="padding-bottom: 10px;">
            <div class="col-md-12">
                <select name="batch_id" id="batch" class="form-control" required>
                    <option value="">Select Batch</option>
                    <!-- Batches will be populated based on studio selection -->
                </select>
            </div>
        </div>

        <!-- Start Date and End Date Selection -->
        <div class="row" style="padding-bottom: 10px;">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="start_date">Start Date:</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="end_date">End Date:</label>
                    <input type="date" name="end_date" id="end_date" class="form-control">
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <button type="submit" name="assign_batch" class="btn btn-primary mt-3">Submit</button>
    </form>
</div>

<script>
// JavaScript to handle studio selection and fetch batches
document.getElementById('studio').addEventListener('change', function() {
    const studioId = this.value;

    if (studioId) {
        // Fetch batches based on selected studio
        fetch(`assign_batch.php?studio_id=${studioId}`)
            .then(response => response.json())
            .then(data => {
                const batchSelect = document.getElementById('batch');
                batchSelect.innerHTML = '<option value="">Select Batch</option>'; // Clear existing options

                // Populate batch dropdown
                data.forEach(batch => {
                    const option = document.createElement('option');
                    option.value = batch.batch_id;
                    option.textContent = batch.batch_name;
                    batchSelect.appendChild(option);
                });
            })
            .catch(error => console.error('Error fetching batches:', error));
    } else {
        // Clear batch selection if no studio is selected
        document.getElementById('batch').innerHTML = '<option value="">Select Batch</option>';
    }
});
</script>

<?php
// Close the database connection
$conn->close();
?>
