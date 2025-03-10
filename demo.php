<?php
ob_start();
require_once 'Path.php'; // Include the path file for constants
require_once 'db.php'; // Include database connection
include 'includes/header.php';
require_once 'includes/batch_functions.php'; // Include the function to get batches and studios
require_once 'phpqrcode/qrlib.php'; // Include the QR code library
// Check if the user is logged in by verifying a session variable, such as 'user_id'
if (!isset($_SESSION['user_id'])) {
    // If user is not logged in, redirect to index.php
    header("Location: login.php");
    exit(); // Ensure no further code is executed after redirection
}
$tomorrow = date('Y-m-d', strtotime('+0 day'));
// Set default date range
$end_date = date('Y-m-d'); // Today's date
$start_date = date('Y-m-d', strtotime('-1 week')); // One week ago

// Fetch enquiry IDs based on the selected date range
$enquiries = [];
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capture dates from the POST request or use defaults
    $start_date = $_POST['start_date'] ?? $start_date;
    $end_date = $_POST['end_date'] ?? $end_date; // Use posted end date, not always today's date

    // Adjusting the SQL query
    $sql = "SELECT e.enquiry_id, 
                   CONCAT(e.title, ' ', e.first_name, ' ', IFNULL(e.middle_name, ''), ' ', e.last_name) AS fullName,
                   e.fullPhoneNumber
            FROM enquiry e
            WHERE e.enquiry_id NOT IN (SELECT d.enquiry_id FROM demo d)
            AND e.created_at >= '$start_date' AND e.created_at < DATE_ADD('$end_date', INTERVAL 1 DAY)"; // Include today's data

    $result = $conn->query($sql);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $enquiries[] = $row;
        }
        $_SESSION['enquiries'] = $enquiries; // Store in session
    } else {
        $_SESSION['error_message'] = "Error fetching enquiries: " . $conn->error; // Optional: Capture SQL errors
    }
}

// Handle enquiry details fetching
$enquiryDetails = $_SESSION['enquiryDetails'] ?? []; // Retrieve from session if exists

  
// Handle enquiry details fetching
$enquiryDetails = [];
if (isset($_POST['fetch_details'])) {
    $enquiry_id = $_POST['enquiry_id'] ?? '';

    if ($enquiry_id) {
        // Prepare the SQL query to fetch the details
        $sql = "SELECT 
                    e.enquiry_id,
                    e.created_at AS create_date,
                    e.dob,
                    e.gender,
                    e.mode_of_work,
                    e.PreferredTime,
                    e.PreferredStyle,
                    e.reason,
                    e.practiced_yoga,
                    e.any_pain,
                    e.health_conditions,
                    e.any_medication,
                    e.reference,
					e.fullPhoneNumber,
                    CASE WHEN e.PreferredStyle = 'Specific' THEN e.specificPreferredStyle ELSE '' END AS specificPreferredStyle
                FROM enquiry e
                WHERE e.enquiry_id = ?";

        // Prepare and execute the statement
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $enquiry_id); // assuming enquiry_id is a string
        $stmt->execute();
        $result = $stmt->get_result();

        // Fetch the data
        if ($result && $row = $result->fetch_assoc()) {
            $enquiryDetails = $row;
        } else {
            $error_message = "No details found for this enquiry ID.";
        }
        
        $stmt->close();
    } else {
        $error_message = "Invalid enquiry ID.";
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['demoForm'])) {
    // Retrieve form data
    $enquiry_id = $_POST['enquiry_id'] ?? null;
    $studio_id = $_POST['studio_id'] ?? null;
    $batch_id = $_POST['batch_id'] ?? null;
    $demo_date = $_POST['demo_date'] ?? null;
    $bed_required = isset($_POST['bed_required']) ? 1 : 0;
    $chair_required = isset($_POST['chair_required']) ? 1 : 0;
    $mat_required = isset($_POST['mat_required']) ? 1 : 0;
    $special_required = isset($_POST['special_assistance']) ? 1 : 0; // Use the correct column name
    $remarks = $_POST['remarks'] ?? null;

    // Prepare the SQL INSERT statement
    $sql = "INSERT INTO demo (enquiry_id, studio_id, batch_id, demo_date, bed_required, chair_required, mat_required, special_required, remarks, active, stamp)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    // Prepare the statement
    $stmt = $conn->prepare($sql);

    // Assuming 'active' is set to 1 for active status
    $active = 1;


    // Bind parameters (updated the "s" for remarks and stamp)
    $stmt->bind_param("iiisiiiisi", $enquiry_id, $studio_id, $batch_id, $demo_date, $bed_required, $chair_required, $mat_required, $special_required, $remarks, $active);

    // Execute the statement
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Demo details inserted successfully.";
    } else {
        $_SESSION['error_message'] = "Error inserting demo details: " . $stmt->error;
    }
}
$DemoSQL = "SELECT d.*, e.title, e.first_name, e.middle_name, 
        e.last_name, 
        b.batch_name, 
        s.studio_name ,
		e.created_at
    FROM 
        demo d
    LEFT JOIN 
        enquiry e ON d.enquiry_id = e.enquiry_id
    LEFT JOIN 
        batch b ON d.batch_id = b.batch_id
    LEFT JOIN 
        studio s ON d.studio_id = s.studio_id
    ORDER BY 
        d.demo_date ASC
