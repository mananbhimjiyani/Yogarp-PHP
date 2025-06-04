<?php
ob_start();
require_once 'Path.php';
require_once 'db.php';
include 'includes/header.php';
require_once 'includes/batch_functions.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$batches_with_studios = getAllBatchesAndStudios();
$batch_data = [];
$bedCount = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['batch_id'])) {
        $_SESSION['selected_batch_id'] = intval($_POST['batch_id']);
    }
    if (isset($_POST['studio_id'])) {
        $_SESSION['selected_studio_id'] = intval($_POST['studio_id']);
    }
    if (isset($_POST['attendance_date'])) {
        $_SESSION['selected_attendance_date'] = $_POST['attendance_date'];
    }
}

$selected_batch_id = $_SESSION['selected_batch_id'] ?? null;
$selected_studio_id = $_SESSION['selected_studio_id'] ?? null;
$selected_attendance_date = $_SESSION['selected_attendance_date'] ?? date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batch_id']) && isset($_POST['studio_id'])) {
    $batch_id = intval($_POST['batch_id']);
    $studio_id = intval($_POST['studio_id']);

    if ($batch_id && $studio_id) {
        $batchQuery = "
        SELECT 
            abc.assigned_client_id, 
            c.title, 
            c.first_name, 
            c.middle_name, 
            c.last_name, 
            c.demo_id, 
            b.batch_name AS assigned_batch_name, 
            s.studio_name AS assigned_studio_name, 
            DATE_FORMAT(abc.batch_start_date, '%d/%m/%Y') AS start_date, 
            DATE_FORMAT(m.end_date, '%d/%m/%Y') AS end_date, 
            d.bed_required, 
            d.chair_required, 
            d.mat_required, 
            d.special_required, 
            d.remarks,
            CASE 
                WHEN DATE(a.attendance_date) = ? THEN 1 
                ELSE 0 
            END AS is_present_today
        FROM assign_batch_to_client abc
        INNER JOIN clients c ON abc.assigned_client_id = c.client_id
        INNER JOIN batch b ON abc.assigned_batch_id = b.batch_id
        INNER JOIN studio s ON abc.assigned_studio_id = s.studio_id
        LEFT JOIN demo d ON c.demo_id = d.demo_id
        LEFT JOIN membership m ON c.client_id = m.client_id
        LEFT JOIN attendance a ON abc.assigned_client_id = a.client_id 
            AND abc.assigned_batch_id = a.batch_id 
            AND abc.assigned_studio_id = a.studio_id
            AND DATE(a.attendance_date) = CURDATE()
        WHERE abc.assigned_batch_id = ? 
            AND abc.assigned_studio_id = ? 
            AND abc.is_active = 1
    ";

        if ($stmt = $conn->prepare($batchQuery)) {
            $stmt->bind_param("sii", $selected_attendance_date, $batch_id, $studio_id);
            if (!$stmt->execute()) {
                $batch_data['error'] = "Database query failed: " . $stmt->error;
            } else {
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
                        ],
                        'is_present_today' => $row['is_present_today']
                    ];
                    if ($row['bed_required']) {
                        $bedCount++;
                    }
                }
            }
            $stmt->close();
        } else {
            $batch_data['error'] = "Database query preparation failed: " . $conn->error;
        }
    } else {
        $batch_data['error'] = "Invalid input data.";
    }
}


