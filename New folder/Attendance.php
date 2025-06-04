<?php
ob_start();
require_once 'Path.php'; // Include the path file for constants
require_once 'db.php'; // Include database connection
include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Webcam</title>
    
</head>
<body>
   <select id="cameraSelect">
    <option value="">Select Camera</option>
    <option value="ip-camera">Enter IP Camera URL</option>
    <option value="mac-address">Enter MAC Address</option>
</select>

<input type="text" id="ipCameraUrl" placeholder="Enter IP Camera URL" style="display: none;">
<input type="text" id="macAddress" placeholder="Enter MAC Address" style="display: none;">
    <div class="col-md-6">
        <video id="videoElement" autoplay></video>
    </div>

    <!-- Labels to display full file name and trimmed file name -->
    <div hidden>
        <label id="fileNameLabel">Full File Name: </label>
    </div>
    <div>
        <label id="trimmedNameLabel">Client ID: </label>
    </div>

    <!-- Label to display client's information -->
    <div>
        <label id="clientInfo">Client Info: </label>
    </div>
<div id="previewDiv" style="display: none;">
    <!-- This will display the preview of the data -->
</div>

<div id="flashMessage" style="display: none; background-color: lightgreen; padding: 10px; margin-top: 10px;">
    <!-- This will display success or error messages -->
</div>
    <script src="includes/js/face-api.js"></script>
    <script>
        const video = document.getElementById('videoElement');
        const fileNameLabel = document.getElementById('fileNameLabel');
        const trimmedNameLabel = document.getElementById('trimmedNameLabel');
        const clientInfoLabel = document.getElementById('clientInfo');
		const cameraSelect = document.getElementById('cameraSelect');
const ipCameraUrlInput = document.getElementById('ipCameraUrl');
const macAddressInput = document.getElementById('macAddress');
        let webcamFaceDescriptor = null;

        // Load models for face-api.js
        async function loadModels() {
            try {
                await Promise.all([
                    faceapi.nets.ssdMobilenetv1.loadFromUri('models'),
                    faceapi.nets.faceLandmark68Net.loadFromUri('models'),
                    faceapi.nets.faceRecognitionNet.loadFromUri('models')
                ]);
                console.log("Models loaded successfully");
                startVideo();
            } catch (err) {
                console.error("Error loading models:", err);
            }
        }

       cameraSelect.addEventListener('change', function () {
    const selectedCamera = cameraSelect.value;

    if (selectedCamera === 'ip-camera') {
        ipCameraUrlInput.style.display = 'block'; // Show the input for URL
        macAddressInput.style.display = 'none'; // Hide the MAC address input
    } else if (selectedCamera === 'mac-address') {
        macAddressInput.style.display = 'block'; // Show the MAC address input
        ipCameraUrlInput.style.display = 'none'; // Hide the URL input
    } else {
        ipCameraUrlInput.style.display = 'none';
        macAddressInput.style.display = 'none';
        startVideo(selectedCamera); // Start selected video device
    }
});

async function startVideo(deviceId) {
    const constraints = {
        video: {
            deviceId: deviceId ? { exact: deviceId } : undefined
        }
    };

    if (deviceId === 'ip-camera') {
        const ipCameraUrl = ipCameraUrlInput.value;
        if (ipCameraUrl) {
            video.srcObject = null; // Reset previous video source
            video.src = ipCameraUrl; // Set the video stream to the IP camera URL
        } else {
            alert("Please enter a valid IP camera URL");
        }
    } else if (deviceId === 'mac-address') {
        const macAddress = macAddressInput.value;
        if (macAddress) {
            const cameraStreamUrl = await getCameraStreamUrlByMac(macAddress);
            if (cameraStreamUrl) {
                video.srcObject = null; // Reset previous video source
                video.src = cameraStreamUrl; // Set the video stream to the camera stream URL
            } else {
                alert("No camera found with this MAC address.");
            }
        } else {
            alert("Please enter a valid MAC address.");
        }
    } else {
        try {
            const stream = await navigator.mediaDevices.getUserMedia(constraints);
            video.srcObject = stream;
        } catch (err) {
            console.error("Error accessing camera:", err);
        }
    }
}

// This function should interact with the backend (or a database/config file) to get the camera stream URL
async function getCameraStreamUrlByMac(macAddress) {
    try {
        const response = await fetch('get_camera_stream.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ mac_address: macAddress })
        });

        const data = await response.json();

        if (data.success && data.stream_url) {
            return data.stream_url; // Return the stream URL for the camera
        } else {
            console.error("Error fetching camera stream:", data.message);
            return null;
        }
    } catch (err) {
        console.error("Error getting camera stream URL:", err);
        return null;
    }
}

// Start continuous face detection
async function startContinuousFaceDetection() {
    const imageFiles = await fetchImageFiles(); // Get list of image files
    let lastDetectedTime = Date.now(); // Store the last time a face was detected

    // Function to update the displayed name or information
    function clearName() {
        console.log("Face not detected, clearing name");
        // You can clear the name or client information here
        displayFileNames([]); // Clear file names
        fetchClientInfo(null); // Clear client info
    }

    // Start a loop to continuously detect faces
    setInterval(async () => {
        const detections = await faceapi.detectAllFaces(video)
            .withFaceLandmarks()
            .withFaceDescriptors();

        if (detections.length === 0) {
            console.log('No face detected in webcam.');
            if (Date.now() - lastDetectedTime > 1000) { // If face hasn't been detected for more than 1 second
                clearName(); // Clear name when face is off-camera for more than 1 second
            }
            return; // Exit if no face is detected
        }

        const webcamFace = detections[0].descriptor; // Get face descriptor from webcam
        const comparisonResult = await compareFaceWithImages(webcamFace, imageFiles); // Compare with stored images

        if (comparisonResult) {
            console.log("Match found with:", comparisonResult);
            displayFileNames(comparisonResult); // Display full and trimmed file name
            fetchClientInfo(comparisonResult); // Fetch client info from the database
            lastDetectedTime = Date.now(); // Update the time of last detection
        } else {
            console.log("No match found");
        }
    }, 1000); // Run the face detection every 1000ms (1 second)
}

