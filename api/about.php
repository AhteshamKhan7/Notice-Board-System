<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - Keystone Notice Board</title>
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
            --border-color: rgba(0,0,0,0.05);
        }

        [data-theme="dark"] {
            --nav-bg: #111827;
            --hero-bg-start: #1f2937;
            --hero-bg-end: #111827;
            --text-dark: #f9fafb;
            --text-muted: #9ca3af;
            --bg-color: #111827;
            --card-bg: #1f2937;
            --border-color: #374151;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; transition: background-color 0.3s, color 0.3s, border-color 0.3s; }
        body { background-color: var(--bg-color); color: var(--text-dark); min-height: 100vh; display: flex; flex-direction: column; }

        /* Navigation */
        .navbar { background: var(--nav-bg); padding: 1rem 5%; display: flex; justify-content: space-between; align-items: center; color: white; box-shadow: 0 4px 12px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 100; }
        .nav-brand { font-size: 1.5rem; font-weight: 800; display: flex; align-items: center; gap: 12px; }
        .nav-icon { background: rgba(255,255,255,0.15); padding: 8px; border-radius: 8px; }
        .nav-links { display: flex; gap: 2rem; align-items: center; font-weight: 600; }
        .nav-links a { color: rgba(255, 255, 255, 0.9); text-decoration: none; transition: color 0.2s; }
        .nav-links a:hover { color: white; }
        
        /* Dark Mode Toggle */
        .theme-toggle { background: rgba(255,255,255,0.1); border: none; color: white; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s; }
        .theme-toggle:hover { background: rgba(255,255,255,0.2); }

        .container { max-width: 800px; margin: 4rem auto; padding: 3rem; background: var(--card-bg); border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.05); border: 1px solid var(--border-color); line-height: 1.8; }
        .container h1 { font-size: 2.5rem; font-weight: 800; margin-bottom: 1rem; color: var(--text-dark); }
        .container p { font-size: 1.1rem; color: var(--text-dark); margin-bottom: 1.5rem; }
        .container ul { margin-bottom: 1.5rem; padding-left: 1.5rem; color: var(--text-dark); }
        .container li { margin-bottom: 0.5rem; }
        
        .login-btn { background: white; color: var(--nav-bg); padding: 8px 16px; border-radius: 8px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 6px; transition: transform 0.2s; }
        .login-btn:hover { transform: translateY(-2px); }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="nav-brand">
            <div class="nav-icon">
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

    <div class="container">
        <h1>About Keystone Notice Board</h1>
        <p>The Keystone Notice Board is a modern, centralized platform designed to streamline communication across all college departments. It serves as a unified digital space where faculty, administration, and students can instantly share and access important announcements.</p>
        
        <p><strong>Key Features:</strong></p>
        <ul>
            <li><strong>Departmental Organization:</strong> Easily filter announcements by AI, Mechanical, ENTC, and Computer Science departments.</li>
            <li><strong>Urgency Badges:</strong> Critical notices are pinned with an "URGENT" pulse to grab immediate attention.</li>
            <li><strong>File Attachments:</strong> Seamlessly download PDFs and view event posters directly from the board.</li>
            <li><strong>Live Search:</strong> Find exactly what you're looking for instantly without reloading the page.</li>
            <li><strong>Dark Mode:</strong> Switch to a gorgeous, eye-friendly dark theme with the click of a button.</li>
        </ul>
        
        <p>This system was built with a focus on speed, accessibility, and modern aesthetics, ensuring that you never miss an important update.</p>
    </div>

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
    </script>
</body>
</html>
