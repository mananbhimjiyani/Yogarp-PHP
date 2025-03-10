<?php
require_once 'Path.php';
require_once 'db.php';
include 'includes/header.php';

// Initialize variables
$client_id = '';
$studio_batch_id = '';
$start_date = '';
$end_date = '';

// Fetch existing assignment data based on the passed client_id and studio_batch_id
if (isset($_GET['client_id']) && isset($_GET['studio_batch_id'])) {
    $client_id = $_GET['client_id'];
    $studio_batch_id = $_GET['studio_batch_id'];

    $client_id = $conn->real_escape_string($client_id);
    $studio_batch_id = $conn->real_escape_string($studio_batch_id);

    $assignment_query = "SELECT * FROM client_batch_assign WHERE client_id = '$client_id' AND studio_batch_id = '$studio_batch_id'";
    $assignment_result = $conn->query($assignment_query);

    if ($row = $assignment_result->fetch_assoc()) {
        $start_date = $row['start_date'];
        $end_date = $row['end_date'];
    }
}

// Fetch studios for the dropdown
$studio_query = "SELECT studio_id, studio_name FROM studio";
$studio_result = $conn->query($studio_query);

?>

<div class="container mt-4">
    <h2>Update Client Batch Assignment</h2>

    <form method="POST" action="update_assign_batch.php">
        <input type="hidden" name="client_id" value="<?= htmlspecialchars($client_id) ?>">
        <input type="hidden" name="studio_batch_id" value="<?= htmlspecialchars($studio_batch_id) ?>">

        <div class="form-group">
            <label for="studio">Select Studio:</label>
            <select name="studio_id" id="studio" class="form-control" disabled>
                <?php while ($row = $studio_result->fetch_assoc()): ?>
                    <option value="<?= $row['studio_id'] ?>" <?= ($row['studio_id'] == $studio_batch_id) ? 'selected' : '' ?>>
                        <?= $row['studio_name'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group mt-3">
            <label for="start_date">Start Date:</label>
            <input type="date" name="start_date" id="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>" required>
        </div>

        <div class="form-group mt-3">
            <label for="end_date">End Date:</label>
            <input type="date" name="end_date" id="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
        </div>

        <button type="submit" name="update_batch" class="btn btn-primary mt-2">Update Batch</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