// Function to format and style the Membership End Date
function getStyledMembershipEndDate($membershipEndDate)
{
    if (empty($membershipEndDate)) {
        return "<span>N/A</span>";
    }
    $endDate = DateTime::createFromFormat('d/m/Y', $membershipEndDate);
    if (!$endDate || $endDate->format('d/m/Y') !== $membershipEndDate) {
        return "<span style='color: gray;'>Invalid Date</span>";
    }
    $today = new DateTime();
    $diff = $today->diff($endDate);

    if ($today->format('Y-m-d') === $endDate->format('Y-m-d')) {
        return "<span style='background-color: white; color: red;'>$membershipEndDate</span>";
    }
    if ($diff->days <= 10 && $endDate > $today) {
        return "<span style='background-color: yellow; color: red;'>$membershipEndDate</span>";
    }
    if ($endDate < $today) {
        return "<span style='background-color: red; color: white;'>$membershipEndDate</span>";
    }
    return $membershipEndDate;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Batch Assign</title>
    <style>
        .table-bordered {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 8px;
            text-align: center;
        }

        @media (max-width: 768px) {
            .table-bordered {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }

        #batchTable th:nth-child(1),
        #batchTable td:nth-child(1) {
            width: 75px;
        }

        #batchTable th:nth-child(2),
        #batchTable td:nth-child(1) {
            width: 200px;
        }

        #batchTable th:nth-child(3),
        #batchTable td:nth-child(3) {
            width: 200px;
        }

        #batchTable th:nth-child(4),
        #batchTable td:nth-child(4) {
            width: 200px;
        }

        #batchTable th:nth-child(5),
        #batchTable td:nth-child(4) {
            width: 100px;
        }

        #batchTable th:nth-child(6),
        #batchTable td:nth-child(6) {
            width: 100px;
        }

        #batchTable th:nth-child(7),
        #batchTable td:nth-child(7) {
            width: 150px;
        }

        #batchTable th:nth-child(8),
        #batchTable td:nth-child(8) {
            width: 70px;
        }

        th.ascending::after {
            content: " ▲";
            color: red;
            margin-left: 5px;
        }

        th.descending::after {
            content: " ▼";
            color: green;
            margin-left: 5px;
        }

        #searchDropdown div {
            padding: 8px;
            cursor: pointer;
        }

        #searchDropdown div:hover {
            background-color: #f0f0f0;
            color: #007bff;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h1 class="mb-4">Batch Assign</h1>
        <?php if (!empty($_SESSION['attendance_message'])): ?>
            <div class="alert alert-info"><?= htmlspecialchars($_SESSION['attendance_message']); ?></div>
            <?php unset($_SESSION['attendance_message']); ?>
        <?php endif; ?>
        <form id="batchForm" method="POST" action="">
            <div class="row">
                <div class="col-md-5">
                    <label for="batch_id" class="form-label">Batch</label>
                    <select name="batch_id" id="batch_id" class="form-control" onchange="fetchStudios(this.value)" required>
                        <option value="">Select a Batch</option>
                        <?php foreach ($batches_with_studios as $batch): ?>
                            <option value="<?= htmlspecialchars($batch['batch_id']); ?>"
                                <?= ($selected_batch_id == $batch['batch_id']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($batch['batch_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label for="studio_id" class="form-label">Studio</label>
                    <select name="studio_id" id="studio_id" class="form-control" required>
                        <option value="">Select a Studio</option>
                        <?php if (isset($selected_batch_id)): ?>
                            <?php foreach ($batches_with_studios as $batch): ?>
                                <?php if ($batch['batch_id'] == $selected_batch_id): ?>
                                    <?php foreach ($batch['studios'] as $studio): ?>
                                        <option value="<?= htmlspecialchars($studio['studio_id']); ?>"
                                            <?= ($selected_studio_id == $studio['studio_id']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($studio['studio_name']); ?>
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
                                Beds Occupied: <?= htmlspecialchars($bedCount); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody style="text-align: center;">
                        <?php if (!empty($batch_data) && !isset($batch_data['error'])): ?>
                            <?php foreach ($batch_data as $data): ?>
                                <tr id="clientRow_<?= htmlspecialchars($data['client_id']); ?>">
                                    <td><?= htmlspecialchars($data['client_id']); ?></td>
                                    <td><?= htmlspecialchars($data['client_name']); ?></td>
                                    <td><?= htmlspecialchars($data['batch_name']); ?></td>
                                    <td><?= htmlspecialchars($data['studio_name']); ?></td>
                                    <td><?= htmlspecialchars($data['batch_assigned_date']); ?></td>
                                    <td>
                                        <?php
                                        $membershipEndDate = $data['membership_end_date'];
                                        echo getStyledMembershipEndDate($membershipEndDate);
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $requirements = [];
                                        if ($data['requirements']['bed_required']) $requirements[] = "Bed";
                                        if ($data['requirements']['chair_required']) $requirements[] = "Chair";
                                        if ($data['requirements']['mat_required']) $requirements[] = "Mat";
                                        if ($data['requirements']['special_required']) $requirements[] = "Special";
                                        echo !empty($requirements) ? htmlspecialchars(implode(", ", $requirements)) : "-";
                                        if (!empty($data['requirements']['remarks'])) {
                                            echo "<br>Remarks: " . htmlspecialchars($data['requirements']['remarks']);
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($data['is_present_today']): ?>
                                            <button class="btn btn-secondary btn-sm" disabled>Marked Present</button>
                                        <?php else: ?>
                                            <form class="removeForm">
                                                <input type="hidden" name="remove_client_id" value="<?= htmlspecialchars($data['client_id']); ?>">
                                                <input type="hidden" name="batch_id" value="<?= htmlspecialchars($_POST['batch_id'] ?? $_SESSION['selected_batch_id']); ?>">
                                                <input type="hidden" name="studio_id" value="<?= htmlspecialchars($_POST['studio_id'] ?? $_SESSION['selected_studio_id']); ?>">
                                                <button type="submit" class="btn btn-success btn-sm markButton">Present</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php elseif (isset($batch_data['error'])): ?>
                            <tr>
                                <td colspan="8" class="text-center"><?= htmlspecialchars($batch_data['error']); ?></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No records to display</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="row" style="padding-top:10px">
            <div class="col-md-10">
                <label for="searchInput" class="form-label">&nbsp;</label><br>
                <input type="text" id="searchInput" placeholder="Search clients..." autocomplete="off" class="form-control">
                <div id="searchDropdown" class="dropdown-menu"></div>
                <input type="hidden" id="selectedClientId">
            </div>
            <div class="col-md-2">
                <label for="searchInput" class="form-label">&nbsp;</label><br>
                <button type="submit" id="fetchDataforAttendence" class="btn btn-primary">Add Client</button>
            </div>
        </div>
    </div>
    <script>
        // Consolidated JavaScript Code

        // Function to fetch studios based on batch selection
        const batchStudioMap = {};
        <?php foreach ($batches_with_studios as $batch): ?>
            batchStudioMap[<?= $batch['batch_id'] ?>] = <?= json_encode($batch['studios']) ?>;
        <?php endforeach; ?>

        function fetchStudios(batchId) {
            console.log("Fetching studios for batchId:", batchId);
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

        // Function to sort the table by column
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

                return isAscending ?
                    cellA.localeCompare(cellB, undefined, {
                        numeric: true
                    }) :
                    cellB.localeCompare(cellA, undefined, {
                        numeric: true
                    });
            });

            // Re-attach sorted rows to the table
            const tbody = table.querySelector('tbody');
            rows.forEach(row => tbody.appendChild(row));
        }

        function setupClientSearch() {
            let searchInput = document.getElementById('searchInput'); // Correct ID
            let searchDropdown = document.getElementById('searchDropdown'); // Ensure correct ID is used
            const selectedClientId = document.getElementById('selectedClientId');

            if (!searchInput || !searchDropdown) {
                return;
            }

            searchInput.addEventListener('input', function() {
                const query = searchInput.value.trim();
                if (query.length > 0) {
                    fetch(`search_clients.php?query=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(data => {
                            searchDropdown.innerHTML = '';
                            if (data.length > 0) {
                                searchDropdown.style.display = 'block';
                                data.forEach(client => {
                                    const div = document.createElement('div');
                                    div.textContent = `${client.title} ${client.first_name} ${client.middle_name} ${client.last_name}`;
                                    div.dataset.clientId = client.client_id;
                                    div.addEventListener('click', function() {
                                        searchInput.value = div.textContent;
                                        selectedClientId.value = div.dataset.clientId;
                                        searchDropdown.style.display = 'none';
                                    });
                                    searchDropdown.appendChild(div);
                                });
                            } else {
                                searchDropdown.style.display = 'none';
                            }
                        })
                        .catch(() => {});
                } else {
                    searchDropdown.style.display = 'none';
                }
            });
        }




        // JavaScript functions
        function setupAddClientButton() {
            const fetchDataforAttendence = document.getElementById('fetchDataforAttendence');
            const searchInput = document.getElementById('searchInput');
            const selectedClientId = document.getElementById('selectedClientId');
            const batchTableBody = document.querySelector('#batchTable tbody');
            fetchDataforAttendence.addEventListener('click', function() {
                const clientId = selectedClientId.value;
                const clientName = searchInput.value.trim();
                if (!clientId || !clientName) {
                    alert('Please select a client from the dropdown.');
                    return;
                }

                // Check if the client already exists in the table
                const existingRow = document.getElementById(`clientRow_${clientId}`);
                if (existingRow) {
                    alert('This client is already in the table.');
                    return;
                }

                // Make an AJAX call to fetch client data based on clientId
                fetch(`fetch_client_details.php?client_id=${encodeURIComponent(clientId)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data && !data.error) {
                            let requirements = [];
                            if (data.requirements.bed_required) requirements.push("Bed");
                            if (data.requirements.chair_required) requirements.push("Chair");
                            if (data.requirements.mat_required) requirements.push("Mat");
                            if (data.requirements.special_required) requirements.push("Special");
                            if (data.requirements.remarks) requirements.push("Remarks: " + data.requirements.remarks);
                            // If no requirements are set, show a hyphen
                            let requirementsDisplay = requirements.length > 0 ? requirements.join(", ") : "-";
                            // Construct a new row with the client data
                            const newRow = document.createElement('tr');
                            newRow.id = `clientRow_${data.client_id}`;
                            newRow.innerHTML = `
                        <td>${data.client_id}</td>
                        <td>${data.name}</td>
                        <td>${data.batch_name || '-'}</td>
                        <td>${data.studio_name || '-'}</td>
                        <td>${data.start_date || '-'}</td>
                        <td>${data.end_date || '-'}</td>
                        <td>${requirementsDisplay}</td>
                        <td>
                            <form class="removeForm" method="POST" action="">
                                <input type="hidden" name="remove_client_id" value="${data.client_id}">
                                <button type="submit" class="btn btn-success btn-sm">Present</button>
                            </form>
                        </td>
                    `;
                            // Append the new row to the table
                            batchTableBody.appendChild(newRow);
                            // Clear the input fields after adding the client
                            searchInput.value = '';
                            selectedClientId.value = '';
                        } else {
                            alert(data.error || 'Error fetching client details.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while fetching client details.');
                    });
            });
        }


        function setupAttendanceRecording() {
            document.querySelectorAll('.removeForm').forEach(form => {
                form.addEventListener('submit', function(event) {
                    event.preventDefault(); // Prevent page reload

                    let formData = new FormData(this); // Get form data
                    let button = this.querySelector('button'); // Select the button

                    button.textContent = "Marking..."; // Temporary UI change

                    fetch('attendance_backend.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.text())
                        .then(data => {
                            if (data.trim() === "success") {
                                button.textContent = "Marked ✅"; // Change button state
                                button.disabled = true; // Disable after marking
                            } else if (data.trim() === "exists") {
                                button.textContent = "Already Marked ✅";
                                button.disabled = true;
                            } else {
                                button.textContent = "Error ❌";
                                console.error("Error:", data);
                            }
                        })
                        .catch(error => {
                            button.textContent = "Error ❌";
                            console.error("Fetch Error:", error);
                        });
                });
            });
        }




        document.addEventListener('DOMContentLoaded', function() {
            console.log("DOM fully loaded, initializing functions"); // Debugging line

            try {
                sortTable(0);
                console.log("sortTable executed");
            } catch (error) {
                console.error("Error in sortTable:", error);
            }

            try {
                setupClientSearch();
                console.log("setupClientSearch executed");
            } catch (error) {
                console.error("Error in setupClientSearch:", error);
            }

            try {
                setupAddClientButton();
                console.log("setupAddClientButton executed");
            } catch (error) {
                console.error("Error in setupAddClientButton:", error);
            }

            try {
                setupAttendanceRecording();
                console.log("setupAttendanceRecording executed");
            } catch (error) {
                console.error("Error in setupAttendanceRecording:", error);
            }
        });
    </script>
</body>

</html>
<?php ob_end_flush(); ?>