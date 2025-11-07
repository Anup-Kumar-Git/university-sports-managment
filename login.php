<?php include 'db_connect.php'; ?>
<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - UNISPORTS</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<!-- ✅ SAME HEADER AS INDEX -->
<header>
    <nav>
        <a href="index.php">Home</a>
        <a href="register.php">Register</a>
        <a href="login.php">Login</a>
        <a href="announcement.php">Announcements</a>
    </nav>
</header>

<h2>Organiser Login</h2>

<form method="POST">
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button type="submit" name="login">Login</button>
</form>

<?php
if(isset($_POST['login'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    $result = mysqli_query($conn, "SELECT * FROM organisers WHERE email='$email'");
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['organiser_id'] = $user['id'];
        header("Location: announcement.php");
    } else {
        echo "<script>alert('Invalid Email or Password');</script>";
    }
}
?>

</body>
</html>
