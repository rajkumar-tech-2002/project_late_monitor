<?php
/**
 * chart_data.php
 * AJAX endpoint — returns JSON data for Chart.js charts.
 * Supports: dept_pie, class_doughnut, top_students, date_trend, monthly_trend
 * Respects all filters: dept, class, reg_no, student_name, from_date, to_date
 * Enforces role-based access (HOD is restricted to own department).
 */

require_once 'db.php';
checkAuth();

header('Content-Type: application/json');

$conn = $pdo;

// ─────────────────────────────────────────────
// HELPER: sanitize input
// ─────────────────────────────────────────────
function clean($v) { return trim($v ?? ''); }

// ─────────────────────────────────────────────
// ROLE-BASED FILTER OVERRIDE
// ─────────────────────────────────────────────
$isHOD    = hasRole('hod');
$userDept = $_SESSION['dept'] ?? '';

$type        = clean($_GET['type']        ?? '');
$fromDate    = clean($_GET['from_date']   ?? '');
$toDate      = clean($_GET['to_date']     ?? '');
$dept        = ($isHOD && $userDept !== '') ? $userDept : clean($_GET['dept'] ?? '');
$class       = clean($_GET['class']       ?? '');
$regNo       = clean($_GET['reg_no']      ?? '');
$studentName = clean($_GET['student_name'] ?? '');

// ─────────────────────────────────────────────
// BUILD SHARED WHERE CLAUSE
// ─────────────────────────────────────────────
$whereClauses = [];
$params       = [];

if ($fromDate !== '') {
    $whereClauses[] = 'scan_date >= ?';
    $params[]       = $fromDate;
}
if ($toDate !== '') {
    $whereClauses[] = 'scan_date <= ?';
    $params[]       = $toDate;
}
if ($dept !== '') {
    $whereClauses[] = 'department LIKE ?';
    $params[]       = $dept;
}
if ($class !== '') {
    $whereClauses[] = 'class LIKE ?';
    $params[]       = $class;
}
if ($regNo !== '') {
    $whereClauses[] = 'reg_no LIKE ?';
    $params[]       = $regNo;
}
if ($studentName !== '') {
    $whereClauses[] = 'student_name LIKE ?';
    $params[]       = "%$studentName%";
}

$whereSql = empty($whereClauses) ? '' : 'WHERE ' . implode(' AND ', $whereClauses);

// ─────────────────────────────────────────────
// PALETTE HELPER
// ─────────────────────────────────────────────
function palette($n) {
    $colors = [
        '#4361ee','#f72585','#4cc9f0','#7209b7','#3a0ca3',
        '#f3722c','#43aa8b','#f9c74f','#90be6d','#277da1',
        '#e63946','#06d6a0','#118ab2','#ffd166','#ef476f',
        '#2ec4b6','#ff9f1c','#cbf3f0','#ffbf69','#a8dadc',
    ];
    $result = [];
    for ($i = 0; $i < $n; $i++) {
        $result[] = $colors[$i % count($colors)];
    }
    return $result;
}

