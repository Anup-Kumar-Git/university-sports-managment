<?php
include 'db_connect.php';
session_start();

if (!isset($_SESSION['organiser_id'])) {
    header("Location: register.php");
    exit;
}

$organiser_id = (int)$_SESSION['organiser_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_name = trim($_POST['event_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $event_date = $_POST['event_date'] ?? null;

    // fetch organiser email (and ensure organiser exists)
    $stmt = $conn->prepare("SELECT email FROM organisers WHERE id = ?");
    $stmt->bind_param("i", $organiser_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$res) {
        echo "<script>alert('Invalid organiser. Please login again.'); window.location.href='register.php';</script>";
        exit;
    }
    $organiser_email = $res['email'];

    if ($event_name === '' || $description === '') {
        echo "<script>alert('Please fill all required fields'); history.back();</script>";
        exit;
    }

    // handle image
    $photo_name = null;
    if (!empty($_FILES['event_photo']) && $_FILES['event_photo']['error'] === 0) {
        $tmp = $_FILES['event_photo']['tmp_name'];
        $orig = basename($_FILES['event_photo']['name']);
        $mime = mime_content_type($tmp);
        $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
        if (!in_array($mime, $allowed)) {
            echo "<script>alert('Invalid image type.'); history.back();</script>";
            exit;
        }
        $ext = pathinfo($orig, PATHINFO_EXTENSION);
        $photo_name = $organiser_email . '_' . time() . '.' . $ext;
        if (!is_dir(__DIR__ . '/uploads')) {
            mkdir(__DIR__ . '/uploads', 0755, true);
        }
        if (!move_uploaded_file($tmp, __DIR__ . '/uploads/' . $photo_name)) {
            echo "<script>alert('Failed to upload image.'); history.back();</script>";
            exit;
        }
    }

    $stmt = $conn->prepare("
        INSERT INTO events (organiser_email, organiser_id, event_name, description, event_date, event_photo)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "sissss",
        $organiser_email,
        $organiser_id,
        $event_name,
        $description,
        $event_date,
        $photo_name
    );

    if ($stmt->execute()) {
        $stmt->close();
        echo "<script>alert('Event created!'); window.location.href='organiser_dashboard.php';</script>";
        exit;
    } else {
        $err = $stmt->error;
        $stmt->close();
        echo "<script>alert('Failed to create event: ".htmlspecialchars($err)."'); history.back();</script>";
        exit;
    }
}

header("Location: organiser_dashboard.php");
