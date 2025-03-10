<?php
// db.php - Database connection file
require_once 'Path.php'; // Include the path file for constants
require_once 'db.php'; // Include database connection
include 'includes/header.php';

// Initialize variables and error messages
$errors = [];
$success = "";

// Check if user is logged in

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// Fetch all applicants data
$sql = "SELECT * FROM applicants";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant List</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        .table-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Applicant List</h2>
 <div class="row mb-3">
        <div class="col">
            <input type="text" id="titleSearch" class="form-control" placeholder="Search by Title">
        </div>
        <div class="col">
            <input type="text" id="firstNameSearch" class="form-control" placeholder="Search by First Name">
        </div>
        <div class="col">
            <input type="text" id="lastNameSearch" class="form-control" placeholder="Search by Last Name">
        </div>
        <div class="col">
            <input type="text" id="genderSearch" class="form-control" placeholder="Search by Gender">
        </div>
        <div class="col">
            <input type="text" id="positionSearch" class="form-control" placeholder="Search by Position Applied">
        </div>
    </div>
        <table id="applicantTable" class="table table-bordered table-hover">
            <thead>
                <tr align="center ">
					<th>Sr. No.</th>
                    <th>ID</th>
                    <th>Title</th>
                    <th>First Name</th>
                    <th>Middle Name</th>
                    <th>Last Name</th>
                    <th>Date of Birth</th>
                    <th>Gender</th>
                    <th>Marital Status</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>Position Applied</th>
                    <th>Photo</th>
                    <th colspan="3">Actions</th>
                </tr>
            </thead>
            <tbody>
                 <?php
            $sr = 1;
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    ?>
                    <tr>
                        <td><?php echo $sr++; ?></td>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo ucwords($row['title']); ?></td>
                        <td><?php echo ucwords($row['first_name']); ?></td>
                        <td><?php echo ucwords($row['middle_name']); ?></td>
                        <td><?php echo ucwords($row['last_name']); ?></td>
                        <td><?php echo date("d/m/Y", strtotime($row['dob'])); ?></td>
                        <td><?php echo ucwords($row['gender']); ?></td>
                        <td><?php echo ucwords($row['marital_status']); ?></td>
                        <td><?php echo strtolower($row['email']); ?></td>
                        <td><?php echo $row['mobile_number']; ?></td>
                        <td><?php echo ucwords($row['position_applied']); ?></td>
                        <td>
                            <?php if ($row['photo']) { ?>
    <img src="<?php echo $row['photo']; ?>" class="table-img" alt="Applicant Photo" data-bs-toggle="modal" data-bs-target="#photoModal<?php echo $row['id']; ?>" onerror="this.onerror=null; this.src='path/to/placeholder.jpg';">
    <!-- Modal for viewing the full-size photo -->
    <div class="modal fade" id="photoModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="photoModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="photoModalLabel<?php echo $row['id']; ?>">Applicant Photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img src="<?php echo $row['photo']; ?>" class="img-fluid" alt="Applicant Photo" onerror="this.onerror=null; this.src='path/to/placeholder.jpg';">
                </div>
            </div>
        </div>
    </div>
<?php } else { 
    echo "No Photo"; 
} ?>
                            </td>
                           <td>
    <a href="activate_employee.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">Activate</a>
</td>
<td>
    <a href="employee.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
</td><td>
                                <a href="delete_applicant.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger">Delete</a>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    echo "<tr><td colspan='13'>No applicants found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

    <!-- Initialize DataTable -->
<script>
    $(document).ready(function() {
        var table = $('#applicantTable').DataTable({
            "order": [[0, "desc"]], // Default sort by Sr. No.
            "searching": true,
            "paging": true,
            "info": true,
        });

        // Custom search functionality for each column
        $('#titleSearch').on('keyup', function() {
            table.column(2).search(this.value).draw();
        });
        
        $('#firstNameSearch').on('keyup', function() {
            table.column(3).search(this.value).draw();
        });

        $('#lastNameSearch').on('keyup', function() {
            table.column(5).search(this.value).draw();
        });

        $('#genderSearch').on('keyup', function() {
            table.column(7).search(this.value).draw();
        });

        $('#positionSearch').on('keyup', function() {
            table.column(11).search(this.value).draw();
        });
    });
</script>
<script>
    $(document).ready(function() {
        var table = $('#applicantTable').DataTable({
            "order": [[0, "desc"]], // Default sorting by Sr. No.
            "searching": true,
            "paging": true,
            "info": true,
            "columnDefs": [
                { "orderable": false, "targets": [12, 13, 14] } // Disable sorting for Actions and Photo
            ]
        });

        // Custom search functionality for each column
        $('#titleSearch').on('keyup', function() {
            table.column(2).search(this.value).draw();
        });
        
        $('#firstNameSearch').on('keyup', function() {
            table.column(3).search(this.value).draw();
        });

        $('#lastNameSearch').on('keyup', function() {
            table.column(5).search(this.value).draw();
        });

        $('#genderSearch').on('keyup', function() {
            table.column(7).search(this.value).draw();
        });

        $('#positionSearch').on('keyup', function() {
            table.column(11).search(this.value).draw();
        });
    });
</script>

    <?php include 'includes/footer.php'; // Include the footer file ?>
</body>
</html>
