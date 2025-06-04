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
    <meta charset="UTF-8">
    <title>Login - Yoga Studio Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <style>
        body {
            background: #f8f9fa;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .login-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(10px, 3vh, 30px);
            min-height: calc(100vh - 100px);
        }

        .card {
            border-radius: 18px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.10);
            background: white;
            width: min(90%, 400px);
            margin: auto;
        }

        .form-label {
            font-weight: 500;
            color: #862118;
            font-size: clamp(0.9rem, 2vw, 1rem);
        }

        .form-control {
            border-radius: 8px !important;
            border: 1px solid #e0d2d2 !important;
            margin-bottom: 12px;
            font-size: clamp(0.9rem, 2vw, 1rem);
            padding: clamp(8px, 1.5vh, 12px);
        }

        .btn {
            border-radius: 8px !important;
            box-shadow: 0 1px 4px rgba(134, 33, 24, 0.08);
            font-weight: 500;
            font-size: clamp(0.9rem, 2vw, 1rem);
            padding: clamp(8px, 1.5vh, 12px) clamp(16px, 3vw, 24px);
        }

        .btn-primary {
            background-color: #862118 !important;
            border-color: #862118 !important;
        }

        .btn-primary:hover {
            background-color: #a42b2b !important;
            border-color: #a42b2b !important;
        }

        h1 {
            color: #862118;
            font-weight: 700;
            margin-bottom: clamp(12px, 3vh, 24px);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: clamp(1.5rem, 4vw, 2rem);
        }

        .alert {
            border-radius: 8px;
            font-size: clamp(0.85rem, 2vw, 0.95rem);
            margin-bottom: clamp(12px, 3vh, 24px);
        }

        .bottom-content1 {
            position: fixed;
            bottom: clamp(60px, 10vh, 100px);
            left: clamp(10px, 3vw, 30px);
            background: rgba(255, 255, 255, 0.95);
            padding: clamp(10px, 2vh, 20px);
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.10);
            width: min(90%, 300px);
            z-index: 1000;
        }

        .bottom-content1 h5 {
            color: #862118;
            margin-bottom: clamp(5px, 1.5vh, 15px);
            font-size: clamp(1rem, 2.5vw, 1.2rem);
        }

        .bottom-content1 p {
            margin-bottom: 0;
            font-size: clamp(0.8rem, 2vw, 0.95rem);
            line-height: 1.6;
        }

        @media (max-height: 600px) {
            .login-container {
                padding: 10px;
            }

            .card {
                padding: 15px !important;
            }

            .bottom-content1 {
                bottom: 50px;
                padding: 8px;
            }
        }

        @media (max-width: 480px) {
            .bottom-content1 {
                left: 50%;
                transform: translateX(-50%);
                width: 90%;
            }
        }

        footer {
            position: relative;
            z-index: 999;
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            console.log("Login page loaded");

            document.querySelector("form").addEventListener("submit", function(e) {
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
    </script>
</head>

<body>
    <div class="login-container">
        <div class="card shadow-sm rounded-4 border-0 p-4">
            <h1 class="mb-3"><i class="bi bi-box-arrow-in-right"></i> Login</h1>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <form action="" method="post" autocomplete="off">
                <div class="mb-3">
                    <label for="userID" class="form-label"><i class="bi bi-person"></i> User ID</label>
                    <input type="text" class="form-control" id="userID" name="userID" required autofocus>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label"><i class="bi bi-lock"></i> Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div class="bottom-content1">
        <h5 class="text-uppercase">Contact Us</h5>
        <p>
            Yoga Studio Management<br>
            Phone: +91 8849459759 <br>
            Email: info@yogarp.com
        </p>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>