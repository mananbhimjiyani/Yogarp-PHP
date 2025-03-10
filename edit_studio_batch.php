<?php
require_once 'Path.php';
require_once 'db.php';

// Fetch assignment details for editing
if (isset($_GET['id'])) {
    $studio_batch_id = $_GET['id'];

    $sql = "SELECT * FROM studio_batch WHERE studio_batch_id = $studio_batch_id";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $assignment = $result->fetch_assoc();
    } else {
        echo "<div class='alert alert-danger'>Assignment not found!</div>";
        exit;
    }
} else {
    echo "<div class='alert alert-danger'>No assignment ID provided!</div>";
    exit;
}

// Fetch studios and batches for dropdowns
$studios = $conn->query("SELECT studio_id, studio_name FROM studio");
$batches = $conn->query("SELECT batch_id, batch_name FROM batch");

// Update assignment details
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $studio_id = $_POST['studio_id'];
    $batch_id = $_POST['batch_id'];
    $active = isset($_POST['active']) ? 1 : 0; // Checkbox for active status

    $sql = "UPDATE studio_batch SET 
            studio_id = '$studio_id', 
            batch_id = '$batch_id', 
            active = '$active'
            WHERE studio_batch_id = $studio_batch_id";

    if ($conn->query($sql) === TRUE) {
        header("Location: assign_batch_to_studio.php?message=Assignment+updated+successfully");
        exit;
    } else {
        echo "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
    }
}

require_once 'includes/header.php';
?>

<!-- HTML Form for Editing Assignment -->
<h2 class="mb-4">Edit Assignment</h2>
<form method="POST" action="">
    <div class="mb-3">
        <label for="studio_id" class="form-label">Studio</label>
        <select class="form-select" id="studio_id" name="studio_id" required>
            <?php while ($row = $studios->fetch_assoc()) { ?>
                <option value="<?= $row['studio_id'] ?>" <?= $assignment['studio_id'] == $row['studio_id'] ? 'selected' : '' ?>><?= $row['studio_name'] ?></option>
            <?php } ?>
        </select>
    </div>
    <div class="mb-3">
        <label for="batch_id" class="form-label">Batch</label>
        <select class="form-select" id="batch_id" name="batch_id" required>
            <?php while ($row = $batches->fetch_assoc()) { ?>
                <option value="<?= $row['batch_id'] ?>" <?= $assignment['batch_id'] == $row['batch_id'] ? 'selected' : '' ?>><?= $row['batch_name'] ?></option>
            <?php } ?>
        </select>
    </div>
    <div class="mb-3">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="active" name="active" <?= $assignment['active'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="active">Active</label>
        </div>
    </div>
    <button type="submit" class="btn btn-primary">Update Assignment</button>
</form>

<?php require_once 'includes/footer.php'; ?>
