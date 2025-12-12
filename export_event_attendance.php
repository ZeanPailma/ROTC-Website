<?php
session_start();

$host = 'localhost';
$db   = 'ro_db';
$user = 'root';
$pass = '';
$port = 4306;

if (!isset($_SESSION['user'])) {
    die("Unauthorized");
}

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("DB error: " . $e->getMessage());
}

// Get event ID from URL
$event_id = $_GET['event_id'] ?? 0;

if (!$event_id) {
    die("Event ID is required");
}

// Validate event
$event_stmt = $pdo->prepare("SELECT title, event_date FROM events WHERE id = ?");
$event_stmt->execute([$event_id]);
$event = $event_stmt->fetch();

if (!$event) {
    die("Event not found");
}

// Get attendance for this specific event (only scanned students)
$sql = "SELECT s.student_number, s.name, s.course, s.college, 
               a.time_in, a.time_out,
               CASE 
                   WHEN a.time_in IS NOT NULL AND a.time_out IS NOT NULL THEN 'Complete'
                   WHEN a.time_in IS NOT NULL THEN 'Time In Only'
                   WHEN a.time_out IS NOT NULL THEN 'Time Out Only'
                   ELSE 'No Data'
               END as status
        FROM students s
        INNER JOIN attendance a ON s.id = a.student_id
        WHERE a.event_id = ?
        ORDER BY s.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$event_id]);
$attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare filename with event details
$safe_title = preg_replace('/[^a-zA-Z0-9]/', '_', $event['title']);
$filename = "attendance_" . $safe_title . "_" . $event['event_date'] . ".csv";

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Open output stream
$output = fopen('php://output', 'w');

// Write CSV header with event info
fputcsv($output, ['MMSU-ROTCU Attendance Report']);
fputcsv($output, ['Event: ' . $event['title']]);
fputcsv($output, ['Date: ' . $event['event_date']]);
fputcsv($output, []); // Empty row

// Write column headers
fputcsv($output, ['Student Number', 'Name', 'Course', 'College', 'Time In', 'Time Out', 'Status']);

// Write attendance data
if (count($attendance) > 0) {
    foreach ($attendance as $row) {
        fputcsv($output, [
            $row['student_number'],
            $row['name'],
            $row['course'],
            $row['college'],
            $row['time_in'] ?: 'Not Recorded',
            $row['time_out'] ?: 'Not Recorded',
            $row['status']
        ]);
    }
} else {
    fputcsv($output, ['No attendance records found for this event']);
}


fclose($output);
exit;