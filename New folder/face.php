<?php
ob_start(); // Start output buffering
require_once 'Path.php'; // Include your path file
require_once 'db.php'; // Include database connection
include 'includes/header.php'; // Include your header
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Face Recognition Attendance</title>
    <!-- Include Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #videoElement {
            width: 100%;
            height: auto;
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <h2>Face Recognition for Attendance</h2>
    <div class="row">
        <div class="col-md-8">
            <!-- Video feed area -->
            <video id="videoElement" autoplay></video>
        </div>
        <div class="col-md-4">
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
    </div>
</div>

<!-- Include required JavaScript libraries -->
<script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api.js"></script>
<script>
    const video = document.getElementById('videoElement');
    const captureBtn = document.getElementById('captureBtn');
    const messageDiv = document.getElementById('message');

    // Load face-api.js models
    Promise.all([
        faceapi.nets.ssdMobilenetv1.loadFromUri('/models'),
        faceapi.nets.faceLandmark68Net.loadFromUri('/models'),
        faceapi.nets.faceRecognitionNet.loadFromUri('/models')
    ]).then(startVideo).catch(err => console.error("Error loading models:", err));

    // Start the webcam video stream (Use the laptop's camera)
    function startVideo() {
        navigator.mediaDevices.getUserMedia({ video: {} })
            .then(stream => {
                video.srcObject = stream;  // Display the webcam video feed on the <video> element
            })
            .catch(err => {
                console.error("Error accessing webcam: ", err);
                messageDiv.innerHTML = 'Error accessing webcam.';
            });
    }

    // Capture and process the face image
    captureBtn.addEventListener('click', async () => {
        const detections = await faceapi.detectAllFaces(video)
            .withFaceLandmarks()
            .withFaceDescriptors();

        if (detections.length > 0) {
            const userFace = detections[0].descriptor; // Get the face descriptor from the detected face
            saveAttendance(userFace); // Save attendance with the face descriptor
        } else {
            messageDiv.innerHTML = 'No face detected. Please try again.';
        }
    });

    // Save attendance to the server (send face descriptor to PHP)
    function saveAttendance(faceData) {
        fetch('attendance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ faceData: faceData })  // Send the face descriptor to the server
        })
        .then(response => response.json())
        .then(data => {
            messageDiv.innerHTML = data.message;  // Show success or error message
        })
        .catch(error => {
            console.error('Error:', error);
            messageDiv.innerHTML = 'Error saving attendance.';
        });
    }
</script>

<script>
<script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api.js"></script>
<script>
    const video = document.getElementById('videoElement');
    const captureBtn = document.getElementById('captureBtn');
    const messageDiv = document.getElementById('message');

    // Load face-api.js models
    Promise.all([
        faceapi.nets.ssdMobilenetv1.loadFromUri('/models'),
        faceapi.nets.faceLandmark68Net.loadFromUri('/models'),
        faceapi.nets.faceRecognitionNet.loadFromUri('/models')
    ])
    .then(startVideo)
    .catch(err => {
        console.error("Error loading models:", err);
        messageDiv.innerHTML = 'Error loading face-api.js models.';
    });

    // Start the webcam video stream
    async function startVideo() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: {} });
            video.srcObject = stream;  // Display the webcam video feed on the <video> element
        } catch (err) {
            console.error("Error accessing webcam: ", err);
            messageDiv.innerHTML = 'Error accessing webcam. Please check your camera settings.';
        }
    }

    // Capture and process the face image
    captureBtn.addEventListener('click', async () => {
        const detections = await faceapi.detectAllFaces(video)
            .withFaceLandmarks()
            .withFaceDescriptors();

        if (detections.length > 0) {
            const userFace = detections[0].descriptor; // Get the face descriptor from the detected face
            saveAttendance(userFace); // Save attendance with the face descriptor
        } else {
            messageDiv.innerHTML = 'No face detected. Please try again.';
        }
    });

    // Save attendance to the server (send face descriptor to PHP)
    function saveAttendance(faceData) {
        fetch('attendance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ faceData: faceData })  // Send the face descriptor to the server
        })
        .then(response => response.json())
        .then(data => {
            messageDiv.innerHTML = data.message;  // Show success or error message
        })
        .catch(error => {
            console.error('Error:', error);
            messageDiv.innerHTML = 'Error saving attendance.';
        });
    }
</script>

</script>

</body>
</html>
