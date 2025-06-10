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
$sql = "SELECT DATE(timestamp) AS rsvp_date, COUNT(*) AS entry_count FROM rsvp WHERE
        (first_name LIKE '%$search_query%' OR
        last_name LIKE '%$search_query%' OR
        email LIKE '%$search_query%' OR
        phone_number LIKE '%$search_query%' OR
        attending LIKE '%$search_query%' OR
        dinner LIKE '%$search_query%')
        GROUP BY rsvp_date
        ORDER BY rsvp_date $order_dir";

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
                                <th><a href="?sort_by=rsvp_date&order=<?= ($order_by == 'rsvp_date' && $order_dir == 'ASC') ? 'desc' : 'asc' ?>">RSVP Date</a></th>
                                <th>Entry Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php

                if (mysqli_num_rows($result) > 0) {
                                   // Output data of each row
                                   while ($row = mysqli_fetch_assoc($result)) {
                                       echo "<tr>";
                                       echo "<td>" . htmlspecialchars($row['rsvp_date']) . "</td>";
                                       echo "<td>" . htmlspecialchars($row['entry_count']) . "</td>";
                                       echo "</tr>";
                                   }
                               } else {
                                   echo "<tr><td colspan='2' class='text-center'>No results found</td></tr>";
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
