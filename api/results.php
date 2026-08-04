<?php
session_start();
require_once __DIR__ . '/db.php';

$message = '';
$mysqli = dbConnect();

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
        $deleteId = intval($_POST['delete_id']);
        $currentAdminId = $_SESSION['admin_id'] ?? 0;
        $currentUsername = $_SESSION['admin_username'] ?? '';

        // Verify Ownership
        $checkStmt = $mysqli->prepare('SELECT admin_id FROM users WHERE id = ?');
        if ($checkStmt) {
            $checkStmt->bind_param('i', $deleteId);
            $checkStmt->execute();
            $checkRes = $checkStmt->get_result();
            $notice = $checkRes->fetch_assoc();
            $checkStmt->close();

            if ($notice && ($notice['admin_id'] == $currentAdminId || $currentUsername === 'admin')) {
                $stmt = $mysqli->prepare('DELETE FROM users WHERE id = ?');
                if ($stmt) {
                    $stmt->bind_param('i', $deleteId);
                    if ($stmt->execute()) {
                        $message = 'Notice deleted successfully.';
                    } else {
                        $message = 'Delete failed: ' . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $message = 'Delete prepare failed: ' . $mysqli->error;
                }
            } else {
                $message = 'Unauthorized. You can only delete your own notices.';
            }
        }
    } else {
        $message = 'Unauthorized. You must log in to delete notices.';
    }
}

// Get requested department
$deptFilter = $_GET['dept'] ?? 'All';

// Fetch records
$records = [];
if ($deptFilter === 'All') {
    $result = $mysqli->query('SELECT id, name, email, department, message, is_urgent, attachment_path, admin_id, created_at FROM users ORDER BY is_urgent DESC, created_at DESC');
} else {
    $selectStmt = $mysqli->prepare('SELECT id, name, email, department, message, is_urgent, attachment_path, admin_id, created_at FROM users WHERE department = ? ORDER BY is_urgent DESC, created_at DESC');
    if ($selectStmt) {
        $selectStmt->bind_param('s', $deptFilter);
        $selectStmt->execute();
        $result = $selectStmt->get_result();
    } else {
        $result = false;
    }
}

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    if (isset($selectStmt)) $selectStmt->close();
}

$mysqli->close();

$deptColors = [
    'AI' => ['bg' => '#f3e8ff', 'text' => '#9333ea'],
    'MECH' => ['bg' => '#ffedd5', 'text' => '#ea580c'],
    'ENTC' => ['bg' => '#dcfce7', 'text' => '#16a34a'],
    'COMP' => ['bg' => '#dbeafe', 'text' => '#2563eb'],
    'All' => ['bg' => '#f1f5f9', 'text' => '#475569']
];

