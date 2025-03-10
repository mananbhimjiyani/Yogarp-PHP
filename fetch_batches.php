<?php
require_once 'db.php'; // Include database connection
// Check if the user is logged in by verifying a session variable, such as 'user_id'
if (!isset($_SESSION['user_id'])) {
    // If user is not logged in, redirect to index.php
    header("Location: login.php");
    exit(); // Ensure no further code is executed after redirection
}
if (isset($_GET['studio_id'])) {
    $studio_id = $_GET['studio_id'];

    // Fetch batch ids from studio_batch based on studio_id
    $batchQuery = "SELECT b.batch_id, b.batch_name 
                   FROM batch b
                   INNER JOIN studio_batch sb ON b.batch_id = sb.batch_id
                   WHERE sb.studio_id = ? AND b.active = 1";
    $stmt = $conn->prepare($batchQuery);
    $stmt->bind_param("i", $studio_id);
    $stmt->execute();
    $batchResult = $stmt->get_result();

    // Fetch instructors again (for reuse in dropdowns)
    $instructorQuery = "SELECT id, 
                               CONCAT(title, ' ', first_name, ' ', middle_name, ' ', last_name) AS fullname 
                        FROM applicants";
    $instructorResult = $conn->query($instructorQuery);

    $instructors = [];
    if ($instructorResult->num_rows > 0) {
        while ($instructor = $instructorResult->fetch_assoc()) {
            $instructors[] = $instructor;
        }
    }

    // Output batches
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
}

?>
