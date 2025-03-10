<?php
ob_start();
require_once 'Path.php'; // Include the path file for constants
require_once 'db.php'; // Include database connection
include 'includes/header.php';
require_once 'includes/batch_functions.php'; // Include the function to get batches and studios
// Check if the user is logged in by verifying a session variable, such as 'user_id'
if (!isset($_SESSION['user_id'])) {
    // If user is not logged in, redirect to index.php
    header("Location: login.php");
    exit(); // Ensure no further code is executed after redirection
}
$batches_with_studios = getAllBatchesAndStudios();
$batch_data = [];
$clients = [];
$bedCount = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batch_id']) && isset($_POST['studio_id'])) {
    $batch_id = isset($_POST['batch_id']) ? intval($_POST['batch_id']) : null;
    $studio_id = isset($_POST['studio_id']) ? intval($_POST['studio_id']) : null;
    $batchQuery = " SELECT abc.assigned_client_id, c.title, c.first_name, c.middle_name, c.last_name, c.demo_id, b.batch_name AS assigned_batch_name, s.studio_name AS assigned_studio_name, 
        DATE_FORMAT(abc.batch_start_date, '%d/%m/%Y') AS start_date, 
        DATE_FORMAT(m.end_date, '%d/%m/%Y') AS end_date, 
        d.bed_required, d.chair_required, d.mat_required, d.special_required, d.remarks
    FROM assign_batch_to_client abc
    INNER JOIN clients c ON abc.assigned_client_id = c.client_id
    INNER JOIN batch b ON abc.assigned_batch_id = b.batch_id
    INNER JOIN studio s ON abc.assigned_studio_id = s.studio_id
    LEFT JOIN demo d ON c.demo_id = d.demo_id
    LEFT JOIN membership m ON c.client_id = m.client_id
    WHERE abc.assigned_batch_id = ? AND abc.assigned_studio_id = ? AND abc.is_active = 1";
    if ($stmt = $conn->prepare($batchQuery)) {
        $stmt->bind_param("ii", $batch_id, $studio_id);
        $stmt->execute();
        $batchResult = $stmt->get_result();
        while ($row = $batchResult->fetch_assoc()) {
            $batch_data[] = [
                'client_id' => $row['assigned_client_id'],
                'client_name' => "{$row['title']} {$row['first_name']} {$row['middle_name']} {$row['last_name']}",
                'batch_name' => $row['assigned_batch_name'],
                'studio_name' => $row['assigned_studio_name'],
                'batch_assigned_date' => $row['start_date'],
                'membership_end_date' => $row['end_date'],
                'requirements' => [
                    'bed_required' => $row['bed_required'],
                    'chair_required' => $row['chair_required'],
                    'mat_required' => $row['mat_required'],
                    'special_required' => $row['special_required'],
                    'remarks' => $row['remarks']
                ]];
            if ($row['bed_required']) {$bedCount++;}
        }
    } else {
        $batch_data['error'] = "Database query failed: " . $conn->error;
    }
    $assigned_client_ids = array_column($batch_data, 'client_id');
    $searchQuery = " SELECT c.client_id, c.title, c.first_name, c.middle_name, c.last_name, c.demo_id, b.batch_name AS assigned_batch_name, 
            s.studio_name AS assigned_studio_name, 
            DATE_FORMAT(abc.batch_start_date, '%d/%m/%Y') AS start_date, 
            DATE_FORMAT(m.end_date, '%d/%m/%Y') AS end_date, 
            d.bed_required, d.chair_required, d.mat_required, d.special_required, d.remarks
        FROM clients c
        LEFT JOIN assign_batch_to_client abc ON abc.assigned_client_id = c.client_id AND abc.is_active = 1
        LEFT JOIN batch b ON abc.assigned_batch_id = b.batch_id
        LEFT JOIN studio s ON abc.assigned_studio_id = s.studio_id
        LEFT JOIN demo d ON c.demo_id = d.demo_id
        LEFT JOIN membership m ON c.client_id = m.client_id";
    if (!empty($assigned_client_ids)) {
        $placeholders = implode(',', array_fill(0, count($assigned_client_ids), '?'));
        $searchQuery .= " WHERE c.client_id NOT IN ($placeholders)";
    }
    if ($stmt = $conn->prepare($searchQuery)) {
        if (!empty($assigned_client_ids)) {
            $stmt->bind_param(str_repeat("i", count($assigned_client_ids)), ...$assigned_client_ids);
        }
        $stmt->execute();
        $clientResult = $stmt->get_result();
        $clients = $clientResult->fetch_all(MYSQLI_ASSOC);
    } else {
        $clients['error'] = "Client search query failed: " . $conn->error;
    }
}
if (isset($_POST['remove_client_id'])) {
    $client_id = $_POST['remove_client_id'];
    $today = date('Y-m-d');

    // Deactivate the client from the current batch
$sql = "UPDATE assign_batch_to_client SET is_active = 0, batch_end_date = '$today', stamp = NOW() 
        WHERE assigned_client_id = $client_id AND is_active = 1";
    if ($conn->query($sql) === TRUE) {
     //   echo "Member removed successfully."; // This will be sent as a response to the AJAX request
    } else {
        echo "Error: " . $conn->error; // If there is an error, this message will be returned
    }
}

