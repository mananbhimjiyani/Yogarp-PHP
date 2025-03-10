<?php
ob_start(); // Start output buffering
require_once 'Path.php';
require_once 'db.php';
include 'includes/header.php';

// Check user_type from session
if (isset($_SESSION['user_type'])) {
    $user_type = $_SESSION['user_type'];
    $inserted_by = $_SESSION['user_id']; // Assuming user_id is stored in session for the user who is inserting
} else {
    header('Location: login.php'); // Redirect to login if user_type is not set
    exit;
}

if (isset($_POST['client_id'])) {
    $client_id = (int)$_POST['client_id']; // Get selected client ID

    $sql_active_fees = "SELECT f.start_date, f.end_date, c.first_name, c.last_name, p.plan_name 
                        FROM fees f 
                        JOIN clients c ON f.client_id = c.client_id 
                        JOIN membership_plan p ON f.plan_id = p.plan_id 
                        WHERE f.client_id = ? AND f.active = 1";
    $stmt_active_fees = $conn->prepare($sql_active_fees);
    $stmt_active_fees->bind_param("i", $client_id);
    $stmt_active_fees->execute();
    $result_active_fees = $stmt_active_fees->get_result();

    $active_fees = []; // Initialize an array to store active fees

    if ($result_active_fees->num_rows > 0) {
        while ($row = $result_active_fees->fetch_assoc()) {
            $active_fees[] = $row; // Store the relevant data for active fees
        }
    }
}


// Initialize variables
$search_client = '';
$clients = [];
$selected_client = null;
$plans = [];
$selected_plan = null;
$start_date = '';
$end_date = '';
$message = '';

// Fetch membership plans from the database
$sql_plans = "SELECT * FROM membership_plan WHERE active = 1"; // Only active plans
$result_plans = $conn->query($sql_plans);

if ($result_plans->num_rows > 0) {
    while ($row = $result_plans->fetch_assoc()) {
        $plans[] = $row; // Store the active plans in an array
    }
}

// Handle client search request
if (isset($_GET['search_client'])) {
    $search_client = $conn->real_escape_string($_GET['search_client']); // Sanitize user input
    $sql_clients = "SELECT client_id, title, first_name, last_name, mobile_number 
                    FROM clients 
                    WHERE first_name LIKE '%$search_client%' OR 
                          last_name LIKE '%$search_client%' OR 
                          mobile_number LIKE '%$search_client%'";

    $result_clients = $conn->query($sql_clients);

    if ($result_clients->num_rows > 0) {
        while ($row = $result_clients->fetch_assoc()) {
            $clients[] = $row; // Store the results in an array
        }
    } else {
        $message = "No clients found.";
    }
}

// Function to calculate the end date based on plan duration
function calculateEndDate($start_date, $plan_duration) {
    if (preg_match('/(\d+)\s*(week|weeks|month|months|day|days)/', $plan_duration, $matches)) {
        $num_units = (int)$matches[1];
        $unit_type = $matches[2];

        switch ($unit_type) {
            case 'week':
            case 'weeks':
                return date('Y-m-d', strtotime($start_date . " +$num_units week"));
            case 'month':
            case 'months':
                return date('Y-m-d', strtotime($start_date . " +$num_units month"));
            case 'day':
            case 'days':
                return date('Y-m-d', strtotime($start_date . " +$num_units day"));
            default:
                return $start_date;
        }
    }
    return $start_date; // Return start_date if format is invalid
}

// Handle form submission after client selection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = isset($_POST['client_id']) ? (int)$_POST['client_id'] : null; // Get selected client ID
    $plan_id = isset($_POST['plan_id']) ? (int)$_POST['plan_id'] : null;
    $start_date = $_POST['start_date'];
    $remarks = $_POST['remarks'];
    $active = 1; // Set active to 1 for new entries

    // Fetch selected plan details including name and duration
    $sql_plan = "SELECT plan_name, plan_duration FROM membership_plan WHERE plan_id = ?";
    $stmt_plan = $conn->prepare($sql_plan);
    $stmt_plan->bind_param("i", $plan_id);
    $stmt_plan->execute();
    $result_plan = $stmt_plan->get_result();

    if ($result_plan->num_rows > 0) {
        $plan_details = $result_plan->fetch_assoc();
        $plan_name = $plan_details['plan_name'];
        $plan_duration = $plan_details['plan_duration']; // Get the duration for the selected plan

        // Check if plan is "Food Plan", skip the membership check
        if ($plan_name == "Food Plan") {
            // Directly insert new entry for Food Plan
            $end_date = calculateEndDate($start_date, $plan_duration);
            $sql_insert = "INSERT INTO fees (client_id, plan_id, start_date, end_date, remarks, inserted_by, active) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)";

            // Change active to 2 for Food Plan
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("iissssi", $client_id, $plan_id, $start_date, $end_date, $remarks, $inserted_by, $active=2);

            if ($stmt_insert->execute()) {
                $message = "Fees added successfully!";
            } else {
                $message = "Error: " . $stmt_insert->error;
            }
        } else {
            // Check if client already has an active membership (except for Food Plan)
            $sql_check = "SELECT fees_id, end_date FROM fees WHERE client_id = ? AND active = 1";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("i", $client_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                // Active membership exists
            } else {
 
                if ($stmt_insert->execute()) {
                    $message = "Fees added successfully!";
                } else {
                    $message = "Error: " . $stmt_insert->error;
                }
            }
        }
    } else {
        $message = "Plan not found.";
    }
}
ob_end_flush();
?>

