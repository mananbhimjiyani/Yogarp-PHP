<?php
ob_start();
require_once 'Path.php';
require_once 'db.php';
include 'includes/header.php';
require_once 'includes/batch_functions.php';
date_default_timezone_set('Asia/Kolkata');
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Set default date to current date
$current_date = date('Y-m-d');
if (isset($_POST['attendance_date'])) {
    $current_date = $_POST['attendance_date'];
}

$batches_with_studios = getAllBatchesAndStudios();
$batch_data = [];
$bedCount = 0;

// Handle batch/studio selection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['batch_id']) && !empty($_POST['batch_id'])) {
        $_SESSION['selected_batch_id'] = intval($_POST['batch_id']);
    }
    if (isset($_POST['studio_id']) && !empty($_POST['studio_id'])) {
        $_SESSION['selected_studio_id'] = intval($_POST['studio_id']);
    }
}

$selected_batch_id = $_SESSION['selected_batch_id'] ?? null;
$selected_studio_id = $_SESSION['selected_studio_id'] ?? null;

// Debug information
if ($selected_batch_id && $selected_studio_id) {
    error_log("Selected Batch ID: " . $selected_batch_id);
    error_log("Selected Studio ID: " . $selected_studio_id);
}

// Multiple entries for today
$attendanceQuery = "SELECT client_id, COUNT(*) as entry_count, GROUP_CONCAT(TIME(attendance_date)) as times 
                   FROM attendance 
                   WHERE DATE(attendance_date) = CURRENT_DATE
                   GROUP BY client_id 
                   HAVING COUNT(*) > 1";
$multipleEntries = [];
$result = $conn->query($attendanceQuery);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $multipleEntries[$row['client_id']] = [
            'count' => $row['entry_count'],
            'times' => $row['times']
        ];
    }
}

// Attendance for today (all batches)
$presentToday = [];
if ($selected_batch_id && $selected_studio_id) {
    $attendanceQuery = "SELECT client_id, demo_id, batch_id FROM attendance 
        WHERE DATE(CONVERT_TZ(attendance_date, '+00:00', '+05:30')) = DATE(CONVERT_TZ(NOW(), '+00:00', '+05:30'))";
    $stmt = $conn->prepare($attendanceQuery);
    $stmt->execute();
    $attendanceResult = $stmt->get_result();
    while ($row = $attendanceResult->fetch_assoc()) {
        $id = $row['demo_id'] ? 'DEMO_' . $row['demo_id'] : $row['client_id'];
        $presentToday[$id] = $row['batch_id'];
    }
    $stmt->close();

    // Get client data (assigned + demo)
    $batchQuery = "
        SELECT 
            abc.assigned_client_id AS client_id,
            COALESCE(c.title, '') AS title,
            COALESCE(c.first_name, '') AS first_name,
            COALESCE(c.middle_name, '') AS middle_name,
            COALESCE(c.last_name, '') AS last_name,
            NULL AS demo_id,
            COALESCE(b.batch_name, '') AS assigned_batch_name,
            COALESCE(s.studio_name, '') AS assigned_studio_name,
            DATE_FORMAT(m.start_date, '%d/%m/%Y') AS start_date,
            DATE_FORMAT(m.end_date, '%d/%m/%Y') AS end_date,
            0 AS bed_required, 0 AS chair_required, 0 AS mat_required, 0 AS special_required, '' AS remarks,
            'Regular' AS user_type
        FROM assign_batch_to_client abc
        JOIN clients c ON abc.assigned_client_id = c.client_id
        LEFT JOIN batch b ON abc.assigned_batch_id = b.batch_id
        LEFT JOIN studio s ON abc.assigned_studio_id = s.studio_id
        LEFT JOIN membership m ON c.client_id = m.client_id
        WHERE abc.assigned_studio_id = ?
          AND abc.assigned_batch_id = ?
          AND abc.is_active = 1
          AND m.active = 1
        UNION ALL
        SELECT 
            CONCAT('DEMO_', e.enquiry_id) AS client_id,
            COALESCE(e.title, '') AS title,
            COALESCE(e.first_name, '') AS first_name,
            COALESCE(e.middle_name, '') AS middle_name,
            COALESCE(e.last_name, '') AS last_name,
            d.demo_id,
            COALESCE(b.batch_name, '') AS assigned_batch_name,
            COALESCE(s.studio_name, '') AS assigned_studio_name,
            DATE_FORMAT(d.demo_date, '%d/%m/%Y') AS start_date,
            DATE_FORMAT(d.demo_date, '%d/%m/%Y') AS end_date,
            COALESCE(d.bed_required, 0) AS bed_required,
            COALESCE(d.chair_required, 0) AS chair_required,
            COALESCE(d.mat_required, 0) AS mat_required,
            COALESCE(d.special_required, 0) AS special_required,
            COALESCE(d.remarks, '') AS remarks,
            'Demo' AS user_type
        FROM demo d
        JOIN enquiry e ON d.enquiry_id = e.enquiry_id
        LEFT JOIN batch b ON d.batch_id = b.batch_id
        LEFT JOIN studio s ON d.studio_id = s.studio_id
        WHERE d.studio_id = ?
          AND d.batch_id = ?
          AND d.active IN (1, 2);";
    if ($stmt = $conn->prepare($batchQuery)) {
        $stmt->bind_param("iiii", $selected_studio_id, $selected_batch_id, $selected_studio_id, $selected_batch_id);
        if ($stmt->execute()) {
            $batchResult = $stmt->get_result();
            while ($row = $batchResult->fetch_assoc()) {
                $client_id = $row['user_type'] === 'Demo' ? $row['client_id'] : $row['client_id'];
                $is_present_today = isset($presentToday[$client_id]);
                $present_batch_name = '';
                if ($is_present_today) {
                    $present_batch_id = $presentToday[$client_id];
                    $batchNameQuery = $conn->prepare("SELECT batch_name FROM batch WHERE batch_id = ?");
                    $batchNameQuery->bind_param("i", $present_batch_id);
                    $batchNameQuery->execute();
                    $batchNameQuery->bind_result($batch_name);
                    if ($batchNameQuery->fetch()) {
                        $present_batch_name = $batch_name;
                    }
                    $batchNameQuery->close();
                }
                $batch_data[] = [
                    'client_id' => $client_id,
                    'client_name' => trim("{$row['title']} {$row['first_name']} {$row['middle_name']} {$row['last_name']}"),
                    'user_type' => $row['user_type'],
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
                    'is_present_today' => $is_present_today,
                    'present_batch_name' => $present_batch_name
                ];
                if ($row['bed_required']) $bedCount++;
            }
        } else {
            error_log("Error executing batch query: " . $stmt->error);
        }
        $stmt->close();
    } else {
        error_log("Error preparing batch query: " . $conn->error);
    }
}

