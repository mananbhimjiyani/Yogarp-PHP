<?php
ob_start();
require_once 'Path.php'; // Include the path file for constants
require_once 'db.php'; // Include database connection
include 'includes/header.php';
require_once 'includes/batch_functions.php'; // Include the function to get batches and studios

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$user_id = $_SESSION['user_id'];

// Fetch existing schedule data if edit is requested
$scheduleData = null;
if (isset($_GET['schedule_id'])) {
    $schedule_id = $_GET['schedule_id'];
    
    // Fetch existing schedule data
    $scheduleQuery = "
        SELECT ss.schedule_id, ss.studio_id, s.studio_name, ss.asana_type, 
               a.asana_name, p.prop_name, ss.Scheduledate, 
               ss.batch_id, ss.instructor_id, ss.demo_instructor_id, 
               ss.correction_instructor_id, ss.support_instructor_id, 
               CONCAT(i.title, ' ', i.first_name, ' ', i.middle_name, ' ', i.last_name) AS instructor_name, 
               CONCAT(di.title, ' ', di.first_name, ' ', di.middle_name, ' ', di.last_name) AS demo_instructor_name, 
               CONCAT(ci.title, ' ', ci.first_name, ' ', ci.middle_name, ' ', ci.last_name) AS correction_instructor_name, 
               CONCAT(si.title, ' ', si.first_name, ' ', si.middle_name, ' ', si.last_name) AS support_instructor_name 
        FROM studio_schedule ss 
        LEFT JOIN studio s ON ss.studio_id = s.studio_id 
        LEFT JOIN asana a ON ss.asana_name = a.asana_name 
        LEFT JOIN props p ON ss.prop_id = p.prop_id 
        LEFT JOIN applicants i ON ss.instructor_id = i.id 
        LEFT JOIN applicants di ON ss.demo_instructor_id = di.id 
        LEFT JOIN applicants ci ON ss.correction_instructor_id = ci.id 
        LEFT JOIN applicants si ON ss.support_instructor_id = si.id 
        WHERE ss.schedule_id = ?";
    
    $stmt = $conn->prepare($scheduleQuery);
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    $scheduleResult = $stmt->get_result();

    if ($scheduleResult->num_rows > 0) {
        $scheduleData = $scheduleResult->fetch_assoc();
    }
}

// Fetch studios
$studioQuery = "SELECT studio_id, studio_name FROM studio";
$studioResult = $conn->query($studioQuery);

// Fetch props
$propsQuery = "SELECT prop_id, prop_name FROM props";
$propsResult = $conn->query($propsQuery);

// Fetch asana types and names
$asanaQuery = "SELECT asana_name, asana_type FROM asana";
$asanaResult = $conn->query($asanaQuery);

// Fetch props
$propsQuery = "SELECT prop_id, prop_name FROM props WHERE active = 1";
$propsResult = $conn->query($propsQuery);

// Fetch instructors (all applicants)
$instructorQuery = "SELECT id, CONCAT(title, ' ', first_name, ' ', middle_name, ' ', last_name) AS full_name FROM applicants";
$instructorResult = $conn->query($instructorQuery);

// Fetch demo instructors (all applicants)
$demoInstructorQuery = "SELECT id, CONCAT(title, ' ', first_name, ' ', middle_name, ' ', last_name) AS full_name FROM applicants";
$demoInstructorResult = $conn->query($demoInstructorQuery);

// Fetch correction instructors (all applicants)
$correctionInstructorQuery = "SELECT id, CONCAT(title, ' ', first_name, ' ', middle_name, ' ', last_name) AS full_name FROM applicants";
$correctionInstructorResult = $conn->query($correctionInstructorQuery);

// Fetch support instructors (all applicants)
$supportInstructorQuery = "SELECT id, CONCAT(title, ' ', first_name, ' ', middle_name, ' ', last_name) AS full_name FROM applicants";
$supportInstructorResult = $conn->query($supportInstructorQuery);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Schedule</title>
    <!-- Include Bootstrap CSS or any other CSS you need -->
