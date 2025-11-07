<?php
session_start();
if (!isset($_SESSION['organiser_id'])) {
    header("Location: register.php");
    exit;
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Dashboard</title><link rel="stylesheet" href="styles.css"></head>
<body>
<?php /* reuse header markup or keep same header HTML here if desired */ ?>
<main style="max-width:1000px;margin:40px auto;padding:20px;">
  <h2>Welcome, <?php echo htmlspecialchars($_SESSION['organiser_name'], ENT_QUOTES); ?></h2>
  <p>This is your organiser dashboard (create events, view participants — to be implemented).</p>
  <p><a href="logout.php">Logout</a></p>
</main>
</body>
</html>
