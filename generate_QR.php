<?php
ob_start();
require_once 'Path.php'; // Include the path file for constants
require_once 'db.php'; // Include database connection
include 'includes/header.php';
require_once 'includes/batch_functions.php'; // Include the function to get batches and studios

// Fetch active clients with membership end date
$clientQuery = "
    SELECT 
        c.client_id AS id, 
        c.first_name, 
        c.last_name, 
        c.fullPhoneNumber, 
        c.email, 
        m.end_date AS membership_end_date, 
        'client' AS type 
    FROM clients c 
    LEFT JOIN membership m ON c.client_id = m.client_id";
$clientResult = $conn->query($clientQuery);
if (!$clientResult) die("Query failed: " . $conn->error);
$clients = $clientResult->fetch_all(MYSQLI_ASSOC);

// Fetch demo clients (enquiries not converted to clients)
$demoQuery = "
    SELECT 
        e.enquiry_id AS id, 
        e.first_name, 
        e.last_name, 
        e.fullPhoneNumber, 
        NULL AS email, 
        NULL AS membership_end_date, 
        'demo' AS type 
    FROM enquiry e
    WHERE e.enquiry_id NOT IN (SELECT enquiry_id FROM clients)";
$demoResult = $conn->query($demoQuery);
if (!$demoResult) die("Query failed: " . $conn->error);
$demoClients = $demoResult->fetch_all(MYSQLI_ASSOC);

// Combine both client lists
$allClients = array_merge($clients, $demoClients);

// Fetch all batches and associated studios
$batches_with_studios = getAllBatchesAndStudios();
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        #search-dropdown { position: absolute; background: white; border: 1px solid #ccc; max-height: 200px; overflow-y: auto; z-index: 10; width: 100%; }
        #search-dropdown div { padding: 10px; cursor: pointer; }
        #search-dropdown div:hover { background: #f0f0f0; }
        .hidden { display: none !important; }
    </style>
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">Attendance</h1>
    <form method="POST" action="">
        <div class="row">
            <div class="col-md-6">
                <label for="batch_id" class="form-label">Batch</label>
                <select name="batch_id" id="batch_id" class="form-control" onchange="fetchStudios(this.value)" required>
                    <option value="">Select a Batch</option>
                    <?php foreach ($batches_with_studios as $batch): ?>
                        <option value="<?= $batch['batch_id']; ?>"><?= $batch['batch_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="studio_id" class="form-label">Studio</label>
                <select name="studio_id" id="studio_id" class="form-control" required>
                    <option value="">Select a Studio</option>
                </select>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-12">
                <label for="remarks">Remarks</label>
                <textarea name="note" class="form-control"></textarea>
            </div>
        </div>
        <div class="mt-4 position-relative">
            <input type="text" id="search-input" class="form-control" placeholder="Search by name, phone, or email">
            <!--<button type="button" id="add-btn" class="btn btn-primary mt-2">Add</button>-->
            <div id="search-dropdown" class="hidden"></div>
        </div>
        <table class="table mt-3" id="clientTable">
            <thead>
            <tr>
                <th>Client No.</th>
                <th>Name</th>
                <th>Membership End Date</th>
				<th>Remaining Days</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody></tbody>
        </table>
        <input type="hidden" id="client-data-input" name="client_data">
        <button type="submit" class="btn btn-success mt-3">Submit Attendance</button>
    </form>
</div>

<div id="warning-messages" class="alert alert-warning hidden" role="alert"></div>

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

const clients = <?= json_encode($allClients) ?>;
const searchInput = document.getElementById('search-input');
const searchDropdown = document.getElementById('search-dropdown');
const clientTable = document.querySelector('#clientTable tbody');
const clientDataInput = document.getElementById('client-data-input');
const warningMessageContainer = document.getElementById('warning-messages');
let selectedClients = [];

function addClientToTable(client) {
    const row = document.createElement('tr');

    const membershipEndDateFormatted = client.membership_end_date 
        ? new Date(client.membership_end_date).toLocaleDateString() 
        : "No Date";

    const warnings = checkMembershipStatus(client);
    const warningsHTML = warnings.map(w => `<div>${w}</div>`).join('');

    // Display "Demo" in Client ID column if type is "demo"
    const clientIdDisplay = client.type === "demo" ? "Demo" : client.id;

    row.innerHTML = `<td>${clientIdDisplay}</td>
                     <td>${client.first_name} ${client.last_name}</td>
                     <td>${membershipEndDateFormatted}</td>
                     <td>${warningsHTML}</td>
                     <td><button class="btn btn-danger btn-sm" onclick="removeClient(this)">Remove</button></td>`;
    clientTable.appendChild(row);
    selectedClients.push(client);
    updateClientDataInput();
}

function removeClient(button) {
    const row = button.closest('tr');
    const clientId = row.cells[0].textContent;
    selectedClients = selectedClients.filter(client => client.id != clientId);
    row.remove();
    updateClientDataInput();
}

function updateClientDataInput() {
    clientDataInput.value = JSON.stringify(selectedClients);
}

function checkMembershipStatus(client) {
    const today = new Date();
    if (!client.membership_end_date) {
        return ["No membership end date"];
    }

    const membershipEndDate = new Date(client.membership_end_date);
    const daysRemaining = Math.floor((membershipEndDate - today) / (1000 * 60 * 60 * 24)); // Difference in days

    if (daysRemaining < 0) {
        return ["Expired membership"];
    } else if (daysRemaining <= 10) {
        return [`${daysRemaining} days remaining (danger)`];
    } else {
        return [`${daysRemaining} days remaining`];
    }
}

function showWarnings(warnings) {
    if (warnings.length > 0) {
        warningMessageContainer.innerHTML = '<ul>' + warnings.map(w => `<li>${w}</li>`).join('') + '</ul>';
        warningMessageContainer.classList.remove('hidden');
    } else {
        warningMessageContainer.classList.add('hidden');
    }
}

searchInput.addEventListener('input', function () {
    const query = this.value.toLowerCase();
    searchDropdown.innerHTML = '';
    if (query.trim() !== '') {
        const results = clients.filter(client =>
            client.first_name.toLowerCase().includes(query) ||
            client.last_name.toLowerCase().includes(query) ||
            client.fullPhoneNumber.includes(query) ||
            (client.email && client.email.toLowerCase().includes(query))
        );

        results.forEach(client => {
            const div = document.createElement('div');
            div.textContent = `${client.first_name} ${client.last_name} - ${client.fullPhoneNumber}`;
            div.onclick = function () {
                addClientToTable(client);
                searchInput.value = '';
                searchDropdown.classList.add('hidden');
            };
            searchDropdown.appendChild(div);
        });

        if (searchDropdown.children.length > 0) {
            searchDropdown.classList.remove('hidden');
        } else {
            searchDropdown.classList.add('hidden');
        }
    } else {
        searchDropdown.classList.add('hidden');
    }
});
</script>

</body>
</html>
