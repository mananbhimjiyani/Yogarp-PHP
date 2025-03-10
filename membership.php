<?php
ob_start();
require_once 'Path.php'; // Include the path file for constants
require_once 'db.php'; // Include database connection
include 'includes/header.php';
require_once 'includes/batch_functions.php'; // Include the function to get batches and studios
// Check if the user is logged in by verifying a session variable, such as 'user_id'
if (!isset($_SESSION['user_id'])) {
    // If user is not logged in, redirect to index.php
    header("Location: login.php");
    exit(); // Ensure no further code is executed after redirection
}
// Initialize form data
$formData = [
    'client_id' => '',
    'plan_id' => '',
    'start_date' => '',
    'end_date' => '',
    'amount' => '',
    'couple_name' => '',
    'payment_type' => '',
    'payment_proff' => '',
    'remarks' => '',
];

// Fetch clients for the dropdown
$clientQuery = "SELECT client_id, CONCAT(title, ' ', first_name, ' ', middle_name, ' ', last_name) AS fullname, fullPhoneNumber FROM clients";
$clientsResult = $conn->query($clientQuery);

// Fetch membership plans for the dropdown
$planQuery = "SELECT plan_id, plan_type, plan_duration, amount FROM membership_plan WHERE active = 1";
$plansResult = $conn->query($planQuery);

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve and sanitize form data
    $formData['client_id'] = $_POST['client_id'];
    $formData['plan_id'] = $_POST['plan_id'];
    $formData['start_date'] = $_POST['start_date'];
    $formData['amount'] = $_POST['amount'];
$formData['couple_name'] = isset($_POST['couple_name']) ? $_POST['couple_name'] : ''; // Avoid undefined key error
    $formData['payment_type'] = $_POST['payment_type'];
    $formData['payment_proff'] = $_POST['payment_proff'];
    $formData['remarks'] = $_POST['remarks'];

$planDurationQuery = $conn->prepare("SELECT plan_duration FROM membership_plan WHERE plan_id = ?");
$planDurationQuery->bind_param("i", $formData['plan_id']);
$planDurationQuery->execute();
$planResult = $planDurationQuery->get_result();
$plan = $planResult->fetch_assoc();

if ($plan) {
    $startDate = new DateTime($formData['start_date']);
    $endDate = clone $startDate;

    // Check if the plan duration is in days or months
    if (strpos($plan['plan_duration'], 'day') !== false) {
        // Add days
        $endDate->modify("+" . $plan['plan_duration'] . " days");
    } elseif (strpos($plan['plan_duration'], 'month') !== false) {
        // Add months
        $endDate->modify("+" . $plan['plan_duration'] . " months");
    }

    $formData['end_date'] = $endDate->format('Y-m-d');
}

    // Insert data into membership table
    $insertQuery = $conn->prepare("INSERT INTO membership (client_id, plan_id, start_date, end_date, amount, couple_name, payment_type, payment_proff, remarks, active, stamp) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())");
    $insertQuery->bind_param("iississss", $formData['client_id'], $formData['plan_id'], $formData['start_date'], $formData['end_date'], $formData['amount'], $formData['couple_name'], $formData['payment_type'], $formData['payment_proff'], $formData['remarks']);

    if ($insertQuery->execute()) {
        echo "<div class='alert alert-success'>Membership added successfully!</div>";
    } else {
        echo "<div class='alert alert-danger'>Error adding membership: " . $conn->error . "</div>";
    }
}

$membershipQuery = "
    SELECT 
        m.client_id, 
        m.plan_id, 
        m.start_date, 
        m.end_date, 
        m.payment_type, 
        m.remarks, 
        c.first_name, c.last_name
    FROM membership m
    INNER JOIN clients c ON m.client_id = c.client_id
    WHERE m.active = 1
";
$membershipsResult = $conn->query($membershipQuery);

// Arrays to group data
$group10Plus = [];
$groupLessThan10 = [];
$groupLessThan0 = [];

