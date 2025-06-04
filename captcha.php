<?php
session_start();

function generateRandomString($length = 6) {
    return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
}

function generateRandomColor() {
    return imagecolorallocate(
        $GLOBALS['image'], 
        rand(0, 255), 
        rand(0, 255), 
        rand(0, 255)
    );
}

// Generate a random string and store it in the session
$captchaText = generateRandomString();
$_SESSION['captcha_text'] = $captchaText;

// Create the CAPTCHA image
$image = imagecreatetruecolor(200, 70);
$backgroundColor = imagecolorallocate($image, 255, 255, 255); // White background
imagefilledrectangle($image, 0, 0, 200, 70, $backgroundColor);

// Draw random lines with random colors
for ($i = 0; $i < 5; $i++) {
    $lineColor = generateRandomColor();
    imageline($image, rand(0, 200), rand(0, 70), rand(0, 200), rand(0, 70), $lineColor);
}

// Path to the Google font .ttf file
$fontPath = __DIR__ . '/includes/fonts/Roboto-Regular.ttf'; // Adjust this path as necessary

if (file_exists($fontPath)) {
    // Set text color to black
    $textColor = imagecolorallocate($image, 0, 0, 0); // Black color
    
    // Draw each character of the CAPTCHA text in black
    $x = 20;
    for ($i = 0; $i < strlen($captchaText); $i++) {
        imagettftext($image, 30, 0, $x, 50, $textColor, $fontPath, $captchaText[$i]);
        $x += 30; // Adjust space between characters
    }
} else {
    die('Font file not found.');
}

// Set the content type header
header('Content-Type: image/png');

// Output the image
imagepng($image);
imagedestroy($image);
