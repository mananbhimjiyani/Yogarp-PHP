<?php
ob_start(); // Start output buffering
require_once 'Path.php';
require_once 'db.php';
include 'includes/header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    error_log("Login form submitted");

    $userID = trim($_POST['userID']);
    $password = trim($_POST['password']);

    if (empty($userID)) {
        $errors[] = "userID is required.";
        error_log("userID is empty");
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
        error_log("Password is empty");
    }

    if (empty($errors)) {
        $sql = "SELECT id, password, active, last_login, last_ip, type FROM User WHERE userID = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("SQL Error: " . $conn->error);
        }
        $stmt->bind_param("s", $userID);
        $stmt->execute();
        $stmt->store_result();

        error_log("Query executed. Rows found: " . $stmt->num_rows);

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($id, $hashedPassword, $active, $lastLogin, $lastIp, $userType);
            $stmt->fetch();

            error_log("User found: ID=$id, Active=$active, LastLogin=$lastLogin, LastIP=$lastIp, Type=$userType");

            if ($active == 1) {
                if (password_verify($password, $hashedPassword)) {
                    if (!empty($lastLogin) && $lastIp !== $_SERVER['REMOTE_ADDR']) {
                        $errors[] = "Your account is already logged in on another device.";
                        error_log("Account already logged in from another IP: $lastIp");
                    } else {
                        $_SESSION['user_id'] = $id;
                        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT']; // FIXED
                        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
                        $_SESSION['user_type'] = $userType;
                        $_SESSION['login_time'] = date('H:i');

                        error_log("User logged in successfully: " . print_r($_SESSION, true));

                        $sql = "UPDATE User SET last_login = NOW(), last_ip = ? WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("si", $_SESSION['ip_address'], $id);
                        $stmt->execute();

                        header("Location: dashboard.php");
                        exit();
                    }
                } else {
                    $errors[] = "Invalid password.";
                    error_log("Invalid password entered for userID: $userID");
                }
            } else {
                $errors[] = "Your account is not active. Please contact the administrator.";
                error_log("Inactive account login attempt: $userID");
            }
        } else {
            $errors[] = "No account found with that userID.";
            error_log("No account found for userID: $userID");
        }

        $stmt->close();
    }
}

ob_end_flush(); // End output buffering
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <style>
        .reload { font-family: Lucida Sans Unicode }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            console.log("Login page loaded");

            document.querySelector("form").addEventListener("submit", function (e) {
                console.log("Form submitted");

                let userID = document.getElementById("userID").value;
                let password = document.getElementById("password").value;

                console.log("userID:", userID);
                console.log("Password:", password.length > 0 ? "Entered" : "Not entered");
            });

            <?php if (!empty($errors)): ?>
                console.error("Login errors:", <?php echo json_encode($errors); ?>);
            <?php endif; ?>
        });

        function reloadCaptcha() {
            let captchaImg = document.getElementById('captchaImage');
            captchaImg.src = 'captcha.php?' + new Date().getTime();
            console.log("Captcha reloaded");
        }
    </script>
</head>
<body>
    <div class="container mt-5">
        <h2>Login</h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="" method="post">
            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <label for="userID" class="form-label">userID</label>
                    <input type="text" class="form-control" id="userID" name="userID" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Login</button>
        </form>
    </div>

    <div class="bottom-content1 container mt-5">
        <div class="row">
            <div class="col">
                <h5 class="text-uppercase">Contact Us</h5>
                <p>
                    Yoga Studio Management<br>
                    Phone: +91 8849459759 <br>
                    Email: info@yogarp.com
                </p>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php';?>
    <script src="<?php echo BOOTSTRAP_JS; ?>"></script>
</body>
</html>
