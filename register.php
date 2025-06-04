<?php
require_once 'Path.php';  // Include the path file for constants
require_once 'db.php';    // Include database connection

// Initialize variables and error messages
$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve form data
    $userID = trim($_POST['UserID']);
    $password = trim($_POST['password']);

    // Validate input
    if (empty($userID)) {
        $errors[] = "Mobile number is required.";
    } elseif (!preg_match('/^[0-9]{10}$/', $userID)) {
        $errors[] = "Mobile number must be 10 digits.";
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (!preg_match('/^[0-9]{6}$/', $password)) {
        $errors[] = "Password must be a 6-digit numeric value.";
    }

    // If no errors, proceed with registration
    if (empty($errors)) {
        // Encrypt the password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Set default values
        $active = 0; // User is not active by default
        $type = 1; // Fixed user type (standard user)

        // Prepare the SQL statement
        $sql = "INSERT INTO User (UserID, password, type, active) VALUES (?, ?, ?, ?)";
        
        // Use prepared statements for security
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $userID, $hashedPassword, $type, $active);

        // Execute the statement
        if ($stmt->execute()) {
            $success = "User registered successfully. Please wait for account activation.";
        } else {
            $errors[] = "Error: " . $stmt->error;
        }

        // Close the statement
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/header.php'; // Include the header file ?>
    <title>User Registration - Yoga Studio Management</title>
</head>
<body>
    <div class="main-content container" style="margin-left: 240px; margin-top: 20px;">
        <h2>Register</h2>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= $success; ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Registration Form -->
        <form action="" method="post">
            <div class="mb-3">
                <label for="UserID" class="form-label">Mobile Number</label>
                <input type="text" class="form-control" id="UserID" name="UserID" maxlength="10" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" maxlength="6" required>
            </div>
            <button type="submit" class="btn btn-primary">Register</button>
        </form>
    </div>

    <?php include 'includes/footer.php'; // Include the footer file ?>
</body>
</html>
