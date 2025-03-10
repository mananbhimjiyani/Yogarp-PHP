<?php
require_once 'db.php'; // Include database connection
// Check if the user is logged in by verifying a session variable, such as 'user_id'

if (isset($_GET['studio_id']) && isset($_GET['Scheduledate'])) {
    $studio_id = $_GET['studio_id'];
    $Scheduledate = $_GET['Scheduledate'];

    // Check if a schedule already exists for the given studio_id and Scheduledate
    $checkQuery = "SELECT s.*, st.studio_name FROM studio_schedule s
                   JOIN studio st ON s.studio_id = st.studio_id 
                   WHERE s.studio_id = ? AND s.Scheduledate = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("is", $studio_id, $Scheduledate);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    // If a schedule exists, alert the user
    if ($checkResult->num_rows > 0) {
        $scheduleData = $checkResult->fetch_assoc();
		$date = new DateTime($scheduleData['Scheduledate']); // Assuming Scheduledate is in Y-m-d format
        $FormatedScheduledate = $date->format('d/m/Y (l)'); // Formats to "Day, dd/mm/yyyy"
$studioName = htmlspecialchars($scheduleData['studio_name']);
$formattedScheduleDate = htmlspecialchars($FormatedScheduledate);

// Generate the alert message
$alertMessage = "Schedule already uploaded for <strong style='color: red;'>$studioName</strong> for <strong style='color: red;'>$formattedScheduleDate</strong>. Please select another studio.";

// Include modal HTML
echo '
<div id="alertModal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border: 1px solid #ccc; padding: 20px; z-index: 1000;">
    <div id="modalContent">' . $alertMessage . '</div>
    <button id="closeModal" style="margin-top: 10px;"class="btn btn-warning btn-sm">Close</button>
</div>
<div id="overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 999;"></div>

<script>
// Show the modal
document.getElementById("alertModal").style.display = "block";
document.getElementById("overlay").style.display = "block";

// Close the modal on button click
document.getElementById("closeModal").onclick = function() {
    document.getElementById("alertModal").style.display = "none";
    document.getElementById("overlay").style.display = "none";
};
</script>
';
        exit; // Stop further processing
    }

    // Fetch batch ids and names associated with the provided studio_id
    $batchQuery = "SELECT b.batch_id, b.batch_name 
                   FROM batch b
                   INNER JOIN studio_batch sb ON b.batch_id = sb.batch_id
                   WHERE sb.studio_id = ? AND b.active = 1";
    $stmt = $conn->prepare($batchQuery);
    $stmt->bind_param("i", $studio_id);
    $stmt->execute();
    $batchResult = $stmt->get_result();

    // Fetch all available instructors based on the selected date
   $instructorQuery = "
    SELECT a.id, CONCAT(a.title, ' ', a.first_name, IFNULL(CONCAT(' ', a.middle_name), ''), ' ', a.last_name) AS fullname
    FROM applicants a
    WHERE a.id NOT IN (
        SELECT ss.instructor_id 
        FROM studio_schedule ss
        JOIN batch b ON ss.batch_id = b.batch_id
        WHERE ss.Scheduledate = ? 
        AND ss.studio_id != ? 
        AND (
            (b.batch_start_time < ? AND b.batch_end_time > ?)
            OR (b.batch_start_time < ? AND b.batch_end_time > ?)
            OR (b.batch_start_time >= ? AND b.batch_end_time <= ?)
        )
    )";

$instructorStmt = $conn->prepare($instructorQuery);
$instructorStmt->bind_param("sissssss", $Scheduledate, $studio_id, $batch_start_time, $batch_start_time, $batch_end_time, $batch_end_time, $batch_start_time, $batch_end_time);
$instructorStmt->execute();
$instructorResult = $instructorStmt->get_result();

    // Store instructors in an array
    $instructors = [];
    if ($instructorResult->num_rows > 0) {
        while ($instructor = $instructorResult->fetch_assoc()) {
            $instructors[] = $instructor;
        }
    }

    // Output batches with dropdowns for each role
    if ($batchResult->num_rows > 0) {
        while ($batch = $batchResult->fetch_assoc()) {
            echo '<div class="row">';
            // Hidden input to submit batch ID
            echo '<input type="hidden" name="batch_id[]" value="' . htmlspecialchars($batch['batch_id']) . '">';

            // Batch Name
            echo '<div class="col-md-4"><label>' . htmlspecialchars($batch['batch_name']) . '</label></div>';

            // Instructor Dropdown
            echo '<div class="col-md-2">';
            echo '<label for="instructor_' . $batch['batch_id'] . '" class="form-label">Instructor</label>';
            echo '<select name="instructor_' . $batch['batch_id'] . '" class="form-control">';
            echo '<option value="">Select Instructor</option>';
            foreach ($instructors as $instructor) {
                echo '<option value="' . htmlspecialchars($instructor['id']) . '">' . htmlspecialchars($instructor['fullname']) . '</option>';
            }
            echo '</select></div>';

            // Demo Dropdown
            echo '<div class="col-md-2">';
            echo '<label for="demo_' . $batch['batch_id'] . '" class="form-label">Demo</label>';
            echo '<select name="demo_' . $batch['batch_id'] . '" class="form-control">';
            echo '<option value="">Select Instructor</option>';
            foreach ($instructors as $instructor) {
                echo '<option value="' . htmlspecialchars($instructor['id']) . '">' . htmlspecialchars($instructor['fullname']) . '</option>';
            }
            echo '</select></div>';

            // Correction Dropdown
            echo '<div class="col-md-2">';
            echo '<label for="correction_' . $batch['batch_id'] . '" class="form-label">Correction</label>';
            echo '<select name="correction_' . $batch['batch_id'] . '" class="form-control">';
            echo '<option value="">Select Instructor</option>';
            foreach ($instructors as $instructor) {
                echo '<option value="' . htmlspecialchars($instructor['id']) . '">' . htmlspecialchars($instructor['fullname']) . '</option>';
            }
            echo '</select></div>';

            // Support Dropdown
            echo '<div class="col-md-2">';
            echo '<label for="support_' . $batch['batch_id'] . '" class="form-label">Support</label>';
            echo '<select name="support_' . $batch['batch_id'] . '" class="form-control">';
            echo '<option value="">Select Instructor</option>';
            foreach ($instructors as $instructor) {
                echo '<option value="' . htmlspecialchars($instructor['id']) . '">' . htmlspecialchars($instructor['fullname']) . '</option>';
            }
            echo '</select></div>';

            echo '</div><br>';
        }
    } else {
        echo "<p>No batches found for this studio.</p>";
    }

} else {
    echo "<p>Studio ID or Scheduledate not provided.</p>";
}
?>

<script>
// Function to load existing schedule data for editing
function loadBatchData(studioId, scheduledate) {
    // Perform an AJAX request to fetch the existing schedule data
    var xhr = new XMLHttpRequest();
    xhr.open("GET", "get_schedule.php?studio_id=" + studioId + "&Scheduledate=" + scheduledate, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            document.getElementById('batch-list').innerHTML = xhr.responseText;
        } else {
            alert('Error loading schedule data.');
        }
    };
    xhr.send();
}
</script>
