<?php
include 'db_connect.php';

$event_id = $_GET['event_id'];

// Fetch event details using ID
$eventQuery = mysqli_query($conn, "SELECT event_name FROM organisers WHERE id = '$event_id'");
$eventData = mysqli_fetch_assoc($eventQuery);
$event_name = $eventData['event_name'];

// Fetch participants linked by sport (event name)
$query = "SELECT name, email, phone FROM participants WHERE sport = '$event_name'";
$result = mysqli_query($conn, $query);

echo "<h3 style='text-align:center; margin-bottom:10px; font-size:20px; color:#003b70;'>Participants - $event_name</h3>";

if (mysqli_num_rows($result) > 0) {
    echo "<table border='1' cellpadding='10' cellspacing='0' width='100%' 
            style='border-collapse:collapse; text-align:center; font-size:14px;'>
            <tr style='background:#003b70; color:white;'>
                <th>Name</th><th>Email</th><th>Phone</th>
            </tr>";

    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>
                <td>{$row['name']}</td>
                <td>{$row['email']}</td>
                <td>{$row['phone']}</td>
              </tr>";
    }

    echo "</table>";

} else {
    echo "<p style='text-align:center; color:#444;'>No participants registered yet for this event.</p>";
}
?>
