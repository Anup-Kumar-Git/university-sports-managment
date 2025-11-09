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

// ✅ Handle delete request (only delete event, not organiser)
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM organisers WHERE id = ? AND email = ?");
    $stmt->bind_param("is", $delete_id, $organiser['email']);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('Event deleted successfully.'); window.location.href='organiser_dashboard.php';</script>";
    exit;
}

// ✅ Fetch all events created by this organiser
$stmt = $conn->prepare("SELECT * FROM organisers WHERE email = ? ORDER BY id DESC");
$stmt->bind_param("s", $organiser['email']);
$stmt->execute();
$events = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Organiser Dashboard - UniSports</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<style>
body { font-family: 'Poppins', sans-serif; margin: 0; background: #eef2f7; }
.container { width: 90%; max-width: 1200px; margin: 30px auto; }

/* ✅ Header same as announcement.php */
header {
  background: #051626;
  color: white;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 40px;
}
.nav_bar a {
  color: white;
  text-decoration: none;
  margin: 0 12px;
  font-weight: 500;
}
.nav_bar a:hover { color: #00cc88; }
.logo img { height: 55px; }

.dashboard-info {
  background: white;
  border-radius: 16px;
  padding: 25px;
  margin-bottom: 25px;
  box-shadow: 0 5px 16px rgba(0,0,0,0.1);
}
.dashboard-info h2 { margin-bottom: 5px; color: #00274d; }
.dashboard-info p { margin: 5px 0; color: #333; }

.event-card {
  display: flex;
  align-items: center;
  background: white;
  border-radius: 14px;
  margin-bottom: 20px;
  padding: 15px;
  box-shadow: 0 4px 14px rgba(0,0,0,0.08);
  gap: 20px;
  transition: transform 0.3s ease;
}
.event-card:hover { transform: translateY(-4px); }

.event-card img {
  width: 180px;
  height: 130px;
  object-fit: cover;
  border-radius: 10px;
}

.event-info { flex: 1; position: relative; }
.event-info h3 { margin: 0; color: #00274d; }
.event-info p { margin: 6px 0; color: #444; }

.event-date {
  position: absolute;
  top: 0;
  right: 0;
  font-size: 14px;
  color: #555;
  font-weight: 500;
}

button {
  border: none;
  padding: 8px 14px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  font-family: 'Poppins', sans-serif;
}
.edit-btn {
  background: #007bff;
  color: white;
}
.edit-btn:hover { background: #0056b3; }
.delete-btn {
  background: #ff4d4d;
  color: white;
  margin-left: 8px;
}
.delete-btn:hover { background: #d63031; }

.no-events {
  text-align: center;
  padding: 40px;
  color: #777;
  font-size: 18px;
}

/* ✅ Edit Popup */
#editPopup, #newEventPopup {
  display: none;
  position: fixed;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background: rgba(0,0,0,0.6);
  justify-content: center;
  align-items: center;
  z-index: 5000;
}
.popup-content {
  background: white;
  padding: 25px;
  border-radius: 12px;
  width: 420px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.3);
  animation: fadeIn 0.3s ease;
}
@keyframes fadeIn { from {opacity:0; transform:scale(0.95);} to {opacity:1; transform:scale(1);} }
.popup-content input, .popup-content textarea {
  width: 100%;
  padding: 8px;
  margin: 8px 0;
  border-radius: 6px;
  border: 1px solid #ccc;
  font-family: 'Poppins', sans-serif;
}
.popup-content button { width: 100%; margin-top: 10px; }
.close-btn {
  background: gray;
  color: white;
}
.close-btn:hover { background: #444; }

.new-event-btn {
  background: #00b894;
  color: white;
  font-weight: 600;
  padding: 10px 16px;
  border-radius: 8px;
  margin-bottom: 20px;
  display: inline-block;
}
.new-event-btn:hover { background: #009874; }
</style>
</head>
<body>

<!-- ✅ Header -->
<header>
  <div class="navigation">
    <nav class="nav_bar">
      <a href="index.html">Home</a>
      <a href="#">Browse</a>
      <a href="https://www.pondiuni.edu.in/wp-content/uploads/2025/03/Calendar2025.pdf">Calendar</a>
    </nav>
  </div>
  <div class="logo">
    <a href="https://www.pondiuni.edu.in/">
      <img src="images/logo.png" alt="PU">
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
  </div>

  <!-- ✅ New Event Button -->
  <button class="new-event-btn" onclick="document.getElementById('newEventPopup').style.display='flex'">+ New Event</button>

  <h2 style="margin-bottom:15px;">Your Organised Events</h2>

  <?php if ($events->num_rows > 0) { ?>
      <?php while ($event = $events->fetch_assoc()) { ?>
          <div class="event-card">
              <img src="uploads/<?php echo htmlspecialchars($event['event_photo']); ?>" alt="Event Image">
              <div class="event-info">
                  <span class="event-date"><?php echo date('d M Y', strtotime($event['created_at'])); ?></span>
                  <h3><?php echo htmlspecialchars($event['event_name']); ?></h3>
                  <p><?php echo htmlspecialchars($event['description']); ?></p>
                  <p><strong>Contact:</strong> <?php echo htmlspecialchars($event['phone']); ?> | <?php echo htmlspecialchars($event['email']); ?></p>

                  <button class="edit-btn"
                      onclick="openEditForm(<?php echo $event['id']; ?>, '<?php echo addslashes($event['event_name']); ?>', '<?php echo addslashes($event['description']); ?>')">
                      Edit
                  </button>
                  <button class="delete-btn" onclick="deleteEvent(<?php echo $event['id']; ?>)">Delete</button>
              </div>
          </div>
      <?php } ?>
  <?php } else { ?>
      <div class="no-events">No events found. Click “+ New Event” to create one.</div>
  <?php } ?>
</div>

<!-- ✅ Edit Popup -->
<div id="editPopup">
  <div class="popup-content">
    <h3>Edit Event</h3>
    <form method="post" action="update_event.php" enctype="multipart/form-data">
      <input type="hidden" id="editEventId" name="event_id">
      <label>Event Name:</label>
      <input type="text" id="editEventName" name="event_name" required>
      <label>Description:</label>
      <textarea id="editEventDescription" name="description" rows="3" required></textarea>
      <label>Change Event Image:</label>
      <input type="file" name="event_photo" accept="image/*">
      <button type="submit" class="edit-btn">Save Changes</button>
      <button type="button" class="close-btn" onclick="closeEditForm()">Cancel</button>
    </form>
  </div>
</div>

<!-- ✅ New Event Popup -->
<div id="newEventPopup">
  <div class="popup-content">
    <h3>Add New Event</h3>
    <form method="post" action="new_event.php" enctype="multipart/form-data">
      <input type="hidden" name="organiser_email" value="<?php echo htmlspecialchars($organiser['email']); ?>">
      <label>Event Name:</label>
      <input type="text" name="event_name" required>
      <label>Description:</label>
      <textarea name="description" rows="3" required></textarea>
      <label>Event Image:</label>
      <input type="file" name="event_photo" accept="image/*" required>
      <button type="submit" class="edit-btn">Create Event</button>
      <button type="button" class="close-btn" onclick="document.getElementById('newEventPopup').style.display='none'">Cancel</button>
    </form>
  </div>
</div>

<script>
function deleteEvent(id) {
    if (confirm("Are you sure you want to delete this event?")) {
        window.location.href = "organiser_dashboard.php?delete=" + id;
    }
}
function openEditForm(id, name, description) {
    document.getElementById('editEventId').value = id;
    document.getElementById('editEventName').value = name;
    document.getElementById('editEventDescription').value = description;
    document.getElementById('editPopup').style.display = 'flex';
}
function closeEditForm() {
    document.getElementById('editPopup').style.display = 'none';
}
</script>

</body>
</html>
