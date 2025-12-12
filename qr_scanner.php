<?php
session_start();

$host = 'localhost';
$db = 'ro_db';
$user = 'root';
$pass = '';
$port = 4306;

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Connect to database for events dropdown
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;port=$port;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get upcoming events
    $today = date("Y-m-d");
    $stmt = $pdo->prepare("SELECT id, title, event_date FROM events WHERE event_date >= ? ORDER BY event_date ASC");
    $stmt->execute([$today]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $events = [];
    $db_error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Scanner</title>
    <link rel="stylesheet" href="css/roster.css">
    <link rel="icon" href="css/images/logo.jpg">
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
        #qr-reader {
            width: 100%;
            max-width: 600px;
            margin: 20px auto;
        }
        #scanned-result {
            background: #f0f0f0;
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .attendance-options {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .student-info {
            padding: 15px;
            background: #e8f4fd;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .event-select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }
        .btn-link {
            padding: 8px 15px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn-link:hover {
            background: #0056b3;
        }
        .btn-rescan {
            background: #6c757d;
        }
        .btn-rescan:hover {
            background: #5a6268;
        }
        #camera-error {
            color: red;
            background: #ffe6e6;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            display: none;
        }
        /* Event Selection at Top */
        .event-selection-top {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 2px solid #4a90e2;
        }
        .event-selection-top h3 {
            margin-top: 0;
            color: #333;
        }
        .selected-event-display {
            background: #e8f4fd;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
            border-left: 4px solid #4a90e2;
            font-weight: bold;
            display: none;
        }
        .selected-event-display.active {
            display: block;
        }
        /* Time Selection Mode */
        .time-mode-selector {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 2px solid #007bff;
        }
        .mode-options {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 15px;
        }
        .mode-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .mode-btn.active {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .mode-time-in {
            background: #28a745;
            color: white;
        }
        .mode-time-in:hover {
            background: #218838;
        }
        .mode-time-in.active {
            background: #1e7e34;
            border: 3px solid #155724;
        }
        .mode-time-out {
            background: #17a2b8;
            color: white;
        }
        .mode-time-out:hover {
            background: #138496;
        }
        .mode-time-out.active {
            background: #117a8b;
            border: 3px solid #0c5460;
        }
        .current-mode {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin: 10px 0;
            font-weight: bold;
            font-size: 18px;
        }
        .scanner-status {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            margin: 15px 0;
            font-weight: bold;
        }
        .time-in-status {
            color: #28a745;
            background: #d4edda;
            border: 2px solid #c3e6cb;
        }
        .time-out-status {
            color: #17a2b8;
            background: #d1ecf1;
            border: 2px solid #bee5eb;
        }
        .scan-counter {
            text-align: center;
            font-size: 14px;
            color: #666;
            margin-top: 10px;
        }
        .scanner-ready-indicator {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            margin: 15px 0;
            border: 2px dashed #ccc;
        }
        .scanner-ready-indicator.ready {
            border: 2px solid #28a745;
            background: #d4edda;
            color: #155724;
        }
        .scanner-ready-indicator.ready.time-in-status {
            border: 2px solid #28a745;
            background: #d4edda;
            color: #155724;
        }
        .scanner-ready-indicator.ready.time-out-status {
            border: 2px solid #17a2b8;
            background: #d1ecf1;
            color: #0c5460;
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
            <li><a href="att_check.php">Attendance</a></li>
            <li class="active"><a href="qr_scanner.php">QR Scanner</a></li>
            <li><a href="backend/logout.php">Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="topbar">
            <div class="toggle-btn">
                <img src="css/images/logo.jpg" alt="logo" height="70">
                QR Code Scanner
            </div>
            <div class="user-info">
                <?php echo "Welcome Back, " . htmlspecialchars($_SESSION['user']); ?>
            </div>
        </div>

        <div class="container">
            <!-- Event Selection at Top -->
            <div class="event-selection-top">
                <h3>Select Event for Attendance</h3>
                
                <select id="event-select-top" class="event-select" onchange="updateSelectedEvent()">
                    <option value="">-- Select Event --</option>
                    <?php
                    if (!empty($events)) {
                        foreach ($events as $event) {
                            $eventDate = date("M d, Y", strtotime($event['event_date']));
                            echo "<option value='{$event['id']}'>{$event['title']} ({$eventDate})</option>";
                        }
                    } else {
                        echo "<option value=''>No upcoming events</option>";
                        if (isset($db_error)) {
                            echo "<option value=''>DB Error: Check connection</option>";
                        }
                    }
                    ?>
                </select>
                
                <div id="selected-event-display" class="selected-event-display">
                    Selected Event: <span id="selected-event-name">None</span>
                </div>
            </div>
            
            <!-- Time Mode Selection -->
            <div class="time-mode-selector">
                <h3> Select Attendance Mode</h3>
                <p>Choose whether to mark all scans as "Time In" or "Time Out"</p>
                
                <div class="mode-options">
                    <button class="mode-btn mode-time-in active" onclick="setTimeMode('in')">
                        <span></span> Time In Mode
                    </button>
                    <button class="mode-btn mode-time-out" onclick="setTimeMode('out')">
                        <span></span> Time Out Mode
                    </button>
                </div>
                
                <div class="current-mode" id="currentModeDisplay">
                    Current Mode: <span style="color:#28a745;">TIME IN</span>
                    <p style="font-size:14px;color:#666;margin-top:5px;">
                        All scans will be automatically marked as Time In
                    </p>
                </div>
                
            </div>
            
            <!-- Scanner Ready Indicator -->
            <div class="scanner-ready-indicator" id="scannerReadyIndicator">
                <p id="scannerStatusText">Waiting for event selection...</p>
            </div>
            
            <!-- Camera Error Display -->
            <div id="camera-error"></div>
            
            <!-- QR Scanner -->
            <div id="qr-reader"></div>
            
            <!-- Scan Counter -->
            <div class="scan-counter">
                Scans today: <span id="scanCount">0</span>
            </div>
            
            <!-- Scan Results -->
            <div id="scanned-result">
                <h3>Scan Result</h3>
                <p id="result-text">Select an event above and scan a QR code to begin</p>
                
                <div id="student-details" class="student-info" style="display:none;">
                    <h4>👤 Student Information</h4>
                    <p><strong>Name:</strong> <span id="student-name"></span></p>
                    <p><strong>Student Number:</strong> <span id="student-number"></span></p>
                    <p><strong>ID:</strong> <span id="student-id"></span></p>
                </div>
                
                <div id="attendance-options" class="attendance-options">
                    <h4> Confirm Attendance</h4>
                    <p><strong>Event:</strong> <span id="current-event-name">Not selected</span></p>
                    
                    <div style="margin-top: 15px;">
                        <button onclick="markAttendance()" class="btn-link" id="markButton" style="background:#28a745;">
                             ✅ Mark Time In
                        </button>
                        <button onclick="restartScanner()" class="btn-link btn-rescan">🔄 Scan Another</button>
                    </div>
                    
                    <div id="attendance-result" style="margin-top: 15px;"></div>
                </div>
            </div>
            
            <!-- Instructions -->
            <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h4>📋 Instructions:</h4>
                <ol>
                    <li><strong>Select an event</strong> from the dropdown at the top</li>
                    <li>Choose <strong>"Time In Mode"</strong> or <strong>"Time Out Mode"</strong></li>
                    <li>Point camera at student's QR code</li>
                    <li>Attendance will be <strong>automatically marked</strong> for the selected event</li>
                    <li>Attendance will automatically appear in Attendance Checker</li>
                </ol>
            </div>
        </div>
    </div>

    <script>
        let currentStudentId = null;
        let currentStudentData = null;
        let html5QrCode = null;
        let currentTimeMode = 'in'; // 'in' or 'out'
        let scanCount = 0;
        let autoMarkEnabled = true;
        let currentEventId = null;
        let currentEventName = null;
        
        // Update selected event display
        function updateSelectedEvent() {
            const eventSelect = document.getElementById('event-select-top');
            const selectedDisplay = document.getElementById('selected-event-display');
            const eventNameDisplay = document.getElementById('selected-event-name');
            const scannerIndicator = document.getElementById('scannerReadyIndicator');
            const scannerStatusText = document.getElementById('scannerStatusText');
            
            currentEventId = eventSelect.value;
            currentEventName = eventSelect.options[eventSelect.selectedIndex].text;
            
            if (currentEventId) {
                selectedDisplay.classList.add('active');
                eventNameDisplay.textContent = currentEventName;
                scannerIndicator.classList.add('ready');
                
                // Update scanner status based on current mode
                if (currentTimeMode === 'in') {
                    scannerIndicator.classList.add('time-in-status');
                    scannerIndicator.classList.remove('time-out-status');
                    scannerStatusText.textContent = "Ready to scan - TIME IN MODE";
                } else {
                    scannerIndicator.classList.add('time-out-status');
                    scannerIndicator.classList.remove('time-in-status');
                    scannerStatusText.textContent = "Ready to scan - TIME OUT MODE";
                }
                
                // Update event name in attendance options
                document.getElementById('current-event-name').textContent = currentEventName;
                
                // Enable scanner if not already started
                if (!html5QrCode && !currentStudentId) {
                    initScanner();
                }
            } else {
                selectedDisplay.classList.remove('active');
                scannerIndicator.classList.remove('ready');
                scannerIndicator.classList.remove('time-in-status');
                scannerIndicator.classList.remove('time-out-status');
                scannerStatusText.textContent = "Waiting for event selection...";
                
                // Stop scanner if no event selected
                if (html5QrCode && html5QrCode.isScanning) {
                    html5QrCode.stop();
                }
            }
        }
        
        // Set time mode
        function setTimeMode(mode) {
            currentTimeMode = mode;
            
            // Update UI
            const timeInBtn = document.querySelector('.mode-time-in');
            const timeOutBtn = document.querySelector('.mode-time-out');
            const modeDisplay = document.getElementById('currentModeDisplay');
            const markButton = document.getElementById('markButton');
            const scannerIndicator = document.getElementById('scannerReadyIndicator');
            const scannerStatusText = document.getElementById('scannerStatusText');
            
            if (mode === 'in') {
                timeInBtn.classList.add('active');
                timeOutBtn.classList.remove('active');
                
                // Update scanner status text
                scannerStatusText.innerHTML = "Ready to scan - TIME IN MODE";
                scannerIndicator.classList.remove('time-out-status');
                scannerIndicator.classList.add('time-in-status');
                
                // Update mode display
                modeDisplay.innerHTML = `
                    Current Mode: <span style="color:#28a745;"> TIME IN</span>
                    <p style="font-size:14px;color:#666;margin-top:5px;">
                        All scans will be automatically marked as Time In
                    </p>
                `;
                
                // Update mark button if it exists
                if (markButton) {
                    markButton.innerHTML = " ✅ Mark Time In";
                    markButton.style.background = "#28a745";
                }
            } else {
                timeInBtn.classList.remove('active');
                timeOutBtn.classList.add('active');
                
                // Update scanner status text
                scannerStatusText.innerHTML = "Ready to scan - TIME OUT MODE";
                scannerIndicator.classList.remove('time-in-status');
                scannerIndicator.classList.add('time-out-status');
                
                // Update mode display
                modeDisplay.innerHTML = `
                    Current Mode: <span style="color:#17a2b8;"> TIME OUT</span>
                    <p style="font-size:14px;color:#666;margin-top:5px;">
                        All scans will be automatically marked as Time Out
                    </p>
                `;
                
                // Update mark button if it exists
                if (markButton) {
                    markButton.innerHTML = " ✅ Mark Time Out";
                    markButton.style.background = "#17a2b8";
                }
            }
            
            // Update local storage
            localStorage.setItem('qrScannerMode', mode);
            
            // If we have a student scanned, update the button
            if (currentStudentId) {
                updateMarkButton();
            }
        }
        
        // Initialize QR Scanner
        function initScanner() {
            // Check if event is selected
            if (!currentEventId) {
                alert("Please select an event first!");
                return;
            }
            
            html5QrCode = new Html5Qrcode("qr-reader");
            
            const config = { 
                fps: 10, 
                qrbox: { width: 250, height: 250 } 
            };
            
            // Start scanner
            html5QrCode.start(
                { facingMode: "environment" },
                config,
                onScanSuccess,
                onScanError
            ).then(() => {
                // Update scanner status
                const scannerIndicator = document.getElementById('scannerReadyIndicator');
                scannerIndicator.classList.add('ready');
                
                if (currentTimeMode === 'in') {
                    scannerIndicator.classList.add('time-in-status');
                    scannerIndicator.classList.remove('time-out-status');
                    document.getElementById('scannerStatusText').textContent = "Scanner active - TIME IN MODE";
                } else {
                    scannerIndicator.classList.add('time-out-status');
                    scannerIndicator.classList.remove('time-in-status');
                    document.getElementById('scannerStatusText').textContent = "Scanner active - TIME OUT MODE";
                }
            }).catch(err => {
                console.error("Camera error:", err);
                document.getElementById("camera-error").innerHTML = 
                    "<strong>📷 Camera Error:</strong> " + err.message + "<br>" +
                    "Please ensure camera permissions are granted and try again.";
                document.getElementById("camera-error").style.display = "block";
            });
        }
        
        function parseQRData(decodedText) {
            // Clean the text
            decodedText = decodedText.trim();
            
            // Try different formats
            try {
                // Format 1: JSON format
                if (decodedText.startsWith('{') && decodedText.endsWith('}')) {
                    return JSON.parse(decodedText);
                }
                
                // Format 2: Simple pipe format from generate_qr.php
                if (decodedText.includes('STUDENT_ID:')) {
                    const data = {};
                    const parts = decodedText.split('|');
                    parts.forEach(part => {
                        const [key, value] = part.split(':');
                        if (key && value !== undefined) {
                            // Convert to lowercase and replace spaces with underscores
                            const cleanKey = key.trim().toLowerCase().replace(/\s+/g, '_');
                            data[cleanKey] = value.trim();
                        }
                    });
                    return data;
                }
                
                // Format 3: Old pipe format (ROTC|ID|NUMBER|NAME)
                if (decodedText.includes('|')) {
                    const parts = decodedText.split('|');
                    if (parts.length >= 3) {
                        return {
                            student_id: parts[1] || '',
                            student_number: parts[2] || '',
                            name: parts[3] ? decodeURIComponent(parts[3]) : ''
                        };
                    }
                }
                
                // Format 4: Just student ID
                if (!isNaN(decodedText)) {
                    return {
                        student_id: decodedText,
                        student_number: '',
                        name: ''
                    };
                }
                
                console.log("Unrecognized format:", decodedText);
                return null;
            } catch (e) {
                console.error("Error parsing QR data:", e);
                return null;
            }
        }
        
        function onScanSuccess(decodedText, decodedResult) {
            // Check if event is selected
            if (!currentEventId) {
                alert("Please select an event first!");
                restartScanner();
                return;
            }
            
            // Stop scanning after successful scan
            if (html5QrCode) {
                html5QrCode.stop();
            }
            
            const studentData = parseQRData(decodedText);
            
            if (studentData && studentData.student_id) {
                currentStudentId = studentData.student_id;
                currentStudentData = studentData;
                
                // Display student info
                document.getElementById("result-text").innerHTML = 
                    `<strong style="color: green;">✅ QR Code Scanned Successfully!</strong>`;
                document.getElementById("student-name").textContent = studentData.name || "(Name not in QR)";
                document.getElementById("student-number").textContent = studentData.student_number || studentData.student_num || "(Number not in QR)";
                document.getElementById("student-id").textContent = studentData.student_id;
                document.getElementById("student-details").style.display = "block";
                document.getElementById("attendance-options").style.display = "block";
                
                // Update scan counter
                scanCount++;
                document.getElementById("scanCount").textContent = scanCount;
                
                // Update the mark button based on current mode
                updateMarkButton();
                
                // Auto-mark attendance after short delay
                setTimeout(() => {
                    markAttendance();
                }, 800); // Slightly longer delay to show student info
                
                // Vibrate if supported
                if (navigator.vibrate) {
                    navigator.vibrate(200);
                }
                
            } else {
                document.getElementById("result-text").innerHTML = 
                    `<strong style="color: red;">❌ Invalid QR Code format</strong><br>
                     <small>Scanned: ${decodedText.substring(0, 50)}...</small>`;
                // Restart scanner after 3 seconds
                setTimeout(restartScanner, 3000);
            }
        }
        
        function updateMarkButton() {
            const markButton = document.getElementById('markButton');
            if (!markButton) return;
            
            if (currentTimeMode === 'in') {
                markButton.innerHTML = "✅ Mark Time In";
                markButton.style.background = "#28a745";
            } else {
                markButton.innerHTML = "✅ Mark Time Out";
                markButton.style.background = "#17a2b8";
            }
        }
        
        function onScanError(error) {
            // Handle scan error - don't show to user unless it's critical
            console.warn("Scan error:", error);
        }
        
        function markAttendance() {
            if (!currentEventId) {
                alert("Please select an event first");
                return;
            }
            
            if (!currentStudentId) {
                alert("No student data available");
                return;
            }
            
            const resultDiv = document.getElementById("attendance-result");
            resultDiv.innerHTML = "<p>Processing attendance...</p>";
            
            // Show loading animation
            resultDiv.innerHTML = '<div style="text-align:center;"><div style="display:inline-block;width:20px;height:20px;border:3px solid #f3f3f3;border-top:3px solid #3498db;border-radius:50%;animation:spin 1s linear infinite;"></div><p>Processing...</p></div>';
            
            fetch("backend/save_attendance.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: `student_id=${currentStudentId}&event_id=${currentEventId}&type=${currentTimeMode}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div style="color: ${currentTimeMode === 'in' ? '#28a745' : '#17a2b8'}; font-weight: bold; padding: 15px; background: ${currentTimeMode === 'in' ? '#d4edda' : '#d1ecf1'}; border-radius: 5px;">
                            <div style="font-size: 24px;">✅</div>
                            <strong>Attendance Marked Successfully!</strong><br><br>
                            <strong>Time:</strong> ${data.time}<br>
                            <strong>Type:</strong> ${currentTimeMode === 'in' ? 'Time In' : 'Time Out'}<br>
                            <strong>Event:</strong> ${currentEventName}<br>
                            <strong>Student ID:</strong> ${currentStudentId}<br>
                            <strong>Name:</strong> ${currentStudentData.name || 'N/A'}<br><br>
                        </div>
                    `;
                    
                    // Show success message and restart scanner after 2 seconds
                    setTimeout(() => {
                        restartScanner();
                    }, 2000);
                    
                } else {
                    resultDiv.innerHTML = `
                        <div style="color: red; font-weight: bold; padding: 15px; background: #f8d7da; border-radius: 5px;">
                            <div style="font-size: 24px;">❌</div>
                            <strong>Error:</strong> ${data.message || "Failed to mark attendance"}
                        </div>
                    `;
                }
            })
            .catch(error => {
                resultDiv.innerHTML = `
                    <div style="color: red; font-weight: bold; padding: 15px; background: #f8d7da; border-radius: 5px;">
                        <div style="font-size: 24px;">❌</div>
                        <strong>Network Error:</strong> ${error.message}<br>
                        Please check your internet connection and try again.
                    </div>
                `;
            });
        }
        
        function restartScanner() {
            // Clear current data
            currentStudentId = null;
            currentStudentData = null;
            
            // Hide result sections
            document.getElementById("attendance-options").style.display = "none";
            document.getElementById("student-details").style.display = "none";
            document.getElementById("attendance-result").innerHTML = "";
            document.getElementById("result-text").innerHTML = "Select an event above and scan a QR code to begin";
            document.getElementById("camera-error").style.display = "none";
            
            // Stop any existing scanner
            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode.stop();
            }
            
            // Only restart scanner if event is selected
            if (currentEventId) {
                // Restart scanner after a short delay
                setTimeout(() => {
                    initScanner();
                }, 500);
            }
        }
        
        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                restartScanner();
            }
            if (e.key === '1') {
                setTimeMode('in');
            }
            if (e.key === '2') {
                setTimeMode('out');
            }
            if (e.key === 'r' || e.key === 'R') {
                restartScanner();
            }
            if (e.key === 'Enter' && currentStudentId) {
                markAttendance();
            }
        });
        
        // Initialize when page loads
        window.addEventListener('load', function() {
            // Load saved mode from local storage
            const savedMode = localStorage.getItem('qrScannerMode') || 'in';
            
            // Initialize the scanner indicator
            const scannerIndicator = document.getElementById('scannerReadyIndicator');
            if (savedMode === 'in') {
                scannerIndicator.classList.add('time-in-status');
            } else {
                scannerIndicator.classList.add('time-out-status');
            }
            
            // Set the mode (this will update the UI)
            setTimeMode(savedMode);
            
            // Auto-select today's event if available
            autoSelectTodayEvent();
            
            // Load scan count from local storage
            const today = new Date().toDateString();
            const savedDate = localStorage.getItem('scanDate');
            if (savedDate === today) {
                scanCount = parseInt(localStorage.getItem('scanCount')) || 0;
            } else {
                scanCount = 0;
                localStorage.setItem('scanDate', today);
            }
            document.getElementById("scanCount").textContent = scanCount;
            
            // Add CSS for spinner
            const style = document.createElement('style');
            style.textContent = `
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        });
        
        function autoSelectTodayEvent() {
            const today = new Date().toISOString().split('T')[0];
            const eventSelect = document.getElementById('event-select-top');
            const todayStr = new Date().toLocaleDateString('en-US', { 
                month: 'short', 
                day: '2-digit', 
                year: 'numeric' 
            });
            
            // Try to find an event for today
            for (let i = 0; i < eventSelect.options.length; i++) {
                const option = eventSelect.options[i];
                if (option.text.includes(todayStr)) {
                    eventSelect.value = option.value;
                    updateSelectedEvent();
                    break;
                }
            }
            
            // If no today's event, select the first upcoming event
            if (!eventSelect.value && eventSelect.options.length > 1) {
                eventSelect.selectedIndex = 1;
                updateSelectedEvent();
            }
        }
        
        // Save scan count when leaving page
        window.addEventListener('beforeunload', function() {
            localStorage.setItem('scanCount', scanCount);
        });
    </script>
</body>
</html>