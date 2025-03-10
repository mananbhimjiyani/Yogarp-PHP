<?php
ob_start(); // Start output buffering
require_once 'Path.php'; // Include your path file
require_once 'db.php'; // Include database connection
include 'includes/header.php'; // Include your header
// Check if the user is logged in by verifying a session variable, such as 'user_id'
if (!isset($_SESSION['user_id'])) {
    // If user is not logged in, redirect to index.php
    header("Location: login.php");
    exit(); // Ensure no further code is executed after redirection
}
$plan_name = $count = $unit = $amount = $remarks = '';
$active = 0;
$is_editing = false;
$plan_id = 0; // Variable to store the plan ID if editing
$plan_duration = $count . ' ' . $unit;

// Handle editing - if 'id' is passed in the URL, load the plan data
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $plan_id = $_GET['id'];
    $is_editing = true;

    // Fetch the existing plan data
    $query = "SELECT * FROM membership_plan WHERE plan_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $plan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $plan = $result->fetch_assoc();

    // Pre-fill form fields with the existing plan data
    if ($plan) {
        $plan_name = $plan['plan_name'];
        $count = $plan['plan_duration'];
		$plan_type = $plan['plan_type'];
        $amount = $plan['amount'];
        $active = $plan['active'];
        $remarks = $plan['remarks'];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $plan_name = $_POST['plan_name'];
    $count = $_POST['count']; // Corrected input name
    $unit = $_POST['unit'];   // Corrected input name
    $plan_duration = $count . ' ' . $unit; // Construct plan_duration dynamically
    $plan_type = $_POST['plan_type']; // Ensure plan_type is retrieved
    $amount = $_POST['amount'];
    $active = isset($_POST['active']) ? 1 : 0;
    $remarks = $_POST['remarks'];

    if ($is_editing) {
        // Update the existing plan
        $update_query = "UPDATE membership_plan 
                         SET plan_name = ?, plan_type = ?, plan_duration = ?, amount = ?, active = ?, remarks = ? 
                         WHERE plan_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sssiisi", $plan_name, $plan_type, $plan_duration, $amount, $active, $remarks, $plan_id);

        if ($stmt->execute()) {
            header("Location: plan.php?message=Plan updated successfully");
            exit();
        } else {
            echo "Error updating plan: " . $conn->error;
        }
    } else {
        // Insert a new plan
        $insert_query = "INSERT INTO membership_plan (plan_name, plan_type, plan_duration, amount, active, remarks, created_at) 
                         VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("sssiss", $plan_name, $plan_type, $plan_duration, $amount, $active, $remarks);

        if ($stmt->execute()) {
            header("Location: plan.php?message=Plan added successfully");
            exit();
        } else {
            echo "Error adding plan: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Plan</title>
     <script>
        // Function to dynamically update the plan_name field
        function updatePlanName() {
            const planType = document.getElementById('plan_type').value;
            const count = document.getElementById('count').value;
            const unit = document.getElementById('unit').value;

            const planNameField = document.getElementById('plan_name');

            // Update the plan name field if all fields have values
            if (planType && count && unit) {
                planNameField.value = `${planType} - ${count} ${unit}`;
            } else {
                planNameField.value = ''; // Clear the plan name if any field is empty
            }
        }

        // Attach event listeners to fields for real-time updates
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('plan_type').addEventListener('change', updatePlanName);
            document.getElementById('count').addEventListener('input', updatePlanName);
            document.getElementById('unit').addEventListener('change', updatePlanName);
        });
    </script>
</head>
<body>
    <div class="container">
        <h2><?php echo $is_editing ? 'Edit' : 'Add'; ?> Plan</h2>
        <form action="" method="post">
            <div class="form-group">
                <label for="plan_name">Plan Name:</label>
                <input type="text" class="form-control" id="plan_name" name="plan_name" value="" required readonly>
            </div>
            <div class="form-group">
                <label for="plan_type">Plan Type:</label>
                <select class="form-control" id="plan_type" name="plan_type" required>
                    <option value="" disabled selected>Select a Type</option>
                    <option value="Regular">Regular</option>
                    <option value="Advance">Advance</option>
                    <option value="Food">Food Plan</option>
                    <option value="Couple - Advance">Couple - Advance</option>
                    <option value="Couple - Regular">Couple - Regular</option>
                </select>
            </div>
            <div class="form-group">
                <label for="count">Count:</label>
                <input type="number" class="form-control" id="count" name="count" value="" required>
            </div>
            <div class="form-group">
                <label for="unit">Unit:</label>
                <select class="form-control" id="unit" name="unit" required>
                    <option value="" disabled selected>Select a Unit</option>
                    <option value="Days">Days</option>
                    <option value="Session">Session</option>
                    <option value="Week">Week</option>
                    <option value="Month">Month</option>
                </select>
            </div>
            <div class="form-group">
                <label for="amount">Amount:</label>
                <input type="number" class="form-control" id="amount" name="amount" value="" required>
            </div>
            <div class="form-group">
                <label for="active">Active:</label>
                <input type="checkbox" id="active" name="active">
            </div>
            <div class="form-group">
                <label for="remarks">Remarks:</label>
                <textarea class="form-control" id="remarks" name="remarks"></textarea>
            </div>
            <button type="submit" class="btn btn-primary"><?php echo $is_editing ? 'Update' : 'Insert'; ?> Plan</button>
        </form>

        <hr>
        <h2>Plans</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Sr. No.</th>
                    <th>Plan Name</th>
                    <th>Plan Type</th>
                    <th>Plan Duration</th>
                    <th>Amount</th>
                    <th>Active</th>
                    <th>Remarks</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $serial_no = 1;
                view_plan($serial_no); // Call the function to view plans
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>


<?php
// Function to view plan and generate rows with Edit/Delete options
function view_plan($serial_no) {
    global $conn;

    // Ensure to select the id column
    $query = "SELECT plan_id, plan_name, plan_type, plan_duration, amount, active, remarks  FROM membership_plan GROUP BY plan_type, plan_duration ORDER BY  plan_type ASC, 
            plan_duration ASC";
    $result = $conn->query($query);

    while ($plan = $result->fetch_assoc()) {
		$formatted_plan_duration = ucfirst($plan['plan_duration']);
        echo "<tr>
            <td>{$serial_no}</td>
            <td>{$plan['plan_name']}</td>
			<td>{$plan['plan_type']}</td>
            <td>{$formatted_plan_duration}</td>
            <td>{$plan['amount']}</td>
            <td>" . ($plan['active'] ? 'Yes' : 'No') . "</td>
            <td>{$plan['remarks']}</td>
            <td>
                <a href='plan.php?id={$plan['plan_id']}' class='btn btn-warning btn-sm'>Edit</a>
                <a href='delete_plan.php?id={$plan['plan_id']}' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure you want to delete this plan?\");'>Delete</a>
            </td>
        </tr>";
        $serial_no++;
    }
}
ob_end_flush();
?>
 