// Call the function to start continuous face detection
startContinuousFaceDetection();


        // Fetch image files from server
        async function fetchImageFiles() {
            try {
                const response = await fetch('list_files.php');
                const imageFiles = await response.json();
                return imageFiles;
            } catch (err) {
                console.error('Error fetching image files:', err);
                return [];
            }
        }

        // Compare the webcam face with the images
        async function compareFaceWithImages(webcamFace, imageFiles) {
            for (const imageFile of imageFiles) {
                const imageUrl = `uploads/Photo/client/${imageFile}`; // Full path to the image file
                const referenceImage = await faceapi.fetchImage(imageUrl);
                const referenceDetections = await faceapi.detectAllFaces(referenceImage)
                    .withFaceLandmarks()
                    .withFaceDescriptors();

                if (referenceDetections.length > 0) {
                    const referenceFace = referenceDetections[0].descriptor;
                    const distance = faceapi.euclideanDistance(webcamFace, referenceFace);

                    // If distance is small, faces match
                    if (distance < 0.6) { // Adjust threshold if needed
                        return imageFile;
                    }
                }
            }
            return null;
        }

        // Display the full file name and trimmed file name (without prefix and extension)
        function displayFileNames(fileName) {
            fileNameLabel.textContent = "Full File Name: " + fileName; // Show full file name
            const trimmedName = fileName.replace('client_', '').replace(/\.[^/.]+$/, ''); // Remove "client_" and extension
            trimmedNameLabel.textContent = "Trimmed Name: " + trimmedName; // Show trimmed file name
        }

async function fetchClientInfo() {
    try {
        const clientId = trimmedNameLabel.textContent.replace("Trimmed Name: ", ""); // Extract the client_id from the label text
        console.log("Sending Client ID:", clientId); // Log to ensure it's being sent

        // Send client_id to the server to store it in the session
        const response = await fetch('get_client_info.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ client_id: clientId })  // Send client_id as the POST data
        });

        const data = await response.json();

        if (data.success) {
            clientInfoLabel.textContent = `Client Info: ${data.full_name}`;
        } else {
            clientInfoLabel.textContent = "Client not found";
        }
    } catch (err) {
        console.error('Error fetching client info:', err);
    }
}
// Function to show flash effect on screen
function showFlashMessage(message) {
    const flashMessageDiv = document.createElement('div');
    flashMessageDiv.style.position = 'fixed';
    flashMessageDiv.style.top = '0';
    flashMessageDiv.style.left = '0';
    flashMessageDiv.style.width = '100%';
    flashMessageDiv.style.padding = '20px';
    flashMessageDiv.style.textAlign = 'center';
    flashMessageDiv.style.fontSize = '20px';
    flashMessageDiv.style.backgroundColor = 'green';
    flashMessageDiv.style.color = 'white';
    flashMessageDiv.style.zIndex = '10000';
    flashMessageDiv.textContent = message;

    document.body.appendChild(flashMessageDiv);

    // Remove flash message after 2 seconds
    setTimeout(() => {
        flashMessageDiv.style.transition = 'opacity 1s';
        flashMessageDiv.style.opacity = '0';

        setTimeout(() => {
            flashMessageDiv.remove();
        }, 1000);
    }, 2000);
}

async function insertAttendance(clientId) {
    try {
        // Log the data being sent to the server for debugging
        console.log('Sending data to server:', { client_id: clientId });

        const response = await fetch('insert_attendance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                client_id: clientId
            })
        });

        const result = await response.json();
        console.log('Server response:', result);

        if (result.success) {
            if (result.message === 'Attendance already recorded') {
                // If attendance is already recorded, show the time
                showFlashMessage(`Attendance already recorded at ${result.attendance_time}`);
            } else {
                // Otherwise, show success message
                showFlashMessage('Attendance recorded successfully');
            }
        } else {
            console.error('Failed to record attendance:', result.message);
            showFlashMessage(`Failed to record attendance: ${result.message}`);
        }
    } catch (err) {
        console.error('Error inserting attendance:', err);
        showFlashMessage('Error registering attendance');
    }
}

// Function to show flash messages (success/error)
function showFlashMessage(message) {
    const flashMessage = document.getElementById('flashMessage');
    flashMessage.textContent = message;
    flashMessage.style.display = 'block';

    setTimeout(() => {
        flashMessage.style.display = 'none';  // Hide the flash message after a few seconds
    }, 1000);
}

// Function to display file names and update the trimmed name
function displayFileNames(fileName) {
    fileNameLabel.textContent = "Full File Name: " + fileName; // Show full file name
    const trimmedName = fileName.replace('client_', '').replace(/\.[^/.]+$/, ''); // Remove "client_" and extension
    trimmedNameLabel.textContent = "Trimmed Name: " + trimmedName; // Show trimmed file name
    
    // Once the trimmedNameLabel is updated with a valid client ID, insert attendance
    insertAttendance(trimmedName);
}

        // Enable compare button when face is detected
        video.addEventListener('play', async () => {
            const detections = await faceapi.detectAllFaces(video)
                .withFaceLandmarks()
                .withFaceDescriptors();

            if (detections.length > 0) {
                webcamFaceDescriptor = detections[0].descriptor;
                compareButton.disabled = false;
            }
        });

        // Load models and start the video
        loadModels();
    </script>
</body>
</html>
