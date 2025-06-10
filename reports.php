<?php
require_once 'db.php';
include 'includes/header.php';

// Helper for sort links - Move function definition to top
function sort_link($label, $field, $sortField, $sortDir)
{
    $dir = ($sortField === $field && $sortDir === 'ASC') ? 'DESC' : 'ASC';
    $icon = ($sortField === $field)
        ? ($sortDir === 'ASC' ? '<i class="bi bi-arrow-up"></i>' : '<i class="bi bi-arrow-down"></i>')
        : '';
    $params = $_GET;
    $params['sort'] = $field;
    $params['dir'] = $dir;
    return sprintf(
        '<a href="?%s" class="text-black text-decoration-none">%s %s</a>',
        http_build_query($params),
        htmlspecialchars($label),
        $icon
    );
}

// Fetch studios and batches for dropdowns
$studios = $conn->query("SELECT studio_id, studio_name FROM studio ORDER BY studio_name ASC");
$batches = $conn->query("SELECT batch_id, batch_name FROM batch ORDER BY batch_name ASC");

// Handle filters
$filterType = $_GET['filter_type'] ?? '1';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$studioId = $_GET['studio_id'] ?? '';
$batchId = $_GET['batch_id'] ?? '';
$nameSearch = $_GET['name_search'] ?? '';

// Sorting logic
$sortField = $_GET['sort'] ?? 'a.attendance_date';
$sortDir = $_GET['dir'] ?? 'DESC';
$allowedSorts = [
    'a.attendance_date' => 'a.attendance_date',
    'c.first_name' => 'c.first_name',
    'c.last_name' => 'c.last_name',
    's.studio_name' => 's.studio_name',
    'b.batch_name' => 'b.batch_name'
];
$allowedDirs = ['ASC', 'DESC'];
if (!isset($allowedSorts[$sortField])) $sortField = 'a.attendance_date';
if (!in_array($sortDir, $allowedDirs)) $sortDir = 'DESC';

