<?php
ob_start();
require_once 'Path.php'; // Include the path file for constants
require_once 'db.php'; // Include database connection
include 'includes/header.php';
// Check if the user is logged in by verifying a session variable, such as 'user_id'
if (!isset($_SESSION['user_id'])) {
    // If user is not logged in, redirect to index.php
    header("Location: login.php");
    exit(); // Ensure no further code is executed after redirection
}
// Enable error reporting for debugging
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Get date range from user input with today's date as default
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get selected filters
$selectedInstructor = isset($_GET['instructor']) ? $_GET['instructor'] : '';
$selectedStudio = isset($_GET['studio']) ? $_GET['studio'] : '';
$selectedBatch = isset($_GET['batch']) ? $_GET['batch'] : '';

// Prepare the SQL query for dropdown options
$instructorQuery = "SELECT id, CONCAT(title, ' ', first_name, ' ', middle_name, ' ', last_name) AS fullname FROM applicants";
$studioQuery = "SELECT studio_id, studio_name FROM studio";
$batchQuery = "SELECT batch_id, batch_name FROM batch";

$instructors = $conn->query($instructorQuery);
$studios = $conn->query($studioQuery);
$batches = $conn->query($batchQuery);

// Prepare the SQL query with sorting and filtering
$query = "
    SELECT ss.schedule_id, 
           ss.Scheduledate, 
           ss.studio_id, 
           s.studio_name, 
           ss.asana_type, 
           a.asana_name, 
           ss.prop_id, 
           p.prop_name, 
           ss.instructor_id, 
           ss.demo_instructor_id, 
           ss.correction_instructor_id, 
           ss.support_instructor_id, 
           b.batch_name, 
           CONCAT(u.title, ' ', u.first_name, ' ', u.middle_name, ' ', u.last_name) AS instructor_fullname, 
           CONCAT(ud.title, ' ', ud.first_name, ' ', ud.middle_name, ' ', ud.last_name) AS demo_instructor_fullname, 
           CONCAT(uc.title, ' ', uc.first_name, ' ', uc.middle_name, ' ', uc.last_name) AS correction_instructor_fullname, 
           CONCAT(us.title, ' ', us.first_name, ' ', us.middle_name, ' ', us.last_name) AS support_instructor_fullname
    FROM studio_schedule ss
    INNER JOIN studio s ON ss.studio_id = s.studio_id
    INNER JOIN props p ON ss.prop_id = p.prop_id
    INNER JOIN batch b ON ss.batch_id = b.batch_id
    LEFT JOIN applicants u ON ss.instructor_id = u.id
    LEFT JOIN applicants ud ON ss.demo_instructor_id = ud.id
    LEFT JOIN applicants uc ON ss.correction_instructor_id = uc.id
    LEFT JOIN applicants us ON ss.support_instructor_id = us.id
    LEFT JOIN asana a ON ss.asana_id = a.asana_id
    WHERE ss.Scheduledate BETWEEN ? AND ?";

// Add filtering based on selected options
if ($selectedInstructor) {
    $query .= " AND ss.instructor_id = ?";
}
if ($selectedStudio) {
    $query .= " AND ss.studio_id = ?";
}
if ($selectedBatch) {
    $query .= " AND ss.batch_id = ?";
}

$query .= " ORDER BY instructor_fullname, demo_instructor_fullname, correction_instructor_fullname, 
             support_instructor_fullname, b.batch_name, s.studio_name";

$stmt = $conn->prepare($query);
if ($stmt === false) {
    die('Prepare failed: ' . htmlspecialchars($conn->error));
}

// Bind parameters
$params = [$startDate, $endDate];
if ($selectedInstructor) {
    $params[] = $selectedInstructor;
}
if ($selectedStudio) {
    $params[] = $selectedStudio;
}
if ($selectedBatch) {
    $params[] = $selectedBatch;
}

// Create a dynamic binding
$stmt->bind_param(str_repeat('s', count($params)), ...$params);

// Execute the statement
$stmt->execute();