// Group the data based on the end_date
while ($row = $membershipsResult->fetch_assoc()) {
    $endDate = new DateTime($row['end_date']);
    $currentDate = new DateTime();

    $diff = $currentDate->diff($endDate);
    $daysLeft = $diff->days;

    if ($daysLeft >= 10) {
        $group10Plus[] = $row;
    } elseif ($daysLeft < 10 && $daysLeft >= 0) {
        $groupLessThan10[] = $row;
    } else {
        $groupLessThan0[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Membership</title>
	 <style>
        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .popup-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            width: 400px;
        }
        .popup-content h4 {
            margin-bottom: 20px;
        }
        .popup-content .form-group {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">Add Membership</h2>
    <form method="post">
        <div class="row">
            <div class="col-md-6">
                <label for="client_id">Client</label>
                <select id="client_id" name="client_id" class="form-control" required>
                    <option value="">Select Client</option>
                    <?php while ($client = $clientsResult->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($client['client_id']) ?>">
                            <?= htmlspecialchars($client['fullname'] . ' (' . $client['fullPhoneNumber'] . ')') ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label for="plan_id">Plan</label>
                <select id="plan_id" name="plan_id" class="form-control" required>
                    <option value="">Select Plan</option>
                    <?php while ($plan = $plansResult->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($plan['plan_id']) ?>" 
                                data-duration="<?= htmlspecialchars($plan['plan_duration']) ?>" 
                                data-amount="<?= htmlspecialchars($plan['amount']) ?>">
                            <?= htmlspecialchars($plan['plan_type'] . ' (' . $plan['plan_duration'] . ' days)') ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
			<div class="form-group col-md-4" id="coupleNameContainer"></div>
        </div>

        <div class="row">
    <div class="col-md-6">
        <label for="start_date">Start Date</label>
        <input type="date" id="start_date" name="start_date" class="form-control" required>
    </div>
    <div class="col-md-6">
        <label for="end_date">End Date</label>
        <input type="text" id="end_date" name="end_date" class="form-control" readonly>
    </div>
</div>

       <div class="row">
<div class="col-md-4">
    <label for="amount">Amount</label>
    <input type="number" id="amount" name="amount" class="form-control" required readonly>
</div>

     <div class="col-md-4">
                <label for="payment_type">Payment type</label>
                <select class="form-control" id="payment_type" name="payment_type" required>
                    <option value="" disabled selected>Select</option>
                    <option value="Cash">Cash</option>
                    <option value="Online">Online</option>
                    <option value="Cheque">Cheque</option>
                    <option value="Transfer">Transfer</option>
                </select>
            </div>

            <div class="col-md-4">
                <label for="payment_proff">Payment Proof</label>
                <input type="text" id="payment_proff" name="payment_proff" class="form-control">
            </div>
        </div>
        <div class="row">
            <label for="remarks">Remarks</label>
            <textarea id="remarks" name="remarks" class="form-control"></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>

<div class="container mt-5">
    <h2 class="mb-4">Memberships Grouped by End Date</h2>

    <h3>10+ Days Remaining</h3>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Client Name</th>
                <th>Plan</th>
                <th>End Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($group10Plus as $membership): ?>
                <tr>
                    <td><?= htmlspecialchars($membership['first_name'] . ' ' . $membership['last_name']) ?></td>
                    <td><?= htmlspecialchars($membership['plan_id']) ?></td>
                    <td><?= htmlspecialchars($membership['end_date']) ?></td>
                    <td><button class="btn btn-primary" onclick="collectFees(<?= htmlspecialchars($membership['client_id']) ?>)">Collect Fees</button></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3>Less Than 10 Days Remaining</h3>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Client Name</th>
                <th>Plan</th>
                <th>End Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($groupLessThan10 as $membership): ?>
                <tr>
                    <td><?= htmlspecialchars($membership['first_name'] . ' ' . $membership['last_name']) ?></td>
                    <td><?= htmlspecialchars($membership['plan_id']) ?></td>
                    <td><?= htmlspecialchars($membership['end_date']) ?></td>
                    <td><button class="btn btn-primary" onclick="collectFees(<?= htmlspecialchars($membership['client_id']) ?>)">Collect Fees</button></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3>Expired Memberships</h3>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Client Name</th>
                <th>Plan</th>
                <th>End Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($groupLessThan0 as $membership): ?>
                <tr>
                    <td><?= htmlspecialchars($membership['first_name'] . ' ' . $membership['last_name']) ?></td>
                    <td><?= htmlspecialchars($membership['plan_id']) ?></td>
                    <td><?= htmlspecialchars($membership['end_date']) ?></td>
                    <td><button class="btn btn-primary" onclick="collectFees(<?= htmlspecialchars($membership['client_id']) ?>)">Collect Fees</button></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Show Membership Form when Collect Fees is Clicked -->
<div id="membershipForm" style="display:none;">
    <?php include 'form.php'; ?>  <!-- Assuming the form HTML is in form.php -->
</div>

<script>
function collectFees(clientId) {
    // Here, you can use AJAX to load the client's membership details into the form
    $.ajax({
        url: 'get_membership_details.php',
        method: 'GET',
        data: { client_id: clientId },
        success: function(response) {
            $('#membershipForm').html(response);
            $('#membershipForm').show();
        },
        error: function() {
            alert('Error fetching membership details');
        }
    });
}
</script>


<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
    document.getElementById('plan_id').addEventListener('change', function () {
        // Get the selected option
        const selectedOption = this.options[this.selectedIndex];

        // Fetch the amount from the data-amount attribute
        const amount = selectedOption.getAttribute('data-amount');

        // Set the amount in the input field
        document.getElementById('amount').value = amount || '';
    });
</script>
<script>
  $(document).ready(function () {
    $('#plan_id').change(function () {
        // Load amount and duration for the selected plan
        let amount = $('#plan_id option:selected').data('amount');
        let duration = $('#plan_id option:selected').data('duration');
        let planType = $('#plan_id option:selected').data('type');
        let startDate = $('#start_date').val();

        // Set amount
        $('#amount').val(amount);

        // Check if the plan type is 'couple' to show the couple name dropdown
        if (planType === "couple") {
            // Fetch client names for the dropdown
            let coupleDropdown = '<label for="couple_name">Couple Name</label>';
            coupleDropdown += '<select id="couple_name" name="couple_name" class="form-control">';
            coupleDropdown += '<option value="">Select Couple Name</option>';

            <?php 
                // PHP loop to output client names into JavaScript as dropdown options
                $clientsResult->data_seek(0); // Reset client result pointer
                while ($client = $clientsResult->fetch_assoc()) {
                    echo "coupleDropdown += '<option value=\"" . htmlspecialchars($client['fullname']) . "\">" . htmlspecialchars($client['fullname']) . "</option>';";
                }
            ?>
            coupleDropdown += '</select>';
            $('#coupleNameContainer').html(coupleDropdown);
        } else {
            // If not a couple plan, show a text input for couple name
            $('#coupleNameContainer').html('<label for="couple_name">Couple Name</label><input type="text" id="couple_name" name="couple_name" class="form-control">');
        }

        // Calculate and set end date if start date is chosen
        if (duration && startDate) {
            let endDate = new Date(startDate);
            endDate.setDate(endDate.getDate() + parseInt(duration));
            $('#end_date').val(endDate.toISOString().split('T')[0]);
        }
    });

    $('#start_date').change(function () {
        $('#plan_id').trigger('change'); // Recalculate end date when start date changes
    });
});
	
	
</script>
<script>
    document.getElementById('plan_id').addEventListener('change', function () {
        // Get the selected option
        const selectedOption = this.options[this.selectedIndex];

        // Fetch the amount from the data-amount attribute
        const amount = selectedOption.getAttribute('data-amount');

        // Set the amount in the input field
        document.getElementById('amount').value = amount || '';
    });

    $('#payment_type').change(function () {
        if ($(this).val() === 'Cash') {
            openCurrencyPopup();
        }
    });

    function openCurrencyPopup() {
        const popup = `
            <div id="currencyPopup" class="popup-overlay">
                <div class="popup-content">
                    <h4>Enter Currency Denominations</h4>
                    <form id="currencyForm">
                        <div class="form-group">
                            <label for="fiveNote">₹5 Notes</label>
                            <input type="number" id="fiveNote" class="form-control" value="0" min="0">
                        </div>
                        <div class="form-group">
                            <label for="tenNote">₹10 Notes</label>
                            <input type="number" id="tenNote" class="form-control" value="0" min="0">
                        </div>
                        <div class="form-group">
                            <label for="twentyNote">₹20 Notes</label>
                            <input type="number" id="twentyNote" class="form-control" value="0" min="0">
                        </div>
                        <div class="form-group">
                            <label for="fiftyNote">₹50 Notes</label>
                            <input type="number" id="fiftyNote" class="form-control" value="0" min="0">
                        </div>
                        <div class="form-group">
                            <label for="hundredNote">₹100 Notes</label>
                            <input type="number" id="hundredNote" class="form-control" value="0" min="0">
                        </div>
						<div class="form-group">
                            <label for="twoHundredNote">₹200 Notes</label>
                            <input type="number" id="twoHundredNote" class="form-control" value="0" min="0">
                        </div>
                        <div class="form-group">
                            <label for="fiveHundredNote">₹500 Notes</label>
                            <input type="number" id="fiveHundredNote" class="form-control" value="0" min="0">
                        </div>
                        <button type="button" class="btn btn-primary" onclick="calculateTotal()">Calculate Total</button>
                        <button type="button" class="btn btn-danger" onclick="closeCurrencyPopup()">Close</button>
                    </form>
                    <div id="currencyResult"></div>
                </div>
            </div>
        `;
        $('body').append(popup);
    }

    function closeCurrencyPopup() {
        $('#currencyPopup').remove();
    }

    function calculateTotal() {
        const five = $('#fiveNote').val() * 5;
        const ten = $('#tenNote').val() * 10;
        const twenty = $('#twentyNote').val() * 20;
        const fifty = $('#fiftyNote').val() * 50;
        const hundred = $('#hundredNote').val() * 100;
		const twoHundred = $('#twoHundredNote').val() * 200;
        const fiveHundred = $('#fiveHundredNote').val() * 500;

        const totalAmount = five + ten + twenty + fifty + hundred + fiveHundred;
        const enteredAmount = $('#amount').val();

        let resultMessage = `<p>Total amount entered: ₹${totalAmount}</p>`;

        if (totalAmount < enteredAmount) {
            const difference = enteredAmount - totalAmount;
            resultMessage += `<p>You are short of ₹${difference}. Please add more currency.</p>`;
        } else if (totalAmount > enteredAmount) {
            const extraAmount = totalAmount - enteredAmount;
            resultMessage += `<p>You have ₹${extraAmount} extra.</p>`;
        } else {
            resultMessage += `<p>Amount matches perfectly.</p>`;
        }

        $('#currencyResult').html(resultMessage);
    }
</script>
<script>
// Helper function to format a date as dd/mm/yyyy
function formatDate(date) {
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0'); // Months are zero-based
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
}

// Function to calculate end date
function calculateEndDate(startDate, duration) {
    const date = new Date(startDate);

    // Parse duration
    if (duration.includes("day")) {
        const days = parseInt(duration, 10);
        date.setDate(date.getDate() + days - 1); // Subtract 1 day
    } else if (duration.includes("week")) {
        const weeks = parseInt(duration, 10);
        date.setDate(date.getDate() + weeks * 7 - 1); // Subtract 1 day
    } else if (duration.includes("month")) {
        const months = parseInt(duration, 10);
        date.setMonth(date.getMonth() + months);
        date.setDate(date.getDate() - 1); // Subtract 1 day
    } else if (duration.includes("session")) {
        // No change for "session" plans, just subtract 1 day
        date.setDate(date.getDate() - 1);
    }

    // Adjust for weekends (Saturday or Sunday)
    const dayOfWeek = date.getDay(); // 0 = Sunday, 6 = Saturday
    if (dayOfWeek === 0) {
        // If Sunday, move to Monday
        date.setDate(date.getDate() + 1);
    } else if (dayOfWeek === 6) {
        // If Saturday, move to Monday
        date.setDate(date.getDate() + 2);
    }

    return formatDate(date);
}

// Event listeners for Plan and Start Date changes
document.getElementById('plan_id').addEventListener('change', updateEndDate);
document.getElementById('start_date').addEventListener('change', updateEndDate);

function updateEndDate() {
    const planDropdown = document.getElementById('plan_id');
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');

    // Get selected option and attributes
    const selectedOption = planDropdown.options[planDropdown.selectedIndex];
    const planDuration = selectedOption.getAttribute('data-duration');
    const startDateValue = startDateInput.value;

    if (startDateValue && planDuration) {
        endDateInput.value = calculateEndDate(startDateValue, planDuration.toLowerCase());
    } else {
        endDateInput.value = ''; // Clear the end date if inputs are invalid
    }
}

</script>
</body>
</html>
