<?php
include 'db_connect.php';
session_start();

// ✅ Ensure organiser is logged in
if (!isset($_SESSION['organiser_id'])) {
    header("Location: register.php");
    exit;
}

$organiser_id = $_SESSION['organiser_id'];

// ✅ Fetch organiser details
$stmt = $conn->prepare("SELECT * FROM organisers WHERE id = ?");
$stmt->bind_param("i", $organiser_id);
$stmt->execute();
$organiser = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$organiser) {
    echo "<script>alert('Organiser not found. Please login again.'); window.location.href='register.php';</script>";
    exit;
}

// ✅ Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $event_name = $_POST['event_name'];
    $description = $_POST['description'];
    $organiser_email = $organiser['email'];

    // Handle image upload
    if (isset($_FILES['event_photo']) && $_FILES['event_photo']['error'] == 0) {
        $ext = pathinfo($_FILES['event_photo']['name'], PATHINFO_EXTENSION);
        $unique_name = $organiser_email . '_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['event_photo']['tmp_name'], "uploads/" . $unique_name);

        // Insert into events table
        $stmt = $conn->prepare("INSERT INTO events (organiser_email, event_name, description, event_photo) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $organiser_email, $event_name, $description, $unique_name);
        $stmt->execute();
        $stmt->close();

        echo "<script>alert('Event created successfully!'); window.location.href='organiser_dashboard.php';</script>";
        exit;
    } else {
        echo "<script>alert('Please upload an image for the event.'); window.location.href='organiser_dashboard.php';</script>";
        exit;
    }
}
?>