$attendanceData = [];
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($filterType === '1' || $filterType === '2')) {
    $where = [];
    $params = [];
    $types = '';

    if ($startDate && $endDate) {
        $where[] = "a.attendance_date BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate;
        $types .= 'ss';
    }

    if ($filterType === '1') {
        $sql = "WITH date_series AS (
            SELECT DISTINCT date_list.d AS attendance_date
            FROM (
                SELECT DATE('$startDate') + INTERVAL (a.a + (10 * b.a)) DAY as d
                FROM (SELECT 0 as a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) a,
                     (SELECT 0 as a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) b
                WHERE DATE('$startDate') + INTERVAL (a.a + (10 * b.a)) DAY <= DATE('$endDate')
            ) date_list
        )
        SELECT
            DATE_FORMAT(d.attendance_date, '%d/%m/%Y') as attendance_date,
            c.first_name,
            c.last_name,
            s.studio_name,
            b.batch_name,
            IF(att.attendance_date IS NULL, 1, 0) as absent,
            ca.assigned_studio_id,
            ca.assigned_batch_id
        FROM date_series d
        CROSS JOIN (
            SELECT abc1.assigned_client_id, abc1.assigned_studio_id, abc1.assigned_batch_id
            FROM assign_batch_to_client abc1
            WHERE abc1.is_active = 1
        ) ca
        JOIN clients c ON ca.assigned_client_id = c.client_id
        JOIN studio s ON ca.assigned_studio_id = s.studio_id
        JOIN batch b ON ca.assigned_batch_id = b.batch_id
        LEFT JOIN attendance att ON DATE(d.attendance_date) = DATE(att.attendance_date)
            AND ca.assigned_client_id = att.client_id
            AND ca.assigned_studio_id = att.studio_id
            AND ca.assigned_batch_id = att.batch_id
        WHERE d.attendance_date BETWEEN '$startDate' AND '$endDate'
        ORDER BY d.attendance_date ASC, c.first_name ASC";

        $result = $conn->query($sql);

        if ($result === false) {
            die('Query failed: ' . $conn->error);
        }

        // Update grouping logic
        $groupedData = [];

        while ($row = $result->fetch_assoc()) {
            // Apply studio and batch filters in PHP
            if (($studioId == '' || $row['assigned_studio_id'] == $studioId) &&
                ($batchId == '' || $row['assigned_batch_id'] == $batchId)) {
                $date = $row['attendance_date'];
                if (!isset($groupedData[$date])) {
                    $groupedData[$date] = [
                        'records' => [],
                        'present' => 0,
                        'absent' => 0,
                        'total' => 0
                    ];
                }

                $groupedData[$date]['records'][] = $row;
                if ($row['absent'] == 1) {
                    $groupedData[$date]['absent']++;
                } else {
                    $groupedData[$date]['present']++;
                }
                $groupedData[$date]['total']++;
            }
        }

        $attendanceData = $groupedData;

        // Remove old group data
        unset($groupedData);

        // Remove duplicate counting code
        unset($groupedData);
    } elseif ($filterType === '2' && $nameSearch) {
        $where[] = "(CONCAT(TRIM(c.first_name), ' ', TRIM(c.last_name)) LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ?)";
        $params[] = "%$nameSearch%";
        $params[] = "%$nameSearch%";
        $params[] = "%$nameSearch%";
        $types .= 'sss';
    }

    // Update SQL to include absent status for filter type 2
    $sql = "SELECT 
            DATE_FORMAT(a.attendance_date, '%d/%m/%Y') as attendance_date,
            c.first_name, 
            c.last_name, 
            s.studio_name, 
            b.batch_name,
            FALSE as absent
        FROM attendance a
        LEFT JOIN clients c ON a.client_id = c.client_id
        LEFT JOIN studio s ON a.studio_id = s.studio_id
        LEFT JOIN batch b ON a.batch_id = b.batch_id";

    if ($where) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY $sortField $sortDir";

    // Only use prepared statements if there are parameters, else use query directly
    if ($params) {
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $attendanceData = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        } else {
            $attendanceData = [];
        }
    } else {
        $result = $conn->query($sql);
        if ($result) {
            $attendanceData = $result->fetch_all(MYSQLI_ASSOC);
        } else {
            $attendanceData = [];
        }
    }

    // For name filter, also fetch absentees in the date range
    if ($filterType === '2' && $nameSearch && $startDate && $endDate) {
        // Get client IDs matching the name search
        $clientSql = "SELECT client_id, first_name, last_name FROM clients WHERE CONCAT(TRIM(first_name), ' ', TRIM(last_name)) LIKE ? OR first_name LIKE ? OR last_name LIKE ?";
        $clientStmt = $conn->prepare($clientSql);
        $clientStmt->bind_param('sss', $params[0], $params[1], $params[2]);
        $clientStmt->execute();
        $clientRes = $clientStmt->get_result();
        $absentees = [];
        while ($client = $clientRes->fetch_assoc()) {
            // For each client, get all dates in range
            $period = new DatePeriod(
                new DateTime($startDate),
                new DateInterval('P1D'),
                (new DateTime($endDate))->modify('+1 day')
            );
            foreach ($period as $date) {
                $dateStr = $date->format('Y-m-d');
                // Check if present in attendanceData for this date
                $found = false;
                foreach ($attendanceData as $row) {
                    if (
                        $row['first_name'] === $client['first_name'] &&
                        $row['last_name'] === $client['last_name'] &&
                        $row['attendance_date'] === $dateStr
                    ) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $absentees[] = [
                        'attendance_date' => $dateStr,
                        'first_name' => $client['first_name'],
                        'last_name' => $client['last_name'],
                        'studio_name' => '',
                        'batch_name' => '',
                        'absent' => true    // Explicitly set absent status
                    ];
                }
            }
        }
        // Merge absentees into attendanceData
        $attendanceData = array_merge($attendanceData, $absentees);
        // Sort again by date and name
        usort($attendanceData, function ($a, $b) use ($sortField, $sortDir) {
            $fieldA = $a['attendance_date'];
            $fieldB = $b['attendance_date'];
            if ($fieldA == $fieldB) {
                return strcmp($a['first_name'], $b['first_name']);
            }
            return ($sortDir === 'ASC' ? strcmp($fieldA, $fieldB) : strcmp($fieldB, $fieldA));
        });
    }
    // Just before displaying the table, add counters for name filter
    if ($filterType === '2' && $nameSearch) {
        $presentCount = count(array_filter($attendanceData, function ($row) {
            return !isset($row['absent']);
        }));
        $absentCount = count(array_filter($attendanceData, function ($row) {
            return isset($row['absent']) && $row['absent'];
        }));
    }

    // Single grouping logic - ensure absent field is always set
    if ($filterType === '1' && $attendanceData) {
        $groupedData = [];
        foreach ($attendanceData as $row) {
            $date = $row['attendance_date'];
            if (!isset($groupedData[$date])) {
                $groupedData[$date] = [
                    'records' => [],
                    'present' => 0,
                    'absent' => 0,
                    'total' => 0
                ];
            }
            // Ensure absent is set
            $row['absent'] = $row['absent'] ?? false;
            $groupedData[$date]['records'][] = $row;

            if ($row['absent']) {
                $groupedData[$date]['absent']++;
            } else {
                $groupedData[$date]['present']++;
            }
            $groupedData[$date]['total']++;
        }

        // Sort by date ascending
        ksort($groupedData);
        $attendanceData = $groupedData;

        // Remove old group data
        unset($groupedData);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Attendance Reports</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <style>
        body {
            background: #f8f9fa;
        }

        .main-content {
            padding-top: 40px;
        }

        .card {
            border-radius: 18px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.10);
        }

        .form-label,
        label {
            font-weight: 500;
            color: #862118;
        }

        .form-control,
        .form-select,
        input,
        select,
        textarea {
            border-radius: 8px !important;
            border: 1px solid #e0d2d2 !important;
            margin-bottom: 12px;
        }

        .btn,
        button {
            border-radius: 8px !important;
            box-shadow: 0 1px 4px rgba(134, 33, 24, 0.08);
            font-weight: 500;
        }

        .btn-primary {
            background-color: #862118 !important;
            border-color: #862118 !important;
        }

        .btn-primary:hover {
            background-color: #a42b2b !important;
            border-color: #a42b2b !important;
        }

        h1,
        h2,
        h3,
        h4,
        h5 {
            color: #862118;
            font-weight: 700;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert {
            border-radius: 8px;
        }

        .table {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.07);
            overflow: hidden;
        }

        th,
        td {
            vertical-align: middle !important;
        }

        th {
            background: #862118;
            color: #fff;
            font-weight: 600;
            border: none !important;
        }

        tr {
            border-bottom: 1px solid #f0eaea;
        }

        tr:last-child {
            border-bottom: none;
        }

        @media (max-width: 768px) {
            .main-content {
                padding-top: 20px;
            }

            .card {
                padding: 0;
            }
        }
    </style>
