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
function formatTime($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $seconds = $seconds % 60;
    return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
}
$asanaResult = $conn->query("SELECT asana_id, sname, asana_name, asana_type, asana_subtype, asana_length FROM asana");

// Capture and display selected asana details after form submission
$selectedAsana = null;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['asana_id'])) {
    $selectedAsanaId = $_POST['asana_id'];
    
    // Fetch the selected asana details
    $selectedAsanaResult = $conn->query("SELECT * FROM asana WHERE asana_id = '$selectedAsanaId'");
    $selectedAsana = $selectedAsanaResult->fetch_assoc();
}
// Initialize variables
$totalTime = 0; // Total time accumulator


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_id']) && $_POST['form_id'] === 'SequenceInsert') {
    // Get the sequence name from the form
    $sequenceName = $_POST['sequence_name'];
    
    // Decode the asana list JSON data
    $asanaList = isset($_POST['asana_list']) ? json_decode($_POST['asana_list'], true) : [];

    // Check if asana list and sequence name are valid
    if (empty($asanaList) || empty($sequenceName)) {
        echo "Invalid data. Please provide a sequence name and at least one asana.";
        exit;
    }

    // Start transaction to ensure data consistency
    $conn->begin_transaction();

    try {
        // Insert into asana_sequence_name table to create a new sequence
        $stmt = $conn->prepare("INSERT INTO asana_sequence_name (sequence_name) VALUES (?)");
        $stmt->bind_param("s", $sequenceName);
        $stmt->execute();
        $sequenceId = $stmt->insert_id; // Get the ID of the new sequence
        $stmt->close();

        // Insert each asana into asana_sequence table
        $stmt = $conn->prepare("
            INSERT INTO asana_sequence (sequence_id, asana_id, asana_name, type, subtype, time, remarks) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($asanaList as $asana) {
            $stmt->bind_param(
                "iisssis", 
                $sequenceId,
                $asana['asana_id'],
                $asana['asana_name'],
                $asana['type'],
                $asana['subtype'],
                $asana['asana_length'],
                $asana['remarks']
            );
            $stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        $stmt->close();

        echo "Asana sequence added successfully.";

    } catch (Exception $e) {
        // Roll back transaction if something went wrong
        $conn->rollback();
        echo "Failed to insert asana sequence: " . $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asana Schedule</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Blinking red text for remaining minutes */
        .remaining-time {
            color: red;
            font-weight: bold;
            animation: blinker 1s linear infinite;
        }
        @keyframes blinker {
            50% { opacity: 0; }
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <form method="POST" id="asana-form">

        <div class="row">
            <h4>Add Asana to Sequence</h4>

            <!-- Input Form -->
            <div class="col-md-2">
                <label for="asana_id" class="form-label">Asana Name</label>
                <select class="form-select" id="asana_id" name="asana_id" required onchange="fillAsanaDetails()">
                    <option value="">Select Asana</option>
                    <?php while ($asana = $asanaResult->fetch_assoc()): ?>
                        <?php if ($asana): // Ensure $asana is not null ?>
                            <option value="<?= htmlspecialchars($asana['asana_id']) ?>"
                                    data-asana-name="<?= htmlspecialchars($asana['asana_name']) ?>"
                                    data-type="<?= htmlspecialchars($asana['asana_type']) ?>"
                                    data-subtype="<?= htmlspecialchars($asana['asana_subtype']) ?>"
                                    data-asana-length="<?= htmlspecialchars($asana['asana_length']) ?>">
                                <?= htmlspecialchars($asana['asana_name']) ?>
                            </option>
                        <?php else: ?>
                            <option value="">No Asanas available</option>
                        <?php endif; ?>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="type" class="form-label">Type</label>
                <input type="text" class="form-control" id="type" name="type" readonly>
            </div>
            <div class="col-md-2">
                <label for="subtype" class="form-label">Subtype</label>
                <input type="text" class="form-control" id="subtype" name="subtype" readonly>
            </div>
            <div class="col-md-2">
                <label for="time" class="form-label">Time (HH:MM:SS)</label>
                <input type="text" class="form-control" id="time" name="time" readonly>
            </div>
			
            <div class="col-md-2">
                <label for="remarks" class="form-label">Remarks</label>
                <input type="text" class="form-control" id="remarks" name="remarks">
            </div>
            <div class="col-md-2">
                <label for="remarks" class="form-label">&nbsp;</label><br>
                <button type="button" class="btn btn-primary" onclick="addAsana()">+</button>
            </div>
        </div>
        <input type="hidden" name="asana_list" id="asana_list">

        <div class="col-md-12">
            <p>Remaining Time: <span id="remaining-time" class="remaining-time">55 minutes</span></p>
        </div>
    </form>

    <form method="POST" action="asana_schedule.php" id="SequenceInsert">
        <!-- Display Temporary Asana List -->
        <div class="col-md-12">
            <h4>Asana List</h4>
            <table class="table table-bordered mt-3" id="asana-table">
                <thead>
                    <tr>
                        <th>Sr. No.</th>
                        <th>Asana Name</th>
                        <th>Type</th>
                        <th>Subtype</th>
                        <th>Time (min)</th>
                        <th>Remarks</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>

            <div class="row">
                <div class="col-md-4">
                    <label for="sequence_name" class="form-label">Sequence Name</label>
                    <input type="text" class="form-control" id="sequence_name" name="sequence_name" required>
                </div>
            </div>
            <button type="button" id="sequence_submit" class="btn btn-primary mt-3" onclick="validateAndSubmit()">Submit Schedule</button>
        </div>
    </form>
</div>

<script>
let asanaList = [];
let totalTime = 0; // Total time tracker

function fillAsanaDetails() {
    const selectedOption = document.getElementById('asana_id').selectedOptions[0];
    document.getElementById('type').value = selectedOption.getAttribute('data-type');
    document.getElementById('subtype').value = selectedOption.getAttribute('data-subtype');
    
    const asanaLength = selectedOption.getAttribute('data-asana-length');
    const timeField = document.getElementById('time');

    if (asanaLength && asanaLength !== "00:00:00") {
        // Set the time field directly with asana_length in HH:MM:SS format
        timeField.value = asanaLength;
        timeField.readOnly = true;
    } else {
        timeField.value = '';
        timeField.readOnly = false;
    }
}

// Convert HH:MM:SS format to seconds
function parseTimeToSeconds(time) {
    if (!time) return 0; // Return 0 if time is empty or invalid

    const timeParts = time.split(':');
    const hours = parseInt(timeParts[0], 10) || 0;
    const minutes = parseInt(timeParts[1], 10) || 0;
    const seconds = parseInt(timeParts[2], 10) || 0;
    
    return (hours * 3600) + (minutes * 60) + seconds;
}

// Convert seconds to HH:MM:SS format
function formatTime(seconds) {
    if (isNaN(seconds) || seconds < 0) return "00:00:00"; // Return default if invalid

    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
}

function addAsana() {
    const asanaSelect = document.getElementById('asana_id');
    const asanaName = asanaSelect.selectedOptions[0].getAttribute('data-asana-name');
    const type = asanaSelect.selectedOptions[0].getAttribute('data-type');
    const subtype = asanaSelect.selectedOptions[0].getAttribute('data-subtype');
    const remarks = document.getElementById('remarks').value;
    const timeInput = parseTimeToSeconds(document.getElementById('time').value); // Get time in seconds

    // Validate inputs
    if (!asanaName) {
        alert('Please select an Asana.');
        return;
    }

    // Add the asana to the list
    asanaList.push({
        asana_id: asanaSelect.value,
        asana_name: asanaName,
        type: type,
        subtype: subtype,
        asana_length: timeInput, // Use the time in seconds
        remarks: remarks
    });

    totalTime += timeInput; // Update total time in seconds
    updateAsanaTable(); // Update the table
    updateRemainingTime(); // Update remaining time in UI

    // Reset the input fields
    document.getElementById('asana-form').reset();
    document.getElementById('type').value = '';
    document.getElementById('subtype').value = '';
}

function updateAsanaTable() {
    const tableBody = document.getElementById('asana-table').querySelector('tbody');
    tableBody.innerHTML = ''; // Clear existing rows

    asanaList.forEach((asana, index) => {
        const asanaTime = formatTime(asana.asana_length); // Convert time to HH:MM:SS format
        const newRow = `<tr>
            <td>${index + 1}</td>
            <td>${asana.asana_name}</td>
            <td>${asana.type}</td>
            <td>${asana.subtype}</td>
            <td>${asanaTime}</td>
            <td>${asana.remarks}</td>
            <td>
                <button type="button" onclick="moveRowUp(this)" class="btn btn-warning btn-sm">Up</button>
                <button type="button" onclick="moveRowDown(this)" class="btn btn-warning btn-sm">Down</button>
                <button type="button" onclick="removeAsana(this, ${asana.asana_length})" class="btn btn-danger btn-sm">Remove</button>
            </td>
        </tr>`;
        tableBody.insertAdjacentHTML('beforeend', newRow);
    });
}

function removeAsana(button, time) {
    const row = button.closest('tr');
    const index = Array.from(row.parentNode.children).indexOf(row);
    row.remove();
    
    // Remove the asana from asanaList and adjust the total time
    const removedAsana = asanaList.splice(index, 1)[0];
    totalTime -= removedAsana.asana_length;
    
    updateRemainingTime();
    updateAsanaTable(); // Refresh table to reflect the new list
}

function updateRemainingTime() {
    const remainingTime = 55 * 60 - totalTime; // Calculate remaining time in seconds
    document.getElementById('remaining-time').textContent = remainingTime > 0 ? formatTime(remainingTime) : '00:00:00';
}

function validateAndSubmit() {
    if (totalTime !== 55 * 60) {
        alert(`Total time must be exactly 55 minutes. Current total: ${formatTime(totalTime)}.`);
    } else {
        document.getElementById('sequence').submit(); // Submit the form if the total time is correct
    }
}

</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
include 'includes/footer.php'; // Include footer
ob_end_flush();
?>