// Format membership end date
function getStyledMembershipEndDate($membershipEndDate)
{
    if (empty($membershipEndDate)) return "<span>N/A</span>";
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
    <title>Attendance</title>
    <style>
        /* Container styling */
        .container {
            max-width: 1400px;
            padding: 20px;
        }

        /* Form controls styling */
        .form-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .form-control {
            height: 45px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            padding: 8px 16px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #862118;
            box-shadow: 0 0 0 0.2rem rgba(134, 33, 24, 0.25);
        }

        /* Table styling */
        .table-bordered {
            width: 100%;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin: 20px 0;
            border: 1px solid #e0e0e0;
        }

        th {
            background: #f8f9fa;
            padding: 15px 10px;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }

        td {
            padding: 12px 10px;
            vertical-align: middle;
            border: 1px solid #e0e0e0;
        }

        tr:hover {
            background-color: #f5f5f5;
            transition: background-color 0.2s ease;
        }

        /* Button styling */
        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: #862118;
            color: white;
        }

        .btn-primary:hover {
            background: #6d1a13;
            transform: translateY(-1px);
        }

        .btn-success {
            background: #2e7d32;
            color: white;
        }

        .btn-success:hover {
            background: #1b5e20;
        }

        .btn-secondary {
            background: #757575;
            color: white;
        }

        /* Search dropdown styling */
        #searchDropdown {
            margin-top: 5px;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 1px solid #e0e0e0;
        }

        #searchDropdown div {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            transition: all 0.2s ease;
        }

        #searchDropdown div:hover {
            background: #f5f5f5;
            color: #862118;
        }

        /* Requirement badges */
        .requirement-badge {
            display: inline-block;
            padding: 4px 12px;
            margin: 2px;
            border-radius: 15px;
            font-size: 12px;
            background: #f5f5f5;
            border: 1px solid #e0e0e0;
            color: #333;
        }

        /* Added styles for better table header */
        .table-header {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: bold;
        }

        .table-header th {
            position: sticky;
            top: 0;
            z-index: 10;
            background: #f8f9fa;
        }

        /* Bed count indicator */
        .bed-count {
            background: #862118;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            display: inline-block;
            font-weight: 500;
            margin-top: 10px;
        }

        /* Status indicators with better contrast */
        .membership-active {
            color: #2e7d32;
            font-weight: 500;
            padding: 4px 8px;
            background: #e8f5e9;
            border-radius: 4px;
        }

        .membership-warning {
            color: #f57c00;
            font-weight: 500;
            padding: 4px 8px;
            background: #fff3e0;
            border-radius: 4px;
        }

        .membership-expired {
            color: #c62828;
            font-weight: 500;
            padding: 4px 8px;
            background: #ffebee;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="page-header" style="padding-top: 20px;">
            <h1 class="mb-4">Attendance Management</h1>
        </div>
        <div class="form-section">
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
        </div>
        <!-- Search bar above the table -->
        <div class="row" style="padding-top:10px">
            <div class="col-md-10">
                <label for="searchInput" class="form-label">&nbsp;</label><br>
                <input type="text" id="searchInput" placeholder="Search clients..." autocomplete="off" class="form-control">
                <div id="searchDropdown" class="dropdown-menu"></div>
                <input type="hidden" id="selectedClientId">
            </div>
            <div class="col-md-2">
                <label for="searchInput" class="form-label">&nbsp;</label><br>
                <button type="button" id="fetchDataforAttendence" class="btn btn-primary">Add Client</button>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <label for="attendanceDate" class="form-label">Attendance Date</label>
                <input type="date" id="attendanceDate" name="attendance_date" class="form-control" value="<?php echo htmlspecialchars($current_date); ?>" required>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <form id="attendanceForm">
                    <table class="table-bordered" id="batchTable">
                        <thead>
                            <tr>
                                <th style="cursor:pointer;" onclick="sortTable(0)">Select</th>
                                <th style="cursor:pointer;" onclick="sortTable(1)">Name</th>
                                <th style="display:none; cursor:pointer;" onclick="sortTable(2)">Last Name</th>
                                <th style="cursor:pointer;" onclick="sortTable(3)">Membership End Date</th>
                                <th style="cursor:pointer;" onclick="sortTable(4)">Requirements</th>
                                <th style="cursor:pointer;" onclick="sortTable(5)">Batch Name</th>
                                <th style="cursor:pointer;" onclick="sortTable(6)">Studio Name</th>
                                <th style="cursor:pointer;" onclick="sortTable(7)">Client No.</th>
                            </tr>
                            <tr>
                                <th></th>
                                <th></th>
                                <th style="display:none;"></th>
                                <th></th>
                                <th style="font-size: 12px; font-weight: normal; text-align: center; background-color: black; color: white; padding: 2px 5px;">
                                    Beds Occupied: <?= htmlspecialchars($bedCount); ?>
                                </th>
                                <th></th>
                                <th></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody style="text-align: center;">
                            <?php if (!empty($batch_data) && !isset($batch_data['error'])): ?>
                                <?php foreach ($batch_data as $data): ?>
                                    <tr id="clientRow_<?= htmlspecialchars($data['client_id']); ?>">
                                        <td>
                                            <input type="checkbox" class="attendance-checkbox" name="attendance[]" value="<?= htmlspecialchars($data['client_id']); ?>">
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($data['client_name']); ?>
                                            <?php if (!empty($data['is_present_today']) && !empty($data['present_batch_name'])): ?>
                                                <div style="color: red; font-size: 12px; margin-top: 4px;">
                                                    <?= htmlspecialchars($data['present_batch_name']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="display:none;">
                                            <?= htmlspecialchars(explode(' ', trim($data['client_name']))[count(explode(' ', trim($data['client_name']))) - 1]); ?>
                                        </td>
                                        <td>
                                            <?= getStyledMembershipEndDate($data['membership_end_date']); ?>
                                        </td>
                                        <td>
                                            <?php
                                            $requirements = [];
                                            if ($data['requirements']['bed_required']) $requirements[] = "<span class='badge bg-dark'>Bed Required</span>";
                                            if ($data['requirements']['chair_required']) $requirements[] = "<span class='badge bg-secondary'>Chair Required</span>";
                                            if ($data['requirements']['mat_required']) $requirements[] = "<span class='badge bg-info'>Mat Required</span>";
                                            if ($data['requirements']['special_required']) $requirements[] = "<span class='badge bg-warning'>Special Required</span>";
                                            echo !empty($requirements) ? implode(" ", $requirements) : "-";
                                            if (!empty($data['requirements']['remarks'])) {
                                                echo "<br><small>Remarks: " . htmlspecialchars($data['requirements']['remarks']) . "</small>";
                                            }
                                            ?>
                                        </td>
                                        <td><?= htmlspecialchars($data['batch_name']); ?></td>
                                        <td><?= htmlspecialchars($data['studio_name']); ?></td>
                                        <td><?= htmlspecialchars($data['client_id']); ?></td>
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
                    <div style="margin-top: 20px; text-align: right; display: flex; align-items: center; justify-content: flex-end; gap: 20px;">
                        <span id="attendanceCounter" style="font-weight: bold; color: #862118;">Selected: 0</span>
                        <button type="submit" class="btn btn-success" id="submitAttendance">Mark Attendance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        // Fetch studios for batch
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
        // Sort table by column
        function sortTable(columnIndex) {
            const table = document.getElementById('batchTable');
            const rows = Array.from(table.querySelectorAll('tbody tr'));
            const headers = table.querySelectorAll('thead th');
            const isAscending = !headers[columnIndex].classList.contains('ascending');
            headers.forEach(th => th.classList.remove('ascending', 'descending'));
            headers[columnIndex].classList.add(isAscending ? 'ascending' : 'descending');
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
            const tbody = table.querySelector('tbody');
            rows.forEach(row => tbody.appendChild(row));
        }
        // Add client button logic
        function setupAddClientButton() {
            const fetchDataforAttendence = document.getElementById('fetchDataforAttendence');
            const searchInput = document.getElementById('searchInput');
            const selectedClientId = document.getElementById('selectedClientId');
            const batchTableBody = document.querySelector('#batchTable tbody');

            fetchDataforAttendence.addEventListener('click', async function() {
                const clientId = selectedClientId.value;
                if (!clientId) {
                    alert('Please select a client from the dropdown.');
                    return;
                }

                try {
                    console.log('Fetching client details for ID:', clientId);
                    const response = await fetch(`search_clients.php?client_id=${encodeURIComponent(clientId)}`);
                    console.log('Response status:', response.status);

                    const responseText = await response.text();
                    console.log('Raw response:', responseText);

                    let data;
                    try {
                        data = JSON.parse(responseText);
                    } catch (e) {
                        console.error('JSON Parse Error:', e);
                        throw new Error('Invalid JSON response from server');
                    }

                    if (!response.ok || data.error) {
                        console.error('Server Error:', data.error);
                        throw new Error(data.error || 'Error fetching client details');
                    }

                    console.log('Parsed client data:', data);

                    // Check if the client already exists in the table
                    if (document.getElementById(`clientRow_${clientId}`)) {
                        alert('This client is already in the table.');
                        return;
                    }

                    // Create requirements string
                    const requirements = [];
                    if (data.requirements) {
                        if (data.requirements.includes('Bed')) requirements.push('Bed');
                        if (data.requirements.includes('Chair')) requirements.push('Chair');
                        if (data.requirements.includes('Mat')) requirements.push('Mat');
                        if (data.requirements.includes('Special')) requirements.push('Special');
                    }
                    const requirementsText = requirements.length > 0 ? requirements.join(', ') : '-';

                    // Add new row to the table
                    const newRow = document.createElement('tr');
                    newRow.id = `clientRow_${data.client_id}`;
                    newRow.innerHTML = `
                        <td>
                            <input type="checkbox" class="attendance-checkbox" name="attendance[]" value="${data.client_id}">
                        </td>
                        <td>${data.name || ''}</td>
                        <td style="display:none;">${(data.name || '').split(' ').slice(-1)[0]}</td>
                        <td>${data.end_date || 'N/A'}</td>
                        <td>${requirementsText}</td>
                        <td>${data.batch_name || '-'}</td>
                        <td>${data.studio_name || '-'}</td>
                        <td>${data.client_id}</td>
                    `;
                    batchTableBody.appendChild(newRow);

                    // Clear the search input and selected client
                    searchInput.value = '';
                    selectedClientId.value = '';
                    document.getElementById('searchDropdown').style.display = 'none';

                    // Update bed count if needed
                    if (data.requirements && data.requirements.includes('Bed')) {
                        const bedCountElement = document.querySelector('th[colspan="8"]');
                        if (bedCountElement) {
                            const currentCount = parseInt(bedCountElement.textContent.match(/\d+/)[0]);
                            bedCountElement.textContent = `Beds Occupied: ${currentCount + 1}`;
                        }
                    }
                } catch (error) {
                    console.error('Error details:', error);
                    alert(`Error adding client: ${error.message}`);
                }
            });
        }

        // Search bar logic
        function setupClientSearch() {
            const searchInput = document.getElementById('searchInput');
            const searchDropdown = document.getElementById('searchDropdown');
            const selectedClientId = document.getElementById('selectedClientId');

            searchInput.addEventListener('input', function() {
                selectedClientId.value = '';
                const query = searchInput.value.trim();
                if (query.length > 0) {
                    fetch(`search_clients.php?query=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(data => {
                            searchDropdown.innerHTML = '';
                            if (data.error) {
                                searchDropdown.style.display = 'block';
                                const div = document.createElement('div');
                                div.className = 'dropdown-item text-muted';
                                div.textContent = data.error;
                                searchDropdown.appendChild(div);
                            } else if (Array.isArray(data) && data.length > 0) {
                                searchDropdown.style.display = 'block';
                                data.forEach(client => {
                                    const div = document.createElement('div');
                                    div.className = 'dropdown-item';
                                    div.textContent = `${client.display_name} (${client.fullPhoneNumber || 'No Phone'})`;
                                    div.dataset.clientId = client.client_id;
                                    div.addEventListener('click', function() {
                                        searchInput.value = div.textContent;
                                        selectedClientId.value = div.dataset.clientId;
                                        searchDropdown.style.display = 'none';
                                    });
                                    searchDropdown.appendChild(div);
                                });
                            } else {
                                searchDropdown.style.display = 'block';
                                const div = document.createElement('div');
                                div.className = 'dropdown-item text-muted';
                                div.textContent = 'No clients found';
                                searchDropdown.appendChild(div);
                            }
                        })
                        .catch(() => {
                            searchDropdown.innerHTML = '';
                            searchDropdown.style.display = 'block';
                            const div = document.createElement('div');
                            div.className = 'dropdown-item text-danger';
                            div.textContent = 'An error occurred while searching. Please try again.';
                            searchDropdown.appendChild(div);
                        });
                } else {
                    searchDropdown.style.display = 'none';
                }
            });

            // Handle Enter key
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const firstItem = searchDropdown.querySelector('.dropdown-item');
                    if (firstItem) {
                        firstItem.click();
                    }
                }
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
                    searchDropdown.style.display = 'none';
                }
            });
        }
        // Attendance submit
        document.addEventListener('DOMContentLoaded', function() {
            setupClientSearch();
            setupAddClientButton();

            // Ensure date is always set
            const attendanceDate = document.getElementById('attendanceDate');
            if (!attendanceDate.value) {
                attendanceDate.value = new Date().toISOString().split('T')[0];
            }

            // Attendance counter logic
            function updateAttendanceCounter() {
                const checked = document.querySelectorAll('.attendance-checkbox:checked').length;
                document.getElementById('attendanceCounter').textContent = `Selected: ${checked}`;
            }
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('attendance-checkbox')) {
                    updateAttendanceCounter();
                }
            });
            // Initialize counter on load
            updateAttendanceCounter();

            document.getElementById('attendanceForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const checked = Array.from(document.querySelectorAll('.attendance-checkbox:checked'));
                if (checked.length === 0) {
                    alert('Please select at least one client to mark attendance.');
                    return;
                }

                const attendanceDate = document.getElementById('attendanceDate').value;
                if (!attendanceDate) {
                    alert('Please select a date for attendance.');
                    return;
                }

                const clientIds = checked.map(cb => cb.value);
                const batchId = document.getElementById('batch_id').value;
                const studioId = document.getElementById('studio_id').value;

                fetch('attendance_backend.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            mark_attendance_bulk: true,
                            client_ids: clientIds,
                            batch_id: batchId,
                            studio_id: studioId,
                            attendance_date: attendanceDate
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            alert('Attendance marked successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + (data.error || 'Unknown error'));
                        }
                    })
                    .catch(err => {
                        alert('Error submitting attendance.');
                        console.error(err);
                    });
            });
        });

        // Add form submission handler
        document.getElementById('batchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const batchId = document.getElementById('batch_id').value;
            const studioId = document.getElementById('studio_id').value;

            if (!batchId || !studioId) {
                alert('Please select both batch and studio');
                return;
            }

            // Submit the form
            this.submit();
        });
    </script>
</body>

</html>
<?php ob_end_flush(); ?>