<?php
include 'db_connect.php';
session_start();

/* ===== Handle participant registration (POST) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['participant_register'])) {
  // Basic sanitization
  $name    = trim($_POST['name']    ?? '');
  $email   = trim($_POST['email']   ?? '');
  $phone   = trim($_POST['phone']   ?? '');
  $sport   = trim($_POST['sport']   ?? '');   // holds the event_name passed from openForm()
  $message = trim($_POST['message'] ?? '');

  if ($name === '' || $email === '' || $sport === '') {
      echo "<script>alert('Please fill Name, Email, and Sport.'); window.location.href='announcements.php';</script>";
      exit;
  }

  // Insert into participants table
  $stmt = $conn->prepare("
      INSERT INTO participants (name, email, phone, sport, message, created_at)
      VALUES (?, ?, ?, ?, ?, NOW())
  ");
  if ($stmt) {
      $stmt->bind_param("sssss", $name, $email, $phone, $sport, $message);
      if ($stmt->execute()) {
          $stmt->close();
          echo "<script>
                  alert('Registered successfully for ' + ".json_encode($sport).");
                  window.location.href = 'announcements.php';
                </script>";
          exit;
      } else {
          $err = htmlspecialchars($stmt->error);
          $stmt->close();
          echo "<script>alert('Failed to register: {$err}'); window.location.href='announcements.php';</script>";
          exit;
      }
  } else {
      $err = htmlspecialchars($conn->error);
      echo "<script>alert('Failed to prepare statement: {$err}'); window.location.href='announcements.php';</script>";
      exit;
  }
}

/* ===== Fetch featured + other events for display ===== */
$featured = $conn->query("SELECT e.*, o.name AS organiser_name, o.phone AS organiser_phone
                          FROM events e
                          LEFT JOIN organisers o ON o.id = e.organiser_id
                          ORDER BY e.id DESC LIMIT 1")->fetch_assoc();

$events = [];
if ($featured) {
  $res = $conn->query("SELECT e.*, o.name AS organiser_name, o.phone AS organiser_phone
                       FROM events e
                       LEFT JOIN organisers o ON o.id = e.organiser_id
                       WHERE e.id != ".(int)$featured['id']."
                       ORDER BY e.id DESC");
  while($r = $res->fetch_assoc()) $events[] = $r;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Announcements - UniSports</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
<style>
/* (styles unchanged from your version) */
body { font-family: 'Poppins', sans-serif; margin: 0; background: #eef2f7; }
.container { width: 90%; max-width: 1200px; margin: 30px auto; }

.featured-card { display:flex; gap:20px; background:#fff; border-radius:16px; padding:20px; box-shadow:0 5px 16px rgba(0,0,0,.12); align-items:center; position:relative; }
.featured-card img { width:50%; height:300px; object-fit:cover; border-radius:12px; cursor:pointer; }
.featured-info{ flex:1; }
.featured-info h2{ margin:0 0 10px; color:#051626; }
.contact-info{ font-size:14px; color:#333; margin:6px 0 10px; }
.contact-info span{ display:block; }
.featured-btn{ background:#00cc88; padding:12px 18px; border-radius:10px; color:#fff; border:none; margin-top:10px; font-size:16px; cursor:pointer; width:200px; }
.featured-btn:hover{ background:#00996b; }
.view-btn{ background:#ff9800; padding:8px 14px; border-radius:8px; color:#fff; border:none; margin-left:10px; cursor:pointer; }
.view-btn:hover{ background:#e68900; }
.event-date{ position:absolute; top:20px; right:25px; font-size:15px; color:#333; font-weight:500; }

.event-item{ display:flex; background:#fff; border-radius:14px; padding:15px; margin-bottom:18px; gap:18px; align-items:center; box-shadow:0 3px 12px rgba(0,0,0,.08); transition:.3s; position:relative; }
.event-item:hover{ transform:translateY(-3px); }
.event-item img{ width:160px; height:120px; object-fit:cover; border-radius:10px; cursor:pointer; }
.small-btn{ background:#007bff; padding:8px 14px; border-radius:8px; color:#fff; border:none; margin-top:8px; cursor:pointer; }
.small-btn:hover{ background:#0056b3; }

/* Popups */
.popup-img{ position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,.85); display:none; justify-content:center; align-items:center; z-index:3000; }
.popup-img img{ width:60%; max-width:800px; border-radius:12px; }
.popup-img span{ position:absolute; top:25px; right:45px; color:#fff; font-size:45px; font-weight:bold; cursor:pointer; }

/* Participant form popup */
.popup-form{ display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,.75); justify-content:center; align-items:center; z-index:4000; }
.form-content{ background:#fff; padding:25px 30px; border-radius:14px; width:90%; max-width:500px; box-shadow:0 5px 25px rgba(0,0,0,.3); position:relative; }
.form-content h2{ margin:0 0 15px; color:#051626; text-align:center; }
.form-content input, .form-content textarea{ width:100%; padding:10px; margin:8px 0; border-radius:8px; border:1px solid #ccc; font-family:'Poppins',sans-serif; }
.form-content button{ background:#00cc88; color:#fff; padding:10px 16px; border:none; border-radius:8px; cursor:pointer; width:100%; margin-top:10px; font-size:16px; }
.form-content button:hover{ background:#00996b; }
.close-btn{ position:absolute; top:12px; right:18px; font-size:25px; font-weight:bold; color:#333; cursor:pointer; }
</style>
</head>
<body>

<!-- Header -->
<header>
  <div class="navigation">
    <nav class="nav_bar">
      <a href="index.html">Home</a>
      <a href="#">Browse</a>
      <a href="calendar.php">Calendar</a>
    </nav>
  </div>
  <div class="logo">
    <a href="https://www.pondiuni.edu.in/">
      <img class="pu_home" src="images/logo.png" alt="PU">
    </a>
  </div>
  <div>
    <nav class="nav_bar">
      <a href="facilities.html">Facilities</a>
      <a href="https://www.pondiuni.edu.in/contact-directory/">Contacts</a>
      <a href="register.php">Register</a>
    </nav>
  </div>
</header>

<div class="container">

  <?php if ($featured): ?>
    <!-- Featured -->
    <div class="featured-card">
      <img src="uploads/<?php echo htmlspecialchars($featured['event_photo']); ?>" onclick="openImage(this.src)">
      <div class="featured-info">
        <h2><?php echo htmlspecialchars($featured['event_name']); ?></h2>
        <div class="contact-info">
          <span><strong>Organiser:</strong> <?php echo htmlspecialchars($featured['organiser_name'] ?: ''); ?></span>
          <span><strong>Email:</strong> <?php echo htmlspecialchars($featured['organiser_email']); ?></span>
          <span><strong>Phone:</strong> <?php echo htmlspecialchars($featured['organiser_phone'] ?: ''); ?></span>
        </div>
        <p><?php echo nl2br(htmlspecialchars($featured['description'])); ?></p>
        <button class="featured-btn" onclick="openForm('<?php echo addslashes($featured['event_name']); ?>')">Participate Now</button>
        <button class="view-btn" onclick="loadParticipants(<?php echo (int)$featured['id']; ?>)">View Participants</button>
      </div>
      <div class="event-date">
        <?php
          echo $featured['event_date'] ? date('d M Y', strtotime($featured['event_date'])) : date('d M Y', strtotime($featured['created_at']));
        ?>
      </div>
    </div>

    <!-- More Events -->
    <?php if (!empty($events)): ?>
      <div class="events-list" style="margin-top:28px;">
        <h2>More Events</h2>
        <?php foreach ($events as $row): ?>
          <div class="event-item">
            <img src="uploads/<?php echo htmlspecialchars($row['event_photo']); ?>" onclick="openImage(this.src)">
            <div class="event-text">
              <h3><?php echo htmlspecialchars($row['event_name']); ?></h3>
              <div class="contact-info">
                <span><strong>Organiser:</strong> <?php echo htmlspecialchars($row['organiser_name'] ?: ''); ?></span>
                <span><strong>Email:</strong> <?php echo htmlspecialchars($row['organiser_email']); ?></span>
                <span><strong>Phone:</strong> <?php echo htmlspecialchars($row['organiser_phone'] ?: ''); ?></span>
              </div>
              <p><?php echo htmlspecialchars(mb_strimwidth($row['description'], 0, 140, '...')); ?></p>
              <button class="small-btn" onclick="openForm('<?php echo addslashes($row['event_name']); ?>')">Participate</button>
              <button class="view-btn" onclick="loadParticipants(<?php echo (int)$row['id']; ?>)">View Participants</button>
            </div>
            <div class="event-date">
              <?php
                echo $row['event_date'] ? date('d M Y', strtotime($row['event_date'])) : date('d M Y', strtotime($row['created_at']));
              ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  <?php else: ?>
    <div style="background:#fff; border-radius:14px; padding:25px; box-shadow:0 5px 16px rgba(0,0,0,.12);">
      No events yet.
    </div>
  <?php endif; ?>
</div>

<!-- Image popup -->
<div class="popup-img" id="imgPopup">
  <span onclick="closeImage()">×</span>
  <img id="imgDisplay">
</div>

<!-- Participants popup -->
<div class="popup-img" id="participantsPopup">
  <span onclick="closeParticipants()">×</span>
  <div id="participantsContent" style="background:#fff; padding:20px; border-radius:10px; width:50%; max-height:70%; overflow-y:auto;"></div>
</div>

<!-- Registration popup -->
<div class="popup-form" id="popupForm">
  <div class="form-content">
    <span class="close-btn" onclick="closeForm()">×</span>
    <h2>Register as Participant</h2>
    <form method="POST">
      <input type="text" name="name" placeholder="Full Name" required>
      <input type="email" name="email" placeholder="Email" required>
      <input type="text" name="phone" placeholder="Phone">
      <input type="text" name="sport" id="sportInput" placeholder="Sport Name" required>
      <textarea name="message" rows="3" placeholder="Message (optional)"></textarea>
      <button type="submit" name="participant_register">Submit</button>
    </form>
  </div>
</div>

<script>
function openImage(src){
  document.getElementById('imgPopup').style.display = 'flex';
  document.getElementById('imgDisplay').src = src;
}
function closeImage(){ document.getElementById('imgPopup').style.display = 'none'; }

function openForm(sportName){
  document.getElementById('popupForm').style.display = 'flex';
  document.getElementById('sportInput').value = sportName;
}
function closeForm(){ document.getElementById('popupForm').style.display = 'none'; }

function loadParticipants(eventId){
  fetch('fetch_participants.php?event_id=' + eventId)
    .then(r => r.text())
    .then(html => {
      document.getElementById('participantsContent').innerHTML = html;
      document.getElementById('participantsPopup').style.display = 'flex';
    })
    .catch(() => alert('Failed to load participants.'));
}
function closeParticipants(){ document.getElementById('participantsPopup').style.display = 'none'; }
</script>
</body>
</html>
