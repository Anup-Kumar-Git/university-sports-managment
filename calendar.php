<?php
include 'db_connect.php';
session_start();

/* Fetch events with dates */
$events = [];
$res = $conn->query("SELECT id, event_name, description, event_date FROM events WHERE event_date IS NOT NULL");
while ($row = $res->fetch_assoc()) {
    $d = date('Y-m-d', strtotime($row['event_date']));
    if (!isset($events[$d])) $events[$d] = [];
    $events[$d][] = [
        'id' => (int)$row['id'],
        'name' => $row['event_name'],
        'desc' => $row['description']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Events Calendar - UNISPORTS</title>
<link rel="stylesheet" href="styles.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

<style>
body { font-family: 'Poppins', sans-serif; margin:0; background:#eef2f7; }

.calendar-container {
    width: 95%;
    max-width: 1100px;
    margin: 30px auto;
    background:#fff;
    border-radius:16px;
    box-shadow:0 8px 20px rgba(0,0,0,0.09);
    padding:20px;
}

.calendar-header {
    display:flex; justify-content:space-between; align-items:center;
    margin-bottom:10px;
}
.calendar-header h2 {
    margin:0; color:#0d3b5c;
}
.nav-btn {
    background:#0d3b5c; color:white; border:none;
    padding:8px 12px; border-radius:8px;
    cursor:pointer;
}
.nav-btn:hover { filter:brightness(1.1); }

.calendar-grid {
    display:grid;
    grid-template-columns:repeat(7,1fr);
    gap:8px;
}
.day-name {
    text-align:center; font-weight:600; padding:10px 0; color:#1e6b8a;
}

.cell {
    position:relative;
    min-height:110px;
    border:1px solid #dadde0;
    border-radius:10px;
    background:#fafafa;
    padding:8px;
}
.cell.blank {
    background:transparent; border:none;
}

.cell.has-event {
    background:#28a745 !important;
    border-color:#1e7e34 !important;
    color:white !important;
}

.date-number {
    font-size:14px; font-weight:600;
}

.event-name {
    display:block;
    margin-top:6px;
    color:white;
    font-size:13px;
    font-weight:600;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}

</style>
</head>

<body>

<!-- HEADER (Same as your site) -->
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
      <a href="register.php">Register</a>
    </nav>
  </div>
</header>

<div class="calendar-container">
    
    <div class="calendar-header">
        <button class="nav-btn" id="prevBtn">&#8592; Prev</button>
        <h2 id="monthLabel"></h2>
        <button class="nav-btn" id="nextBtn">Next &#8594;</button>
    </div>

    <!-- Day Names -->
    <div class="calendar-grid">
        <div class="day-name">Sun</div>
        <div class="day-name">Mon</div>
        <div class="day-name">Tue</div>
        <div class="day-name">Wed</div>
        <div class="day-name">Thu</div>
        <div class="day-name">Fri</div>
        <div class="day-name">Sat</div>
    </div>

    <!-- Calendar Dates -->
    <div class="calendar-grid" id="calendarGrid"></div>

</div>

<script>
const eventsByDate = <?php echo json_encode($events, JSON_UNESCAPED_UNICODE); ?>;

let viewYear, viewMonth;

const calendarGrid = document.getElementById("calendarGrid");
const monthLabel = document.getElementById("monthLabel");

function setMonth(y, m) {
    viewYear = y;
    viewMonth = m;
    drawCalendar();
}

function drawCalendar() {
    calendarGrid.innerHTML = "";

    const firstDay = new Date(viewYear, viewMonth, 1);
    const startDay = firstDay.getDay();
    const totalDays = new Date(viewYear, viewMonth + 1, 0).getDate();

    const monthNames = ["January","February","March","April","May","June","July","August","September",
        "October","November","December"];

    monthLabel.innerText = `${monthNames[viewMonth]} ${viewYear}`;

    // Blank cells before start
    for (let i = 0; i < startDay; i++) {
        const blank = document.createElement("div");
        blank.classList.add("cell", "blank");
        calendarGrid.appendChild(blank);
    }

    // Fill the days
    for (let d = 1; d <= totalDays; d++) {
        const cell = document.createElement("div");
        cell.classList.add("cell");

        const dateKey = formatKey(viewYear, viewMonth, d);

        const dayNumber = document.createElement("div");
        dayNumber.classList.add("date-number");
        dayNumber.innerText = d;
        cell.appendChild(dayNumber);

        if (eventsByDate[dateKey]) {
            cell.classList.add("has-event");

            eventsByDate[dateKey].forEach(ev => {
                const ename = document.createElement("span");
                ename.classList.add("event-name");
                ename.textContent = ev.name;
                cell.appendChild(ename);
            });
        }

        calendarGrid.appendChild(cell);
    }
}

document.getElementById("prevBtn").addEventListener("click", () => {
    let m = viewMonth - 1;
    let y = viewYear;
    if (m < 0) { m = 11; y--; }
    setMonth(y, m);
});

document.getElementById("nextBtn").addEventListener("click", () => {
    let m = viewMonth + 1;
    let y = viewYear;
    if (m > 11) { m = 0; y++; }
    setMonth(y, m);
});

function formatKey(y, m, d){
    return `${y}-${String(m+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
}

const today = new Date();
setMonth(today.getFullYear(), today.getMonth());
</script>

</body>
</html>
