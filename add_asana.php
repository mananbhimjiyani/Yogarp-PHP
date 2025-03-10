<?php
ob_start();
require_once 'Path.php'; // Include the path file for constants
require_once 'db.php'; // Include database connection
include 'includes/header.php';
require_once 'includes/batch_functions.php'; // Include the function to get batches and studios
// Check if the user is logged in by verifying a session variable, such as 'user_id'
if (!isset($_SESSION['user_id'])) {
    // If user is not logged in, redirect to index.php
    header("Location: login.php");
    exit(); // Ensure no further code is executed after redirection
}

// Initialize empty variables for form handling
$asana_id = $sname = $asana_name = $asana_type = $asana_subtype = $note = "";
$error = "";

// Handle form submission (Insert or Update)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sname = $_POST['sname'];
    $asana_name = $_POST['asana_name'];
    $asana_type = $_POST['asana_type'];
    $asana_subtype = $_POST['asana_subtype'];
    $note = $_POST['note'];

    if (!empty($_POST['asana_id'])) {
        // Update an existing record
        $asana_id = $_POST['asana_id'];
        $updateQuery = "UPDATE asana SET sname=?, asana_name=?, asana_type=?, asana_subtype=?, note=? WHERE asana_id=?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("sssssi", $sname, $asana_name, $asana_type, $asana_subtype, $note, $asana_id);

        if ($stmt->execute()) {
            header("Location: asana_crud.php");
            exit();
        } else {
            $error = "Error updating record: " . $stmt->error;
        }
    } else {
        // Insert a new record
        $insertQuery = "INSERT INTO asana (sname, asana_name, asana_type, asana_subtype, note) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("sssss", $sname, $asana_name, $asana_type, $asana_subtype, $note);

        if ($stmt->execute()) {
            header("Location: asana_crud.php");
            exit();
        } else {
            $error = "Error inserting record: " . $stmt->error;
        }
    }
}

// Handle delete request
if (isset($_GET['delete'])) {
    $asana_id = $_GET['delete'];
    $deleteQuery = "DELETE FROM asana WHERE asana_id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $asana_id);

    if ($stmt->execute()) {
        header("Location: asana_crud.php");
        exit();
    } else {
        $error = "Error deleting record: " . $stmt->error;
    }
}

// Handle edit request (fetch data for the edit form)
if (isset($_GET['edit'])) {
    $asana_id = $_GET['edit'];
    $selectQuery = "SELECT * FROM asana WHERE asana_id = ?";
    $stmt = $conn->prepare($selectQuery);
    $stmt->bind_param("i", $asana_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $asana_id = $row['asana_id'];
        $sname = $row['sname'];
        $asana_name = $row['asana_name'];
        $asana_type = $row['asana_type'];
        $asana_subtype = $row['asana_subtype'];
        $note = $row['note'];
    }
}

// Fetch all records for display
$asanaQuery = "SELECT * FROM asana";
$result = $conn->query($asanaQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asana Management</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<div class="container mt-4">
    <h2 class="text-center">Manage Asanas</h2>

    <!-- Error Message Display -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Form to Add/Edit Asanas -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="asana_crud.php" method="POST">
                <input type="hidden" name="asana_id" value="<?= $asana_id ?>">

                <div class="form-row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="sname">Sanskrit Name</label>
<input type="text" name="sname" id="sname" class="form-control" value="<?= htmlspecialchars($sname ?? '', ENT_QUOTES) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="asana_name">Asana Name</label>
<input type="text" name="asana_name" id="asana_name" class="form-control" value="<?= htmlspecialchars($asana_name ?? '', ENT_QUOTES) ?>" required>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="asana_type">Asana Type</label>
<input type="text" name="asana_type" id="asana_type" class="form-control" value="<?= htmlspecialchars($asana_type ?? '', ENT_QUOTES) ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="asana_subtype">Asana Subtype</label>
<input type="text" name="asana_subtype" id="asana_subtype" class="form-control" value="<?= htmlspecialchars($asana_subtype ?? '', ENT_QUOTES) ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="note">Note</label>
<textarea name="note" id="note" class="form-control"><?= htmlspecialchars($note ?? '', ENT_QUOTES) ?></textarea>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-primary">Save Asana</button>
                    <a href="asana_crud.php" class="btn btn-secondary">Clear Form</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Display Asanas in a Table -->
    <table class="table table-bordered">
        <thead class="thead-dark">
            <tr>
                <th>ID</th>
                <th>Sanskrit Name</th>
                <th>Asana Name</th>
                <th>Asana Type</th>
                <th>Asana Subtype</th>
                <th>Note</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                   <td><?= htmlspecialchars($row['asana_id'] ?? '', ENT_QUOTES) ?></td>
<td><?= htmlspecialchars($row['sname'] ?? '', ENT_QUOTES) ?></td>
<td><?= htmlspecialchars($row['asana_name'] ?? '', ENT_QUOTES) ?></td>
<td><?= htmlspecialchars($row['asana_type'] ?? '', ENT_QUOTES) ?></td>
<td><?= htmlspecialchars($row['asana_subtype'] ?? '', ENT_QUOTES) ?></td>
<td><?= htmlspecialchars($row['note'] ?? '', ENT_QUOTES) ?></td>
                    <td>
                        <a href="asana_crud.php?edit=<?= $row['asana_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                        <a href="asana_crud.php?delete=<?= $row['asana_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this asana?');">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>
