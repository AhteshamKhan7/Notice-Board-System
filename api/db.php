<?php
// MySQL / SQLite dual-mode connection handler.
// Automatically falls back to SQLite if MySQL host is unavailable or times out.

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$dbName = getenv('DB_NAME') ?: 'notice_board';
$dbPort = getenv('DB_PORT') ? (int)getenv('DB_PORT') : 3306;

class SQLiteStmt {
    private $stmt;
    private $params = [];

    public function __construct($stmt) {
        $this->stmt = $stmt;
    }

    public function bind_param($types, ...$vars) {
        $this->params = $vars;
        return true;
    }

    public function execute() {
        try {
            return $this->stmt->execute($this->params);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function get_result() {
        return new SQLiteResult($this->stmt);
    }

    public function store_result() {
        return true;
    }

    public function close() {
        return true;
    }

    public function __get($name) {
        if ($name === 'num_rows') {
            return $this->stmt->rowCount();
        }
        return 0;
    }
}

class SQLiteResult {
    private $rows = [];
    private $index = 0;

    public function __construct($stmt) {
        if ($stmt instanceof PDOStatement) {
            $this->rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function fetch_assoc() {
        if ($this->index < count($this->rows)) {
            return $this->rows[$this->index++];
        }
        return null;
    }

    public function fetch_row() {
        $assoc = $this->fetch_assoc();
        return $assoc ? array_values($assoc) : null;
    }

    public function __get($name) {
        if ($name === 'num_rows') {
            return count($this->rows);
        }
        return 0;
    }
}

class SQLiteDB {
    private $pdo;
    public $error = '';
    public $connect_error = null;

    public function __construct($dbFile) {
        $this->pdo = new PDO('sqlite:' . $dbFile);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec("PRAGMA foreign_keys = ON;");
    }

    public function query($sql) {
        try {
            $sql = str_replace('INT AUTO_INCREMENT PRIMARY KEY', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
            $sql = str_replace('TINYINT(1)', 'INTEGER', $sql);
            $sql = str_replace('ENGINE=InnoDB DEFAULT CHARSET=utf8mb4', '', $sql);
            $sql = preg_replace('/AFTER `\w+`/', '', $sql);
            $sql = preg_replace('/ADD CONSTRAINT `\w+` FOREIGN KEY [^;]+/', '', $sql);

            $stmt = $this->pdo->query($sql);
            if (!$stmt) return false;
            return new SQLiteResult($stmt);
        } catch (Throwable $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function prepare($sql) {
        try {
            $sql = str_replace('INT AUTO_INCREMENT PRIMARY KEY', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
            $stmt = $this->pdo->prepare($sql);
            return new SQLiteStmt($stmt);
        } catch (Throwable $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function select_db($name) { return true; }
    public function set_charset($charset) { return true; }
    public function close() { return true; }
}

function initTables($db) {
    // 1. Admins Table
    $db->query("CREATE TABLE IF NOT EXISTS admins (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 2. Users Table
    $db->query("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        department VARCHAR(50) DEFAULT 'All',
        message TEXT NOT NULL,
        is_urgent INTEGER DEFAULT 0,
        attachment_path VARCHAR(255) NULL,
        admin_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Insert default admin if no admins exist
    $res = $db->query("SELECT COUNT(*) FROM admins");
    if ($res) {
        $row = $res->fetch_row();
        if ($row && $row[0] == 0) {
            $defaultUser = 'admin';
            $defaultPass = password_hash('password123', PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");
            if ($stmt) {
                $stmt->bind_param("ss", $defaultUser, $defaultPass);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}

function dbConnect() {
    global $dbHost, $dbUser, $dbPass, $dbName, $dbPort;

    mysqli_report(MYSQLI_REPORT_OFF);

    // Try MySQL if DB_HOST is explicitly configured to a remote/custom host
    $envHost = getenv('DB_HOST');
    $hasCustomHost = ($envHost && $envHost !== '127.0.0.1' && $envHost !== 'localhost');

    if ($hasCustomHost) {
        try {
            $mysqli = mysqli_init();
            if ($mysqli) {
                $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 1);
                $flags = 0;
                $hostLower = strtolower($dbHost);
                if (getenv('DB_SSL') === 'true' || strpos($hostLower, 'aiven') !== false || strpos($hostLower, 'tidb') !== false || strpos($hostLower, 'clever') !== false) {
                    $mysqli->ssl_set(NULL, NULL, NULL, NULL, NULL);
                    $flags = MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
                }

                $connected = @$mysqli->real_connect($dbHost, $dbUser, $dbPass, '', (int)$dbPort, NULL, $flags);
                if (!$connected && $flags !== 0) {
                    $connected = @$mysqli->real_connect($dbHost, $dbUser, $dbPass, '', (int)$dbPort);
                }

                if ($connected) {
                    $mysqli->set_charset('utf8mb4');
                    try {
                        $mysqli->select_db($dbName);
                    } catch (Throwable $e) {
                        $mysqli->query("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                        $mysqli->select_db($dbName);
                    }
                    initTables($mysqli);
                    return $mysqli;
                }
            }
        } catch (Throwable $e) {
            // Connection failed/timed out, fall through to SQLite
        }
    }

    // Zero-Setup SQLite Fallback (Instant, 0ms latency)
    $dbDir = sys_get_temp_dir();
    $dbFile = $dbDir . '/notice_board.sqlite';
    $sqlite = new SQLiteDB($dbFile);
    initTables($sqlite);
    return $sqlite;
}
