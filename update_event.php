<?php
include 'db_connect.php';
session_start();

if (!isset($_SESSION['organiser_id'])) {
    header("Location: register.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = $_POST['event_id'];
    $event_name = $_POST['event_name'];
    $description = $_POST['description'];

    // Handle new image upload if provided
    $image_name = null;
    if (!empty($_FILES['event_photo']['name'])) {
        $target_dir = "uploads/";
        $image_name = time() . "_" . basename($_FILES["event_photo"]["name"]);
        $target_file = $target_dir . $image_name;
        move_uploaded_file($_FILES["event_photo"]["tmp_name"], $target_file);

        $stmt = $conn->prepare("UPDATE organisers SET event_name = ?, description = ?, event_photo = ? WHERE id = ?");
        $stmt->bind_param("sssi", $event_name, $description, $image_name, $event_id);
    } else {
        $stmt = $conn->prepare("UPDATE organisers SET event_name = ?, description = ? WHERE id = ?");
        $stmt->bind_param("ssi", $event_name, $description, $event_id);
    }

    $stmt->execute();
    $stmt->close();

    echo "<script>alert('Event updated successfully!'); window.location.href='organiser_dashboard.php';</script>";
    exit;
}
?>
