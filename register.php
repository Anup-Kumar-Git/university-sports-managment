<?php
include 'db_connect.php';
session_start();

// Helper: sanitize input
function clean($v) {
    return trim($v);
}

// Allowed image mime types
$allowed_image_types = ['image/jpeg','image/png','image/webp','image/gif'];

/* ------------------------------
   Handle organiser registration
   ------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_type']) && $_POST['register_type'] === 'organiser') {
    $name = clean($_POST['org_name'] ?? '');
    $email = clean($_POST['org_email'] ?? '');
    $phone = clean($_POST['org_phone'] ?? '');
    $password = $_POST['org_password'] ?? '';
    $event_name = clean($_POST['org_event'] ?? '');
    $description = clean($_POST['org_desc'] ?? '');
    $photo_name = null;

    // Basic server-side validation
    if (!$name || !$email || !$password || !$event_name) {
        echo "<script>alert('Please fill required organiser fields.'); window.location.href='register.php';</script>";
        exit;
    }

    // Handle photo upload if provided
    if (!empty($_FILES['org_photo']) && $_FILES['org_photo']['error'] === 0) {
        $tmp = $_FILES['org_photo']['tmp_name'];
        $orig = basename($_FILES['org_photo']['name']);
        $mime = mime_content_type($tmp);
        if (!in_array($mime, $allowed_image_types)) {
            echo "<script>alert('Invalid image type for event photo. Allowed: jpg, png, webp, gif'); window.location.href='register.php';</script>";
            exit;
        }
        $ext = pathinfo($orig, PATHINFO_EXTENSION);
        $photo_name = time() . "_" . bin2hex(random_bytes(6)) . "." . $ext;
        $target_path = __DIR__ . "/uploads/" . $photo_name;
        if (!is_dir(__DIR__ . "/uploads")) {
            mkdir(__DIR__ . "/uploads", 0755, true);
        }
        if (!move_uploaded_file($tmp, $target_path)) {
            echo "<script>alert('Failed to upload event photo.'); window.location.href='register.php';</script>";
            exit;
        }
    }

    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert into DB with prepared statement
    $stmt = $conn->prepare("INSERT INTO organisers (name, email, phone, password, event_name, description, event_photo) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        echo "<script>alert('Database error: prepare failed');</script>";
    } else {
        $stmt->bind_param("sssssss", $name, $email, $phone, $password_hash, $event_name, $description, $photo_name);
        if ($stmt->execute()) {
            $stmt->close();
            echo "<script>alert('Organiser registered successfully!'); window.location.href='register.php';</script>";
            exit;
        } else {
            // if duplicate email or other error
            $err = $stmt->error;
            $stmt->close();
            echo "<script>alert('Registration failed: ".htmlspecialchars($err)."'); window.location.href='register.php';</script>";
            exit;
        }
    }
}

/* ------------------------------
   Handle participant registration
   ------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_type']) && $_POST['register_type'] === 'participant') {
    $name = clean($_POST['part_name'] ?? '');
    $email = clean($_POST['part_email'] ?? '');
    $phone = clean($_POST['part_phone'] ?? '');
    $sport = clean($_POST['part_sport'] ?? '');
    $message = clean($_POST['part_message'] ?? '');
    $player_list = null;

    // Handle file (player list) if provided
    if (!empty($_FILES['part_list']) && $_FILES['part_list']['error'] === 0) {
        $tmp = $_FILES['part_list']['tmp_name'];
        $orig = basename($_FILES['part_list']['name']);
        // Accept any file but limit size (example)
        $ext = pathinfo($orig, PATHINFO_EXTENSION);
        $player_list = time() . "_" . bin2hex(random_bytes(6)) . "." . $ext;
        $target_path = __DIR__ . "/uploads/" . $player_list;
        if (!is_dir(__DIR__ . "/uploads")) {
            mkdir(__DIR__ . "/uploads", 0755, true);
        }
        if (!move_uploaded_file($tmp, $target_path)) {
            echo "<script>alert('Failed to upload player list.'); window.location.href='register.php';</script>";
            exit;
        }
    }

    $stmt = $conn->prepare("INSERT INTO participants (name, email, phone, sport, player_list, message) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        echo "<script>alert('Database error: prepare failed');</script>";
    } else {
        $stmt->bind_param("ssssss", $name, $email, $phone, $sport, $player_list, $message);
        if ($stmt->execute()) {
            $stmt->close();
            echo "<script>alert('Participant registered successfully!'); window.location.href='register.php';</script>";
            exit;
        } else {
            $err = $stmt->error;
            $stmt->close();
            echo "<script>alert('Registration failed: ".htmlspecialchars($err)."'); window.location.href='register.php';</script>";
            exit;
        }
    }
}

/* ------------------------------
   Handle organiser login
   ------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_organiser'])) {
    $email = clean($_POST['login_email'] ?? '');
    $password = $_POST['login_password'] ?? '';

    $stmt = $conn->prepare("SELECT id, name, email, phone, password FROM organisers WHERE email = ?");
    if (!$stmt) {
        echo "<script>alert('Database error: prepare failed');</script>";
    } else {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['organiser_id'] = $user['id'];
                $_SESSION['organiser_name'] = $user['name'];
                header("Location: organiser_dashboard.php"); // create later if needed
                exit;
            } else {
                echo "<script>alert('Invalid password');</script>";
            }
        } else {
            echo "<script>alert('Organiser not found');</script>";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register | KHELOGRAM</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    body {
      background: #f8fafc;
      font-family: 'Kanit', sans-serif;
    }

    /* ==== REGISTRATION AREA ==== */
    .register_section {
      display: flex;
      justify-content: center;
      align-items: flex-start;
      padding: 70px 20px;
    }

    .register_container {
      background: white;
      width: 90%;
      max-width: 750px;
      padding: 40px;
      border-radius: 16px;
      box-shadow: 0 6px 20px rgba(0,0,0,0.1);
      transition: all 0.3s ease;
    }

    .register_container:hover {
      transform: translateY(-5px);
    }

    .register_container h1 {
      text-align: center;
      color: #00274d;
      font-size: 28px;
      margin-bottom: 8px;
    }

    .subtitle {
      text-align: center;
      color: #555;
      margin-bottom: 30px;
      font-size: 16px;
    }

    .register_tabs {
      display: flex;
      justify-content: center;
      margin-bottom: 25px;
    }

    .tab-btn {
      flex: 1;
      padding: 12px;
      background: #ddd;
      border: none;
      cursor: pointer;
      font-size: 16px;
      border-radius: 8px 8px 0 0;
      transition: background 0.3s ease;
      margin: 0 6px;
    }

    .tab-btn.active {
      background: #00274d;
      color: white;
      font-weight: bold;
    }

    form {
      display: none;
      animation: fadeIn 0.3s ease;
    }

    form.active {
      display: block;
    }

    label {
      display: block;
      margin-bottom: 6px;
      font-weight: 500;
      color: #333;
    }

    input, select, textarea {
      width: 100%;
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
      margin-bottom: 15px;
      font-size: 15px;
      transition: all 0.3s ease;
    }

    input:focus, textarea:focus, select:focus {
      outline: none;
      border-color: #00274d;
      box-shadow: 0 0 4px rgba(0, 39, 77, 0.4);
    }

    button[type="submit"] {
      background: #00274d;
      color: white;
      border: none;
      border-radius: 8px;
      padding: 12px;
      font-size: 16px;
      cursor: pointer;
      transition: background 0.3s ease;
      width: 100%;
    }

    button[type="submit"]:hover {
      background: #004080;
    }

    .login-btn {
      margin-top: 25px;
      width: 100%;
      background: #0066cc;
      color: white;
      padding: 12px;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      cursor: pointer;
      transition: 0.3s ease;
    }

    .login-btn:hover {
      background: #004a99;
    }

    /* ==== LOGIN MODAL ==== */
    .modal {
      display: none;
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background-color: rgba(0, 0, 0, 0.6);
      justify-content: center;
      align-items: center;
      z-index: 1000;
    }

    .modal-content {
      background: white;
      padding: 30px;
      border-radius: 12px;
      width: 400px;
      position: relative;
      text-align: center;
      animation: slideDown 0.3s ease;
    }

    .modal-content input {
      width: 100%;
      padding: 10px;
      margin: 10px 0;
      border-radius: 8px;
      border: 1px solid #ccc;
    }

    .close {
      position: absolute;
      top: 15px;
      right: 20px;
      font-size: 22px;
      cursor: pointer;
      color: #333;
    }

    @keyframes fadeIn {
      from {opacity: 0;}
      to {opacity: 1;}
    }

    @keyframes slideDown {
      from {transform: translateY(-30px); opacity: 0;}
      to {transform: translateY(0); opacity: 1;}
    }
  </style>
