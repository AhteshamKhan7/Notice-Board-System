<?php
session_start();
require_once __DIR__ . '/db.php';

$mysqli = dbConnect();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($username === '' || $password === '' || $confirm_password === '') {
        $error = 'Please fill in all fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        // Check if username exists
        $checkStmt = $mysqli->prepare('SELECT id FROM admins WHERE username = ?');
        if ($checkStmt) {
            $checkStmt->bind_param('s', $username);
            $checkStmt->execute();
            $checkStmt->store_result();
            
            if ($checkStmt->num_rows > 0) {
                $error = 'Username already exists. Please choose another.';
            } else {
                // Insert new user
                $insertStmt = $mysqli->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)');
                if ($insertStmt) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $insertStmt->bind_param('ss', $username, $hash);
                    
                    if ($insertStmt->execute()) {
                        $success = 'Account created successfully! You can now log in.';
                    } else {
                        $error = 'Failed to create account: ' . $insertStmt->error;
                    }
                    $insertStmt->close();
                } else {
                    $error = 'Database error: ' . $mysqli->error;
                }
            }
            $checkStmt->close();
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
    <title>Create Account - Keystone Notice Board</title>
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

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        body, .login-card, .form-control, .submit-btn { transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease; }
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
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }

        .back-link { display: inline-block; margin-top: 1.5rem; color: var(--text-muted); text-decoration: none; font-size: 0.95rem; font-weight: 500; transition: color 0.2s; }
        .back-link:hover { color: var(--nav-bg); text-decoration: underline; }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="login-header">
            <div class="login-icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
            </div>
            <h2>Create Account</h2>
            <p>Join the notice board to publish</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php else: ?>
            <form method="post">
                <div class="form-group">
                    <label for="username">Choose Username</label>
                    <input type="text" id="username" name="username" class="form-control" required placeholder="new_admin" autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Create Password</label>
                    <input type="password" id="password" name="password" class="form-control" required placeholder="••••••••" minlength="6">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required placeholder="••••••••" minlength="6">
                </div>
                <button type="submit" class="submit-btn">
                    Register
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </button>
            </form>
        <?php endif; ?>

        <a href="login.php" class="back-link">← Back to Login</a>
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