// ─────────────────────────────────────────────
// RESPONSE
// ─────────────────────────────────────────────
try {
    switch ($type) {

        // ── 1. DEPARTMENT-WISE PIE CHART ──────────────────────────────
        case 'dept_pie': {
            $sql  = "SELECT department, COUNT(*) AS late_count
                     FROM student_late_entry_monitor
                     $whereSql
                     GROUP BY department
                     ORDER BY late_count DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            $labels = array_column($rows, 'department');
            $data   = array_map('intval', array_column($rows, 'late_count'));
            $colors = palette(count($labels));

            echo json_encode([
                'labels'          => $labels,
                'datasets'        => [[
                    'data'            => $data,
                    'backgroundColor' => $colors,
                    'borderWidth'     => 2,
                    'borderColor'     => '#fff',
                ]],
            ]);
            break;
        }

        // ── 2. CLASS-WISE DOUGHNUT CHART ──────────────────────────────
        case 'class_doughnut': {
            $sql  = "SELECT class, COUNT(*) AS late_count
                     FROM student_late_entry_monitor
                     $whereSql
                     GROUP BY class
                     ORDER BY late_count DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            $labels = array_column($rows, 'class');
            $data   = array_map('intval', array_column($rows, 'late_count'));
            $colors = palette(count($labels));

            echo json_encode([
                'labels'   => $labels,
                'datasets' => [[
                    'data'            => $data,
                    'backgroundColor' => $colors,
                    'borderWidth'     => 2,
                    'borderColor'     => '#fff',
                ]],
            ]);
            break;
        }

        // ── 3. TOP 10 LATE STUDENTS BAR CHART ────────────────────────
        case 'top_students': {
            $sql  = "SELECT TOP 10
                         reg_no,
                         MAX(student_name) AS student_name,
                         COUNT(*) AS late_count
                     FROM student_late_entry_monitor
                     $whereSql
                     GROUP BY reg_no
                     ORDER BY late_count DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            // Label: "Name (RegNo)"
            $labels = array_map(function($r) {
                return $r['student_name'] . ' (' . $r['reg_no'] . ')';
            }, $rows);
            $data   = array_map('intval', array_column($rows, 'late_count'));
            $colors = palette(count($labels));

            echo json_encode([
                'labels'   => $labels,
                'datasets' => [[
                    'label'           => 'Total Late Count',
                    'data'            => $data,
                    'backgroundColor' => $colors,
                    'borderColor'     => $colors,
                    'borderWidth'     => 1,
                    'borderRadius'    => 6,
                ]],
            ]);
            break;
        }

        // ── 4. DATE-WISE TREND LINE CHART ─────────────────────────────
        case 'date_trend': {
            $sql  = "SELECT CONVERT(VARCHAR(10), scan_date, 105) AS scan_day,
                            COUNT(*) AS late_count
                     FROM student_late_entry_monitor
                     $whereSql
                     GROUP BY scan_date
                     ORDER BY scan_date ASC";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            $labels = array_column($rows, 'scan_day');
            $data   = array_map('intval', array_column($rows, 'late_count'));

            echo json_encode([
                'labels'   => $labels,
                'datasets' => [[
                    'label'           => 'Daily Late Count',
                    'data'            => $data,
                    'borderColor'     => '#4361ee',
                    'backgroundColor' => 'rgba(67,97,238,0.15)',
                    'fill'            => true,
                    'tension'         => 0.4,
                    'pointBackgroundColor' => '#4361ee',
                    'pointRadius'     => 4,
                ]],
            ]);
            break;
        }

        // ── 5. MONTHLY TREND LINE CHART ───────────────────────────────
        case 'monthly_trend': {
            $sql  = "SELECT YEAR(scan_date) AS yr,
                            MONTH(scan_date) AS mo,
                            COUNT(*) AS late_count
                     FROM student_late_entry_monitor
                     $whereSql
                     GROUP BY YEAR(scan_date), MONTH(scan_date)
                     ORDER BY yr ASC, mo ASC";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            $labels = array_map(function($r) use ($months) {
                return $months[intval($r['mo']) - 1] . ' ' . $r['yr'];
            }, $rows);
            $data = array_map('intval', array_column($rows, 'late_count'));

            echo json_encode([
                'labels'   => $labels,
                'datasets' => [[
                    'label'           => 'Monthly Late Count',
                    'data'            => $data,
                    'borderColor'     => '#f72585',
                    'backgroundColor' => 'rgba(247,37,133,0.15)',
                    'fill'            => true,
                    'tension'         => 0.4,
                    'pointBackgroundColor' => '#f72585',
                    'pointRadius'     => 4,
                ]],
            ]);
            break;
        }

        // ── 6. SUMMARY STATS ──────────────────────────────────────────
        case 'stats': {
            $sql  = "SELECT
                         COUNT(*) AS total_late,
                         COUNT(DISTINCT reg_no) AS unique_students,
                         COUNT(DISTINCT department) AS dept_count,
                         COUNT(DISTINCT class) AS class_count
                     FROM student_late_entry_monitor
                     $whereSql";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch();

            echo json_encode([
                'total_late'      => intval($row['total_late']),
                'unique_students' => intval($row['unique_students']),
                'dept_count'      => intval($row['dept_count']),
                'class_count'     => intval($row['class_count']),
            ]);
            break;
        }

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid chart type specified.']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
