<?php
session_start();

$host = 'localhost';
$db   = 'ro_db';
$user = 'root';
$pass = '';
$port = 4306;

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;port=$port;charset=utf8", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get upcoming events only
$today = date("Y-m-d");
$stmt = $pdo->prepare("SELECT id, title, event_date FROM events WHERE event_date >= ? ORDER BY event_date ASC");
$stmt->execute([$today]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selected_event = $_GET['event_id'] ?? null;
$students = [];

if ($selected_event) {
    // MODIFIED: Show only students who have been scanned (have attendance records)
    $sql = "SELECT s.id AS student_id, s.student_number, s.name, s.course, s.college, a.time_in, a.time_out
            FROM students s
            INNER JOIN attendance a ON s.id = a.student_id
            WHERE a.event_id = ?
            ORDER BY s.name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$selected_event]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance Checker</title>
    <link rel="stylesheet" href="css/roster.css">
    <style>
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%;
                 background:rgba(0,0,0,0.5); justify-content:center; align-items:center; }
        .modal-content { background:#fff; padding:20px; border-radius:8px; width:300px; }
        table { width:100%; border-collapse:collapse; margin-top:20px; }
        th, td { border:1px solid #ccc; padding:8px; text-align:center; }
        th { cursor:pointer; }
        .attendance-summary {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #007bff;
        }
        /* Export Dropdown Styles */
        .export-section {
            display: flex;
            justify-content: flex-end;
            margin: 15px 0;
            padding: 10px 0;
        }
        .dropdown {
            position: relative;
            display: inline-block;
        }
        .btn-export {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-export:hover {
            background: #218838;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 250px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1000;
            border-radius: 5px;
            overflow: hidden;
        }
        .dropdown-content a {
            color: #333;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }
        .dropdown-content a:hover {
            background-color: #f8f9fa;
        }
        .dropdown:hover .dropdown-content {
            display: block;
        }
        .dropdown-divider {
            height: 1px;
            background: #dee2e6;
            margin: 5px 0;
        }
        /* Print styles */
        @media print {
            .export-section, .dropdown {
                display: none !important;
            }
        }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="logo">MMSU-ROTCU</div>
    <ul class="menu">
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="roster.php">Roster of Cadets</a></li>
        <li><a href="rs.php">Reports and Schedule</a></li>
        <li class="active"><a href="att_check.php">Attendance Checker</a></li>
        <li><a href="backend/logout.php">Logout</a></li>
    </ul>
</div>

<div class="main-content">
    <div class="topbar">
        <div class="toggle-btn">
            <img src="css/images/logo.jpg" alt="logo" height="70">
            Attendance Checker
        </div>
        <div class="user-info">
            <?php echo "Welcome Back, " . htmlspecialchars($_SESSION['user']); ?>
        </div>
    </div>

    <h2>Select Event</h2>
    <form method="get">
        <select name="event_id" required>
            <option value="">-- Choose Event --</option>
            <?php foreach ($events as $ev): ?>
                <option value="<?= $ev['id'] ?>" <?= $selected_event == $ev['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($ev['title']) ?> (<?= $ev['event_date'] ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Check</button>
    </form>

    <?php if ($selected_event): ?>
        <!-- Export Section -->
        <div class="export-section">
            <div class="dropdown">
                <button class="btn-export">
                    <span></span> Export ▼
                </button>
                <div class="dropdown-content">
                    <a href="backend/export_event_attendance.php?event_id=<?= $selected_event ?>">
                         Export Current Event
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="backend/export_overall_attendance.php">
                         Export Overall Attendance
                    </a>
                </div>
            </div>
        </div>

        <!-- Show attendance summary -->
        <div class="attendance-summary">
            <h4>Attendance Summary</h4>
            <?php
            $total_scanned = count($students);
            $total_present = 0;
            $total_time_in = 0;
            $total_time_out = 0;
            
            foreach ($students as $stu) {
                if ($stu['time_in']) $total_time_in++;
                if ($stu['time_out']) $total_time_out++;
                if ($stu['time_in'] && $stu['time_out']) $total_present++;
            }
            ?>
            <p><strong>With Time In:</strong> <?php echo $total_time_in; ?> | 
               <strong>With Time Out:</strong> <?php echo $total_time_out; ?> | 
               <strong>Complete Attendance:</strong> <?php echo $total_present; ?>
            </p>
        </div>
        
        <!-- Search bar -->
        <input type="text" id="searchInput" placeholder="Search student..." onkeyup="filterTable()">
        
        <!-- Attendance table -->
        <table id="attendanceTable">
            <thead>
                <tr>
                    <th onclick="sortTable(0)">Student No.</th>
                    <th onclick="sortTable(1)">Name</th>
                    <th onclick="sortTable(2)">Course</th>
                    <th onclick="sortTable(3)">College</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($students) > 0): ?>
                <?php foreach ($students as $stu): ?>
                    <tr>
                        <td><?= htmlspecialchars($stu['student_number']) ?></td>
                        <td><?= htmlspecialchars($stu['name']) ?></td>
                        <td><?= htmlspecialchars($stu['course']) ?></td>
                        <td><?= htmlspecialchars($stu['college']) ?></td>
                        <td>
                            <span id="timein-<?= $stu['student_id'] ?>">
                                <?= $stu['time_in'] ? htmlspecialchars($stu['time_in']) : '-' ?>
                            </span>
                            <?php if (!$stu['time_in']): ?>
                                <button onclick="markAttendance(<?= $stu['student_id'] ?>, <?= $selected_event ?>, 'in', this)">Time In</button>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span id="timeout-<?= $stu['student_id'] ?>">
                                <?= $stu['time_out'] ? htmlspecialchars($stu['time_out']) : '-' ?>
                            </span>
                            <?php if (!$stu['time_out']): ?>
                                <button onclick="markAttendance(<?= $stu['student_id'] ?>, <?= $selected_event ?>, 'out', this)">Time Out</button>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button onclick="openEditModal(<?= $stu['student_id'] ?>, <?= $selected_event ?>, '<?= $stu['time_in'] ?>', '<?= $stu['time_out'] ?>')">
                                Edit
                            </button>
                            <button onclick="deleteAttendance(<?= $stu['student_id'] ?>, <?= $selected_event ?>)">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 20px;">
                        <p style="color: #666;">No students have been scanned for this event yet.</p>
                        <p>Use the QR Scanner to start marking attendance.</p>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
  <div class="modal-content">
    <h3>Edit Attendance</h3>
    <form id="editForm">
      <input type="hidden" id="editStudentId">
      <input type="hidden" id="editEventId">
      <label>Time In:</label>
      <input type="time" id="editTimeIn"><br><br>
      <label>Time Out:</label>
      <input type="time" id="editTimeOut"><br><br>
      <button type="button" onclick="saveEdit()">Save</button>
      <button type="button" onclick="closeEditModal()">Cancel</button>
    </form>
  </div>
</div>

<script>
function filterTable() {
    let input = document.getElementById("searchInput").value.toLowerCase();
    let rows = document.querySelectorAll("#attendanceTable tbody tr");
    rows.forEach(r => {
        let text = r.innerText.toLowerCase();
        r.style.display = text.includes(input) ? "" : "none";
    });
}

function sortTable(n) {
    let table = document.getElementById("attendanceTable");
    let rows = Array.from(table.rows).slice(1);
    let asc = table.getAttribute("data-sort") != "asc";
    rows.sort((a,b) => a.cells[n].innerText.localeCompare(b.cells[n].innerText));
    if (!asc) rows.reverse();
    rows.forEach(r => table.tBodies[0].appendChild(r));
    table.setAttribute("data-sort", asc ? "asc" : "desc");
}

function markAttendance(studentId, eventId, type, btn) {
    // Get current time from server first to ensure accuracy
    const currentTime = new Date().toLocaleTimeString('en-US', { 
        hour12: false,
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    
    // Show immediate feedback while waiting for server response
    if (type === "in") {
        document.getElementById("timein-" + studentId).innerText = currentTime;
    } else {
        document.getElementById("timeout-" + studentId).innerText = currentTime;
    }
    
    // Send to server
    fetch("backend/save_attendance.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "student_id=" + studentId + "&event_id=" + eventId + "&type=" + type
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Update with server time (more accurate)
            if (type === "in") {
                document.getElementById("timein-" + studentId).innerText = data.time;
            } else {
                document.getElementById("timeout-" + studentId).innerText = data.time;
            }
            btn.remove(); // Remove the button
        } else {
            alert(data.message);
            // Revert if error
            if (type === "in") {
                document.getElementById("timein-" + studentId).innerText = '-';
            } else {
                document.getElementById("timeout-" + studentId).innerText = '-';
            }
        }
    })
    .catch(error => {
        console.error("Error:", error);
        alert("Network error. Please try again.");
        // Revert on network error
        if (type === "in") {
            document.getElementById("timein-" + studentId).innerText = '-';
        } else {
            document.getElementById("timeout-" + studentId).innerText = '-';
        }
    });
}

function openEditModal(studentId, eventId, timeIn, timeOut) {
    document.getElementById("editStudentId").value = studentId;
    document.getElementById("editEventId").value = eventId;
    document.getElementById("editTimeIn").value = timeIn && timeIn !== '-' ? timeIn : "";
    document.getElementById("editTimeOut").value = timeOut && timeOut !== '-' ? timeOut : "";
    document.getElementById("editModal").style.display = "flex";
}

function closeEditModal() {
    document.getElementById("editModal").style.display = "none";
}

function saveEdit() {
    const studentId = document.getElementById("editStudentId").value;
    const eventId = document.getElementById("editEventId").value;
    const timeIn = document.getElementById("editTimeIn").value;
    const timeOut = document.getElementById("editTimeOut").value;

    fetch("backend/edit_attendance.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "student_id=" + studentId + "&event_id=" + eventId + "&time_in=" + timeIn + "&time_out=" + timeOut
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("Attendance updated!");
            location.reload();
        } else {
            alert("Error: " + data.message);
        }
    });
}

function deleteAttendance(studentId, eventId) {
    if (!confirm("Are you sure you want to delete this student's attendance?")) return;

    fetch("backend/delete_attendance.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "student_id=" + studentId + "&event_id=" + eventId
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("Attendance deleted!");
            location.reload();
        } else {
            alert("Error: " + data.message);
        }
    });
}
</script>
</body>
</html>