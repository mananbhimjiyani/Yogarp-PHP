<?php
session_start();
include 'phpqrcode/qrlib.php'; // Include the QR code library

// Ensure the user ID is set in the session
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Example user ID
}

// Generate QR content
$date = date('Y-m-d');
$time = date('H:i:s');
$userId = $_SESSION['user_id'];
$qrContent = "Date: $date\nTime: $time\nUser ID: $userId";

// Define the output file path
$qrFilePath = 'qrcodes/user_qr_' . $userId . '.png';

// Create the directory if it doesn't exist
if (!is_dir('qrcodes')) {
    mkdir('qrcodes', 0777, true);
}

// Generate the QR code
QRcode::png($qrContent, $qrFilePath, QR_ECLEVEL_L, 10);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code</title>
</head>
<body>
    <h1>Your QR Code</h1>
    <p>Date: <?= $date ?></p>
    <p>Time: <?= $time ?></p>
    <p>User ID: <?= $userId ?></p>
    <img src="<?= $qrFilePath ?>" alt="QR Code">
</body>
</html>
