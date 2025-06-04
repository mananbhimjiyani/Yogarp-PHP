<?php
ob_start();
require_once 'Path.php'; // Include the path file for constants
require_once 'db.php'; // Include database connection
include 'includes/header.php';
require_once 'includes/batch_functions.php'; // Include the function to get batches and studios

// Handle AJAX request to fetch data
if (isset($_POST['batch_id']) && isset($_POST['studio_id'])) {
    $batchId = $_POST['batch_id'];
    $studioId = $_POST['studio_id'];

    // Prepare the SQL query
    $batchQuery = "
        SELECT 
            abtc.client_id,
            c.first_name,
            c.last_name,
            b.batch_name,
            s.studio_name,
            abtc.start_date,
            abtc.end_date
        FROM 
            assign_batch_to_client abtc
        JOIN 
            clients c ON abtc.client_id = c.client_id
        JOIN 
            batch b ON abtc.batch_id = b.batch_id
        JOIN 
            studio s ON abtc.studio_id = s.studio_id
        WHERE 
            abtc.active = 1
            AND abtc.batch_id = ? 
            AND abtc.studio_id = ?
    ";

    $stmt = $conn->prepare($batchQuery);
    $stmt->bind_param("ii", $batchId, $studioId); // Bind parameters
    $stmt->execute();
    $result = $stmt->get_result();
    $clients = $result->fetch_all(MYSQLI_ASSOC); // Fetch all clients data
    
    echo json_encode($clients); // Return data as JSON
    exit();
}

// Fetch all batches and associated studios
$batches_with_studios = getAllBatchesAndStudios();
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch Assign</title>
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">Batch Assign</h1>
    <form id="batchForm" method="POST" action="">
        <div class="row">
            <div class="col-md-5">
                <label for="batch_id" class="form-label">Batch</label>
                <select name="batch_id" id="batch_id" class="form-control" onchange="fetchStudios(this.value)" required>
                    <option value="">Select a Batch</option>
                    <?php foreach ($batches_with_studios as $batch): ?>
                        <option value="<?= $batch['batch_id']; ?>"><?= $batch['batch_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label for="studio_id" class="form-label">Studio</label>
                <select name="studio_id" id="studio_id" class="form-control" required>
                    <option value="">Select a Studio</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="fetch" class="form-label">&nbsp;</label></br>
                <button type="button" id="fetch" class="btn btn-primary" onclick="fetchBatchData()">Fetch</button>
            </div>
        </div>

        <table class="table mt-3" id="batchTable">
            <thead>
            <tr>
                <th>Client No.</th>
                <th>Name</th>
                <th>Batch Name</th>
                <th>Studio Name</th>
                <th>Batch Assigned Date</th>
                <th>Membership End Date</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
                <!-- Fetched rows will be displayed here -->
            </tbody>
        </table>

        <div class="mt-4 position-relative">
            <input type="text" id="search-input" class="form-control" placeholder="Search by name, phone, or email">
            <div id="search-dropdown" class="hidden"></div>
        </div>

        <div class="row mt-3">
            <input type="hidden" id="client-data-input" name="client_data">
            <button type="submit" class="btn btn-success mt-3">Assign Batch</button>
        </div>
    </form>
</div>
<script>
const batchStudioMap = {};

<?php foreach ($batches_with_studios as $batch): ?>
    batchStudioMap[<?= $batch['batch_id'] ?>] = <?= json_encode($batch['studios']) ?>;
<?php endforeach; ?>

function fetchStudios(batchId) {
    const studioDropdown = document.getElementById("studio_id");
    studioDropdown.innerHTML = "<option value=''>Select a Studio</option>";

    if (batchId in batchStudioMap) {
        batchStudioMap[batchId].forEach(studio => {
            const option = document.createElement("option");
            option.value = studio['studio_id'];
            option.text = studio['studio_name'];
            studioDropdown.appendChild(option);
        });
    }
}
</script>
<script>
// Fetch and populate client data in the table
function fetchBatchData() {
    const batchId = document.getElementById("batch_id").value;
    const studioId = document.getElementById("studio_id").value;

    const tableBody = document.querySelector("#batchTable tbody");
    tableBody.innerHTML = '';  // Clear the table

    if (batchId && studioId) {
        const formData = new FormData();
        formData.append('batch_id', batchId);
        formData.append('studio_id', studioId);

        fetch('', {  // Use the same file
            method: 'POST', 
            body: formData 
        })
        .then(response => response.json())
        .then(data => {
            if (data.length > 0) {
                data.forEach(client => {
                    const row = document.createElement("tr");
                    row.innerHTML = `
                        <td>${client.client_id}</td>
                        <td>${client.first_name} ${client.last_name}</td>
                        <td>${client.batch_name}</td>
                        <td>${client.studio_name}</td>
                        <td>${client.start_date}</td>
                        <td>${client.end_date || 'N/A'}</td>
                        <td><button class="btn btn-danger btn-sm remove-client" data-client-id="${client.client_id}">Remove</button></td>
                    `;
                    tableBody.appendChild(row);
                });
            } else {
                const row = document.createElement("tr");
                row.innerHTML = `<td colspan="7" class="text-center text-danger">No clients found for the selected Batch and Studio.</td>`;
                tableBody.appendChild(row);
            }
        })
        .catch(error => console.error('Error fetching data:', error));
    } else {
        alert("Please select both Batch and Studio.");
    }
}
</script>

</body>
</html>
