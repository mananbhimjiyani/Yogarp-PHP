<?php
ob_start();
require_once 'Path.php';
require_once 'db.php';
include 'includes/header.php';
// Check if the user is logged in by verifying a session variable, such as 'user_id'
if (!isset($_SESSION['user_id'])) {
    // If user is not logged in, redirect to index.php
    header("Location: login.php");
    exit(); // Ensure no further code is executed after redirection
}
// Initialize variables
$is_editing = false;
$batch_name = $batch_type = $batch_start_time = $batch_end_time = $notes = '';
$active = 0;
$batch_id = 0;

// Insert or Update logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $batch_name = $conn->real_escape_string($_POST['batch_name']);
    $batch_type = $conn->real_escape_string($_POST['batch_type']);
    $batch_start_time = $conn->real_escape_string($_POST['batch_start_time']);
    $batch_end_time = $conn->real_escape_string($_POST['batch_end_time']);
    $notes = $conn->real_escape_string($_POST['notes']);
    $active = isset($_POST['active']) ? 1 : 0;

    if (isset($_POST['batch_id']) && $_POST['batch_id']) {
        // Update existing batch
        $batch_id = $_POST['batch_id'];
        $sql = "UPDATE batch SET batch_name='$batch_name', batch_type='$batch_type', batch_start_time='$batch_start_time', 
        batch_end_time='$batch_end_time', notes='$notes', active='$active' WHERE batch_id='$batch_id'";

        $_SESSION['message'] = 'Batch updated successfully';
    } else {
        // Insert new batch
        $sql = "INSERT INTO batch (batch_name, batch_type, batch_start_time, batch_end_time, notes, active) 
                VALUES ('$batch_name', '$batch_type', '$batch_start_time', '$batch_end_time', '$notes', '$active')";
        $_SESSION['message'] = 'Batch added successfully';
    }

    if ($conn->query($sql) === TRUE) {
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        echo "Error: " . $conn->error;
    }
}

// Edit logic
if (isset($_GET['edit'])) {
    $batch_id = $_GET['edit'];
    $result = $conn->query("SELECT * FROM batch WHERE batch_id='$batch_id' LIMIT 1");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $is_editing = true;
        $batch_name = $row['batch_name'];
        $batch_type = $row['batch_type'];
        $batch_start_time = $row['batch_start_time'];
        $batch_end_time = $row['batch_end_time'];
        $notes = $row['notes'];
        $active = $row['active'];
    }
}

// Delete logic
if (isset($_GET['delete'])) {
    $batch_id = $_GET['delete'];
    $sql = "DELETE FROM batch WHERE batch_id='$batch_id'";
    if ($conn->query($sql) === TRUE) {
        $_SESSION['message'] = 'Batch deleted successfully';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        echo "Error: " . $conn->error;
    }
}
ob_end_flush();
?>

<!-- HTML Form and Table -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Batch Management</title>
</head>
<body>
<div class="container">
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success">
            <?php
            echo $_SESSION['message'];
            unset($_SESSION['message']);
            ?>
        </div>
    <?php endif; ?>

    <h2 class="mb-4"><?php echo $is_editing ? 'Edit Batch' : 'Add Batch'; ?></h2>
    <form method="POST" action="">
        <input type="hidden" name="batch_id" value="<?php echo $batch_id; ?>">
        <div class="mb-3">
            <label for="batch_name" class="form-label">Batch Name</label>
            <input type="text" class="form-control" id="batch_name" name="batch_name" value="<?php echo htmlspecialchars($batch_name); ?>" required>
        </div>
        <div class="mb-3">
            <label for="batch_type" class="form-label">Type</label>
            <select class="form-control" id="type" name="batch_type" required>
                <option value="">Select Type</option>
                <option value="Regular" <?php if ($batch_type == 'Regular') echo 'selected'; ?>>Regular</option>
                <option value="Advance" <?php if ($batch_type == 'Advance') echo 'selected'; ?>>Advance</option>
                <option value="Special" <?php if ($batch_type == 'Special') echo 'selected'; ?>>Special</option>
                <option value="Food Plan" <?php if ($batch_type == 'Food Plan') echo 'selected'; ?>>Food Plan</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="batch_start_time" class="form-label">Batch Start Time</label>
            <input type="time" class="form-control" id="batch_start_time" name="batch_start_time" value="<?php echo htmlspecialchars($batch_start_time); ?>" required>
        </div>
        <div class="mb-3">
            <label for="batch_end_time" class="form-label">Batch End Time</label>
            <input type="time" class="form-control" id="batch_end_time" name="batch_end_time" value="<?php echo htmlspecialchars($batch_end_time); ?>" required>
        </div>
        <div class="mb-3">
            <label for="notes" class="form-label">Notes</label>
            <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($notes); ?></textarea>
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="active" name="active" value="1" <?php if ($active) echo 'checked'; ?>>
            <label class="form-check-label" for="active">Active</label>
        </div>
        <button type="submit" class="btn btn-primary"><?php echo $is_editing ? 'Update' : 'Submit'; ?></button>
    </form>

    <h2 class="mt-5">Batch List</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Batch Name</th>
                <th>Type</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Active</th>
                <th>Notes</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php $sr=1;
        $result = $conn->query("SELECT * FROM batch ORDER BY batch_type ASC, batch_start_time ASC ");
        while ($row = $result->fetch_assoc()):
        ?>
            <tr>
                <td><?= $sr++ ?></td>
                <td><?php echo $row['batch_name']; ?></td>
                <td><?php echo $row['batch_type']; ?></td>
                <td><?php echo $row['batch_start_time']; ?></td>
                <td><?php echo $row['batch_end_time']; ?></td>
                <td><?php echo $row['active'] ? 'Yes' : 'No'; ?></td>
                <td><?php echo $row['notes']; ?></td>
                <td>
                    <a href="?edit=<?php echo $row['batch_id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                    <a href="?delete=<?php echo $row['batch_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this batch?');">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
