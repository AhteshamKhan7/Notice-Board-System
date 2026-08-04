<?php
session_start();
require_once 'db.php';

$mysqli = dbConnect();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $messageText = trim($_POST['message'] ?? '');
    $department = trim($_POST['department'] ?? 'All');
    $isUrgent = isset($_POST['is_urgent']) ? 1 : 0;

    $attachmentPath = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $fileName = time() . '_' . basename($_FILES['attachment']['name']);
        $targetFilePath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetFilePath)) {
            $attachmentPath = $targetFilePath;
        }
    }

    if ($name === '' || $email === '' || $messageText === '') {
        $message = 'Please fill in all fields.';
    } else {
        $adminId = $_SESSION['admin_id'] ?? null;
        if ($adminId) {
            $stmt = $mysqli->prepare('INSERT INTO users (name, email, department, message, is_urgent, attachment_path, admin_id) VALUES (?, ?, ?, ?, ?, ?, ?)');
            if ($stmt) {
                $stmt->bind_param('ssssisi', $name, $email, $department, $messageText, $isUrgent, $attachmentPath, $adminId);
                if ($stmt->execute()) {
                    $message = 'Notice saved successfully.';
                } else {
                    $message = 'Insert failed: ' . $stmt->error;
                }
                $stmt->close();
            } else {
                $message = 'Prepare failed: ' . $mysqli->error;
            }
        } else {
            $message = 'Error: You must be logged in to submit a notice.';
        }
    }
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keystone Notice Board</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --nav-bg: #2349C3;
            --hero-bg-start: #15327A;
            --hero-bg-end: #09132D;
            --text-dark: #1f2937;
            --text-muted: #6b7280;
            --bg-color: #f9fafb;
            --card-bg: white;
            --input-bg: #f9fafb;
            --border-color: #d1d5db;
        }

        [data-theme="dark"] {
            --nav-bg: #111827;
            --hero-bg-start: #1f2937;
            --hero-bg-end: #111827;
            --text-dark: #f9fafb;
            --text-muted: #9ca3af;
            --bg-color: #111827;
            --card-bg: #1f2937;
            --input-bg: #374151;
            --border-color: #4b5563;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
            transition: background-color 0.3s, color 0.3s, border-color 0.3s;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Navigation */
        .navbar {
            background-color: var(--nav-bg);
            padding: 1.2rem 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.4rem;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .bell-icon {
            width: 36px;
            height: 36px;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 2.5rem;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .nav-links a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            transition: color 0.2s;
        }

        .nav-links a:hover {
            color: white;
        }

        .nav-links a.login-btn {
            background: white;
            color: #2349C3; /* Explicit hex code instead of var to prevent white-on-white bug */
            padding: 0.6rem 1.5rem;
            border-radius: 6px;
            font-weight: 700;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .nav-links a.login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            color: #1e3a8a;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(to right, var(--hero-bg-start) 0%, var(--hero-bg-end) 100%);
            padding: 6rem 2rem 8rem 2rem;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: repeating-linear-gradient(
                45deg,
                rgba(255, 255, 255, 0.02) 0px,
                rgba(255, 255, 255, 0.02) 2px,
                transparent 2px,
                transparent 12px
            );
            pointer-events: none;
        }

        .hero::after {
            content: '';
            position: absolute;
            right: 15%;
            bottom: -50px;
            width: 300px;
            height: 300px;
            background: linear-gradient(to top right, rgba(255,255,255,0.01), rgba(255,255,255,0.06));
            clip-path: polygon(50% 0%, 0% 100%, 100% 100%);
            pointer-events: none;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.2rem;
            position: relative;
            z-index: 1;
            letter-spacing: -1px;
        }

        .hero p {
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto;
            color: rgba(255, 255, 255, 0.85);
            line-height: 1.6;
            position: relative;
            z-index: 1;
        }

        /* Cards Section */
        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            padding: 0 3rem;
            max-width: 1300px;
            margin: -4rem auto 2rem auto;
            position: relative;
            z-index: 10;
        }

        .card {
            border-radius: 16px;
            padding: 2.5rem 1.5rem;
            text-align: center;
            color: white;
            box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
        }

        .card-icon {
            width: 72px;
            height: 72px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .card-icon svg {
            width: 32px;
            height: 32px;
            stroke-width: 1.5;
        }

        .card h3 {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .card p {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            opacity: 0.9;
        }

        .card-ai { background: linear-gradient(135deg, #A825FF, #8B1EFE); }
        .card-mech { background: linear-gradient(135deg, #FF9500, #F05E00); }
        .card-entc { background: linear-gradient(135deg, #0ED781, #00A355); }
        .card-comp { background: linear-gradient(135deg, #337BFF, #1555E0); }

        /* Form Section */
        .form-section {
            max-width: 600px;
            margin: 3rem auto 5rem auto;
            background: var(--card-bg);
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            width: 90%;
            border: 1px solid var(--border-color);
        }

        .form-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .form-header h2 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .form-header p {
            color: var(--text-muted);
        }

        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.2s;
            background: var(--input-bg);
            color: var(--text-dark);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--nav-bg);
            box-shadow: 0 0 0 4px rgba(35, 73, 195, 0.1);
            background: var(--card-bg);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: rgba(239, 68, 68, 0.05);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 8px;
        }

        .checkbox-group label {
            margin: 0;
            color: #b91c1c;
            font-weight: 700;
            cursor: pointer;
        }

        [data-theme="dark"] .checkbox-group label { color: #fca5a5; }

        /* Toast Notification */
        .toast-container {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .toast {
            background: var(--card-bg);
            color: var(--text-dark);
            padding: 1rem 1.5rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border-left: 4px solid #10b981;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            transform: translateX(120%);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .toast.show {
            transform: translateX(0);
            opacity: 1;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        .submit-btn {
            width: 100%;
            padding: 1.1rem;
            background: var(--nav-bg);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 1rem;
        }

        .submit-btn:hover {
            background: #1d3da3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(35, 73, 195, 0.3);
        }

        .submit-btn:active {
            transform: scale(0.98);
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            font-weight: 600;
            text-align: center;
        }

        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        .view-notices {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-align: center;
            margin-top: 2rem;
            color: var(--nav-bg);
            font-weight: 700;
            text-decoration: none;
            width: 100%;
            padding: 1rem;
            border-radius: 10px;
            background: #f3f4f6;
            transition: background 0.2s;
        }

        .view-notices:hover {
            background: #e5e7eb;
        }
    </style>
</head>
<body>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-brand">
            <div class="bell-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
            </div>
            Keystone Notice Board
        </div>
        <div class="nav-links">
            <button class="theme-toggle" id="themeToggle" title="Toggle Dark Mode">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="moon-icon"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
            </button>
            <a href="index.php">Home</a>
            <a href="about.php">About</a>
            <a href="results.php">Departments</a>
            <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']): ?>
                <a href="logout.php" class="login-btn" style="background: #fee2e2; color: #991b1b;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    Logout
                </a>
            <?php else: ?>
                <a href="login.php" class="login-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    Login
                </a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero">
        <h1>College Notice Board</h1>
        <p>Stay updated with the latest announcements, events, and academic schedules from across all departments.</p>
    </header>

    <!-- Cards Section -->
    <div class="cards-container">
        <a href="results.php?dept=AI" class="card card-ai" style="text-decoration: none; color: white;">
            <div class="card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
            </div>
            <h3>AI</h3>
            <p>Artificial Intelligence</p>
        </a>
        <a href="results.php?dept=MECH" class="card card-mech" style="text-decoration: none; color: white;">
            <div class="card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
            </div>
            <h3>MECH</h3>
            <p>Mechanical Engg</p>
        </a>
        <a href="results.php?dept=ENTC" class="card card-entc" style="text-decoration: none; color: white;">
            <div class="card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            </div>
            <h3>ENTC</h3>
            <p>Electronics & TC</p>
        </a>
        <a href="results.php?dept=COMP" class="card card-comp" style="text-decoration: none; color: white;">
            <div class="card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
            </div>
            <h3>COMP</h3>
            <p>Computer Science</p>
        </a>
    </div>

    <!-- Form Section -->
    <section class="form-section">
        <div class="form-header">
            <h2>Add New Notice</h2>
            <p>Publish an announcement to the notice board</p>
        </div>

        <?php if ($message !== ''): ?>
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    showToast("<?php echo addslashes($message); ?>");
                });
            </script>
        <?php endif; ?>

        <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']): ?>
            <form method="post" id="notice-form" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Publisher Name</label>
                    <input type="text" id="name" name="name" class="form-control" required placeholder="e.g. Prof. Smith">
                </div>
                <div class="form-group">
                    <label for="email">Contact Email</label>
                    <input type="email" id="email" name="email" class="form-control" required placeholder="your@email.com">
                </div>
                <div class="form-group">
                    <label for="department">Department</label>
                    <select id="department" name="department" class="form-control">
                        <option value="All">All Departments</option>
                        <option value="AI">AI - Artificial Intelligence</option>
                        <option value="MECH">MECH - Mechanical Engg</option>
                        <option value="ENTC">ENTC - Electronics & TC</option>
                        <option value="COMP">COMP - Computer Science</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="message">Notice Details</label>
                    <textarea id="message" name="message" class="form-control" rows="5" required placeholder="Type the announcement here..."></textarea>
                </div>
                <div class="form-group">
                    <label for="attachment">Attachment (Optional - Image or PDF)</label>
                    <input type="file" id="attachment" name="attachment" class="form-control" accept="image/*,.pdf">
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" id="is_urgent" name="is_urgent" value="1">
                    <label for="is_urgent">Mark as URGENT (High Priority)</label>
                </div>
                <button type="submit" class="submit-btn">Publish Notice</button>
            </form>
        <?php else: ?>
            <div style="text-align: center; padding: 2rem; background: var(--input-bg); border-radius: 12px; border: 1px solid var(--border-color); margin-bottom: 2rem;">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 1rem;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                <h3 style="margin-bottom: 0.5rem; color: var(--text-dark);">Login Required</h3>
                <p style="color: var(--text-muted); margin-bottom: 1.5rem;">You must be logged in to publish or update notices on the board.</p>
                <a href="login.php" class="view-notices" style="display: inline-block; width: auto; padding: 0.8rem 2rem; margin-top: 0; background: var(--nav-bg); color: white;">Log In Now</a>
            </div>
        <?php endif; ?>

        <a href="results.php" class="view-notices">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
            View All Published Notices
        </a>
        <div style="text-align: center; margin-top: 1rem;">
            <a href="test_db_connection.php" style="color: #6b7280; font-size: 0.85rem; text-decoration: underline;">Run DB Connection Test</a>
        </div>
    </section>

    <div class="toast-container" id="toastContainer"></div>

    <script>
        // Dark Mode Logic
        const themeToggle = document.getElementById('themeToggle');
        const root = document.documentElement;
        
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            root.setAttribute('data-theme', savedTheme);
        }

        themeToggle.addEventListener('click', () => {
            const currentTheme = root.getAttribute('data-theme');
            if (currentTheme === 'dark') {
                root.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
            } else {
                root.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            }
        });

        // Toast Logic
        function showToast(message) {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.innerHTML = `
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                ${message}
            `;
            container.appendChild(toast);
            
            // Trigger animation
            setTimeout(() => toast.classList.add('show'), 10);
            
            // Remove after 4s
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 400);
            }, 4000);
        }
    </script>
    <div class="toast-container" id="toastContainer"></div>

    <script>
        // Dark Mode Logic
        const themeToggle = document.getElementById('themeToggle');
        const root = document.documentElement;
        
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            root.setAttribute('data-theme', savedTheme);
        }

        themeToggle.addEventListener('click', () => {
            const currentTheme = root.getAttribute('data-theme');
            if (currentTheme === 'dark') {
                root.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
            } else {
                root.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            }
        });

        // Toast Logic
        function showToast(message) {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.innerHTML = `
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                ${message}
            `;
            container.appendChild(toast);
            
            // Trigger animation
            setTimeout(() => toast.classList.add('show'), 10);
            
            // Remove after 4s
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 400);
            }, 4000);
        }
    </script>
</body>
</html>
