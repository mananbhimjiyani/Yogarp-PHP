<?php
require_once 'db.php'; // Include database connection

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    echo "Unauthorized access!";
    exit;
}

// Fetch parameters
$user_id = $_SESSION['user_id'];

$query = "SELECT ss.schedule_id, s.studio_name, ss.asana_type, a.fullname AS instructor_name, ss.batch_id, ss.Scheduledate
          FROM studio_schedule ss
          JOIN studio s ON ss.studio_id = s.studio_id
          JOIN applicants a ON ss.instructor_id = a.id
          WHERE ss.user_id = ?";

// Apply filters from GET parameters (instructor_id, studio_id, batch_id, date range)
$params = [$user_id]; // Default user_id for security

// Handle multiple instructor IDs
if (isset($_GET['instructor_id']) && !empty($_GET['instructor_id'])) {
    $instructor_ids = implode(',', array_fill(0, count($_GET['instructor_id']), '?'));
    $query .= " AND ss.instructor_id IN ($instructor_ids)";
    $params = array_merge($params, $_GET['instructor_id']);
}

// Handle multiple studio IDs
if (isset($_GET['studio_id']) && !empty($_GET['studio_id'])) {
    $studio_ids = implode(',', array_fill(0, count($_GET['studio_id']), '?'));
    $query .= " AND ss.studio_id IN ($studio_ids)";
    $params = array_merge($params, $_GET['studio_id']);
}

// Handle batch ID
if (isset($_GET['batch_id']) && !empty($_GET['batch_id'])) {
    // You may want to handle this as a separate multiple selection if necessary
    $query .= " AND ss.batch_id = ?";
    $params[] = $_GET['batch_id'];
}

// Handle date range
if (isset($_GET['start_date']) && !empty($_GET['start_date']) && isset($_GET['end_date']) && !empty($_GET['end_date'])) {
    $query .= " AND ss.Scheduledate BETWEEN ? AND ?";
    $params[] = $_GET['start_date'];
    $params[] = $_GET['end_date'];
}

// Prepare and execute the query
$stmt = $conn->prepare($query);
$stmt->bind_param(str_repeat('s', count($params)), ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Generate the HTML table rows dynamically
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['studio_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['asana_type']) . "</td>";
    echo "<td>" . htmlspecialchars($row['instructor_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['batch_id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Scheduledate']) . "</td>";
    echo "<td><button class='btn btn-sm btn-warning edit-btn' data-id='" . $row['schedule_id'] . "'>Edit</button> ";
    echo "<button class='btn btn-sm btn-danger delete-btn' data-id='" . $row['schedule_id'] . "'>Delete</button></td>";
    echo "</tr>";
}
?>
