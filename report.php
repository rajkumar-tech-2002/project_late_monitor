<?php
require_once 'db.php';
$conn = $pdo;  // Use the connection from db.php

try {
    function cleanFilter($value)
    {
        $value = trim($value ?? '');
        return $value;
    }

    // ==========================
    // SAFE FILTER INPUTS
    // ==========================
    $fromDate = cleanFilter($_GET['from_date'] ?? '');
    $toDate = cleanFilter($_GET['to_date'] ?? '');
    $dept = cleanFilter($_GET['dept'] ?? '');
    $class = cleanFilter($_GET['class'] ?? '');
    $regNo = cleanFilter($_GET['reg_no'] ?? '');
    $studentName = cleanFilter($_GET['student_name'] ?? '');

    // ==========================
    // BUILD WHERE CLAUSE
    // ==========================
    $whereClauses = [];
    $params = [];

    if ($fromDate !== '') {
        $whereClauses[] = 'scan_date >= ?';
        $params[] = $fromDate;
    }

    if ($toDate !== '') {
        $whereClauses[] = 'scan_date <= ?';
        $params[] = $toDate;
    }

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

    // FETCH ALL FILTERED DATA (Stable one-shot fetch for DataTable)
    $dataQuery = "SELECT *
                  FROM student_late_entry_monitor
                  $whereSql
                  ORDER BY scan_date DESC, entry_time DESC, reg_no DESC";

    $dataStmt = $conn->prepare($dataQuery);
    $dataStmt->execute($params);
    $records = $dataStmt->fetchAll();

    $totalRecords = count($records);

    // ==========================
    // EXPORT
    // ==========================
    if (isset($_GET['export']) && $_GET['export'] === 'excel') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=late_report_' . date('d-m-Y') . '.csv');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Reg No', 'Name', 'Department', 'Class', 'Date', 'Entry Time', 'Gender']);

        $exportQuery = "SELECT reg_no, student_name, department, class, scan_date, entry_time, Gender
                        FROM student_late_entry_monitor
                        $whereSql
                        ORDER BY scan_date DESC, entry_time DESC";

        $exportStmt = $conn->prepare($exportQuery);
        $exportStmt->execute($params);

        while ($row = $exportStmt->fetch(PDO::FETCH_ASSOC)) {
            // Explicitly format date and time for better Excel compatibility
            if (isset($row['scan_date'])) {
                $row['scan_date'] = date('d-m-Y', strtotime($row['scan_date']));
            }
            if (isset($row['entry_time'])) {
                $row['entry_time'] = date('h:i:s A', strtotime($row['entry_time']));
            }
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    // ==========================
    // DROPDOWN DATA
    // ==========================
    $depts = $conn
        ->query('SELECT DISTINCT department FROM student_late_entry_monitor WHERE department IS NOT NULL ORDER BY department')
        ->fetchAll(PDO::FETCH_COLUMN);

    $classes = $conn
        ->query('SELECT DISTINCT class FROM student_late_entry_monitor WHERE class IS NOT NULL ORDER BY class')
        ->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    die('Critical Error: ' . $e->getMessage());
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Late Attendance Report | Nandha Institutions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        .filter-card { border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 25px; border: none; }
        .table-card { border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: none; overflow: hidden; }
        .table thead { background-color: #0d6efd !important; color: white !important; }
        .table thead th { border: none; padding: 15px 10px; }
        .student-photo { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 1px solid #dee2e6; }
        .page-header { background: linear-gradient(90deg, #0d6efd 0%, #003d99 100%); color: white; padding: 20px 0; margin-bottom: 30px; box-shadow: 0 4px 12px rgba(13,110,253,0.1); }
        
        /* DataTable styling overrides */
        .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 0; margin: 0; }
        .dataTables_info { padding: 15px; font-size: 0.875rem; color: #6c757d; }
        .dataTables_paginate { padding: 15px; }
        .pagination { margin-bottom: 0; }
        .dataTables_length { padding: 15px; font-size: 0.875rem; }
        
        @media print {
            .no-print { display: none !important; }
            .table-card { box-shadow: none; }
        }
    </style>
</head>
<body>

<div class="page-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <div>
            <h3 class="mb-0 fw-bold">Late Attendance Report</h3>
            <p class="mb-0 opacity-75">Nandha Educational Institution</p>
        </div>
        <div class="d-flex gap-2 no-print">
            <a href="student_summary.php" class="btn btn-info text-white">
                <i class="bi bi-person-badge"></i> Summary Report
            </a>
            <a href="index.php" class="btn btn-outline-light">
                <i class="bi bi-arrow-left"></i> Back to Scanner
            </a>
        </div>
    </div>
</div>

<div class="container-fluid pb-5">

    <div class="card filter-card no-print">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-funnel"></i> Search Filters</h5>
            <form method="GET" id="filterForm" class="row g-3">
                <!-- DataTable handles page state locally now -->

                <div class="col-md-3">
                    <label class="form-label small fw-bold">From Date</label>
                    <input type="date" name="from_date" class="form-control filter-input" value="<?= htmlspecialchars($fromDate) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">To Date</label>
                    <input type="date" name="to_date" class="form-control filter-input" value="<?= htmlspecialchars($toDate) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Department</label>
                    <select name="dept" class="form-select filter-input">
                        <option value="">All Departments</option>
                        <?php foreach ($depts as $d): ?>
                            <option value="<?= htmlspecialchars($d) ?>" <?= $dept == $d ? 'selected' : '' ?>><?= htmlspecialchars($d) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Class</label>
                    <select name="class" class="form-select filter-input">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= $class == $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Register Number</label>
                    <input type="text" name="reg_no" class="form-control filter-input" placeholder="Search Reg No..." value="<?= htmlspecialchars($regNo) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Student Name</label>
                    <input type="text" name="student_name" class="form-control filter-input" placeholder="Search Name..." value="<?= htmlspecialchars($studentName) ?>">
                </div>
                <div class="col-md-6 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-search"></i> Search</button>
                    <a href="report.php" class="btn btn-secondary px-4">Reset</a>
                    <button type="button" onclick="document.getElementById('exportFlag').value='excel'; document.getElementById('filterForm').submit();" class="btn btn-success px-4">
                        <i class="bi bi-file-earmark-excel"></i> Export Excel
                    </button>
                    <input type="hidden" name="export" id="exportFlag" value="">
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card table-card">
        <div class="card-body p-0">
            <div class="table-responsive p-3">
                <table id="attendanceTable" class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Photo</th>
                            <th>Reg No</th>
                            <th>Name</th>
                            <th>Class</th>
                            <th>Department</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Gender</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($records)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">No records found matching your criteria.</td>
                            </tr>
                        <?php else: ?>
                            <?php
                            foreach ($records as $row):
                                $photoFileJpg = 'assets/images/students/' . $row['reg_no'] . '.jpg';
                                $photoFilePng = 'assets/images/students/' . $row['reg_no'] . '.png';
                                $photoPath = file_exists(__DIR__ . '/' . $photoFileJpg)
                                    ? $photoFileJpg
                                    : (file_exists(__DIR__ . '/' . $photoFilePng) ? $photoFilePng : 'assets/images/students/default.png');
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <img src="<?= $photoPath ?>" class="student-photo" alt="Photo">
                                    </td>
                                    <td class="fw-bold"><?= htmlspecialchars($row['reg_no']) ?></td>
                                    <td><?= htmlspecialchars($row['student_name']) ?></td>
                                    <td><?= htmlspecialchars($row['class']) ?></td>
                                    <td><?= htmlspecialchars($row['department']) ?></td>
                                    <td><?= date('d-m-Y', strtotime($row['scan_date'])) ?></td>
                                    <td><?= date('H:i:s', strtotime($row['entry_time'])) ?></td>
                                    <td><?= htmlspecialchars($row['Gender'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <!-- DataTable will inject pagination here -->
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#attendanceTable').DataTable({
        "paging": true,
        "ordering": false, // Keep backend sort
        "info": true,
        "searching": false, // Use our custom filters
        "pageLength": 10,
        "lengthMenu": [10, 25, 50, 100],
        "language": {
            "emptyTable": "No records found matching your criteria.",
            "info": "Showing _START_ to _END_ of _TOTAL_ records",
            "paginate": {
                "first": "First",
                "last": "Last",
                "next": "Next",
                "previous": "Prev"
            }
        },
        "dom": '<"top"f>rt<"bottom"lip><"clear">'
    });

    // Cleanup: Remove export flag from URL if present after page load
    if (window.history.replaceState) {
        const url = new URL(window.location.href);
        if (url.searchParams.has('export')) {
            url.searchParams.delete('export');
            window.history.replaceState({}, document.title, url.pathname + url.search);
        }
    }
});
</script>
    </div>
</div>

</body>
</html>
