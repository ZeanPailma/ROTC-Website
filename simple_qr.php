[file name]: simple_qr.php
[file content begin]
<?php
// Simple QR generation using Google Charts API
session_start();

$host = 'localhost';
$db = 'ro_db';
$user = 'root';
$pass = '';
$port = 4306;
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;port=$port;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed");
}

if (isset($_GET['student_id'])) {
    $student_id = $_GET['student_id'];
    
    $stmt = $pdo->prepare("SELECT student_number, name FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    
    if ($student) {
        // Create SIMPLE pipe-separated data (compatible format)
        $qrData = "STUDENT_ID:" . $student_id . "|STUDENT_NUM:" . $student['student_number'] . "|NAME:" . $student['name'];
        
        // Generate QR code using Google Charts API
        $qrUrl = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=" . 
                 urlencode($qrData) . "&choe=UTF-8";
        
        // Display QR code
        echo "<!DOCTYPE html>
        <html>
        <head>
            <title>QR Code for {$student['name']}</title>
            <style>
                body { text-align: center; padding: 50px; font-family: Arial; }
                .qr-container { margin: 20px auto; }
                .info { margin-top: 20px; }
            </style>
        </head>
        <body>
            <h2>QR Code for {$student['name']}</h2>
            <div class='qr-container'>
                <img src='{$qrUrl}' alt='QR Code'>
            </div>
            <div class='info'>
                <p><strong>Student Number:</strong> {$student['student_number']}</p>
                <p><strong>Student ID:</strong> {$student_id}</p>
                <p>Scan this QR code with the attendance scanner.</p>
                <button onclick='window.print()'>Print QR Code</button>
                <button onclick='window.close()'>Close</button>
            </div>
        </body>
        </html>";
        exit;
    }
}

echo "Student not found";
