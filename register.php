<?php
include 'db_connect.php';
session_start();

function clean($v) { return trim($v); }

$allowed_image_types = ['image/jpeg','image/png','image/webp','image/gif'];

/* ------------------------------
   Handle organiser registration
   ------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_type']) && $_POST['register_type'] === 'organiser') {
    $name = clean($_POST['org_name'] ?? '');
    $email = clean($_POST['org_email'] ?? '');
    $phone = clean($_POST['org_phone'] ?? '');
    $password = $_POST['org_password'] ?? '';

    if (!$name || !$email || !$password) {
        echo "<script>alert('Please fill all required organiser fields.'); window.location.href='register.php';</script>";
        exit;
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // ✅ Insert organiser WITHOUT event_name, description, event_photo
    $stmt = $conn->prepare("INSERT INTO organisers (name, email, phone, password) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ssss", $name, $email, $phone, $password_hash);
        if ($stmt->execute()) {
            echo "<script>alert('Organiser registered successfully!'); window.location.href='register.php';</script>";
            exit;
        } else {
            echo "<script>alert('Registration failed: ".htmlspecialchars($stmt->error)."');</script>";
        }
        $stmt->close();
    }
}

/* ------------------------------
   Handle organiser login
   ------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_organiser'])) {
    $email = clean($_POST['login_email'] ?? '');
    $password = $_POST['login_password'] ?? '';

    $stmt = $conn->prepare("SELECT id, name, password FROM organisers WHERE email = ?");
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['organiser_id'] = $user['id'];
                $_SESSION['organiser_name'] = $user['name'];
                header("Location: organiser_dashboard.php");
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
  <title>Organiser | KHELOGRAM</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    body { background: #f8fafc; font-family: 'Kanit', sans-serif; }
    .register_section { display: flex; justify-content: center; padding: 70px 20px; }
    .register_container {
      background: white; width: 90%; max-width: 750px; padding: 40px;
      border-radius: 16px; box-shadow: 0 6px 20px rgba(0,0,0,0.1);
    }
    h1 { text-align: center; color: #00274d; font-size: 28px; margin-bottom: 8px; }
    .subtitle { text-align: center; color: #555; margin-bottom: 20px; }
    .switch-buttons { display: flex; justify-content: center; margin-bottom: 25px; }
    .switch-buttons button {
      flex: 1; max-width: 180px; margin: 0 10px; padding: 12px;
      border-radius: 8px; border: none; background: #ddd; cursor: pointer;
    }
    .switch-buttons button.active { background: #00274d; color: white; font-weight: bold; }
    form { display: none; }
    form.active { display: block; animation: fadeIn 0.3s ease; }
    input, textarea {
      width: 100%; padding: 10px; margin-bottom: 15px;
      border-radius: 8px; border: 1px solid #ccc;
    }
    button[type="submit"] {
      width: 100%; padding: 12px; background: #00274d; color: white;
      border-radius: 8px; cursor: pointer;
    }
    button[type="submit"]:hover { background: #004080; }
    @keyframes fadeIn { from {opacity:0;} to {opacity:1;} }
  </style>
</head>
<body>

<header>
    <div class="navigation">
      <nav class="nav_bar">
        <a href="announcements.php">Announcements</a>
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
        <a href="index.html">Home</a>
      </nav>
    </div>
</header>

<main>
  <section class="register_section">
    <div class="register_container">
      <h1>Organiser Portal</h1>
      <p class="subtitle">Register or Login to manage your sports events</p>

      <div class="switch-buttons">
        <button id="showRegister" class="active">Register</button>
        <button id="showLogin">Login</button>
      </div>

      <!-- Register Form -->
      <form id="registerForm" class="active" method="POST">
        <input type="hidden" name="register_type" value="organiser">
        <label>Full Name</label>
        <input type="text" name="org_name" required>

        <label>Email</label>
        <input type="email" name="org_email" required>

        <label>Phone</label>
        <input type="tel" name="org_phone" required>

        <label>Password</label>
        <input type="password" name="org_password" required>

        <!-- ✅ Removed Fields -->
        <!-- Sports Name -->
        <!-- Sports Description -->
        <!-- Upload Photo -->

        <button type="submit">Register</button>
      </form>

      <!-- Login Form -->
      <form id="loginForm" method="POST">
        <label>Email</label>
        <input type="email" name="login_email" required>

        <label>Password</label>
        <input type="password" name="login_password" required>

        <button type="submit" name="login_organiser">Login</button>
      </form>
    </div>
  </section>
</main>

<script>
const registerBtn = document.getElementById('showRegister');
const loginBtn = document.getElementById('showLogin');
const registerForm = document.getElementById('registerForm');
const loginForm = document.getElementById('loginForm');

registerBtn.addEventListener('click', () => {
  registerBtn.classList.add('active');
  loginBtn.classList.remove('active');
  registerForm.classList.add('active');
  loginForm.classList.remove('active');
});

loginBtn.addEventListener('click', () => {
  loginBtn.classList.add('active');
  registerBtn.classList.remove('active');
  loginForm.classList.add('active');
  registerForm.classList.remove('active');
});
</script>

</body>
</html>