if (isset($_POST['addMember'])) {
    $client_id = $_POST['remove_client_id'];
    $assigned_batch_id = $_POST['batch_id'];
    $assigned_studio_id = $_POST['studio_id'];
    $today = date('Y-m-d');

    // Check if the client is already assigned to the batch in the studio
    $check_sql = "SELECT * FROM assign_batch_to_client 
                  WHERE assigned_client_id = $client_id AND assigned_batch_id = $assigned_batch_id 
                  AND assigned_studio_id = $assigned_studio_id AND is_active = 1";
    $result = $conn->query($check_sql);

    if ($result->num_rows > 0) {
        // Client is already assigned, proceed with updating the assignment
        echo "Client is already assigned. Please update the assignment.";
    } else {
        // Deactivate the existing batch assignment (if any) and insert the new assignment
        $sqlDeactivate = "UPDATE assign_batch_to_client SET is_active = 0, batch_end_date = '$today', stamp = NOW() 
                          WHERE assigned_client_id = $client_id AND is_active = 1";
        if ($conn->query($sqlDeactivate) === TRUE) {
            // Insert new batch assignment
            $sqlInsert = "INSERT INTO assign_batch_to_client (assigned_studio_id, assigned_batch_id, assigned_client_id, batch_start_date, is_active, stamp)
                          VALUES ($assigned_studio_id, $assigned_batch_id, $client_id, '$today', 1, NOW())";
            if ($conn->query($sqlInsert) === TRUE) {
                // Fetch the new assignment details from the `enquiry` table
                $clientDetails = "
                    SELECT 
                        clients.first_name, 
                        clients.last_name, 
                        demo.bed_required, 
                        demo.chair_required, 
                        demo.mat_required, 
                        demo.special_required, 
                        demo.remarks, 
                        batch.batch_name, 
                        studio.studio_name 
                    FROM 
                        clients 
                    JOIN demo ON demo.demo_id = clients.demo_id
                    JOIN batch ON batch.batch_id = $assigned_batch_id
                    JOIN studio ON studio.studio_id = $assigned_studio_id
                    WHERE 
                        clients.client_id = $client_id
                ";

                // Debugging: Output the query for error checking
//                echo "Executing query: " . $clientDetails . "<br>";

                $clientResult = $conn->query($clientDetails);

                // Check if the query executed successfully
                if ($clientResult === FALSE) {
                    echo "Error fetching client details: " . $conn->error;
                    exit;
                }

                // Check if any result is returned
                if ($clientResult->num_rows > 0) {
                    $clientData = $clientResult->fetch_assoc();

                    // Prepare the client information to send back for the table update
                    $response = [
                        'client_id' => $client_id,
                        'client_name' => $clientData['first_name'] . ' ' . $clientData['last_name'],
                        'batch_name' => $clientData['batch_name'],
                        'studio_name' => $clientData['studio_name'],
                        'batch_assigned_date' => $today,
                        'requirements' => [
                            'bed_required' => $clientData['bed_required'],
                            'chair_required' => $clientData['chair_required'],
                            'mat_required' => $clientData['mat_required'],
                            'special_required' => $clientData['special_required'],
                            'remarks' => $clientData['remarks']
                        ]
                    ];

                    // Return the response as JSON
                    echo json_encode($response);
                } else {
                    echo "No client details found for the given client ID.";
                }
            } else {
                echo "Error inserting new assignment: " . $conn->error;
            }
        } else {
            echo "Error deactivating the existing batch: " . $conn->error;
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fetchData'])) {
    ob_end_flush();
} 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Batch Assign</title>
<style>
.table-bordered {
    width: 100%;
    table-layout: fixed; /* Make the table cells fit proportionally */
    border-collapse: collapse;
}

/* Styling for table header and cells */
th, td {
    padding: 8px;
    text-align: center;
}

/* For small screens, make the table scrollable horizontally */
@media (max-width: 768px) {
    .table-bordered {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
    }
}
/* Set the width for specific columns */
#batchTable th:nth-child(1), #batchTable td:nth-child(1) {
    width: 75px; /* Example for Client No. column */
}
#batchTable th:nth-child(2), #batchTable td:nth-child(1) {
    width: 200px; /* Example for Client No. column */
}
#batchTable th:nth-child(3), #batchTable td:nth-child(3) {
    width: 200px; /* Example for Batch Name column */
}