</head>

<body>
    <div class="main-content container py-4">
        <h1><i class="bi bi-bar-chart-line"></i> Attendance Reports</h1>
        <div class="card shadow-sm rounded-4 border-0 mb-4">
            <div class="card-body p-4">
                <form method="get" class="mb-4">
                    <div class="row mb-2">
                        <div class="col-md-3">
                            <label class="form-label">Filter Type</label>
                            <select name="filter_type" id="filter_type" class="form-select" onchange="toggleFilters()" required>
                                <option value="1" <?= $filterType == '1' ? 'selected' : '' ?>>By Studio & Batch</option>
                                <option value="2" <?= $filterType == '2' ? 'selected' : '' ?>>By Name</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>" data-date-format="dd/mm/yyyy" onchange="updateDateFormat(this);" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>" data-date-format="dd/mm/yyyy" onchange="updateDateFormat(this);" required>
                        </div>
                    </div>
                    <div class="row mb-2" id="studioBatchFilters" <?= $filterType == '2' ? 'style="display:none;"' : '' ?>>
                        <div class="col-md-3">
                            <label class="form-label">Studio</label>
                            <select name="studio_id" class="form-select">
                                <option value="">All Studios</option>
                                <?php if ($studios) while ($studio = $studios->fetch_assoc()): ?>
                                    <option value="<?= $studio['studio_id'] ?>" <?= $studioId == $studio['studio_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($studio['studio_name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Batch</label>
                            <select name="batch_id" class="form-select">
                                <option value="">All Batches</option>
                                <?php if ($batches) while ($batch = $batches->fetch_assoc()): ?>
                                    <option value="<?= $batch['batch_id'] ?>" <?= $batchId == $batch['batch_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($batch['batch_name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-2" id="nameSearchFilter" <?= $filterType == '1' ? 'style="display:none;"' : '' ?>>
                        <div class="col-md-4">
                            <label class="form-label">Name</label>
                            <input type="text" name="name_search" class="form-control" value="<?= htmlspecialchars($nameSearch) ?>" placeholder="Enter name">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2 mt-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Filter</button>
                        </div>
                    </div>
                </form>
                <?php if ($attendanceData): ?>
                    <?php if ($filterType === '2' && $nameSearch): ?>
                        <div class="alert alert-info">
                            <strong>Summary:</strong>
                            <span class="badge bg-success ms-2">Present: <?= $presentCount ?></span>
                            <span class="badge bg-danger ms-2">Absent: <?= $absentCount ?></span>
                            <span class="badge bg-secondary ms-2">Total: <?= count($attendanceData) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="table-responsive">
                        <?php if ($filterType === '1'): ?>
                            <?php foreach ($attendanceData as $date => $group): ?>
                                <div class="mb-3">
                                    <div class="alert alert-info d-flex justify-content-between align-items-center">
                                                                            <strong><?= date('d/m/Y', strtotime($date)) ?></strong>
                                                                            <div>
                                                                                <span class="badge bg-success ms-2">Present: <?= $group['present'] ?? 0 ?></span>
                                                                                <span class="badge bg-danger ms-2">Absent: <?= $group['absent'] ?? 0 ?></span>
                                                                                <span class="badge bg-secondary ms-2">Total: <?= ($group['present'] ?? 0) + ($group['absent'] ?? 0) ?></span>
                                                                            </div>
                                                                        </div>
                                                                        <table class="table table-bordered table-striped rounded-3 shadow-sm align-middle">
                                        <thead>
                                            <tr>
                                                <th><?= sort_link('Name', 'c.first_name', $sortField, $sortDir) ?></th>
                                                <th><?= sort_link('Studio', 's.studio_name', $sortField, $sortDir) ?></th>
                                                <th><?= sort_link('Batch', 'b.batch_name', $sortField, $sortDir) ?></th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($group['records'] as $row): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars(trim($row['first_name'] . ' ' . $row['last_name'])) ?></td>
                                                    <td><?= htmlspecialchars($row['studio_name']) ?></td>
                                                    <td><?= htmlspecialchars($row['batch_name']) ?></td>
                                                    <td>
                                                        <?php if ($row['absent']): ?>
                                                            <span class="badge bg-danger">Absent</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-success">Present</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Existing table code for filter type 2 -->
                            <table class="table table-bordered table-striped rounded-3 shadow-sm align-middle">
                                <thead>
                                    <tr>
                                        <th><?= sort_link('Date', 'a.attendance_date', $sortField, $sortDir) ?></th>
                                        <th><?= sort_link('Name', 'c.first_name', $sortField, $sortDir) ?></th>
                                        <th><?= sort_link('Studio', 's.studio_name', $sortField, $sortDir) ?></th>
                                        <th><?= sort_link('Batch', 'b.batch_name', $sortField, $sortDir) ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attendanceData as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['attendance_date']) ?></td>
                                            <td><?= htmlspecialchars(trim($row['first_name'] . ' ' . $row['last_name'])) ?>
                                                <?php if (isset($row['absent']) && $row['absent']): ?>
                                                    <span class="badge bg-danger ms-2">Absent</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($row['studio_name']) ?></td>
                                            <td><?= htmlspecialchars($row['batch_name']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                <?php elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && ($startDate && $endDate)): ?>
                    <div class="alert alert-warning mt-3">No attendance records found for the selected filters.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        function toggleFilters() {
            var filterType = document.getElementById('filter_type').value;
            document.getElementById('studioBatchFilters').style.display = (filterType === '1') ? '' : 'none';
            document.getElementById('nameSearchFilter').style.display = (filterType === '2') ? '' : 'none';
        }

        function updateDateFormat(input) {
            if (!input.value) return;
            const parts = input.value.split('-');
            if (parts.length === 3) {
                const displayDate = `${parts[2]}/${parts[1]}/${parts[0]}`;
                input.setAttribute('data-display-value', displayDate);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const dateInputs = document.querySelectorAll('input[type="date"]');
            dateInputs.forEach(updateDateFormat);
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<?php include 'includes/footer.php'; ?>