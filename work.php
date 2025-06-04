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
// Handle insert or update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $work_id = isset($_POST['work_id']) ? intval($_POST['work_id']) : 0;
    $work = trim($_POST['work']);
    $remarks = trim($_POST['remarks']);
    $active = isset($_POST['active']) ? 1 : 0;

    // Check for duplicate entry
    if ($work_id == 0) { // Only check if we're inserting a new record
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM work WHERE work = ?");
        $checkStmt->bind_param("s", $work);
        $checkStmt->execute();
        $checkStmt->bind_result($count);
        $checkStmt->fetch();
        $checkStmt->close();

        if ($count > 0) {
            echo "<div class='alert alert-danger'>Error: Work entry already exists!</div>";
        } else {
            // Insert new record
            $stmt = $conn->prepare("INSERT INTO work (work, remarks, active) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $work, $remarks, $active);
            if ($stmt->execute()) {
                echo "<div class='alert alert-success'>Record saved successfully!</div>";
            } else {
                echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
            }
            $stmt->close();
        }
    } else {
        // Update existing record
        $stmt = $conn->prepare("UPDATE work SET work = ?, remarks = ?, active = ? WHERE work_id = ?");
        $stmt->bind_param("ssii", $work, $remarks, $active, $work_id);
        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>Record updated successfully!</div>";
        } else {
            echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM work WHERE work_id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Record deleted successfully!</div>";
    } else {
        echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// Fetch records for display
$result = $conn->query("SELECT * FROM work");
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work Management</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<div class="container mt-5">
    <h2 class="text-center">Work Management</h2>

    <!-- Form for inserting and editing records -->
    <form method="POST" class="mb-4">
        <input type="hidden" name="work_id" id="work_id" value="">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="work">Work</label>
                <input type="text" class="form-control" name="work" id="work" required>
            </div>
            <div class="form-group col-md-6">
                <label for="active">Active</label>
                <input type="checkbox" name="active" id="active" value="1">
            </div>
        </div>
        <div class="form-group">
            <label for="remarks">Remarks</label>
            <textarea class="form-control" name="remarks" id="remarks" rows="3"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Save</button>
    </form>

    <!-- Table for displaying records -->
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Work ID</th>
                <th>Work</th>
                <th>Remarks</th>
                <th>Active</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['work_id']; ?></td>
                <td><?php echo $row['work']; ?></td>
                <td><?php echo $row['remarks']; ?></td>
                <td><?php echo $row['active'] ? 'Yes' : 'No'; ?></td>
                <td>
                    <a href="javascript:void(0);" onclick="editRecord(<?php echo $row['work_id']; ?>, '<?php echo addslashes($row['work']); ?>', '<?php echo addslashes($row['remarks']); ?>', <?php echo $row['active']; ?>)" class="btn btn-warning btn-sm">Edit</a>
                    <a href="?delete=<?php echo $row['work_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this record?')">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
    function editRecord(work_id, work, remarks, active) {
        document.getElementById('work_id').value = work_id;
        document.getElementById('work').value = work;
        document.getElementById('remarks').value = remarks;
        document.getElementById('active').checked = active == 1;
    }
</script>

<?php $conn->close();
include 'includes/footer.php'; // Include the footer file ?>
</body>
</html>