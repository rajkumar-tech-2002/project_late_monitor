<?php
require_once 'db.php';
if (isset($_SESSION['user_id'])) {
    header('Location: report.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Nandha Educational Institution | Late Attendance Monitor</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #0d6efd;
            --primary-dark: #003d99;
            --accent: #ffc107;
            --bg-gradient: linear-gradient(135deg, #f8faff 0%, #eef2f7 100%);
            --header-gradient: linear-gradient(90deg, #0d6efd 0%, #003d99 100%);
        }

        html, body {
            height: 100%;
            margin: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-gradient);
            display: flex;
            flex-direction: column;
            color: #2d3436;
        }

        /* HEADER */
        .top-header {
            background: var(--header-gradient);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .header-brand img {
            height: 55px;
            border-radius: 8px;
            background: white;
            padding: 2px;
        }

        .header-brand h3 {
            margin: 0;
            font-weight: 700;
            font-size: 1.4rem;
            letter-spacing: -0.5px;
        }

        /* MAIN SECTION */
        .main-content {
            flex: 1 0 auto;
            padding: 1rem; /* Reduced padding for more width */
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            margin: 0;
        }

        /* CARD STYLES - Truly Full Width */
        .full-width-card {
            width: 100% !important;
            max-width: none !important;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            margin-top: 1rem;
        }

        .scan-icon-box {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
            display: inline-block;
            background: #eef4ff;
            width: 80px;
            height: 80px;
            line-height: 80px;
            border-radius: 50%;
        }

        .scan-input {
            font-size: 1.8rem;
            padding: 1rem;
            border-radius: 12px;
            border: 2px solid #dee2e6;
            text-align: center;
            letter-spacing: 4px;
            transition: all 0.3s ease;
            background: #ffffff;
            font-weight: 600;
            color: var(--primary);
            width: 100%;
            max-width: 600px; /* Kept specific for scanning experience but in full container */
            margin: 0 auto;
        }

        .scan-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.15);
            outline: none;
        }

        .student-photo {
            width: 200px;
            height: 200px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            object-fit: cover;
            background: #f8f9fa;
        }

        .info-label {
            color: #636e72;
            font-weight: 500;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2d3436;
            margin-bottom: 1rem;
        }

        .stat-card-custom {
            padding: 1.25rem;
            border-radius: 12px;
            font-weight: 700;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03);
        }

        .stat-total { background: #fee2e2; color: #dc2626; }
        .stat-month { background: #ffedd5; color: #ea580c; }

        /* FOOTER */
        .footer {
            flex-shrink: 0;
            background: white;
            padding: 1rem;
            text-align: center;
            color: #636e72;
            font-weight: 500;
            border-top: 1px solid #eee;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-up {
            animation: slideUp 0.4s ease-out;
        }
    </style>
</head>

<body>

    <header class="top-header">
        <div class="header-brand">
            <img src="assets/images/nec_logo.png" alt="NEC Logo">
            <h3>NANDHA ENGINEERING COLLEGE <small class="d-none d-lg-inline">(Autonomous), Erode</small></h3>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="ms-2 d-flex align-items-center gap-3">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="report.php" class="btn btn-warning fw-bold btn-sm rounded-pill px-3 shadow-sm">
                        <i class="bi bi-shield-lock"></i> Admin Panel
                    </a>
                    <div class="text-end d-none d-md-block text-white">
                        <div class="small fw-bold lh-1"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                    </div>
                    <a href="logout.php" class="btn btn-outline-light btn-sm rounded-pill px-3">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-light btn-sm rounded-pill px-4 fw-bold text-primary shadow-sm">
                        <i class="bi bi-person-fill"></i> Staff Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>


    <main class="main-content container-fluid">
        <!-- Scanner Interface -->
        <div id="scannerUI" class="full-width-card shadow-sm p-4 text-center">
            <div class="scan-icon-box">
                <i class="bi bi-upc-scan"></i>
            </div>
            <h2 class="fw-bold text-primary-dark">Late Attendance Monitor</h2>
            <p class="text-muted mb-4">Please scan your Student ID Card Barcode below</p>
            
            <form id="scanForm">
                <input type="text" id="reg_no" name="reg_no" 
                       class="form-control scan-input" 
                       placeholder="Scan Barcode Here" 
                       autofocus autocomplete="off" readonly>
            </form>
            
            <div class="mt-3 text-muted small">
                <i class="bi bi-info-circle me-1"></i> Use dedicated barcode scanner only
            </div>
        </div>

        <!-- Result Container -->
        <div id="studentInfo" class="w-100"></div>
    </main>

    <?php include 'footer.php'; ?>

    <script>
        const regInput = document.getElementById('reg_no');
        let scannerTimeout;

        document.addEventListener('keydown', function () {
            clearTimeout(scannerTimeout);
            regInput.readOnly = false;
            scannerTimeout = setTimeout(() => {
                regInput.readOnly = true;
            }, 100);
        });

        document.getElementById('scanForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const regNo = regInput.value.trim();
            
            if (regNo !== "") {
                fetch('save_scan.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'reg_no=' + encodeURIComponent(regNo)
                })
                .then(res => res.json())
                .then(data => {
                    const infoDiv = document.getElementById('studentInfo');
                    const scannerUI = document.getElementById('scannerUI');

                    if (data.status === 'success') {
                        scannerUI.style.display = 'none';
                        const photoPath = `assets/images/students/${data.reg_no}.jpg`;
                        
                        infoDiv.innerHTML = `
                            <div class="full-width-card shadow p-4 animate-up mb-4">
                                <div class="row g-4 align-items-center">
                                    <div class="col-lg-3 text-center">
                                        <img src="${photoPath}" class="student-photo" 
                                             onerror="this.src='assets/images/students/default.png'">
                                    </div>
                                    <div class="col-lg-9">
                                        <div class="border-bottom pb-2 mb-4">
                                            <h1 class="display-5 fw-bold text-primary-dark m-0">${data.name}</h1>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="info-label">Register Number</div>
                                                <div class="info-value text-primary">${data.reg_no}</div>
                                                
                                                <div class="info-label">Department</div>
                                                <div class="info-value">${data.dept}</div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="info-label">Class</div>
                                                <div class="info-value">${data.class}</div>
                                                
                                                <div class="info-label">Gender</div>
                                                <div class="info-value">${data.gender || 'Not Specified'}</div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="info-label">Entry Date</div>
                                                <div class="info-value">${data.date}</div>
                                                
                                                <div class="info-label">Entry Time</div>
                                                <div class="info-value">${data.time}</div>
                                            </div>
                                        </div>

                                        <div class="row g-3 mt-2">
                                            <div class="col-md-6">
                                                <div class="stat-card-custom stat-total d-flex align-items-center gap-3">
                                                    <i class="bi bi-calendar-x fs-2"></i>
                                                    <div>
                                                        <div class="small fw-normal">Total Late Entries</div>
                                                        <div class="fs-4">${data.total_late_days} Days</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="stat-card-custom stat-month d-flex align-items-center gap-3">
                                                    <i class="bi bi-calendar-month fs-2"></i>
                                                    <div>
                                                        <div class="small fw-normal">Current Month</div>
                                                        <div class="fs-4">${data.month_late_count} Days</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="alert alert-success mt-4 d-flex align-items-center gap-2 py-2">
                                            <i class="bi bi-check-circle-fill fs-5"></i>
                                            <span class="fw-bold">Scan Recorded Successfully</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;

                        setTimeout(() => window.location.reload(), 6000);
                    } else {
                        infoDiv.innerHTML = `
                            <div class="alert alert-danger mt-4 p-4 shadow-sm border-0 d-flex align-items-center gap-3">
                                <i class="bi bi-exclamation-triangle-fill fs-3"></i>
                                <span class="fw-bold fs-5">${data.message || 'Scan Failed'}</span>
                            </div>
                        `;
                        setTimeout(() => window.location.reload(), 4000);
                    }
                })
                .catch(err => {
                    console.error('Fetch Error:', err);
                    document.getElementById('studentInfo').innerHTML = 
                        `<div class="alert alert-danger mt-4 shadow-sm">Network Error. Please check connectivity.</div>`;
                });

                regInput.value = '';
                regInput.readOnly = true;
                regInput.focus();
            }
        });
    </script>
</body>
</html>

