<?php
require_once 'Path.php';
require_once 'db.php';
require_once 'includes/header.php';

// Fetch studios and batches for dropdowns
$studios = $conn->query("SELECT studio_id, studio_name FROM studio");
$batches = $conn->query("SELECT batch_id, batch_name FROM batch WHERE active=1 ORDER BY batch_name ASC");

// Insert assignment
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $studio_id = $_POST['studio_id'];
    $batch_id = $_POST['batch_id'];
    $active = isset($_POST['active']) ? 1 : 0; // Checkbox for active status

    // Insert query
    $sql = "INSERT INTO studio_batch (studio_id, batch_id, active) 
            VALUES ('$studio_id', '$batch_id', '$active')";

    if ($conn->query($sql) === TRUE) {
        $message = "Batch assigned to studio successfully";
    } else {
        $message = "Error: " . $conn->error;
    }
}

// Fetch all assignments for display, sorted by Studio Name and then Batch Timing
$sql = "
    SELECT sb.studio_batch_id, sb.studio_id, sb.batch_id, sb.active, 
           b.batch_name, s.studio_name, 
           CONCAT(b.batch_start_time, ' - ', b.batch_end_time) AS batch_timing
    FROM studio_batch sb
    JOIN studio s ON sb.studio_id = s.studio_id 
    JOIN batch b ON sb.batch_id = b.batch_id 
    ORDER BY s.studio_name ASC, batch_timing ASC
";
$result = $conn->query($sql);

// Fetch studio and batch names for easy lookup
$studio_names = [];
$batch_names = [];

// Fetch studio names
$studio_result = $conn->query("SELECT studio_id, studio_name FROM studio");
while ($row = $studio_result->fetch_assoc()) {
    $studio_names[$row['studio_id']] = $row['studio_name'];
}

// Fetch batch names
$batch_result = $conn->query("SELECT batch_id, batch_name FROM batch");
while ($row = $batch_result->fetch_assoc()) {
    $batch_names[$row['batch_id']] = $row['batch_name'];
}
?>

<!-- HTML Form for Assigning Batch to Studio -->
<h2 class="mb-4">Assign Batch to Studio</h2>
<form method="POST" action="">
    <div class="mb-3">
        <label for="studio_id" class="form-label">Studio</label>
        <select class="form-select" id="studio_id" name="studio_id" required>
            <option value="">Select Studio</option>
            <?php while ($row = $studios->fetch_assoc()) { ?>
                <option value="<?= $row['studio_id'] ?>"><?= $row['studio_name'] ?></option>
            <?php } ?>
        </select>
    </div>
    <div class="mb-3">
        <label for="batch_id" class="form-label">Batch</label>
        <select class="form-select" id="batch_id" name="batch_id" required>
            <option value="">Select Batch</option>
            <?php while ($row = $batches->fetch_assoc()) { ?>
                <option value="<?= $row['batch_id'] ?>"><?= $row['batch_name'] ?></option>
            <?php } ?>
        </select>
    </div>
    <div class="mb-3">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="active" name="active" checked>
            <label class="form-check-label" for="active">Active</label>
        </div>
    </div>
    <button type="submit" class="btn btn-primary">Assign Batch</button>
</form>

<!-- Display Assignments -->
<h2 class="mt-4 mb-4">Batch Assignments</h2>
<?php if (isset($message)) { echo "<div class='alert alert-info'>$message</div>"; } ?>
<table class="table table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Studio Name</th>
            <th>Batch Name</th>
            <th>Active</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php $sr=1; while ($row = $result->fetch_assoc()) {  ?>
        <tr>
            <td><?= $sr++ ?></td>
            <td><?= isset($studio_names[$row['studio_id']]) ? $studio_names[$row['studio_id']] : 'Unknown' ?></td>
            <td><?= isset($batch_names[$row['batch_id']]) ? $batch_names[$row['batch_id']] : 'Unknown' ?></td>
            <td><?= $row['active'] ? 'Yes' : 'No' ?></td>
            <td>
                <a href="edit_studio_batch.php?id=<?= $row['studio_batch_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                <a href="delete_studio_batch.php?id=<?= $row['studio_batch_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this assignment?')">Delete</a>
            </td>
        </tr>
        <?php } ?>
    </tbody>
</table>
<div>&nbsp;</div>
<div>&nbsp;</div>
<?php require_once 'includes/footer.php'; ?>
