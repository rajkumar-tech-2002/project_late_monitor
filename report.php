<?php
require_once 'db.php';
checkAuth();  // Redirect to login if not authenticated
$conn = $pdo;

try {
    function cleanFilter($value)
    {
        $value = trim($value ?? '');
        return $value;
    }

    // ==========================
    // ROLE-BASED FILTER OVERRIDE
    // ==========================
    $isAdmin = hasRole('admin');
    $isHOD = hasRole('hod');
    $userDept = $_SESSION['dept'] ?? '';

    // ==========================
    // SAFE FILTER INPUTS
    // ==========================
    $fromDate = cleanFilter($_GET['from_date'] ?? '');
    $toDate = cleanFilter($_GET['to_date'] ?? '');
    $dept = ($isHOD && $userDept !== '') ? $userDept : cleanFilter($_GET['dept'] ?? '');
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

    $classQuery = 'SELECT DISTINCT class FROM student_late_entry_monitor WHERE class IS NOT NULL';
    $classParams = [];
    if ($dept !== '') {
        $classQuery .= ' AND department = ?';
        $classParams[] = $dept;
    }
    $classQuery .= ' ORDER BY class';

    $classesStmt = $conn->prepare($classQuery);
    $classesStmt->execute($classParams);
    $classes = $classesStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    die('Critical Error: ' . $e->getMessage());
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Late Attendance Report | Late Attendance</title>
    <link rel="icon" type="image/png" href="assets/images/nec_logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        .filter-card { border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 25px; border: none; }
        .table-card { border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: none; overflow: hidden; }
        .table thead { background-color: #0d6efd !important; color: white !important; }
        .table thead th { border: none; padding: 15px 10px; }
        .student-photo { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 1px solid #dee2e6; }
        .page-header { background: linear-gradient(90deg, #0d6efd 0%, #003d99 100%); color: white; padding: 20px 0; margin-bottom: 30px; box-shadow: 0 4px 12px rgba(13,110,253,0.1); }
        .logo-img { height: 60px; width: auto; margin-right: 15px; background: white; padding: 5px; border-radius: 8px; }
        
        /* Deletion Styles */
        .btn-delete { color: #dc3545; transition: all 0.2s; }
        .btn-delete:hover { color: #a52834; transform: scale(1.1); }
        .checkbox-custom { width: 18px; height: 18px; cursor: pointer; }
        .bulk-actions { display: none; }
        
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

        /* ══ Analytics Dashboard ══════════════════════════════════════ */
        .analytics-section { margin-bottom: 32px; }

        /* KPI Stat Cards */
        .stat-card {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid #f0f2f5;
            padding: 20px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.09);
        }
        .stat-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.35rem; flex-shrink: 0;
        }
        .stat-value {
            font-size: 1.75rem; font-weight: 700;
            line-height: 1; color: #1a1d23;
        }
        .stat-label {
            font-size: 0.75rem; font-weight: 600;
            text-transform: uppercase; letter-spacing: .6px;
            color: #8a94a6; margin-top: 5px;
        }

        /* Chart Cards */
        .chart-card {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid #f0f2f5;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            overflow: hidden;
            transition: box-shadow .2s ease;
        }
        .chart-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
        .chart-card .card-header {
            background: #ffffff;
            border-bottom: 1px solid #f4f5f7;
            padding: 14px 18px;
            font-weight: 600;
            font-size: .88rem;
            color: #3d4451;
            display: flex;
            align-items: center;
            gap: 8px;
            letter-spacing: .1px;
        }
        .chart-card .card-header i { font-size: 1rem; }
        .chart-card .card-body { padding: 16px 14px; position: relative; }

        /* Spinner & placeholder */
        .chart-spinner {
            position: absolute; inset: 0;
            display: flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,.85); z-index: 10; border-radius: 14px;
        }
        .chart-placeholder {
            display: flex; flex-direction: column; align-items: center;
            justify-content: center; gap: 8px;
            height: 200px; color: #b0bac9; font-size: .85rem;
        }
        .chart-placeholder i { font-size: 1.6rem; opacity: .5; }

        /* Dashboard header */
        .dashboard-section-title {
            font-size: .92rem; font-weight: 700; color: #3d4451;
            letter-spacing: .2px;
        }
        .dashboard-filter-badge {
            font-size: .74rem; background: #eef2ff;
            color: #4361ee; padding: 3px 10px;
            border-radius: 20px; font-weight: 600;
        }
        .dashboard-toggle-btn {
            font-size: .78rem; padding: 4px 12px;
            border-radius: 20px; border-color: #e2e6ea;
            color: #6c757d;
        }
        .dashboard-toggle-btn:hover { background: #f8f9fa; }
    </style>
</head>
<body>

<div class="page-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <img src="assets/images/nec_logo.png" class="logo-img" alt="College Logo">
            <div>
                <h3 class="mb-0 fw-bold">Late Attendance Report</h3>
                <p class="mb-0 opacity-75">Nandha Educational Institution</p>
            </div>
        </div>
        <div class="d-flex gap-2 no-print">
            <a href="student_summary.php" class="btn btn-outline-light">
                <i class="bi bi-person-badge"></i> Summary Report
            </a>
            <a href="logout.php" class="btn btn-light text-danger fw-bold">
                <i class="bi bi-box-arrow-right"></i> Logout
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
                    <select name="dept" class="form-select filter-input" <?= $isHOD ? 'disabled' : '' ?>>
                        <option value="">All Departments</option>
                        <?php foreach ($depts as $d): ?>
                            <option value="<?= htmlspecialchars($d) ?>" <?= $dept == $d ? 'selected' : '' ?>><?= htmlspecialchars($d) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($isHOD): ?><input type="hidden" name="dept" value="<?= htmlspecialchars($dept) ?>"><?php endif; ?>
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
                    <?php if ($isAdmin): ?>
                        <button type="button" class="btn btn-danger px-4" data-bs-toggle="modal" data-bs-target="#rangeDeleteModal">
                            <i class="bi bi-calendar-range"></i> Range Delete
                        </button>
                    <?php endif; ?>
                    <input type="hidden" name="export" id="exportFlag" value="">
                </div>
            </form>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         ANALYTICS DASHBOARD — Added above existing table
    ═══════════════════════════════════════════════════════════ -->
    <div class="analytics-section no-print" id="analyticsDashboard">

        <!-- Dashboard Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0"><i class="bi bi-bar-chart-line-fill text-primary me-2"></i>Analytics Dashboard</h5>
            <div class="d-flex gap-2 align-items-center">
                <span class="text-muted" style="font-size:.82rem" id="chartFilterLabel">Showing all data</span>
                <button class="btn btn-outline-secondary dashboard-toggle-btn" onclick="toggleDashboard()" id="dashToggleBtn">
                    <i class="bi bi-chevron-up" id="dashToggleIcon"></i> Collapse
                </button>
            </div>
        </div>

        <div id="dashboardContent">
            <!-- ── Stat Cards Row ── -->
            <div class="row g-3 mb-4" id="statsRow">
                <div class="col-6 col-md-3">
                    <div class="stat-card" style="background:linear-gradient(135deg,#4361ee15,#4361ee08)">
                        <div class="stat-icon" style="background:#4361ee20;color:#4361ee"><i class="bi bi-clock-history"></i></div>
                        <div>
                            <div class="stat-value text-primary" id="statTotalLate">—</div>
                            <div class="stat-label">Total Late</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card" style="background:linear-gradient(135deg,#f7258515,#f7258508)">
                        <div class="stat-icon" style="background:#f7258520;color:#f72585"><i class="bi bi-people-fill"></i></div>
                        <div>
                            <div class="stat-value" style="color:#f72585" id="statStudents">—</div>
                            <div class="stat-label">Students</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card" style="background:linear-gradient(135deg,#4cc9f015,#4cc9f008)">
                        <div class="stat-icon" style="background:#4cc9f020;color:#0098c7"><i class="bi bi-building"></i></div>
                        <div>
                            <div class="stat-value" style="color:#0098c7" id="statDepts">—</div>
                            <div class="stat-label">Departments</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card" style="background:linear-gradient(135deg,#7209b715,#7209b708)">
                        <div class="stat-icon" style="background:#7209b720;color:#7209b7"><i class="bi bi-mortarboard-fill"></i></div>
                        <div>
                            <div class="stat-value" style="color:#7209b7" id="statClasses">—</div>
                            <div class="stat-label">Classes</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Charts Row 1: Pie + Doughnut + Bar ── -->
            <div class="row g-3 mb-3">
                <!-- Dept Pie -->
                <div class="col-md-3">
                    <div class="card chart-card h-100">
                        <div class="card-header"><i class="bi bi-pie-chart-fill text-primary"></i> Dept-wise Late</div>
                        <div class="card-body" style="min-height:280px">
                            <div class="chart-spinner" id="spinner_dept"><div class="spinner-border text-primary" role="status"></div></div>
                            <canvas id="chartDept"></canvas>
                        </div>
                    </div>
                </div>
                <!-- Class Doughnut -->
                <div class="col-md-3">
                    <div class="card chart-card h-100">
                        <div class="card-header"><i class="bi bi-check-circle text-warning"></i> Class-wise Late</div>
                        <div class="card-body" style="min-height:280px">
                            <div class="chart-spinner" id="spinner_class"><div class="spinner-border text-warning" role="status"></div></div>
                            <canvas id="chartClass"></canvas>
                        </div>
                    </div>
                </div>
                <!-- Top Students Bar -->
                <div class="col-md-6">
                    <div class="card chart-card h-100">
                        <div class="card-header"><i class="bi bi-bar-chart-fill text-danger"></i> Top 10 Most Late Students</div>
                        <div class="card-body" style="min-height:280px">
                            <div class="chart-spinner" id="spinner_top"><div class="spinner-border text-danger" role="status"></div></div>
                            <canvas id="chartTop"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Charts Row 2: Date Trend ── -->
            <div class="row g-3 mb-3">
                <div class="col-12 col-lg-6">
                    <div class="card chart-card">
                        <div class="card-header"><i class="bi bi-graph-up text-success"></i> Daily Late Trend</div>
                        <div class="card-body" style="min-height:220px">
                            <div class="chart-spinner" id="spinner_date"><div class="spinner-border text-success" role="status"></div></div>
                            <canvas id="chartDate"></canvas>
                        </div>
                    </div>
                </div>

            <!-- ── Charts Row 3: Monthly Trend ── -->
                <div class="col-12 col-lg-6">
                    <div class="card chart-card">
                        <div class="card-header"><i class="bi bi-calendar3 text-info"></i> Monthly Late Trend</div>
                        <div class="card-body" style="min-height:220px">
                            <div class="chart-spinner" id="spinner_monthly"><div class="spinner-border text-info" role="status"></div></div>
                            <canvas id="chartMonthly"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /#dashboardContent -->
    </div><!-- /.analytics-section -->
    <!-- ─────────────────────────────────────────────────────────── -->

    <!-- Bulk Actions Bar -->
    <div class="bulk-actions mb-3 animate-up no-print">
        <div class="alert alert-info d-flex justify-content-between align-items-center py-2 px-3 rounded-pill shadow-sm">
            <span class="fw-bold"><i class="bi bi-check2-all me-2"></i> <span id="selectedCount">0</span> records selected</span>
            <button type="button" class="btn btn-danger btn-sm px-4 rounded-pill" onclick="handleBulkDelete()">
                <i class="bi bi-trash-fill me-1"></i> Delete Selected
            </button>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card table-card">
        <div class="card-body p-0">
            <div class="table-responsive p-3">
                <table id="attendanceTable" class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4 no-print"><input type="checkbox" id="selectAll" class="checkbox-custom"></th>
                            <th>Photo</th>
                            <th>Reg No</th>
                            <th>Name</th>
                            <th>Class</th>
                            <th>Department</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th class="no-print">Action</th>
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
                                    : (file_exists(__DIR__ . '/' . $photoFilePng) ? $photoFilePng : 'assets/images/students/profile.png');
                                ?>
                                <tr>
                                    <td class="ps-4 no-print">
                                        <input type="checkbox" name="record_ids[]" value="<?= $row['id'] ?>" class="checkbox-custom record-checkbox">
                                    </td>
                                    <td>
                                        <img src="<?= $photoPath ?>" class="student-photo" alt="Photo">
                                    </td>
                                    <td class="fw-bold"><?= htmlspecialchars($row['reg_no']) ?></td>
                                    <td><?= htmlspecialchars($row['student_name']) ?></td>
                                    <td><?= htmlspecialchars($row['class']) ?></td>
                                    <td><?= htmlspecialchars($row['department']) ?></td>
                                    <td><?= date('d-m-Y', strtotime($row['scan_date'])) ?></td>
                                    <td><?= date('H:i:s', strtotime($row['entry_time'])) ?></td>
                                    <td class="no-print text-center">
                                        <button type="button" class="btn btn-link btn-delete p-0" onclick="handleSingleDelete(<?= $row['id'] ?>)">
                                            <i class="bi bi-trash3-fill"></i>
                                        </button>
                                    </td>
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

<!-- Range Delete Modal (Admin Only) -->
<?php if ($isAdmin): ?>
<div class="modal fade" id="rangeDeleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Advanced Range Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="rangeDeleteForm">
                <div class="modal-body">
                    <div class="alert alert-warning small">
                        <i class="bi bi-exclamation-triangle-fill"></i> Warning: This action will permanently delete all records within the selected range.
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">From Date</label>
                            <input type="date" name="from_date" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">To Date</label>
                            <input type="date" name="to_date" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Optional: Specific Department</label>
                            <select name="dept" class="form-select">
                                <option value="">All Departments</option>
                                <?php foreach ($depts as $d): ?>
                                    <option value="<?= htmlspecialchars($d) ?>"><?= htmlspecialchars($d) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4">Confirm Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    const table = $('#attendanceTable').DataTable({
        "paging": true,
        "ordering": false,
        "info": true,
        "searching": false,
        "pageLength": 10,
        "lengthMenu": [10, 25, 50, 100],
        "dom": '<"top"f>rt<"bottom"lip><"clear">'
    });

    // Select All Checkboxes
    $('#selectAll').on('change', function() {
        $('.record-checkbox').prop('checked', this.checked);
        updateBulkActions();
    });

    $('.record-checkbox').on('change', function() {
        updateBulkActions();
    });

    // Dynamic Class Filtering
    $('select[name="dept"]').on('change', function() {
        const dept = $(this).val();
        const classSelect = $('select[name="class"]');
        
        // Disable class select while loading
        classSelect.prop('disabled', true);
        
        $.getJSON('get_classes.php', { dept: dept }, function(data) {
            classSelect.empty();
            classSelect.append('<option value="">All Classes</option>');
            
            data.forEach(function(className) {
                classSelect.append($('<option>', {
                    value: className,
                    text: className
                }));
            });
        }).always(function() {
            classSelect.prop('disabled', false);
        });
    });

    function updateBulkActions() {
        const selectedCount = $('.record-checkbox:checked').length;
        $('#selectedCount').text(selectedCount);
        if (selectedCount > 0) {
            $('.bulk-actions').fadeIn();
        } else {
            $('.bulk-actions').fadeOut();
            $('#selectAll').prop('checked', false);
        }
    }

    // Handle Single Delete
    window.handleSingleDelete = function(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This record will be permanently deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                deleteRecords([id], 'single');
            }
        });
    }

    // Handle Bulk Delete
    window.handleBulkDelete = function() {
        const ids = $('.record-checkbox:checked').map(function() { return this.value; }).get();
        Swal.fire({
            title: 'Delete Selected?',
            text: `You are about to delete ${ids.length} records.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, delete them!'
        }).then((result) => {
            if (result.isConfirmed) {
                deleteRecords(ids, 'multiple');
            }
        });
    }

    // Handle Range Delete
    $('#rangeDeleteForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        Swal.fire({
            title: 'Confirm Range Delete',
            text: "All records in this range will be lost!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Delete Range'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('delete_handler.php?type=range', formData, function(res) {
                    handleResponse(res);
                    $('#rangeDeleteModal').modal('hide');
                }, 'json');
            }
        });
    });

    function deleteRecords(ids, type) {
        $.ajax({
            url: 'delete_handler.php?type=' + type,
            method: 'POST',
            data: { ids: ids },
            dataType: 'json',
            success: function(res) {
                handleResponse(res);
            }
        });
    }

    function handleResponse(res) {
        if (res.status === 'success') {
            Swal.fire('Deleted!', res.message, 'success').then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire('Error', res.message, 'error');
        }
    }
});
</script>

<!-- ═══════════════════════════════════════════════════════
     ANALYTICS DASHBOARD — Chart.js AJAX Script (Refined UI)
═══════════════════════════════════════════════════════ -->
<script>
(function() {
    'use strict';

    // ── Professional Soft Color Palette ─────────────────────
    const PALETTE = [
        '#4361ee','#3a86ff','#48cae4','#06a77d','#e9c46a',
        '#f4a261','#e76f51','#8338ec','#b5838d','#6d9dc5',
        '#52b788','#c77dff','#f77f00','#457b9d','#e63946'
    ];
    // Single accent for bar + line charts
    const ACCENT       = '#4361ee';
    const ACCENT_LIGHT = 'rgba(67,97,238,0.12)';
    const PINK         = '#e63d72';
    const PINK_LIGHT   = 'rgba(230,61,114,0.12)';

    // ── Chart.js Global Defaults ─────────────────────────────
    Chart.defaults.font.family   = "'Poppins', sans-serif";
    Chart.defaults.font.size     = 12;
    Chart.defaults.color         = '#6c757d';
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(30,35,50,0.88)';
    Chart.defaults.plugins.tooltip.titleColor      = '#ffffff';
    Chart.defaults.plugins.tooltip.bodyColor       = '#d1d5db';
    Chart.defaults.plugins.tooltip.padding         = 10;
    Chart.defaults.plugins.tooltip.cornerRadius    = 8;
    Chart.defaults.plugins.tooltip.displayColors   = false;

    // shared scale style
    const SCALE_BASE = {
        grid:  { color: 'rgba(0,0,0,0.05)', drawBorder: false },
        ticks: { color: '#8a94a6' },
        border:{ dash: [3, 3], color: 'transparent' }
    };

    // ── Filter state ─────────────────────────────────────────
    const urlParams = new URLSearchParams(window.location.search);
    const filters = {
        from_date:    urlParams.get('from_date')    || '',
        to_date:      urlParams.get('to_date')      || '',
        dept:         urlParams.get('dept')         || '',
        class:        urlParams.get('class')        || '',
        reg_no:       urlParams.get('reg_no')       || '',
        student_name: urlParams.get('student_name') || '',
    };

    function updateFilterLabel() {
        const parts = [];
        if (filters.from_date) parts.push('From: ' + filters.from_date);
        if (filters.to_date)   parts.push('To: '   + filters.to_date);
        if (filters.dept)      parts.push('Dept: ' + filters.dept);
        if (filters['class'])  parts.push('Class: '+ filters['class']);
        if (filters.reg_no)    parts.push('Reg: '  + filters.reg_no);
        const lbl = document.getElementById('chartFilterLabel');
        if (!lbl) return;
        if (parts.length) {
            lbl.className = 'dashboard-filter-badge';
            lbl.textContent = parts.join(' · ');
        } else {
            lbl.className = 'text-muted';
            lbl.style.fontSize = '.78rem';
            lbl.textContent = 'Showing all data';
        }
    }
    updateFilterLabel();

    window.toggleDashboard = function() {
        const content = document.getElementById('dashboardContent');
        const btn     = document.getElementById('dashToggleBtn');
        if (content.style.display === 'none') {
            content.style.display = '';
            btn.innerHTML = '<i class="bi bi-chevron-up" id="dashToggleIcon"></i> Collapse';
        } else {
            content.style.display = 'none';
            btn.innerHTML = '<i class="bi bi-chevron-down" id="dashToggleIcon"></i> Expand';
        }
    };

    function buildUrl(type) {
        return 'chart_data.php?' + new URLSearchParams({ type, ...filters }).toString();
    }

    const chartInstances = {};
    function hideSpinner(id) { const s = document.getElementById('spinner_' + id); if (s) s.style.display = 'none'; }
    function showPlaceholder(canvasId, message) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        canvas.style.display = 'none';
        const p = document.createElement('div');
        p.className = 'chart-placeholder';
        p.innerHTML = '<i class="bi bi-bar-chart"></i><span>' + message + '</span>';
        canvas.parentElement.appendChild(p);
    }
    function destroyChart(key) {
        if (chartInstances[key]) { chartInstances[key].destroy(); delete chartInstances[key]; }
    }
    function fetchChart(type, renderFn, spinnerId) {
        fetch(buildUrl(type))
            .then(r => r.json())
            .then(data => {
                hideSpinner(spinnerId);
                if (!data.labels || !data.labels.length) {
                    const map = { dept_pie:'chartDept', class_doughnut:'chartClass', top_students:'chartTop', date_trend:'chartDate', monthly_trend:'chartMonthly' };
                    showPlaceholder(map[type] || type, 'No data for current filters');
                    return;
                }
                renderFn(data);
            })
            .catch(() => hideSpinner(spinnerId));
    }

    // ── Legend shared config ──────────────────────────────────
    const LEGEND_BOTTOM = {
        position: 'bottom',
        labels: {
            font: { size: 11, family: "'Poppins', sans-serif" },
            padding: 14,
            usePointStyle: true,
            pointStyle: 'circle',
            color: '#6c757d'
        }
    };
    const TOOLTIP_LATE = { callbacks: { label: c => ' ' + c.parsed.toLocaleString() + ' late entries' } };

    // ── 1. Stats Cards ───────────────────────────────────────
    fetch(buildUrl('stats')).then(r => r.json()).then(d => {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = (v||0).toLocaleString(); };
        set('statTotalLate', d.total_late); set('statStudents', d.unique_students);
        set('statDepts', d.dept_count);     set('statClasses',  d.class_count);
    }).catch(() => {});

    // ── 2. Dept Pie ──────────────────────────────────────────
    fetchChart('dept_pie', function(raw) {
        destroyChart('dept');
        // Inject refined palette into dataset
        raw.datasets[0].backgroundColor = PALETTE.slice(0, raw.labels.length);
        raw.datasets[0].borderColor      = '#ffffff';
        raw.datasets[0].borderWidth      = 2;
        raw.datasets[0].hoverOffset      = 6;
        const ctx = document.getElementById('chartDept').getContext('2d');
        chartInstances['dept'] = new Chart(ctx, {
            type: 'pie', data: raw,
            options: {
                responsive: true, maintainAspectRatio: true,
                plugins: { legend: LEGEND_BOTTOM, tooltip: TOOLTIP_LATE }
            }
        });
    }, 'dept');

    // ── 3. Class Doughnut ────────────────────────────────────
    fetchChart('class_doughnut', function(raw) {
        destroyChart('class');
        raw.datasets[0].backgroundColor = PALETTE.slice(0, raw.labels.length);
        raw.datasets[0].borderColor      = '#ffffff';
        raw.datasets[0].borderWidth      = 2;
        raw.datasets[0].hoverOffset      = 6;
        const ctx = document.getElementById('chartClass').getContext('2d');
        chartInstances['class'] = new Chart(ctx, {
            type: 'doughnut', data: raw,
            options: {
                responsive: true, maintainAspectRatio: true, cutout: '65%',
                plugins: { legend: LEGEND_BOTTOM, tooltip: TOOLTIP_LATE }
            }
        });
    }, 'class');

    // ── 4. Top 10 Students Bar (single accent color) ─────────
    fetchChart('top_students', function(raw) {
        destroyChart('top');
        // Override to uniform accent with slight alpha variation per rank
        const n = raw.labels.length;
        raw.datasets[0].backgroundColor = raw.labels.map((_, i) =>
            `rgba(67,97,238,${0.95 - i * (0.5 / Math.max(n, 1))})`);
        raw.datasets[0].borderColor   = 'transparent';
        raw.datasets[0].borderRadius  = 6;
        raw.datasets[0].borderSkipped = false;
        const ctx = document.getElementById('chartTop').getContext('2d');
        chartInstances['top'] = new Chart(ctx, {
            type: 'bar', data: raw,
            options: {
                indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false },
                    tooltip: { callbacks: { label: c => ' ' + c.parsed.x.toLocaleString() + ' times late' } } },
                scales: {
                    x: { ...SCALE_BASE, beginAtZero: true, ticks: { precision: 0, color:'#8a94a6' },
                        title: { display: true, text: 'Late Count', color: '#8a94a6', font: { size: 11 } } },
                    y: { ...SCALE_BASE, grid: { display: false },
                        ticks: { color: '#3d4451', font: { size: 11 }, mirror: false } }
                }
            }
        });
    }, 'top');

    // ── 5. Date Trend Line (soft blue gradient) ──────────────
    fetchChart('date_trend', function(raw) {
        destroyChart('date');
        const ctx = document.getElementById('chartDate').getContext('2d');
        // Create vertical gradient fill
        const grad = ctx.createLinearGradient(0, 0, 0, 280);
        grad.addColorStop(0,   'rgba(67,97,238,0.20)');
        grad.addColorStop(0.6, 'rgba(67,97,238,0.05)');
        grad.addColorStop(1,   'rgba(67,97,238,0.00)');
        raw.datasets[0].borderColor          = ACCENT;
        raw.datasets[0].backgroundColor      = grad;
        raw.datasets[0].fill                 = true;
        raw.datasets[0].tension              = 0.4;
        raw.datasets[0].pointRadius          = raw.labels.length > 60 ? 0 : 3;
        raw.datasets[0].pointHoverRadius     = 5;
        raw.datasets[0].pointBackgroundColor = ACCENT;
        raw.datasets[0].borderWidth          = 2;
        chartInstances['date'] = new Chart(ctx, {
            type: 'line', data: raw,
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: false },
                    tooltip: { callbacks: { label: c => ' ' + c.parsed.y.toLocaleString() + ' late entries' } } },
                scales: {
                    x: { ...SCALE_BASE, ticks: { maxRotation: 45, color:'#8a94a6', font:{ size:10 },
                        maxTicksLimit: 20 } },
                    y: { ...SCALE_BASE, beginAtZero: true, ticks: { precision:0, color:'#8a94a6' },
                        title: { display: true, text: 'Late Count', color:'#8a94a6', font:{size:11} } }
                }
            }
        });
    }, 'date');

    // ── 6. Monthly Trend Line (soft pink gradient) ───────────
    fetchChart('monthly_trend', function(raw) {
        destroyChart('monthly');
        const ctx = document.getElementById('chartMonthly').getContext('2d');
        const grad = ctx.createLinearGradient(0, 0, 0, 260);
        grad.addColorStop(0,   'rgba(230,61,114,0.20)');
        grad.addColorStop(0.6, 'rgba(230,61,114,0.05)');
        grad.addColorStop(1,   'rgba(230,61,114,0.00)');
        raw.datasets[0].borderColor          = PINK;
        raw.datasets[0].backgroundColor      = grad;
        raw.datasets[0].fill                 = true;
        raw.datasets[0].tension              = 0.4;
        raw.datasets[0].pointRadius          = 4;
        raw.datasets[0].pointHoverRadius     = 6;
        raw.datasets[0].pointBackgroundColor = PINK;
        raw.datasets[0].borderWidth          = 2;
        chartInstances['monthly'] = new Chart(ctx, {
            type: 'line', data: raw,
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: false },
                    tooltip: { callbacks: { label: c => ' ' + c.parsed.y.toLocaleString() + ' late entries' } } },
                scales: {
                    x: { ...SCALE_BASE, ticks: { color:'#8a94a6', font:{ size:11 } } },
                    y: { ...SCALE_BASE, beginAtZero: true, ticks: { precision:0, color:'#8a94a6' },
                        title: { display: true, text: 'Late Count', color:'#8a94a6', font:{size:11} } }
                }
            }
        });
    }, 'monthly');

})();
</script>
    <?php include 'footer.php'; ?>
</body>
</html>
