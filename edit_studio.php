<?php
require_once 'Path.php';
require_once 'db.php';

// Fetch studio details for editing
if (isset($_GET['id'])) {
    $studio_id = $_GET['id'];

    // Get studio data from the database
    $sql = "SELECT * FROM studio WHERE studio_id = $studio_id";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $studio = $result->fetch_assoc();
    } else {
        echo "<div class='alert alert-danger'>Studio not found!</div>";
        exit;
    }
} else {
    echo "<div class='alert alert-danger'>No studio ID provided!</div>";
    exit;
}

// Update studio details logic
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $studio_name = $_POST['studio_name'];
    $studio_address = $_POST['studio_address'];
    $studio_city = $_POST['studio_city'];
    $studio_state = $_POST['studio_state'];
    $studio_country = $_POST['studio_country'];
    $studio_contact = $_POST['studio_contact'];
    $studio_email = $_POST['studio_email'];

    // Update query
    $sql = "UPDATE studio SET 
            studio_name = '$studio_name', 
            studio_address = '$studio_address', 
            studio_city = '$studio_city', 
            studio_state = '$studio_state', 
            studio_country = '$studio_country', 
            studio_contact = '$studio_contact', 
            studio_email = '$studio_email'
            WHERE studio_id = $studio_id";

    if ($conn->query($sql) === TRUE) {
        header("Location: add_studio.php?message=Studio+updated+successfully");
        exit; // Stop further script execution after redirect
    } else {
        echo "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
    }
}

require_once 'includes/header.php';
?>

<!-- HTML Form for Editing Studio -->
<h2 class="mb-4">Edit Studio</h2>
<form method="POST" action="" onsubmit="return confirmEdit()">
    <div class="mb-3">
        <label for="studio_name" class="form-label">Studio Name</label>
        <input type="text" class="form-control" id="studio_name" name="studio_name" value="<?= $studio['studio_name'] ?>" required>
    </div>
    <div class="mb-3">
        <label for="studio_address" class="form-label">Address</label>
        <input type="text" class="form-control" id="studio_address" name="studio_address" value="<?= $studio['studio_address'] ?>">
    </div>
    <div class="mb-3">
        <label for="studio_city" class="form-label">City</label>
        <input type="text" class="form-control" id="studio_city" name="studio_city" value="<?= $studio['studio_city'] ?>">
    </div>
    <div class="mb-3">
        <label for="studio_state" class="form-label">State</label>
        <input type="text" class="form-control" id="studio_state" name="studio_state" value="<?= $studio['studio_state'] ?>">
    </div>
    <div class="mb-3">
        <label for="studio_country" class="form-label">Country</label>
        <input type="text" class="form-control" id="studio_country" name="studio_country" value="<?= $studio['studio_country'] ?>">
    </div>
    <div class="mb-3">
        <label for="studio_contact" class="form-label">Contact Number</label>
        <input type="text" class="form-control" id="studio_contact" name="studio_contact" value="<?= $studio['studio_contact'] ?>">
    </div>
    <div class="mb-3">
        <label for="studio_email" class="form-label">Email</label>
        <input type="email" class="form-control" id="studio_email" name="studio_email" value="<?= $studio['studio_email'] ?>">
    </div>
    <button type="submit" class="btn btn-primary">Update</button>
    <a href="add_studio.php" class="btn btn-secondary">Cancel</a>
</form>

<!-- Confirmation Script -->
<script>
function confirmEdit() {
    return confirm("Are you sure you want to update this studio?");
}
</script>

<?php require_once 'includes/footer.php'; ?>
