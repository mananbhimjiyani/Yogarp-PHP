<?php
// Start output buffering
ob_start();
require_once 'Path.php'; // Path constants
require_once 'db.php'; // Database connection
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Scanner</title>
    <script src="https://unpkg.com/html5-qrcode/minified/html5-qrcode.min.js"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        #reader {
            width: 100%;
            max-width: 500px;
            margin: auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 10px;
        }
        .table-container {
            margin-top: 30px;
        }
        #error-message {
            color: red;
            text-align: center;
        }
        video {
            width: 100%; /* Make video element fill the available space */
            height: auto;
            border: 1px solid #ddd;
            border-radius: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h2 class="text-center">QR Code Scanner</h2>

    <!-- Video feed for the camera -->
    <div id="video-container">
        <video id="videoElement" autoplay></video>
		    <script>
        navigator.mediaDevices.getUserMedia({ video: true })
            .then(stream => {
                document.getElementById('videoElement').srcObject = stream;
            })
            .catch(err => {
                console.error('Error accessing webcam:', err);
            });
    </script>
    </div>

    <!-- QR code scanning box -->
    <div id="reader" style="display: none;"></div>

    <div id="error-message" class="mt-3"></div>

    <!-- Form to submit scanned QR code data -->
    <form id="qr-form" method="POST" action="">
        <input type="hidden" name="qrData" id="qrData">
    </form>

    <!-- Output Table -->
    <div class="table-container">
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qrData'])) {
            $qrData = htmlspecialchars($_POST['qrData']); // Get the scanned QR code data

            // Example: Parse QR data into rows
            $dataRows = explode("\n", $qrData);

            echo '<h3>Scanned QR Code Data</h3>';
            echo '<table class="table table-striped table-bordered">';
            echo '<thead class="table-dark"><tr><th>Field</th><th>Value</th></tr></thead>';
            echo '<tbody>';

            foreach ($dataRows as $row) {
                $fieldValue = explode(':', $row, 2); // Split data into field and value
                if (count($fieldValue) === 2) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars(trim($fieldValue[0])) . '</td>';
                    echo '<td>' . htmlspecialchars(trim($fieldValue[1])) . '</td>';
                    echo '</tr>';
                }
            }

            echo '</tbody>';
            echo '</table>';
        }
        ?>
    </div>
</div>

<script>
    // QR Code scanner success callback
    function onScanSuccess(qrCodeMessage) {
        // Display the scanned data in the form
        alert("QR Code Scanned: " + qrCodeMessage);

        // Populate hidden input and submit form
        document.getElementById('qrData').value = qrCodeMessage;
        document.getElementById('qr-form').submit();

        // Stop the scanner
        html5QrcodeScanner.clear();
    }

    // QR Code scanner error callback
    function onScanError(errorMessage) {
        // Display error message if scanning fails
        document.getElementById('error-message').textContent = "Scanning failed. Please try again.";
        console.error("QR Code Scan Error: " + errorMessage);
    }

    // Initialize the QR Code scanner
    const html5QrcodeScanner = new Html5QrcodeScanner(
        "reader",
        { fps: 10, qrbox: 250 } // Settings: 10 FPS, QR code scanning box size 250px
    );

    // Render the QR code scanner inside the reader container
    html5QrcodeScanner.render(onScanSuccess, onScanError);

    // Use the device's camera for video streaming
    navigator.mediaDevices.getUserMedia({ video: true })
        .then(stream => {
            document.getElementById('videoElement').srcObject = stream; // Display video feed
        })
        .catch(err => {
            console.error('Error accessing webcam:', err);
            document.getElementById('error-message').textContent = "Error accessing camera. Please ensure camera is available.";
        });
</script>

</body>
</html>
