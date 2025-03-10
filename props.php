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
    $prop_id = isset($_POST['prop_id']) ? intval($_POST['prop_id']) : 0;
    $prop_name = trim($_POST['prop_name']);
    $quantity = intval($_POST['quantity']);
    $remarks = trim($_POST['remarks']);
    $active = isset($_POST['active']) ? 1 : 0;

    // Check for duplicate entry if inserting
    if ($prop_id == 0) { // Only check if we're inserting a new record
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM props WHERE prop_name = ?");
        $checkStmt->bind_param("s", $prop_name);
        $checkStmt->execute();
        $checkStmt->bind_result($count);
        $checkStmt->fetch();
        $checkStmt->close();

        if ($count > 0) {
            echo "<div class='alert alert-danger'>Error: Property name already exists!</div>";
        } else {
            // Insert new record
            $stmt = $conn->prepare("INSERT INTO props (prop_name, quantity, remarks, active, stamp) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("siis", $prop_name, $quantity, $remarks, $active);
            if ($stmt->execute()) {
                echo "<div class='alert alert-success'>Record saved successfully!</div>";
            } else {
                echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
            }
            $stmt->close();
        }
    } else {
        // Update existing record
        $stmt = $conn->prepare("UPDATE props SET prop_name = ?, quantity = ?, remarks = ?, active = ?, stamp = NOW() WHERE prop_id = ?");
        $stmt->bind_param("siisi", $prop_name, $quantity, $remarks, $active, $prop_id);
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
    $stmt = $conn->prepare("DELETE FROM props WHERE prop_id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Record deleted successfully!</div>";
    } else {
        echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// Fetch records for display
$result = $conn->query("SELECT * FROM props");
?>

<div class="container">
    <h2 class="my-4">Property Management</h2>
    <form method="POST" class="mb-4">
        <input type="hidden" name="prop_id" value="<?= isset($prop_id) ? $prop_id : 0; ?>">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="prop_name">Property Name</label>
                <input type="text" class="form-control" id="prop_name" name="prop_name" required value="<?= isset($prop_name) ? htmlspecialchars($prop_name) : ''; ?>">
            </div>
            <div class="form-group col-md-6">
                <label for="quantity">Quantity</label>
                <input type="number" class="form-control" id="quantity" name="quantity" required value="<?= isset($quantity) ? $quantity : ''; ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="remarks">Remarks</label>
            <textarea class="form-control" id="remarks" name="remarks"><?= isset($remarks) ? htmlspecialchars($remarks) : ''; ?></textarea>
        </div>
        <div class="form-group">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="active" name="active" <?= isset($active) && $active ? 'checked' : ''; ?>>
                <label class="form-check-label" for="active">Active</label>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Save</button>
    </form>

    <h3>Existing Properties</h3>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Property Name</th>
                <th>Quantity</th>
                <th>Remarks</th>
                <th>Active</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['prop_id']; ?></td>
                    <td><?= htmlspecialchars($row['prop_name']); ?></td>
                    <td><?= $row['quantity']; ?></td>
                    <td><?= htmlspecialchars($row['remarks']); ?></td>
                    <td><?= $row['active'] ? 'Yes' : 'No'; ?></td>
                    <td>
                        <a href="?edit=<?= $row['prop_id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                        <a href="?delete=<?= $row['prop_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this item?');">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