// Fetch results
$result = $stmt->get_result();
$schedules = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>View Studio Schedule</title>
</head>
<body>
<h2 class="text-center">Studio Schedule</h2>
<div class="container mt-5">
    <h2>Filter by Date Range</h2>
    <form method="GET" action="">
        <div class="row">
            <div class="col-md-5">
                <label for="start_date">Start Date:</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>" required>
            </div>
            <div class="col-md-5">
                <label for="end_date">End Date:</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>" required>
            </div>
            <div class="col-md-2">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary form-control">Filter</button>
            </div>
        </div>
    </form>

    <h2>Filter by Instructor, Studio, and Batch</h2>
    <form method="GET" action="">
        <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
        <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
        <div class="row">
            <div class="col-md-4">
                <label for="instructor">Instructor:</label>
                <select class="form-control" name="instructor">
                    <option value="">Select Instructor</option>
                    <?php while ($row = $instructors->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($row['id']); ?>" <?php echo ($row['id'] == $selectedInstructor) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($row['fullname']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="studio">Studio:</label>
                <select class="form-control" name="studio">
                    <option value="">Select Studio</option>
                    <?php while ($row = $studios->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($row['studio_id']); ?>" <?php echo ($row['studio_id'] == $selectedStudio) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($row['studio_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="batch">Batch:</label>
                <select class="form-control" name="batch">
                    <option value="">Select Batch</option>
                    <?php while ($row = $batches->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($row['batch_id']); ?>" <?php echo ($row['batch_id'] == $selectedBatch) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($row['batch_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </div>
    </form>
</div>

<div class="container mt-5">
    <?php if ($result->num_rows > 0): ?>
        <?php
        $currentStudio = '';
        $currentDate = '';
        $studioData = []; // Initialize an array to store studio data grouped by date

        // Group schedules by studio and date
 // Group schedules by date first
foreach ($schedules as $row) {
    $studioData[$row['Scheduledate']][$row['studio_name']][] = $row;
}

// Display the grouped schedules
foreach ($studioData as $date => $studios) {
   
    // Display date
    $formattedDate = DateTime::createFromFormat('Y-m-d', $date)->format('d/m/Y (l)');
	
  
echo '<div class="container mt-5" style="border-bottom: 5px solid #000; border-right: 5px solid #000; border-left: 5px solid #000;border-radius: 7px;">';
    foreach ($studios as $studioName => $schedules) {
		 echo '<div class="row" style="margin-bottom: 20px">';
        echo '<div class="col-md-6" style="border-bottom: 5px solid #000;border-top: 5px solid #000; border-right: 1px solid #000;"><h4>Studio: ' . htmlspecialchars($studioName) . '</h4></div>';
		 echo '<div class="col-md-6" style="border-bottom: 5px solid #000;border-top: 5px solid #000;"><h4>Date: ' . htmlspecialchars($formattedDate) . '</h4></div>';
         echo '</div>';
        // Display headers for schedule details
        echo '<div class="row" style="margin-bottom: 20px">';
        echo '<div class="col-md-4" style="border: 1px solid #ccc;"><strong>Asana Type:</strong> ' . htmlspecialchars($schedules[0]['asana_type'] ?? 'N/A') . '</div>';
        echo '<div class="col-md-4" style="border: 1px solid #ccc;"><strong>Asana Name:</strong> ' . htmlspecialchars($schedules[0]['asana_name'] ?? 'N/A') . '</div>';
        echo '<div class="col-md-4" style="border: 1px solid #ccc;"><strong>Props:</strong> ' . htmlspecialchars($schedules[0]['prop_name'] ?? 'N/A') . '</div>';
        echo '</div>';
        
        // Display the schedule
        echo '<div class="row">';
        echo '<div class="col-md-4 text-center" style="border: 1px solid #ccc; padding: 10px;"><strong>Batch</strong></div>';
        echo '<div class="col-md-2 text-center" style="border: 1px solid #ccc; padding: 10px;"><strong>Instructor</strong></div>';
        echo '<div class="col-md-2 text-center" style="border: 1px solid #ccc; padding: 10px;"><strong>Demo</strong></div>';
        echo '<div class="col-md-2 text-center" style="border: 1px solid #ccc; padding: 10px;"><strong>Correction</strong></div>';
        echo '<div class="col-md-2 text-center" style="border: 1px solid #ccc; padding: 10px;"><strong>Support</strong></div>';
        echo '</div>';

        // Display all schedules for this studio
        foreach ($schedules as $schedule) {
            echo '<div class="row" style="margin-bottom: 0;">'; // Set margin-bottom to 0
            echo '<div class="col-md-4" style="border: 1px solid #ccc; padding: 10px;">';
            echo htmlspecialchars($schedule['batch_name']) . '</div>';
            echo '<div class="col-md-2" style="border: 1px solid #ccc; padding: 10px;">';
            echo htmlspecialchars($schedule['instructor_fullname'] ?? 'N/A') . '</div>';
            echo '<div class="col-md-2" style="border: 1px solid #ccc; padding: 10px;">';
            echo htmlspecialchars($schedule['demo_instructor_fullname'] ?? 'N/A') . '</div>';
            echo '<div class="col-md-2" style="border: 1px solid #ccc; padding: 10px;">';
            echo htmlspecialchars($schedule['correction_instructor_fullname'] ?? 'N/A') . '</div>';
            echo '<div class="col-md-2" style="border: 1px solid #ccc; padding: 10px;">';
            echo htmlspecialchars($schedule['support_instructor_fullname'] ?? 'N/A') . '</div>';
            echo '</div>';
            echo '<hr style="margin: 0;">'; // Remove margin from the separator
        }
    }
    echo '</div>'; // Close the main container for this date

        }
        ?>
    <?php else: ?>
        <p>No schedules found for the selected filters.</p>
    <?php endif; ?>
</div>


<?php
$stmt->close();
$conn->close();
ob_end_flush();
include 'includes/footer.php'; // Include footer file
?>
