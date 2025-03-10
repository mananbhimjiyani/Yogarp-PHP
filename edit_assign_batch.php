<?php
require_once 'Path.php';
require_once 'db.php';
include 'includes/header.php';

$id = $_GET['id'] ?? '';

if (!$id) {
    echo '<div class="alert alert-danger">No ID provided.</div>';
    exit;
}

// Fetch the existing assignment data
$query = "SELECT * FROM client_batch_assign WHERE id = '$id'";
$result = $conn->query($query);
$assignment = $result->fetch_assoc();

if (!$assignment) {
    echo '<div class="alert alert-danger">Assignment not found.</div>';
    exit;
}

// Handle form submission for updating the assignment
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $studio_batch_id = $_POST['studio_batch_id'];
    $client_id = $_POST['client_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    $studio_batch_id = $conn->real_escape_string($studio_batch_id);
    $client_id = $conn->real_escape_string($client_id);
    $start_date = $conn->real_escape_string($start_date);
    $end_date = $conn->real_escape_string($end_date);

    $update_query = "UPDATE client_batch_assign SET studio_batch_id='$studio_batch_id', client_id='$client_id', start_date='$start_date', end_date='$end_date' WHERE id='$id'";

    if ($conn->query($update_query)) {
        echo '<div class="alert alert-success">Assignment updated successfully.</div>';
        header("Location: assign_batch.php");
        exit;
    } else {
        echo '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
    }
}
?>

<div class="container">
    <h2 class="my-4">Edit Assignment</h2>

    <form method="POST" action="">
        <div class="mb-3">
            <label for="studio_batch_id" class="form-label">Studio Batch ID</label>
            <input type="number" class="form-control" id="studio_batch_id" name="studio_batch_id" value="<?php echo htmlspecialchars($assignment['studio_batch_id']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="client_id" class="form-label">Client ID</label>
            <input type="number" class="form-control" id="client_id" name="client_id" value="<?php echo htmlspecialchars($assignment['client_id']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="start_date" class="form-label">Start Date</label>
            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($assignment['start_date']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="end_date" class="form-label">End Date</label>
            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($assignment['end_date']); ?>">
        </div>
        <button type="submit" class="btn btn-primary">Update Assignment</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
