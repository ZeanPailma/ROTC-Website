<?php
session_start();

$host = 'localhost';
$db = 'ro_db';
$user = 'root';
$pass = '';
$port = 4306;

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

if (isset($_GET['student_id'])) {
    $student_id = $_GET['student_id'];
    
    // Get student info
    $stmt = $pdo->prepare("SELECT student_number, name FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    
    if ($student) {
        // Create SIMPLE string data that can be easily parsed - This is the FIX
        $qrData = "STUDENT_ID:" . $student_id . "|STUDENT_NUM:" . $student['student_number'] . "|NAME:" . $student['name'];
        
        // Check if we want direct image or HTML page
        if (isset($_GET['direct']) && $_GET['direct'] == '1') {
            // Direct image output
            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . 
                     urlencode($qrData) . "&format=png&margin=10";
            header('Location: ' . $qrUrl);
            exit;
        }
        
        // HTML page with QR code
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>QR Code - <?php echo htmlspecialchars($student['name']); ?></title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                }
                
                body {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    padding: 20px;
                }
                
                .container {
                    background: white;
                    border-radius: 20px;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    overflow: hidden;
                    max-width: 400px;
                    width: 100%;
                    animation: slideUp 0.5s ease;
                }
                
                @keyframes slideUp {
                    from { transform: translateY(30px); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
                
                .header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 30px 20px;
                    text-align: center;
                }
                
                .header h1 {
                    font-size: 24px;
                    margin-bottom: 10px;
                }
                
                .header .logo {
                    font-size: 36px;
                    margin-bottom: 15px;
                }
                
                .content {
                    padding: 30px;
                }
                
                .student-info {
                    background: #f8f9fa;
                    border-radius: 10px;
                    padding: 20px;
                    margin-bottom: 25px;
                    border-left: 4px solid #667eea;
                }
                
                .student-info h3 {
                    color: #333;
                    margin-bottom: 10px;
                    font-size: 20px;
                }
                
                .student-info p {
                    color: #666;
                    margin: 5px 0;
                }
                
                .student-info strong {
                    color: #333;
                }
                
                .qr-container {
                    text-align: center;
                    padding: 20px;
                    background: white;
                    border-radius: 10px;
                    border: 2px dashed #e0e0e0;
                    margin-bottom: 25px;
                }
                
                .qr-code {
                    max-width: 100%;
                    height: auto;
                    border-radius: 10px;
                    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                }
                
                .buttons {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 15px;
                    margin-top: 20px;
                }
                
                .btn {
                    padding: 15px;
                    border: none;
                    border-radius: 10px;
                    font-size: 16px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 10px;
                }
                
                .btn-print {
                    background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
                    color: white;
                }
                
                .btn-print:hover {
                    background: linear-gradient(135deg, #45a049 0%, #4CAF50 100%);
                    transform: translateY(-2px);
                    box-shadow: 0 5px 15px rgba(76, 175, 80, 0.4);
                }
                
                .btn-save {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }
                
                .btn-save:hover {
                    background: linear-gradient(135deg, #1976D2 0%, #2196F3 100%);
                    transform: translateY(-2px);
                    box-shadow: 0 5px 15px rgba(33, 150, 243, 0.4);
                }
                
                .btn-close {
                    background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
                    color: white;
                    grid-column: span 2;
                }
                
                .btn-close:hover {
                    background: linear-gradient(135deg, #d32f2f 0%, #f44336 100%);
                    transform: translateY(-2px);
                    box-shadow: 0 5px 15px rgba(244, 67, 54, 0.4);
                }
                
                .instructions {
                    margin-top: 25px;
                    padding: 15px;
                    background: #e8f4fd;
                    border-radius: 10px;
                    border-left: 4px solid #2196F3;
                }
                
                .instructions h4 {
                    color: #1976D2;
                    margin-bottom: 10px;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
                
                .instructions ul {
                    padding-left: 20px;
                    color: #555;
                }
                
                .instructions li {
                    margin: 8px 0;
                }
                
                @media print {
                    body {
                        background: white;
                    }
                    .container {
                        box-shadow: none;
                        border: 1px solid #ccc;
                    }
                    .buttons, .instructions {
                        display: none;
                    }
                    .qr-container {
                        border: none;
                    }
                    .header {
                        background: white;
                        color: black;
                    }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <div class="logo">📱</div>
                    <h1>ROTC Attendance QR Code</h1>
                    <p>Mariano Marcos State University</p>
                </div>
                
                <div class="content">
                    <div class="student-info">
                        <h3><?php echo htmlspecialchars($student['name']); ?></h3>
                        <p><strong>Student Number:</strong> <?php echo htmlspecialchars($student['student_number']); ?></p>
                        <p><strong>ID:</strong> <?php echo $student_id; ?></p>
                        <p><strong>Generated:</strong> <?php echo date('F j, Y g:i A'); ?></p>
                    </div>
                    
                    <div class="qr-container">
                        <?php
                        // Use a more reliable QR API
                        $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . 
                                 urlencode($qrData) . "&format=png&margin=10&color=000&bgcolor=FFF";
                        ?>
                        <img src="<?php echo $qrUrl; ?>" alt="QR Code" class="qr-code" id="qrImage" 
                             onerror="this.onerror=null; this.src='https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=<?php echo urlencode($qrData); ?>&choe=UTF-8'">
                        <p style="margin-top: 15px; color: #666; font-size: 14px;">
                            Scan this code with the ROTC Attendance Scanner
                        </p>
                    </div>
                    
                    <div class="buttons">
                        <button class="btn btn-print" onclick="printQR()">
                            <span>🖨️</span> Print QR Code
                        </button>
                        <button class="btn btn-save" onclick="saveQR()">
                            <span>💾</span> Save Image
                        </button>
                        <button class="btn btn-close" onclick="closeWindow()">
                            <span>❌</span> Close Window
                        </button>
                    </div>
                    
                    <div class="instructions">
                        <h4>📋 Instructions:</h4>
                        <ul>
                            <li>Print this QR code or save it on your phone</li>
                            <li>Present QR code at ROTC attendance checkpoints</li>
                            <li>Code will be scanned by ROTC officers</li>
                            <li>Keep this code secure - it's for your attendance only</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <script>
            function printQR() {
                window.print();
            }
            
            function saveQR() {
                const qrImage = document.getElementById('qrImage');
                const link = document.createElement('a');
                link.href = qrImage.src;
                link.download = 'ROTC_QR_<?php echo $student_id; ?>_<?php echo $student['student_number']; ?>.png';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                // Show confirmation
                alert('QR code saved as PNG file!');
            }
            
            function closeWindow() {
                window.close();
            }
            
            // Auto-print if print parameter is set
            if (window.location.search.includes('print=1')) {
                setTimeout(() => {
                    window.print();
                }, 1000);
            }
            
            // Auto-save if download parameter is set
            if (window.location.search.includes('download=1')) {
                setTimeout(() => {
                    saveQR();
                }, 500);
            }
            
            // Test if QR image loads
            window.addEventListener('load', function() {
                const qrImage = document.getElementById('qrImage');
                qrImage.onerror = function() {
                    // Fallback to Google Charts API
                    this.src = 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=<?php echo urlencode($qrData); ?>&choe=UTF-8&margin=10';
                };
            });
            </script>
        </body>
        </html>
        <?php
        exit;
    }
}

// If student not found
echo '<!DOCTYPE html>
<html>
<head>
    <title>Error</title>
    <style>
        body { font-family: Arial; padding: 50px; text-align: center; }
        .error { color: #d32f2f; padding: 20px; background: #ffebee; border-radius: 10px; }
    </style>
</head>
<body>
    <div class="error">
        <h1>⚠️ Student Not Found</h1>
        <p>The requested student ID was not found in the database.</p>
        <button onclick="window.history.back()">Go Back</button>
    </div>
</body>
</html>';
?>
