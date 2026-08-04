<?php
require_once __DIR__ . '/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test DB Connection</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 40px auto; padding: 0 16px; }
        .top { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 20px; }
        .button { display: inline-block; padding: 10px 16px; background: #007bff; color: #fff; text-decoration: none; border-radius: 5px; }
        .button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="top">
        <h1>DB Connection Test</h1>
        <div>
            <a class="button" href="index.php">Add Notice</a>
            <a class="button" href="results.php" style="background: #28a745;">View Notices</a>
        </div>
    </div>

    <?php
    try {
        $mysqli = dbConnect();
        $currentDb = $mysqli->query('SELECT DATABASE()')->fetch_row()[0];
        echo '<h2>MySQL Connection OK</h2>';
        echo '<p>Connected to database: <strong>' . htmlspecialchars($currentDb) . '</strong></p>';
        $mysqli->close();
    } catch (Throwable $e) {
        echo '<h2>MySQL Connection Failed</h2>';
        echo '<p style="color: red;">' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    ?>
</body>
</html>
