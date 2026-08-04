<?php
// MySQL connection settings.
// Support Environment Variables for Cloud Hosting (Vercel / Render / Railway) with local XAMPP fallback
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$dbName = getenv('DB_NAME') ?: 'notice_board';
$dbPort = getenv('DB_PORT') ? (int)getenv('DB_PORT') : 3306;

function dbConnect() {
    global $dbHost, $dbUser, $dbPass, $dbName, $dbPort;

    mysqli_report(MYSQLI_REPORT_OFF);

    try {
        $mysqli = @new mysqli($dbHost, $dbUser, $dbPass, '', $dbPort);
        if ($mysqli->connect_error) {
            throw new Exception('Connection to MySQL failed: ' . $mysqli->connect_error);
        }

        $mysqli->set_charset('utf8mb4');

        try {
            $mysqli->select_db($dbName);
        } catch (Throwable $e) {
            $createDbSql = "CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            $mysqli->query($createDbSql);
            $mysqli->select_db($dbName);
        }

        // 1. Create admins table first (so users can reference it)
        $createAdminsSql = "CREATE TABLE IF NOT EXISTS `admins` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(50) NOT NULL UNIQUE,
            `password_hash` VARCHAR(255) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        if (!$mysqli->query($createAdminsSql)) {
            throw new Exception('Failed to create admins table: ' . $mysqli->error);
        }

        // 2. Create users table with foreign key to admins
        $createTableSql = "CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `email` VARCHAR(100) NOT NULL,
            `department` VARCHAR(50) DEFAULT 'All',
            `message` TEXT NOT NULL,
            `is_urgent` TINYINT(1) DEFAULT 0,
            `attachment_path` VARCHAR(255) NULL,
            `admin_id` INT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        if (!$mysqli->query($createTableSql)) {
            throw new Exception('Failed to create users table: ' . $mysqli->error);
        }

        // 3. ALTER TABLE for existing users table (if admin_id or department doesn't exist yet)
        $checkColumnSql = "SHOW COLUMNS FROM `users` LIKE 'admin_id'";
        $columnExists = $mysqli->query($checkColumnSql);
        if ($columnExists && $columnExists->num_rows == 0) {
            $alterSql = "ALTER TABLE `users` 
                         ADD COLUMN `admin_id` INT NULL AFTER `message`,
                         ADD CONSTRAINT `fk_admin_id` FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE SET NULL";
            $mysqli->query($alterSql);
        }

        $checkDeptSql = "SHOW COLUMNS FROM `users` LIKE 'department'";
        $deptExists = $mysqli->query($checkDeptSql);
        if ($deptExists && $deptExists->num_rows == 0) {
            $alterDeptSql = "ALTER TABLE `users` ADD COLUMN `department` VARCHAR(50) DEFAULT 'All' AFTER `email`";
            $mysqli->query($alterDeptSql);
        }

        $checkUrgentSql = "SHOW COLUMNS FROM `users` LIKE 'is_urgent'";
        $urgentExists = $mysqli->query($checkUrgentSql);
        if ($urgentExists && $urgentExists->num_rows == 0) {
            $alterUrgentSql = "ALTER TABLE `users` ADD COLUMN `is_urgent` TINYINT(1) DEFAULT 0 AFTER `message`";
            $mysqli->query($alterUrgentSql);
        }

        $checkAttachSql = "SHOW COLUMNS FROM `users` LIKE 'attachment_path'";
        $attachExists = $mysqli->query($checkAttachSql);
        if ($attachExists && $attachExists->num_rows == 0) {
            $alterAttachSql = "ALTER TABLE `users` ADD COLUMN `attachment_path` VARCHAR(255) NULL AFTER `is_urgent`";
            $mysqli->query($alterAttachSql);
        }

        // Insert default admin if no admins exist
        $result = $mysqli->query("SELECT COUNT(*) FROM admins");
        if ($result && $result->fetch_row()[0] == 0) {
            $defaultUser = 'admin';
            $defaultPass = password_hash('password123', PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");
            if ($stmt) {
                $stmt->bind_param("ss", $defaultUser, $defaultPass);
                $stmt->execute();
                $stmt->close();
            }
        }

        return $mysqli;
    } catch (Throwable $e) {
        die("<div style='font-family: Inter, Arial, sans-serif; max-width: 650px; margin: 60px auto; padding: 28px; border: 1px solid #fecaca; background: #fef2f2; border-radius: 16px; color: #991b1b; line-height: 1.6; box-shadow: 0 10px 25px rgba(0,0,0,0.05);'>"
            . "<h2 style='margin-top:0; color: #7f1d1d;'>⚠️ Database Connection Failed</h2>"
            . "<p><strong>Error Details:</strong> " . htmlspecialchars($e->getMessage()) . "</p>"
            . "<hr style='border: 0; border-top: 1px solid #fca5a5; margin: 18px 0;'>"
            . "<h3 style='margin-bottom: 8px; color: #7f1d1d;'>How to Fix on Vercel:</h3>"
            . "<p>Vercel runs on cloud serverless functions and cannot connect to local <code>127.0.0.1</code> (XAMPP). You need a cloud MySQL database (e.g. Aiven, TiDB, Clever Cloud, Railway, PlanetScale, or Supabase MySQL).</p>"
            . "<p>Set the following variables in <strong>Vercel Dashboard &rarr; Settings &rarr; Environment Variables</strong>:</p>"
            . "<ul style='margin-left: 20px; font-family: monospace; font-size: 0.95rem;'>"
            . "<li><strong>DB_HOST</strong> (e.g. mysql-xxx.aivencloud.com)</li>"
            . "<li><strong>DB_USER</strong></li>"
            . "<li><strong>DB_PASS</strong></li>"
            . "<li><strong>DB_NAME</strong> (e.g. notice_board)</li>"
            . "<li><strong>DB_PORT</strong> (e.g. 3306)</li>"
            . "</ul>"
            . "</div>");
    }
}
