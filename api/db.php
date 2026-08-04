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
        $mysqli = mysqli_init();
        if (!$mysqli) {
            throw new Exception('mysqli_init failed');
        }

        // Set 8-second connection timeout
        $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 8);

        // Enable SSL for cloud hosts (Aiven, TiDB, Clever Cloud, etc.)
        $flags = 0;
        $hostLower = strtolower($dbHost);
        if (getenv('DB_SSL') === 'true' || strpos($hostLower, 'aiven') !== false || strpos($hostLower, 'tidb') !== false || strpos($hostLower, 'clever') !== false) {
            $mysqli->ssl_set(NULL, NULL, NULL, NULL, NULL);
            $flags = MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
        }

        $connected = @$mysqli->real_connect($dbHost, $dbUser, $dbPass, '', (int)$dbPort, NULL, $flags);
        
        // Fallback retry without SSL if initial SSL attempt failed
        if (!$connected && $flags !== 0) {
            $connected = @$mysqli->real_connect($dbHost, $dbUser, $dbPass, '', (int)$dbPort);
        }

        if (!$connected) {
            throw new Exception('Connection to MySQL failed: ' . ($mysqli->connect_error ?: 'Connection timed out or refused.'));
        }

        $mysqli->set_charset('utf8mb4');

        try {
            $mysqli->select_db($dbName);
        } catch (Throwable $e) {
            $createDbSql = "CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            $mysqli->query($createDbSql);
            $mysqli->select_db($dbName);
        }

        // 1. Create admins table first
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

        // 3. ALTER TABLE for existing users table
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
        die("<div style='font-family: Inter, Arial, sans-serif; max-width: 680px; margin: 60px auto; padding: 28px; border: 1px solid #fecaca; background: #fef2f2; border-radius: 16px; color: #991b1b; line-height: 1.6; box-shadow: 0 10px 25px rgba(0,0,0,0.05);'>"
            . "<h2 style='margin-top:0; color: #7f1d1d;'>⚠️ Database Connection Error</h2>"
            . "<p><strong>Error Details:</strong> " . htmlspecialchars($e->getMessage()) . "</p>"
            . "<hr style='border: 0; border-top: 1px solid #fca5a5; margin: 18px 0;'>"
            . "<h3 style='margin-bottom: 8px; color: #7f1d1d;'>Troubleshooting 'Connection Timed Out':</h3>"
            . "<ol style='margin-left: 20px; font-size: 0.95rem;'>"
            . "<li><strong>IP Whitelist / Firewall:</strong> In your cloud database dashboard (Aiven, TiDB, Clever Cloud, Railway), check <em>IP Allow List</em> / <em>Firewall Rules</em> and set it to <code>0.0.0.0/0</code> (Allow all incoming IP addresses).</li>"
            . "<li><strong>Check DB_PORT:</strong> Ensure your <code>DB_PORT</code> in Vercel matches your cloud host (e.g. Aiven uses custom ports like <code>25681</code>, not standard <code>3306</code>).</li>"
            . "<li><strong>Check DB_HOST & User/Pass:</strong> Make sure there are no trailing spaces or typos in your Vercel Environment Variables.</li>"
            . "</ol>"
            . "</div>");
    }
}
