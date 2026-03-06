<?php
require_once 'db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: report.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $selectedRole = $_POST['role'] ?? '';

    if ($username !== '' && $password !== '' && $selectedRole !== '') {
        $stmt = $pdo->prepare('SELECT id, username, role FROM users WHERE username = ? AND password = ? AND role = ?');
        $stmt->execute([$username, $password, $selectedRole]);
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'hod') {
                $_SESSION['dept'] = $user['username'];
            }

            header('Location: report.php');
            exit;
        } else {
            $error = 'Invalid credentials for the selected role.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/images/nec_logo.png">
    <title>Staff Portal | Nandha Engineering College</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --accent: #4cc9f0;
            --dark: #0f172a;
            --text-muted: #64748b;
            --glass: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f8fafc;
            height: 100vh;
            margin: 0;
            overflow: hidden;
            display: flex;
        }

        .login-wrapper {
            display: flex;
            width: 100%;
            height: 100%;
        }

        /* LEFT SIDE - DYNAMIC VISUAL */
        .visual-panel {
            flex: 1.3;
            background: #0f172a;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* Animated Mesh Gradient Background */
        .mesh-bg {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: 
                radial-gradient(at 0% 0%, hsla(225, 100%, 50%, 0.3) 0, transparent 50%), 
                radial-gradient(at 50% 0%, hsla(250, 100%, 50%, 0.3) 0, transparent 50%), 
                radial-gradient(at 100% 0%, hsla(210, 100%, 50%, 0.3) 0, transparent 50%),
                radial-gradient(at 0% 100%, hsla(240, 100%, 50%, 0.2) 0, transparent 50%),
                radial-gradient(at 100% 100%, hsla(190, 100%, 50%, 0.2) 0, transparent 50%);
            filter: blur(80px);
            z-index: 1;
        }

        /* Ambient Orbs */
        .orb {
            position: absolute;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            filter: blur(100px);
            opacity: 0.15;
            z-index: 0;
            animation: move 20s infinite alternate;
        }

        .orb-1 { top: -100px; left: -100px; }
        .orb-2 { bottom: -100px; right: -100px; animation-delay: -5s; }

        @keyframes move {
            0% { transform: translate(0, 0); }
            100% { transform: translate(100px, 50px); }
        }

        .visual-inner {
            position: relative;
            z-index: 10;
            padding: 5rem;
            color: white;
            text-align: left;
        }

        .logo-box {
            background: white;
            display: inline-flex;
            padding: 15px;
            border-radius: 20px;
            margin-bottom: 3rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            animation: fadeInDown 0.8s ease-out;
        }

        .logo-box img {
            height: 70px;
        }

        .hero-text h1 {
            font-size: 4rem;
            font-weight: 800;
            background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1.5rem;
            animation: fadeInLeft 1s ease-out 0.2s both;
        }

        .hero-text p {
            font-size: 1.25rem;
            color: #94a3b8;
            max-width: 450px;
            margin-bottom: 3rem;
            animation: fadeInLeft 1s ease-out 0.4s both;
        }

        .feature-pills {
            display: flex;
            gap: 1rem;
            animation: fadeInUp 1s ease-out 0.6s both;
        }

        .pill {
            background: var(--glass);
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(10px);
            padding: 0.75rem 1.5rem;
            border-radius: 100px;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* RIGHT SIDE - FORM */
        .form-panel {
            flex: 1;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4rem;
            position: relative;
        }

        .form-content {
            width: 100%;
            max-width: 400px;
            animation: fadeInRight 0.8s ease-out;
        }

        .form-header {
            margin-bottom: 3rem;
        }

        .form-header h2 {
            font-weight: 800;
            font-size: 2.25rem;
            color: var(--dark);
            letter-spacing: -1px;
        }

        .form-header p {
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--dark);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.75rem;
            display: block;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1.1rem;
            transition: color 0.3s;
            pointer-events: none;
        }

        .form-control, .form-select {
            height: 60px;
            padding-left: 3.5rem;
            border-radius: 16px;
            border: 2px solid #f1f5f9;
            background: #f8fafc;
            font-weight: 500;
            color: var(--dark);
            transition: all 0.3s;
        }

        .form-control:focus, .form-select:focus {
            background: white;
            border-color: var(--primary);
            box-shadow: 0 0 0 5px rgba(67, 97, 238, 0.1);
        }

        /* Password field – flexbox wrapper acts as the input box */
        .password-wrapper {
            display: flex;
            align-items: center;
            height: 60px;
            border-radius: 16px;
            border: 2px solid #f1f5f9;
            background: #f8fafc;
            padding: 0 1rem 0 1.25rem;
            gap: 0.75rem;
            transition: all 0.3s;
        }

        .password-wrapper:focus-within {
            background: white;
            border-color: var(--primary);
            box-shadow: 0 0 0 5px rgba(67, 97, 238, 0.1);
        }

        .password-wrapper .pw-lock-icon {
            color: var(--text-muted);
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .password-wrapper #passwordInput {
            flex: 1;
            height: 100%;
            border: none !important;
            background: transparent !important;
            box-shadow: none !important;
            outline: none;
            padding: 0;
            font-weight: 500;
            color: var(--dark);
            font-size: 1rem;
            border-radius: 0;
        }

        .toggle-password {
            background: none;
            border: none;
            padding: 0;
            color: var(--text-muted);
            font-size: 1.1rem;
            cursor: pointer;
            line-height: 1;
            transition: color 0.3s;
            flex-shrink: 0;
            display: flex;
            align-items: center;
        }

        .toggle-password:hover {
            color: var(--primary);
        }

        .btn-login {
            height: 60px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 16px;
            font-weight: 700;
            font-size: 1.1rem;
            width: 100%;
            margin-top: 2rem;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .btn-login:hover {
            background: var(--secondary);
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(67, 97, 238, 0.25);
        }

        .error-toast {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            color: #b91c1c;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
        }

        .back-to-scanner {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2.5rem;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: color 0.2s;
        }

        .back-to-scanner:hover {
            color: var(--primary);
        }

        /* Animations */
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }

        @media (max-width: 1024px) {
            .visual-panel { display: none; }
            .form-panel { padding: 2rem; }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <!-- LEFT PANEL -->
        <div class="visual-panel">
            <div class="mesh-bg"></div>
            <div class="orb orb-1"></div>
            <div class="orb orb-2"></div>
            
            <div class="visual-inner">
                <div class="logo-box">
                    <img src="assets/images/nec_logo.png" alt="NEC Logo">
                </div>
                <div class="hero-text">
                    <h1>Late Attendance<br>Monitoring System</h1>
                    <p>Streamlined administrative access for Staff and HODs of Nandha Engineering College.</p>
                    <p>Developed by: <span class="fw-bold text-primary">Rajkumar Anbazhagan</span></p>
                </div>
                <div class="feature-pills">
                    <div class="pill">
                        <i class="bi bi-shield-check"></i> Secure Access
                    </div>
                    <div class="pill">
                        <i class="bi bi-graph-up-arrow"></i> Real-time Reports
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL -->
        <div class="form-panel">
            <div class="form-content">
                <div class="form-header">
                    <h2>Welcome back</h2>
                    <p>Securely sign in to your staff account</p>
                </div>

                <?php if ($error): ?>
                    <div class="error-toast">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" autocomplete="off">
                    <div class="form-group">
                        <label>Identity</label>
                        <div class="input-wrapper">
                            <i class="bi bi-person-badge"></i>
                            <select name="role" class="form-select" required>
                                <option value="" disabled selected>Select your role</option>
                                <option value="admin">Administrator</option>
                                <option value="hod">Department HOD</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Username</label>
                        <div class="input-wrapper">
                            <i class="bi bi-person"></i>
                            <input type="text" name="username" class="form-control" placeholder="Staff ID or Username" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <div class="password-wrapper">
                            <i class="bi bi-lock pw-lock-icon"></i>
                            <input type="password" id="passwordInput" name="password" placeholder="••••••••" required>
                            <button type="button" class="toggle-password" id="togglePassword" aria-label="Toggle password visibility">
                                <i class="bi bi-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        Sign In <i class="bi bi-arrow-right"></i>
                    </button>

                    <a href="index.php" class="back-to-scanner">
                        <i class="bi bi-arrow-left"></i> Back to Student Scanner
                    </a>
                </form>
            </div>
        </div>
    </div>
<script>
    document.getElementById('togglePassword').addEventListener('click', function () {
        var input = document.getElementById('passwordInput');
        var icon  = document.getElementById('toggleIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    });
</script>
</body>
</html>