";
$result = $conn->query($DemoSQL);

// Initialize arrays to hold active and inactive records
$activeDemos = [];
$inactiveDemos = [];
$lapsDemos = [];

// Process the results
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($row['active'] == 1) {
            $activeDemos[] = $row; // Store active records
        } elseif ($row['active'] == 2) {
            $inactiveDemos[] = $row; // Store inactive records
        } elseif ($row['active'] == 0) {
            $lapsDemos[] = $row; // Store lapsed records
        }
    }
} else {
    echo "No records found.";
}
// Assuming $conn is your mysqli connection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['edit'])) {
        $edit_id = $_POST['edit_id'];
        header("Location: demo.php?demo_id=$edit_id");
        exit;
    } elseif (isset($_POST['demo_given'])) {
        $demo_given_id = $_POST['demo_given_id'];

        // Fetch demo date for the selected demo
        $select_sql = "SELECT demo_date FROM demo WHERE demo_id = ?";
        $select_stmt = $conn->prepare($select_sql);
        $select_stmt->bind_param("i", $demo_given_id);
        $select_stmt->execute();
        $result = $select_stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $demo_date = new DateTime($row['demo_date']);
            $current_date = new DateTime();

            // Check if demo date is in the future
            if ($demo_date > $current_date) {
                $_SESSION['error_message'] = "Error: Demo date is in the future.";
            } else {
                // Update the active status to 2 for the demo given
                $update_sql = "UPDATE demo SET active = 2 WHERE demo_id = ?";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("i", $demo_given_id);
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Demo status updated to 'Demo Given' successfully.";
                } else {
                    $_SESSION['error_message'] = "Error updating demo status: " . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            $_SESSION['error_message'] = "Demo not found.";
        }

        $select_stmt->close();
        $conn->close();
        header("Location: demo.php");
        exit;
    } elseif (isset($_POST['laps_demo'])) {
        $demo_given_id = $_POST['laps_demo_id'];

        // Update the active status to 0 for the laps demo
        $update_sql = "UPDATE demo SET active = 0 WHERE demo_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("i", $demo_given_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Demo status updated to 'Laps Demo'.";
        } else {
            $_SESSION['error_message'] = "Error updating demo status: " . $stmt->error;
        }
        $stmt->close();
        $conn->close();
        header("Location: demo.php");
        exit;
    }
}

// Function to automatically update demo statuses
function updateOldDemos($conn) {
    // Get current date
    $current_date = new DateTime();
    $current_date_str = $current_date->format('Y-m-d H:i:s'); // Store as a string

    // Update demos that are active and their demo_date is older than one month
    $update_sql = "UPDATE demo SET active = 0 WHERE active = 2 AND demo_date < DATE_SUB(?, INTERVAL 1 MONTH)";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("s", $current_date_str); // Bind the variable

    if ($stmt->execute()) {
        // Optionally, you can log or notify about the number of demos updated
        return $stmt->affected_rows; // Number of rows affected
    } else {
        return "Error updating old demos: " . $stmt->error;
    }

    $stmt->close();
}

// Define the directory to store the QR codes
$qrCodeDir = 'qrcodes/';
if (!file_exists($qrCodeDir)) {
    mkdir($qrCodeDir, 0755, true);
}


