<?php
require_once 'Path.php'; // Include the path file for constants
require_once 'db.php'; // Include database connection
include 'includes/header.php';

// Initialize variables
$errors = [];
$success = "";
// Check if the user is logged in by verifying a session variable, such as 'user_id'
if (!isset($_SESSION['user_id'])) {
    // If user is not logged in, redirect to index.php
    header("Location: login.php");
    exit(); // Ensure no further code is executed after redirection
}
?>

    <div class="bottom-content1 container mt-5">
        <!-- Links Section -->
        <div class="row">
            <!-- Contact Us -->
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
</div>

<?php include 'includes/footer.php'; // Include the footer file ?>
<script src="<?php echo BOOTSTRAP_JS; ?>"></script>
</body>
</html>
