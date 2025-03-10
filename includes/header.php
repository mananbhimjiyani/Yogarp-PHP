<?php
session_start();
require_once 'Path.php';
$timeLimit = 5 * 3600; ;
$isUserLoggedIn = isset($_SESSION['user_id']);
if ($isUserLoggedIn) {
    if (isset($_SESSION['last_activity'])) {
        $inactiveTime = time() - $_SESSION['last_activity'];
        if ($inactiveTime > $timeLimit) {
            session_unset();
            session_destroy();
            header("Location: logout.php");
            exit();
        }
    }
$_SESSION['last_activity'] = time();
}
$userType = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : null;
date_default_timezone_set('Asia/Kolkata');
?>


<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yoga Studio Management</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<style>
    /* Custom styles for the vertical menu */
    .vertical-menu {
        background-color: #862118;
        color: white;
        width: 20vh;
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        padding-top: 20px; /* Adjust padding to accommodate logo */
        box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        text-align: left; /* Center align logo and menu items */
    }

    .vertical-menu a {
        color: white;
        padding: 15px;
        text-decoration: none;
        display: block;
        font-size: 16px;
    }

    .vertical-menu a:hover {
        background-color: #a42b2b;
        text-decoration: none;
    }

    .vertical-menu .logo {
        margin-bottom: 20px; /* Space between logo and menu items */
        text-align: center; /* Center align logo and menu items */
    }

    .main-content {
        margin-left: 240px; /* Space for the sidebar */
    }

    /* Submenu styles */
    .submenu {
        display: none;
        background-color: #6c1b1b;
        margin-left: 20px;
    }

    .submenu a {
        padding: 10px 15px;
    }

    .submenu.show {
        display: block;
    }
	 .bottom-content {
            position: fixed;
            bottom: 0;
            width: 100%;

        }
			 .bottom-content1 {
            position: fixed;
            bottom: 25px;
            width: 100%;

        }
</style>
<div class="vertical-menu">
    <div class="logo">
		<img src="<?php echo LOGO_PATH; ?>" alt="Nisha's Yoga Studio" class="img-fluid" style="max-height: 10vh;">
   </div>
    <?php if ($isUserLoggedIn && $userType == 1): ?>
			<a href="#" class="menu-item" data-target="#AdministrationSubmenu">Administration</a>
		       <div id="AdministrationSubmenu" class="submenu">
					<a href="<?= BASE_URL . 'add_client.php'; ?>">Add Client</a>
		            <a href="<?= BASE_URL . 'add_studio.php'; ?>">Add Studio</a>
					<a href="<?= BASE_URL . 'add_batch.php'; ?>">Add Batch</a>
					<a href="<?= BASE_URL . 'assign_batch_to_client.php'; ?>">Client Batch Change</a>
					<a href="<?= BASE_URL . 'assign_batch_to_studio.php'; ?>">Assign Batch to Studio</a>
			        <a href="<?= BASE_URL . 'employee.php'; ?>">Employee</a>
					<a href="<?= BASE_URL . 'employee.php'; ?>">Department</a>
					<a href="<?= BASE_URL . 'employee.php'; ?>">Role</a>
					<a href="<?= BASE_URL . 'employee.php'; ?>">Activate Client</a>
					<a href="<?= BASE_URL . 'register.php'; ?>">Register User</a>
					<a href="<?= BASE_URL . 'plan.php'; ?>">Plan</a>
				</div>
				<a href="<?= BASE_URL . 'demo.php'; ?>">Demo Registration</a>
	        <a href="<?= BASE_URL . 'fees.php'; ?>">Fees</a>
	        <a href="<?= BASE_URL . 'client_restriction.php'; ?>">Client Restriction</a>
            <a href="<?= BASE_URL . 'instructor_batch_assign.php'; ?>">Instructor Batch Assign</a>	
            <a href="<?= BASE_URL . 'schedule.php'; ?>">Schedule</a>	
			<a href="<?= BASE_URL . 'membership.php'; ?>">Membership</a>	
			<a href="<?= BASE_URL . 'attendance.php'; ?>">Attendance</a>

			<a href="<?= BASE_URL . 'reports.php'; ?>">Reports</a>
			<a href="<?= BASE_URL . 'logout.php'; ?>">Logout</a>
		
    <?php elseif ($isUserLoggedIn && $userType == 2): ?>
			<a href="<?= BASE_URL . 'fees.php'; ?>">Fees</a>
			<a href="<?= BASE_URL . 'client_restriction.php'; ?>">Client Restriction</a>  
			<a href="<?= BASE_URL . 'attendance.php'; ?>">Attendance</a>
			<a href="<?= BASE_URL . 'membership.php'; ?>">Membership Check</a>
			<a href="<?= BASE_URL . 'add_client.php'; ?>">Add Client</a>
			<a href="<?= BASE_URL . 'logout.php'; ?>">Logout</a>

    <?php else: ?>
			<a href="<?= BASE_URL . 'enquiry.php'; ?>">Enquiry</a>
			<a href="<?= BASE_URL . 'login.php'; ?>">Login</a>
			
    <?php endif; ?>
</div>
   <div class="main-content container" style="margin-left: 21vh; margin-top: 20px;">
 <script>
    document.addEventListener('DOMContentLoaded', () => {
        // JavaScript to handle submenu toggle
        document.querySelectorAll('.menu-item').forEach(item => {
            item.addEventListener('click', function(event) {
                event.preventDefault(); // Prevent default link behavior
                const targetId = this.getAttribute('data-target');
                const submenu = document.querySelector(targetId);

                // Toggle submenu visibility
                if (submenu) submenu.classList.toggle('show');
            });
        });
    });
</script>