<!-- HTML PART -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Add Fees</title>
</head>
<body>
<div class="container">
    <h2 class="mb-4">Add Fees</h2>

    <?php if ($message) : ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <!-- Search for clients -->
    <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="fees">
        <div class="mb-3">
            <div class="input-group mb-3">
                <input type="text" class="form-control" name="search_client" 
                       placeholder="Search by first name, last name, or mobile number" 
                       value="<?php echo htmlspecialchars($search_client); ?>" 
                       required>
                <button class="btn btn-primary" type="submit">Search Client</button>
            </div>
        </div>
    </form>

    <!-- Show clients and plans -->
    <?php if (!empty($clients)): ?>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="continue_form">
            <div class="mb-3">
                <label for="client_id" class="form-label">Select Client</label>
                <select class="form-select" id="client_id" name="client_id" required>
                    <option value="">Choose a client</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?php echo $client['client_id']; ?>">
                            <?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="plan_id" class="form-label">Select Membership Plan</label>
                <select class="form-select" id="plan_id" name="plan_id" required>
                    <option value="">Choose a plan</option>
                    <?php foreach ($plans as $plan): ?>
                        <option value="<?php echo $plan['plan_id']; ?>"><?php echo htmlspecialchars($plan['plan_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
    <label for="start_date" class="form-label">Start Date</label>
    <input type="date" class="form-control" id="start_date" name="start_date" readonly required>
</div>

            <div class="mb-3">
                <label for="remarks" class="form-label">Remarks</label>
                <textarea class="form-control" id="remarks" name="remarks" rows="3"></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    <?php endif; ?>
<div id="confirmationDetails" style="display: none;" class="container mt-4">
    <h4>Confirmation Details</h4>
    <p><strong>Client:</strong> <span id="confirmedClientName"></span></p>
    <p><strong>Plan:</strong> <span id="confirmedPlanName"></span></p>
    <p><strong>New Start Date:</strong> <span id="newStartDate"></span></p>
    <p><strong>New End Date:</strong> <span id="newEndDate"></span></p>
</div>
<?php if (!empty($active_fees)): ?>
    <div class="mb-4">
        <h5>Active Memberships:</h5>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Client Name</th>
                    <th>Plan Name</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($active_fees as $fee): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($fee['first_name'] . ' ' . $fee['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($fee['plan_name']); ?></td>
                        <td><?php echo htmlspecialchars($fee['start_date']); ?></td>
                        <td><?php echo htmlspecialchars($fee['end_date']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="alert alert-info">No active memberships found for this client.</div>
<?php endif; ?>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // JavaScript logic to handle confirmation
    if (confirm('This client already has an active membership that expires in ' + remainingDays + ' days. Do you want to continue with this client?')) {
        // Populate the fees form
        document.getElementById('start_date').value = newStartDate.toISOString().split('T')[0]; // Set new start date
        document.getElementById('end_date').value = calculateNewEndDate(newStartDate, planDuration); // Set new end date

        // Show the fees form
        document.getElementById('fees').style.display = 'block'; // Display the form

        // Populate the confirmation details
        document.getElementById('confirmedClientName').innerText = '<?php echo addslashes($client['first_name'] . " " . $client['last_name']); ?>'; // Set confirmed client name
        document.getElementById('confirmedPlanName').innerText = '<?php echo addslashes($plan_name); ?>'; // Set confirmed plan name
        document.getElementById('newStartDate').innerText = newStartDate.toISOString().split('T')[0]; // Set new start date
        document.getElementById('newEndDate').innerText = calculateNewEndDate(newStartDate, planDuration); // Calculate and set new end date

        // Show the confirmation details
        document.getElementById('confirmationDetails').style.display = 'block'; // Display confirmation details

        // Hide continue form if you don't want to show it anymore
        continueForm.style.display = 'none'; 
    } else {
        window.location.href = 'fees.php'; // Redirect to search user
    }
</script>
<script>
    // Function to set the start_date input to today's date and disable it
    window.onload = function() {
        var today = new Date().toISOString().split('T')[0]; // Get today's date in YYYY-MM-DD format
        document.getElementById('start_date').value = today; // Set the value
        document.getElementById('start_date').setAttribute('readonly', 'readonly'); // Make the field non-editable
    };
</script>
</body>
</html>