#batchTable th:nth-child(4), #batchTable td:nth-child(4) {
    width: 200px; /* Example for Studio Name column */
}
#batchTable th:nth-child(5), #batchTable td:nth-child(4) {
    width: 100px; /* Example for Studio Name column */
}
#batchTable th:nth-child(6), #batchTable td:nth-child(6) {
    width: 100px; /* Example for Membership End Date column */
}

#batchTable th:nth-child(7), #batchTable td:nth-child(7) {
    width: 150px; /* Example for Requirements column */
}

#batchTable th:nth-child(8), #batchTable td:nth-child(8) {
    width: 70px; /* Example for Action column */
}
/* Set the width for specific columns */
#clientTable th:nth-child(1), #clientTable td:nth-child(1) {
    width: 75px; /* Example for Client No. column */
}
#clientTable th:nth-child(2), #clientTable td:nth-child(1) {
    width: 200px; /* Example for Client No. column */
}
#clientTable th:nth-child(3), #clientTable td:nth-child(3) {
    width: 200px; /* Example for Batch Name column */
}

#clientTable th:nth-child(4), #clientTable td:nth-child(4) {
    width: 200px; /* Example for Studio Name column */
}
#clientTable th:nth-child(5), #clientTable td:nth-child(4) {
    width: 100px; /* Example for Studio Name column */
}
#clientTable th:nth-child(6), #clientTable td:nth-child(6) {
    width: 100px; /* Example for Membership End Date column */
}

#clientTable th:nth-child(7), #clientTable td:nth-child(7) {
    width: 150px; /* Example for Requirements column */
}

#clientTable th:nth-child(8), #clientTable td:nth-child(8) {
    width: 70px; /* Example for Action column */
}
 th.ascending::after {
    content: " ▲"; /* Upward arrow */
    color: red;
    margin-left: 5px;
}