</head>
<body>
    <div class="container">
        <h2>Edit Schedule</h2>

        <!-- Schedule Edit Form -->
        <form method="POST" action="schedule.php">
            <input type="hidden" name="schedule_id" value="<?php echo $scheduleData['schedule_id'] ?? ''; ?>">
            
            <div class="row">
                <div class="col-md-6">
                    <label for="Scheduledate" class="form-label">Date</label>
                    <input type="date" name="Scheduledate" value="<?php echo htmlspecialchars($scheduleData['Scheduledate'] ?? ''); ?>" class="form-control" readonly>
                </div>
                <div class="col-md-6">
                    <label for="studio_name" class="form-label">Studio Name</label>
                    <input type="text" name="studio_name" value="<?php echo htmlspecialchars($scheduleData['studio_name'] ?? ''); ?>" class="form-control" readonly>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <label for="asana_type" class="form-label">Asana Type</label>
                    <select name="asana_type" class="form-control">
                        <option value="">Select Asana Type</option>
                        <?php
                        while ($asana = $asanaResult->fetch_assoc()) {
                            $selected = ($asana['asana_type'] == ($scheduleData['asana_type'] ?? '')) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($asana['asana_type']) . '" ' . $selected . '>' . htmlspecialchars($asana['asana_type']) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="asana_name" class="form-label">Asana Name</label>
                    <select name="asana_name" class="form-control">
                        <option value="">Select Asana Name</option>
                        <?php
                        while ($asana = $asanaResult->fetch_assoc()) {
                            $selected = ($asana['asana_name'] == ($scheduleData['asana_name'] ?? '')) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($asana['asana_name']) . '" ' . $selected . '>' . htmlspecialchars($asana['asana_name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="prop_name" class="form-label">Props</label>
                    <select name="prop_name" class="form-control">
                        <option value="">Select Prop</option>
                        <?php
                        while ($prop = $propsResult->fetch_assoc()) {
                            $selected = ($prop['prop_name'] == ($scheduleData['prop_name'] ?? '')) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($prop['prop_name']) . '" ' . $selected . '>' . htmlspecialchars($prop['prop_name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <label for="instructor_id" class="form-label">Instructor</label>
                    <select name="instructor_id" class="form-control">
                        <option value="">Select Instructor</option>
                        <?php
                        while ($instructor = $instructorResult->fetch_assoc()) {
                            $selected = ($instructor['id'] == ($scheduleData['instructor_id'] ?? '')) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($instructor['id']) . '" ' . $selected . '>' . htmlspecialchars($instructor['full_name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="demo_instructor_id" class="form-label">Demo Instructor</label>
                    <select name="demo_instructor_id" class="form-control">
                        <option value="">Select Demo Instructor</option>
                        <?php
                        while ($demoInstructor = $demoInstructorResult->fetch_assoc()) {
                            $selected = ($demoInstructor['id'] == ($scheduleData['demo_instructor_id'] ?? '')) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($demoInstructor['id']) . '" ' . $selected . '>' . htmlspecialchars($demoInstructor['full_name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="correction_instructor_id" class="form-label">Correction Instructor</label>
                    <select name="correction_instructor_id" class="form-control">
                        <option value="">Select Correction Instructor</option>
                        <?php
                        while ($correctionInstructor = $correctionInstructorResult->fetch_assoc()) {
                            $selected = ($correctionInstructor['id'] == ($scheduleData['correction_instructor_id'] ?? '')) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($correctionInstructor['id']) . '" ' . $selected . '>' . htmlspecialchars($correctionInstructor['full_name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="support_instructor_id" class="form-label">Support Instructor</label>
                    <select name="support_instructor_id" class="form-control">
                        <option value="">Select Support Instructor</option>
                        <?php
                        while ($supportInstructor = $supportInstructorResult->fetch_assoc()) {
                            $selected = ($supportInstructor['id'] == ($scheduleData['support_instructor_id'] ?? '')) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($supportInstructor['id']) . '" ' . $selected . '>' . htmlspecialchars($supportInstructor['full_name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
            
            <div id="batchesContainer"></div>
            <button type="submit" class="btn btn-success">Update Schedule</button>
        </form>
    </div>

    <script>
    function loadBatches(studioId) {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', 'fetch_batches.php?studio_id=' + studioId, true);
        xhr.onload = function() {
            if (this.status === 200) {
                document.getElementById('batchesContainer').innerHTML = this.responseText;
            }
        };
        xhr.send();
    }

    // Load batches when the page loads with the selected studio
    window.onload = function() {
        loadBatches(<?php echo $scheduleData['studio_id'] ?? 'null'; ?>);
    };
    </script>
</body>
</html>
