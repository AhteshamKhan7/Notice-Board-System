<?php
session_start();
require_once 'db.php';

$mysqli = dbConnect();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please fill in both fields.';
    } else {
        $stmt = $mysqli->prepare('SELECT id, password_hash FROM admins WHERE username = ?');
        if ($stmt) {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                if (password_verify($password, $row['password_hash'])) {
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_username'] = $username;
                    $_SESSION['admin_id'] = $row['id'];
                    header('Location: index.php');
                    exit;
                } else {
                    $error = 'Invalid password.';
                }
            } else {
                $error = 'Invalid username.';
            }
            $stmt->close();
        } else {
            $error = 'Database error: ' . $mysqli->error;
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
    <title>Admin Login - Keystone Notice Board</title>
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
            --bg-color: #f3f4f6;
            --card-bg: white;
            --input-bg: #f9fafb;
            --border-color: rgba(0,0,0,0.05);
        }

        [data-theme="dark"] {
            --nav-bg: #111827;
            --text-dark: #f9fafb;
            --text-muted: #9ca3af;
            --bg-color: #111827;
            --card-bg: #1f2937;
            --input-bg: #374151;
            --border-color: #374151;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; transition: background-color 0.3s, color 0.3s, border-color 0.3s; }
        body { background-color: var(--bg-color); color: var(--text-dark); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        [data-theme="light"] body, :not([data-theme="dark"]) body { background-image: linear-gradient(135deg, #e0e7ff 0%, #f3f4f6 100%); }
        
        .login-card {
            background: var(--card-bg);
            padding: 3rem 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(35, 73, 195, 0.1);
            width: 100%;
            max-width: 420px;
            border: 1px solid var(--border-color);
            text-align: center;
        }

        .login-header { margin-bottom: 2.5rem; }
        .login-icon { width: 64px; height: 64px; background: #eef2ff; color: var(--nav-bg); border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem; }
        .login-header h2 { font-size: 1.8rem; font-weight: 800; color: var(--nav-bg); margin-bottom: 0.5rem; }
        .login-header p { color: var(--text-muted); font-size: 0.95rem; }

        .form-group { margin-bottom: 1.5rem; text-align: left; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--text-dark); font-size: 0.95rem; }
        .form-control { width: 100%; padding: 0.85rem 1rem; border: 1px solid var(--border-color); border-radius: 10px; font-size: 1rem; transition: all 0.2s; background: var(--input-bg); color: var(--text-dark); }
        .form-control:focus { outline: none; border-color: var(--nav-bg); box-shadow: 0 0 0 4px rgba(35, 73, 195, 0.1); background: var(--card-bg); }

        .submit-btn { width: 100%; padding: 1.1rem; background: var(--nav-bg); color: white; border: none; border-radius: 10px; font-size: 1.1rem; font-weight: 700; cursor: pointer; transition: all 0.2s; margin-top: 0.5rem; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .submit-btn:hover { background: #1d3da3; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(35, 73, 195, 0.3); }
        .submit-btn:active { transform: scale(0.98); }

        .alert { padding: 1rem 1.5rem; border-radius: 10px; margin-bottom: 1.5rem; font-weight: 600; text-align: center; font-size: 0.9rem; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        .back-link { display: inline-block; margin-top: 1.5rem; color: var(--text-muted); text-decoration: none; font-size: 0.95rem; font-weight: 500; transition: color 0.2s; }
        .back-link:hover { color: var(--nav-bg); text-decoration: underline; }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="login-header">
            <div class="login-icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            </div>
            <h2>Admin Access</h2>
            <p>Sign in to manage the notice board</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" required placeholder="admin" autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required placeholder="••••••••">
            </div>
            <button type="submit" class="submit-btn">
                Log In
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            </button>
        </form>

        <a href="register.php" class="back-link" style="margin-right: 15px;">Create Account</a>
        <a href="index.php" class="back-link">← Return to Home</a>
    </div>

    <script>
        const root = document.documentElement;
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            root.setAttribute('data-theme', savedTheme);
        }
    </script>
</body>
</html>
