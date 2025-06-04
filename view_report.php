<?php
// Include your database connection script
include('db.php');

// Handle sorting and search query
$search_query = '';
$order_by = 'timestamp';  // Default sort by timestamp
$order_dir = 'DESC';  // Default sorting direction

if (isset($_GET['search'])) {
    $search_query = htmlspecialchars($_GET['search']);
}

if (isset($_GET['sort_by'])) {
    $order_by = htmlspecialchars($_GET['sort_by']);
    $order_dir = (isset($_GET['order']) && $_GET['order'] === 'asc') ? 'ASC' : 'DESC';
}

// Build the SQL query to fetch data with search and sorting
$sql = "SELECT * FROM rsvp WHERE 
        (first_name LIKE '%$search_query%' OR
        last_name LIKE '%$search_query%' OR
        email LIKE '%$search_query%' OR
        phone_number LIKE '%$search_query%' OR
        attending LIKE '%$search_query%' OR
        dinner LIKE '%$search_query%') 
        ORDER BY $order_by $order_dir";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RSVP Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">RSVP Report for Navratri Function</h2>

        <!-- Search Form -->
        <form class="d-flex mb-3" action="view_report.php" method="GET">
            <input class="form-control me-2" type="search" name="search" value="<?= $search_query ?>" placeholder="Search RSVP..." aria-label="Search">
            <button class="btn btn-outline-primary" type="submit">Search</button>
        </form>

        <!-- RSVP Table -->
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th><a href="?sort_by=first_name&order=<?= ($order_by == 'first_name' && $order_dir == 'ASC') ? 'desc' : 'asc' ?>">First Name</a></th>
                    <th><a href="?sort_by=last_name&order=<?= ($order_by == 'last_name' && $order_dir == 'ASC') ? 'desc' : 'asc' ?>">Last Name</a></th>
                    <th><a href="?sort_by=email&order=<?= ($order_by == 'email' && $order_dir == 'ASC') ? 'desc' : 'asc' ?>">Email</a></th>
                    <th><a href="?sort_by=phone_number&order=<?= ($order_by == 'phone_number' && $order_dir == 'ASC') ? 'desc' : 'asc' ?>">Phone</a></th>
                    <th><a href="?sort_by=attending&order=<?= ($order_by == 'attending' && $order_dir == 'ASC') ? 'desc' : 'asc' ?>">Attending</a></th>
                    <th><a href="?sort_by=adults_attending&order=<?= ($order_by == 'adults_attending' && $order_dir == 'ASC') ? 'desc' : 'asc' ?>">Adults Attending</a></th>
                    <th><a href="?sort_by=children_attending&order=<?= ($order_by == 'children_attending' && $order_dir == 'ASC') ? 'desc' : 'asc' ?>">Children Attending</a></th>
                   <!-- <th><a href="?sort_by=dinner&order=<?= ($order_by == 'dinner' && $order_dir == 'ASC') ? 'desc' : 'asc' ?>">Convenience</a></th>
                    --><th><a href="?sort_by=timestamp&order=<?= ($order_by == 'timestamp' && $order_dir == 'ASC') ? 'desc' : 'asc' ?>">RSVP Date</a></th>
                </tr>
            </thead>
            <tbody>
                <?php
				$original_timestamp = $row['timestamp'];
				$formatted_timestamp = date('d/m/Y h:i A', strtotime($original_timestamp));

                if (mysqli_num_rows($result) > 0) {
                    // Output data of each row
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['first_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['last_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['phone_number']) . "</td>";
                        echo "<td>" . ($row['attending'] == 'yes' ? 'Yes' : 'No') . "</td>";
                        echo "<td>" . htmlspecialchars($row['adults_attending']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['children_attending']) . "</td>";
                       // echo "<td>" . ($row['dinner'] == 'yes' ? 'Yes' : 'No') . "</td>";
						echo "<td>" . htmlspecialchars($formatted_timestamp) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='9' class='text-center'>No results found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Close the database connection
mysqli_close($conn);
?>