function getDeptColor($dept, $type) {
    global $deptColors;
    if (array_key_exists($dept, $deptColors)) {
        return $deptColors[$dept][$type];
    }
    return $deptColors['All'][$type];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($deptFilter === 'All' ? 'All Departments' : $deptFilter); ?> - Notice Board</title>
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

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; -webkit-tap-highlight-color: transparent; }
        button, a, input, select, textarea { touch-action: manipulation; }
        button:active, a:active, .filter-btn:active, .notice-card:active { transform: scale(0.97) !important; }
        body, .navbar, .notice-card, .filter-btn, .search-box { transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease; }
        body { background-color: var(--bg-color); color: var(--text-dark); min-height: 100vh; }

        /* Navigation */
        .navbar { background: var(--nav-bg); padding: 1rem 5%; display: flex; justify-content: space-between; align-items: center; color: white; box-shadow: 0 4px 12px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 100; }
        .nav-brand { font-size: 1.5rem; font-weight: 800; display: flex; align-items: center; gap: 12px; }
        .nav-icon { background: rgba(255,255,255,0.15); padding: 8px; border-radius: 8px; }
        .nav-links { display: flex; gap: 2rem; align-items: center; font-weight: 600; }
        .nav-links a { color: rgba(255, 255, 255, 0.9); text-decoration: none; transition: color 0.2s; }
        .nav-links a:hover { color: white; }
        
        .nav-links a.login-btn {
            background: white;
            color: #2349C3;
            padding: 0.6rem 1.5rem;
            border-radius: 6px;
            font-weight: 700;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .nav-links a.login-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); color: #1e3a8a; }

        /* Hero Header */
        .hero { background: linear-gradient(135deg, var(--hero-bg-start) 0%, var(--hero-bg-end) 100%); padding: 4rem 2rem; text-align: center; color: white; }
        .hero h1 { font-size: 2.8rem; font-weight: 800; margin-bottom: 1rem; letter-spacing: -0.5px; }
        .hero p { font-size: 1.1rem; color: rgba(255,255,255,0.8); max-width: 600px; margin: 0 auto; line-height: 1.6; }

        /* Content Container */
        .container { max-width: 1000px; margin: -2rem auto 4rem; padding: 0 2rem; position: relative; z-index: 10; }

        .alert { padding: 1rem 1.5rem; border-radius: 10px; margin-bottom: 1.5rem; font-weight: 600; background: #eef2ff; color: #3730a3; border: 1px solid #c7d2fe; }

        /* Filter Pills */
        .filters { display: flex; gap: 10px; margin-bottom: 2rem; flex-wrap: wrap; justify-content: center; }
        .filter-btn { padding: 8px 16px; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 20px; text-decoration: none; color: var(--text-muted); font-weight: 600; font-size: 0.9rem; transition: all 0.2s; }
        .filter-btn:hover { background: var(--border-color); color: var(--text-dark); }
        .filter-btn.active { background: var(--nav-bg); color: white; border-color: var(--nav-bg); }

        /* Notice Cards */
        .notice-grid { display: grid; gap: 1.5rem; }
        .notice-card { background: var(--card-bg); border-radius: 16px; padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03); border: 1px solid var(--border-color); position: relative; transition: transform 0.2s; overflow: hidden; }
        .notice-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
        .notice-card.urgent { border: 2px solid #ef4444; box-shadow: 0 0 15px rgba(239, 68, 68, 0.2); }
        
        .card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; }
        .badges-group { display: flex; gap: 8px; align-items: center; }
        .dept-badge { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.5px; }
        .urgent-badge { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.5px; background: #fee2e2; color: #b91c1c; animation: pulse 2s infinite; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); } 70% { box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); } 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); } }
        .date { font-size: 0.85rem; color: var(--text-muted); }
        
        .message-body { font-size: 1.05rem; line-height: 1.6; color: var(--text-dark); margin-bottom: 1.5rem; }
        
        .attachment-preview { margin-bottom: 1.5rem; border-radius: 8px; overflow: hidden; border: 1px solid var(--border-color); }
        .attachment-preview img { width: 100%; max-height: 400px; object-fit: contain; display: block; background: #f9fafb; }
        .attachment-btn { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; background: #f3f4f6; color: #4b5563; text-decoration: none; border-radius: 6px; font-size: 0.9rem; font-weight: 600; margin-bottom: 1.5rem; border: 1px solid #e5e7eb; transition: background 0.2s; }
        .attachment-btn:hover { background: #e5e7eb; }
        [data-theme="dark"] .attachment-btn { background: #374151; color: #d1d5db; border-color: #4b5563; }
        [data-theme="dark"] .attachment-btn:hover { background: #4b5563; }
        
        .card-footer { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); padding-top: 1rem; }
        .author-info { display: flex; align-items: center; gap: 10px; }
        .avatar { width: 32px; height: 32px; border-radius: 50%; background: var(--border-color); display: flex; align-items: center; justify-content: center; font-weight: bold; color: var(--text-muted); }
        .author-details h4 { font-size: 0.9rem; color: var(--text-dark); margin-bottom: 2px; }
        .author-details p { font-size: 0.8rem; color: var(--text-muted); }

        .delete-btn { background: #fee2e2; color: #991b1b; border: none; padding: 6px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: background 0.2s; display: flex; align-items: center; gap: 4px; }
        .delete-btn:hover { background: #fecaca; }

        .empty-state { text-align: center; padding: 4rem 2rem; background: var(--card-bg); border-radius: 16px; border: 2px dashed var(--border-color); }
        .empty-state svg { color: var(--border-color); margin-bottom: 1rem; }
        .empty-state h3 { font-size: 1.2rem; color: var(--text-dark); margin-bottom: 0.5rem; }
        .empty-state p { color: var(--text-muted); margin-bottom: 1.5rem; }
        .primary-btn { display: inline-block; padding: 10px 20px; background: var(--nav-bg); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; }

        /* Dark Mode Toggle */
        .theme-toggle {
            background: rgba(255,255,255,0.1);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        .theme-toggle:hover { background: rgba(255,255,255,0.2); }
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

    <header class="hero">
        <h1><?php echo htmlspecialchars($deptFilter === 'All' ? 'All Notices' : $deptFilter . ' Notices'); ?></h1>
        <p>Stay updated with the latest announcements and academic schedules.</p>
    </header>

    <div class="container">
        
        <div class="filters">
            <a href="results.php?dept=All" class="filter-btn <?php echo $deptFilter === 'All' ? 'active' : ''; ?>">All</a>
            <a href="results.php?dept=AI" class="filter-btn <?php echo $deptFilter === 'AI' ? 'active' : ''; ?>">AI</a>
            <a href="results.php?dept=MECH" class="filter-btn <?php echo $deptFilter === 'MECH' ? 'active' : ''; ?>">MECH</a>
            <a href="results.php?dept=ENTC" class="filter-btn <?php echo $deptFilter === 'ENTC' ? 'active' : ''; ?>">ENTC</a>
            <a href="results.php?dept=COMP" class="filter-btn <?php echo $deptFilter === 'COMP' ? 'active' : ''; ?>">COMP</a>
        </div>

        <div style="margin-bottom: 2rem;">
            <input type="text" id="searchInput" placeholder="🔍 Search notices by keyword or publisher..." style="width: 100%; padding: 12px 20px; border-radius: 10px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-dark); font-size: 1rem; outline: none; transition: box-shadow 0.2s;">
        </div>

        <?php if ($message !== ''): ?>
            <div class="alert"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if (count($records) === 0): ?>
            <div class="empty-state">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="3" x2="9" y2="21"></line></svg>
                <h3>No notices found</h3>
                <p>There are currently no announcements for this department.</p>
                <a href="index.php" class="primary-btn">Publish a Notice</a>
            </div>
        <?php else: ?>
            <div class="notice-grid">
                <?php foreach ($records as $record): ?>
                    <?php 
                        $dept = $record['department'] ?? 'All'; 
                        $bgColor = getDeptColor($dept, 'bg');
                        $textColor = getDeptColor($dept, 'text');
                    ?>
                    <div class="notice-card <?php echo $record['is_urgent'] ? 'urgent' : ''; ?>">
                        <div class="card-header">
                            <div class="badges-group">
                                <?php if ($record['is_urgent']): ?>
                                    <span class="urgent-badge">URGENT</span>
                                <?php endif; ?>
                                <span class="dept-badge" style="background: <?php echo $bgColor; ?>; color: <?php echo $textColor; ?>;">
                                    <?php echo htmlspecialchars($dept); ?>
                                </span>
                            </div>
                            <span class="date">
                                <?php echo date('M j, Y', strtotime($record['created_at'])); ?>
                            </span>
                        </div>
                        
                        <div class="message-body">
                            <?php echo nl2br(htmlspecialchars($record['message'])); ?>
                        </div>

                        <?php 
                            $isAuthor = (isset($_SESSION['admin_id']) && $_SESSION['admin_id'] == $record['admin_id']);
                            $isSuperAdmin = (isset($_SESSION['admin_username']) && $_SESSION['admin_username'] === 'admin');
                            $canManage = $isAuthor || $isSuperAdmin;
                        ?>

                        <?php if (!empty($record['attachment_path'])): ?>
                            <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']): ?>
                                <?php $ext = strtolower(pathinfo($record['attachment_path'], PATHINFO_EXTENSION)); ?>
                                <?php if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                                    <div class="attachment-preview">
                                        <img src="<?php echo htmlspecialchars($record['attachment_path']); ?>" alt="Notice Attachment">
                                    </div>
                                <?php else: ?>
                                    <a href="<?php echo htmlspecialchars($record['attachment_path']); ?>" class="attachment-btn" download>
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                        Download Attachment
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <div style="margin-bottom: 1.5rem; padding: 10px 15px; background: rgba(35, 73, 195, 0.05); color: var(--text-muted); border-radius: 8px; border: 1px dashed var(--border-color); font-size: 0.9rem; display: flex; align-items: center; gap: 8px;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                    Attachment is hidden. Please log in to view this file.
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <div class="card-footer">
                            <div class="author-info">
                                <div class="avatar">
                                    <?php echo strtoupper(substr(htmlspecialchars($record['name']), 0, 1)); ?>
                                </div>
                                <div class="author-details">
                                    <h4><?php echo htmlspecialchars($record['name']); ?></h4>
                                    <p><?php echo htmlspecialchars($record['email']); ?></p>
                                </div>
                            </div>
                            
                            <?php if ($canManage): ?>
                                <form method="post" onsubmit="return confirm('Delete this notice?');" style="margin: 0;">
                                    <input type="hidden" name="delete_id" value="<?php echo htmlspecialchars($record['id']); ?>">
                                    <button class="delete-btn" type="submit">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                        Delete
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

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

        // Live Search Logic
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                const term = e.target.value.toLowerCase();
                const cards = document.querySelectorAll('.notice-card');
                
                cards.forEach(card => {
                    const text = card.textContent.toLowerCase();
                    if (text.includes(term)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        }
    </script>
</body>
</html>
