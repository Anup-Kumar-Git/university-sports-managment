<?php
include 'db_connect.php';
session_start();

if (!isset($_SESSION['organiser_id'])) {
    header("Location: register.php");
    exit;
}
$organiser_id = (int)$_SESSION['organiser_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = (int)($_POST['event_id'] ?? 0);
    $event_name = trim($_POST['event_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $event_date = $_POST['event_date'] ?? null;

    if ($event_id <= 0 || $event_name === '' || $description === '') {
        echo "<script>alert('Please fill all required fields'); history.back();</script>";
        exit;
    }

    // Ensure event belongs to organiser
    $stmt = $conn->prepare("SELECT event_photo FROM events WHERE id = ? AND organiser_id = ?");
    $stmt->bind_param("ii", $event_id, $organiser_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        echo "<script>alert('Event not found.'); window.location.href='organiser_dashboard.php';</script>";
        exit;
    }

    $photo_name = $row['event_photo'];

    // Optional new image
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

        // get organiser email for filename prefix
        $s = $conn->prepare("SELECT email FROM organisers WHERE id = ?");
        $s->bind_param("i", $organiser_id);
        $s->execute();
        $org = $s->get_result()->fetch_assoc();
        $s->close();
        $prefix = $org ? $org['email'] : ('org_'.$organiser_id);

        $new_name = $prefix . '_' . time() . '.' . $ext;
        if (!is_dir(__DIR__ . '/uploads')) {
            mkdir(__DIR__ . '/uploads', 0755, true);
        }
        if (!move_uploaded_file($tmp, __DIR__ . '/uploads/' . $new_name)) {
            echo "<script>alert('Failed to upload image.'); history.back();</script>";
            exit;
        }
        $photo_name = $new_name;
    }

    $stmt = $conn->prepare("
        UPDATE events
        SET event_name = ?, description = ?, event_date = ?, event_photo = ?
        WHERE id = ? AND organiser_id = ?
    ");
    $stmt->bind_param("ssssii", $event_name, $description, $event_date, $photo_name, $event_id, $organiser_id);

    if ($stmt->execute()) {
        $stmt->close();
        echo "<script>alert('Event updated!'); window.location.href='organiser_dashboard.php';</script>";
        exit;
    } else {
        $err = $stmt->error;
        $stmt->close();
        echo "<script>alert('Failed to update event: ".htmlspecialchars($err)."'); history.back();</script>";
        exit;
    }
}

header("Location: organiser_dashboard.php");
