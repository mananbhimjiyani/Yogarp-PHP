<?php
header('Content-Type: application/json');

$directory = 'uploads/Photo/client/';
if (!is_dir($directory)) {
    echo json_encode(['error' => 'Directory not found']);
    exit;
}

$files = array_diff(scandir($directory), array('..', '.')); // Exclude '.' and '..'
$imageFiles = [];

foreach ($files as $file) {
    if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $file)) {
        $imageFiles[] = $file; // Add image files to the list
    }
}

if (empty($imageFiles)) {
    echo json_encode(['error' => 'No images found']);
    exit;
}

echo json_encode($imageFiles); // Return the list of image files
?>
