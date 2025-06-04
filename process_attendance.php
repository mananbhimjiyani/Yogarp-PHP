<?php
require_once 'Path.php';
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attendance = $_POST['attendance'] ?? [];  // Capture the attendance data
    
    $presentClients = [];
    $absentClients = [];
    $pendingClients = [];
    
    // Group clients based on their attendance selection
    foreach ($attendance as $client_id => $status) {
        $query = "SELECT title, first_name, last_name FROM clients WHERE client_id = ?";
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("i", $client_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $client = $result->fetch_assoc();

            if ($status === 'present') {
                $presentClients[] = $client;
            } elseif ($status === 'absent') {
                $absentClients[] = $client;
            } elseif ($status === 'pending') {
                $pendingClients[] = $client;
            }
        }
    }

    // Display the grouped results
    echo "<h3>Attendance Summary</h3>";

    // Group: Present
    echo "<h4>Present Clients (" . count($presentClients) . ")</h4>";
    if (!empty($presentClients)) {
        echo "<table class='table table-bordered'>";
        echo "<thead><tr><th>Title</th><th>First Name</th><th>Last Name</th></tr></thead>";
        echo "<tbody>";
        foreach ($presentClients as $client) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($client['title']) . "</td>";
            echo "<td>" . htmlspecialchars($client['first_name']) . "</td>";
            echo "<td>" . htmlspecialchars($client['last_name']) . "</td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
    } else {
        echo "<p>No clients marked as present.</p>";
    }

    // Group: Absent
    echo "<h4>Absent Clients (" . count($absentClients) . ")</h4>";
    if (!empty($absentClients)) {
        echo "<table class='table table-bordered'>";
        echo "<thead><tr><th>Title</th><th>First Name</th><th>Last Name</th></tr></thead>";
        echo "<tbody>";
        foreach ($absentClients as $client) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($client['title']) . "</td>";
            echo "<td>" . htmlspecialchars($client['first_name']) . "</td>";
            echo "<td>" . htmlspecialchars($client['last_name']) . "</td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
    } else {
        echo "<p>No clients marked as absent.</p>";
    }

    // Group: Pending
    echo "<h4>Pending Clients (" . count($pendingClients) . ")</h4>";
    if (!empty($pendingClients)) {
        echo "<table class='table table-bordered'>";
        echo "<thead><tr><th>Title</th><th>First Name</th><th>Last Name</th></tr></thead>";
        echo "<tbody>";
        foreach ($pendingClients as $client) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($client['title']) . "</td>";
            echo "<td>" . htmlspecialchars($client['first_name']) . "</td>";
            echo "<td>" . htmlspecialchars($client['last_name']) . "</td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
    } else {
        echo "<p>No clients marked as pending.</p>";
    }

    // Optional: Go back to the attendance page after showing the summary
    echo "<a href='attendance.php' class='btn btn-primary'>Go back to Attendance</a>";
}
?>
