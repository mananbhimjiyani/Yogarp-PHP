<?php
require_once 'Path.php';
require_once 'db.php';
include 'includes/header.php';
date_default_timezone_set('Asia/Kolkata');
if (session_status() === PHP_SESSION_NONE) {
    session_start();}
// Fetch and display client attendance
$instructor_id = $_SESSION['user_id'];

// Assuming `user_id` is stored in session
$user_id = $_SESSION['user_id'] ?? null;

// Check if user_id is set
if (!$user_id) {
    die("User not logged in.");
}

// SQL query with error handling
$query = "
    SELECT 
        s.studio_name,
        b.batch_name,
        CASE
            WHEN ss.instructor_id = ? THEN 'Instructor'
            WHEN ss.demo_instructor_id = ? THEN 'Demo Instructor'
            WHEN ss.correction_instructor_id = ? THEN 'Correction Instructor'
            WHEN ss.support_instructor_id = ? THEN 'Support Instructor'
            ELSE 'Unknown Role'
        END AS role
    FROM 
        studio_schedule ss
    JOIN 
        studio s ON ss.studio_id = s.studio_id
    JOIN 
        batch b ON ss.batch_id = b.batch_id
    WHERE 
        (
            ss.instructor_id = ? 
            OR ss.demo_instructor_id = ? 
            OR ss.correction_instructor_id = ? 
            OR ss.support_instructor_id = ?
        )
        AND NOW() BETWEEN b.batch_start_time AND b.batch_end_time
        AND ss.Scheduledate = CURDATE()
";
$stmt = $conn->prepare($query);

// Check if query preparation was successful
if (!$stmt) {
    die("Query preparation failed: " . $conn->error);
}

// Bind parameters and execute query
$stmt->bind_param("iiiiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Attendance</title>
   
</head>
<body>

<div class="container mt-5">
    <h2 class="text-center mb-4">Attendance</h2>
    
    <?php if ($result && $result->num_rows > 0): ?>
        <!-- Row for column headers -->
        <div class="row font-weight-bold">
            <div class="col-md-4">Studio Name</div>
            <div class="col-md-4">Batch Name</div>
            <div class="col-md-4">Role</div>
        </div>

        <!-- Row for each result -->
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="row border-top py-2">
                <div class="col-md-4"><?= htmlspecialchars($row['studio_name']) ?></div>
                <div class="col-md-4"><?= htmlspecialchars($row['batch_name']) ?></div>
                <div class="col-md-4"><?= htmlspecialchars($row['role']) ?></div>
            </div>
        <?php endwhile; ?>

    <?php else: ?>
        <div class="row">
            <div class="col-12">
                <p class="text-center">No active batches found for your roles.</p>
            </div>
        </div>
    <?php endif; ?>

</div>

</body>
</html>

<?php
// Close database connection
$stmt->close();
$conn->close();
include 'includes/footer.php';
?>