<?php
include '../db.php'; // Database connection file

session_start();

// Get the current user ID (assuming stored in session)
$user_id = $_SESSION['id'];

// Get current time
$current_time = date('H:i:s');

// Fetch batches assigned to the user where start and end time are within current time
$sql = "SELECT b.batch_id, b.batch_name
        FROM batch b
        JOIN instructor_batch_assign iba ON b.batch_id = iba.studio_batch_id
        WHERE iba.instructor_id = ? 
        AND b.batch_start_time <= ? 
        AND b.batch_end_time >= ? 
        AND b.active = 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param('sss', $user_id, $current_time, $current_time);
$stmt->execute();
$batches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle search request
$search_results = [];
if (isset($_POST['search'])) {
    $search_term = '%' . $_POST['search_term'] . '%';
    $sql = "SELECT client_id, CONCAT(first_name, ' ', last_name) AS name
            FROM clients 
            WHERE (client_id LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR mobile_number LIKE ?) 
            AND active = 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssss', $search_term, $search_term, $search_term, $search_term);
    $stmt->execute();
    $search_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Handle batch selection and client selection
$selected_batch_id = $_POST['batch_id'] ?? null;
$selected_client_ids = $_POST['client_ids'] ?? [];

// Fetch clients for the selected batch
$batch_clients = [];
if ($selected_batch_id) {
    $sql = "SELECT c.client_id, CONCAT(c.first_name, ' ', c.last_name) AS name
            FROM clients c
            JOIN client_batch_assign cba ON c.client_id = cba.client_id
            WHERE cba.studio_batch_id = ? 
            AND cba.active = 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $selected_batch_id);
    $stmt->execute();
    $batch_clients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Handle form submission to mark attendance
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_attendance'])) {
    $attendance_date = date('Y-m-d');
    $note = $_POST['note'] ?? '';

    foreach ($selected_client_ids as $client_id) {
        $sql = "INSERT INTO attendance (client_id, studio_batch_id, attendance_date, note)
                VALUES (?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iiss', $client_id, $selected_batch_id, $attendance_date, $note);
        $stmt->execute();
    }

    echo "Attendance marked successfully!";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance</title>
    <!-- Include Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1>Mark Attendance</h1>
        <form method="POST" action="">
            <div class="form-group">
                <label for="batch">Select Batch:</label>
                <select id="batch" name="batch_id" class="form-control" required>
                    <option value="">-- Select Batch --</option>
                    <?php foreach ($batches as $batch): ?>
                        <option value="<?= $batch['batch_id'] ?>"><?= $batch['batch_name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="search">Search Clients:</label>
                <input type="text" id="search" name="search_term" class="form-control" placeholder="Search by ID, Name, or Mobile Number">
                <button type="submit" name="search" class="btn btn-primary mt-2">Search</button>
            </div>

            <?php if (!empty($search_results)): ?>
                <div class="form-group">
                    <label for="search_results">Search Results:</label>
                    <select id="search_results" name="client_ids[]" class="form-control" multiple>
                        <?php foreach ($search_results as $client): ?>
                            <option value="<?= $client['client_id'] ?>"><?= $client['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <?php if (!empty($batch_clients)): ?>
                <div class="form-group">
                    <label for="batch_clients">Batch Clients:</label>
                    <select id="batch_clients" name="client_ids[]" class="form-control" multiple>
                        <?php foreach ($batch_clients as $client): ?>
                            <option value="<?= $client['client_id'] ?>"><?= $client['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="note">Note:</label>
                <textarea id="note" name="note" class="form-control"></textarea>
            </div>

            <button type="submit" name="mark_attendance" class="btn btn-primary">Mark Attendance</button>
        </form>
    </div>

    <!-- Include Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
</body>
</html>
