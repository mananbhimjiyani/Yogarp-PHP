<?php
ob_start();
require_once 'Path.php';
require_once 'db.php';
include 'includes/header.php';

// Initialize variables
$studio_id = 0;
$studio_name = $studio_address = $studio_city = $studio_state = $studio_country = $studio_contact = $studio_email = '';
$is_editing = false;
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $studio_name = $conn->real_escape_string($_POST['studio_name']);
    $studio_address = $conn->real_escape_string($_POST['studio_address']);
    $studio_city = $conn->real_escape_string($_POST['studio_city']);
    $studio_state = $conn->real_escape_string($_POST['studio_state']);
    $studio_country = $conn->real_escape_string($_POST['studio_country']);
    $studio_contact = $conn->real_escape_string($_POST['studio_contact']);
    $studio_email = $conn->real_escape_string($_POST['studio_email']);

    if (isset($_POST['studio_id']) && $_POST['studio_id']) {
        // Update an existing studio
        $studio_id = $_POST['studio_id'];
        $sql = "UPDATE studio SET studio_name='$studio_name', studio_address='$studio_address', studio_city='$studio_city', 
                studio_state='$studio_state', studio_country='$studio_country', studio_contact='$studio_contact', studio_email='$studio_email' 
                WHERE studio_id='$studio_id'";
        $message = 'Studio updated successfully';
    } else {
        // Insert a new studio
        $sql = "INSERT INTO studio (studio_name, studio_address, studio_city, studio_state, studio_country, studio_contact, studio_email) 
                VALUES ('$studio_name', '$studio_address', '$studio_city', '$studio_state', '$studio_country', '$studio_contact', '$studio_email')";
        $message = 'Studio added successfully';
    }

    // Execute the query and check for success
    if ($conn->query($sql) === TRUE) {
        header('Location: ' . $_SERVER['PHP_SELF']); // Redirect to clear the form
        exit;
    } else {
        $message = "Error: " . $conn->error;
    }
}

// Handle the edit action
if (isset($_GET['edit'])) {
    $studio_id = $_GET['edit'];
    $result = $conn->query("SELECT * FROM studio WHERE studio_id='$studio_id' LIMIT 1");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $is_editing = true;
        $studio_name = $row['studio_name'];
        $studio_address = $row['studio_address'];
        $studio_city = $row['studio_city'];
        $studio_state = $row['studio_state'];
        $studio_country = $row['studio_country'];
        $studio_contact = $row['studio_contact'];
        $studio_email = $row['studio_email'];
    }
}

// Handle the delete action
if (isset($_GET['delete'])) {
    $studio_id = $_GET['delete'];
    $sql = "DELETE FROM studio WHERE studio_id='$studio_id'";
    if ($conn->query($sql) === TRUE) {
        $message = 'Studio deleted successfully';
        header('Location: ' . $_SERVER['PHP_SELF']); // Redirect after deletion
        exit;
    } else {
        $message = "Error: " . $conn->error;
    }
}
ob_end_flush();
?>

<!-- HTML Form and Display Logic -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Studio Management</title>
</head>
<body>
<div class="container">
    <?php if ($message): ?>
        <div class="alert alert-info"><?php echo $message; ?></div>
    <?php endif; ?>

    <h2 class="mb-4"><?php echo $is_editing ? 'Edit Studio' : 'Add Studio'; ?></h2>
    <form method="POST" action="">
        <input type="hidden" name="studio_id" value="<?php echo $studio_id; ?>">
        <div class="mb-3">
            <label for="studio_name" class="form-label">Studio Name</label>
            <input type="text" class="form-control" id="studio_name" name="studio_name" value="<?php echo htmlspecialchars($studio_name); ?>" required>
        </div>
        <div class="mb-3">
            <label for="studio_address" class="form-label">Address</label>
            <input type="text" class="form-control" id="studio_address" name="studio_address" value="<?php echo htmlspecialchars($studio_address); ?>">
        </div>
        <div class="mb-3">
            <label for="studio_city" class="form-label">City</label>
            <input type="text" class="form-control" id="studio_city" name="studio_city" value="<?php echo htmlspecialchars($studio_city); ?>">
        </div>
        <div class="mb-3">
            <label for="studio_state" class="form-label">State</label>
            <input type="text" class="form-control" id="studio_state" name="studio_state" value="<?php echo htmlspecialchars($studio_state); ?>">
        </div>
        <div class="mb-3">
            <label for="studio_country" class="form-label">Country</label>
            <input type="text" class="form-control" id="studio_country" name="studio_country" value="<?php echo htmlspecialchars($studio_country); ?>">
        </div>
        <div class="mb-3">
            <label for="studio_contact" class="form-label">Contact Number</label>
            <input type="text" class="form-control" id="studio_contact" name="studio_contact" value="<?php echo htmlspecialchars($studio_contact); ?>">
        </div>
        <div class="mb-3">
            <label for="studio_email" class="form-label">Email</label>
            <input type="email" class="form-control" id="studio_email" name="studio_email" value="<?php echo htmlspecialchars($studio_email); ?>">
        </div>
        <button type="submit" class="btn btn-primary"><?php echo $is_editing ? 'Update Studio' : 'Add Studio'; ?></button>
    </form>

    <!-- Display all studio -->
    <h2 class="mt-4 mb-4">All studio</h2>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Studio ID</th>
                <th>Name</th>
                <th>Address</th>
                <th>City</th>
                <th>State</th>
                <th>Country</th>
                <th>Contact</th>
                <th>Email</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $result = $conn->query("SELECT * FROM studio");
        while ($row = $result->fetch_assoc()):
        ?>
            <tr>
                <td><?php echo $row['studio_id']; ?></td>
                <td><?php echo $row['studio_name']; ?></td>
                <td><?php echo $row['studio_address']; ?></td>
                <td><?php echo $row['studio_city']; ?></td>
                <td><?php echo $row['studio_state']; ?></td>
                <td><?php echo $row['studio_country']; ?></td>
                <td><?php echo $row['studio_contact']; ?></td>
                <td><?php echo $row['studio_email']; ?></td>
                <td>
                    <a href="?edit=<?php echo $row['studio_id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                    <a href="?delete=<?php echo $row['studio_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this studio?');">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
