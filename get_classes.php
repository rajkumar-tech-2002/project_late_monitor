<?php
require_once 'db.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$dept = $_GET['dept'] ?? '';

try {
    $query = "SELECT DISTINCT class FROM student_late_entry_monitor WHERE class IS NOT NULL";
    $params = [];

    if ($dept !== '') {
        $query .= " AND department = ?";
        $params[] = $dept;
    }

    $query .= " ORDER BY class";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $classes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    header('Content-Type: application/json');
    echo json_encode($classes);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
