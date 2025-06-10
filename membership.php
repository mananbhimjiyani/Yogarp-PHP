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

// Function to check if user has previous membership
function hasPreviousMembership($client_id)
{
    global $conn;
    $query = "SELECT COUNT(*) as count FROM membership WHERE client_id = ? AND active = 0";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'] > 0;
}

// Function to get client details
function getClientDetails($client_id)
{
    global $conn;
    $query = "SELECT c.*, m.start_date, m.end_date, m.amount, m.payment_type, m.payment_proff, m.remarks 
              FROM clients c 
              LEFT JOIN membership m ON c.client_id = m.client_id 
              WHERE c.client_id = ? 
              ORDER BY m.membership_id DESC 
              LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Function to format date
function formatDate($date)
{
    return date('d/m/Y', strtotime($date));
}

// Function to format phone number with country code
function formatPhoneNumber($phone)
{
    // Remove any non-numeric characters
    $phone = preg_replace("/[^0-9]/", "", $phone);

    // If number is 10 digits, add 91 as country code
    if (strlen($phone) === 10) {
        $phone = "91" . $phone;
    }

    return $phone;
}

function generateWhatsAppMessage($client, $isRenewal)
{
    $message = "";
    if ($isRenewal) {
        $message = "🎉 *Your Nisha's Yoga Membership Has Been Renewed! 🎉*\n\n";
        $message .= "Dear " . $client['first_name'] . ",\n\n";
        $message .= "We're delighted to confirm the successful renewal of your membership with Nisha's Yoga. Thank you for continuing your journey with us!\n\n";
    } else {
        $message = "🎉 *A Warm Welcome to Nisha's Yoga! 🎉*\n\n";
        $message .= "Dear " . $client['first_name'] . ",\n\n";
        $message .= "Welcome aboard! Your membership with Nisha's Yoga has now been successfully activated. We're thrilled to have you as part of our community.\n\n";
    }

    $message .= "📅 *Membership Overview:*\n";
    $message .= "Start Date: " . formatDate($client['start_date']) . "\n";
    $message .= "End Date: " . formatDate($client['end_date']) . "\n";
    $message .= "Amount Paid: ₹" . number_format($client['amount'], 2) . "\n";
    $message .= "Payment Type: " . ($client['payment_type'] ?: 'Not specified') . "\n";

    if (!empty($client['remarks'])) {
        $message .= "Remarks: " . $client['remarks'] . "\n";
    }

    if (!empty($client['payment_proff'])) {
        $message .= "Payment Proof: " . $client['payment_proff'] . "\n";
    }

    $message .= "\nWe appreciate your trust in Nisha's Yoga.\n\n\n\n";
    $message .= "Best regards,\n";
    $message .= "The *Client Relations Team*\n";
    $message .= "Nisha's Yoga Studio\n";
    $message .= "(A brand of Yognishi Pvt. Ltd.)\n\n";

    $message .= "📝 *Please Note:*\n";
    $message .= "Should you have any questions or require further assistance, feel free to reach out to us at:\n";
    $message .= "📞 Phone: +918866160330";

    return urlencode($message);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->begin_transaction();

        // Get and sanitize form data
        $client_id = intval($_POST['client_id']);
        $plan_id = intval($_POST['plan_id']);
        $start_date = $_POST['start_date'];
        $amount = floatval($_POST['amount']);
        $couple_name = $_POST['couple_name'] ?? '';
        $payment_type = $_POST['payment_type'] ?? '';
        $payment_proff = $_POST['payment_proff'] ?? '';
        $remarks = $_POST['remarks'] ?? '';

        // Get plan duration
        $planQuery = "SELECT plan_duration FROM membership_plan WHERE plan_id = ?";
        $planStmt = $conn->prepare($planQuery);
        if (!$planStmt) {
            throw new Exception("Error preparing plan query: " . $conn->error);
        }

        $planStmt->bind_param("i", $plan_id);
        if (!$planStmt->execute()) {
            throw new Exception("Error executing plan query: " . $planStmt->error);
        }

        $planResult = $planStmt->get_result();
        $plan = $planResult->fetch_assoc();

        if (!$plan) {
            throw new Exception("No plan found for the selected plan");
        }

        $plan_duration = $plan['plan_duration'];

        // Calculate end date based on plan duration
        $start = new DateTime($start_date);
        $end = clone $start;

        // Parse duration (e.g., "1 Month", "3 Months", "1 Year")
        if (preg_match('/(\d+)\s+(Month|Year)s?/', $plan_duration, $matches)) {
            $number = intval($matches[1]);
            $unit = $matches[2];

            if ($unit === 'Month') {
                $end->modify("+{$number} months");
            } else if ($unit === 'Year') {
                $end->modify("+{$number} years");
            }
        } else {
            throw new Exception("Invalid plan duration format");
        }

        // Subtract one day to make it inclusive
        $end->modify('-1 day');
        $end_date = $end->format('Y-m-d');

        // Deactivate previous membership
        $deactivateQuery = "UPDATE membership SET active = 0 WHERE client_id = ? AND active = 1";
        $deactivateStmt = $conn->prepare($deactivateQuery);
        $deactivateStmt->bind_param("i", $client_id);
        $deactivateStmt->execute();

        // Insert new membership
        $insertQuery = "INSERT INTO membership (client_id, plan_id, start_date, end_date, amount, couple_name, payment_type, payment_proff, remarks, active) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param(
            "iisssssss",
            $client_id,
            $plan_id,
            $start_date,
            $end_date,
            $amount,
            $couple_name,
            $payment_type,
            $payment_proff,
            $remarks
        );

        if (!$insertStmt->execute()) {
            throw new Exception("Error inserting new membership: " . $insertStmt->error);
        }

        // Get client details
        $client = getClientDetails($client_id);

        if (!$client) {
            throw new Exception("Client details not found");
        }

        // Check if it's a renewal
        $isRenewal = hasPreviousMembership($client_id);

        // Generate WhatsApp message
        $message = generateWhatsAppMessage($client, $isRenewal);

        // Format phone number with country code
        $formatted_phone = formatPhoneNumber($client['fullPhoneNumber']);

        // Create WhatsApp URL
        $whatsapp_url = "https://wa.me/" . $formatted_phone . "?text=" . $message;

        // Commit the transaction
        $conn->commit();

        // Redirect to WhatsApp only after successful insert
        header("Location: " . $whatsapp_url);
        exit();
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        $error = $e->getMessage();
        error_log("Error in membership creation: " . $error);
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

// Function to format date for display
function formatDateForDisplay($date)
{
    if (empty($date) || $date == '0000-00-00') {
        return 'N/A';
    }
    return date('d/m/Y', strtotime($date));
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

        .date-cell {
            white-space: nowrap;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h2 class="mb-4">Add Membership</h2>
        <form method="post">
            <div class="row">
                <div class="col-md-6">
                    <label for="client_search">Client</label>
                    <div class="client-search-container position-relative">
                        <input type="text" id="client_search" class="form-control" placeholder="Search by client name..." autocomplete="off">
                        <input type="hidden" id="client_id" name="client_id">
                        <input type="hidden" id="client_name" name="client_name">
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="plan_id">Plan</label>
                    <select id="plan_id" name="plan_id" class="form-control" required>
                        <option value="">Select Plan</option>
                        <?php while ($plan = $plansResult->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($plan['plan_id']) ?>"
                                data-amount="<?= htmlspecialchars($plan['amount']) ?>"
                                data-duration="<?= htmlspecialchars($plan['plan_duration']) ?>">
                                <?= htmlspecialchars($plan['plan_type'] . ' (' . $plan['plan_duration'] . ')') ?>
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

        <!-- Add search input -->
        <div class="row mb-3">
            <div class="col-md-6">
                <input type="text" id="searchInput" class="form-control" placeholder="Search by client name...">
            </div>
        </div>

        <h3>Less Than 10 Days Remaining</h3>
        <table class="table table-striped" id="tableLessThan10">
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
                        <td class="date-cell"><?= formatDateForDisplay($membership['end_date']) ?></td>
                        <td><button class="btn btn-primary" onclick="collectFees(<?= htmlspecialchars($membership['client_id']) ?>)">Collect Fees</button></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3>10+ Days Remaining</h3>
        <table class="table table-striped" id="table10Plus">
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
                        <td class="date-cell"><?= formatDateForDisplay($membership['end_date']) ?></td>
                        <td><button class="btn btn-primary" onclick="collectFees(<?= htmlspecialchars($membership['client_id']) ?>)">Collect Fees</button></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3>Expired Memberships</h3>
        <table class="table table-striped" id="tableExpired">
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
                        <td class="date-cell"><?= formatDateForDisplay($membership['end_date']) ?></td>
                        <td><button class="btn btn-primary" onclick="collectFees(<?= htmlspecialchars($membership['client_id']) ?>)">Collect Fees</button></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Show Membership Form when Collect Fees is Clicked -->
    <div id="membershipForm" style="display:none;">
        <form method="post" class="mt-4">
            <div class="row">
                <div class="col-md-6">
                    <label for="client_search">Client</label>
                    <div class="client-search-container position-relative">
                        <input type="text" id="client_search" class="form-control" placeholder="Search by client name..." autocomplete="off">
                        <input type="hidden" id="client_id" name="client_id">
                        <input type="hidden" id="client_name" name="client_name">
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="plan_id">Plan</label>
                    <select id="plan_id" name="plan_id" class="form-control" required>
                        <option value="">Select Plan</option>
                        <?php while ($plan = $plansResult->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($plan['plan_id']) ?>"
                                data-amount="<?= htmlspecialchars($plan['amount']) ?>"
                                data-duration="<?= htmlspecialchars($plan['plan_duration']) ?>">
                                <?= htmlspecialchars($plan['plan_type'] . ' (' . $plan['plan_duration'] . ')') ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-6">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label for="end_date">End Date</label>
                    <input type="text" id="end_date" name="end_date" class="form-control" readonly>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-4">
                    <label for="amount">Amount</label>
                    <input type="number" id="amount" name="amount" class="form-control" required readonly>
                </div>
                <div class="col-md-4">
                    <label for="payment_type">Payment Type</label>
                    <select id="payment_type" name="payment_type" class="form-control" required>
                        <option value="">Select Payment Type</option>
                        <option value="Cash">Cash</option>
                        <option value="Online">Online</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="payment_proff">Payment Proof</label>
                    <input type="text" id="payment_proff" name="payment_proff" class="form-control">
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-6">
                    <label for="couple_name">Couple Name</label>
                    <input type="text" id="couple_name" name="couple_name" class="form-control">
                </div>
                <div class="col-md-6">
                    <label for="remarks">Remarks</label>
                    <input type="text" id="remarks" name="remarks" class="form-control">
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        // ======================== Load Membership Details ========================
        function loadMembershipDetails(clientId) {
            $.ajax({
                url: 'get_membership_details.php',
                method: 'GET',
                data: {
                    client_id: clientId
                },
                success: function(response) {
                    $('#membershipForm').html(response);
                    $('#membershipForm').show();
                },
                error: function() {
                    alert('Error fetching membership details');
                }
            });
        }

        // ======================== Update Amount ========================
        function updateAmount() {
            const planDropdown = document.getElementById('plan_id');
            if (planDropdown) {
                const selectedOption = planDropdown.options[planDropdown.selectedIndex];
                const amount = selectedOption.getAttribute('data-amount');
                document.getElementById('amount').value = amount || '';
            }
        }

        // ======================== Update End Date ========================
        function formatDate(date) {
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            return `${day}/${month}/${year}`;
        }

        function calculateEndDate(startDate, duration) {
            const date = new Date(startDate);

            if (duration.includes("day")) {
                date.setDate(date.getDate() + parseInt(duration) - 1);
            } else if (duration.includes("week")) {
                date.setDate(date.getDate() + parseInt(duration) * 7 - 1);
            } else if (duration.includes("month")) {
                date.setMonth(date.getMonth() + parseInt(duration));
                date.setDate(date.getDate() - 1);
            } else if (duration.includes("session")) {
                date.setDate(date.getDate() - 1);
            }

            // Adjust for weekends
            const dayOfWeek = date.getDay();
            if (dayOfWeek === 0) date.setDate(date.getDate() + 1);
            if (dayOfWeek === 6) date.setDate(date.getDate() + 2);

            return formatDate(date);
        }

        function updateEndDate() {
            const planDropdown = document.getElementById('plan_id');
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');

            if (planDropdown && startDateInput && endDateInput) {
                const selectedOption = planDropdown.options[planDropdown.selectedIndex];
                const duration = selectedOption.getAttribute('data-duration');
                const startDate = startDateInput.value;

                if (startDate && duration) {
                    endDateInput.value = calculateEndDate(startDate, duration.toLowerCase());
                } else {
                    endDateInput.value = '';
                }
            }
        }

        // ======================== Handle Couple Name ========================
        function toggleCoupleName() {
            const planDropdown = document.getElementById('plan_id');
            const coupleNameDiv = document.getElementById('coupleNameDiv');

            if (planDropdown && coupleNameDiv) {
                const selectedOption = planDropdown.options[planDropdown.selectedIndex];
                const planType = selectedOption.textContent.toLowerCase();

                coupleNameDiv.style.display = planType.includes('couple') ? 'block' : 'none';
            }
        }

        // ======================== Handle Payment Type ========================
        function openCurrencyPopup() {
            const popup = `
            <div id="currencyPopup" class="popup-overlay">
                <div class="popup-content">
                    <h4>Enter Currency Denominations</h4>
                    <form id="currencyForm">
                        ${generateCurrencyInputs()}
                        <button type="button" class="btn btn-primary" onclick="calculateTotal()">Calculate Total</button>
                        <button type="button" class="btn btn-danger" onclick="closeCurrencyPopup()">Close</button>
                    </form>
                    <div id="currencyResult"></div>
                </div>
            </div>
        `;
            document.body.insertAdjacentHTML('beforeend', popup);
        }

        function generateCurrencyInputs() {
            const denominations = [5, 10, 20, 50, 100, 200, 500];
            return denominations.map(denom => `
            <div class="form-group">
                <label for="${denom}Note">₹${denom} Notes</label>
                <input type="number" id="${denom}Note" class="form-control" value="0" min="0">
            </div>
        `).join('');
        }

        function closeCurrencyPopup() {
            const popup = document.getElementById('currencyPopup');
            if (popup) popup.remove();
        }

        function calculateTotal() {
            const denominations = [5, 10, 20, 50, 100, 200, 500];
            let total = 0;

            denominations.forEach(denom => {
                const input = document.getElementById(`${denom}Note`);
                if (input) total += input.value * denom;
            });

            const enteredAmount = parseInt(document.getElementById('amount').value);
            let message = `<p>Total amount entered: ₹${total}</p>`;

            if (total < enteredAmount) {
                message += `<p>You are short of ₹${enteredAmount - total}. Please add more currency.</p>`;
            } else if (total > enteredAmount) {
                message += `<p>You have ₹${total - enteredAmount} extra.</p>`;
            } else {
                message += `<p>Amount matches perfectly.</p>`;
            }

            document.getElementById('currencyResult').innerHTML = message;
        }

        // ======================== Search Functionality ========================
        function filterTable(input, tableId) {
            const filter = input.value.toLowerCase();
            const table = document.getElementById(tableId);
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                let found = false;

                for (let j = 0; j < cells.length; j++) {
                    const cell = cells[j];
                    if (cell.textContent.toLowerCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }

                row.style.display = found ? '' : 'none';
            }
        }

        // ======================== Client Search Functionality ========================
        let searchTimeout;
        const searchResults = document.createElement('div');
        searchResults.className = 'search-results';
        searchResults.style.display = 'none';
        searchResults.style.position = 'absolute';
        searchResults.style.zIndex = '1000';
        searchResults.style.backgroundColor = 'white';
        searchResults.style.border = '1px solid #ddd';
        searchResults.style.maxHeight = '200px';
        searchResults.style.overflowY = 'auto';
        searchResults.style.width = '100%';
        searchResults.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';

        function searchClients(input) {
            clearTimeout(searchTimeout);
            const searchValue = input.value.trim();

            if (searchValue.length < 2) {
                searchResults.style.display = 'none';
                return;
            }

            searchTimeout = setTimeout(() => {
                $.ajax({
                    url: 'search_clients.php',
                    method: 'GET',
                    data: {
                        query: searchValue
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.error) {
                            console.error('Search error:', response.error);
                            return;
                        }

                        searchResults.innerHTML = '';
                        if (!response || response.length === 0) {
                            searchResults.innerHTML = '<div class="p-2">No clients found</div>';
                        } else {
                            response.forEach(client => {
                                const div = document.createElement('div');
                                div.className = 'p-2 border-bottom hover-bg-light';
                                div.style.cursor = 'pointer';
                                div.innerHTML = `${client.display_name} - ${client.fullPhoneNumber}`;
                                div.onclick = () => selectClient(client);
                                searchResults.appendChild(div);
                            });
                        }
                        searchResults.style.display = 'block';
                    },
                    error: function(xhr, status, error) {
                        console.error('Error searching clients:', error);
                        searchResults.innerHTML = '<div class="p-2 text-danger">Error searching clients</div>';
                        searchResults.style.display = 'block';
                    }
                });
            }, 300);
        }

        function selectClient(client) {
            const clientSearch = document.getElementById('client_search');
            const clientIdInput = document.getElementById('client_id');

            if (clientSearch) clientSearch.value = client.display_name;
            if (clientIdInput) clientIdInput.value = client.client_id;

            searchResults.style.display = 'none';

            // Get current membership details
            $.ajax({
                url: 'get_client_membership.php',
                method: 'GET',
                data: {
                    client_id: client.client_id
                },
                success: function(response) {
                    if (response.error) {
                        console.error('Error:', response.error);
                        return;
                    }

                    // Fill the form with current membership details
                    if (response) {
                        // Fill plan if available
                        if (response.plan_id) {
                            const planDropdown = document.getElementById('plan_id');
                            if (planDropdown) {
                                planDropdown.value = response.plan_id;
                                updateAmount();
                                toggleCoupleName();
                            }
                        }

                        // Fill start date if available
                        if (response.next_start_date) {
                            const startDateInput = document.getElementById('start_date');
                            if (startDateInput) {
                                startDateInput.value = response.next_start_date;
                                updateEndDate();
                            }
                        }

                        // Fill amount if available
                        if (response.amount) {
                            const amountInput = document.getElementById('amount');
                            if (amountInput) amountInput.value = response.amount;
                        }

                        // Get next membership details
                        $.ajax({
                            url: 'get_next_membership.php',
                            method: 'GET',
                            data: {
                                client_id: client.client_id
                            },
                            success: function(nextResponse) {
                                if (nextResponse.error) {
                                    console.error('Error:', nextResponse.error);
                                    return;
                                }

                                // Update form with next membership details
                                if (nextResponse) {
                                    // Update plan if different
                                    if (nextResponse.plan_id && nextResponse.plan_id !== response.plan_id) {
                                        const planDropdown = document.getElementById('plan_id');
                                        if (planDropdown) {
                                            planDropdown.value = nextResponse.plan_id;
                                            updateAmount();
                                            toggleCoupleName();
                                        }
                                    }

                                    // Update start date if different
                                    if (nextResponse.next_start_date && nextResponse.next_start_date !== response.next_start_date) {
                                        const startDateInput = document.getElementById('start_date');
                                        if (startDateInput) {
                                            startDateInput.value = nextResponse.next_start_date;
                                            updateEndDate();
                                        }
                                    }

                                    // Update amount if different
                                    if (nextResponse.amount && nextResponse.amount !== response.amount) {
                                        const amountInput = document.getElementById('amount');
                                        if (amountInput) amountInput.value = nextResponse.amount;
                                    }
                                }
                            },
                            error: function() {
                                console.error('Error loading next membership details');
                            }
                        });
                    }
                },
                error: function() {
                    console.error('Error loading current membership details');
                }
            });
        }

        // Close search results when clicking outside
        document.addEventListener('click', function(e) {
            const searchContainer = document.querySelector('.client-search-container');
            if (searchContainer && !searchContainer.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });

        // ======================== Collect Fees Function ========================
        function collectFees(clientId) {
            // Get current membership details
            $.ajax({
                url: 'get_client_membership.php',
                method: 'GET',
                data: {
                    client_id: clientId
                },
                success: function(response) {
                    if (response.error) {
                        console.error('Error:', response.error);
                        return;
                    }

                    // Fill the form with current membership details
                    if (response) {
                        // Set client search value
                        const clientSearch = document.getElementById('client_search');
                        const clientIdInput = document.getElementById('client_id');
                        if (clientSearch) clientSearch.value = response.client_name;
                        if (clientIdInput) clientIdInput.value = response.client_id;

                        // Fill plan if available
                        if (response.plan_id) {
                            const planDropdown = document.getElementById('plan_id');
                            if (planDropdown) {
                                planDropdown.value = response.plan_id;
                                updateAmount();
                                toggleCoupleName();
                            }
                        }

                        // Fill start date if available
                        if (response.next_start_date) {
                            const startDateInput = document.getElementById('start_date');
                            if (startDateInput) {
                                startDateInput.value = response.next_start_date;
                                updateEndDate();
                            }
                        }

                        // Fill amount if available
                        if (response.amount) {
                            const amountInput = document.getElementById('amount');
                            if (amountInput) amountInput.value = response.amount;
                        }

                        // Get next membership details
                        $.ajax({
                            url: 'get_next_membership.php',
                            method: 'GET',
                            data: {
                                client_id: clientId
                            },
                            success: function(nextResponse) {
                                if (nextResponse.error) {
                                    console.error('Error:', nextResponse.error);
                                    return;
                                }

                                // Update form with next membership details
                                if (nextResponse) {
                                    // Update plan if different
                                    if (nextResponse.plan_id && nextResponse.plan_id !== response.plan_id) {
                                        const planDropdown = document.getElementById('plan_id');
                                        if (planDropdown) {
                                            planDropdown.value = nextResponse.plan_id;
                                            updateAmount();
                                            toggleCoupleName();
                                        }
                                    }

                                    // Update start date if different
                                    if (nextResponse.next_start_date && nextResponse.next_start_date !== response.next_start_date) {
                                        const startDateInput = document.getElementById('start_date');
                                        if (startDateInput) {
                                            startDateInput.value = nextResponse.next_start_date;
                                            updateEndDate();
                                        }
                                    }

                                    // Update amount if different
                                    if (nextResponse.amount && nextResponse.amount !== response.amount) {
                                        const amountInput = document.getElementById('amount');
                                        if (amountInput) amountInput.value = nextResponse.amount;
                                    }
                                }
                            },
                            error: function() {
                                console.error('Error loading next membership details');
                            }
                        });
                    }
                },
                error: function() {
                    console.error('Error loading current membership details');
                }
            });
        }

        // ======================== Event Initializers ========================
        function initializeEventListeners() {
            // Get all potential elements
            const elements = {
                clientSearch: document.getElementById('client_search'),
                planDropdown: document.getElementById('plan_id'),
                startDateInput: document.getElementById('start_date'),
                paymentDropdown: document.getElementById('payment_type'),
                searchInput: document.getElementById('searchInput')
            };

            // Initialize client search if it exists
            if (elements.clientSearch) {
                const searchContainer = elements.clientSearch.closest('.client-search-container');
                if (searchContainer) {
                    searchContainer.appendChild(searchResults);
                }

                elements.clientSearch.addEventListener('input', function() {
                    searchClients(this);
                });

                elements.clientSearch.addEventListener('focus', function() {
                    if (this.value.length >= 2) {
                        searchClients(this);
                    }
                });
            }

            // Initialize plan dropdown if it exists
            if (elements.planDropdown) {
                elements.planDropdown.addEventListener('change', () => {
                    updateAmount();
                    toggleCoupleName();
                    updateEndDate();
                });
            }

            // Initialize start date input if it exists
            if (elements.startDateInput) {
                elements.startDateInput.addEventListener('change', updateEndDate);
            }

            // Initialize payment dropdown if it exists
            if (elements.paymentDropdown) {
                elements.paymentDropdown.addEventListener('change', () => {
                    if (elements.paymentDropdown.value === 'Cash') openCurrencyPopup();
                });
            }

            // Initialize search input if it exists
            if (elements.searchInput) {
                elements.searchInput.addEventListener('keyup', () => {
                    filterTable(elements.searchInput, 'table10Plus');
                    filterTable(elements.searchInput, 'tableLessThan10');
                    filterTable(elements.searchInput, 'tableExpired');
                });
            }
        }

        // ======================== DOM Initialization ========================
        document.addEventListener('DOMContentLoaded', () => {
            initializeEventListeners();
            updateAmount();
            toggleCoupleName();
        });

        // Add some CSS for hover effect
        const style = document.createElement('style');
        style.textContent = `
            .hover-bg-light:hover {
                background-color: #f8f9fa;
            }
            .search-results div {
                transition: background-color 0.2s;
            }
        `;
        document.head.appendChild(style);
    </script>

</body>

</html>