</head>

<body>

  <!-- ==== SAME HEADER ==== -->
  <header>
    <div class="navigation">
      <nav class="nav_bar">
        <a href="announcements.php">Announcements</a>
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
        <a href="index.html">Home</a>
      </nav>
    </div>
  </header>

  <!-- ==== MAIN CONTENT ==== -->
  <main>
    <section class="register_section">
      <div class="register_container">
        <h1>Register for Sports Events</h1>
        <p class="subtitle">Select your role to participate in KHELOGRAM</p>

        <div class="register_tabs">
          <button class="tab-btn active" data-target="#organiserForm">Organiser</button>
          <button class="tab-btn" data-target="#participantForm">Participant</button>
        </div>

        <!-- Organiser Form -->
        <form id="organiserForm" class="active" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="register_type" value="organiser">
          <label>Full Name</label>
          <input type="text" name="org_name" placeholder="Enter your name" required>
          <label>Email</label>
          <input type="email" name="org_email" placeholder="Enter your email" required>
          <label>Phone</label>
          <input type="tel" name="org_phone" placeholder="Enter phone number" required>
          <label>Password</label>
          <input type="password" name="org_password" placeholder="Create password" required>
          <label>Sports Name</label>
          <input type="text" name="org_event" placeholder="Name of your event" required>
          <label>Sports Description</label>
          <textarea name="org_desc" rows="3" placeholder="Describe your event"></textarea>
          <label>Upload Event Photo</label>
          <input type="file" name="org_photo" accept="image/*">
          <button type="submit">Submit as Organiser</button>
        </form>

        <!-- Participant Form -->
        <form id="participantForm" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="register_type" value="participant">
          <label>Full Name</label>
          <input type="text" name="part_name" placeholder="Enter your name" required>
          <label>Email</label>
          <input type="email" name="part_email" placeholder="Enter your email" required>
          <label>Phone Number</label>
          <input type="tel" name="part_phone" placeholder="Enter phone number" required>
          <label>Select Sport</label>
          <select name="part_sport" required>
            <option value="">Choose a sport</option>
            <option>Football</option>
            <option>Cricket</option>
            <option>Basketball</option>
            <option>Athletics</option>
            <option>Volleyball</option>
          </select>
          <label>Upload Player List</label>
          <input type="file" name="part_list">
          <label>Message (Optional)</label>
          <textarea name="part_message" rows="3" placeholder="Any message or notes"></textarea>
          <button type="submit">Submit as Participant</button>
        </form>

        <button class="login-btn" id="openLogin">Login as Organiser</button>
      </div>
    </section>
  </main>

  <!-- ==== LOGIN MODAL ==== -->
  <div id="loginModal" class="modal">
    <div class="modal-content">
      <span class="close" id="closeModal">&times;</span>
      <h2>Login as Organiser</h2>
      <form method="POST">
        <input type="email" name="login_email" placeholder="Email" required>
        <input type="password" name="login_password" placeholder="Password" required>
        <button type="submit" name="login_organiser">Login</button>
      </form>
    </div>
  </div>

  <script>
    // Tabs
    const tabs = document.querySelectorAll(".tab-btn");
    const forms = document.querySelectorAll("form");
    tabs.forEach(tab => {
      tab.addEventListener("click", () => {
        tabs.forEach(t => t.classList.remove("active"));
        forms.forEach(f => f.classList.remove("active"));
        tab.classList.add("active");
        document.querySelector(tab.dataset.target).classList.add("active");
      });
    });

    // Modal
    const modal = document.getElementById("loginModal");
    document.getElementById("openLogin").onclick = () => modal.style.display = "flex";
    document.getElementById("closeModal").onclick = () => modal.style.display = "none";
    window.onclick = e => { if (e.target === modal) modal.style.display = "none"; };
  </script>
</body>
</html>
