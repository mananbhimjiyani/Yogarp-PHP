<?php
ob_start();
require_once 'Path.php';
require_once 'db.php';
include 'includes/header.php';
require_once 'includes/batch_functions.php';
// Check if the user is logged in by verifying a session variable, such as 'user_id'
if (!isset($_SESSION['user_id'])) {
    // If user is not logged in, redirect to index.php
    header("Location: login.php");
    exit(); // Ensure no further code is executed after redirection
}
$user_id = $_SESSION['user_id'];
error_reporting(E_ALL);
ini_set('display_errors', 1);

$studioQuery = "SELECT studio_id, studio_name FROM studio";
$studioResult = $conn->query($studioQuery);

$asanaTypeQuery = "SELECT DISTINCT asana_type FROM asana WHERE asana_type IS NOT NULL";
$asanaTypeResult = $conn->query($asanaTypeQuery);

$propsQuery = "SELECT prop_id, prop_name FROM props WHERE active = 1";
$propsResult = $conn->query($propsQuery);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $studio_id = $_POST['studio_id'];
    $asana_type = $_POST['asana_type'];
    $asana_id = $_POST['asana_id'];
    $Scheduledate = $_POST['Scheduledate'];
    $prop_id = $_POST['prop_id'];

    if (isset($_POST['batch_id']) && is_array($_POST['batch_id'])) {
        foreach ($_POST['batch_id'] as $batch_id) {
            $instructor_id = isset($_POST['instructor_' . $batch_id]) ? $_POST['instructor_' . $batch_id] : null;
            $demo_instructor_id = isset($_POST['demo_' . $batch_id]) ? $_POST['demo_' . $batch_id] : null;
            $correction_instructor_id = isset($_POST['correction_' . $batch_id]) ? $_POST['correction_' . $batch_id] : null;
            $support_instructor_id = isset($_POST['support_' . $batch_id]) ? $_POST['support_' . $batch_id] : null;

            // Prepare the insert query
            $insertQuery = "
                INSERT INTO studio_schedule 
                (studio_id, asana_type, asana_id, Scheduledate, prop_id, instructor_id, demo_instructor_id, correction_instructor_id, support_instructor_id, batch_id, user_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($insertQuery);
            
            if ($stmt === false) {
                die('Prepare failed: ' . htmlspecialchars($conn->error));
            }

            // Bind parameters
$stmt->bind_param("iiissiiiiii", $studio_id,  $batch_id, $asana_id, $asana_type, $Scheduledate, $prop_id, $instructor_id, $demo_instructor_id, $correction_instructor_id, $support_instructor_id,  $user_id);
            
            if (!$stmt->execute()) {
                echo "Error inserting record: " . htmlspecialchars($stmt->error);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Studio Schedule</title>
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</head>
<body>
<div class="container">
    <h2 class="text-center">Studio Class Schedule</h2>
    <form method="POST" action="schedule.php">
<div class="row">
    <div class="col-md-4">
        <label for="asana_type" class="form-label"><span style="color:red">* </span>Asana Type</label>
        <select name="asana_type" id="asana_type" class="form-control" onchange="fetchAsanas(this.value)" required>
            <option value="">Select Asana Type</option>
            <?php while ($row = $asanaTypeResult->fetch_assoc()): ?>
                <option value="<?= htmlspecialchars($row['asana_type']); ?>">
                    <?= htmlspecialchars($row['asana_type']); ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>
    <div class="col-md-4">
        <label for="asana_id" class="form-label"><span style="color:red">* </span>Asana Name</label>
        <select name="asana_id" id="asana_id" class="form-control" required>
            <option value="">Select Asana</option>
        </select>
    </div>
    <div class="col-md-4">
        <label for="prop_name" class="form-label"><span style="color:red">* </span>Props</label>
        <select name="prop_id" class="form-control" required>
            <option value="">Select Prop</option>
            <?php while ($prop = $propsResult->fetch_assoc()): ?>
                <option value="<?= $prop['prop_id']; ?>"><?= htmlspecialchars($prop['prop_name']); ?></option>
            <?php endwhile; ?>
        </select>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <label for="Scheduledate" class="form-label"><span style="color:red">* </span>Date</label>
        <input type="date" name="Scheduledate" id="Scheduledate" class="form-control" onchange="loadAvailableInstructors()" required>
    </div>
    <div class="col-md-6">
        <label for="studio_id" class="form-label"><span style="color:red">* </span>Studio Name</label>
        <select name="studio_id" id="studio_id" class="form-control" onchange="loadAvailableInstructors()" required>
            <option value="">Select Studio</option>
            <?php while ($studio = $studioResult->fetch_assoc()): ?>
                <option value="<?= $studio['studio_id']; ?>"><?= htmlspecialchars($studio['studio_name']); ?></option>
            <?php endwhile; ?>
        </select>
    </div>
</div>
        <div id="batch-list"></div>
        <div class="row">
            <div class="col-md-12 text-center mt-3">
                <button type="submit" class="btn btn-primary">Submit Schedule</button>
            </div>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function fetchAsanas(asanaType) {
    if (asanaType) {
        $.get("fetch_asanas.php?asana_type=" + encodeURIComponent(asanaType), function(response) {
            var asanaSelect = $("#asana_id");
            asanaSelect.empty().append('<option value="">Select Asana</option>');
            $.each(response, function(index, asana) {
                asanaSelect.append('<option value="' + asana.asana_id + '">' + asana.asana_name + '</option>');
            });
        }, "json");
    } else {
        $("#asana_id").html('<option value="">Select Asana</option>');
    }
}

function loadAvailableInstructors() {
    const studio_id = document.getElementById('studio_id').value;
    const Scheduledate = document.getElementById('Scheduledate').value;

    if (studio_id && Scheduledate) {
        $.ajax({
            url: 'fetch_available_instructors.php',
            type: 'GET',
            data: { studio_id: studio_id, Scheduledate: Scheduledate },
            success: function(response) {
                $('#batch-list').html(response);
            },
            error: function(xhr, status, error) {
                console.error("Error fetching instructors:", status, error);
                $('#batch-list').html("<p>Error loading available instructors.</p>");
            }
        });
    }
}
</script>
<script>
    // Get the date input field
    var dateField = document.getElementById('Scheduledate');
    
    // Create a new Date object for today
    var today = new Date();
    
    // Set minimum date to today (in 'YYYY-MM-DD' format)
    var minDate = today.toISOString().split('T')[0];
    
    // Create a new Date object for tomorrow
    var tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    
    // Set default date to tomorrow (in 'YYYY-MM-DD' format)
    var defaultDate = tomorrow.toISOString().split('T')[0];
    
    // Set the attributes of the date input field
    dateField.setAttribute('min', minDate);
    dateField.setAttribute('value', defaultDate);
</script>
</body>
</html>
