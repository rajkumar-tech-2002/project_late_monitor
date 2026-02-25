<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database configuration
$serverName = "10.11.16.250";
$database = "SLM";
$username = "feedback";
$password = "DL480eG8";
$port = 1433; // default port for SQL Server

try {
    // SQL Server connection using PDO
    $dsn = "sqlsrv:Server=$serverName,$port;Database=$database;TrustServerCertificate=1";
    $conn = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Validate input
    if (empty($_POST['reg_no'])) {
        echo json_encode(['status' => 'error', 'message' => 'Register number is required']);
        exit;
    }

    $reg_no = trim($_POST['reg_no']);

    // ✅ Fetch student details

$query = "SELECT Reg_Number, Name, Department, Class, Gender 
                  FROM Student_Master 
                  WHERE Reg_Number = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$reg_no]);
    $student = $stmt->fetch();

    if ($student) {
        // ✅ Insert scan record
        $current_date = date('Y-m-d');
        $current_time = date('H:i:s');
        $gender = isset($student['Gender']) ? $student['Gender'] : '';
        $photo = $student['Reg_Number'] . '.jpg';

        $insertQuery = "INSERT INTO student_late_entry_monitor 
                        (reg_no, student_name, department, class, scan_date, entry_time, Gender, Photo)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->execute([
            $student['Reg_Number'],
            $student['Name'],
            $student['Department'],
            $student['Class'],
            $current_date,
            $current_time,
            $gender,
            $photo
        ]);

        // ✅ Fetch Late Statistics
        // A. Total Late Days (Overall)
        $totalLateQuery = "SELECT COUNT(*) as total FROM student_late_entry_monitor WHERE reg_no = ?";
        $totalLateStmt = $conn->prepare($totalLateQuery);
        $totalLateStmt->execute([$student['Reg_Number']]);
        $totalLateDays = $totalLateStmt->fetch()['total'];

        // B. Current Month Late Count
        $monthLateQuery = "SELECT COUNT(*) as total 
                           FROM student_late_entry_monitor
                           WHERE reg_no = ?
                           AND MONTH(scan_date) = MONTH(GETDATE())
                           AND YEAR(scan_date) = YEAR(GETDATE())";
        $monthLateStmt = $conn->prepare($monthLateQuery);
        $monthLateStmt->execute([$student['Reg_Number']]);
        $monthLateCount = $monthLateStmt->fetch()['total'];

        // ✅ Success response
        echo json_encode([
            'status' => 'success',
            'reg_no' => $student['Reg_Number'],
            'name' => $student['Name'],
            'dept' => $student['Department'],
            'class' => $student['Class'],
            'date' => date('d-m-Y'),
            'time' => date('H:i:s'),
            'gender' => $gender,
            'photo' => $photo,
            'total_late_days' => $totalLateDays,
            'month_late_count' => $monthLateCount
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Student not found for Register Number: ' . $reg_no]);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
