<?php
include 'db_connect.php';
session_start();

// Ensure organiser is logged in
if (!isset($_SESSION['organiser_id'])) {
    header("Location: register.php");
    exit;
}

$organiser_id = (int)$_SESSION['organiser_id'];

/* Fetch organiser profile (from organisers) */
$stmt = $conn->prepare("SELECT id, name, email, phone FROM organisers WHERE id = ?");
$stmt->bind_param("i", $organiser_id);
$stmt->execute();
$organiser = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$organiser) {
    echo "<script>alert('Organiser not found. Please login again.'); window.location.href='register.php';</script>";
    exit;
}

/* Delete an event (only from events table) */
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];

    // Make sure the event belongs to this organiser
    $stmt = $conn->prepare("DELETE FROM events WHERE id = ? AND organiser_id = ?");
    $stmt->bind_param("ii", $delete_id, $organiser_id);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('Event deleted successfully.'); window.location.href='organiser_dashboard.php';</script>";
    exit;
}

/* Fetch all events by this organiser (from events) */
$stmt = $conn->prepare("
    SELECT id, event_name, description, event_photo, event_date, created_at
    FROM events
    WHERE organiser_id = ?
    ORDER BY id DESC
");
$stmt->bind_param("i", $organiser_id);
$stmt->execute();
$events = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Organiser Dashboard - UniSports</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="styles.css">

<style>
  body { font-family: 'Poppins', sans-serif; margin: 0; background: #eef2f7; }
  .container { width: 90%; max-width: 1200px; margin: 30px auto; }

  /* Dashboard blocks */
  .dashboard-info{ background:#fff; border-radius:16px; padding:25px; margin-bottom:25px; box-shadow:0 5px 16px rgba(0,0,0,.1); position:relative; }
  .dashboard-info h2{ margin:0 0 6px; color:#00274d; }
  .dashboard-info p{ margin:4px 0; color:#333; }
  .new-event-btn{ position:absolute; top:20px; right:20px; background:#00b894; color:#fff; border:none; padding:10px 16px; border-radius:8px; font-weight:600; cursor:pointer; }
  .new-event-btn:hover{ background:#009874; }

  .event-card{ display:flex; align-items:center; background:#fff; border-radius:14px; margin-bottom:20px; padding:15px; box-shadow:0 4px 14px rgba(0,0,0,.08); gap:20px; transition:.3s; }
  .event-card:hover{ transform:translateY(-4px); }
  .event-card img{ width:180px; height:130px; object-fit:cover; border-radius:10px; cursor:pointer; }
  .event-info{ flex:1; position:relative; }
  .event-info h3{ margin:0; color:#00274d; }
  .event-info p{ margin:6px 0; color:#444; }
  .event-date{ position:absolute; top:0; right:0; font-size:14px; color:#555; font-weight:500; }

  button{ border:none; padding:8px 14px; border-radius:8px; cursor:pointer; font-weight:500; font-family:'Poppins',sans-serif; }
  .edit-btn{ background:#007bff; color:#fff; }
  .edit-btn:hover{ background:#0056b3; }
  .delete-btn{ background:#ff4d4d; color:#fff; margin-left:8px; }
  .delete-btn:hover{ background:#d63031; }
  .view-btn{ background:#ff9800; color:#fff; margin-left:8px; }
  .view-btn:hover{ background:#e68900; }

  .no-events{ text-align:center; padding:40px; color:#777; font-size:18px; }

  /* Popups */
  #editPopup, #newEventPopup, #participantsPopup{
    display:none; position:fixed; top:0; left:0; width:100%; height:100%;
    background:rgba(0,0,0,.6); justify-content:center; align-items:center; z-index:5000;
  }
  .popup-content{ background:#fff; padding:25px; border-radius:12px; width:420px; box-shadow:0 4px 20px rgba(0,0,0,.3); animation:fadeIn .3s ease; }
  @keyframes fadeIn{ from{opacity:0; transform:scale(.95)} to{opacity:1; transform:scale(1)} }
  .popup-content input, .popup-content textarea{ width:100%; padding:8px; margin:8px 0; border-radius:6px; border:1px solid #ccc; font-family:'Poppins',sans-serif; }
  .popup-content button{ width:100%; margin-top:10px; }
  .close-btn{ background:gray; color:#fff; }
  .close-btn:hover{ background:#444; }

  /* Participants area inside popup */
  #participantsBox{ background:#fff; padding:20px; border-radius:10px; width:70%; max-height:70%; overflow:auto; }
</style>
</head>
<body>

<!-- Header (SAME as other pages using styles.css classes) -->
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
  <div class="dashboard-info">
    <h2>Welcome, <?php echo htmlspecialchars($organiser['name']); ?> 👋</h2>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($organiser['email']); ?></p>
    <p><strong>Phone:</strong> <?php echo htmlspecialchars($organiser['phone']); ?></p>
    <button class="new-event-btn" onclick="document.getElementById('newEventPopup').style.display='flex'">+ New Event</button>
  </div>

  <h2 style="margin-bottom:15px;">Your Organised Events</h2>

  <?php if ($events->num_rows > 0): ?>
      <?php while ($event = $events->fetch_assoc()): ?>
          <div class="event-card">
              <img src="uploads/<?php echo htmlspecialchars($event['event_photo']); ?>" alt="Event Image"
                   onclick="window.open('uploads/<?php echo htmlspecialchars($event['event_photo']); ?>','_blank')">
              <div class="event-info">
                  <span class="event-date">
                    <?php
                      echo $event['event_date']
                        ? date('d M Y', strtotime($event['event_date']))
                        : date('d M Y', strtotime($event['created_at']));
                    ?>
                  </span>
                  <h3><?php echo htmlspecialchars($event['event_name']); ?></h3>
                  <p><?php echo htmlspecialchars($event['description']); ?></p>
                  <p><strong>Contact:</strong> <?php echo htmlspecialchars($organiser['phone']); ?> | <?php echo htmlspecialchars($organiser['email']); ?></p>

                  <button class="edit-btn"
                      onclick="openEditForm(
                        <?php echo (int)$event['id']; ?>,
                        '<?php echo addslashes($event['event_name']); ?>',
                        '<?php echo addslashes($event['description']); ?>',
                        '<?php echo $event['event_date'] ? htmlspecialchars($event['event_date']) : ''; ?>'
                      )">
                      Edit
                  </button>
                  <button class="delete-btn" onclick="deleteEvent(<?php echo (int)$event['id']; ?>)">Delete</button>
                  <!-- View Participants button (NEW) -->
                  <button class="view-btn" onclick="loadParticipants(<?php echo (int)$event['id']; ?>)">View Participants</button>
              </div>
          </div>
      <?php endwhile; ?>
  <?php else: ?>
      <div class="no-events">No events found. Click “+ New Event” to create one.</div>
  <?php endif; ?>
</div>

<!-- Edit Event Popup -->
<div id="editPopup">
  <div class="popup-content">
    <h3>Edit Event</h3>
    <form method="post" action="update_event.php" enctype="multipart/form-data">
      <input type="hidden" id="editEventId" name="event_id">
      <label>Event Name:</label>
      <input type="text" id="editEventName" name="event_name" required>
      <label>Description:</label>
      <textarea id="editEventDescription" name="description" rows="3" required></textarea>
      <label>Event Date (optional):</label>
      <input type="date" id="editEventDate" name="event_date">
      <label>Change Event Image (optional):</label>
      <input type="file" name="event_photo" accept="image/*">
      <button type="submit" class="edit-btn">Save Changes</button>
      <button type="button" class="close-btn" onclick="closeEditForm()">Cancel</button>
    </form>
  </div>
</div>

<!-- New Event Popup -->
<div id="newEventPopup">
  <div class="popup-content">
    <h3>Add New Event</h3>
    <form method="post" action="new_event.php" enctype="multipart/form-data">
      <input type="hidden" name="organiser_id" value="<?php echo (int)$organiser['id']; ?>">
      <input type="hidden" name="organiser_email" value="<?php echo htmlspecialchars($organiser['email']); ?>">
      <label>Event Name:</label>
      <input type="text" name="event_name" required>
      <label>Description:</label>
      <textarea name="description" rows="3" required></textarea>
      <label>Event Date (optional):</label>
      <input type="date" name="event_date">
      <label>Event Image:</label>
      <input type="file" name="event_photo" accept="image/*" required>
      <button type="submit" class="edit-btn">Create Event</button>
      <button type="button" class="close-btn" onclick="document.getElementById('newEventPopup').style.display='none'">Cancel</button>
    </form>
  </div>
</div>

<!-- Participants Popup -->
<div id="participantsPopup">
  <div id="participantsBox"></div>
</div>

<script>
function deleteEvent(id){
  if(confirm("Are you sure you want to delete this event?")){
    window.location.href = "organiser_dashboard.php?delete=" + id;
  }
}
function openEditForm(id, name, description, dateVal){
  document.getElementById('editEventId').value = id;
  document.getElementById('editEventName').value = name;
  document.getElementById('editEventDescription').value = description;
  document.getElementById('editEventDate').value = dateVal || '';
  document.getElementById('editPopup').style.display = 'flex';
}
function closeEditForm(){
  document.getElementById('editPopup').style.display = 'none';
}

// View participants (uses fetch_participants.php)
function loadParticipants(eventId){
  fetch('fetch_participants.php?event_id=' + eventId)
    .then(r => r.text())
    .then(html => {
      document.getElementById('participantsBox').innerHTML =
        html + '<div style="margin-top:10px; text-align:center;">' +
        '<button onclick="closeParticipants()" class="close-btn" style="padding:8px 14px; border-radius:8px;">Close</button>' +
        '</div>';
      document.getElementById('participantsPopup').style.display = 'flex';
    });
}
function closeParticipants(){ document.getElementById('participantsPopup').style.display = 'none'; }
</script>
</body>
</html>