// Call the function to update old demos (you can schedule this in a cron job)
$oldDemosUpdated = updateOldDemos($conn);
// Fetch all batches and associated studios
$batches_with_studios = getAllBatchesAndStudios();
ob_end_flush();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Select Enquiry</title>
	<style>
	table, th, td {
  border: 1px solid black;
  border-collapse: collapse;
}
	</style>
</head>
<body>

<div class="container-fluid">
    <h2>Select Enquiry</h2>
    <form id="enquiryForm" method="POST">
        <div class="row">
            <div class="col-md-4">
                <label for="start_date">Start Date:</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" required>
            </div>
            <div class="col-md-4">
                <label for="end_date">End Date:</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" max="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-4">
                <label for="submit">&nbsp;</label><br>
                <button type="submit" class="btn btn-primary">Filter Enquiries</button>
            </div>
        </div>
    </form>

    <?php if ($error_message): ?>
        <div class="alert alert-warning mt-3">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

<form id="enquiryDropdown" class="mt-4" method="POST">
    <div class="row">
        <div class="col-md-4">
            <label for="enquiry_id">Enquiry ID:</label>
            <select class="form-control" id="enquiry_id" name="enquiry_id" required>
                <option value="">Select Enquiry</option>
                <?php
                // Retain the selected value after form submission
                $selected_enquiry_id = isset($_POST['enquiry_id']) ? $_POST['enquiry_id'] : '';

                foreach ($enquiries as $row) {
                    // If the current enquiry_id matches the selected one, mark it as selected
                    $selected = ($row['enquiry_id'] == $selected_enquiry_id) ? 'selected' : '';
                    echo "<option value='{$row['enquiry_id']}' $selected>{$row['fullPhoneNumber']} - {$row['fullName']} ({$row['enquiry_id']})</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-4">
            <label for="submit">&nbsp;</label><br>
            <button type="submit" name="fetch_details" class="btn btn-primary">Show Details</button>
        </div>
    </div>
</form>

    <div class="row">
        <div class="col-md-7">
            <h4>Demo Details</h4>
            <form id="demoForm" method="POST" class="mt-4">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="demo_date">Demo Date:</label>
                        <input type="date" class="form-control" id="demo_date" name="demo_date"  min="<?php echo $tomorrow; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Required Equipment:</label>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <input type="checkbox" class="form-check-input" name="bed_required" id="bed_required">
                                <label for="bed_required" class="form-check-label">&nbsp;Bed Required</label>
                            </div>
                            <div class="col-md-6 mb-3">
                                <input type="checkbox" class="form-check-input" name="mat_required" id="mat_required">
                                <label for="mat_required" class="form-check-label">&nbsp;Mat Required</label>
                            </div>
                            <div class="col-md-6 mb-3">
                                <input type="checkbox" class="form-check-input" name="chair_required" id="chair_required">
                                <label for="chair_required" class="form-check-label">&nbsp;Chair Required</label>
                            </div>
                            <div class="col-md-6 mb-3">
                                <input type="checkbox" class="form-check-input" name="special_assistance" id="special_assistance">
                                <label for="special_assistance" class="form-check-label">&nbsp;Special Assistance</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <label for="batch_id" class="form-label">Batch</label>
                        <select name="batch_id" id="batch_id" class="form-control" onchange="fetchStudios(this.value)" required>
                            <option value="">Select a Batch</option>
                            <?php foreach ($batches_with_studios as $batch): ?>
                                <option value="<?= $batch['batch_id']; ?>"><?= $batch['batch_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="studio_id" class="form-label">Studio</label>
                        <select name="studio_id" id="studio_id" class="form-control" required>
                            <option value="">Select a Studio</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <label for="remarks" class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control"></textarea>
                    </div>
                </div>
<input type="hidden" name="enquiry_id" value="<?= htmlspecialchars($enquiryDetails['enquiry_id'] ?? ''); ?>">
                <div class="row mt-3">
                    <div class="col-md-12">
                        <button type="submit" name="demoForm" class="btn btn-success">Insert Demo</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="col-md-5" style="background-color: #ECECEC; border-radius: 10px;">
            <h4>Enquiry Details</h4>
            <?php if ($enquiryDetails): ?>
                <div class="col-md-12"><strong>Enquiry ID:</strong> <?= htmlspecialchars($enquiryDetails['enquiry_id']) ?></div>
                <div class="col-md-12"><strong>Created At:</strong> <?= htmlspecialchars($enquiryDetails['create_date']) ?></div>
                <div class="col-md-12"><strong>Date of Birth:</strong> <?= htmlspecialchars($enquiryDetails['dob']) ?></div>
                <div class="col-md-12"><strong>Gender:</strong> <?= htmlspecialchars($enquiryDetails['gender']) ?></div>
                <div class="col-md-12"><strong>Mode of Work:</strong> <?= htmlspecialchars($enquiryDetails['mode_of_work']) ?></div>
                <div class="col-md-12"><strong>Preferred Time:</strong> <?= htmlspecialchars($enquiryDetails['PreferredTime']) ?></div>
                <div class="col-md-12"><strong>Preferred Style:</strong> <?= htmlspecialchars($enquiryDetails['PreferredStyle']) . ' (' . htmlspecialchars($enquiryDetails['specificPreferredStyle']) . ')' ?></div>
                <div class="col-md-12"><strong>Reason:</strong> <?= htmlspecialchars($enquiryDetails['reason']) ?></div>
                <div class="col-md-12"><strong>Practiced Yoga:</strong> <?= htmlspecialchars($enquiryDetails['practiced_yoga']) ?></div>
                <div class="col-md-12"><strong>Any Pain:</strong> <?= htmlspecialchars($enquiryDetails['any_pain']) ?></div>
                <div class="col-md-12"><strong>Health Conditions:</strong> <?= htmlspecialchars($enquiryDetails['health_conditions']) ?></div>
                <div class="col-md-12"><strong>Any Medication:</strong> <?= htmlspecialchars($enquiryDetails['any_medication']) ?></div>
                <div class="col-md-12"><strong>Reference:</strong> <?= htmlspecialchars($enquiryDetails['reference']) ?></div>
				<div class="col-md-12" hidden><strong>Reference:</strong> <?= htmlspecialchars($enquiryDetails['fullPhoneNumber']) ?></div>

            <?php endif; ?>
        </div>
    </div>


<!-- JavaScript Code -->
<script>
    const batchStudioMap = {};
    
    <?php foreach ($batches_with_studios as $batch): ?>
        batchStudioMap[<?= $batch['batch_id'] ?>] = <?= json_encode($batch['studios']) ?>;
    <?php endforeach; ?>
    
    function fetchStudios(batchId) {
        const studioDropdown = document.getElementById("studio_id");
        studioDropdown.innerHTML = "<option value=''>Select a Studio</option>";
        
        if (batchId in batchStudioMap) {
            batchStudioMap[batchId].forEach(studio => {
                const option = document.createElement("option");
                option.value = studio['studio_id'];
                option.text = studio['studio_name'];
                studioDropdown.appendChild(option);
            });
        }
    }
</script>

</body>
</html>

<h2>Registered from Demo</h2>
<?php if (!empty($activeDemos)): ?>
    <table class="table table-success">
        <thead>
            <tr style="text-align: center; vertical-align: middle;">
                <th rowspan="2">Name</th>
                <th rowspan="2">Batch Name</th>
                <th rowspan="2">Studio Name</th>
                <th rowspan="2">Demo Date</th>
                <th colspan="3">Requirements</th>
                <th rowspan="2">Special Assistance</th>
                <th rowspan="2">Remarks</th>
                <th rowspan="2">Action</th>
                <th rowspan="2">QR Code</th>
                <th rowspan="2">Download QR</th>
            </tr>
            <tr style="text-align: center; vertical-align: middle;">
                <th>Bed</th>
                <th>Chair</th>
                <th>Mat</th>
            </tr>
        </thead>
        <tbody>
           <?php foreach ($activeDemos as $demo):
    
    // Prepare the full name
    $fullName = htmlspecialchars($demo['title'] . ' ' . $demo['first_name'] . ' ' . $demo['middle_name'] . ' ' . $demo['last_name']);
    $studioName = $demo['studio_name'];
    $batchName = $demo['batch_name'];

    // Get the phone number (make sure it's initialized first)
    $fullPhoneNumber = $demo['fullPhoneNumber'] ?? ''; // Initialize fullPhoneNumber properly

    // Prepare the data for the QR code
    $qrData = sprintf(
        "Name: %s %s %s %s\nBatch Name: %s\nStudio Name: %s\nDemo Date: %s\nBed Required: %s\nChair Required: %s\nMat Required: %s\nSpecial Assistance: %s\nRemarks: %s",
        $demo['title'],
        $demo['first_name'],
        $demo['middle_name'],
        $demo['last_name'],
        $demo['batch_name'],
        $demo['studio_name'],
        (new DateTime($demo['demo_date']))->format('d/m/Y'),
        $demo['bed_required'] ? 'Yes' : 'No',
        $demo['chair_required'] ? 'Yes' : 'No',
        $demo['mat_required'] ? 'Yes' : 'No',
        $demo['special_required'] ? 'Yes' : 'No',
        $demo['remarks']
    );

    // Define the QR code file path
    $qrCodeDir = "uploads/demoQRCode/";  // Directory for saving QR codes
    $qrFileName = $qrCodeDir . $demo['demo_id'] . '.png';  // Set the filename based on the demo ID

    // Check if the QR code file already exists, if not, generate it
    if (!file_exists($qrFileName)) {
        QRcode::png($qrData, $qrFileName, QR_ECLEVEL_L, 4);  // Generate the QR code
    }

    // Public URL for the QR code image (replace localhost with your domain name for production)
    $qrImageUrl = "https://www.yogarp.com/{$qrFileName}"; // Change localhost to your domain when live

    // Prepare the WhatsApp message after initializing $fullPhoneNumber
    $message = urlencode("Namaste $fullName,\n\nGreetings!\nWe are pleased to confirm your demo session with the following details:\n\n*Batch Timing:* $batchName.\n*Studio:* $studioName,\n*Google Location*: https://maps.app.goo.gl/ucuunhC28bFhd31z6\n\n*Important Notes:*\n*1*. Please bring your own yoga mat.\n*2*. Have breakfast at least 1 hour before the session and lunch at least 3 hours before.\n*3*. Download  your QR Code:: $qrImageUrl and show it to the instructor upon arrival.\n\nWe look forward to welcoming you!\nWarm regards,\nTeam Digital\n*Nisha's Yoga Studio*");


    // WhatsApp link including the QR code image URL
    $waLink = "https://wa.me/" . htmlspecialchars($fullPhoneNumber) . "?text=" . $message;
?>
                <tr>
                    <td><?= htmlspecialchars($demo['title'] . ' ' . $demo['first_name'] . ' ' . $demo['middle_name'] . ' ' . $demo['last_name']); ?></td>
                    <td><?= htmlspecialchars($demo['batch_name']); ?></td>
                    <td><?= htmlspecialchars($demo['studio_name']); ?></td>
                    <td><?= htmlspecialchars((new DateTime($demo['demo_date']))->format('d/m/Y')); ?></td>
                    <td style="text-align: center;"><?= htmlspecialchars($demo['bed_required'] ? 'Yes' : 'No'); ?></td>
                    <td style="text-align: center;"><?= htmlspecialchars($demo['chair_required'] ? 'Yes' : 'No'); ?></td>
                    <td style="text-align: center;"><?= htmlspecialchars($demo['mat_required'] ? 'Yes' : 'No'); ?></td>
                    <td style="text-align: center;"><?= htmlspecialchars($demo['special_required'] ? 'Yes' : 'No'); ?></td>
                    <td><?= htmlspecialchars($demo['remarks']); ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="demo_given_id" value="<?= htmlspecialchars($demo['demo_id']); ?>">
                            <button type="submit" name="demo_given" class="btn btn-success btn-sm">Demo Given</button>
                        </form>
                    </td>
                    <td>
                        <img src="<?= $qrFileName ?>" alt="QR Code" width="100">
                    </td>
                    <td>
                        <a href="<?= $waLink ?>" id="sendLink" class="btn btn-success btn-sm" target="_blank">Send</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No active demos found.</p>
<?php endif; ?>


   <h2 class="mt-5">Demo Given</h2>
    <?php if (!empty($inactiveDemos)): ?>
        <table class="table table-danger">
            <thead>
                                <tr style="text-align: center; vertical-align: middle;">
                    <th rowspan="2">Name</th>
                    <th rowspan="2">Batch Name</th>
                    <th rowspan="2">Studio Name</th>
                    <th rowspan="2">Demo Date</th>
                    <th colspan="3">Requirements</th>
                    
                    <th rowspan="2">Special Assistance</th>
                    <th rowspan="2">Remarks</th>
                    <th rowspan="2">Action</th>
                </tr>
				                <tr style="text-align: center; vertical-align: middle;">
                   
                    
                    <th>Bed</th>
                    <th>Chair</th>
                    <th>Mat</th>

                </tr>
            </thead>
            <tbody>
                <?php foreach ($inactiveDemos as $demo): ?>
                    <tr>
                        <td><?= htmlspecialchars($demo['title'] . ' ' . $demo['first_name'] . ' ' . $demo['middle_name'] . ' ' . $demo['last_name']); ?></td>
                        <td><?= htmlspecialchars($demo['batch_name']); ?></td>
                        <td><?= htmlspecialchars($demo['studio_name']); ?></td>
<td><?= htmlspecialchars((new DateTime($demo['demo_date']))->format('d/m/Y')); ?></td>
                        <td style="text-align: center;"><?= htmlspecialchars($demo['bed_required'] ? 'Yes' : 'No'); ?></td>
						<td style="text-align: center;"><?= htmlspecialchars($demo['chair_required'] ? 'Yes' : 'No'); ?></td>
						<td style="text-align: center;"><?= htmlspecialchars($demo['mat_required'] ? 'Yes' : 'No'); ?></td>
						<td style="text-align: center;"><?= htmlspecialchars($demo['special_required'] ? 'Yes' : 'No'); ?></td>
                        <td><?= htmlspecialchars($demo['remarks']); ?></td>
						<td><form method="POST" style="display:inline;">
                                <input type="hidden" name="laps_demo_id" value="<?= htmlspecialchars($demo['demo_id']); ?>">
                                <button type="submit" name="laps_demo" class="btn btn-danger btn-sm">Laps Demo</button>
                            </form></td>
							
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No inactive demos found.</p>
    <?php endif; ?>
	
	
	
   <h2 class="mt-5">Laps Demo</h2>
    <?php if (!empty($lapsDemos)): ?>
        <table class="table stips">
            <thead>
                <tr style="text-align: center; vertical-align: middle;"> 
				<th colspan="2">Enquiry</th>
				<th rowspan="2">Name</th>
				<th rowspan="2">Batch Name</th>
                <th rowspan="2">Studio Name</th>
                <th rowspan="2">Demo Date</th>
				<th colspan="3">Requirements</th>
                <th rowspan="2">Special Assistance</th>
                <th rowspan="2"style="text-align: center; vertical-align: middle;">Remarks</th>	 
				 </tr>
				<tr style="text-align: center; vertical-align: middle;">
				<th >Number</th>
				<th > Date</th>
				<th>Bed</th>
                    <th>Chair</th>
                    <th>Mat</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lapsDemos as $demo): ?>
                    <tr>
<td style="text-align: center;">
    <?= htmlspecialchars($demo['enquiry_id']); ?> </td>
  <td>  <?= htmlspecialchars((new DateTime($demo['created_at']))->format('d/m/Y')); ?></td>
	<td><?= htmlspecialchars($demo['title'] . ' ' . $demo['first_name'] . ' ' . $demo['middle_name'] . ' ' . $demo['last_name']); ?></td>
                        <td><?= htmlspecialchars($demo['batch_name']); ?></td>
                        <td><?= htmlspecialchars($demo['studio_name']); ?></td>
<td><?= htmlspecialchars((new DateTime($demo['demo_date']))->format('d/m/Y')); ?></td>
                        <td style="text-align: center;"><?= htmlspecialchars($demo['bed_required'] ? 'Yes' : 'No'); ?></td>
						<td style="text-align: center;"><?= htmlspecialchars($demo['chair_required'] ? 'Yes' : 'No'); ?></td>
						<td style="text-align: center;"><?= htmlspecialchars($demo['mat_required'] ? 'Yes' : 'No'); ?></td>
						<td style="text-align: center;"><?= htmlspecialchars($demo['special_required'] ? 'Yes' : 'No'); ?></td>
                        <td><?= htmlspecialchars($demo['remarks']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No inactive demos found.</p>
    <?php endif; ?>
</div>
<?php
$conn->close();
include 'includes/footer.php'; // Include footer
?>
</body>
</html>