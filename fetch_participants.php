<?php
include 'db_connect.php';

$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
if ($event_id <= 0) {
    echo "<h3>Participants -</h3><p>Invalid event.</p>";
    exit;
}

/* Look up event_name from events, then find participants where participants.sport = event_name */
$stmt = $conn->prepare("SELECT event_name FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event) {
    echo "<h3>Participants -</h3><p>Event not found.</p>";
    exit;
}

$ename = $event['event_name'];
$stmt = $conn->prepare("SELECT name, email, phone, created_at FROM participants WHERE sport = ? ORDER BY id DESC");
$stmt->bind_param("s", $ename);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

echo "<h3>Participants - ".htmlspecialchars($ename)."</h3>";

if ($result->num_rows === 0) {
    echo "<p>No participants registered yet for this event.</p>";
    exit;
}

echo '<table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-collapse:collapse;">
        <tr style="background:#f5f5f5;">
            <th>Name</th><th>Email</th><th>Phone</th><th>Registered At</th>
        </tr>';
while ($row = $result->fetch_assoc()) {
    echo '<tr>
            <td>'.htmlspecialchars($row['name']).'</td>
            <td>'.htmlspecialchars($row['email']).'</td>
            <td>'.htmlspecialchars($row['phone']).'</td>
            <td>'.htmlspecialchars($row['created_at']).'</td>
          </tr>';
}
echo '</table>';
