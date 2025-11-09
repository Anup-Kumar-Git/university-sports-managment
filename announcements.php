<?php
include 'db_connect.php';
session_start();

// Fetch featured (latest) event
$featured = $conn->query("SELECT * FROM organisers ORDER BY id DESC LIMIT 1")->fetch_assoc();

// Fetch all other events excluding featured
$events = $conn->query("SELECT * FROM organisers WHERE id != {$featured['id']} ORDER BY id DESC");

// ✅ Handle participant registration popup form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['participant_register'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $sport = $_POST['sport'];
    $message = $_POST['message'];

    $stmt = $conn->prepare("INSERT INTO participants (name, email, phone, sport, message) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $phone, $sport, $message);
    $stmt->execute();

    echo "<script>alert('Registration Successful!');</script>";
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
body { font-family: 'Poppins', sans-serif; margin: 0; background: #eef2f7; }
.container { width: 90%; max-width: 1200px; margin: 30px auto; }

.featured-card { display: flex; gap: 20px; background: white; border-radius: 16px;
padding: 20px; box-shadow: 0 5px 16px rgba(0,0,0,0.12); align-items: center; position: relative; }

.featured-card img { width: 50%; height: 300px; object-fit: cover;
border-radius: 12px; cursor: pointer; }

.featured-info { flex: 1; }
.featured-btn { background: #00cc88; padding: 12px 18px; border-radius: 10px;
color: white; border:none; margin-top: 12px; font-size: 16px; cursor:pointer; width: 200px; }
.featured-btn:hover { background: #00996b; }

.event-date { position: absolute; top: 20px; right: 25px; font-size: 15px; color: #333; font-weight: 500; }

.event-item { display: flex; background: white; border-radius: 14px; padding: 15px;
margin-bottom: 18px; gap: 18px; align-items: center;
box-shadow: 0 3px 12px rgba(0,0,0,0.08); transition: 0.3s; position: relative; }

.event-item img { width: 160px; height: 120px; object-fit: cover; border-radius: 10px; cursor: pointer; }

.small-btn { background: #007bff; padding: 8px 14px; border-radius: 8px;
color: white; font-size: 14px; margin-top: 8px; text-decoration: none; cursor: pointer; border: none; }
.small-btn:hover { background: #0056b3; }

.view-btn {
    background: #ff9800;
    padding: 8px 14px;
    border-radius: 8px;
    color: white;
    font-size: 14px;
    text-decoration: none;
    margin-left: 10px;
    border: none;
    cursor: pointer;
}
.view-btn:hover { background: #e68900; }

.contact-info { font-size: 14px; color: #333; margin-top: 5px; }
.contact-info span { display: block; }

.popup-img { position: fixed; top:0; left:0; width:100%; height:100%;
background:rgba(0,0,0,0.85); display:none; justify-content:center;
align-items:center; z-index:3000; }
.popup-img img { width:60%; max-width:800px; border-radius:12px; }
.popup-img span { position:absolute; top:25px; right:45px; color:white;
font-size:45px; font-weight:bold; cursor:pointer; }

.popup-form {
  display: none;
  position: fixed;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background: rgba(0,0,0,0.75);
  justify-content: center;
  align-items: center;
  z-index: 4000;
}
.form-content {
  background: white;
  padding: 25px 30px;
  border-radius: 14px;
  width: 90%;
  max-width: 500px;
  box-shadow: 0 5px 25px rgba(0,0,0,0.3);
  position: relative;
  animation: slideIn 0.4s ease;
}
@keyframes slideIn {
  from { transform: translateY(-20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}
.form-content h2 {
  margin-bottom: 15px;
  color: #051626;
  text-align: center;
}
.form-content input, .form-content textarea {
  width: 100%;
  padding: 10px;
  margin: 8px 0;
  border-radius: 8px;
  border: 1px solid #ccc;
  font-family: 'Poppins', sans-serif;
}
.form-content button {
  background: #00cc88;
  color: white;
  padding: 10px 16px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  width: 100%;
  margin-top: 10px;
  font-size: 16px;
}
.form-content button:hover { background: #00996b; }
.close-btn {
  position: absolute;
  top: 12px;
  right: 18px;
  font-size: 25px;
  font-weight: bold;
  color: #333;
  cursor: pointer;
}
</style>
</head>

<body>

<!-- ✅ SAME HEADER -->
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

    <!-- ⭐ Featured Event -->
    <div class="featured-card">
        <img src="uploads/<?php echo $featured['event_photo']; ?>" onclick="openPopup(this.src)">
        <div class="featured-info">
            <h2><?php echo $featured['event_name']; ?></h2>
            <p><strong>Organiser:</strong> <?php echo $featured['name']; ?></p>
            <div class="contact-info">
                <span><strong>Email:</strong> <?php echo $featured['email']; ?></span>
                <span><strong>Phone:</strong> <?php echo $featured['phone']; ?></span>
            </div>
            <p><?php echo $featured['description']; ?></p>
            <button class="featured-btn" onclick="openForm('<?php echo $featured['event_name']; ?>')">Participate Now</button>
            <button class="view-btn" onclick="loadParticipants(<?php echo $featured['id']; ?>)">View Participants</button>
        </div>
        <div class="event-date"><?php echo date('d M Y', strtotime($featured['created_at'])); ?></div>
    </div>

    <!-- 🏆 Other Events -->
    <div class="events-list">
        <h2>More Events</h2>
        <?php while($row = $events->fetch_assoc()) { ?>
        <div class="event-item">
            <img src="uploads/<?php echo $row['event_photo']; ?>" onclick="openPopup(this.src)">
            <div class="event-text">
                <h3><?php echo $row['event_name']; ?></h3>
                <p><strong>Organiser:</strong> <?php echo $row['name']; ?></p>
                <div class="contact-info">
                    <span><strong>Email:</strong> <?php echo $row['email']; ?></span>
                    <span><strong>Phone:</strong> <?php echo $row['phone']; ?></span>
                </div>
                <p><?php echo substr($row['description'],0,70); ?>...</p>
                <button class="small-btn" onclick="openForm('<?php echo $row['event_name']; ?>')">Participate</button>
                <button class="view-btn" onclick="loadParticipants(<?php echo $row['id']; ?>)">View Participants</button>
            </div>
            <div class="event-date"><?php echo date('d M Y', strtotime($row['created_at'])); ?></div>
        </div>
        <?php } ?>
    </div>
</div>

<!-- 🔍 Image Popup -->
<div class="popup-img" id="popupImg">
    <span onclick="closePopup()">×</span>
    <img id="popupImageDisplay">
</div>

<!-- ✅ Participants Popup -->
<div class="popup-img" id="participantsPopup">
    <span onclick="closeParticipants()">×</span>
    <div id="participantsContent" style="background:#fff; padding:20px; border-radius:10px; width:50%; max-height:70%; overflow-y:auto;"></div>
</div>

<!-- 🧾 Registration Popup -->
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
function openPopup(src){
    document.getElementById("popupImg").style.display = "flex";
    document.getElementById("popupImageDisplay").src = src;
}
function closePopup(){ document.getElementById("popupImg").style.display = "none"; }

function loadParticipants(eventId) {
    fetch("fetch_participants.php?event_id=" + eventId)
    .then(response => response.text())
    .then(data => {
        document.getElementById("participantsContent").innerHTML = data;
        document.getElementById("participantsPopup").style.display = "flex";
    });
}
function closeParticipants(){
    document.getElementById("participantsPopup").style.display = "none";
}

function openForm(sportName){
    document.getElementById("popupForm").style.display = "flex";
    document.getElementById("sportInput").value = sportName;
}
function closeForm(){
    document.getElementById("popupForm").style.display = "none";
}
</script>

</body>
</html>
