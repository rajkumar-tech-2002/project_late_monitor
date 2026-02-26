<?php
require_once 'db.php';
checkAuth();  // Basic auth check

header('Content-Type: application/json');

$type = $_GET['type'] ?? '';
$isAdmin = hasRole('admin');
$isHOD = hasRole('hod');
$userDept = $_SESSION['dept'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

try {
    if ($type === 'single' || $type === 'multiple') {
        $ids = $_POST['ids'] ?? [];
        if (empty($ids)) {
            echo json_encode(['status' => 'error', 'message' => 'No records selected.']);
            exit;
        }

        // Prepare placeholders for IN clause
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        // Security: If HOD, ensure they can only delete their department's records
        $sql = "DELETE FROM student_late_entry_monitor WHERE id IN ($placeholders)";
        $params = $ids;

        if ($isHOD) {
            $sql .= ' AND department = ?';
            $params[] = $userDept;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $count = $stmt->rowCount();

        echo json_encode(['status' => 'success', 'message' => "$count records deleted successfully."]);
        exit;
    }

    if ($type === 'range') {
        if (!$isAdmin) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied. Admin only.']);
            exit;
        }

        $fromDate = $_POST['from_date'] ?? '';
        $toDate = $_POST['to_date'] ?? '';
        $targetDept = $_POST['dept'] ?? '';

        if (!$fromDate || !$toDate) {
            echo json_encode(['status' => 'error', 'message' => 'Please provide both dates.']);
            exit;
        }

        $sql = 'DELETE FROM student_late_entry_monitor WHERE scan_date >= ? AND scan_date <= ?';
        $params = [$fromDate, $toDate];

        if ($targetDept !== '') {
            $sql .= ' AND department = ?';
            $params[] = $targetDept;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $count = $stmt->rowCount();

        echo json_encode(['status' => 'success', 'message' => "$count records deleted in the specified range."]);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid deletion type.']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>