th.descending::after {
    content: " ▼"; /* Downward arrow */
    color: green;
    margin-left: 5px;
}
</style>
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">Batch Assign</h1>

    <?php if (!empty($remove_message)): ?>
        <div class="alert alert-info"><?= $remove_message; ?></div>
    <?php endif; ?>

    <form id="batchForm" method="POST" action="">
    <div class="row">
        <div class="col-md-5">
            <label for="batch_id" class="form-label">Batch</label>
            <select name="batch_id" id="batch_id" class="form-control" onchange="fetchStudios(this.value)" required>
                <option value="">Select a Batch</option>
                <?php foreach ($batches_with_studios as $batch): ?>
                    <option value="<?= $batch['batch_id']; ?>" <?php if (isset($_POST['batch_id']) && $_POST['batch_id'] == $batch['batch_id']) echo 'selected'; ?>>
                        <?= $batch['batch_name']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-5">
            <label for="studio_id" class="form-label">Studio</label>
            <select name="studio_id" id="studio_id" class="form-control" required>
                <option value="">Select a Studio</option>
                <?php if (isset($_POST['batch_id'])): ?>
                    <?php foreach ($batches_with_studios as $batch): ?>
                        <?php if ($batch['batch_id'] == $_POST['batch_id']): ?>
                            <?php foreach ($batch['studios'] as $studio): ?>
                                <option value="<?= $studio['studio_id']; ?>" <?php if (isset($_POST['studio_id']) && $_POST['studio_id'] == $studio['studio_id']) echo 'selected'; ?>>
                                    <?= $studio['studio_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label for="fetch" class="form-label">&nbsp;</label><br>
            <button type="submit" id="fetchData" class="btn btn-primary">Fetch</button>
        </div>
    </div>
</form>

<div class="row">
    <div class="col-md-12">
        <table class="table-bordered" id="batchTable">
            <thead>
                <tr>
                    <th rowspan="2" style="text-align: center; vertical-align: middle;">Client No.</th>
                    <th rowspan="2" style="text-align: center; vertical-align: middle;">Name</th>
                    <th rowspan="2" style="text-align: center; vertical-align: middle;">Batch Name</th>
                    <th rowspan="2" style="text-align: center; vertical-align: middle;">Studio Name</th>
                    <th rowspan="2" style="text-align: center; vertical-align: middle;">Assigned Date</th>
                    <th rowspan="2" style="text-align: center; vertical-align: middle;">Membership End Date</th>
                    <th style="text-align: center; vertical-align: middle;">Requirements</th>
                    <th rowspan="2" style="text-align: center; vertical-align: middle;">Action</th>
                </tr>
                <tr>
                    <th colspan="1" style="font-size: 12px; font-weight: normal; text-align: center; vertical-align: middle; background-color: black; color: white; padding: 2px 5px;">
                        Beds Occupied: <?= $bedCount; ?>
                    </th>
                </tr>
            </thead>
            <tbody style="text-align: center;">
                <?php if (!empty($batch_data) && !isset($batch_data['error'])): ?>
                    <?php foreach ($batch_data as $data): ?>
                        <tr id="clientRow_<?= $data['client_id']; ?>">
                            <td><?= $data['client_id']; ?></td>
                            <td><?= $data['client_name']; ?></td>
                            <td><?= $data['batch_name']; ?></td>
                            <td><?= $data['studio_name']; ?></td>
                            <td><?= $data['batch_assigned_date']; ?></td>
                            <td><?= $data['membership_end_date']; ?></td>
                            <td>
                                <?php 
                                $requirements = [];
                                if ($data['requirements']['bed_required']) $requirements[] = "Bed";
                                if ($data['requirements']['chair_required']) $requirements[] = "Chair";
                                if ($data['requirements']['mat_required']) $requirements[] = "Mat";
                                if ($data['requirements']['special_required']) $requirements[] = "Special";
                                echo !empty($requirements) ? implode(", ", $requirements) : "-";
                                if (!empty($data['requirements']['remarks'])) {
                                    echo "<br>Remarks: " . htmlspecialchars($data['requirements']['remarks']);
                                }
                                ?>
                            </td>
                            <td>
                                <form class="removeForm" method="POST" action="">
                                    <input type="hidden" name="remove_client_id" value="<?= $data['client_id']; ?>">
                                    <button type="submit" id="removeMember" class="btn btn-danger btn-sm">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php elseif (isset($batch_data['error'])): ?>
                    <tr><td colspan="8" class="text-center"><?= $batch_data['error']; ?></td></tr>
                <?php else: ?>
                    <tr><td colspan="8" class="text-center">No records to display</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="container mt-5">
    <h2>Client Search and Assign Batch</h2>
    <div class="row">
        <div class="col-md-12">
            <table class="table-bordered" id="clientTable">
                <thead>
                    <tr>
                        <th>Client No.</th>
                        <th>Name</th>
                        <th>Batch Name</th>
                        <th>Studio Name</th>
                        <th>Assigned Date</th>
                        <th>Membership End Date</th>
                        <th>Requirements</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody style="text-align: center;" id="clientTableBody">
                    <?php 
                    // Assume you fetch $clients from your database
                    foreach ($clients as $client): 
                    ?>
                        <tr>
                            <td><?php echo $client['client_id']; ?></td>
                            <td><?php echo $client['first_name'] . ' ' . $client['last_name']; ?></td>
                            <td><?php echo $client['assigned_batch_name']; ?></td>
                            <td><?php echo $client['assigned_studio_name']; ?></td>
                            <td><?php echo $client['start_date']; ?></td>
                            <td><?php echo $client['end_date']; ?></td>
                            <td>
                                <?php 
                                $requirements = [];
                                if ($client['bed_required']) $requirements[] = "Bed";
                                if ($client['chair_required']) $requirements[] = "Chair";
                                if ($client['mat_required']) $requirements[] = "Mat";
                                if ($client['special_required']) $requirements[] = "Special";
                                echo !empty($requirements) ? implode(", ", $requirements) : "-";
                                ?>
                            </td>
                            <td>
                                <form method="POST" class="addForm"action="">
                                    <input type="hidden" name="remove_client_id" value="<?php echo $client['client_id']; ?>">
                                    <input type="hidden" name="batch_id" value="<?php echo $_POST['batch_id']; ?>"> <!-- Hidden input for batch ID -->
                                    <input type="hidden" name="studio_id" value="<?php echo $_POST['studio_id']; ?>"> <!-- Hidden input for studio ID -->
                                    <button type="submit" name="addMember" id="addMember" class="btn btn-success">Add</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function sortTable(columnIndex) {
    const table = document.getElementById('clientTable'); // Ensure the correct table ID
    const rows = Array.from(table.querySelectorAll('tbody tr')); // Target only rows in tbody
    const header = table.querySelectorAll('th')[columnIndex]; // Target the correct header cell

    // Check if the column is already sorted in ascending order
    const isAscending = !header.classList.contains('ascending');

    // Remove sorting classes from all headers
    table.querySelectorAll('th').forEach(th => {
        th.classList.remove('ascending', 'descending');
    });

    // Apply the correct class to the clicked header
    header.classList.add(isAscending ? 'ascending' : 'descending');

    // Sort rows based on the column
    rows.sort((rowA, rowB) => {
        const cellA = rowA.cells[columnIndex]?.textContent.trim() || '';
        const cellB = rowB.cells[columnIndex]?.textContent.trim() || '';

        return isAscending
            ? cellA.localeCompare(cellB, undefined, { numeric: true })
            : cellB.localeCompare(cellA, undefined, { numeric: true });
    });

    // Re-attach sorted rows to the table
    const tbody = table.querySelector('tbody');
    rows.forEach(row => tbody.appendChild(row));
}

// Default sorting by column index 1 (second column)
document.addEventListener('DOMContentLoaded', () => {
    sortTable(0); // Sort by column index 1 (second column) by default
});
document.addEventListener("DOMContentLoaded", function() {
    // Handle Remove Member button click
    document.querySelectorAll(".removeForm").forEach(function(form) {
        form.addEventListener("submit", function(event) {
            event.preventDefault(); // Prevent the form from submitting (which reloads the page)

            // Show a confirmation dialog to the user
            if (confirm("Are you sure you want to remove this member?")) {
                // Get form data
                var formData = new FormData(form);

                // Send data via AJAX
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text()) // Parse the response text
                .then(data => {
                    // Check if the response contains a success message
                    if (data.includes("successfully")) {
                        showNotification(data, 'success'); // Show success message
                        form.closest("tr").remove(); // Remove the corresponding row from the table
                    } else {
                        showNotification(data, 'error'); // Show error message
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred while removing the member.', 'error');
                });
            }
        });
    });
});

// Notification function for success and error messages
function showNotification(message, type) {
    // Create a notification element
    var notification = document.createElement("div");
    notification.className = type === 'success' ? 'notification success' : 'notification error';
    notification.innerText = message;

    // Append notification to the body
    document.body.appendChild(notification);

    // Automatically remove notification after 3 seconds
    setTimeout(function() {
        notification.remove();
    }, 3000);
}
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Handle Add Member button click
    document.querySelectorAll(".addForm").forEach(function(form) {
        form.addEventListener("submit", function(event) {
            event.preventDefault(); // Prevent the form from submitting (which reloads the page)

            var formData = new FormData(form);  // Form data is collected here

            // Send request to add the member to the batch
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text()) // Get the response text from PHP
            .then(data => {
                if (data.includes("Client assigned successfully")) {
                    showNotification(data, 'success'); // Show success message

                    // Now move the client from client table to batch table
                    var clientRow = form.closest("tr");
                    document.getElementById("batchTable").getElementsByTagName("tbody")[0].appendChild(clientRow);
                    clientRow.querySelector(".addForm").remove(); // Remove the "Add" button from the client row
                } else {
                    showNotification(data, 'error'); // Show error message
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred while adding the member.', 'error');
            });
        });
    });
});

    // Handle Remove Member button click
    document.querySelectorAll(".removeForm").forEach(function(form) {
        form.addEventListener("submit", function(event) {
            event.preventDefault(); // Prevent the form from submitting (which reloads the page)

            // Show a confirmation dialog to the user
            if (confirm("Are you sure you want to remove this member?")) {
                // Get form data
                var formData = new FormData(form);

                // Send data via AJAX to remove the member from the batch
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text()) // Parse the response text
                .then(data => {
                    if (data.includes("Client removed successfully")) {
                        showNotification(data, 'success'); // Show success message

                        // Move the client from the batch table to the client table
                        var batchRow = form.closest("tr");
                        document.getElementById("clientTableBody").appendChild(batchRow);
                        batchRow.querySelector(".removeForm").remove(); // Remove the "Remove" button from the batch row
                    } else {
                        showNotification(data, 'error'); // Show error message
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred while removing the member.', 'error');
                });
            }
        });
    });

    // Function to display success or error messages
    function showNotification(message, type) {
        var notification = document.getElementById('notification');
        if (!notification) {
            notification = document.createElement('div');
            notification.id = 'notification';
            notification.style.position = 'fixed';
            notification.style.top = '10px';
            notification.style.right = '10px';
            notification.style.padding = '10px';
            notification.style.borderRadius = '5px';
            notification.style.zIndex = '1000';
            document.body.appendChild(notification);
        }

        notification.innerText = message;
        if (type === 'success') {
            notification.style.backgroundColor = '#4CAF50'; // Green for success
            notification.style.color = 'white';
        } else if (type === 'error') {
            notification.style.backgroundColor = '#f44336'; // Red for error
            notification.style.color = 'white';
        }

        // Automatically hide notification after 5 seconds
        setTimeout(function() {
            notification.style.display = 'none';
        }, 5000);
    }
});







</script>


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
</div></div>
</body>
</html>
