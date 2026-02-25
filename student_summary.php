<?php
require_once 'db.php';
$conn = $pdo;

try {
    function cleanFilter($value)
    {
        return trim($value ?? '');
    }

    $dept = cleanFilter($_GET['dept'] ?? '');
    $class = cleanFilter($_GET['class'] ?? '');
    $regNo = cleanFilter($_GET['reg_no'] ?? '');
    $studentName = cleanFilter($_GET['student_name'] ?? '');

    // BUILD WHERE CLAUSE
    $whereClauses = [];
    $params = [];

    if ($dept !== '') {
        $whereClauses[] = 'department LIKE ?';
        $params[] = $dept;
    }
    if ($class !== '') {
        $whereClauses[] = 'class LIKE ?';
        $params[] = $class;
    }
    if ($regNo !== '') {
        $whereClauses[] = 'reg_no LIKE ?';
        $params[] = $regNo;
    }
    if ($studentName !== '') {
        $whereClauses[] = 'student_name LIKE ?';
        $params[] = "%$studentName%";
    }

    $whereSql = '';
    if (!empty($whereClauses)) {
        $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
    }

    // ==========================
    // AGGREGATED QUERY
    // ==========================
    // We group by reg_no and count total + current month
    $dataQuery = "SELECT 
                    reg_no, 
                    MAX(student_name) as student_name, 
                    MAX(class) as class, 
                    MAX(department) as department,
                    MAX(Gender) as gender,
                    COUNT(*) as total_count,
                    SUM(CASE WHEN MONTH(scan_date) = MONTH(GETDATE()) AND YEAR(scan_date) = YEAR(GETDATE()) THEN 1 ELSE 0 END) as monthly_count
                  FROM student_late_entry_monitor
                  $whereSql
                  GROUP BY reg_no
                  ORDER BY total_count DESC, reg_no ASC";

    $dataStmt = $conn->prepare($dataQuery);
    $dataStmt->execute($params);
    $records = $dataStmt->fetchAll();

    // ==========================
    // EXPORT
    // ==========================
    if (isset($_GET['export']) && $_GET['export'] === 'excel') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=summary_report_' . date('d-m-Y') . '.csv');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Reg No', 'Name', 'Department', 'Class', 'Monthly Count', 'Total Count']);

        foreach ($records as $row) {
            fputcsv($output, [
                $row['reg_no'],
                $row['student_name'],
                $row['department'],
                $row['class'],
                $row['monthly_count'],
                $row['total_count']
            ]);
        }
        fclose($output);
        exit;
    }

    // DROPDOWN DATA
    $depts = $conn->query('SELECT DISTINCT department FROM student_late_entry_monitor WHERE department IS NOT NULL ORDER BY department')->fetchAll(PDO::FETCH_COLUMN);
    $classes = $conn->query('SELECT DISTINCT class FROM student_late_entry_monitor WHERE class IS NOT NULL ORDER BY class')->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    die('Critical Error: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Summary Report | Late Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        .filter-card { border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 25px; border: none; }
        .table-card { border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: none; overflow: hidden; }
        .table thead { background-color: #0d6efd !important; color: white !important; }
        .student-photo { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 1px solid #dee2e6; }
        .page-header { background: linear-gradient(90deg, #6610f2 0%, #3f0d99 100%); color: white; padding: 20px 0; margin-bottom: 30px; }
        .badge-count { font-size: 0.9rem; padding: 0.5em 0.8em; border-radius: 8px; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>

<div class="page-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <div>
            <h3 class="mb-0 fw-bold">Student Summary Report</h3>
            <p class="mb-0 opacity-75">Nandha Educational Institution</p>
        </div>
        <div class="d-flex gap-2 no-print">
            <a href="report.php" class="btn btn-outline-light"><i class="bi bi-list-check"></i> Detailed Report</a>
            <a href="index.php" class="btn btn-outline-light"><i class="bi bi-upc-scan"></i> Back to Scanner</a>
        </div>
    </div>
</div>

<div class="container-fluid pb-5">
    <!-- Filters -->
    <div class="card filter-card no-print">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-funnel"></i> Summary Filters</h5>
            <form method="GET" id="filterForm" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Department</label>
                    <select name="dept" class="form-select">
                        <option value="">All Departments</option>
                        <?php foreach ($depts as $d): ?>
                            <option value="<?= htmlspecialchars($d) ?>" <?= $dept == $d ? 'selected' : '' ?>><?= htmlspecialchars($d) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Class</label>
                    <select name="class" class="form-select">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= $class == $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Reg No</label>
                    <input type="text" name="reg_no" class="form-control" value="<?= htmlspecialchars($regNo) ?>" placeholder="Search...">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Student Name</label>
                    <input type="text" name="student_name" class="form-control" value="<?= htmlspecialchars($studentName) ?>" placeholder="Search...">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                    <a href="student_summary.php" class="btn btn-secondary px-3">Reset</a>
                    <button type="button" onclick="document.getElementById('exportFlag').value='excel'; document.getElementById('filterForm').submit();" class="btn btn-success"><i class="bi bi-file-earmark-excel"></i></button>
                    <input type="hidden" name="export" id="exportFlag" value="">
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card table-card">
        <div class="card-body p-3">
            <div class="table-responsive">
                <table id="summaryTable" class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Reg No</th>
                        <th>Name</th>
                        <th>Class</th>
                        <th>Department</th>
                        <th class="text-center">Monthly Late</th>
                        <th class="text-center">Total Late</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($records as $row):
                        $photoFileJpg = 'assets/images/students/' . $row['reg_no'] . '.jpg';
                        $photoFilePng = 'assets/images/students/' . $row['reg_no'] . '.png';
                        $photoPath = file_exists(__DIR__ . '/' . $photoFileJpg)
                            ? $photoFileJpg
                            : (file_exists(__DIR__ . '/' . $photoFilePng) ? $photoFilePng : 'assets/images/students/default.png');
                        ?>
                        <tr>
                            <td><img src="<?= $photoPath ?>" class="student-photo"></td>
                            <td class="fw-bold"><?= htmlspecialchars($row['reg_no']) ?></td>
                            <td><?= htmlspecialchars($row['student_name']) ?></td>
                            <td><?= htmlspecialchars($row['class']) ?></td>
                            <td><?= htmlspecialchars($row['department']) ?></td>
                            <td class="text-center">
                                <span class="badge bg-warning text-dark badge-count"><?= $row['monthly_count'] ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-danger badge-count"><?= $row['total_count'] ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('#summaryTable').DataTable({
        "pageLength": 25,
        "order": [[6, "desc"]], // Sort by Total Late by default
        "language": {
            "search": "Search within results:"
        }
    });

    if (window.history.replaceState) {
        const url = new URL(window.location.href);
        if (url.searchParams.has('export')) {
            url.searchParams.delete('export');
            window.history.replaceState({}, document.title, url.pathname + url.search);
        }
    }
});
</script>
</body>
</html>
